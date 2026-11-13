<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();
$admin = ViewHelper::requireLogin(AuthHelper::ROLE_ADMIN);

$activePage = 'penyelenggara';

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT u.*,
        (SELECT COUNT(*) FROM events e WHERE e.owner_id = u.id) AS total_event,
        (SELECT COUNT(*) FROM events e WHERE e.owner_id = u.id AND e.status_verifikasi = 'TERBIT') AS event_terbit,
        (SELECT COUNT(*) FROM events e WHERE e.owner_id = u.id AND e.status_verifikasi = 'MENUNGGU_TINJAUAN') AS event_pending,
        (SELECT COUNT(*) FROM events e WHERE e.owner_id = u.id AND e.status_verifikasi = 'DITOLAK') AS event_ditolak,
        (SELECT COUNT(*) FROM tickets t
           JOIN events e ON e.id = t.event_id
          WHERE e.owner_id = u.id AND t.status_tiket != 'BATAL') AS total_pendaftar
    FROM users u
    WHERE u.role_id = :role_id
";
$params = ['role_id' => AuthHelper::ROLE_EVENT_OWNER];

if ($search !== '') {
    $sql .= " AND (u.nama ILIKE :s OR u.email ILIKE :s OR u.institusi ILIKE :s)";
    $params['s'] = "%{$search}%";
}
$sql .= " ORDER BY u.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$owners = $stmt->fetchAll();

$totalCount    = count($owners);
$menungguCount = (int)$db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TIXORA — Admin — Penyelenggara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
    <script src="<?= BASE_URL ?>/admin/responsive.js" defer></script>
</head>

<body>
    <div class="frame" style="width:1440px;min-height:900px;height:auto">
        <div class="app">
            <?php include __DIR__ . '/_sidebar.php'; ?>

            <main class="main">
                <p class="crumb">Penyelenggara</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Penyelenggara</h1>
                        <p class="page-subtitle"><?= number_format($totalCount) ?> akun penyelenggara</p>
                    </div>
                </div>

                <form method="GET" action="" class="filter-bar">
                    <input type="text" name="search" class="input" style="flex:1;width:auto"
                        placeholder="Cari nama, surel, atau institusi..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="button secondary-button" style="cursor:pointer">Cari</button>
                </form>

                <div class="tbl-card">
                    <div class="tbl-head">
                        <div class="tbl-cell" style="width:280px">PENYELENGGARA</div>
                        <div class="tbl-cell" style="width:220px">INSTITUSI</div>
                        <div class="tbl-cell" style="width:120px;text-align:center">ACARA</div>
                        <div class="tbl-cell" style="width:120px;text-align:center">PENDAFTAR</div>
                        <div class="tbl-cell" style="width:130px">STATUS</div>
                        <div class="tbl-cell tbl-right" style="width:120px"></div>
                    </div>

                    <?php if (!empty($owners)): ?>
                        <?php foreach ($owners as $o): ?>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:280px">
                                    <b><?= htmlspecialchars($o['nama']) ?></b>
                                    <div class="cell-sub"><?= htmlspecialchars($o['email']) ?></div>
                                </div>
                                <div class="tbl-cell" style="width:220px"><?= htmlspecialchars($o['institusi'] ?: '—') ?></div>
                                <div class="tbl-cell" style="width:120px;text-align:center">
                                    <b><?= (int)$o['total_event'] ?></b>
                                    <?php if ((int)$o['event_pending'] > 0): ?>
                                        <div class="cell-sub" style="font-size:11px;color:#B45309"><?= (int)$o['event_pending'] ?> menunggu</div>
                                    <?php endif; ?>
                                </div>
                                <div class="tbl-cell" style="width:120px;text-align:center"><?= (int)$o['total_pendaftar'] ?></div>
                                <div class="tbl-cell" style="width:130px">
                                    <span class="badge <?= $o['is_active'] ? 'badge-success' : 'badge-error' ?>">
                                        <span class="badge-dot"></span><?= $o['is_active'] ? 'Aktif' : 'Ditangguhkan' ?>
                                    </span>
                                </div>
                                <div class="tbl-cell tbl-right" style="width:120px">
                                    <a href="<?= BASE_URL ?>/admin/detail-pengguna?id=<?= urlencode($o['id']) ?>"
                                       class="button secondary-button small-button" style="text-decoration:none">Detail</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:32px 16px;text-align:center">
                            <p class="slate">Belum ada penyelenggara terdaftar.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>

</html>