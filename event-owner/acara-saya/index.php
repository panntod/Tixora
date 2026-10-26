<?php
declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();

$owner = ViewHelper::requireLogin('EVENT_OWNER');

$status = trim($_GET['status'] ?? 'SEMUA');
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT e.*, 
           (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') as total_pendaftar
    FROM events e 
    WHERE e.owner_id = :owner_id
";
$params = ['owner_id' => $owner['id']];

if (!empty($status) && strtoupper($status) !== 'SEMUA') {
    $sql .= " AND e.status_verifikasi = :status";
    $params['status'] = strtoupper($status);
}

if (!empty($search)) {
    $sql .= " AND (e.judul_event ILIKE :search OR e.nama_tempat ILIKE :search)";
    $params['search'] = "%{$search}%";
}

$sql .= " ORDER BY e.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Hitung counts per status
$stmtCounts = $db->prepare("
    SELECT status_verifikasi, COUNT(*) as cnt 
    FROM events 
    WHERE owner_id = :owner_id 
    GROUP BY status_verifikasi
");
$stmtCounts->execute(['owner_id' => $owner['id']]);
$rawCounts = $stmtCounts->fetchAll(PDO::FETCH_KEY_PAIR);
$totalSemua = array_sum($rawCounts);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Acara Saya</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;min-height:881px;height:auto">
        <div class="app">
            <aside class="sidebar">
                <div class="logo">
                    <svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                        <path d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z" fill="#4B34DB" />
                        <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" fill="none" />
                    </svg>
                    <span class="logo-text">tixora</span>
                </div>
                <a href="<?= BASE_URL ?>/event-owner" class="side-item" style="text-decoration:none">Dasbor</a>
                <a href="<?= BASE_URL ?>/event-owner/acara-saya" class="side-item side-item-active" style="text-decoration:none">Acara saya</a>
                <a href="<?= BASE_URL ?>/event-owner/buat-acara" class="side-item" style="text-decoration:none">Buat acara</a>
                <a href="<?= BASE_URL ?>/jelajah-acara" class="side-item" style="text-decoration:none">Jelajah Acara</a>
                <a href="<?= BASE_URL ?>/logout" class="side-item" style="text-decoration:none;color:#B91C1C">Keluar</a>
                <div class="side-user">
                    <b><?= htmlspecialchars($owner['nama']) ?></b>
                    <?= htmlspecialchars($owner['email']) ?>
                </div>
            </aside>

            <main class="main">
                <p class="crumb">Acara saya</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Acara Saya</h1>
                        <p class="page-subtitle">Kelola semua acara dalam satu daftar</p>
                    </div>
                    <div class="head-actions">
                        <a href="<?= BASE_URL ?>/event-owner/buat-acara" class="button primary-button button-large" style="text-decoration:none">+ Buat acara</a>
                    </div>
                </div>

                <form method="GET" action="" class="filter-bar">
                    <?php if ($status !== 'SEMUA'): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                    <?php endif; ?>
                    <input type="text" name="search" class="input" style="flex:1;width:auto" placeholder="Cari nama acara..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="button secondary-button">Cari</button>
                </form>

                <div class="chip-row">
                    <a href="<?= BASE_URL ?>/event-owner/acara-saya" class="chip <?= $status === 'SEMUA' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Semua <?= $totalSemua ?>
                    </a>
                    <a href="<?= BASE_URL ?>/event-owner/acara-saya?status=DRAF" class="chip <?= $status === 'DRAF' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Draf <?= $rawCounts['DRAF'] ?? 0 ?>
                    </a>
                    <a href="<?= BASE_URL ?>/event-owner/acara-saya?status=MENUNGGU_TINJAUAN" class="chip <?= $status === 'MENUNGGU_TINJAUAN' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Menunggu <?= $rawCounts['MENUNGGU_TINJAUAN'] ?? 0 ?>
                    </a>
                    <a href="<?= BASE_URL ?>/event-owner/acara-saya?status=TERBIT" class="chip <?= $status === 'TERBIT' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Terbit <?= ($rawCounts['TERBIT'] ?? 0) + ($rawCounts['PUBLISHED'] ?? 0) ?>
                    </a>
                    <a href="<?= BASE_URL ?>/event-owner/acara-saya?status=DITOLAK" class="chip <?= $status === 'DITOLAK' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Ditolak <?= $rawCounts['DITOLAK'] ?? 0 ?>
                    </a>
                </div>

                <div class="tbl-card">
                    <div class="tbl-head">
                        <div class="tbl-cell" style="width:380px">ACARA</div>
                        <div class="tbl-cell" style="width:140px">TANGGAL</div>
                        <div class="tbl-cell" style="width:150px">PENDAFTAR</div>
                        <div class="tbl-cell" style="width:160px">STATUS</div>
                        <div class="tbl-cell tbl-right" style="width:220px">TINDAKAN</div>
                    </div>

                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $ev): ?>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:380px">
                                    <b><?= htmlspecialchars($ev['judul_event']) ?></b>
                                    <div class="cell-sub"><?= htmlspecialchars($ev['nama_tempat']) ?>, <?= htmlspecialchars($ev['kota']) ?></div>
                                </div>
                                <div class="tbl-cell" style="width:140px">
                                    <?= date('d M Y', strtotime($ev['tanggal_mulai'])) ?>
                                </div>
                                <div class="tbl-cell" style="width:150px">
                                    <b><?= (int)$ev['total_pendaftar'] ?></b> / <?= (int)$ev['kuota'] ?>
                                </div>
                                <div class="tbl-cell" style="width:160px">
                                    <span class="badge <?= $ev['status_verifikasi'] === 'TERBIT' ? 'badge-success' : ($ev['status_verifikasi'] === 'MENUNGGU_TINJAUAN' ? 'badge-warning' : ($ev['status_verifikasi'] === 'DITOLAK' ? 'badge-error' : 'badge-neutral')) ?>">
                                        <span class="badge-dot"></span><?= htmlspecialchars($ev['status_verifikasi']) ?>
                                    </span>
                                </div>
                                <div class="tbl-cell tbl-right" style="width:220px">
                                    <?php if ($ev['status_verifikasi'] === 'DRAF'): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/api/owner/submit-event/<?= $ev['id'] ?>" style="display:inline">
                                            <button type="submit" class="button primary-button small-button" style="cursor:pointer">Ajukan</button>
                                        </form>
                                    <?php elseif ($ev['status_verifikasi'] === 'TERBIT'): ?>
                                        <a href="<?= BASE_URL ?>/detail-acara?id=<?= $ev['id'] ?>" class="button secondary-button small-button" style="text-decoration:none">Lihat Publik</a>
                                    <?php else: ?>
                                        <span class="muted" style="font-size:13px"><?= htmlspecialchars($ev['status_verifikasi']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:48px 16px;text-align:center">
                            <p class="slate">Tidak ada acara yang sesuai filter saat ini.</p>
                            <a href="<?= BASE_URL ?>/event-owner/buat-acara" class="button primary-button small-button" style="margin-top:12px;display:inline-block;text-decoration:none">+ Buat Acara Baru</a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
