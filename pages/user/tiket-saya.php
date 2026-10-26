<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/../../api/helpers/BannerHelper.php';
$db = ViewHelper::getDb();
PaymentHelper::ensureSchema($db);
PaymentHelper::expireOverdue($db);

$currentUser = ViewHelper::requireLogin('USER');

$stmt = $db->prepare("
    SELECT t.*, e.judul_event, e.kategori, e.banner_url, e.tanggal_mulai, e.tanggal_selesai, e.nama_tempat, e.kota,
           (SELECT p.order_id FROM payments p WHERE p.ticket_id = t.id AND p.status = 'MENUNGGU'
            ORDER BY p.created_at DESC LIMIT 1) AS order_menunggu
    FROM tickets t 
    JOIN events e ON t.event_id = e.id 
    WHERE t.user_id = :user_id 
    ORDER BY t.created_at DESC
");
$stmt->execute(['user_id' => $currentUser['id']]);
$allTickets = $stmt->fetchAll();

$upcoming = [];
$completed = [];
$cancelled = [];

$now = date('Y-m-d H:i:s');
foreach ($allTickets as $t) {
    if ($t['status_tiket'] === 'BATAL') {
        $cancelled[] = $t;
    } elseif ($t['status_kehadiran'] || $t['tanggal_mulai'] < $now) {
        $completed[] = $t;
    } else {
        $upcoming[] = $t;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Tiket Saya</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
    <script>
        function filterTickets(tab, btn) {
            document.querySelectorAll('.tab').forEach(b => b.classList.remove('tab-active'));
            btn.classList.add('tab-active');

            const up = document.getElementById('sec-upcoming');
            const comp = document.getElementById('sec-completed');
            const canc = document.getElementById('sec-cancelled');

            if (tab === 'all' || tab === 'upcoming') up.style.display = 'block'; else up.style.display = 'none';
            if (tab === 'all' || tab === 'completed') comp.style.display = 'block'; else comp.style.display = 'none';
            if (tab === 'all' || tab === 'cancelled') canc.style.display = 'block'; else canc.style.display = 'none';
        }
    </script>
</head>

<body>
    <div class="frame tx-style-1f688caae9">
        <?php ViewHelper::renderNavbar('tiket'); ?>

        <main class="tx-style-7aca552fb6">
            <h1 class="page-title tx-style-49f14f8f83">Tiket Saya</h1>

            <div class="tabs">
                <span class="tab tab-active" onclick="filterTickets('upcoming', this)">Akan datang
                    (<?= count($upcoming) ?>)</span>
                <span class="tab" onclick="filterTickets('completed', this)">Selesai (<?= count($completed) ?>)</span>
                <span class="tab" onclick="filterTickets('cancelled', this)">Dibatalkan
                    (<?= count($cancelled) ?>)</span>
                <span class="tab" onclick="filterTickets('all', this)">Semua (<?= count($allTickets) ?>)</span>
            </div>

            <div id="sec-upcoming">
                <?php if (!empty($upcoming)): ?>
                    <?php foreach ($upcoming as $t): ?>
                        <div class="ticket-row">
                            <div style="flex:none">
                                <?= BannerHelper::render($t, 'thumb') ?>
                            </div>
                            <div class="ticket-row-body">
                                <?php if ($t['status_tiket'] === 'MENUNGGU_PEMBAYARAN'): ?>
                                    <span class="badge badge-warning"><span class="badge-dot"></span>Menunggu pembayaran</span>
                                <?php else: ?>
                                    <span class="badge badge-success"><span class="badge-dot"></span>Sah / Aktif</span>
                                <?php endif; ?>
                                <h3 class="ticket-row-title"><?= htmlspecialchars($t['judul_event']) ?></h3>
                                <p class="ticket-row-meta"><?= date('d M Y', strtotime($t['tanggal_mulai'])) ?> ·
                                    <?= htmlspecialchars($t['kode_tiket']) ?> · Kategori:
                                    <?= htmlspecialchars($t['jenis_tiket']) ?></p>
                            </div>
                            <?php if ($t['status_tiket'] === 'MENUNGGU_PEMBAYARAN' && !empty($t['order_menunggu'])): ?>
                                <a href="<?= BASE_URL ?>/pembayaran?order=<?= urlencode($t['order_menunggu']) ?>"
                                    class="button primary-button" style="text-decoration:none">Bayar</a>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/tiket-digital?id=<?= $t['id'] ?>" class="button primary-button"
                                    style="text-decoration:none">Buka Tiket</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div
                        style="padding:32px 16px;text-align:center;background:#fff;border-radius:12px;border:1px solid #E4E4EE;margin-bottom:20px">
                        <p class="slate">Belum ada tiket aktif untuk acara mendatang.</p>
                        <a href="<?= BASE_URL ?>/jelajah-acara" class="button secondary-button"
                            style="margin-top:12px;display:inline-block">Cari Acara Menarik</a>
                    </div>
                <?php endif; ?>
            </div>

            <div id="sec-completed" style="display:none">
                <h2 class="group-label">SELESAI</h2>
                <?php if (!empty($completed)): ?>
                    <?php foreach ($completed as $t): ?>
                        <div class="ticket-row">
                            <div class="is-dimmed" style="flex:none">
                                <?= BannerHelper::render($t, 'thumb') ?>
                            </div>
                            <div class="ticket-row-body">
                                <span class="badge badge-neutral"><span
                                        class="badge-dot"></span><?= $t['status_kehadiran'] ? 'Sudah Digunakan' : 'Selesai' ?></span>
                                <h3 class="ticket-row-title"><?= htmlspecialchars($t['judul_event']) ?></h3>
                                <p class="ticket-row-meta"><?= date('d M Y', strtotime($t['tanggal_mulai'])) ?> ·
                                    <?= htmlspecialchars($t['kode_tiket']) ?></p>
                            </div>
                            <a href="<?= BASE_URL ?>/tiket-digital?id=<?= $t['id'] ?>" class="button ghost-button"
                                style="text-decoration:none">Detail</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="slate" style="padding:16px 0">Tidak ada riwayat acara yang selesai.</p>
                <?php endif; ?>
            </div>

            <div id="sec-cancelled" style="display:none">
                <h2 class="group-label">DIBATALKAN</h2>
                <?php if (!empty($cancelled)): ?>
                    <?php foreach ($cancelled as $t): ?>
                        <div class="ticket-row">
                            <div class="is-dimmed" style="flex:none">
                                <?= BannerHelper::render($t, 'thumb') ?>
                            </div>
                            <div class="ticket-row-body">
                                <span class="badge badge-error"><span class="badge-dot"></span>Dibatalkan</span>
                                <h3 class="ticket-row-title"><?= htmlspecialchars($t['judul_event']) ?></h3>
                                <p class="ticket-row-meta"><?= htmlspecialchars($t['kode_tiket']) ?></p>
                            </div>
                            <a href="<?= BASE_URL ?>/tiket-digital?id=<?= $t['id'] ?>" class="button ghost-button"
                                style="text-decoration:none">Detail</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="slate" style="padding:16px 0">Tidak ada tiket yang dibatalkan.</p>
                <?php endif; ?>
            </div>
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
</body>

</html>