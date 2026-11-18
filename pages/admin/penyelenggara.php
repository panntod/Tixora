<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/_helpers.php';
$db = ViewHelper::getDb();
$admin = ViewHelper::requireLogin(AuthHelper::ROLE_ADMIN);

$activePage = 'penyelenggara';

$uuidRe = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

$errorMsg = '';

// === TANGGUHKAN / AKTIFKAN KEMBALI ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = trim($_POST['id'] ?? '');

    if (!ViewHelper::checkCsrf()) {
        $errorMsg = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (($_POST['action'] ?? '') === 'toggle_suspend' && preg_match($uuidRe, $targetId)) {
        $stmt = $db->prepare("SELECT id, is_active FROM users WHERE id = :id AND role_id = :role");
        $stmt->execute(['id' => $targetId, 'role' => AuthHelper::ROLE_EVENT_OWNER]);
        $target = $stmt->fetch();

        if (!$target) {
            $errorMsg = 'Akun penyelenggara tidak ditemukan.';
        } else {
            $newState = !(bool)$target['is_active'];
            $alasan   = trim($_POST['alasan'] ?? '');

            if (!$newState && $alasan === '') {
                $errorMsg = 'Alasan penangguhan wajib diisi.';
            } else {
                $db->prepare("
                    UPDATE users
                    SET is_active = :active,
                        alasan_suspensi = :alasan,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ")->execute([
                    'active' => $newState ? 'true' : 'false',
                    'alasan' => $newState ? null : $alasan,
                    'id'     => $targetId,
                ]);
                header('Location: ' . BASE_URL . '/admin/penyelenggara?id=' . urlencode($targetId)
                    . '&saved=' . ($newState ? 'activate' : 'suspend'));
                exit;
            }
        }
    }
}

// === DAFTAR PENYELENGGARA ===
$stmt = $db->prepare("
    SELECT u.id, u.nama, u.email, u.no_whatsapp, u.institusi, u.is_active, u.alasan_suspensi, u.created_at,
        u.nama AS nama_tampil,
        (SELECT COUNT(*) FROM events e WHERE e.owner_id = u.id) AS total_event,
        (SELECT COUNT(*) FROM tickets t
           JOIN events e ON e.id = t.event_id
          WHERE e.owner_id = u.id AND t.status_tiket != 'BATAL') AS total_pendaftar
    FROM users u
    WHERE u.role_id = :role_id
    ORDER BY u.is_active DESC, total_event DESC, nama_tampil ASC
");
$stmt->execute(['role_id' => AuthHelper::ROLE_EVENT_OWNER]);
$owners = $stmt->fetchAll();

$totalCount    = count($owners);
$menungguCount = (int)$db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN'")->fetchColumn();

// === EKSPOR DAFTAR (CSV) ===
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tixora-penyelenggara-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM untuk Excel
    fputcsv($out, ['Penyelenggara', 'Penanggung Jawab', 'Surel', 'WhatsApp', 'Bergabung',
                   'Total Acara', 'Total Pendaftar', 'Status']);
    foreach ($owners as $o) {
        fputcsv($out, [
            $o['nama_tampil'],
            $o['nama'],
            $o['email'],
            $o['no_whatsapp'] ?? '',
            date('Y-m-d', strtotime($o['created_at'])),
            (int)$o['total_event'],
            (int)$o['total_pendaftar'],
            $o['is_active'] ? 'Aktif' : 'Ditangguhkan',
        ]);
    }
    fclose($out);
    exit;
}

// === PENYELENGGARA TERPILIH (default: yang pertama) ===
$selectedId = trim($_GET['id'] ?? ($_POST['id'] ?? ''));
$selected = null;
foreach ($owners as $o) {
    if (strcasecmp($o['id'], $selectedId) === 0) {
        $selected = $o;
        break;
    }
}
if ($selected === null && !empty($owners)) {
    $selected = $owners[0];
}

