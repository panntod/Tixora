<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/../../api/helpers/AdminNotificationHelper.php';
require_once __DIR__ . '/_helpers.php';
$db = ViewHelper::getDb();
$admin = ViewHelper::requireLogin(AuthHelper::ROLE_ADMIN);

$activePage = 'notifikasi';

$filter = $_GET['filter'] ?? ($_POST['filter'] ?? 'semua');
if (!is_string($filter) || !in_array($filter, AdminNotificationHelper::FILTERS, true)) {
    $filter = 'semua';
}
$filterQuery = $filter !== 'semua' ? '?filter=' . $filter : '';

$errorMsg = '';

// === TANDAI SEMUA DIBACA ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ViewHelper::checkCsrf()) {
        $errorMsg = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (($_POST['action'] ?? '') === 'mark_all_read') {
        AdminNotificationHelper::markAllRead($db, (string) $admin['id']);
        header('Location: ' . BASE_URL . '/admin/notifikasi' . $filterQuery);
        exit;
    }
}

$feed        = AdminNotificationHelper::feed($db, (string) $admin['id']);
$belumDibaca = $feed['belum_dibaca'];
$items       = AdminNotificationHelper::filter($feed['items'], $filter);

$menungguCount = (int) $db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN'")->fetchColumn();

$chips = [
    'semua'          => 'Semua',
    'perlu-tindakan' => 'Perlu tindakan',
    'sistem'         => 'Sistem',
];
$kosong = [
    'semua'          => 'Belum ada notifikasi.',
    'perlu-tindakan' => 'Tidak ada yang perlu ditindaklanjuti saat ini.',
    'sistem'         => 'Belum ada notifikasi sistem.',
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TIXORA — Admin — Notifikasi</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
    <script src="<?= BASE_URL ?>/admin/responsive.js" defer></script>
</head>

<body>
    <div class="frame" style="width:1440px;min-height:700px;height:auto">
        <div class="app">
            <?php include __DIR__ . '/_sidebar.php'; ?>

            <main class="main">
                <p class="crumb">Notifikasi</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Notifikasi</h1>
                        <p class="page-subtitle"><?= $belumDibaca > 0 ? $belumDibaca . ' belum dibaca' : 'Semua sudah dibaca' ?></p>
                    </div>
                    <div class="head-actions">
                        <form method="POST" action="<?= BASE_URL ?>/admin/notifikasi<?= adm_h($filterQuery) ?>" style="margin:0">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="action" value="mark_all_read">
                            <input type="hidden" name="filter" value="<?= adm_h($filter) ?>">
                            <button type="submit" class="button secondary-button" <?= $belumDibaca === 0 ? 'disabled' : '' ?>>Tandai semua dibaca</button>
                        </form>
                    </div>
                </div>

                <?php if ($errorMsg !== ''): ?>
                    <div class="alert alert-error" style="margin-bottom:16px"><?= adm_h($errorMsg) ?></div>
                <?php endif; ?>

                <div class="notif-wrap">
                    <div class="chip-row">
                        <?php foreach ($chips as $key => $label): ?>
                            <a href="<?= BASE_URL ?>/admin/notifikasi<?= $key === 'semua' ? '' : '?filter=' . $key ?>"
                               class="chip <?= $filter === $key ? 'chip-active' : '' ?>"
                               style="text-decoration:none"><?= adm_h($label) ?></a>
                        <?php endforeach; ?>
                    </div>

                    <div class="card notif-card">
                        <?php if (empty($items)): ?>
                            <p class="slate" style="margin:0;padding:24px 0;text-align:center"><?= adm_h($kosong[$filter]) ?></p>
                        <?php else: ?>
                            <?php foreach ($items as $it):
                                $dot = match ($it['tingkat']) {
                                    'PERINGATAN' => 'feed-dot feed-dot-warning',
                                    'INFO'       => 'feed-dot feed-dot-muted',
                                    default      => 'feed-dot',
                                };
                                $cls  = 'feed-item notif-item' . ($it['dibaca'] ? '' : ' is-unread');
                                $href = !empty($it['tautan']) ? BASE_URL . $it['tautan'] : '';
                            ?>
                                <<?= $href !== '' ? 'a' : 'div' ?> class="<?= $cls ?>"<?= $href !== '' ? ' href="' . adm_h($href) . '"' : '' ?>>
                                    <span class="<?= $dot ?>"></span>
                                    <div>
                                        <b><?= adm_h($it['judul']) ?></b>
                                        <div class="cell-sub"><?= adm_h($it['pesan']) ?></div>
                                        <div class="feed-time"><?= adm_h($it['waktu_label']) ?></div>
                                    </div>
                                </<?= $href !== '' ? 'a' : 'div' ?>>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
