<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EO::loadEvent($db, $owner);
$id = $event['id'];

$stats = EO::stats($db, $event);
$categories = EO::categories($db, $id);

$stmt = $db->prepare("
    SELECT t.status_tiket, t.waktu_daftar, t.jenis_tiket, u.nama
    FROM tickets t JOIN users u ON u.id = t.user_id
    WHERE t.event_id = :id
    ORDER BY t.waktu_daftar DESC
    LIMIT 4");
$stmt->execute(['id' => $id]);
$recent = $stmt->fetchAll();

$hariMenuju = max(0, (int) ceil((strtotime($event['tanggal_mulai']) - time()) / 86400));
$isPublished = in_array($event['status_verifikasi'], ['TERBIT', 'PUBLISHED'], true);

// Rincian pendapatan per kategori, mis. "78 Umum · 88 Mhs · 30 VIP".
$rincian = [];
foreach ($categories as $c) {
    $nama = mb_strtolower($c['nama_kategori']) === 'mahasiswa' ? 'Mhs' : $c['nama_kategori'];
    $rincian[] = (int) $c['terjual'] . ' ' . $nama;
}

$actions = ($isPublished
        ? '<a href="' . BASE_URL . '/detail-acara?id=' . EO::e($id) . '" class="button secondary-button" style="text-decoration:none;cursor:pointer">Lihat halaman publik</a>'
        : '')
    . '<a href="' . EO::url('edit-informasi', $id) . '" class="button primary-button" style="text-decoration:none;cursor:pointer">Edit informasi</a>';

$subtitle = EO::badge(EO::eventBadge($event['status_verifikasi'])) . ' &nbsp;<span class="slate" style="font-size:14px">'
    . EO::e(EO::tanggal($event['tanggal_mulai'], true)) . ' · ' . EO::e($event['nama_tempat']) . ', ' . EO::e($event['kota']) . '</span>';

EO::open($db, $owner, $event, 'ringkasan', $subtitle, $actions);
?>
                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">PENDAFTAR</div>
                        <div class="metric-value"><?= $stats['pendaftar'] ?></div>
                        <div class="metric-note">dari <?= $stats['kuota'] ?> kuota</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">PENDAPATAN TIKET</div>
                        <div class="metric-value"><?= EO::e(EO::rp($stats['pendapatan'])) ?></div>
                        <div class="metric-note"><?= EO::e($rincian ? implode(' · ', $rincian) : 'Belum ada kategori tiket') ?></div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">SISA KAPASITAS</div>
                        <div class="metric-value"><?= $stats['sisa'] ?></div>
                        <div class="metric-note"><?= $stats['persen'] ?>% terisi</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">HARI MENUJU ACARA</div>
                        <div class="metric-value"><?= $hariMenuju ?></div>
                        <div class="metric-note">Acara dimulai <?= EO::e(EO::tanggalPendek($event['tanggal_mulai'])) ?>, <?= date('H.i', strtotime($event['tanggal_mulai'])) ?></div>
                    </div>
                </div>

                <div class="cols">
                    <div class="col-wide card" style="padding:20px 16px 8px;box-sizing:border-box">
                        <h2 class="card-title" style="padding:0 8px">Pendaftar terbaru</h2>
                        <div>
                            <div class="tbl-head">
                                <div class="tbl-cell" style="width:200px">NAMA</div>
                                <div class="tbl-cell" style="width:110px">TIKET</div>
                                <div class="tbl-cell" style="width:170px">WAKTU</div>
                                <div class="tbl-cell" style="width:168px">STATUS</div>
                            </div>
                            <?php foreach ($recent as $r): ?>
                                <div class="tbl-row">
                                    <div class="tbl-cell" style="width:200px"><b><?= EO::e($r['nama']) ?></b></div>
                                    <div class="tbl-cell" style="width:110px"><?= EO::e($r['jenis_tiket']) ?></div>
                                    <div class="tbl-cell" style="width:170px"><?= EO::e(EO::waktuRelatif($r['waktu_daftar'])) ?></div>
                                    <div class="tbl-cell" style="width:168px"><?= EO::badge(EO::ticketBadge($r['status_tiket'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$recent): ?>
                                <div class="empty-note">Belum ada pendaftar.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Ketersediaan tiket</h2>
                        <?php foreach ($categories as $c):
                            $terjual = (int) $c['terjual'];
                            $kuota = (int) $c['kuota'];
                            $sisa = max(0, $kuota - $terjual);
                            $persen = $kuota > 0 ? min(100, (int) round($terjual / $kuota * 100)) : 0;
                            ?>
                            <div style="margin-bottom:16px">
                                <div class="legend-row"><b><?= EO::e($c['nama_kategori']) ?></b><span class="slate"><?= $terjual ?> / <?= $kuota ?> · <?= $sisa > 0 ? 'sisa ' . $sisa : 'habis' ?></span></div>
                                <div class="progress"><div class="progress-fill" style="width:<?= $persen ?>%"></div></div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$categories): ?>
                            <p class="slate" style="font-size:14px;margin-bottom:16px">Belum ada kategori tiket.</p>
                        <?php endif; ?>
                        <a href="<?= EO::url('tiket', $id) ?>" class="button secondary-button full-width" style="text-decoration:none;cursor:pointer">Kelola tiket</a>
                    </div>
                </div>
<?php EO::close(); ?>
