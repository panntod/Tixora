<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();

$owner = ViewHelper::requireLogin('EVENT_OWNER');

// Statistik Real
$stmtTotal = $db->prepare("SELECT COUNT(*) FROM events WHERE owner_id = :id");
$stmtTotal->execute(['id' => $owner['id']]);
$totalAcara = (int)$stmtTotal->fetchColumn();

$stmtTerbit = $db->prepare("SELECT COUNT(*) FROM events WHERE owner_id = :id AND status_verifikasi IN ('TERBIT', 'PUBLISHED')");
$stmtTerbit->execute(['id' => $owner['id']]);
$acaraTerbit = (int)$stmtTerbit->fetchColumn();

$stmtDraft = $db->prepare("SELECT COUNT(*) FROM events WHERE owner_id = :id AND status_verifikasi = 'DRAF'");
$stmtDraft->execute(['id' => $owner['id']]);
$draftCount = (int)$stmtDraft->fetchColumn();

$stmtPending = $db->prepare("SELECT COUNT(*) FROM events WHERE owner_id = :id AND status_verifikasi = 'MENUNGGU_TINJAUAN'");
$stmtPending->execute(['id' => $owner['id']]);
$pendingCount = (int)$stmtPending->fetchColumn();

$stmtTickets = $db->prepare("
    SELECT COUNT(t.id) 
    FROM tickets t 
    JOIN events e ON t.event_id = e.id 
    WHERE e.owner_id = :id AND t.status_tiket != 'BATAL'
");
$stmtTickets->execute(['id' => $owner['id']]);
$totalPendaftar = (int)$stmtTickets->fetchColumn();

// Cek acara perlu revisi
$stmtRevisi = $db->prepare("SELECT * FROM events WHERE owner_id = :id AND status_verifikasi = 'DITOLAK' LIMIT 1");
$stmtRevisi->execute(['id' => $owner['id']]);
$acaraRevisi = $stmtRevisi->fetch();

// Acara mendatang
$stmtUpcoming = $db->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') as total_pendaftar
    FROM events e 
    WHERE e.owner_id = :id 
    ORDER BY e.tanggal_mulai ASC LIMIT 5
");
$stmtUpcoming->execute(['id' => $owner['id']]);
$upcomingEvents = $stmtUpcoming->fetchAll();

// Log aktivitas terbaru
$stmtLogs = $db->prepare("
    SELECT esl.*, e.judul_event 
    FROM event_submission_logs esl 
    JOIN events e ON esl.event_id = e.id 
    WHERE e.owner_id = :id 
    ORDER BY esl.created_at DESC LIMIT 5
");
$stmtLogs->execute(['id' => $owner['id']]);
$logs = $stmtLogs->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Dasbor</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;min-height:808px;height:auto">
        <div class="app">
            <aside class="sidebar">
                <div class="logo">
                    <svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                        <path d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z" fill="#4B34DB" />
                        <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" fill="none" />
                    </svg>
                    <span class="logo-text">tixora</span>
                </div>
                <a href="<?= BASE_URL ?>/event-owner" class="side-item side-item-active" style="text-decoration:none">Dasbor</a>
                <a href="<?= BASE_URL ?>/event-owner/acara-saya" class="side-item" style="text-decoration:none">Acara saya</a>
                <a href="<?= BASE_URL ?>/event-owner/buat-acara" class="side-item" style="text-decoration:none">Buat acara</a>
                <a href="<?= BASE_URL ?>/jelajah-acara" class="side-item" style="text-decoration:none">Jelajah Acara</a>
                <a href="<?= BASE_URL ?>/logout" class="side-item" style="text-decoration:none;color:#B91C1C">Keluar</a>
                <div class="side-user">
                    <b><?= htmlspecialchars($owner['nama']) ?></b>
                    <?= htmlspecialchars($owner['email']) ?>
                </div>
            </aside>

            <main class="main">
                <p class="crumb">Dasbor</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Halo, <?= htmlspecialchars($owner['nama']) ?></h1>
                        <p class="page-subtitle">Panel Pengelolaan Acara &amp; Tiket Kampus</p>
                    </div>
                    <div class="head-actions">
                        <a href="<?= BASE_URL ?>/event-owner/buat-acara" class="button primary-button button-large" style="text-decoration:none">+ Buat acara</a>
                    </div>
                </div>

                <?php if ($acaraRevisi): ?>
                    <div style="margin-bottom:24px" class="alert alert-error">
                        1 acara perlu revisi: <b><?= htmlspecialchars($acaraRevisi['judul_event']) ?></b>. 
                        Alasan: <?= htmlspecialchars($acaraRevisi['alasan_penolakan'] ?? 'Tinjau catatan admin.') ?>
                    </div>
                <?php endif; ?>

                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">TOTAL ACARA</div>
                        <div class="metric-value"><?= $totalAcara ?></div>
                        <div class="metric-note"><?= $draftCount ?> draf · <?= $pendingCount ?> menunggu</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">ACARA TERBIT</div>
                        <div class="metric-value"><?= $acaraTerbit ?></div>
                        <div class="metric-note">Aktif di jelajah acara</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">TIKET TERDAFTAR</div>
                        <div class="metric-value"><?= $totalPendaftar ?></div>
                        <div class="metric-note">Seluruh acara</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">STATUS DRAF</div>
                        <div class="metric-value"><?= $draftCount ?></div>
                        <div class="metric-note">Belum diajukan</div>
                    </div>
                </div>

                <div class="cols">
                    <div class="col-wide tbl-card" style="padding:20px 16px 8px;width:680px;box-sizing:border-box">
                        <h2 class="card-title" style="padding:0 8px">Acara Anda</h2>
                        <div style="box-shadow:none;padding:0">
                            <div class="tbl-head">
                                <div class="tbl-cell" style="width:360px">ACARA</div>
                                <div class="tbl-cell" style="width:150px">STATUS</div>
                                <div class="tbl-cell" style="width:170px">PENDAFTAR</div>
                            </div>
                            <?php if (!empty($upcomingEvents)): ?>
                                <?php foreach ($upcomingEvents as $ev): ?>
                                    <?php 
                                        $pct = $ev['kuota'] > 0 ? min(100, round(((int)$ev['total_pendaftar'] / (int)$ev['kuota']) * 100)) : 0;
                                    ?>
                                    <div class="tbl-row">
                                        <div class="tbl-cell" style="width:360px">
                                            <b><?= htmlspecialchars($ev['judul_event']) ?></b>
                                            <div class="cell-sub"><?= date('d M Y', strtotime($ev['tanggal_mulai'])) ?> · <?= htmlspecialchars($ev['nama_tempat']) ?></div>
                                        </div>
                                        <div class="tbl-cell" style="width:150px">
                                            <span class="badge <?= $ev['status_verifikasi'] === 'TERBIT' ? 'badge-success' : ($ev['status_verifikasi'] === 'MENUNGGU_TINJAUAN' ? 'badge-warning' : 'badge-neutral') ?>">
                                                <span class="badge-dot"></span><?= htmlspecialchars($ev['status_verifikasi']) ?>
                                            </span>
                                        </div>
                                        <div class="tbl-cell" style="width:170px">
                                            <div class="progress">
                                                <div class="progress-fill" style="width:<?= $pct ?>%"></div>
                                            </div>
                                            <div class="cell-sub" style="font-size:12px;margin-top:4px"><?= (int)$ev['total_pendaftar'] ?> / <?= (int)$ev['kuota'] ?> pendaftar (<?= $pct ?>%)</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding:32px 16px;text-align:center">
                                    <p class="slate">Belum ada acara. Mulai buat acara pertama Anda.</p>
                                    <a href="<?= BASE_URL ?>/event-owner/buat-acara" class="button primary-button small-button" style="margin-top:10px;text-decoration:none">+ Buat Acara</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Aktivitas &amp; Log</h2>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $lg): ?>
                                <div class="feed-item">
                                    <span class="feed-dot"></span>
                                    <div>
                                        <b><?= htmlspecialchars($lg['judul_event']) ?></b> — <?= htmlspecialchars($lg['catatan'] ?? $lg['status']) ?>
                                        <div class="feed-time"><?= date('d M Y, H:i', strtotime($lg['created_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="feed-item">
                                <span class="feed-dot"></span>
                                <div><b>Sistem Tixora</b> Siap digunakan untuk mempublikasikan kegiatan kampus.<div class="feed-time">Sekarang</div></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
