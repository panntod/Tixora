<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/../../api/helpers/AccountApplicationHelper.php';
require_once __DIR__ . '/_helpers.php';
$db = ViewHelper::getDb();
$admin = ViewHelper::requireLogin(AuthHelper::ROLE_ADMIN);

$activePage = 'notifikasi';

AccountApplicationHelper::ensureSchema($db);

$uuidRe = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

function pa_role_label(string $roleId): array
{
    return match ($roleId) {
        AuthHelper::ROLE_ADMIN       => ['badge-neutral', 'Admin'],
        AuthHelper::ROLE_EVENT_OWNER => ['badge-info', 'Penyelenggara'],
        default                      => ['badge-purple', 'Peserta'],
    };
}

// === FILTER ===
$search       = trim((string) ($_GET['search'] ?? ($_POST['search'] ?? '')));
$roleFilter   = trim((string) ($_GET['role'] ?? ($_POST['role'] ?? '')));
$statusFilter = trim((string) ($_GET['status'] ?? ($_POST['status'] ?? '')));
if ($roleFilter !== '' && !preg_match($uuidRe, $roleFilter)) {
    $roleFilter = '';
}
if ($statusFilter !== '' && !in_array($statusFilter, AccountApplicationHelper::STATUSES, true)) {
    $statusFilter = '';
}

/** Query string filter aktif (untuk redirect / tautan), ditambah parameter ekstra. */
function pa_query(string $search, string $role, string $status, array $extra = []): string
{
    $q = array_filter(['search' => $search, 'role' => $role, 'status' => $status] + $extra, fn($v) => $v !== '');
    return $q ? '?' . http_build_query($q) : '';
}

$errorMsg = '';

