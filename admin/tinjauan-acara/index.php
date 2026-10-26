<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();

$admin = ViewHelper::requireLogin('ADMIN');

$eventId = trim($_GET['id'] ?? '');

if (empty($eventId)) {
    // Ambil acara menunggu tinjauan pertama jika ada, atau acara pertama
    $stmtFirst = $db->query("SELECT id FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN' ORDER BY created_at ASC LIMIT 1");
    $eventId = $stmtFirst->fetchColumn() ?: '';
    if (empty($eventId)) {
        $stmtFirstAny = $db->query("SELECT id FROM events ORDER BY created_at DESC LIMIT 1");
        $eventId = $stmtFirstAny->fetchColumn() ?: '';
    }
}

if (empty($eventId)) {
    header('Location: ' . BASE_URL . '/admin/antrean-acara');
    exit;
}

$stmtEvent = $db->prepare("
    SELECT e.*, u.nama as owner_nama, u.email as owner_email, u.institusi as owner_institusi, u.no_whatsapp as owner_whatsapp
    FROM events e 
    JOIN users u ON e.owner_id = u.id 
    WHERE e.id = :id
");
$stmtEvent->execute(['id' => $eventId]);
$event = $stmtEvent->fetch();

if (!$event) {
    header('Location: ' . BASE_URL . '/admin/antrean-acara');
    exit;
}

$successMsg = '';
$errorMsg = '';

// Handle Moderasi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keputusan = trim($_POST['keputusan'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    if (!in_array($keputusan, ['APPROVE', 'REJECT'])) {
        $errorMsg = 'Pilihan keputusan moderasi tidak valid.';
    } elseif ($keputusan === 'REJECT' && strlen($catatan) < 10) {
        $errorMsg = 'Untuk menolak acara, wajib menyertakan alasan yang jelas minimal 10 karakter.';
    } else {
        try {
            $db->beginTransaction();

            $statusVerifikasi = ($keputusan === 'APPROVE') ? 'TERBIT' : 'DITOLAK';
            $alasanPenolakan = ($keputusan === 'REJECT') ? $catatan : null;

            $stmtUpdate = $db->prepare("
                UPDATE events 
                SET status_verifikasi = :status, 
                    alasan_penolakan = :alasan,
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                'status' => $statusVerifikasi,
                'alasan' => $alasanPenolakan,
                'id' => $eventId
            ]);

            // Catat log moderasi
            $stmtMod = $db->prepare("
                INSERT INTO moderation_logs (admin_id, event_id, keputusan, catatan)
                VALUES (:admin_id, :event_id, :keputusan, :catatan)
            ");
            $stmtMod->execute([
                'admin_id' => $admin['id'],
                'event_id' => $eventId,
                'keputusan' => $statusVerifikasi,
                'catatan' => $catatan ?: ($statusVerifikasi === 'TERBIT' ? 'Acara disetujui dan diterbitkan.' : 'Acara ditolak.')
            ]);

            // Catat ke event_submission_logs
            $stmtSub = $db->prepare("
                INSERT INTO event_submission_logs (event_id, status, catatan)
                VALUES (:event_id, :status, :catatan)
            ");
            $stmtSub->execute([
                'event_id' => $eventId,
                'status' => $statusVerifikasi,
                'catatan' => 'Keputusan Admin: ' . $statusVerifikasi . ($catatan ? " ({$catatan})" : '')
            ]);

            $db->commit();

            // Refresh data event
            $event['status_verifikasi'] = $statusVerifikasi;
            $successMsg = ($statusVerifikasi === 'TERBIT') 
                ? 'Acara berhasil DISETUJUI & DITERBITKAN ke portal publik.' 
                : 'Acara berhasil DITOLAK. Penyelenggara akan menerima catatan revisi.';
        } catch (Exception $e) {
            $db->rollBack();
            $errorMsg = 'Gagal menyimpan keputusan moderasi: ' . $e->getMessage();
        }
    }
}

// Ambil data penunjang
$stmtCategories = $db->prepare("SELECT * FROM ticket_categories WHERE event_id = :id");
$stmtCategories->execute(['id' => $eventId]);
$categories = $stmtCategories->fetchAll();

