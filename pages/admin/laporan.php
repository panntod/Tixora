<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();
$admin = ViewHelper::requireLogin(AuthHelper::ROLE_ADMIN);

$activePage = 'laporan';

// Filter periode
$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to'] ?? '');

$dateFilter = '';
$dateParams = [];

if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $dateFilter .= ' AND e.created_at >= :from';
    $dateParams['from'] = $from . ' 00:00:00';
} else {
    $from = '';
}
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $dateFilter .= ' AND e.created_at <= :to';
    $dateParams['to'] = $to . ' 23:59:59';
} else {
    $to = '';
}

// === CSV EXPORT ===
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tixora-laporan-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM untuk Excel
    fputcsv($out, ['Judul Acara', 'Penyelenggara', 'Kategori', 'Tanggal Mulai', 'Status',
                   'Total Pendaftar', 'Total Hadir', 'Estimasi RAB']);

    $stmt = $db->prepare("
        SELECT e.judul_event, e.kategori, e.tanggal_mulai, e.status_verifikasi,
               u.nama AS owner_nama,
               (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') AS pendaftar,
               (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_kehadiran = TRUE AND t.status_tiket != 'BATAL') AS hadir,
               COALESCE((SELECT SUM(rencana) FROM rab_details WHERE event_id = e.id), 0) AS rab
        FROM events e
        JOIN users u ON e.owner_id = u.id
        WHERE 1=1 {$dateFilter}
        ORDER BY e.created_at DESC
    ");
    $stmt->execute($dateParams);
    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['judul_event'],
            $row['owner_nama'],
            $row['kategori'],
            $row['tanggal_mulai'],
            $row['status_verifikasi'],
            $row['pendaftar'],
            $row['hadir'],
            number_format((float)$row['rab'], 0, ',', '.'),
        ]);
    }
    fclose($out);
    exit;
}

