<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/../../api/helpers/BannerHelper.php';
require_once __DIR__ . '/_helpers.php';
$db = ViewHelper::getDb();

$admin = ViewHelper::requireLogin(AuthHelper::ROLE_ADMIN);
$activePage = 'antrean';
$menungguCount = (int) $db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN'")->fetchColumn();

$eventId = trim($_GET['id'] ?? '');

if (empty($eventId)) {
    // Ambil acara menunggu tinjauan tertua jika ada, atau acara terbaru
    $eventId = $db->query("SELECT id FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN' ORDER BY created_at ASC LIMIT 1")->fetchColumn() ?: '';
    if (empty($eventId)) {
        $eventId = $db->query("SELECT id FROM events ORDER BY created_at DESC LIMIT 1")->fetchColumn() ?: '';
    }
}

if (empty($eventId)) {
    header('Location: ' . BASE_URL . '/admin/antrean-acara');
    exit;
}

$stmtEvent = $db->prepare("
    SELECT e.*, u.nama AS owner_nama, u.email AS owner_email, u.institusi AS owner_institusi, u.no_whatsapp AS owner_whatsapp
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

AuthHelper::initSession();
$errorMsg = '';

// ---- Simpan keputusan moderasi (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keputusan = trim($_POST['keputusan'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    if (!ViewHelper::checkCsrf()) {
        $errorMsg = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (($event['status_verifikasi'] ?? '') !== 'MENUNGGU_TINJAUAN') {
        // Hanya acara yang sedang diajukan yang boleh dimoderasi (mencegah menyetujui DRAF/BATAL)
        $errorMsg = 'Acara ini tidak sedang menunggu tinjauan, keputusan tidak dapat diubah.';
    } elseif (!in_array($keputusan, ['APPROVE', 'REJECT'], true)) {
        $errorMsg = 'Pilihan keputusan moderasi tidak valid.';
    } elseif ($keputusan === 'REJECT' && mb_strlen($catatan) < 10) {
        $errorMsg = 'Untuk menolak acara, wajib menyertakan alasan yang jelas minimal 10 karakter.';
    } else {
        try {
            $db->beginTransaction();

            $statusVerifikasi = ($keputusan === 'APPROVE') ? 'DISETUJUI' : 'DITOLAK';
            $alasanPenolakan = ($keputusan === 'REJECT') ? $catatan : null;

            $stmtUpdate = $db->prepare("
                UPDATE events
                SET status_verifikasi = :status,
                    alasan_penolakan = :alasan,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND status_verifikasi = 'MENUNGGU_TINJAUAN'
            ");
            $stmtUpdate->execute(['status' => $statusVerifikasi, 'alasan' => $alasanPenolakan, 'id' => $eventId]);

            if ($stmtUpdate->rowCount() !== 1) {
                // Admin lain sudah memutuskan lebih dulu.
                $db->rollBack();
                $errorMsg = 'Acara ini sudah diputuskan oleh admin lain.';
            } else {
                $stmtMod = $db->prepare("
                    INSERT INTO moderation_logs (admin_id, event_id, keputusan, catatan)
                    VALUES (:admin_id, :event_id, :keputusan, :catatan)
                ");
                $stmtMod->execute([
                    'admin_id' => $admin['id'],
                    'event_id' => $eventId,
                    'keputusan' => $statusVerifikasi,
                    'catatan' => $catatan !== '' ? $catatan : 'Acara disetujui.',
                ]);

                $stmtSub = $db->prepare("
                    INSERT INTO event_submission_logs (event_id, status, catatan)
                    VALUES (:event_id, :status, :catatan)
                ");
                $stmtSub->execute([
                    'event_id' => $eventId,
                    'status' => $statusVerifikasi,
                    'catatan' => 'Keputusan Admin: ' . $statusVerifikasi . ($catatan !== '' ? " ({$catatan})" : ''),
                ]);

                $db->commit();

                // Post/Redirect/Get agar refresh tidak mengirim ulang keputusan.
                $_SESSION['flash_moderasi'] = ['id' => $eventId, 'status' => $statusVerifikasi];
                header('Location: ' . BASE_URL . '/admin/tinjauan-acara?id=' . urlencode((string) $eventId));
                exit;
            }
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[TIXORA] Moderasi gagal: ' . $e->getMessage());
            $errorMsg = 'Gagal menyimpan keputusan moderasi. Silakan coba lagi.';
        }
    }

    // Muat ulang status terbaru bila gagal karena balapan antar admin.
    $stmtEvent->execute(['id' => $eventId]);
    $event = $stmtEvent->fetch() ?: $event;
}

// ---- Flash sukses (hanya jika cocok dengan acara & status saat ini) ----
$successMsg = '';
$flash = $_SESSION['flash_moderasi'] ?? null;
unset($_SESSION['flash_moderasi']);
if ($flash && ($flash['id'] ?? '') === (string) $eventId && ($flash['status'] ?? '') === ($event['status_verifikasi'] ?? '')) {
    $successMsg = $flash['status'] === 'DISETUJUI'
        ? 'Acara disetujui. Penyelenggara telah diberi tahu dan status kini Disetujui. Acara belum terbit.'
        : 'Acara ditolak. Alasan telah dikirim ke penyelenggara.';
}

// ---- Data penunjang ----
$stmtCategories = $db->prepare("SELECT * FROM ticket_categories WHERE event_id = :id ORDER BY created_at ASC");
$stmtCategories->execute(['id' => $eventId]);
$categories = $stmtCategories->fetchAll();

$stmtRundowns = $db->prepare("SELECT * FROM rundowns WHERE event_id = :id ORDER BY waktu_mulai ASC");
$stmtRundowns->execute(['id' => $eventId]);
$rundowns = $stmtRundowns->fetchAll();

$stmtRab = $db->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(rencana), 0) AS total, MAX(GREATEST(created_at, updated_at)) AS terakhir FROM rab_details WHERE event_id = :id");
$stmtRab->execute(['id' => $eventId]);
$rab = $stmtRab->fetch();

$stmtSub = $db->prepare("SELECT status, catatan, created_at FROM event_submission_logs WHERE event_id = :id AND status IN ('DRAF', 'MENUNGGU_TINJAUAN') ORDER BY created_at ASC");
$stmtSub->execute(['id' => $eventId]);
$subLogs = $stmtSub->fetchAll();

$stmtModLogs = $db->prepare("
    SELECT ml.*, u.nama AS admin_nama
    FROM moderation_logs ml
    JOIN users u ON ml.admin_id = u.id
    WHERE ml.event_id = :id
    ORDER BY ml.created_at ASC
");
$stmtModLogs->execute(['id' => $eventId]);
$modLogs = $stmtModLogs->fetchAll();

// ---- Pengajuan & revisi ----
$pengajuan = array_values(array_filter($subLogs, fn($l) => $l['status'] === 'MENUNGGU_TINJAUAN'));
$jumlahPengajuan = count($pengajuan);
$diajukanPada = $jumlahPengajuan ? end($pengajuan)['created_at'] : null;
$isRevisi = $jumlahPengajuan > 1;
[$jenisLabel, $jenisBadge] = adm_jenis_pengajuan($jumlahPengajuan);

// Penolakan terakhir sebelum pengajuan terbaru (untuk mode revisi)
$penolakanSebelumnya = null;
if ($isRevisi) {
    foreach ($modLogs as $ml) {
        if ($ml['keputusan'] === 'DITOLAK' && $ml['created_at'] < $diajukanPada) {
            $penolakanSebelumnya = $ml; // log terurut ASC, ambil yang terakhir
        }
    }
}

// Perubahan sejak penolakan: hanya yang benar-benar bisa dilacak lewat updated_at.
$perubahan = [];
if ($penolakanSebelumnya) {
    $t = $penolakanSebelumnya['created_at'];
    $stmtChg = $db->prepare("SELECT COUNT(*) FROM ticket_categories WHERE event_id = :id AND GREATEST(created_at, updated_at) > :t");
    $stmtChg->execute(['id' => $eventId, 't' => $t]);
    $tiketBerubah = (int) $stmtChg->fetchColumn() > 0;

    $stmtChg = $db->prepare("SELECT COUNT(*) FROM rundowns WHERE event_id = :id AND GREATEST(created_at, updated_at) > :t");
    $stmtChg->execute(['id' => $eventId, 't' => $t]);
    $rundownBerubah = (int) $stmtChg->fetchColumn() > 0;

    $rabBerubah = (int) $rab['n'] > 0 && $rab['terakhir'] && $rab['terakhir'] > $t;

    $perubahan[] = [
        (int) $rab['n'] > 0 ? 'RAB (' . adm_rupiah($rab['total']) . ')' : 'RAB belum dilampirkan',
        $rabBerubah,
    ];
    $perubahan[] = ['Kategori tiket', $tiketBerubah];
    $perubahan[] = ['Rundown', $rundownBerubah];
}

// ---- Kelengkapan ----
$kelengkapan = [
    ['Poster', !empty($event['banner_url']), 'Lengkap', 'Belum'],
    ['Rundown', count($rundowns) > 0, 'Lengkap', 'Belum'],
    ['Kategori tiket', count($categories) > 0, 'Lengkap', 'Belum'],
    ['RAB', (int) $rab['n'] > 0, 'Terlampir', 'Belum'],
];

// ---- Riwayat tinjauan (gabungan pengajuan + keputusan admin), terbaru di atas ----
$riwayat = [];
$organizer = $event['owner_institusi'] ?: $event['owner_nama'];
$n = 0;
foreach ($subLogs as $l) {
    if ($l['status'] === 'DRAF') {
        $riwayat[] = ['waktu' => $l['created_at'], 'teks' => 'Draf dibuat', 'error' => false];
    } else {
        $n++;
        $riwayat[] = [
            'waktu' => $l['created_at'],
            'teks' => $n === 1 ? 'Diajukan oleh ' . $organizer : 'Diajukan ulang (revisi ke-' . ($n - 1) . ')',
            'error' => false,
        ];
    }
}
foreach ($modLogs as $ml) {
    $riwayat[] = [
        'waktu' => $ml['created_at'],
        'teks' => ($ml['keputusan'] === 'DITOLAK' ? 'Ditolak' : 'Disetujui') . ' oleh ' . $ml['admin_nama'],
        'error' => $ml['keputusan'] === 'DITOLAK',
    ];
}
usort($riwayat, fn($a, $b) => strcmp($b['waktu'], $a['waktu']));

$status = adm_status_norm($event['status_verifikasi']);
$bisaMemutuskan = $status === 'MENUNGGU_TINJAUAN';
$rentang = adm_rentang($event['tanggal_mulai'], $event['tanggal_selesai']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Tinjauan Acara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="<?= BASE_URL ?>/admin/responsive.js" defer></script>
</head>

<body>
    <div class="frame" style="width:1440px;min-height:950px;height:auto">
        <div class="app">
            <?php include __DIR__ . '/_sidebar.php'; ?>

            <main class="main">
                <p class="crumb"><a href="<?= BASE_URL ?>/admin/antrean-acara"
                        style="color:inherit;text-decoration:none">Antrean acara</a> / <?= $isRevisi ? 'Tinjauan revisi' : 'Tinjauan' ?></p>
                <div class="head">
                    <div>
                        <h1 class="page-title"><?= adm_h($event['judul_event']) ?></h1>
                        <p class="page-subtitle">
                            <span class="badge <?= adm_status_badge($status) ?>">
                                <span class="badge-dot"></span><?= adm_h(adm_status_label($status)) ?>
                            </span>
                            <?php if ($jumlahPengajuan > 0): ?>
                                <span class="badge <?= $jenisBadge ?>"><span class="badge-dot"></span><?= adm_h($jenisLabel) ?></span>
                                &nbsp;<span class="slate" style="font-size:14px"><?= $isRevisi ? 'Diajukan ulang' : 'Diajukan' ?>
                                    <?= adm_h(adm_tgl($diajukanPada, true)) ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="head-actions">
                        <a href="<?= BASE_URL ?>/admin/antrean-acara" class="button secondary-button"
                            style="text-decoration:none">← Kembali ke antrean</a>
                    </div>
                </div>

                <?php if ($successMsg): ?>
                    <div class="alert alert-success" style="margin-bottom:24px" role="status"><?= adm_h($successMsg) ?></div>
                <?php endif; ?>
                <?php if ($errorMsg): ?>
                    <div class="alert alert-error" style="margin-bottom:24px" role="alert"><?= adm_h($errorMsg) ?></div>
                <?php endif; ?>

                <div class="cols">
                    <div class="stack gap-24" style="width:740px">

                        <?php if ($penolakanSebelumnya): ?>
                            <div class="card" style="box-shadow:inset 0 0 0 1px #F3B6B6">
                                <h2 class="card-title">Penolakan sebelumnya</h2>
                                <p class="feed-time" style="font-weight:700;margin-bottom:8px">
                                    <?= adm_h(strtoupper(adm_tgl($penolakanSebelumnya['created_at'], true))) ?> · <?= adm_h(strtoupper($penolakanSebelumnya['admin_nama'])) ?>
                                </p>
                                <p class="slate" style="font-size:16px;line-height:24px"><?= nl2br(adm_h($penolakanSebelumnya['catatan'])) ?></p>
                            </div>

                            <div class="card">
                                <h2 class="card-title">Perubahan sejak penolakan</h2>
                                <div class="stack gap-10">
                                    <?php foreach ($perubahan as [$label, $berubah]): ?>
                                        <div class="row-between" style="font-size:14px">
                                            <span><?= adm_h($label) ?></span>
                                            <?php if ($berubah): ?>
                                                <span class="badge badge-success"><span class="badge-dot"></span>Diperbarui</span>
                                            <?php else: ?>
                                                <span class="badge badge-neutral"><span class="badge-dot"></span>Tidak berubah</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="help">Perbandingan versi kata demi kata belum tersedia. Perubahan pada judul, deskripsi, dan tanggal acara belum dilacak, jadi periksa langsung pada informasi di bawah.</p>
                            </div>
                        <?php endif; ?>

                        <div style="border-radius:12px;overflow:hidden;margin-bottom:0;line-height:0;background:#F1F1F7">
                            <?= BannerHelper::render($event, 'wide', [
                                'kicker' => strtoupper($rentang),
                                'title' => (string) $event['judul_event'],
                                'style' => 'height:240px',
                            ]) ?>
                        </div>

                        <div class="card">
                            <h2 class="card-title">Informasi acara</h2>
                            <dl class="definition">
                                <div>
                                    <dt>PENYELENGGARA</dt>
                                    <dd><?= adm_h($event['owner_institusi'] ?: $event['owner_nama']) ?><?= $event['owner_institusi'] ? ' · ' . adm_h($event['owner_nama']) : '' ?></dd>
                                </div>
                                <div>
                                    <dt>KATEGORI</dt>
                                    <dd><?= adm_h($event['kategori']) ?></dd>
                                </div>
                                <div>
                                    <dt>TANGGAL</dt>
                                    <dd><?= adm_h($rentang) ?></dd>
                                </div>
                                <div>
                                    <dt>LOKASI</dt>
                                    <dd><?= adm_h($event['nama_tempat']) ?>, <?= adm_h($event['kota']) ?></dd>
                                </div>
                                <div class="wide">
                                    <dt>DESKRIPSI</dt>
                                    <dd style="font-weight:400;color:#4b5170"><?= nl2br(adm_h($event['deskripsi'])) ?></dd>
                                </div>
                            </dl>
                        </div>

                        <div class="card">
                            <h2 class="card-title">Kategori tiket</h2>
                            <?php if ($categories): ?>
                                <div class="tbl-head" style="padding-left:0;padding-right:0">
                                    <div class="tbl-cell" style="width:230px">KATEGORI</div>
                                    <div class="tbl-cell" style="width:140px">HARGA</div>
                                    <div class="tbl-cell" style="width:110px">KUOTA</div>
                                    <div class="tbl-cell" style="width:200px">PENJUALAN</div>
                                </div>
                                <?php foreach ($categories as $cat): ?>
                                    <?php
                                    $harga = (float) ($cat['harga'] ?? 0);
                                    $mulai = $cat['mulai_dijual'] ?? null;
                                    $akhir = $cat['berakhir_dijual'] ?? null;
                                    if ($mulai && $akhir) {
                                        $penjualan = adm_tgl($mulai) . ' – ' . adm_tgl($akhir);
                                    } elseif ($mulai) {
                                        $penjualan = 'Mulai ' . adm_tgl($mulai);
                                    } else {
                                        $penjualan = '—';
                                    }
                                    ?>
                                    <div class="tbl-row">
                                        <div class="tbl-cell" style="width:230px">
                                            <?= adm_h($cat['nama_kategori']) ?>
                                            <?= !empty($cat['wajib_ktm']) ? '<span class="badge badge-purple badge-sm" style="margin-left:6px">Wajib KTM</span>' : '' ?>
                                        </div>
                                        <div class="tbl-cell" style="width:140px"><?= $harga > 0 ? adm_h(adm_rupiah($harga)) : 'Gratis' ?></div>
                                        <div class="tbl-cell" style="width:110px"><?= (int) $cat['kuota'] ?></div>
                                        <div class="tbl-cell" style="width:200px"><?= adm_h($penjualan) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="slate">Belum ada kategori tiket.</p>
                            <?php endif; ?>
                        </div>

                        <?php if ($rundowns): ?>
                            <div class="card">
                                <h2 class="card-title">Rundown</h2>
                                <?php foreach ($rundowns as $rd): ?>
                                    <div class="rundown-row">
                                        <span class="rundown-time"><?= adm_h(str_replace(':', '.', (string) $rd['waktu_mulai'])) ?></span>
                                        <div>
                                            <?= adm_h($rd['judul_sesi']) ?>
                                            <?php if (!empty($rd['pembicara'])): ?>
                                                <span class="slate"> · <?= adm_h($rd['pembicara']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="stack gap-16" style="width:364px">
                        <!-- Keputusan -->
                        <div class="card card-raised">
                            <h2 class="card-title">Keputusan</h2>
                            <?php if ($bisaMemutuskan): ?>
                                <div class="stack gap-8">
                                    <button type="button" class="button button-large primary-button full-width"
                                        style="cursor:pointer" data-open="dlg-setuju">Setujui acara</button>
                                    <button type="button" class="button button-large danger-outline-button full-width"
                                        style="cursor:pointer" data-open="dlg-tolak">Tolak acara</button>
                                </div>
                                <p class="help" style="margin-top:12px">Menyetujui tidak menerbitkan acara.</p>
                            <?php else: ?>
                                <?php if ($status === 'DISETUJUI'): ?>
                                    <p class="card-sub-line">Keputusan tersimpan: <b>Disetujui</b>. Penerbitan dilakukan terpisah sesuai alur produk.</p>
                                <?php elseif ($status === 'DITOLAK'): ?>
                                    <p class="card-sub-line" style="margin-bottom:8px">Keputusan tersimpan: <b>Ditolak</b>. Alasan:</p>
                                    <div class="reason-box" style="margin-bottom:12px"><?= nl2br(adm_h($event['alasan_penolakan'] ?? '')) ?></div>
                                <?php else: ?>
                                    <p class="card-sub-line">Acara berstatus <b><?= adm_h(adm_status_label($status)) ?></b>, jadi tidak ada keputusan yang perlu diambil.</p>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>/admin/antrean-acara" class="button secondary-button full-width"
                                    style="text-decoration:none;justify-content:center">Kembali ke antrean</a>
                            <?php endif; ?>
                        </div>

                        <!-- Kelengkapan -->
                        <div class="card">
                            <h2 class="card-title">Kelengkapan</h2>
                            <?php foreach ($kelengkapan as [$nama, $ok, $labelOk, $labelNo]): ?>
                                <div class="check-line">
                                    <span><?= adm_h($nama) ?></span>
                                    <span class="badge <?= $ok ? 'badge-success' : 'badge-warning' ?>">
                                        <span class="badge-dot"></span><?= adm_h($ok ? $labelOk : $labelNo) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Riwayat tinjauan -->
                        <div class="card">
                            <h2 class="card-title">Riwayat tinjauan</h2>
                            <?php if ($riwayat): ?>
                                <div class="timeline">
                                    <?php foreach ($riwayat as $r): ?>
                                        <div class="timeline-item">
                                            <span class="timeline-dot <?= $r['error'] ? 'timeline-dot-error' : '' ?>"></span>
                                            <b><?= adm_h(adm_tgl_jam($r['waktu'])) ?></b> · <?= adm_h($r['teks']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="slate" style="font-size:13px">Belum ada riwayat tinjauan.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php if ($bisaMemutuskan): ?>
        <!-- Dialog: setujui -->
        <dialog class="dialog" id="dlg-setuju" aria-labelledby="dlg-setuju-title">
            <form method="POST" action="">
                <?= ViewHelper::csrfField() ?>
                <input type="hidden" name="keputusan" value="APPROVE">
                <h2 class="dialog-title" id="dlg-setuju-title">Setujui acara ini?</h2>
                <p class="dialog-text">Status berubah menjadi <b>Disetujui</b>. Persetujuan tidak menerbitkan acara; penerbitan dilakukan terpisah sesuai alur produk. Penyelenggara akan diberi tahu.</p>
                <div class="dialog-actions">
                    <button type="button" class="button secondary-button" data-close style="cursor:pointer">Batal</button>
                    <button type="submit" class="button primary-button" style="cursor:pointer">Ya, setujui</button>
                </div>
            </form>
        </dialog>

        <!-- Dialog: tolak -->
        <dialog class="dialog" id="dlg-tolak" aria-labelledby="dlg-tolak-title">
            <form method="POST" action="">
                <?= ViewHelper::csrfField() ?>
                <input type="hidden" name="keputusan" value="REJECT">
                <h2 class="dialog-title" id="dlg-tolak-title">Tolak acara ini?</h2>
                <p class="dialog-text">Alasan akan dikirim ke penyelenggara, yang dapat merevisi lalu mengajukan ulang.</p>
                <label class="field-label" for="alasan-tolak">Alasan penolakan (wajib)</label>
                <textarea id="alasan-tolak" name="catatan" class="input input-area" rows="4" required minlength="10"
                    style="height:auto;display:block;padding:10px 12px"
                    placeholder="Jelaskan bagian yang perlu direvisi (minimal 10 karakter)"></textarea>
                <div class="dialog-actions">
                    <button type="button" class="button secondary-button" data-close style="cursor:pointer">Batal</button>
                    <button type="submit" class="button danger-button" style="cursor:pointer">Tolak acara</button>
                </div>
            </form>
        </dialog>

        <script>
            document.querySelectorAll('[data-open]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var d = document.getElementById(btn.getAttribute('data-open'));
                    if (d && d.showModal) { d.showModal(); }
                });
            });
            document.querySelectorAll('dialog [data-close]').forEach(function (btn) {
                btn.addEventListener('click', function () { btn.closest('dialog').close(); });
            });
            document.querySelectorAll('dialog.dialog').forEach(function (d) {
                d.addEventListener('click', function (e) { if (e.target === d) { d.close(); } });
            });
        </script>
    <?php endif; ?>

    <script src="<?= BASE_URL ?>/assets/default-banner.js"></script>
</body>

</html>