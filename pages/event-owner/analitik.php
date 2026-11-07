<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EO::loadEvent($db, $owner);
$id = $event['id'];

const HARI_GRAFIK = 14;
const HARI_RATA2 = 10;

$stats = EO::stats($db, $event);
$categories = EO::categories($db, $id);

// Pendaftaran per hari (tiket tidak batal), HARI_GRAFIK hari terakhir termasuk hari ini.
$stmt = $db->prepare("SELECT waktu_daftar::date AS hari, COUNT(*) AS n
                      FROM tickets WHERE event_id = :id AND status_tiket <> 'BATAL'
                        AND waktu_daftar >= CURRENT_DATE - (:span || ' days')::interval
                      GROUP BY 1");
$stmt->execute(['id' => $id, 'span' => HARI_GRAFIK - 1]);
$perHari = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$series = [];
for ($i = HARI_GRAFIK - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $series[] = ['hari' => $d, 'n' => (int) ($perHari[$d] ?? 0)];
}

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="pendaftaran-per-hari-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Tanggal', 'Pendaftar']);
    foreach ($series as $s) {
        fputcsv($out, [$s['hari'], $s['n']]);
    }
    exit;
}

$max = max(1, ...array_column($series, 'n'));
$peak = $series[0];
foreach ($series as $s) {
    if ($s['n'] > $peak['n']) {
        $peak = $s;
    }
}
$last10 = array_slice($series, -HARI_RATA2);
$rata = (int) round(array_sum(array_column($last10, 'n')) / HARI_RATA2);

$totalPendapatan = array_sum(array_map(static fn($c) => (float) $c['pendapatan'], $categories));
$namaKategori = implode(', ', array_column($categories, 'nama_kategori'));

$actions = '<a href="' . EO::e(EO::url('analitik', $id, ['export' => 'csv'])) . '" class="button secondary-button" style="text-decoration:none;cursor:pointer">Ekspor CSV</a>';
$subtitle = '<span class="slate" style="font-size:16px">' . EO::e(EO::tanggal($event['tanggal_mulai'])) . ' · ' . EO::e($event['nama_tempat']) . '</span>';

EO::open($db, $owner, $event, 'analitik', $subtitle, $actions, 'Analitik acara', 'Analitik');

// Geometri grafik batang (viewBox 580x190, baseline y=170).
$barW = 24;
$gap = 16;
?>
                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">PENDAPATAN</div>
                        <div class="metric-value"><?= EO::e(EO::rp($stats['pendapatan'])) ?></div>
                        <div class="metric-note"><?= EO::e($namaKategori !== '' ? 'Tiket ' . $namaKategori : 'Belum ada kategori tiket') ?></div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">TIKET TERJUAL</div>
                        <div class="metric-value"><?= $stats['pendaftar'] ?></div>
                        <div class="metric-note"><?= $stats['persen'] ?>% dari kapasitas</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">PENDAFTARAN / HARI</div>
                        <div class="metric-value"><?= $rata ?></div>
                        <div class="metric-note">Rata-rata <?= HARI_RATA2 ?> hari terakhir</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">MENUNGGU PEMBAYARAN</div>
                        <div class="metric-value"><?= $stats['menunggu'] ?></div>
                        <div class="metric-note"><?= EO::e(EO::rp($stats['tertunda'])) ?> tertunda</div>
                    </div>
                </div>

                <div class="cols">
                    <div class="card col-wide" style="box-sizing:border-box">
                        <h2 class="card-title">Pendaftaran per hari</h2>
                        <svg width="100%" viewBox="0 0 580 190" role="img" aria-label="Grafik batang pendaftaran per hari selama <?= HARI_GRAFIK ?> hari terakhir"
                            style="max-width:616px">
                            <line x1="0" y1="170" x2="580" y2="170" stroke="#E4E4EE" />
                            <?php foreach ($series as $i => $s):
                                $h = $s['n'] > 0 ? max(4, (int) round($s['n'] / $max * 156)) : 0;
                                $isPeak = $peak['n'] > 0 && $s['hari'] === $peak['hari'];
                                ?>
                                <rect x="<?= 16 + $i * ($barW + $gap) ?>" y="<?= 170 - $h ?>" width="<?= $barW ?>" height="<?= $h ?>" rx="4"
                                    fill="<?= $isPeak ? '#4B34DB' : '#C9C5FF' ?>"><title><?= EO::e(EO::tanggalPendek($s['hari'])) ?>: <?= $s['n'] ?> pendaftar</title></rect>
                            <?php endforeach; ?>
                        </svg>
                        <p class="help">
                            <?= $peak['n'] > 0
                                ? 'Puncak: ' . $peak['n'] . ' pendaftar pada ' . EO::e(EO::tanggalPendek($peak['hari'])) . '.'
                                : 'Belum ada pendaftar dalam ' . HARI_GRAFIK . ' hari terakhir.' ?>
                            Menampilkan <?= HARI_GRAFIK ?> hari terakhir, <?= EO::e(EO::tanggalPendek($series[0]['hari'])) ?>–<?= EO::e(EO::tanggalPendek($series[HARI_GRAFIK - 1]['hari'])) ?>.
                        </p>
                    </div>

                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Pendapatan per kategori</h2>
                        <?php foreach ($categories as $c):
                            $share = $totalPendapatan > 0 ? (int) round((float) $c['pendapatan'] / $totalPendapatan * 100) : 0;
                            ?>
                            <div style="margin-bottom:16px">
                                <div class="legend-row"><b><?= EO::e($c['nama_kategori']) ?></b><span class="slate"><?= EO::e(EO::rp($c['pendapatan'])) ?></span></div>
                                <div class="progress"><div class="progress-fill" style="width:<?= $share ?>%"></div></div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$categories): ?><p class="slate" style="font-size:14px">Belum ada kategori tiket.</p><?php endif; ?>
                    </div>
                </div>
<?php EO::close(); ?>
