<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/_helpers.php';
$db = ViewHelper::getDb();
$admin = ViewHelper::requireLogin(AuthHelper::ROLE_ADMIN);

$activePage = 'laporan';

// === PERIODE ===
$presetList = [
    '7' => '7 hari terakhir',
    '30' => '30 hari terakhir',
    '90' => '90 hari terakhir',
    'bulan' => 'Bulan ini',
    'tahun' => 'Tahun ini',
    'semua' => 'Semua waktu',
];
$periode = $_GET['periode'] ?? '30';
if (!is_string($periode) || !isset($presetList[$periode])) {
    $periode = '30';
}
$allTime = ($periode === 'semua');

$today = new DateTimeImmutable('today');
$end = $today;
$start = match ($periode) {
    '7' => $today->modify('-6 days'),
    '30' => $today->modify('-29 days'),
    '90' => $today->modify('-89 days'),
    'bulan' => $today->modify('first day of this month'),
    'tahun' => $today->setDate((int) $today->format('Y'), 1, 1),
    default => null,
};
if ($start === null) {
    $min = $db->query("SELECT MIN(created_at) FROM users")->fetchColumn();
    $start = $min ? new DateTimeImmutable(substr((string) $min, 0, 10)) : $today;
}

$qStart = $allTime ? '1970-01-01 00:00:00' : $start->format('Y-m-d') . ' 00:00:00';
$qEnd = $end->format('Y-m-d') . ' 23:59:59';
$range = ['s' => $qStart, 'e' => $qEnd];

$sameYear = $start->format('Y') === $end->format('Y');
$periodeLabel = adm_tgl($start->format('Y-m-d'), !$sameYear) . ' – ' . adm_tgl($end->format('Y-m-d'), true);

// === CSV EXPORT (acara yang dibuat pada periode terpilih) ===
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $dateFilter = $allTime ? '' : ' AND e.created_at >= :s AND e.created_at <= :e';
    $dateParams = $allTime ? [] : $range;

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tixora-laporan-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM untuk Excel
    fputcsv($out, [
        'Judul Acara',
        'Penyelenggara',
        'Kategori',
        'Tanggal Mulai',
        'Status',
        'Total Pendaftar',
        'Total Hadir',
        'Estimasi RAB'
    ]);

    $csvSafe = static function ($v): string {
        $s = (string) $v;
        if ($s !== '' && in_array($s[0], ['=', '+', '-', '@', "\t", "\r", "\n"], true)) {
            return "'" . $s;
        }
        return $s;
    };

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
            $csvSafe($row['judul_event']),
            $csvSafe($row['owner_nama']),
            $csvSafe($row['kategori']),
            $row['tanggal_mulai'],
            $row['status_verifikasi'],
            $row['pendaftar'],
            $row['hadir'],
            number_format((float) $row['rab'], 0, ',', '.'),
        ]);
    }
    fclose($out);
    exit;
}

$fmt = fn($n): string => number_format((int) $n, 0, ',', '.');

