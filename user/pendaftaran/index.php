<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();
PaymentHelper::ensureSchema($db);
PaymentHelper::expireOverdue($db);

// Wajib login untuk mendaftar tiket (setelah login kembali ke halaman ini)
$currentUser = ViewHelper::requireLogin('USER');

const EVENT_OPEN_STATUSES = ['TERBIT', 'PUBLISHED', 'DISETUJUI'];

$eventId = trim($_GET['event_id'] ?? $_POST['event_id'] ?? '');
$categoryId = trim($_GET['category_id'] ?? $_POST['category_id'] ?? '');

if (!PaymentHelper::isUuid($eventId)) {
    header('Location: ' . BASE_URL . '/jelajah-acara');
    exit;
}

// Ambil info detail event
$stmtEvent = $db->prepare("
    SELECT e.*, u.nama as owner_nama,
           (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') as total_pendaftar
    FROM events e
    JOIN users u ON e.owner_id = u.id
    WHERE e.id = :id
");
$stmtEvent->execute(['id' => $eventId]);
$event = $stmtEvent->fetch();

if (!$event) {
    header('Location: ' . BASE_URL . '/jelajah-acara');
    exit;
}

// Hanya acara terbit dan belum selesai yang boleh didaftar
$eventTerbuka = in_array($event['status_verifikasi'], EVENT_OPEN_STATUSES, true)
    && strtotime($event['tanggal_selesai']) > time();

// Ambil data user lengkap
$stmtUserData = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmtUserData->execute(['id' => $currentUser['id']]);
$userData = $stmtUserData->fetch() ?: $currentUser;

// Kategori tiket beserta kursi yang sudah terpakai (tiket aktif + menunggu pembayaran)
$stmtCats = $db->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM tickets t WHERE t.ticket_category_id = c.id AND t.status_tiket != 'BATAL') AS terpakai
    FROM ticket_categories c
    WHERE c.event_id = :event_id
    ORDER BY c.created_at ASC
");
$stmtCats->execute(['event_id' => $eventId]);
$categories = $stmtCats->fetchAll();

// Tentukan kategori terpilih: harus milik acara ini; jika tidak valid pakai kategori pertama yang masih ada kursinya
$selectedCategory = null;
foreach ($categories as $cat) {
    if ($cat['id'] === $categoryId) {
        $selectedCategory = $cat;
        break;
    }
}
if ($selectedCategory === null && !empty($categories)) {
    foreach ($categories as $cat) {
        if ((int)$cat['kuota'] - (int)$cat['terpakai'] > 0) {
            $selectedCategory = $cat;
            break;
        }
    }
    $selectedCategory = $selectedCategory ?? $categories[0];
}
$categoryId = $selectedCategory['id'] ?? '';
$harga = $selectedCategory ? (float)$selectedCategory['harga'] : 0.0;
$berbayar = $harga > 0;

$errorMessage = '';
$sisaKuotaEvent = max(0, (int)$event['kuota'] - (int)$event['total_pendaftar']);
$sisaKuotaKategori = $selectedCategory
    ? max(0, (int)$selectedCategory['kuota'] - (int)$selectedCategory['terpakai'])
    : $sisaKuotaEvent;
$sisaKuota = min($sisaKuotaEvent, $sisaKuotaKategori);

/** Buat kode tiket unik: TXR-YYYYMMDD-XXXX */
function generateUniqueTicketCode(PDO $db): string
{
    $stmt = $db->prepare("SELECT 1 FROM tickets WHERE kode_tiket = :kode");
    for ($i = 0; $i < 10; $i++) {
        $kode = 'TXR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $stmt->execute(['kode' => $kode]);
        if (!$stmt->fetchColumn()) {
            return $kode;
        }
    }
    return 'TXR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noWhatsapp = trim($_POST['no_whatsapp'] ?? '');
    $institusi = trim($_POST['institusi'] ?? '');

    if (!ViewHelper::checkCsrf()) {
        $errorMessage = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (!$eventTerbuka) {
        $errorMessage = 'Pendaftaran untuk acara ini sudah ditutup.';
    } elseif (!preg_match('/^[0-9+\-\s]{8,20}$/', $noWhatsapp)) {
        $errorMessage = 'Nomor WhatsApp tidak valid. Gunakan angka, contoh: 081234567890.';
    } elseif (!empty($categories) && $selectedCategory === null) {
        $errorMessage = 'Pilih kategori tiket terlebih dahulu.';
    } else {
        try {
            $db->beginTransaction();

            // Kunci baris acara agar pemeriksaan kuota tidak balapan dengan pendaftar lain
            $stmtLock = $db->prepare("SELECT id, kuota FROM events WHERE id = :id FOR UPDATE");
            $stmtLock->execute(['id' => $eventId]);
            $lockedEvent = $stmtLock->fetch();

            // Sudah punya tiket (aktif atau menunggu pembayaran) untuk acara ini?
            $stmtCheck = $db->prepare("
                SELECT t.id, t.status_tiket,
                       (SELECT p.order_id FROM payments p WHERE p.ticket_id = t.id AND p.status = 'MENUNGGU'
                        ORDER BY p.created_at DESC LIMIT 1) AS order_id
                FROM tickets t
                WHERE t.event_id = :event_id AND t.user_id = :user_id
                  AND t.status_tiket IN ('AKTIF', 'MENUNGGU_PEMBAYARAN')
                LIMIT 1
            ");
            $stmtCheck->execute(['event_id' => $eventId, 'user_id' => $currentUser['id']]);
            $existing = $stmtCheck->fetch();

            if ($existing) {
                $db->rollBack();
                if ($existing['status_tiket'] === 'MENUNGGU_PEMBAYARAN' && !empty($existing['order_id'])) {
                    // Lanjutkan pesanan yang belum dibayar, bukan membuat yang baru
                    header('Location: ' . BASE_URL . '/pembayaran?order=' . urlencode($existing['order_id']));
                    exit;
                }
                $errorMessage = 'Anda sudah terdaftar dan memiliki tiket aktif untuk acara ini.';
            } else {
                $stmtCount = $db->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = :id AND status_tiket != 'BATAL'");
                $stmtCount->execute(['id' => $eventId]);
                $terpakaiEvent = (int)$stmtCount->fetchColumn();

                $kategoriPenuh = false;
                $hargaFinal = 0.0;
                $jenisTiket = 'Umum';
                $ticketCatId = null;

                if ($selectedCategory) {
                    $stmtCat = $db->prepare("SELECT id, nama_kategori, kuota, harga FROM ticket_categories WHERE id = :id AND event_id = :event_id FOR UPDATE");
                    $stmtCat->execute(['id' => $categoryId, 'event_id' => $eventId]);
                    $lockedCat = $stmtCat->fetch();
                    if (!$lockedCat) {
                        throw new RuntimeException('Kategori tiket tidak ditemukan.');
                    }
                    $stmtCatCount = $db->prepare("SELECT COUNT(*) FROM tickets WHERE ticket_category_id = :id AND status_tiket != 'BATAL'");
                    $stmtCatCount->execute(['id' => $lockedCat['id']]);
                    $kategoriPenuh = (int)$stmtCatCount->fetchColumn() >= (int)$lockedCat['kuota'];
                    // Harga selalu diambil ulang dari database, bukan dari input form
                    $hargaFinal = (float)$lockedCat['harga'];
                    $jenisTiket = $lockedCat['nama_kategori'];
                    $ticketCatId = $lockedCat['id'];
                }

                if ($terpakaiEvent >= (int)$lockedEvent['kuota']) {
                    $db->rollBack();
                    $errorMessage = 'Maaf, kuota tiket untuk acara ini sudah penuh.';
                } elseif ($kategoriPenuh) {
                    $db->rollBack();
                    $errorMessage = 'Maaf, kuota untuk kategori tiket ini sudah penuh. Pilih kategori lain.';
                } else {
                    // Perbarui data user jika diisi
                    $db->prepare("
                        UPDATE users
                        SET no_whatsapp = COALESCE(NULLIF(:no_whatsapp, ''), no_whatsapp),
                            institusi = COALESCE(NULLIF(:institusi, ''), institusi),
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = :id
                    ")->execute([
                        'no_whatsapp' => $noWhatsapp,
                        'institusi' => $institusi,
                        'id' => $currentUser['id'],
                    ]);

                    $statusTiket = $hargaFinal > 0 ? 'MENUNGGU_PEMBAYARAN' : 'AKTIF';

                    $stmtInsertTicket = $db->prepare("
                        INSERT INTO tickets (event_id, ticket_category_id, user_id, kode_tiket, jenis_tiket, status_tiket, status_kehadiran)
                        VALUES (:event_id, :ticket_category_id, :user_id, :kode_tiket, :jenis_tiket, :status_tiket, FALSE)
                        RETURNING id
                    ");
                    $stmtInsertTicket->execute([
                        'event_id' => $eventId,
                        'ticket_category_id' => $ticketCatId,
                        'user_id' => $currentUser['id'],
                        'kode_tiket' => generateUniqueTicketCode($db),
                        'jenis_tiket' => $jenisTiket,
                        'status_tiket' => $statusTiket,
                    ]);
                    $newTicketId = $stmtInsertTicket->fetchColumn();

                    $orderId = null;
                    if ($hargaFinal > 0) {
                        $orderId = PaymentHelper::generateOrderId();
                        $db->prepare("
                            INSERT INTO payments (ticket_id, user_id, order_id, jumlah, status, batas_waktu)
                            VALUES (:ticket_id, :user_id, :order_id, :jumlah, 'MENUNGGU',
                                    CURRENT_TIMESTAMP + make_interval(hours => :jam))
                        ")->execute([
                            'ticket_id' => $newTicketId,
                            'user_id' => $currentUser['id'],
                            'order_id' => $orderId,
                            'jumlah' => $hargaFinal,
                            'jam' => PaymentHelper::PAYMENT_WINDOW_HOURS,
                        ]);
                    }

                    $db->commit();

                    if ($orderId !== null) {
                        header('Location: ' . BASE_URL . '/pembayaran?order=' . urlencode($orderId));
                    } else {
                        header('Location: ' . BASE_URL . '/tiket-digital?id=' . $newTicketId . '&success=1');
                    }
                    exit;
                }
            }
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[TIXORA] Pendaftaran gagal: ' . $e->getMessage());
            $errorMessage = 'Pendaftaran gagal diproses. Silakan coba lagi beberapa saat lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Pendaftaran <?= htmlspecialchars($event['judul_event']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame tx-style-f60fce6ec7">
        <?php ViewHelper::renderNavbar('tiket'); ?>

        <main class="page-body tx-style-e5d5285078">
            <h1 class="page-title tx-style-49f14f8f83">Pendaftaran Acara</h1>

            <?php if ($errorMessage): ?>
                <div class="alert alert-error" style="margin-bottom:20px">
                    <?= htmlspecialchars($errorMessage) ?>
                    <?php if (str_contains($errorMessage, 'sudah terdaftar')): ?>
                        <div style="margin-top:8px">
                            <a href="<?= BASE_URL ?>/tiket-saya" class="button secondary-button small-button">Buka Tiket Saya</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="two-col">
                <form method="POST" action="" class="col-main stack gap-24">
                    <?= ViewHelper::csrfField() ?>
                    <input type="hidden" name="event_id" value="<?= htmlspecialchars($event['id']) ?>">
                    <input type="hidden" name="category_id" value="<?= htmlspecialchars($categoryId) ?>">

                    <div class="card">
                        <h2 class="card-title">Data Peserta</h2>
                        <div class="stack gap-16">
                            <div class="two-fields">
                                <div>
                                    <label class="field-label">Nama lengkap</label>
                                    <input type="text" class="input" value="<?= htmlspecialchars($userData['nama'] ?? '') ?>" readonly style="background:#F8F8FC">
                                </div>
                                <div>
                                    <label class="field-label">Surel</label>
                                    <input type="email" class="input" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" readonly style="background:#F8F8FC">
                                </div>
                            </div>
                            <div class="two-fields">
                                <div>
                                    <label class="field-label">Nomor WhatsApp</label>
                                    <input type="text" name="no_whatsapp" class="input" placeholder="081234567890" value="<?= htmlspecialchars($userData['no_whatsapp'] ?? '') ?>" required>
                                </div>
                                <div>
                                    <label class="field-label">Institusi / Instansi</label>
                                    <input type="text" name="institusi" class="input" placeholder="Nama kampus atau instansi" value="<?= htmlspecialchars($userData['institusi'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h2 class="card-title"><?= $berbayar ? 'Pembayaran' : 'Metode Registrasi' ?></h2>
                        <div class="stack gap-10">
                            <div class="option option-selected">
                                <?php if ($berbayar): ?>
                                    <b>Tiket Berbayar</b>
                                    <span class="badge badge-purple"><span class="badge-dot"></span><?= PaymentHelper::rupiah($harga) ?></span>
                                <?php else: ?>
                                    <b>Tiket Terbuka (Gratis)</b>
                                    <span class="badge badge-purple"><span class="badge-dot"></span>Tanpa Biaya</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($berbayar): ?>
                            <p class="help" style="margin-top:8px">Kursi Anda ditahan selama <?= PaymentHelper::PAYMENT_WINDOW_HOURS ?> jam. Pilih metode dan selesaikan pembayaran di langkah berikutnya; tiket QR aktif setelah pembayaran berhasil.</p>
                        <?php else: ?>
                            <p class="help" style="margin-top:8px">E-Ticket QR Code resmi langsung diterbitkan setelah Anda menekan tombol di bawah.</p>
                        <?php endif; ?>
                        <?php if ($selectedCategory && $selectedCategory['wajib_ktm']): ?>
                            <p class="help" style="margin-top:8px">Kategori ini wajib membawa KTM aktif saat registrasi ulang di lokasi.</p>
                        <?php endif; ?>
                    </div>

                    <div class="check-row">
                        <input type="checkbox" id="agree" required checked>
                        <label for="agree" style="font-size:14px;color:#4B5170">Data yang saya isi sudah benar dan saya menyetujui Syarat &amp; Ketentuan kegiatan.</label>
                    </div>

                    <div class="row">
                        <a href="<?= BASE_URL ?>/detail-acara?id=<?= $event['id'] ?>" class="button button-large secondary-button" style="text-decoration:none">Kembali</a>
                        <?php if ($eventTerbuka && $sisaKuota > 0): ?>
                            <button type="submit" class="button button-large primary-button" style="cursor:pointer">
                                <?= $berbayar ? 'Lanjut ke Pembayaran' : 'Konfirmasi &amp; Dapatkan Tiket' ?>
                            </button>
                        <?php else: ?>
                            <button type="button" disabled class="button button-large disabled-button">
                                <?= $eventTerbuka ? 'Tiket Habis' : 'Pendaftaran Ditutup' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </form>

                <aside class="col-side card">
                    <div class="poster poster-indigo tx-style-63c893b1c4">
                        <div class="poster-art"><span class="art-circle-cyan"></span><span class="art-ring-dark"></span></div>
                        <span class="poster-title poster-title-card"><?= htmlspecialchars(mb_strimwidth($event['judul_event'], 0, 24, '...')) ?></span>
                    </div>
                    <h3 class="tx-style-e9c0b256f0" style="margin-top:12px"><?= htmlspecialchars($event['judul_event']) ?></h3>
                    <p class="slate tx-style-95668ca799">
                        <?= date('d M Y', strtotime($event['tanggal_mulai'])) ?> · <?= htmlspecialchars($event['nama_tempat']) ?>, <?= htmlspecialchars($event['kota']) ?>
                    </p>
                    <div class="stack gap-10 tx-style-b8fb61d376" style="margin-top:16px;border-top:1px solid #E4E4EE;padding-top:12px">
                        <div class="summary-row">
                            <span>Kategori</span>
                            <b><?= htmlspecialchars($selectedCategory['nama_kategori'] ?? 'Umum / Terbuka') ?></b>
                        </div>
                        <div class="summary-row">
                            <span>Harga</span>
                            <b><?= $berbayar ? PaymentHelper::rupiah($harga) : 'Gratis' ?></b>
                        </div>
                        <div class="summary-row">
                            <span>Jumlah</span>
                            <b>1 Tiket</b>
                        </div>
                        <div class="summary-row summary-total">
                            <span><?= $berbayar ? 'Total Bayar' : 'Total Biaya' ?></span>
                            <b style="color:#4B34DB"><?= $berbayar ? PaymentHelper::rupiah($harga) : 'Gratis' ?></b>
                        </div>
                    </div>
                </aside>
            </div>
        </main>

        <?php ViewHelper::renderFooter(); ?>
    </div>
</body>

</html>