// === METRICS (real dari DB) ===
$totalPengguna = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$penggunaBaru = (int)$db->query("
    SELECT COUNT(*) FROM users WHERE created_at >= CURRENT_TIMESTAMP - interval '30 days'
")->fetchColumn();
$totalEvent     = (int)$db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$eventTerbit    = (int)$db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'TERBIT'")->fetchColumn();
$eventPending   = (int)$db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN'")->fetchColumn();
$eventDitolak   = (int)$db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'DITOLAK'")->fetchColumn();
$totalTiket     = (int)$db->query("SELECT COUNT(*) FROM tickets WHERE status_tiket != 'BATAL'")->fetchColumn();
$tiketHadir     = (int)$db->query("SELECT COUNT(*) FROM tickets WHERE status_kehadiran = TRUE AND status_tiket != 'BATAL'")->fetchColumn();
$nilaiTransaksi = (float)$db->query("
    SELECT COALESCE(SUM(jumlah), 0) FROM payments WHERE status = 'BERHASIL'
")->fetchColumn();

// === REKAP EVENT ===
$stmtEvents = $db->prepare("
    SELECT e.id, e.judul_event, e.kategori, e.tanggal_mulai, e.status_verifikasi,
           u.nama AS owner_nama,
           (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') AS pendaftar
    FROM events e
    JOIN users u ON e.owner_id = u.id
    WHERE 1=1 {$dateFilter}
    ORDER BY pendaftar DESC, e.created_at DESC
    LIMIT 30
");
$stmtEvents->execute($dateParams);
$rekapEvent = $stmtEvents->fetchAll();

$menungguCount = $eventPending;

// Helper untuk URL filter + export
$urlBase = BASE_URL . '/admin/laporan';
$buildUrl = function (array $extra = []) use ($urlBase, $from, $to): string {
    $params = array_filter(array_merge(
        ['from' => $from, 'to' => $to],
        $extra
    ), fn($v) => $v !== '' && $v !== null);
    return $urlBase . ($params ? '?' . http_build_query($params) : '');
};
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TIXORA — Admin — Laporan</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;min-height:900px;height:auto">
        <div class="app">
            <?php include __DIR__ . '/_sidebar.php'; ?>

            <main class="main">
                <p class="crumb">Laporan</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Laporan Platform</h1>
                        <p class="page-subtitle">
                            <?= ($from || $to)
                                ? 'Periode: ' . ($from ?: '…') . ' – ' . ($to ?: '…')
                                : 'Semua waktu' ?>
                        </p>
                    </div>
                    <div class="head-actions" style="display:flex;gap:8px;align-items:center">
                        <form method="GET" action="" class="row" style="gap:8px;display:flex">
                            <input type="date" name="from" class="input" value="<?= htmlspecialchars($from) ?>" style="width:auto">
                            <input type="date" name="to" class="input" value="<?= htmlspecialchars($to) ?>" style="width:auto">
                            <button type="submit" class="button secondary-button" style="cursor:pointer">Filter</button>
                        </form>
                        <a href="<?= htmlspecialchars($buildUrl(['export' => 'csv'])) ?>"
                           class="button primary-button" style="text-decoration:none">Ekspor CSV</a>
                    </div>
                </div>

                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">TOTAL PENGGUNA</div>
                        <div class="metric-value"><?= number_format($totalPengguna) ?></div>
                        <div class="metric-note">+<?= $penggunaBaru ?> dalam 30 hari</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">TOTAL ACARA</div>
                        <div class="metric-value"><?= number_format($totalEvent) ?></div>
                        <div class="metric-note"><?= $eventTerbit ?> terbit · <?= $eventPending ?> menunggu · <?= $eventDitolak ?> ditolak</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">TIKET TERJUAL</div>
                        <div class="metric-value"><?= number_format($totalTiket) ?></div>
                        <div class="metric-note"><?= $tiketHadir ?> check-in</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">NILAI TRANSAKSI</div>
                        <div class="metric-value">Rp<?= number_format($nilaiTransaksi, 0, ',', '.') ?></div>
                        <div class="metric-note">Pembayaran berhasil</div>
                    </div>
                </div>

                <div class="card" style="padding:20px 16px 8px;box-sizing:border-box;margin-top:24px">
                    <h2 class="card-title" style="padding:0 8px">Acara dengan Pendaftar Terbanyak</h2>
                    <?php if (!empty($rekapEvent)): ?>
                        <div class="tbl-head">
                            <div class="tbl-cell" style="width:400px">ACARA</div>
                            <div class="tbl-cell" style="width:280px">PENYELENGGARA</div>
                            <div class="tbl-cell" style="width:140px">TANGGAL</div>
                            <div class="tbl-cell" style="width:180px">STATUS</div>
                            <div class="tbl-cell tbl-right" style="width:120px">PENDAFTAR</div>
                        </div>
                        <?php foreach ($rekapEvent as $ev):
                            $statusBadge = match ($ev['status_verifikasi']) {
                                'TERBIT'             => 'badge-success',
                                'DITOLAK'            => 'badge-error',
                                'MENUNGGU_TINJAUAN'  => 'badge-warning',
                                'DISETUJUI'          => 'badge-info',
                                default              => 'badge-neutral',
                            };
                        ?>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:400px">
                                    <b><?= htmlspecialchars($ev['judul_event']) ?></b>
                                    <div class="cell-sub"><?= htmlspecialchars($ev['kategori']) ?></div>
                                </div>
                                <div class="tbl-cell" style="width:280px"><?= htmlspecialchars($ev['owner_nama']) ?></div>
                                <div class="tbl-cell" style="width:140px"><?= date('d M Y', strtotime($ev['tanggal_mulai'])) ?></div>
                                <div class="tbl-cell" style="width:180px">
                                    <span class="badge <?= $statusBadge ?>">
                                        <span class="badge-dot"></span><?= htmlspecialchars($ev['status_verifikasi']) ?>
                                    </span>
                                </div>
                                <div class="tbl-cell tbl-right" style="width:120px"><b><?= (int)$ev['pendaftar'] ?></b></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:32px 16px;text-align:center">
                            <p class="slate">Belum ada data acara pada periode ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>

</html>