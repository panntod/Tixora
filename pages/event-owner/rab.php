<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EO::loadEvent($db, $owner);
$id = $event['id'];

$stmt = $db->prepare('SELECT kategori, rencana, realisasi FROM rab_details WHERE event_id = :id ORDER BY rencana DESC, created_at ASC');
$stmt->execute(['id' => $id]);
$items = $stmt->fetchAll();

$totalRencana = 0.0;
$totalRealisasi = 0.0;
$melebihi = [];
foreach ($items as $it) {
    $totalRencana += (float) $it['rencana'];
    $totalRealisasi += (float) $it['realisasi'];
    if ((float) $it['realisasi'] > (float) $it['rencana']) {
        $melebihi[] = $it;
    }
}
$sisa = $totalRencana - $totalRealisasi;

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="rab-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $safe = static fn($v) => is_string($v) && $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false ? "'" . $v : $v;
    fputcsv($out, ['Kategori', 'Rencana', 'Realisasi', 'Selisih']);
    foreach ($items as $it) {
        fputcsv($out, [$safe($it['kategori']), (float) $it['rencana'], (float) $it['realisasi'], (float) $it['rencana'] - (float) $it['realisasi']]);
    }
    fputcsv($out, ['Total', $totalRencana, $totalRealisasi, $sisa]);
    exit;
}

$selisihText = static fn(float $v): string => ($v < 0 ? '−' : '+') . EO::rp(abs($v));
$persen = $totalRencana > 0 ? (int) round($totalRealisasi / $totalRencana * 100) : 0;

$actions = '<a href="' . EO::e(EO::url('rab', $id, ['export' => 'csv'])) . '" class="button secondary-button" style="text-decoration:none;cursor:pointer">Ekspor RAB</a>'
    . '<a href="#form-rab" class="button primary-button" style="text-decoration:none;cursor:pointer" onclick="document.getElementById(\'f-kategori\').focus()">+ Tambah pengeluaran</a>';
$subtitle = '<span class="slate" style="font-size:16px">Bandingkan rencana dengan realisasi pengeluaran</span>';

