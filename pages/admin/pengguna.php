<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();
$admin = ViewHelper::requireLogin(AuthHelper::ROLE_ADMIN);

$activePage = 'pengguna';

$search     = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');

$sql = "
    SELECT u.*, r.nama AS role_nama
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (u.nama ILIKE :s OR u.email ILIKE :s)";
    $params['s'] = "%{$search}%";
}
if ($roleFilter !== '') {
    $sql .= " AND u.role_id = :role";
    $params['role'] = $roleFilter;
}
$sql .= " ORDER BY u.created_at DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$totalCount    = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$menungguCount = (int)$db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN'")->fetchColumn();

$roleCounts = $db->query("
    SELECT r.id, r.nama, COUNT(u.id) AS cnt
    FROM roles r
    LEFT JOIN users u ON u.role_id = r.id
    GROUP BY r.id, r.nama
    ORDER BY r.nama
")->fetchAll();

function roleBadge(string $roleId): array
{
    return match ($roleId) {
        AuthHelper::ROLE_ADMIN        => ['badge-neutral', 'Admin'],
        AuthHelper::ROLE_EVENT_OWNER  => ['badge-info', 'Penyelenggara'],
        default                       => ['badge-purple', 'Peserta'],
    };
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TIXORA — Admin — Pengguna</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
    <script src="<?= BASE_URL ?>/admin/responsive.js" defer></script>
</head>

<body>
    <div class="frame" style="width:1440px;min-height:900px;height:auto">
        <div class="app">
            <?php include __DIR__ . '/_sidebar.php'; ?>

            <main class="main">
                <p class="crumb">Pengguna</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Pengguna</h1>
                        <p class="page-subtitle"><?= number_format($totalCount) ?> akun terdaftar</p>
                    </div>
                </div>

                <form method="GET" action="" class="filter-bar">
                    <input type="text" name="search" class="input" style="flex:1;width:auto"
                        placeholder="Cari nama atau surel..." value="<?= htmlspecialchars($search) ?>">
                    <?php if ($roleFilter !== ''): ?>
                        <input type="hidden" name="role" value="<?= htmlspecialchars($roleFilter) ?>">
                    <?php endif; ?>
                    <button type="submit" class="button secondary-button" style="cursor:pointer">Cari</button>
                </form>

                <div class="chip-row" style="margin-bottom:16px">
                    <?php
                    $urlSemua = BASE_URL . '/admin/pengguna' . ($search !== '' ? '?search=' . urlencode($search) : '');
                    ?>
                    <a href="<?= $urlSemua ?>" class="chip <?= $roleFilter === '' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Semua <?= $totalCount ?>
                    </a>
                    <?php foreach ($roleCounts as $rc):
                        $urlRole = BASE_URL . '/admin/pengguna?role=' . urlencode($rc['id']);
                        if ($search !== '') $urlRole .= '&search=' . urlencode($search);
                    ?>
                        <a href="<?= $urlRole ?>" class="chip <?= $roleFilter === $rc['id'] ? 'chip-active' : '' ?>" style="text-decoration:none">
                            <?= htmlspecialchars($rc['nama']) ?> <?= (int)$rc['cnt'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="tbl-card">
                    <div class="tbl-head">
                        <div class="tbl-cell" style="width:220px">NAMA</div>
                        <div class="tbl-cell" style="width:280px">SUREL</div>
                        <div class="tbl-cell" style="width:160px">PERAN</div>
                        <div class="tbl-cell" style="width:130px">TERDAFTAR</div>
                        <div class="tbl-cell" style="width:150px">STATUS</div>
                        <div class="tbl-cell tbl-right" style="width:120px"></div>
                    </div>

                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $u): $badge = roleBadge($u['role_id']); ?>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:220px"><b><?= htmlspecialchars($u['nama']) ?></b></div>
                                <div class="tbl-cell" style="width:280px"><?= htmlspecialchars($u['email']) ?></div>
                                <div class="tbl-cell" style="width:160px">
                                    <span class="badge <?= $badge[0] ?>"><span class="badge-dot"></span><?= $badge[1] ?></span>
                                </div>
                                <div class="tbl-cell" style="width:130px"><?= date('d M Y', strtotime($u['created_at'])) ?></div>
                                <div class="tbl-cell" style="width:150px">
                                    <span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-error' ?>">
                                        <span class="badge-dot"></span><?= $u['is_active'] ? 'Aktif' : 'Ditangguhkan' ?>
                                    </span>
                                </div>
                                <div class="tbl-cell tbl-right" style="width:120px">
                                    <a href="<?= BASE_URL ?>/admin/detail-pengguna?id=<?= urlencode($u['id']) ?>"
                                       class="button secondary-button small-button" style="text-decoration:none">Detail</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:32px 16px;text-align:center">
                            <p class="slate">Tidak ada pengguna yang cocok dengan filter.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>

</html>