<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();
PaymentHelper::ensureSchema($db);
PaymentHelper::expireOverdue($db);

$search = trim($_GET['search'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$sortBy = trim($_GET['sortBy'] ?? 'terbaru');

$sql = "
    SELECT e.*, u.nama as owner_nama,
           (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') as total_pendaftar,
           COALESCE((SELECT MIN(c.harga) FROM ticket_categories c WHERE c.event_id = e.id), 0) as harga_mulai
    FROM events e
    JOIN users u ON e.owner_id = u.id
    WHERE e.status_verifikasi IN ('TERBIT', 'PUBLISHED', 'DISETUJUI')
";
$params = [];

if (!empty($kategori) && strtolower($kategori) !== 'semua') {
    $sql .= " AND e.kategori ILIKE :kategori";
    $params['kategori'] = $kategori;
}

if (!empty($search)) {
    $sql .= " AND (e.judul_event ILIKE :search OR e.deskripsi ILIKE :search OR e.nama_tempat ILIKE :search OR e.kota ILIKE :search)";
    $params['search'] = "%{$search}%";
}

if ($sortBy === 'terdekat') {
    $sql .= " ORDER BY e.tanggal_mulai ASC";
} else {
    $sql .= " ORDER BY e.created_at DESC";
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();
$totalCount = count($events);

$categories = ['Semua', 'Seminar', 'Workshop', 'Konferensi', 'Kompetisi'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Jelajah Acara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame">
        <?php ViewHelper::renderNavbar('jelajah'); ?>

        <main class="page-body tx-style-27072cba65">
            <div class="page-head tx-style-3e5bc306f6">
                <div>
                    <h1 class="page-title tx-style-b8ba206d8e">Jelajahi acara</h1>
                    <p class="page-subtitle tx-style-86c3bb25ab"><?= $totalCount ?> acara ditemukan</p>
                </div>
                <form method="GET" action="" class="tx-style-2acaf3b57e">
                    <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                    <?php if ($kategori): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>"><?php endif; ?>
                    <label class="field-label tx-style-4bf93bf0d9">Urutkan</label>
                    <select name="sortBy" class="input" onchange="this.form.submit()">
                        <option value="terbaru" <?= $sortBy === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="terdekat" <?= $sortBy === 'terdekat' ? 'selected' : '' ?>>Jadwal Terdekat</option>
                    </select>
                </form>
            </div>

            <!-- Search Bar -->
            <form method="GET" action="" class="search-bar search-wide tx-style-19656c7ed0">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="input" placeholder="Cari acara, topik, atau penyelenggara..." style="border:none;flex:1;outline:none">
                <button type="submit" class="button button-large primary-button tx-style-2eda1daa91">Cari</button>
            </form>

            <!-- Kategori Chips -->
            <div class="chips-row tx-style-4adb1102f3">
                <?php foreach ($categories as $cat): ?>
                    <?php 
                        $isActive = ($kategori === $cat) || ($cat === 'Semua' && empty($kategori));
                        $url = BASE_URL . '/jelajah-acara?' . http_build_query(array_merge($_GET, ['kategori' => ($cat === 'Semua' ? '' : $cat)]));
                    ?>
                    <a href="<?= $url ?>" class="chip <?= $isActive ? 'chip-active' : '' ?>"><?= $cat ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Event Cards Grid -->
            <div class="event-grid tx-style-4d14229815">
                <?php if (!empty($events)): ?>
                    <?php foreach ($events as $idx => $ev): ?>
                        <?php 
                            $sisaKuota = max(0, (int)$ev['kuota'] - (int)$ev['total_pendaftar']);
                            $bgColors = ['poster-indigo', 'poster-coral', 'poster-ink', 'poster-amber', 'poster-cyan'];
                            $posterBg = $bgColors[$idx % count($bgColors)];
                            $dateStr = date('d M Y', strtotime($ev['tanggal_mulai']));
                        ?>
                        <article class="event-card tx-style-30e741d98e">
                            <div class="poster <?= $posterBg ?>" style="height:168px">
                                <div class="poster-art"><span class="art-circle-cyan"></span></div>
                                <span class="poster-kicker"><?= strtoupper(htmlspecialchars($ev['kategori'])) ?></span>
                                <span class="poster-title poster-title-card"><?= htmlspecialchars(mb_strimwidth($ev['judul_event'], 0, 30, '...')) ?></span>
                            </div>
                            <div class="event-card-body">
                                <span class="badge badge-purple"><span class="badge-dot"></span>Akan datang</span>
                                <h3 class="event-card-title"><?= htmlspecialchars($ev['judul_event']) ?></h3>
                                <p class="event-card-meta"><?= $dateStr ?> · <?= htmlspecialchars($ev['nama_tempat']) ?></p>
                                <div class="event-card-footer tx-style-34c37090dc">
                                    <?php if ((float)$ev['harga_mulai'] > 0): ?>
                                        <b class="event-card-price">Mulai <?= PaymentHelper::rupiah($ev['harga_mulai']) ?></b>
                                    <?php else: ?>
                                        <b class="event-card-price price-free">Gratis</b>
                                    <?php endif; ?>
                                    <span class="event-card-left">Sisa <?= $sisaKuota ?> kursi</span>
                                </div>
                                <a href="<?= BASE_URL ?>/detail-acara?id=<?= $ev['id'] ?>" class="button primary-button" style="width:100%;margin-top:12px;text-align:center">Detail &amp; Daftar</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="tx-style-31c91595c9">
                        <h3 class="tx-style-4b66864699">Tidak ada acara yang cocok</h3>
                        <p class="slate tx-style-87c136dfd0">Coba gunakan kata kunci pencarian atau kategori lain.</p>
                        <a href="<?= BASE_URL ?>/jelajah-acara" class="button secondary-button">Reset Filter</a>
                    </div>
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
</body>

</html>
