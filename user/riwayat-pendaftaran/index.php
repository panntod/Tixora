<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();
PaymentHelper::ensureSchema($db);
PaymentHelper::expireOverdue($db);

$currentUser = ViewHelper::requireLogin('USER');

$stmt = $db->prepare("
    SELECT t.id, t.kode_tiket, t.status_tiket, t.status_kehadiran, t.created_at,
           e.judul_event, e.tanggal_mulai,
           p.order_id, p.jumlah, p.status AS status_bayar
    FROM tickets t
    JOIN events e ON e.id = t.event_id
    LEFT JOIN LATERAL (
        SELECT order_id, jumlah, status FROM payments
        WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1
    ) p ON TRUE
    WHERE t.user_id = :uid
    ORDER BY t.created_at DESC
");
$stmt->execute(['uid' => $currentUser['id']]);
$rows = $stmt->fetchAll();

// Kelompokkan: akan datang (aktif + menunggu bayar), selesai, dibatalkan
$now = date('Y-m-d H:i:s');
$counts = ['semua' => count($rows), 'akan' => 0, 'selesai' => 0, 'batal' => 0];
foreach ($rows as &$r) {
    if ($r['status_tiket'] === 'BATAL') {
        $r['grup'] = 'batal';
    } elseif ($r['status_kehadiran'] || $r['tanggal_mulai'] < $now) {
        $r['grup'] = 'selesai';
    } else {
        $r['grup'] = 'akan';
    }
    $counts[$r['grup']]++;
}
unset($r);

$filter = $_GET['filter'] ?? 'semua';
if (!isset($counts[$filter])) {
    $filter = 'semua';
}
$shown = array_values(array_filter($rows, fn($r) => $filter === 'semua' || $r['grup'] === $filter));
$chip = fn(string $key, string $label) => '<a href="' . BASE_URL . '/riwayat-pendaftaran?filter=' . $key . '" class="chip '
    . ($filter === $key ? 'chip-active' : '') . '" style="text-decoration:none">' . $label . ' ' . $counts[$key] . '</a>';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Riwayat Pendaftaran</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame tx-style-5bc8079a41">
        <?php ViewHelper::renderNavbar('tiket'); ?>
        <main class="page-body tx-style-e5d5285078">
            <h1 class="page-title tx-style-8677744d08">Akun saya</h1>
            <div class="two-col">
                <nav class="side-nav stack tx-style-d767683936"><a class="side-link" href="<?= BASE_URL ?>/akun" style="text-decoration:none">Profil</a><a
                        class="side-link side-link-active" href="<?= BASE_URL ?>/riwayat-pendaftaran" style="text-decoration:none">Riwayat pendaftaran</a></nav>
                <div class="tx-style-88e7a5ed79">
                    <div class="chip-row"><?= $chip('semua', 'Semua') ?><?= $chip('akan', 'Akan datang') ?><?= $chip('selesai', 'Selesai') ?><?= $chip('batal', 'Dibatalkan') ?></div>
                    <div class="tbl-card">
                        <div class="tbl-head">
                            <div class="tbl-cell tx-style-667fedd0e8">ACARA</div>
                            <div class="tbl-cell tx-style-c6fffdfe96">NO. PESANAN</div>
                            <div class="tbl-cell tx-style-db4cf10b18">TANGGAL</div>
                            <div class="tbl-cell tx-style-581be415aa">TOTAL</div>
                            <div class="tbl-cell tx-style-d52ddd0894">STATUS</div>
                            <div class="tbl-cell tx-style-624b33c36d"></div>
                        </div>
                        <?php if (empty($shown)): ?>
                            <div class="tbl-row"><div class="tbl-cell">Belum ada pendaftaran.</div></div>
                        <?php endif; ?>
                        <?php foreach ($shown as $r): ?>
                            <?php
                                $pending = $r['status_tiket'] === 'MENUNGGU_PEMBAYARAN';
                                if ($r['grup'] === 'batal') {
                                    $badge = ['badge-error', $r['status_bayar'] === 'KEDALUWARSA' ? 'Kedaluwarsa' : 'Dibatalkan'];
                                } elseif ($pending) {
                                    $badge = ['badge-warning', 'Menunggu pembayaran'];
                                } elseif ($r['status_kehadiran']) {
                                    $badge = ['badge-neutral', 'Sudah dipakai'];
                                } elseif ($r['grup'] === 'selesai') {
                                    $badge = ['badge-neutral', 'Selesai'];
                                } else {
                                    $badge = ['badge-success', 'Sah'];
                                }
                            ?>
                            <div class="tbl-row">
                                <div class="tbl-cell tx-style-667fedd0e8"><b><?= htmlspecialchars($r['judul_event']) ?></b></div>
                                <div class="tbl-cell tx-style-c6fffdfe96"><?= htmlspecialchars($r['order_id'] ?? $r['kode_tiket']) ?></div>
                                <div class="tbl-cell tx-style-db4cf10b18"><?= date('d M Y', strtotime($r['tanggal_mulai'])) ?></div>
                                <div class="tbl-cell tx-style-581be415aa"><?= $r['jumlah'] !== null ? PaymentHelper::rupiah($r['jumlah']) : 'Gratis' ?></div>
                                <div class="tbl-cell tx-style-d52ddd0894"><span class="badge <?= $badge[0] ?>"><span class="badge-dot"></span><?= $badge[1] ?></span></div>
                                <div class="tbl-cell tbl-right tx-style-624b33c36d">
                                    <?php if ($pending && $r['status_bayar'] === 'MENUNGGU'): ?>
                                        <a href="<?= BASE_URL ?>/pembayaran?order=<?= urlencode($r['order_id']) ?>" class="button secondary-button small-button" style="text-decoration:none">Bayar</a>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>/tiket-digital?id=<?= htmlspecialchars($r['id']) ?>" class="button <?= $r['grup'] === 'akan' ? 'secondary-button' : 'ghost-button' ?> small-button" style="text-decoration:none"><?= $r['grup'] === 'akan' ? 'Lihat tiket' : 'Detail' ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="row-between tx-style-c3f00cfc4e"><span><?= count($shown) ?> dari <?= $counts['semua'] ?> pendaftaran</span></div>
                </div>
            </div>
        </main>
        <?php ViewHelper::renderFooter(); ?>
    </div>
</body>

</html>