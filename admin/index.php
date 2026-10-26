<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();

$admin = ViewHelper::requireLogin('ADMIN');

// Real metrics
$totalPengguna = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalAcara = (int)$db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$menungguCount = (int)$db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN'")->fetchColumn();
$acaraTerbit = (int)$db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi IN ('TERBIT', 'PUBLISHED')")->fetchColumn();

// Perlu tindakan: Acara yang menunggu tinjauan
$stmtPerlu = $db->query("
    SELECT e.*, u.nama as owner_nama 
    FROM events e 
    JOIN users u ON e.owner_id = u.id 
    WHERE e.status_verifikasi = 'MENUNGGU_TINJAUAN' 
    ORDER BY e.created_at ASC LIMIT 5
");
$perluTindakan = $stmtPerlu->fetchAll();

// Aktivitas platform
$stmtLogs = $db->query("
    SELECT esl.*, e.judul_event, u.nama as owner_nama 
    FROM event_submission_logs esl
    JOIN events e ON esl.event_id = e.id
    JOIN users u ON e.owner_id = u.id
    ORDER BY esl.created_at DESC LIMIT 5
");
$aktivitas = $stmtLogs->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Dasbor</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;min-height:900px;height:auto">
        <div class="app">
            <aside class="sidebar">
                <div class="logo">
                    <svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                        <path d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z" fill="#4B34DB" />
                        <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" fill="none" />
                    </svg>
                    <span class="logo-text">tixora</span>
                </div>
                <span class="side-label">ADMIN PANEL</span>
                <a class="side-item side-item-active" href="<?= BASE_URL ?>/admin">Dasbor</a>
                <a class="side-item" href="<?= BASE_URL ?>/admin/antrean-acara">
                    Antrean acara<span class="count-pill"><?= $menungguCount ?></span>
                </a>
                <a class="side-item" href="<?= BASE_URL ?>/jelajah-acara">Jelajah Acara</a>
                <a class="side-item" href="<?= BASE_URL ?>/logout" style="color:#B91C1C">Keluar</a>
                <div class="side-user">
                    <b><?= htmlspecialchars($admin['nama']) ?></b>
                    <?= htmlspecialchars($admin['email']) ?>
                </div>
            </aside>

            <main class="main">
                <p class="crumb">Dasbor</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Dasbor Admin</h1>
                        <p class="page-subtitle">Ringkasan Sistem Verifikasi Acara &amp; Pengguna Kampus</p>
                    </div>
                    <div class="head-actions">
                        <a class="button primary-button" href="<?= BASE_URL ?>/admin/antrean-acara">Buka antrean acara</a>
                    </div>
                </div>

                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">TOTAL PENGGUNA</div>
                        <div class="metric-value"><?= $totalPengguna ?></div>
                        <div class="metric-note">Terdaftar di sistem</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">TOTAL ACARA</div>
                        <div class="metric-value"><?= $totalAcara ?></div>
                        <div class="metric-note"><?= $acaraTerbit ?> sedang terbit</div>
                    </div>
                    <div class="metric" style="box-shadow:inset 0 0 0 2px #F3D9A4">
                        <div class="metric-label">MENUNGGU TINJAUAN</div>
                        <div class="metric-value" style="color:#B45309"><?= $menungguCount ?></div>
                        <div class="metric-note">Perlu moderasi</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">ACARA TERBIT</div>
                        <div class="metric-value"><?= $acaraTerbit ?></div>
                        <div class="metric-note">Tampil di portal publik</div>
                    </div>
                </div>

                <div class="cols">
                    <div class="card" style="padding:20px 16px 8px;width:680px;box-sizing:border-box">
                        <h2 class="card-title" style="padding:0 8px">Perlu tindakan moderasi</h2>
                        <div style="padding:0">
                            <div class="tbl-head">
                                <div class="tbl-cell" style="width:280px">ACARA</div>
                                <div class="tbl-cell" style="width:100px">DIAJUKAN</div>
                                <div class="tbl-cell" style="width:140px">STATUS</div>
                                <div class="tbl-cell tbl-right" style="width:128px">TINDAKAN</div>
                            </div>

                            <?php if (!empty($perluTindakan)): ?>
                                <?php foreach ($perluTindakan as $ev): ?>
                                    <div class="tbl-row">
                                        <div class="tbl-cell" style="width:280px">
                                            <b><?= htmlspecialchars($ev['judul_event']) ?></b>
                                            <div class="cell-sub">Oleh <?= htmlspecialchars($ev['owner_nama']) ?></div>
                                        </div>
                                        <div class="tbl-cell" style="width:100px">
                                            <?= date('d M', strtotime($ev['created_at'])) ?>
                                        </div>
                                        <div class="tbl-cell" style="width:140px">
                                            <span class="badge badge-warning"><span class="badge-dot"></span>Menunggu</span>
                                        </div>
                                        <div class="tbl-cell tbl-right" style="width:128px">
                                            <a href="<?= BASE_URL ?>/admin/tinjauan-acara?id=<?= $ev['id'] ?>" class="button primary-button small-button" style="text-decoration:none">Tinjau</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding:32px 16px;text-align:center">
                                    <p class="slate">Semua pengajuan acara sudah ditinjau. Tidak ada antrean baru.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Aktivitas Platform</h2>
                        <?php if (!empty($aktivitas)): ?>
                            <?php foreach ($aktivitas as $act): ?>
                                <div class="feed-item">
                                    <span class="feed-dot"></span>
                                    <div>
                                        <b><?= htmlspecialchars($act['owner_nama']) ?></b> — <?= htmlspecialchars($act['judul_event']) ?>
                                        <div class="cell-sub" style="font-size:12px;color:#6B7280"><?= htmlspecialchars($act['catatan'] ?? $act['status']) ?></div>
                                        <div class="feed-time"><?= date('d M, H:i', strtotime($act['created_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="feed-item">
                                <span class="feed-dot"></span>
                                <div><b>Aktivitas Sistem</b> Belum ada riwayat aktivitas terbaru.<div class="feed-time">Hari ini</div></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