// === METRIK (mengikuti periode) ===
$stmtSub = $db->prepare("
    SELECT
        COUNT(*) FILTER (WHERE status = 'MENUNGGU_TINJAUAN') AS diajukan,
        COUNT(*) FILTER (WHERE status = 'DISETUJUI')         AS disetujui,
        COUNT(*) FILTER (WHERE status = 'DITOLAK')           AS ditolak
    FROM event_submission_logs
    WHERE created_at >= :s AND created_at <= :e
");
$stmtSub->execute($range);
$sub = $stmtSub->fetch();
$pengajuan = (int) ($sub['diajukan'] ?? 0);
$disetujui = (int) ($sub['disetujui'] ?? 0);
$ditolak = (int) ($sub['ditolak'] ?? 0);

$stmtBaru = $db->prepare("SELECT COUNT(*) FROM users WHERE created_at >= :s AND created_at <= :e");
$stmtBaru->execute($range);
$penggunaBaru = (int) $stmtBaru->fetchColumn();
$totalPengguna = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

$stmtTiket = $db->prepare("
    SELECT COUNT(*) FROM tickets
    WHERE status_tiket != 'BATAL' AND waktu_daftar >= :s AND waktu_daftar <= :e
");
$stmtTiket->execute($range);
$totalTiket = (int) $stmtTiket->fetchColumn();

$stmtNilai = $db->prepare("
    SELECT COALESCE(SUM(jumlah), 0) FROM payments
    WHERE status = 'BERHASIL' AND COALESCE(dibayar_pada, created_at) >= :s AND COALESCE(dibayar_pada, created_at) <= :e
");
$stmtNilai->execute($range);
$nilaiTransaksi = (float) $stmtNilai->fetchColumn();

$menungguCount = (int) $db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN'")->fetchColumn();

// === GRAFIK 1: pengajuan & persetujuan per minggu (9 minggu terakhir sampai akhir periode) ===
$jumlahMinggu = 9;
$wkFirst = $end->modify('-' . ($jumlahMinggu * 7 - 1) . ' days');
$minggu = [];
for ($k = 0; $k < $jumlahMinggu; $k++) {
    $minggu[$k] = ['diajukan' => 0, 'disetujui' => 0];
}
$stmtWk = $db->prepare("
    SELECT status, created_at::date AS d
    FROM event_submission_logs
    WHERE created_at >= :ws AND created_at <= :e AND status IN ('MENUNGGU_TINJAUAN', 'DISETUJUI')
");
$stmtWk->execute(['ws' => $wkFirst->format('Y-m-d') . ' 00:00:00', 'e' => $qEnd]);
foreach ($stmtWk->fetchAll() as $row) {
    $idx = intdiv((int) $wkFirst->diff(new DateTimeImmutable((string) $row['d']))->days, 7);
    if ($idx < 0 || $idx >= $jumlahMinggu) {
        continue;
    }
    $minggu[$idx][$row['status'] === 'DISETUJUI' ? 'disetujui' : 'diajukan']++;
}
$maxMinggu = 1;
foreach ($minggu as $m) {
    $maxMinggu = max($maxMinggu, $m['diajukan'], $m['disetujui']);
}
$barPct = fn(int $v): int => $v > 0 ? max(4, (int) round($v / $maxMinggu * 100)) : 0;

// === GRAFIK 2: pertumbuhan pengguna (total kumulatif sepanjang periode) ===
$stmtBefore = $db->prepare("SELECT COUNT(*) FROM users WHERE created_at < :s");
$stmtBefore->execute(['s' => $qStart]);
$totalAwal = (int) $stmtBefore->fetchColumn();

$stmtDay = $db->prepare("
    SELECT created_at::date AS d, COUNT(*) AS c
    FROM users
    WHERE created_at >= :s AND created_at <= :e
    GROUP BY 1 ORDER BY 1
");
$stmtDay->execute($range);
$perHari = [];
foreach ($stmtDay->fetchAll() as $row) {
    $perHari[(string) $row['d']] = (int) $row['c'];
}

$nHari = (int) $start->diff($end)->days + 1;
$running = $totalAwal;
$seri = [];
for ($i = 0; $i < $nHari; $i++) {
    $running += $perHari[$start->modify("+{$i} days")->format('Y-m-d')] ?? 0;
    $seri[] = $running;
}
$totalAkhir = $running;

$step = max(1, (int) ceil(count($seri) / 40));
$titik = [];
for ($i = 0; $i < count($seri); $i += $step) {
    $titik[] = $seri[$i];
}
if ((count($seri) - 1) % $step !== 0) {
    $titik[] = $seri[count($seri) - 1];
}
if (count($titik) < 2) {
    $titik[] = $titik[0];
}

$svgW = 480;
$svgH = 140;
$padT = 12;
$padB = 16;
$vMin = min($titik);
$vMax = max($titik);
$vSpan = max(1, $vMax - $vMin);
$nT = count($titik);
$coords = [];
foreach ($titik as $i => $v) {
    $x = round($i / ($nT - 1) * $svgW, 1);
    $y = round($svgH - $padB - (($v - $vMin) / $vSpan) * ($svgH - $padT - $padB), 1);
    $coords[] = $x . ',' . $y;
}

// === ACARA DENGAN PENDAFTAR TERBANYAK (pendaftaran pada periode) ===
$stmtTop = $db->prepare("
    SELECT e.id, e.judul_event,
           COALESCE(NULLIF(u.institusi, ''), u.nama) AS penyelenggara,
           COUNT(t.id) AS pendaftar
    FROM events e
    JOIN users u ON u.id = e.owner_id
    JOIN tickets t ON t.event_id = e.id
                  AND t.status_tiket != 'BATAL'
                  AND t.waktu_daftar >= :s AND t.waktu_daftar <= :e
    GROUP BY e.id, e.judul_event, u.id, u.institusi, u.nama
    ORDER BY pendaftar DESC, e.judul_event ASC
    LIMIT 5
");
$stmtTop->execute($range);
$topEvent = $stmtTop->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TIXORA — Admin — Laporan</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
    <script src="<?= BASE_URL ?>/admin/responsive.js" defer></script>
</head>

<body>
    <div class="frame" style="width:1440px;min-height:900px;height:auto">
        <div class="app">
            <?php include __DIR__ . '/_sidebar.php'; ?>

            <main class="main">
                <p class="crumb">Laporan</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Laporan platform</h1>
                        <p class="page-subtitle">Periode <?= adm_h($periodeLabel) ?></p>
                    </div>
                    <div class="head-actions">
                        <form method="GET" action="" style="margin:0">
                            <select name="periode" class="input select-input" aria-label="Periode laporan"
                                onchange="this.form.submit()">
                                <?php foreach ($presetList as $val => $label): ?>
                                    <option value="<?= adm_h($val) ?>" <?= (string) $val === $periode ? 'selected' : '' ?>>
                                        <?= adm_h($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <noscript><button type="submit" class="button secondary-button">Terapkan</button></noscript>
                        </form>
                        <a href="<?= BASE_URL ?>/admin/laporan?periode=<?= urlencode($periode) ?>&export=csv"
                            class="button secondary-button">Ekspor CSV</a>
                        <button type="button" class="button primary-button" style="cursor:pointer"
                            onclick="window.print()">Unduh laporan PDF</button>
                    </div>
                </div>

                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">PENGAJUAN ACARA</div>
                        <div class="metric-value"><?= $fmt($pengajuan) ?></div>
                        <div class="metric-note"><?= $fmt($disetujui) ?> disetujui · <?= $fmt($ditolak) ?> ditolak</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">PENGGUNA BARU</div>
                        <div class="metric-value"><?= $fmt($penggunaBaru) ?></div>
                        <div class="metric-note">Total <?= $fmt($totalPengguna) ?></div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">TIKET TERJUAL</div>
                        <div class="metric-value"><?= $fmt($totalTiket) ?></div>
                        <div class="metric-note">Semua acara</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">NILAI TRANSAKSI</div>
                        <div class="metric-value"><?= adm_h(adm_rupiah($nilaiTransaksi)) ?></div>
                        <div class="metric-note">Tiket berbayar</div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="card">
                        <h2 class="card-title">Pengajuan dan persetujuan per minggu</h2>
                        <div class="wk-chart" role="img"
                            aria-label="Grafik pengajuan dan persetujuan acara per minggu selama <?= $jumlahMinggu ?> minggu terakhir">
                            <?php foreach ($minggu as $k => $m):
                                $wMulai = $wkFirst->modify('+' . ($k * 7) . ' days');
                                $wAkhir = $wMulai->modify('+6 days');
                                $tip = adm_tgl($wMulai->format('Y-m-d')) . ' – ' . adm_tgl($wAkhir->format('Y-m-d'))
                                    . ': ' . $m['diajukan'] . ' diajukan, ' . $m['disetujui'] . ' disetujui';
                                ?>
                                <div class="wk-group" title="<?= adm_h($tip) ?>">
                                    <span class="wk-bar wk-light" style="height:<?= $barPct($m['diajukan']) ?>%"></span>
                                    <span class="wk-bar wk-dark" style="height:<?= $barPct($m['disetujui']) ?>%"></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="chart-note">Ungu muda: diajukan · ungu tua: disetujui. <?= $jumlahMinggu ?> minggu
                            terakhir.</p>
                    </div>

                    <div class="card">
                        <h2 class="card-title">Pertumbuhan pengguna</h2>
                        <div class="line-chart" role="img"
                            aria-label="Grafik pertumbuhan total pengguna dari <?= $fmt($totalAwal) ?> menjadi <?= $fmt($totalAkhir) ?>">
                            <svg viewBox="0 0 <?= $svgW ?> <?= $svgH ?>" preserveAspectRatio="none" focusable="false">
                                <polyline points="<?= implode(' ', $coords) ?>" fill="none" stroke="#4b34db"
                                    stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"
                                    vector-effect="non-scaling-stroke" />
                            </svg>
                        </div>
                        <p class="chart-note">Total pengguna <?= $fmt($totalAwal) ?> → <?= $fmt($totalAkhir) ?>.</p>
                    </div>
                </div>

                <div class="card" style="padding:20px 16px 8px;box-sizing:border-box;margin-top:24px">
                    <h2 class="card-title" style="padding:0 8px">Acara dengan pendaftar terbanyak</h2>
                    <?php if (!empty($topEvent)): ?>
                        <div class="tbl-head">
                            <div class="tbl-cell top-title">ACARA</div>
                            <div class="tbl-cell top-owner">PENYELENGGARA</div>
                            <div class="tbl-cell tbl-right top-count">PENDAFTAR</div>
                        </div>
                        <?php foreach ($topEvent as $ev): ?>
                            <div class="tbl-row">
                                <div class="tbl-cell top-title"><b><?= adm_h($ev['judul_event']) ?></b></div>
                                <div class="tbl-cell top-owner"><?= adm_h($ev['penyelenggara']) ?></div>
                                <div class="tbl-cell tbl-right top-count"><?= $fmt($ev['pendaftar']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:24px 8px 16px">
                            <p class="slate">Belum ada pendaftaran pada periode ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>

</html>