EO::open($db, $owner, $event, 'rab', $subtitle, $actions, 'Rencana anggaran biaya', 'RAB');
?>
                <div class="metrics" style="margin-bottom:20px">
                    <div class="metric">
                        <div class="metric-label">RENCANA</div>
                        <div class="metric-value" style="color:#3c29b8"><?= EO::e(EO::rp($totalRencana)) ?></div>
                        <div class="metric-note"><?= count($items) ?> pos anggaran</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">REALISASI</div>
                        <div class="metric-value"><?= EO::e(EO::rp($totalRealisasi)) ?></div>
                        <div class="metric-note"><?= $persen ?>% dari rencana</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">SISA ANGGARAN</div>
                        <div class="metric-value" style="color:<?= $sisa < 0 ? '#b91c1c' : '#15803d' ?>"><?= $sisa < 0 ? '−' : '' ?><?= EO::e(EO::rp(abs($sisa))) ?></div>
                        <div class="metric-note"><?= $melebihi ? count($melebihi) . ' pos melebihi rencana' : 'Semua pos sesuai rencana' ?></div>
                    </div>
                </div>

                <?php foreach ($melebihi as $m): ?>
                    <div class="alert alert-warning" style="margin-bottom:12px"><?= EO::e($m['kategori']) ?> melebihi rencana <?= EO::e(EO::rp((float) $m['realisasi'] - (float) $m['rencana'])) ?>.</div>
                <?php endforeach; ?>

                <div class="tbl-card" style="padding:8px 16px;margin-bottom:24px">
                    <div class="tbl-head">
                        <div class="tbl-cell" style="flex:2.2">KATEGORI</div>
                        <div class="tbl-cell" style="flex:1.2"><span class="badge badge-purple"><span class="badge-dot"></span>Rencana</span></div>
                        <div class="tbl-cell" style="flex:2"><span class="badge" style="background:#0e1128;color:#fff"><span class="badge-dot"></span>Realisasi</span></div>
                        <div class="tbl-cell" style="flex:1.2;text-align:right">SELISIH</div>
                    </div>
                    <?php foreach ($items as $it):
                        $rencana = (float) $it['rencana'];
                        $real = (float) $it['realisasi'];
                        $selisih = $rencana - $real;
                        $bar = $rencana > 0 ? min(100, (int) round($real / $rencana * 100)) : ($real > 0 ? 100 : 0);
                        ?>
                        <div class="tbl-row">
                            <div class="tbl-cell" style="flex:2.2"><b><?= EO::e($it['kategori']) ?></b></div>
                            <div class="tbl-cell" style="flex:1.2"><?= EO::e(EO::rp($rencana)) ?></div>
                            <div class="tbl-cell" style="flex:2">
                                <?= EO::e(EO::rp($real)) ?>
                                <div class="progress" style="margin-top:6px"><div class="progress-fill <?= $selisih < 0 ? 'progress-over' : '' ?>" style="width:<?= $bar ?>%"></div></div>
                            </div>
                            <div class="tbl-cell" style="flex:1.2;text-align:right;font-weight:700;color:<?= $selisih < 0 ? '#b91c1c' : '#15803d' ?>"><?= EO::e($selisihText($selisih)) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$items): ?><div class="empty-note">Belum ada pos anggaran.</div><?php endif; ?>
                    <?php if ($items): ?>
                        <div class="tbl-row" style="border-bottom:0">
                            <div class="tbl-cell" style="flex:2.2"><b>Total</b></div>
                            <div class="tbl-cell" style="flex:1.2"><b><?= EO::e(EO::rp($totalRencana)) ?></b></div>
                            <div class="tbl-cell" style="flex:2"><b><?= EO::e(EO::rp($totalRealisasi)) ?></b></div>
                            <div class="tbl-cell" style="flex:1.2;text-align:right;font-weight:700;color:<?= $sisa < 0 ? '#b91c1c' : '#15803d' ?>"><?= EO::e($selisihText($sisa)) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="help" style="margin:-8px 0 24px">Selisih = rencana − realisasi. Hijau: di bawah rencana. Merah: melebihi rencana.</p>

                <div class="card" id="form-rab" style="width:560px;box-sizing:border-box">
                    <h2 class="card-title">Tambah pengeluaran</h2>
                    <form id="form-rab-el" class="stack gap-16" novalidate>
                        <div>
                            <label class="field-label" for="f-kategori">Kategori</label>
                            <input class="input" id="f-kategori" type="text" maxlength="100" placeholder="Contoh: Konsumsi" style="width:100%">
                            <div class="help help-error" id="e-kategori" style="display:none"></div>
                        </div>
                        <div class="two-fields">
                            <div>
                                <label class="field-label" for="f-rencana">Rencana (Rp)</label>
                                <input class="input" id="f-rencana" type="number" min="0" value="0" style="width:100%">
                            </div>
                            <div>
                                <label class="field-label" for="f-real">Realisasi (Rp)</label>
                                <input class="input" id="f-real" type="number" min="0" value="0" style="width:100%">
                            </div>
                        </div>
                        <div class="help help-error" id="e-angka" style="display:none;margin-top:-8px"></div>
                        <div class="help help-error" id="e-form" style="display:none"></div>
                        <div><button type="submit" id="btn-simpan" class="button primary-button" style="cursor:pointer">Simpan pengeluaran</button></div>
                    </form>
                </div>
<?php
EO::close('<script>
(function () {
    const eventId = ' . json_encode($id) . ';
    const g = (i) => document.getElementById(i);
    g("form-rab-el").addEventListener("submit", async (ev) => {
        ev.preventDefault();
        ["kategori", "angka", "form"].forEach((k) => eoShowError(g("e-" + k), ""));
        g("f-kategori").classList.remove("input-error");
        const kategori = g("f-kategori").value.trim();
        const rencana = Number(g("f-rencana").value || 0), realisasi = Number(g("f-real").value || 0);
        let ok = true;
        if (!kategori) { eoShowError(g("e-kategori"), "Kategori wajib diisi."); g("f-kategori").classList.add("input-error"); ok = false; }
        if (rencana < 0 || realisasi < 0) { eoShowError(g("e-angka"), "Nominal tidak boleh negatif."); ok = false; }
        if (!ok) return;
        g("btn-simpan").disabled = true;
        const r = await eoApi("rab/" + eventId, "POST", { kategori: kategori, rencana: rencana, realisasi: realisasi });
        if (r.ok) { location.reload(); return; }
        g("btn-simpan").disabled = false;
        eoShowError(g("e-form"), r.message);
    });
})();
</script>');
