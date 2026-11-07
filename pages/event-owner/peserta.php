<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EO::loadEvent($db, $owner);
$id = $event['id'];

const PER_PAGE = 8;
$q = trim((string) ($_GET['q'] ?? ''));
$kategori = trim((string) ($_GET['kategori'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$page = max(1, (int) ($_GET['hal'] ?? 1));
$selectedId = trim((string) ($_GET['tiket'] ?? ''));

$where = ['t.event_id = :id'];
$params = ['id' => $id];
if ($q !== '') {
    $where[] = '(u.nama ILIKE :q OR u.email ILIKE :q OR t.kode_tiket ILIKE :q)';
    $params['q'] = '%' . addcslashes($q, '%_\\') . '%';
}
if ($kategori !== '') {
    $where[] = 't.jenis_tiket = :kat';
    $params['kat'] = $kategori;
}
if (in_array($status, ['AKTIF', 'SUDAH_PAKAI', 'MENUNGGU_PEMBAYARAN', 'BATAL'], true)) {
    $where[] = 't.status_tiket = :st';
    $params['st'] = $status;
} else {
    $status = '';
}
$whereSql = implode(' AND ', $where);
$from = "FROM tickets t JOIN users u ON u.id = t.user_id WHERE $whereSql";
$cols = 't.id, t.kode_tiket, t.jenis_tiket, t.status_tiket, t.waktu_daftar, u.nama, u.email, u.no_whatsapp, u.institusi';

// Ekspor CSV (mengikuti filter aktif).
if (($_GET['export'] ?? '') === 'csv') {
    $stmt = $db->prepare("SELECT $cols $from ORDER BY t.waktu_daftar DESC");
    $stmt->execute($params);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="peserta-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $safe = static fn($v) => is_string($v) && $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false ? "'" . $v : $v;
    fputcsv($out, ['Nama', 'Surel', 'WhatsApp', 'Institusi', 'Kategori', 'No. tiket', 'Status', 'Waktu daftar']);
    foreach ($stmt as $r) {
        fputcsv($out, array_map($safe, [$r['nama'], $r['email'], $r['no_whatsapp'], $r['institusi'], $r['jenis_tiket'],
            $r['kode_tiket'], EO::ticketBadge($r['status_tiket'])[1], $r['waktu_daftar']]));
    }
    exit;
}

$stmt = $db->prepare("SELECT COUNT(*) $from");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pages = max(1, (int) ceil($total / PER_PAGE));
$page = min($page, $pages);

$stmt = $db->prepare("SELECT $cols $from ORDER BY t.waktu_daftar DESC LIMIT " . PER_PAGE . ' OFFSET ' . (($page - 1) * PER_PAGE));
$stmt->execute($params);
$rows = $stmt->fetchAll();

$selected = null;
foreach ($rows as $r) {
    if ($r['id'] === $selectedId) {
        $selected = $r;
    }
}
$selected ??= $rows[0] ?? null;

$stmt = $db->prepare("SELECT DISTINCT jenis_tiket FROM tickets WHERE event_id = :id ORDER BY 1");
$stmt->execute(['id' => $id]);
$kategoriList = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stats = EO::stats($db, $event);
$link = static fn(array $over = []) => EO::url('peserta', $id, array_filter(
    array_merge(['q' => $q, 'kategori' => $kategori, 'status' => $status], $over),
    static fn($v) => $v !== '' && $v !== null
));

$actions = '<a href="' . EO::e($link(['export' => 'csv'])) . '" class="button secondary-button" style="text-decoration:none;cursor:pointer">Ekspor CSV</a>';
$subtitle = '<span class="slate" style="font-size:16px">' . $stats['pendaftar'] . ' pendaftar</span>';

EO::open($db, $owner, $event, 'peserta', $subtitle, $actions, 'Peserta', 'Peserta');
?>
                <form method="GET" action="<?= EO::e(BASE_URL . '/event-owner/peserta') ?>" class="filter-bar" style="margin-bottom:16px">
                    <input type="hidden" name="id" value="<?= EO::e($id) ?>">
                    <input type="text" name="q" class="input" style="flex:1;width:auto" placeholder="Cari nama, surel, atau nomor tiket" value="<?= EO::e($q) ?>">
                    <select name="kategori" class="input" style="width:170px" onchange="this.form.submit()">
                        <option value="">Semua kategori</option>
                        <?php foreach ($kategoriList as $k): ?>
                            <option value="<?= EO::e($k) ?>" <?= $k === $kategori ? 'selected' : '' ?>><?= EO::e($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="input" style="width:170px" onchange="this.form.submit()">
                        <option value="">Semua status</option>
                        <?php foreach (['AKTIF', 'SUDAH_PAKAI', 'MENUNGGU_PEMBAYARAN', 'BATAL'] as $s): ?>
                            <option value="<?= $s ?>" <?= $s === $status ? 'selected' : '' ?>><?= EO::e(EO::ticketBadge($s)[1]) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button secondary-button" style="cursor:pointer">Cari</button>
                </form>

                <div class="cols">
                    <div style="flex:1;min-width:0">
                        <div class="tbl-card" style="padding:8px 16px">
                            <div class="tbl-head">
                                <div class="tbl-cell" style="flex:2">PESERTA</div>
                                <div class="tbl-cell" style="flex:1">KATEGORI</div>
                                <div class="tbl-cell" style="flex:1.4">NO. TIKET</div>
                                <div class="tbl-cell" style="flex:.9">DAFTAR</div>
                                <div class="tbl-cell" style="flex:1.4">STATUS</div>
                            </div>
                            <?php foreach ($rows as $r): ?>
                                <a href="<?= EO::e($link(['tiket' => $r['id'], 'hal' => $page > 1 ? (string) $page : ''])) ?>" class="tbl-row" style="text-decoration:none;color:inherit;<?= $selected && $selected['id'] === $r['id'] ? 'background:#f6f5ff;' : '' ?>">
                                    <div class="tbl-cell" style="flex:2;min-width:0;overflow-wrap:anywhere"><b><?= EO::e($r['nama']) ?></b><div class="cell-sub"><?= EO::e($r['email']) ?></div></div>
                                    <div class="tbl-cell" style="flex:1"><?= EO::e($r['jenis_tiket']) ?></div>
                                    <div class="tbl-cell" style="flex:1.4;overflow-wrap:anywhere"><?= EO::e($r['kode_tiket']) ?></div>
                                    <div class="tbl-cell" style="flex:.9"><?= EO::e(EO::tanggalPendek($r['waktu_daftar'])) ?></div>
                                    <div class="tbl-cell" style="flex:1.4"><?= EO::badge(EO::ticketBadge($r['status_tiket'])) ?></div>
                                </a>
                            <?php endforeach; ?>
                            <?php if (!$rows): ?>
                                <div class="empty-note">Tidak ada peserta yang sesuai.</div>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding:0 4px">
                            <span class="slate" style="font-size:14px"><?= $total ? (($page - 1) * PER_PAGE + 1) . '–' . (($page - 1) * PER_PAGE + count($rows)) : 0 ?> dari <?= $total ?></span>
                            <div style="display:flex;gap:8px">
                                <?php if ($page > 1): ?><a class="button secondary-button small-button" style="text-decoration:none;cursor:pointer" href="<?= EO::e($link(['hal' => (string) ($page - 1)])) ?>">Sebelumnya</a><?php endif; ?>
                                <?php if ($page < $pages): ?><a class="button secondary-button small-button" style="text-decoration:none;cursor:pointer" href="<?= EO::e($link(['hal' => (string) ($page + 1)])) ?>">Berikutnya</a><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="width:424px;flex:none;box-sizing:border-box">
                        <?php if ($selected): ?>
                            <h2 class="card-title" style="margin-bottom:12px"><?= EO::e($selected['nama']) ?></h2>
                            <div class="two-fields" style="margin-bottom:16px;row-gap:16px;flex-wrap:wrap">
                                <div><div class="metric-label">TIKET</div><b style="font-size:14px"><?= EO::e($selected['jenis_tiket']) ?> · <?= EO::e($selected['kode_tiket']) ?></b></div>
                                <div><div class="metric-label">STATUS</div><?= EO::badge(EO::ticketBadge($selected['status_tiket'])) ?></div>
                            </div>
                            <div class="two-fields" style="margin-bottom:20px;row-gap:16px;flex-wrap:wrap">
                                <div><div class="metric-label">WHATSAPP</div><b style="font-size:14px"><?= EO::e($selected['no_whatsapp'] ?: '-') ?></b></div>
                                <div><div class="metric-label">INSTITUSI</div><b style="font-size:14px"><?= EO::e($selected['institusi'] ?: '-') ?></b></div>
                            </div>
                            <?php if (in_array($selected['status_tiket'], ['AKTIF', 'MENUNGGU_PEMBAYARAN'], true)): ?>
                                <div class="help help-error" id="e-form" style="display:none;margin:0 0 8px"></div>
                                <button type="button" id="btn-batal" class="button danger-outline-button full-width" style="cursor:pointer">Batalkan tiket</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="slate" style="font-size:14px">Pilih peserta untuk melihat detail.</p>
                        <?php endif; ?>
                    </div>
                </div>
<?php
$script = '';
if ($selected && in_array($selected['status_tiket'], ['AKTIF', 'MENUNGGU_PEMBAYARAN'], true)) {
    $script = '<script>
document.getElementById("btn-batal").addEventListener("click", async (e) => {
    if (!confirm("Batalkan tiket ' . EO::e(addslashes($selected['kode_tiket'])) . '? Tindakan ini tidak dapat dibatalkan.")) return;
    e.target.disabled = true;
    const r = await eoApi("cancel-ticket/' . EO::e($selected['id']) . '", "PATCH");
    if (r.ok) { location.reload(); return; }
    e.target.disabled = false;
    eoShowError(document.getElementById("e-form"), r.message);
});
</script>';
}
EO::close($script);
