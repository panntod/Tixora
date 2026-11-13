<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/_helpers.php';
$db = ViewHelper::getDb();

$admin = ViewHelper::requireLogin(AuthHelper::ROLE_ADMIN);
$activePage = 'dashboard';

// ---- Ringkasan angka ----
$totalPengguna = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$penggunaBaruMinggu = (int) $db->query("SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL '7 days'")->fetchColumn();
$penggunaBaruHariIni = (int) $db->query("SELECT COUNT(*) FROM users WHERE created_at::date = CURRENT_DATE")->fetchColumn();

$statusCounts = [];
foreach ($db->query("SELECT status_verifikasi, COUNT(*) AS cnt FROM events GROUP BY status_verifikasi")->fetchAll() as $r) {
    $k = adm_status_norm($r['status_verifikasi']);
    $statusCounts[$k] = ($statusCounts[$k] ?? 0) + (int) $r['cnt'];
}
$totalAcara = array_sum($statusCounts);
$menungguCount = $statusCounts['MENUNGGU_TINJAUAN'] ?? 0;
$acaraTerbit = $statusCounts['TERBIT'] ?? 0;
$acaraDisetujui = $statusCounts['DISETUJUI'] ?? 0;

// ---- Perlu tindakan: antrean menunggu tinjauan, yang tertua lebih dulu ----
$antrean = $db->query("
    SELECT e.id, e.judul_event,
           COALESCE(NULLIF(u.institusi, ''), u.nama) AS penyelenggara,
           COALESCE(
               (SELECT MAX(l.created_at) FROM event_submission_logs l WHERE l.event_id = e.id AND l.status = 'MENUNGGU_TINJAUAN'),
               e.updated_at
           ) AS diajukan,
           (SELECT COUNT(*) FROM event_submission_logs l WHERE l.event_id = e.id AND l.status = 'MENUNGGU_TINJAUAN') AS jumlah_pengajuan
    FROM events e
    JOIN users u ON e.owner_id = u.id
    WHERE e.status_verifikasi = 'MENUNGGU_TINJAUAN'
    ORDER BY diajukan ASC
")->fetchAll();
$terlama = $antrean ? $antrean[0]['diajukan'] : null;
$perluTindakan = array_slice($antrean, 0, 5);

// ---- Aktivitas platform ----
$logs = $db->query("
    SELECT esl.status, esl.created_at, e.judul_event,
           COALESCE(NULLIF(u.institusi, ''), u.nama) AS penyelenggara,
           (SELECT COUNT(*) FROM event_submission_logs p
             WHERE p.event_id = esl.event_id AND p.status = 'MENUNGGU_TINJAUAN' AND p.created_at < esl.created_at) AS pengajuan_sebelumnya
    FROM event_submission_logs esl
    JOIN events e ON esl.event_id = e.id
    JOIN users u ON e.owner_id = u.id
    WHERE esl.status IN ('MENUNGGU_TINJAUAN', 'DISETUJUI', 'DITOLAK', 'TERBIT')
    ORDER BY esl.created_at DESC
    LIMIT 4
")->fetchAll();

$aktivitas = [];
foreach ($logs as $l) {
    switch ($l['status']) {
        case 'MENUNGGU_TINJAUAN':
            $aktor = $l['penyelenggara'];
            $aksi = ((int) $l['pengajuan_sebelumnya'] > 0 ? 'mengajukan ulang ' : 'mengajukan ') . $l['judul_event'];
            break;
        case 'DISETUJUI':
            $aktor = 'Admin';
            $aksi = 'menyetujui ' . $l['judul_event'];
            break;
        case 'DITOLAK':
            $aktor = 'Admin';
            $aksi = 'menolak ' . $l['judul_event'];
            break;
        default:
            $aktor = $l['penyelenggara'];
            $aksi = 'menerbitkan ' . $l['judul_event'];
    }
    $aktivitas[] = ['aktor' => $aktor, 'aksi' => $aksi, 'waktu' => adm_tgl_jam($l['created_at'])];
}
if ($penggunaBaruHariIni > 0) {
    $aktivitas[] = ['aktor' => $penggunaBaruHariIni . ' pengguna baru', 'aksi' => 'mendaftar hari ini', 'waktu' => 'Hari ini'];
}

// ---- Pendaftaran peserta 14 hari terakhir ----
$stmtReg = $db->query("
    SELECT d::date AS hari, COUNT(t.id) AS jumlah
    FROM generate_series(CURRENT_DATE - 13, CURRENT_DATE, INTERVAL '1 day') d
    LEFT JOIN tickets t ON t.waktu_daftar::date = d::date AND t.status_tiket <> 'BATAL'
    GROUP BY d
    ORDER BY d
");
$pendaftaran = $stmtReg->fetchAll();
$maxReg = max(1, ...array_map(fn($r) => (int) $r['jumlah'], $pendaftaran ?: [['jumlah' => 1]]));
$totalReg = array_sum(array_map(fn($r) => (int) $r['jumlah'], $pendaftaran));

// ---- Status acara (urutan sesuai rancangan) ----
$statusBar = [
    ['TERBIT', '#15803D'],
    ['SELESAI', '#0E7490'],
    ['DRAF', '#8E92AD'],
    ['DITOLAK', '#B91C1C'],
    ['DISETUJUI', '#1D4ED8'],
    ['MENUNGGU_TINJAUAN', '#B45309'],
    ['BATAL', '#E8A0A0'],
];
$tanggalHariIni = adm_tgl(date('Y-m-d'), true);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Dasbor</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="<?= BASE_URL ?>/admin/responsive.js" defer></script>
</head>

<body>
    <div class="frame" style="width:1440px;min-height:900px;height:auto">
        <div class="app">
            <?php include __DIR__ . '/_sidebar.php'; ?>

            <main class="main">
                <p class="crumb">Dasbor</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Dasbor admin</h1>
                        <p class="page-subtitle">Ringkasan platform per <?= adm_h($tanggalHariIni) ?></p>
                    </div>
                    <div class="head-actions">
                        <a class="button primary-button" href="<?= BASE_URL ?>/admin/antrean-acara">Buka antrean acara</a>
                        <a class="button secondary-button" href="<?= BASE_URL ?>/admin/pengguna">Kelola pengguna</a>
                    </div>
                </div>

                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">TOTAL PENGGUNA</div>
                        <div class="metric-value"><?= number_format($totalPengguna, 0, ',', '.') ?></div>
                        <div class="metric-note">+<?= number_format($penggunaBaruMinggu, 0, ',', '.') ?> minggu ini</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">TOTAL ACARA</div>
                        <div class="metric-value"><?= $totalAcara ?></div>
                        <div class="metric-note"><?= $acaraTerbit ?> sedang terbit</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">MENUNGGU TINJAUAN</div>
                        <div class="metric-value metric-value-warning"><?= $menungguCount ?></div>
                        <div class="metric-note"><?= $terlama ? 'Terlama: ' . adm_h(adm_tgl($terlama)) : 'Tidak ada antrean' ?></div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">ACARA TERBIT</div>
                        <div class="metric-value"><?= $acaraTerbit ?></div>
                        <div class="metric-note"><?= $acaraDisetujui ?> disetujui, belum terbit</div>
                    </div>
                </div>

                <div class="cols cols-stretch">
                    <div class="card card-flush col-wide-card">
                        <h2 class="card-title">Perlu tindakan</h2>
                        <div class="tbl-head">
                            <div class="tbl-cell" style="width:250px">ACARA</div>
                            <div class="tbl-cell" style="width:90px">DIAJUKAN</div>
                            <div class="tbl-cell" style="width:150px">JENIS</div>
                            <div class="tbl-cell tbl-right" style="width:140px"></div>
                        </div>

                        <?php if ($perluTindakan): ?>
                            <?php foreach ($perluTindakan as $ev): ?>
                                <?php [$jenisLabel, $jenisBadge] = adm_jenis_pengajuan((int) $ev['jumlah_pengajuan']); ?>
                                <div class="tbl-row">
                                    <div class="tbl-cell" style="width:250px">
                                        <b><?= adm_h($ev['judul_event']) ?></b>
                                        <div class="cell-sub"><?= adm_h($ev['penyelenggara']) ?></div>
                                    </div>
                                    <div class="tbl-cell" style="width:90px"><?= adm_h(adm_tgl($ev['diajukan'])) ?></div>
                                    <div class="tbl-cell" style="width:150px">
                                        <span class="badge <?= $jenisBadge ?>"><span class="badge-dot"></span><?= adm_h($jenisLabel) ?></span>
                                    </div>
                                    <div class="tbl-cell tbl-right" style="width:140px">
                                        <a href="<?= BASE_URL ?>/admin/tinjauan-acara?id=<?= adm_h($ev['id']) ?>"
                                            class="button primary-button small-button" style="text-decoration:none">Tinjau</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding:32px 16px;text-align:center">
                                <p class="slate">Semua pengajuan sudah ditinjau. Pengajuan baru akan muncul di sini.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card col-narrow card-scroll" style="box-sizing:border-box">
                        <h2 class="card-title">Aktivitas platform</h2>
                        <div class="scroll-bpdy" tabindex="0" aria-label="Daftar aktivitas platform">
                            <?php if ($aktivitas): ?>
                            <?php foreach ($aktivitas as $act): ?>
                                <div class="feed-item">
                                    <span class="feed-dot"></span>
                                    <div>
                                        <b><?= adm_h($act['aktor']) ?></b>
                                        <div class="feed-text"><?= adm_h($act['aksi']) ?></div>
                                        <div class="feed-time"><?= adm_h($act['waktu']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="slate">Belum ada aktivitas terbaru.</p>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="cols" style="margin-top:24px">
                    <div class="card col-wide-card">
                        <h2 class="card-title">Pendaftaran peserta per hari</h2>
                        <div class="bar-chart" role="img"
                            aria-label="Grafik pendaftaran peserta 14 hari terakhir, total <?= $totalReg ?> pendaftaran">
                            <?php $lastIdx = count($pendaftaran) - 1; ?>
                            <?php foreach ($pendaftaran as $i => $r): ?>
                                <?php $pct = max(3, (int) round(((int) $r['jumlah'] / $maxReg) * 100)); ?>
                                <div class="bar <?= $i === $lastIdx ? 'bar-last' : '' ?>" style="height:<?= $pct ?>%"
                                    title="<?= adm_h(adm_tgl($r['hari'])) ?>: <?= (int) $r['jumlah'] ?> pendaftaran"></div>
                            <?php endforeach; ?>
                        </div>
                        <p class="chart-note">14 hari terakhir · <?= number_format($totalReg, 0, ',', '.') ?> pendaftaran</p>
                    </div>

                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Status acara</h2>
                        <div class="stack-bar">
                            <?php foreach ($statusBar as [$key, $warna]): ?>
                                <?php if (($statusCounts[$key] ?? 0) > 0): ?>
                                    <span class="seg" style="flex:<?= $statusCounts[$key] ?>;background:<?= $warna ?>"></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="legend-grid">
                            <?php foreach ($statusBar as [$key, $warna]): ?>
                                <div class="legend-item">
                                    <span><span class="legend-swatch" style="background:<?= $warna ?>"></span><?= adm_h(adm_status_label($key)) ?></span>
                                    <b><?= $statusCounts[$key] ?? 0 ?></b>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>