$riwayat = ['disetujui' => 0, 'ditolak' => 0, 'menunggu' => 0];
$acaraTerkait = [];
if ($selected !== null) {
    $stmtRiwayat = $db->prepare("
        SELECT
            COUNT(*) FILTER (WHERE l.status = 'DISETUJUI') AS disetujui,
            COUNT(*) FILTER (WHERE l.status = 'DITOLAK')   AS ditolak
        FROM event_submission_logs l
        JOIN events e ON e.id = l.event_id
        WHERE e.owner_id = :id
    ");
    $stmtRiwayat->execute(['id' => $selected['id']]);
    $r = $stmtRiwayat->fetch();
    $riwayat['disetujui'] = (int)($r['disetujui'] ?? 0);
    $riwayat['ditolak']   = (int)($r['ditolak'] ?? 0);

    $stmtMenunggu = $db->prepare("
        SELECT COUNT(*) FROM events WHERE owner_id = :id AND status_verifikasi = 'MENUNGGU_TINJAUAN'
    ");
    $stmtMenunggu->execute(['id' => $selected['id']]);
    $riwayat['menunggu'] = (int)$stmtMenunggu->fetchColumn();

    $stmtAcara = $db->prepare("
        SELECT e.id, e.judul_event, e.status_verifikasi, e.tanggal_mulai, e.tanggal_selesai
        FROM events e
        WHERE e.owner_id = :id
        ORDER BY e.created_at DESC
        LIMIT 10
    ");
    $stmtAcara->execute(['id' => $selected['id']]);
    $acaraTerkait = $stmtAcara->fetchAll();
}

/** Rentang tanggal ringkas: tahun disembunyikan bila sama dengan tahun berjalan. */
function pny_rentang(?string $mulai, ?string $selesai): string
{
    $txt = adm_rentang($mulai, $selesai);
    if ($mulai && date('Y', strtotime($mulai)) === date('Y')) {
        $txt = preg_replace('/ \d{4}$/', '', $txt);
    }
    return $txt;
}

$saved = $_GET['saved'] ?? '';
$successMsg = '';
if ($selected !== null && $saved === 'suspend') {
    $successMsg = 'Akun ' . $selected['nama_tampil'] . ' ditangguhkan.';
} elseif ($selected !== null && $saved === 'activate') {
    $successMsg = 'Akun ' . $selected['nama_tampil'] . ' diaktifkan kembali.';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TIXORA — Admin — Penyelenggara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
    <script src="<?= BASE_URL ?>/admin/responsive.js" defer></script>
</head>

<body>
    <div class="frame" style="width:1440px;min-height:900px;height:auto">
        <div class="app">
            <?php include __DIR__ . '/_sidebar.php'; ?>

            <main class="main">
                <p class="crumb">Penyelenggara</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Penyelenggara</h1>
                        <p class="page-subtitle"><?= number_format($totalCount, 0, ',', '.') ?> akun penyelenggara</p>
                    </div>
                    <div class="head-actions">
                        <a href="<?= BASE_URL ?>/admin/penyelenggara?export=csv" class="button secondary-button">Ekspor daftar</a>
                    </div>
                </div>

                <?php if ($successMsg !== ''): ?>
                    <div class="alert alert-success" style="margin-bottom:16px"><?= adm_h($successMsg) ?></div>
                <?php endif; ?>
                <?php if ($errorMsg !== ''): ?>
                    <div class="alert alert-error" style="margin-bottom:16px"><?= adm_h($errorMsg) ?></div>
                <?php endif; ?>

                <?php if ($selected === null): ?>
                    <div class="card" style="text-align:center">
                        <p class="slate">Belum ada penyelenggara terdaftar.</p>
                    </div>
                <?php else: ?>
                    <div class="split">
                        <!-- Daftar penyelenggara -->
                        <div class="tbl-card">
                            <div class="tbl-head">
                                <div class="tbl-cell org-name">PENYELENGGARA</div>
                                <div class="tbl-cell org-count">ACARA</div>
                                <div class="tbl-cell org-status">STATUS</div>
                            </div>
                            <?php foreach ($owners as $o): ?>
                                <a class="tbl-row org-row <?= $o['id'] === $selected['id'] ? 'is-selected' : '' ?>"
                                   href="<?= BASE_URL ?>/admin/penyelenggara?id=<?= urlencode($o['id']) ?>"
                                   <?= $o['id'] === $selected['id'] ? 'aria-current="true"' : '' ?>>
                                    <div class="tbl-cell org-name"><b><?= adm_h($o['nama_tampil']) ?></b></div>
                                    <div class="tbl-cell org-count"><?= (int)$o['total_event'] ?></div>
                                    <div class="tbl-cell org-status">
                                        <span class="badge <?= $o['is_active'] ? 'badge-success' : 'badge-error' ?>">
                                            <span class="badge-dot"></span><?= $o['is_active'] ? 'Aktif' : 'Ditangguhkan' ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <div class="stack gap-24">
                            <!-- Detail penyelenggara terpilih -->
                            <div class="card">
                                <div class="detail-head">
                                    <h2 class="detail-title"><?= adm_h($selected['nama_tampil']) ?></h2>
                                    <span class="badge <?= $selected['is_active'] ? 'badge-success' : 'badge-error' ?>">
                                        <span class="badge-dot"></span><?= $selected['is_active'] ? 'Aktif' : 'Ditangguhkan' ?>
                                    </span>
                                </div>

                                <dl class="definition" style="margin:0">
                                    <div>
                                        <dt>PENANGGUNG JAWAB</dt>
                                        <dd><?= adm_h($selected['nama']) ?></dd>
                                    </div>
                                    <div>
                                        <dt>KONTAK</dt>
                                        <dd><?= adm_h($selected['email']) ?><?= !empty($selected['no_whatsapp']) ? ' · ' . adm_h($selected['no_whatsapp']) : '' ?></dd>
                                    </div>
                                    <div>
                                        <dt>BERGABUNG</dt>
                                        <dd><?= adm_h(adm_tgl($selected['created_at'], true)) ?></dd>
                                    </div>
                                    <div>
                                        <dt>RIWAYAT PERSETUJUAN</dt>
                                        <dd><?= $riwayat['disetujui'] ?> disetujui · <?= $riwayat['ditolak'] ?> ditolak · <?= $riwayat['menunggu'] ?> menunggu</dd>
                                    </div>
                                </dl>

                                <?php if (!$selected['is_active'] && !empty($selected['alasan_suspensi'])): ?>
                                    <div class="alert alert-warning" style="margin-top:16px">
                                        Alasan penangguhan: <?= adm_h($selected['alasan_suspensi']) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="detail-actions">
                                    <a href="mailto:<?= adm_h($selected['email']) ?>" class="button secondary-button">Kirim surel</a>

                                    <?php if ($selected['is_active']): ?>
                                        <button type="button" class="button danger-outline-button" style="cursor:pointer"
                                            data-open="dlg-suspend">Tangguhkan penyelenggara</button>
                                    <?php else: ?>
                                        <form method="POST" action="" style="margin:0">
                                            <?= ViewHelper::csrfField() ?>
                                            <input type="hidden" name="action" value="toggle_suspend">
                                            <input type="hidden" name="id" value="<?= adm_h($selected['id']) ?>">
                                            <button type="submit" class="button primary-button" style="cursor:pointer">Aktifkan kembali</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Acara terkait -->
                            <div class="card" style="padding:20px 16px 8px;box-sizing:border-box">
                                <h2 class="card-title" style="padding:0 8px">Acara terkait</h2>
                                <?php if (!empty($acaraTerkait)): ?>
                                    <div class="tbl-head">
                                        <div class="tbl-cell rel-title">ACARA</div>
                                        <div class="tbl-cell rel-status">STATUS</div>
                                        <div class="tbl-cell rel-date">TANGGAL</div>
                                    </div>
                                    <?php foreach ($acaraTerkait as $ev): ?>
                                        <div class="tbl-row">
                                            <div class="tbl-cell rel-title"><b><?= adm_h($ev['judul_event']) ?></b></div>
                                            <div class="tbl-cell rel-status">
                                                <span class="badge <?= adm_status_badge($ev['status_verifikasi']) ?>">
                                                    <span class="badge-dot"></span><?= adm_h(adm_status_label($ev['status_verifikasi'])) ?>
                                                </span>
                                            </div>
                                            <div class="tbl-cell rel-date"><?= adm_h(pny_rentang($ev['tanggal_mulai'], $ev['tanggal_selesai'])) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="padding:24px 8px 16px">
                                        <p class="slate">Penyelenggara ini belum memiliki acara.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($selected['is_active']): ?>
                        <dialog class="dialog" id="dlg-suspend">
                            <form method="POST" action="" style="margin:0">
                                <?= ViewHelper::csrfField() ?>
                                <input type="hidden" name="action" value="toggle_suspend">
                                <input type="hidden" name="id" value="<?= adm_h($selected['id']) ?>">
                                <h3 class="dialog-title">Tangguhkan penyelenggara <?= adm_h($selected['nama_tampil']) ?>?</h3>
                                <p class="dialog-text">Penyelenggara tidak dapat membuat atau mengelola acara selama
                                    ditangguhkan. Acara yang sedang terbit perlu keputusan produk.</p>
                                <div>
                                    <label class="field-label" for="alasan-suspensi">Alasan (wajib)</label>
                                    <textarea id="alasan-suspensi" name="alasan" class="input" style="height:88px" required></textarea>
                                </div>
                                <div class="dialog-actions">
                                    <button type="button" class="button secondary-button" style="cursor:pointer" data-close>Batal</button>
                                    <button type="submit" class="button danger-button" style="cursor:pointer">Tangguhkan</button>
                                </div>
                            </form>
                        </dialog>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        (function () {
            var dlg = document.getElementById('dlg-suspend');
            if (!dlg) { return; }
            document.querySelectorAll('[data-open="dlg-suspend"]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (typeof dlg.showModal === 'function') { dlg.showModal(); } else { dlg.setAttribute('open', ''); }
                    var ta = dlg.querySelector('textarea');
                    if (ta) { ta.focus(); }
                });
            });
            dlg.querySelectorAll('[data-close]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (typeof dlg.close === 'function') { dlg.close(); } else { dlg.removeAttribute('open'); }
                });
            });
        })();
    </script>
</body>

</html>