// === TINDAK: SETUJUI / TOLAK ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = trim((string) ($_POST['id'] ?? ''));
    $aksi     = (string) ($_POST['action'] ?? '');
    $alasan   = trim((string) ($_POST['alasan'] ?? ''));

    if (!ViewHelper::checkCsrf()) {
        $errorMsg = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (!preg_match($uuidRe, $targetId) || !in_array($aksi, ['approve', 'reject'], true)) {
        $errorMsg = 'Permintaan tidak valid.';
    } elseif ($aksi === 'reject' && $alasan === '') {
        $errorMsg = 'Alasan penolakan wajib diisi.';
    } else {
        if ($aksi === 'approve') {
            // Menyetujui juga boleh dari DITOLAK (keputusan ditinjau ulang).
            $stmt = $db->prepare("
                UPDATE users
                SET status_pengajuan = 'DISETUJUI', ditinjau_oleh = :admin, ditinjau_pada = LOCALTIMESTAMP,
                    alasan_penolakan_akun = NULL, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND status_pengajuan IN ('MENUNGGU', 'DITOLAK')
            ");
            $stmt->execute(['admin' => $admin['id'], 'id' => $targetId]);
        } else {
            $stmt = $db->prepare("
                UPDATE users
                SET status_pengajuan = 'DITOLAK', ditinjau_oleh = :admin, ditinjau_pada = LOCALTIMESTAMP,
                    alasan_penolakan_akun = :alasan, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND status_pengajuan = 'MENUNGGU'
            ");
            $stmt->execute(['admin' => $admin['id'], 'alasan' => $alasan, 'id' => $targetId]);
        }

        if ($stmt->rowCount() === 0) {
            $errorMsg = 'Pengajuan ini sudah ditinjau oleh admin lain.';
        } else {
            header('Location: ' . BASE_URL . '/admin/permohonan-akun'
                . pa_query($search, $roleFilter, $statusFilter, ['saved' => $aksi, 'u' => $targetId]));
            exit;
        }
    }
}

// === DATA ===
$where  = ['u.status_pengajuan IS NOT NULL'];
$params = [];
if ($search !== '') {
    $where[] = '(u.nama ILIKE :s OR u.email ILIKE :s)';
    $params['s'] = '%' . $search . '%';
}
if ($roleFilter !== '') {
    $where[] = 'u.role_id = :role';
    $params['role'] = $roleFilter;
}
if ($statusFilter !== '') {
    $where[] = 'u.status_pengajuan = :status';
    $params['status'] = $statusFilter;
}
$whereSql = implode(' AND ', $where);

$sqlList = "
    SELECT u.id, u.nama, u.email, u.no_whatsapp, u.institusi, u.role_id, u.status_pengajuan,
           u.diajukan_pada, u.alasan_penolakan_akun
    FROM users u
    WHERE {$whereSql}
    ORDER BY CASE u.status_pengajuan WHEN 'MENUNGGU' THEN 0 ELSE 1 END, u.diajukan_pada DESC NULLS LAST, u.nama ASC
";

// === EKSPOR DAFTAR (CSV) — mengikuti filter aktif ===
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = $db->prepare($sqlList);
    $stmt->execute($params);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="tixora-pengajuan-akun-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM untuk Excel
    fputcsv($out, ['Nama', 'Surel', 'WhatsApp', 'Institusi', 'Peran', 'Mengajukan', 'Status Pengajuan', 'Alasan Penolakan']);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $r['nama'],
            $r['email'],
            $r['no_whatsapp'] ?? '',
            $r['institusi'] ?? '',
            pa_role_label((string) $r['role_id'])[1],
            $r['diajukan_pada'] ? date('Y-m-d', strtotime((string) $r['diajukan_pada'])) : '',
            AccountApplicationHelper::badge($r['status_pengajuan'])[1],
            $r['alasan_penolakan_akun'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

$stmt = $db->prepare($sqlList . ' LIMIT 100');
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPengajuan = (int) $db->query("SELECT COUNT(*) FROM users WHERE status_pengajuan IS NOT NULL")->fetchColumn();
$totalMenunggu  = (int) $db->query("SELECT COUNT(*) FROM users WHERE status_pengajuan = 'MENUNGGU'")->fetchColumn();
$menungguCount  = (int) $db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN'")->fetchColumn();

$roleOptions = $db->query("
    SELECT DISTINCT r.id
    FROM users u JOIN roles r ON r.id = u.role_id
    WHERE u.status_pengajuan IS NOT NULL
")->fetchAll(PDO::FETCH_COLUMN);

$statusLabel = ['MENUNGGU' => 'Menunggu', 'DISETUJUI' => 'Disetujui', 'DITOLAK' => 'Ditolak'];

// Pesan sukses setelah redirect.
$successMsg = '';
$saved = (string) ($_GET['saved'] ?? '');
$savedUser = (string) ($_GET['u'] ?? '');
if (in_array($saved, ['approve', 'reject'], true) && preg_match($uuidRe, $savedUser)) {
    $stmtN = $db->prepare("SELECT nama FROM users WHERE id = :id");
    $stmtN->execute(['id' => $savedUser]);
    $namaSaved = $stmtN->fetchColumn();
    if ($namaSaved !== false) {
        $successMsg = 'Pengajuan akun ' . $namaSaved . ($saved === 'approve' ? ' disetujui.' : ' ditolak.');
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TIXORA — Admin — Notifikasi</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
    <script src="<?= BASE_URL ?>/admin/responsive.js" defer></script>
</head>

<body>
    <div class="frame" style="width:1440px;min-height:700px;height:auto">
        <div class="app">
            <?php include __DIR__ . '/_sidebar.php'; ?>

            <main class="main">
                <p class="crumb">Notifikasi</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Notifikasi</h1>
                        <p class="page-subtitle"><?= number_format($totalPengajuan, 0, ',', '.') ?> pengajuan akun<?= $totalMenunggu > 0 ? ' · ' . $totalMenunggu . ' menunggu' : '' ?></p>
                    </div>
                    <div class="head-actions">
                        <a href="<?= BASE_URL ?>/admin/permohonan-akun<?= adm_h(pa_query($search, $roleFilter, $statusFilter, ['export' => 'csv'])) ?>"
                           class="button secondary-button">Ekspor daftar</a>
                    </div>
                </div>

                <?php if ($successMsg !== ''): ?>
                    <div class="alert alert-success" style="margin-bottom:16px"><?= adm_h($successMsg) ?></div>
                <?php endif; ?>
                <?php if ($errorMsg !== ''): ?>
                    <div class="alert alert-error" style="margin-bottom:16px"><?= adm_h($errorMsg) ?></div>
                <?php endif; ?>

                <form method="GET" action="<?= BASE_URL ?>/admin/permohonan-akun" class="pa-filter">
                    <input type="text" name="search" class="input" placeholder="Cari nama atau surel"
                        value="<?= adm_h($search) ?>" aria-label="Cari nama atau surel">
                    <select name="role" class="input select-input pa-role" aria-label="Filter peran" onchange="this.form.submit()">
                        <option value="">Semua peran</option>
                        <?php foreach ($roleOptions as $rid): ?>
                            <option value="<?= adm_h($rid) ?>" <?= $roleFilter === $rid ? 'selected' : '' ?>><?= adm_h(pa_role_label((string) $rid)[1]) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="input select-input pa-stat" aria-label="Filter status" onchange="this.form.submit()">
                        <option value="">Semua status</option>
                        <?php foreach ($statusLabel as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $statusFilter === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button type="submit" class="button secondary-button">Terapkan</button></noscript>
                </form>

                <div class="tbl-card">
                    <div class="tbl-head">
                        <div class="tbl-cell ap-nama">NAMA</div>
                        <div class="tbl-cell ap-surel">SUREL</div>
                        <div class="tbl-cell ap-peran">PERAN</div>
                        <div class="tbl-cell ap-tgl">MENGAJUKAN</div>
                        <div class="tbl-cell ap-status">STATUS PENGAJUAN</div>
                        <div class="tbl-cell ap-aksi"></div>
                    </div>

                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $r):
                            $role  = pa_role_label((string) $r['role_id']);
                            $badge = AccountApplicationHelper::badge($r['status_pengajuan']);
                            $tgl   = $r['diajukan_pada'] ? adm_tgl((string) $r['diajukan_pada'], true) : '—';
                        ?>
                            <div class="tbl-row">
                                <div class="tbl-cell ap-nama"><b><?= adm_h($r['nama']) ?></b></div>
                                <div class="tbl-cell ap-surel"><?= adm_h($r['email']) ?></div>
                                <div class="tbl-cell ap-peran">
                                    <span class="badge <?= $role[0] ?>"><span class="badge-dot"></span><?= $role[1] ?></span>
                                </div>
                                <div class="tbl-cell ap-tgl"><?= adm_h($tgl) ?></div>
                                <div class="tbl-cell ap-status">
                                    <span class="badge <?= $badge[0] ?>"><span class="badge-dot"></span><?= $badge[1] ?></span>
                                </div>
                                <div class="tbl-cell tbl-right ap-aksi">
                                    <button type="button" class="button secondary-button small-button"
                                        data-tindak
                                        data-id="<?= adm_h($r['id']) ?>"
                                        data-nama="<?= adm_h($r['nama']) ?>"
                                        data-email="<?= adm_h($r['email']) ?>"
                                        data-wa="<?= adm_h($r['no_whatsapp'] ?? '') ?>"
                                        data-institusi="<?= adm_h($r['institusi'] ?? '') ?>"
                                        data-tgl="<?= adm_h($tgl) ?>"
                                        data-status="<?= adm_h($r['status_pengajuan']) ?>"
                                        data-status-label="<?= adm_h($badge[1]) ?>"
                                        data-alasan="<?= adm_h($r['alasan_penolakan_akun'] ?? '') ?>">Tindak</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:32px 16px;text-align:center">
                            <p class="slate"><?= ($search !== '' || $roleFilter !== '' || $statusFilter !== '')
                                ? 'Tidak ada pengajuan yang cocok dengan filter.'
                                : 'Belum ada pengajuan akun.' ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <dialog class="dialog" id="dlg-tindak" data-base="<?= adm_h(BASE_URL) ?>">
                    <form method="POST" action="" style="margin:0">
                        <?= ViewHelper::csrfField() ?>
                        <input type="hidden" name="id" id="tindak-id">
                        <input type="hidden" name="search" value="<?= adm_h($search) ?>">
                        <input type="hidden" name="role" value="<?= adm_h($roleFilter) ?>">
                        <input type="hidden" name="status" value="<?= adm_h($statusFilter) ?>">

                        <h3 class="dialog-title" id="tindak-judul">Tinjau pengajuan akun</h3>
                        <div class="pa-kv-list">
                            <div class="pa-kv"><span>Nama</span><b data-f="nama"></b></div>
                            <div class="pa-kv"><span>Surel</span><b data-f="email"></b></div>
                            <div class="pa-kv"><span>WhatsApp</span><b data-f="wa"></b></div>
                            <div class="pa-kv"><span>Institusi</span><b data-f="institusi"></b></div>
                            <div class="pa-kv"><span>Mengajukan</span><b data-f="tgl"></b></div>
                            <div class="pa-kv"><span>Status</span><b data-f="status-label"></b></div>
                        </div>

                        <div id="tindak-alasan-wrap" style="margin-top:16px">
                            <label class="field-label" for="tindak-alasan" id="tindak-alasan-label">Alasan penolakan (wajib bila menolak)</label>
                            <textarea id="tindak-alasan" name="alasan" class="input" style="height:88px"></textarea>
                        </div>

                        <div class="dialog-actions">
                            <button type="button" class="button secondary-button" data-close>Tutup</button>
                            <a href="#" class="button secondary-button" id="tindak-lihat" hidden>Lihat penyelenggara</a>
                            <button type="submit" name="action" value="reject" class="button danger-outline-button" id="tindak-tolak">Tolak</button>
                            <button type="submit" name="action" value="approve" class="button primary-button" id="tindak-setujui">Setujui</button>
                        </div>
                    </form>
                </dialog>
            </main>
        </div>
    </div>

    <script>
        (function () {
            var dlg = document.getElementById('dlg-tindak');
            if (!dlg) { return; }
            var ta = document.getElementById('tindak-alasan');
            var wrap = document.getElementById('tindak-alasan-wrap');
            var label = document.getElementById('tindak-alasan-label');
            var btnTolak = document.getElementById('tindak-tolak');
            var btnSetujui = document.getElementById('tindak-setujui');
            var lihat = document.getElementById('tindak-lihat');

            function setText(name, value) {
                var el = dlg.querySelector('[data-f="' + name + '"]');
                if (el) { el.textContent = value && value !== '' ? value : '—'; }
            }

            document.querySelectorAll('[data-tindak]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var d = btn.dataset;
                    document.getElementById('tindak-id').value = d.id;
                    ['nama', 'email', 'wa', 'institusi', 'tgl'].forEach(function (k) { setText(k, d[k]); });
                    setText('status-label', d.statusLabel);

                    var status = d.status;
                    ta.value = '';
                    ta.setCustomValidity('');
                    document.getElementById('tindak-judul').textContent =
                        status === 'MENUNGGU' ? 'Tinjau pengajuan akun' : 'Detail pengajuan akun';

                    // Menunggu: setujui / tolak (alasan wajib bila menolak).
                    // Ditolak: tampilkan alasan, boleh disetujui ulang. Disetujui: hanya info + tautan.
                    btnTolak.hidden = status !== 'MENUNGGU';
                    btnSetujui.hidden = status === 'DISETUJUI';
                    lihat.hidden = status !== 'DISETUJUI';
                    lihat.href = dlg.dataset.base + '/admin/penyelenggara?id=' + encodeURIComponent(d.id);

                    if (status === 'MENUNGGU') {
                        wrap.hidden = false; ta.hidden = false; ta.readOnly = false;
                        label.textContent = 'Alasan penolakan (wajib bila menolak)';
                    } else if (status === 'DITOLAK') {
                        wrap.hidden = false; ta.hidden = false; ta.readOnly = true;
                        ta.value = d.alasan || '';
                        label.textContent = 'Alasan penolakan';
                    } else {
                        wrap.hidden = true;
                    }

                    if (typeof dlg.showModal === 'function') { dlg.showModal(); } else { dlg.setAttribute('open', ''); }
                });
            });

            btnTolak.addEventListener('click', function (e) {
                if (ta.value.trim() === '') {
                    e.preventDefault();
                    ta.setCustomValidity('Alasan penolakan wajib diisi.');
                    ta.reportValidity();
                }
            });
            ta.addEventListener('input', function () { ta.setCustomValidity(''); });

            dlg.querySelectorAll('[data-close]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (typeof dlg.close === 'function') { dlg.close(); } else { dlg.removeAttribute('open'); }
                });
            });
        })();
    </script>
</body>

</html>