<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();

// Wajib login untuk mendaftar tiket
$currentUser = ViewHelper::requireLogin('USER');

$eventId = trim($_GET['event_id'] ?? $_POST['event_id'] ?? '');
$categoryId = trim($_GET['category_id'] ?? $_POST['category_id'] ?? '');

if (empty($eventId)) {
    // Ambil default event pertama
    $stmt = $db->query("SELECT id FROM events WHERE status_verifikasi IN ('TERBIT', 'PUBLISHED', 'DISETUJUI') ORDER BY created_at DESC LIMIT 1");
    $eventId = $stmt->fetchColumn() ?: '';
}

if (empty($eventId)) {
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

// Ambil data user lengkap
$stmtUserData = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmtUserData->execute(['id' => $currentUser['id']]);
$userData = $stmtUserData->fetch() ?: $currentUser;

// Ambil kategori tiket terpilih jika ada
$selectedCategory = null;
if (!empty($categoryId)) {
    $stmtCat = $db->prepare("SELECT * FROM ticket_categories WHERE id = :id AND event_id = :event_id");
    $stmtCat->execute(['id' => $categoryId, 'event_id' => $eventId]);
    $selectedCategory = $stmtCat->fetch();
}

$errorMessage = '';
$sisaKuota = max(0, (int)$event['kuota'] - (int)$event['total_pendaftar']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noWhatsapp = trim($_POST['no_whatsapp'] ?? '');
    $institusi = trim($_POST['institusi'] ?? '');
    $catId = trim($_POST['category_id'] ?? '');

    if ($sisaKuota <= 0) {
        $errorMessage = 'Maaf, kuota tiket untuk acara ini sudah penuh.';
    } else {
        try {
            $db->beginTransaction();

            // Cek apakah user sudah terdaftar
            $stmtCheck = $db->prepare("
                SELECT id FROM tickets 
                WHERE event_id = :event_id AND user_id = :user_id AND status_tiket = 'AKTIF'
            ");
            $stmtCheck->execute([
                'event_id' => $eventId,
                'user_id' => $currentUser['id']
            ]);
            $existing = $stmtCheck->fetch();

            if ($existing) {
                $db->rollBack();
                $errorMessage = 'Anda sudah terdaftar dan memiliki tiket aktif untuk acara ini.';
            } else {
                // Perbarui data user jika diisi
                if (!empty($noWhatsapp) || !empty($institusi)) {
                    $stmtUpdateUser = $db->prepare("
                        UPDATE users 
                        SET no_whatsapp = COALESCE(NULLIF(:no_whatsapp, ''), no_whatsapp),
                            institusi = COALESCE(NULLIF(:institusi, ''), institusi),
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = :id
                    ");
                    $stmtUpdateUser->execute([
                        'no_whatsapp' => $noWhatsapp,
                        'institusi' => $institusi,
                        'id' => $currentUser['id']
                    ]);
                }

                // Tentukan jenis tiket
                $jenisTiket = 'Umum';
                $ticketCatId = null;
                if (!empty($catId)) {
                    $stmtFindCat = $db->prepare("SELECT id, nama_kategori FROM ticket_categories WHERE id = :id");
                    $stmtFindCat->execute(['id' => $catId]);
                    $fc = $stmtFindCat->fetch();
                    if ($fc) {
                        $jenisTiket = $fc['nama_kategori'];
                        $ticketCatId = $fc['id'];
                    }
                }

                // Generate kode tiket unik: TXR-YYYYMMDD-XXXX
                $dateStr = date('Ymd');
                $randomCode = strtoupper(bin2hex(random_bytes(2)));
                $kodeTiket = "TXR-{$dateStr}-{$randomCode}";

                $stmtInsertTicket = $db->prepare("
                    INSERT INTO tickets (event_id, ticket_category_id, user_id, kode_tiket, jenis_tiket, status_tiket, status_kehadiran)
                    VALUES (:event_id, :ticket_category_id, :user_id, :kode_tiket, :jenis_tiket, 'AKTIF', FALSE)
                    RETURNING id
                ");
                $stmtInsertTicket->execute([
                    'event_id' => $eventId,
                    'ticket_category_id' => $ticketCatId,
                    'user_id' => $currentUser['id'],
                    'kode_tiket' => $kodeTiket,
                    'jenis_tiket' => $jenisTiket
                ]);
                $newTicketId = $stmtInsertTicket->fetchColumn();

                $db->commit();

                header('Location: ' . BASE_URL . '/tiket-digital?id=' . $newTicketId . '&success=1');
                exit;
            }
        } catch (Exception $e) {
            $db->rollBack();
            $errorMessage = 'Gagal memproses pendaftaran: ' . $e->getMessage();
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
                        <h2 class="card-title">Metode Registrasi</h2>
                        <div class="stack gap-10">
                            <div class="option option-selected">
                                <b>Tiket Terbuka (Gratis)</b>
                                <span class="badge badge-purple"><span class="badge-dot"></span>Tanpa Biaya</span>
                            </div>
                        </div>
                        <p class="help" style="margin-top:8px">E-Ticket QR Code resmi langsung diterbitkan setelah Anda menekan tombol di bawah.</p>
                    </div>

                    <div class="check-row">
                        <input type="checkbox" id="agree" required checked>
                        <label for="agree" style="font-size:14px;color:#4B5170">Data yang saya isi sudah benar dan saya menyetujui Syarat &amp; Ketentuan kegiatan.</label>
                    </div>

                    <div class="row">
                        <a href="<?= BASE_URL ?>/detail-acara?id=<?= $event['id'] ?>" class="button button-large secondary-button" style="text-decoration:none">Kembali</a>
                        <button type="submit" class="button button-large primary-button" style="cursor:pointer">
                            Konfirmasi &amp; Dapatkan Tiket
                        </button>
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
                            <span>Jumlah</span>
                            <b>1 Tiket</b>
                        </div>
                        <div class="summary-row summary-total">
                            <span>Total Biaya</span>
                            <b style="color:#4B34DB">Gratis</b>
                        </div>
                    </div>
                </aside>
            </div>
        </main>

        <footer class="footer">
            <div class="container tx-style-a3d12b9b83">
                <div>
                    <div class="logo">
                        <svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                            <path d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z" fill="#4B34DB" />
                            <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" fill="none" />
                        </svg>
                        <span class="logo-text">tixora</span>
                    </div>
                    <p class="footer-text">Platform acara dan tiket digital.</p>
                </div>
                <nav class="footer-links">
                    <a href="#">Bantuan</a>
                    <a href="#">Syarat &amp; ketentuan</a>
                    <a href="#">Kebijakan privasi</a>
                </nav>
            </div>
        </footer>
    </div>
</body>

</html>
