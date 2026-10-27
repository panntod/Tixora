<?php
declare(strict_types=1);

/**
 * Halaman e-ticket publik — tujuan dari QR Code.
 * Tidak perlu login (dibuka dari kamera HP / aplikasi scanner), tetapi wajib membawa
 * tanda tangan (parameter "s") yang valid agar data peserta tidak bisa ditebak.
 */
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/BannerHelper.php' : __DIR__ . '/../../api/helpers/BannerHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/TicketLinkHelper.php' : __DIR__ . '/../../api/helpers/TicketLinkHelper.php';

$db = ViewHelper::getDb();
$kode = trim((string)($_GET['code'] ?? ''));
$sig = trim((string)($_GET['s'] ?? ''));

$ticket = null;
if (TicketLinkHelper::verify($kode, $sig)) {
    $stmt = $db->prepare("
        SELECT t.*, e.judul_event, e.kategori, e.banner_url, e.tanggal_mulai, e.nama_tempat, u.nama AS user_nama
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        JOIN users u ON t.user_id = u.id
        WHERE t.kode_tiket = :code
    ");
    $stmt->execute(['code' => $kode]);
    $ticket = $stmt->fetch() ?: null;
}

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

if (!$ticket) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
        <title>TIXORA — Tiket tidak valid</title>
        <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
    </head>
    <body>
        <main style="max-width:390px;margin:96px auto;padding:0 16px;text-align:center;font-family:Arial,sans-serif">
            <h1 style="font-size:20px;color:#0E1128">Tiket tidak valid</h1>
            <p style="color:#5F6483">Kode QR ini tidak dikenali atau tautannya sudah diubah. Pastikan Anda memindai QR dari e-ticket TIXORA yang asli.</p>
            <a href="<?= BASE_URL ?>/" class="button button-large secondary-button" style="text-decoration:none;display:inline-block;margin-top:16px">Ke beranda</a>
        </main>
    </body>
    </html>
    <?php
    exit;
}

$kickerDate = strtoupper(date('D · d M Y', strtotime($ticket['tanggal_mulai'])));
$isCancelled = $ticket['status_tiket'] === 'BATAL';
$isPending = $ticket['status_tiket'] === 'MENUNGGU_PEMBAYARAN';
$isUsed = !$isCancelled && !$isPending && (!empty($ticket['status_kehadiran']) || $ticket['status_tiket'] === 'SUDAH_PAKAI');
$showQr = !$isCancelled && !$isPending;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="robots" content="noindex, nofollow">
    <title>TIXORA — E-Ticket <?= htmlspecialchars($ticket['kode_tiket']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame">
        <header style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;background:#fff;border-bottom:1px solid #E4E4EE">
            <span style="font-weight:700;color:#0E1128">E-Ticket</span>
            <span class="badge badge-purple">TIXORA E-PASS</span>
        </header>

        <main class="ticket-page-main">
            <div class="ticket ticket-fluid">
                <?= BannerHelper::render($ticket, 'ticket', ['kicker' => $kickerDate]) ?>

                <div class="ticket-stub">
                    <span class="ticket-notch-left"></span>
                    <span class="ticket-notch-right"></span>

                    <div class="row-between">
                        <?php if ($isCancelled): ?>
                            <span class="badge badge-error"><span class="badge-dot"></span>Dibatalkan</span>
                        <?php elseif ($isPending): ?>
                            <span class="badge badge-neutral"><span class="badge-dot"></span>Menunggu Pembayaran</span>
                        <?php elseif ($isUsed): ?>
                            <span class="badge badge-neutral"><span class="badge-dot"></span>Sudah Hadir</span>
                        <?php else: ?>
                            <span class="badge badge-success"><span class="badge-dot"></span>Sah / Aktif</span>
                        <?php endif; ?>
                        <span class="ticket-id"><?= htmlspecialchars($ticket['kode_tiket']) ?></span>
                    </div>

                    <?php if ($showQr): ?>
                        <div class="qr-box" style="text-align:center;padding:16px 0">
                            <div id="qr" class="qr-canvas"></div>
                            <span class="help" style="display:block;margin-top:8px">
                                <?= $isUsed ? 'Tiket ini sudah di-check-in dan tidak berlaku lagi' : 'Tunjukkan kode QR ini ke panitia di meja registrasi' ?>
                            </span>
                        </div>
                    <?php endif; ?>

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

            <p class="help" style="text-align:center;margin-top:12px">Kode QR bersifat pribadi dan unik per peserta. Jangan dibagikan kepada orang lain.</p>
        </main>
    </div>

    <script src="<?= BASE_URL ?>/assets/default-banner.js"></script>
    <?php if ($showQr): ?>
        <script src="<?= BASE_URL ?>/assets/qr.js"></script>
        <script>
            TixoraQR.render('#qr', <?= json_encode(TicketLinkHelper::url($ticket['kode_tiket']), JSON_UNESCAPED_SLASHES) ?>, {label: 'QR Code <?= htmlspecialchars($ticket['kode_tiket'], ENT_QUOTES) ?>'});
        </script>
    <?php endif; ?>
</body>

</html>