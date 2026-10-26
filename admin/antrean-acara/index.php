<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();

$admin = ViewHelper::requireLogin('ADMIN');

$status = trim($_GET['status'] ?? 'MENUNGGU_TINJAUAN');
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT e.*, u.nama as owner_nama, u.email as owner_email,
           (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') as total_pendaftar
    FROM events e
    JOIN users u ON e.owner_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($status) && strtoupper($status) !== 'SEMUA') {
    $sql .= " AND e.status_verifikasi = :status";
    $params['status'] = strtoupper($status);
}

if (!empty($search)) {
    $sql .= " AND (e.judul_event ILIKE :search OR u.nama ILIKE :search OR e.nama_tempat ILIKE :search)";
    $params['search'] = "%{$search}%";
}

$sql .= " ORDER BY e.created_at ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Counts
$rawCounts = $db->query("SELECT status_verifikasi, COUNT(*) as cnt FROM events GROUP BY status_verifikasi")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalSemua = array_sum($rawCounts);
$menungguCount = $rawCounts['MENUNGGU_TINJAUAN'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Antrean Acara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;min-height:900px;height:auto">
        <div class="app">
            <aside class="sidebar">
                <div class="logo">
                    <img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA">
                </div>
                <span class="side-label">ADMIN PANEL</span>
                <a class="side-item" href="<?= BASE_URL ?>/admin">Dasbor</a>
                <a class="side-item side-item-active" href="<?= BASE_URL ?>/admin/antrean-acara">
                    Antrean acara<span class="count-pill"><?= $menungguCount ?></span>
                </a>
                <a class="side-item" href="<?= BASE_URL ?>/jelajah-acara">Jelajah Acara</a>
                <a class="side-item" href="<?= BASE_URL ?>/logout" style="color:#B91C1C">Keluar</a>
                <div class="side-user">
                    <b><?= htmlspecialchars($admin['nama']) ?></b>
                    <?= htmlspecialchars($admin['email']) ?>
                </div>
            </aside>

            <main class="main">
                <p class="crumb">Antrean acara</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Antrean Acara</h1>
                        <p class="page-subtitle">Tinjau pengajuan dan moderasi acara sebelum terbit</p>
                    </div>
                </div>

                <form method="GET" action="" class="filter-bar">
                    <?php if ($status !== 'MENUNGGU_TINJAUAN'): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                    <?php endif; ?>
                    <input type="text" name="search" class="input" style="flex:1;width:auto" placeholder="Cari nama acara atau penyelenggara..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="button secondary-button">Cari</button>
                </form>

                <div class="chip-row">
                    <a href="<?= BASE_URL ?>/admin/antrean-acara?status=MENUNGGU_TINJAUAN" class="chip <?= $status === 'MENUNGGU_TINJAUAN' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Menunggu Tinjauan <?= $rawCounts['MENUNGGU_TINJAUAN'] ?? 0 ?>
                    </a>
                    <a href="<?= BASE_URL ?>/admin/antrean-acara?status=TERBIT" class="chip <?= $status === 'TERBIT' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Terbit <?= ($rawCounts['TERBIT'] ?? 0) + ($rawCounts['PUBLISHED'] ?? 0) ?>
                    </a>
                    <a href="<?= BASE_URL ?>/admin/antrean-acara?status=DITOLAK" class="chip <?= $status === 'DITOLAK' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Ditolak <?= $rawCounts['DITOLAK'] ?? 0 ?>
                    </a>
                    <a href="<?= BASE_URL ?>/admin/antrean-acara?status=SEMUA" class="chip <?= $status === 'SEMUA' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Semua <?= $totalSemua ?>
                    </a>
                </div>

                <div class="tbl-card">
                    <div class="tbl-head">
                        <div class="tbl-cell" style="width:340px">ACARA</div>
                        <div class="tbl-cell" style="width:180px">PENYELENGGARA</div>
                        <div class="tbl-cell" style="width:120px">TANGGAL</div>
                        <div class="tbl-cell" style="width:150px">STATUS</div>
                        <div class="tbl-cell tbl-right" style="width:170px">TINDAKAN</div>
                    </div>

                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $ev): ?>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:340px">
                                    <b><?= htmlspecialchars($ev['judul_event']) ?></b>
                                    <div class="cell-sub"><?= htmlspecialchars($ev['kategori']) ?> · <?= htmlspecialchars($ev['nama_tempat']) ?></div>
                                </div>
                                <div class="tbl-cell" style="width:180px">
                                    <b><?= htmlspecialchars($ev['owner_nama']) ?></b>
                                    <div class="cell-sub"><?= htmlspecialchars($ev['owner_email']) ?></div>
                                </div>
                                <div class="tbl-cell" style="width:120px">
                                    <?= date('d M Y', strtotime($ev['tanggal_mulai'])) ?>
                                </div>
                                <div class="tbl-cell" style="width:150px">
                                    <span class="badge <?= $ev['status_verifikasi'] === 'TERBIT' ? 'badge-success' : ($ev['status_verifikasi'] === 'MENUNGGU_TINJAUAN' ? 'badge-warning' : ($ev['status_verifikasi'] === 'DITOLAK' ? 'badge-error' : 'badge-neutral')) ?>">
                                        <span class="badge-dot"></span><?= htmlspecialchars($ev['status_verifikasi']) ?>
                                    </span>
                                </div>
                                <div class="tbl-cell tbl-right" style="width:170px">
                                    <a href="<?= BASE_URL ?>/admin/tinjauan-acara?id=<?= $ev['id'] ?>" class="button primary-button small-button" style="text-decoration:none">Tinjau</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:48px 16px;text-align:center">
                            <p class="slate">Tidak ada antrean acara pada status ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
