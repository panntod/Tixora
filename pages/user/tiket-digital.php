<?php

declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../../api/helpers/ViewHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/BannerHelper.php' : __DIR__ . '/../../../api/helpers/BannerHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/TicketLinkHelper.php' : __DIR__ . '/../../api/helpers/TicketLinkHelper.php';
$db = ViewHelper::getDb();
PaymentHelper::ensureSchema($db);
PaymentHelper::expireOverdue($db);

$currentUser = ViewHelper::requireLogin(AuthHelper::ROLE_USER);

$ticketId = trim($_GET['id'] ?? '');
$ticketCode = trim($_GET['code'] ?? '');

$ticket = null;
if (!empty($ticketId) || !empty($ticketCode)) {
    $stmt = $db->prepare("
        SELECT t.*, e.judul_event, e.kategori, e.banner_url, e.tanggal_mulai, e.tanggal_selesai, e.nama_tempat, e.kota, u.nama as user_nama
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        JOIN users u ON t.user_id = u.id
        WHERE (t.id = :id OR t.kode_tiket = :code) AND t.user_id = :user_id
    ");
    $stmt->execute([
        'id' => $ticketId ?: '00000000-0000-0000-0000-000000000000',
        'code' => $ticketCode ?: 'NONE',
        'user_id' => $currentUser['id']
    ]);
    $ticket = $stmt->fetch();
}

if (!$ticket) {
    // Ambil tiket terbaru milik user
    $stmt = $db->prepare("
        SELECT t.*, e.judul_event, e.kategori, e.banner_url, e.tanggal_mulai, e.tanggal_selesai, e.nama_tempat, e.kota, u.nama as user_nama
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        JOIN users u ON t.user_id = u.id
        WHERE t.user_id = :user_id
        ORDER BY t.created_at DESC LIMIT 1
    ");
    $stmt->execute(['user_id' => $currentUser['id']]);
    $ticket = $stmt->fetch();
}

if (!$ticket) {
    header('Location: ' . BASE_URL . '/tiket-saya');
    exit;
}

// Tiket yang belum dibayar: redirect ke halaman pembayaran
if ($ticket['status_tiket'] === 'MENUNGGU_PEMBAYARAN') {
    $stmtOrder = $db->prepare("
        SELECT order_id FROM payments
        WHERE ticket_id = :tid AND user_id = :uid AND status = 'MENUNGGU'
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmtOrder->execute(['tid' => $ticket['id'], 'uid' => $currentUser['id']]);
    $orderId = $stmtOrder->fetchColumn();

    if ($orderId) {
        header('Location: ' . BASE_URL . '/pembayaran?order=' . urlencode($orderId));
        exit;
    }
    // Data inconsistency: tiket MENUNGGU_PEMBAYARAN tanpa payment aktif
    header('Location: ' . BASE_URL . '/tiket-saya');
    exit;
}

$isSuccess = isset($_GET['success']);
// URL QR dihitung SEBELUM HTML dikirim, supaya error konfigurasi tidak memotong halaman.
$qrUrl = '';
$qrError = '';
try {
    $qrUrl = TicketLinkHelper::url($ticket['kode_tiket']);
} catch (Throwable $e) {
    error_log('[TIXORA] Gagal membuat URL QR: ' . $e->getMessage());
    $qrError = 'QR belum dapat dibuat. Hubungi administrator.';
    http_response_code(200);
}

$kickerDate = strtoupper(date('D · d M Y', strtotime($ticket['tanggal_mulai'])));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>TIXORA — E-Ticket <?= htmlspecialchars($ticket['kode_tiket']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame tx-style-3627af4deb">
        <header class="tx-style-7ce961b601"
            style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;background:#fff;border-bottom:1px solid #E4E4EE">
            <a href="<?= BASE_URL ?>/tiket-saya"
                style="display:flex;align-items:center;gap:8px;text-decoration:none;color:#0E1128;font-weight:700">
                <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M12 4l-6 6 6 6M6 10h10" stroke="#0E1128" stroke-width="2" fill="none"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span>Tiket Saya</span>
            </a>
            <span class="badge badge-purple">TIXORA E-PASS</span>
        </header>

        <main class="tx-style-d84e93a035 ticket-page-main">
            <?php if ($isSuccess): ?>
                <div class="alert alert-success" style="margin-bottom:20px;text-align:center">
                    🎉 Pendaftaran Berhasil! E-Ticket Anda telah aktif dan siap digunakan.
                </div>
            <?php endif; ?>

            <div class="ticket ticket-fluid tx-style-d944635d3c">
                <?= BannerHelper::render($ticket, 'ticket', ['kicker' => $kickerDate]) ?>

                <div class="ticket-stub tx-style-2a1b75c911">
                    <span class="ticket-notch-left"></span>
                    <span class="ticket-notch-right"></span>

                    <div class="row-between">
                        <?php if ($ticket['status_tiket'] === 'BATAL'): ?>
                            <span class="badge badge-error"><span class="badge-dot"></span>Dibatalkan</span>
                        <?php elseif ($ticket['status_kehadiran']): ?>
                            <span class="badge badge-neutral"><span class="badge-dot"></span>Sudah Hadir</span>
                        <?php else: ?>
                            <span class="badge badge-success"><span class="badge-dot"></span>Sah / Aktif</span>
                        <?php endif; ?>
                        <span
                            class="ticket-id tx-style-1169661891"><?= htmlspecialchars($ticket['kode_tiket']) ?></span>
                    </div>

                    <div class="qr-box tx-style-f63b87b3fb" style="text-align:center;padding:16px 0">
                        <!-- QR asli: berisi tautan e-ticket bertanda tangan, dirender oleh assets/qr.js -->
                        <div id="qr" class="qr-canvas"><?= $qrError !== '' ? htmlspecialchars($qrError) : '' ?></div>
                        <noscript><span class="help" style="display:block">Aktifkan JavaScript untuk menampilkan kode
                                QR.</span></noscript>
                        <span class="help tx-style-1169661891" style="display:block;margin-top:8px">Tunjukkan kode QR
                            ini ke panitia di meja registrasi</span>
                    </div>

                    <dl class="definition">
                        <div class="wide">
                            <dt>ACARA</dt>
                            <dd><?= htmlspecialchars($ticket['judul_event']) ?></dd>
                        </div>
                        <div>
                            <dt>PESERTA</dt>
                            <dd><?= htmlspecialchars($ticket['user_nama']) ?></dd>
                        </div>
                        <div>
                            <dt>KATEGORI</dt>
                            <dd><?= htmlspecialchars($ticket['jenis_tiket']) ?></dd>
                        </div>
                        <div>
                            <dt>WAKTU</dt>
                            <dd><?= date('H:i', strtotime($ticket['tanggal_mulai'])) ?> WIB</dd>
                        </div>
                        <div>
                            <dt>LOKASI</dt>
                            <dd><?= htmlspecialchars($ticket['nama_tempat']) ?></dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="stack gap-10 tx-style-1b0f4999d2" style="margin-top:20px">
                <button onclick="window.print()" class="button button-large primary-button full-width"
                    style="cursor:pointer">
                    Cetak / Simpan Tiket (PDF)
                </button>
                <a href="<?= BASE_URL ?>/tiket-saya" class="button button-large secondary-button full-width"
                    style="text-align:center;text-decoration:none">
                    Kembali ke Tiket Saya
                </a>
            </div>
            <p class="help tx-style-1294536864" style="text-align:center;margin-top:12px">Kode QR bersifat pribadi dan
                unik per peserta. Jangan dibagikan kepada orang lain.</p>
        </main>
        <footer class="footer">
            <div class="container tx-style-a3d12b9b83">
                <div>
                    <div class="logo">
                        <img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA">
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
    <script src="<?= BASE_URL ?>/assets/default-banner.js"></script>
    <script src="<?= BASE_URL ?>/assets/qr.js"></script>
    <script>
        <?php if ($qrUrl !== ''): ?>TixoraQR.render('#qr', <?= json_encode($qrUrl, JSON_UNESCAPED_SLASHES) ?>, {
            label: 'QR Code <?= htmlspecialchars($ticket['kode_tiket'], ENT_QUOTES) ?>'
        });
        <?php endif; ?>
    </script>
</body>

</html>