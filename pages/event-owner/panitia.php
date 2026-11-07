<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EO::loadEvent($db, $owner);
$id = $event['id'];

$stmt = $db->prepare('SELECT nama_panitia, posisi_jabatan, divisi, kontak, surel FROM committees WHERE event_id = :id ORDER BY created_at ASC');
$stmt->execute(['id' => $id]);
$members = $stmt->fetchAll();

$subtitle = '<span class="slate" style="font-size:16px">' . count($members) . ' anggota</span>';
EO::open($db, $owner, $event, 'panitia', $subtitle, '', 'Kepanitiaan', 'Panitia');
?>
                <div class="cols">
                    <div class="tbl-card" style="flex:1;min-width:0;padding:8px 16px 16px">
                        <div class="tbl-head">
                            <div class="tbl-cell" style="flex:1.4">NAMA</div>
                            <div class="tbl-cell" style="flex:1.6">PERAN</div>
                            <div class="tbl-cell" style="flex:1">DIVISI</div>
                            <div class="tbl-cell" style="flex:1.6">KONTAK</div>
                        </div>
                        <?php foreach ($members as $m): ?>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="flex:1.4"><b><?= EO::e($m['nama_panitia']) ?></b></div>
                                <div class="tbl-cell" style="flex:1.6"><?= EO::e($m['posisi_jabatan']) ?></div>
                                <div class="tbl-cell" style="flex:1"><?= EO::e($m['divisi'] ?: '-') ?></div>
                                <div class="tbl-cell" style="flex:1.6;min-width:0;overflow-wrap:anywhere">
                                    <?= EO::e($m['kontak'] ?: '-') ?>
                                    <?php if (!empty($m['surel'])): ?><div class="cell-sub"><?= EO::e($m['surel']) ?></div><?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$members): ?><div class="empty-note">Belum ada anggota panitia.</div><?php endif; ?>
                    </div>

                    <div class="card" style="width:424px;flex:none;box-sizing:border-box">
                        <h2 class="card-title">Tambah anggota</h2>
                        <form id="form-panitia" class="stack gap-16" novalidate>
                            <div>
                                <label class="field-label" for="f-nama">Nama lengkap</label>
                                <input class="input" id="f-nama" type="text" maxlength="255" style="width:100%">
                                <div class="help help-error" id="e-nama" style="display:none"></div>
                            </div>
                            <div>
                                <label class="field-label" for="f-peran">Peran</label>
                                <input class="input" id="f-peran" type="text" maxlength="100" placeholder="Contoh: Koordinator Dokumentasi" style="width:100%">
                                <div class="help help-error" id="e-peran" style="display:none"></div>
                            </div>
                            <div>
                                <label class="field-label" for="f-divisi">Divisi</label>
                                <input class="input" id="f-divisi" type="text" maxlength="100" placeholder="Contoh: Humas" style="width:100%">
                            </div>
                            <div>
                                <label class="field-label" for="f-wa">Nomor WhatsApp</label>
                                <input class="input" id="f-wa" type="tel" maxlength="20" placeholder="0812-3456-7890" style="width:100%">
                                <div class="help help-error" id="e-wa" style="display:none"></div>
                            </div>
                            <div>
                                <label class="field-label" for="f-surel">Surel</label>
                                <input class="input" id="f-surel" type="email" maxlength="255" style="width:100%">
                                <div class="help help-error" id="e-surel" style="display:none"></div>
                            </div>
                            <div class="help help-error" id="e-form" style="display:none"></div>
                            <button type="submit" id="btn-simpan" class="button primary-button full-width" style="cursor:pointer">Simpan anggota</button>
                        </form>
                    </div>
                </div>
<?php
EO::close('<script>
(function () {
    const eventId = ' . json_encode($id) . ';
    const g = (i) => document.getElementById(i);
    g("form-panitia").addEventListener("submit", async (ev) => {
        ev.preventDefault();
        ["nama", "peran", "wa", "surel", "form"].forEach((k) => { eoShowError(g("e-" + k), ""); if (g("f-" + k)) g("f-" + k).classList.remove("input-error"); });
        const v = (k) => g("f-" + k).value.trim();
        let ok = true;
        const fail = (k, msg) => { eoShowError(g("e-" + k), msg); g("f-" + k).classList.add("input-error"); ok = false; };
        if (!v("nama")) fail("nama", "Nama lengkap wajib diisi.");
        if (!v("peran")) fail("peran", "Peran wajib diisi.");
        const digits = v("wa").replace(/\D/g, "");
        if (v("wa") && (digits.length < 10 || digits.length > 14)) fail("wa", "Nomor WhatsApp tidak lengkap.");
        if (v("surel") && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v("surel"))) fail("surel", "Format surel tidak valid.");
        if (!ok) return;
        g("btn-simpan").disabled = true;
        const r = await eoApi("committee/" + eventId, "POST", { nama_panitia: v("nama"), posisi_jabatan: v("peran"), divisi: v("divisi") || "Inti", kontak: v("wa"), surel: v("surel") });
        if (r.ok) { location.reload(); return; }
        g("btn-simpan").disabled = false;
        eoShowError(g("e-form"), r.message);
    });
})();
</script>');