$stmtRundowns = $db->prepare("SELECT * FROM rundowns WHERE event_id = :id ORDER BY waktu_mulai ASC");
$stmtRundowns->execute(['id' => $eventId]);
$rundowns = $stmtRundowns->fetchAll();

$stmtModLogs = $db->prepare("
    SELECT ml.*, u.nama as admin_nama 
    FROM moderation_logs ml 
    JOIN users u ON ml.admin_id = u.id 
    WHERE ml.event_id = :id 
    ORDER BY ml.created_at DESC
");
$stmtModLogs->execute(['id' => $eventId]);
$modLogs = $stmtModLogs->fetchAll();

$kickerDate = strtoupper(date('d M Y', strtotime($event['tanggal_mulai'])));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Tinjauan Acara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;min-height:950px;height:auto">
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
                <a class="side-item" href="<?= BASE_URL ?>/admin">Dasbor</a>
                <a class="side-item side-item-active" href="<?= BASE_URL ?>/admin/antrean-acara">Antrean acara</a>
                <a class="side-item" href="<?= BASE_URL ?>/jelajah-acara">Jelajah Acara</a>
                <a class="side-item" href="<?= BASE_URL ?>/logout" style="color:#B91C1C">Keluar</a>
                <div class="side-user">
                    <b><?= htmlspecialchars($admin['nama']) ?></b>
                    <?= htmlspecialchars($admin['email']) ?>
                </div>
            </aside>

            <main class="main">
                <p class="crumb"><a href="<?= BASE_URL ?>/admin/antrean-acara" style="color:inherit;text-decoration:none">Antrean acara</a> / Tinjauan</p>
                <div class="head">
                    <div>
                        <h1 class="page-title"><?= htmlspecialchars($event['judul_event']) ?></h1>
                        <p class="page-subtitle">
                            <span class="badge <?= $event['status_verifikasi'] === 'TERBIT' ? 'badge-success' : ($event['status_verifikasi'] === 'MENUNGGU_TINJAUAN' ? 'badge-warning' : ($event['status_verifikasi'] === 'DITOLAK' ? 'badge-error' : 'badge-neutral')) ?>">
                                <span class="badge-dot"></span><?= htmlspecialchars($event['status_verifikasi']) ?>
                            </span>
                            &nbsp;<span class="slate" style="font-size:14px">Diajukan oleh <?= htmlspecialchars($event['owner_nama']) ?> (<?= htmlspecialchars($event['owner_email']) ?>)</span>
                        </p>
                    </div>
                    <div class="head-actions">
                        <a href="<?= BASE_URL ?>/admin/antrean-acara" class="button secondary-button" style="text-decoration:none">← Kembali ke Antrean</a>
                    </div>
                </div>

                <?php if ($successMsg): ?>
                    <div class="alert alert-success" style="margin-bottom:20px"><?= htmlspecialchars($successMsg) ?></div>
                <?php endif; ?>
                <?php if ($errorMsg): ?>
                    <div class="alert alert-error" style="margin-bottom:20px"><?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>

                <div class="cols">
                    <div class="stack gap-24" style="width:740px">
                        <div class="poster poster-indigo" style="height:200px;border-radius:12px;padding:24px">
                            <span class="poster-kicker"><?= $kickerDate ?></span>
                            <span class="poster-title" style="font-size:32px;line-height:36px"><?= htmlspecialchars(mb_strimwidth($event['judul_event'], 0, 36, '...')) ?></span>
                        </div>

                        <div class="card">
                            <h2 class="card-title">Informasi &amp; Deskripsi Acara</h2>
                            <p style="margin:12px 0 16px;line-height:1.6"><?= nl2br(htmlspecialchars($event['deskripsi'])) ?></p>
                            <div class="two-fields" style="border-top:1px solid #E4E4EE;padding-top:16px">
                                <div>
                                    <span class="slate" style="font-size:13px">Kategori:</span>
                                    <div style="font-weight:700"><?= htmlspecialchars($event['kategori']) ?></div>
                                </div>
                                <div>
                                    <span class="slate" style="font-size:13px">Kapasitas Kuota:</span>
                                    <div style="font-weight:700"><?= (int)$event['kuota'] ?> Peserta</div>
                                </div>
                            </div>
                            <div class="two-fields" style="margin-top:12px">
                                <div>
                                    <span class="slate" style="font-size:13px">Waktu Pelaksanaan:</span>
                                    <div style="font-weight:700"><?= date('d M Y, H:i', strtotime($event['tanggal_mulai'])) ?> – <?= date('H:i', strtotime($event['tanggal_selesai'])) ?> WIB</div>
                                </div>
                                <div>
                                    <span class="slate" style="font-size:13px">Lokasi Tempat:</span>
                                    <div style="font-weight:700"><?= htmlspecialchars($event['nama_tempat']) ?>, <?= htmlspecialchars($event['kota']) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <h2 class="card-title">Kategori Tiket</h2>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <div class="row-between" style="padding:10px 0;border-bottom:1px solid #F1F1F5">
                                        <div>
                                            <b><?= htmlspecialchars($cat['nama_kategori']) ?></b>
                                            <?= $cat['wajib_ktm'] ? '<span class="badge badge-purple" style="font-size:11px;margin-left:8px">Wajib KTM</span>' : '' ?>
                                        </div>
                                        <div>
                                            <span class="slate">Kuota: <?= (int)$cat['kuota'] ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="slate">Tiket umum (default).</p>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($rundowns)): ?>
                            <div class="card">
                                <h2 class="card-title">Rundown Kegiatan</h2>
                                <?php foreach ($rundowns as $rd): ?>
                                    <div class="rundown-row">
                                        <span class="rundown-time"><?= htmlspecialchars($rd['waktu_mulai']) ?></span>
                                        <div>
                                            <b><?= htmlspecialchars($rd['judul_sesi']) ?></b>
                                            <?php if ($rd['pembicara']): ?> · <?= htmlspecialchars($rd['pembicara']) ?><?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="stack gap-16" style="width:364px">
                        <!-- Panel Keputusan Moderasi -->
                        <div class="card card-raised">
                            <h2 class="card-title">Keputusan Moderasi</h2>
                            <form method="POST" action="" class="stack gap-12" style="margin-top:12px">
                                <div>
                                    <label class="field-label">Catatan / Alasan Moderasi</label>
                                    <textarea name="catatan" class="input input-area" style="height:90px;font-family:inherit;padding:8px" placeholder="Tulis catatan persetujuan atau alasan revisi/penolakan jika ditolak..."></textarea>
                                </div>
                                <div class="stack gap-8">
                                    <button type="submit" name="keputusan" value="APPROVE" class="button button-large primary-button full-width" style="cursor:pointer">
                                        ✓ Setujui &amp; Terbitkan Acara
                                    </button>
                                    <button type="submit" name="keputusan" value="REJECT" class="button button-large danger-outline-button full-width" style="cursor:pointer" onclick="return confirm('Apakah Anda yakin ingin menolak acara ini?')">
                                        ✕ Tolak / Minta Revisi
                                    </button>
                                </div>
                            </form>
                            <p class="help" style="margin-top:8px">Penyelenggara akan dapat melihat status dan catatan ini.</p>
                        </div>

                        <!-- Riwayat Moderasi -->
                        <div class="card">
                            <h2 class="card-title">Riwayat Keputusan</h2>
                            <?php if (!empty($modLogs)): ?>
                                <div class="timeline">
                                    <?php foreach ($modLogs as $ml): ?>
                                        <div class="timeline-item">
                                            <span class="timeline-dot"></span>
                                            <b><?= htmlspecialchars($ml['admin_nama']) ?></b>: <?= htmlspecialchars($ml['keputusan']) ?>
                                            <p class="slate" style="font-size:12px;margin:2px 0"><?= htmlspecialchars($ml['catatan'] ?? '') ?></p>
                                            <div class="feed-time" style="font-size:11px"><?= date('d M, H:i', strtotime($ml['created_at'])) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="slate" style="font-size:13px">Belum ada tindakan moderasi sebelumnya.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
