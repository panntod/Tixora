<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/EventHelper.php' : __DIR__ . '/../../api/helpers/EventHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EO::loadEvent($db, $owner);
$id = $event['id'];

$stmt = $db->prepare('SELECT id, nama_panitia, posisi_jabatan, divisi, kontak, surel FROM committees WHERE event_id = :id ORDER BY created_at ASC');
$stmt->execute(['id' => $id]);
$members = $stmt->fetchAll();

// Datang dari langkah Rundown pada wizard buat acara: setelah menyimpan anggota, kembali ke sana.
$backUrl = '';
if (($_GET['dari'] ?? '') === 'rundown' && in_array($event['status_verifikasi'], ['DRAF', 'DITOLAK'], true)) {
    $backUrl = EventHelper::stepUrl(3, (string) $id);
}

$subtitle = '<span class="slate" style="font-size:16px">' . count($members) . ' anggota</span>';
EO::open($db, $owner, $event, 'panitia', $subtitle, '', 'Kepanitiaan', 'Panitia');
?>
                <div class="cols">
                    <div class="tbl-card" style="flex:1;min-width:0;padding:8px 16px 16px">
                        <div class="alert alert-error" id="e-list" style="display:none;margin:8px 0"></div>
                        <div class="tbl-head">
                            <div class="tbl-cell" style="flex:1.4">NAMA</div>
                            <div class="tbl-cell" style="flex:1.6">PERAN</div>
                            <div class="tbl-cell" style="flex:1">DIVISI</div>
                            <div class="tbl-cell" style="flex:1.6">KONTAK</div>
                            <div class="tbl-cell" style="flex:none;width:112px"></div>
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
                                <div class="tbl-cell" style="flex:none;width:112px;display:flex;gap:4px;justify-content:flex-end">
                                    <button type="button" class="button ghost-button small-button js-edit" style="cursor:pointer"
                                        data-id="<?= EO::e($m['id']) ?>" data-nama="<?= EO::e($m['nama_panitia']) ?>" data-peran="<?= EO::e($m['posisi_jabatan']) ?>"
                                        data-divisi="<?= EO::e($m['divisi']) ?>" data-wa="<?= EO::e($m['kontak']) ?>" data-surel="<?= EO::e($m['surel']) ?>">Edit</button>
                                    <button type="button" class="button ghost-button small-button js-hapus" style="cursor:pointer;color:#B91C1C"
                                        data-id="<?= EO::e($m['id']) ?>" data-nama="<?= EO::e($m['nama_panitia']) ?>">Hapus</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$members): ?><div class="empty-note">Belum ada anggota panitia.</div><?php endif; ?>
                    </div>

                    <div class="card" style="width:424px;flex:none;box-sizing:border-box">
                        <h2 class="card-title" id="form-title">Tambah anggota</h2>
                        <?php if ($backUrl): ?>
                            <p class="help" style="margin:-8px 0 16px">Setelah disimpan, Anda otomatis kembali ke langkah Rundown. <a href="<?= EO::e($backUrl) ?>" style="color:#4b34db">Kembali tanpa menambah</a></p>
                        <?php endif; ?>
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
                            <button type="button" id="btn-batal-edit" class="button secondary-button full-width" style="cursor:pointer;display:none">Batal edit</button>
                        </form>
                    </div>
                </div>
<?php
EO::close('<script>
(function () {
    const eventId = ' . json_encode($id) . ';
    const backUrl = ' . json_encode($backUrl) . ';
    const g = (i) => document.getElementById(i);
    const keys = ["nama", "peran", "divisi", "wa", "surel"];
    let editId = null;
    function clearErrors() {
        ["nama", "peran", "wa", "surel", "form"].forEach((k) => { eoShowError(g("e-" + k), ""); if (g("f-" + k)) g("f-" + k).classList.remove("input-error"); });
    }
    function resetForm() {
        editId = null;
        keys.forEach((k) => { g("f-" + k).value = ""; });
        g("form-title").textContent = "Tambah anggota";
        g("btn-simpan").textContent = "Simpan anggota";
        g("btn-batal-edit").style.display = "none";
        clearErrors();
    }
    document.querySelectorAll(".js-edit").forEach((b) => b.addEventListener("click", () => {
        editId = b.dataset.id;
        g("f-nama").value = b.dataset.nama;
        g("f-peran").value = b.dataset.peran;
        g("f-divisi").value = b.dataset.divisi;
        g("f-wa").value = b.dataset.wa;
        g("f-surel").value = b.dataset.surel;
        g("form-title").textContent = "Edit anggota";
        g("btn-simpan").textContent = "Simpan perubahan";
        g("btn-batal-edit").style.display = "block";
        clearErrors();
        g("form-panitia").scrollIntoView({ behavior: "smooth", block: "center" });
        g("f-nama").focus();
    }));
    g("btn-batal-edit").addEventListener("click", resetForm);
    document.querySelectorAll(".js-hapus").forEach((b) => b.addEventListener("click", async () => {
        eoShowError(g("e-list"), "");
        const ok = await eoConfirm({
            title: "Hapus anggota " + b.dataset.nama + "?",
            text: "Anggota dihapus dari kepanitiaan acara ini. Kegiatan rundown yang ia tangani perlu diberi PIC baru.",
            confirmLabel: "Hapus anggota",
            tone: "danger"
        });
        if (!ok) return;
        b.disabled = true;
        const r = await eoApi("committee-delete/" + b.dataset.id, "DELETE");
        if (r.ok) { location.reload(); return; }
        b.disabled = false;
        eoShowError(g("e-list"), r.message);
    }));
    g("form-panitia").addEventListener("submit", async (ev) => {
        ev.preventDefault();
        clearErrors();
        const v = (k) => g("f-" + k).value.trim();
        let ok = true;
        const fail = (k, msg) => { eoShowError(g("e-" + k), msg); g("f-" + k).classList.add("input-error"); ok = false; };
        if (!v("nama")) fail("nama", "Nama lengkap wajib diisi.");
        if (!v("peran")) fail("peran", "Peran wajib diisi.");
        const digits = v("wa").replace(/\\D/g, "");
        if (v("wa") && (digits.length < 10 || digits.length > 14)) fail("wa", "Nomor WhatsApp tidak lengkap.");
        if (v("surel") && !/^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$/.test(v("surel"))) fail("surel", "Format surel tidak valid.");
        if (!ok) return;
        g("btn-simpan").disabled = true;
        const payload = { nama_panitia: v("nama"), posisi_jabatan: v("peran"), divisi: v("divisi") || "Inti", kontak: v("wa"), surel: v("surel") };
        const r = editId
            ? await eoApi("committee-update/" + editId, "PATCH", payload)
            : await eoApi("committee/" + eventId, "POST", payload);
        if (r.ok) {
            if (!editId && backUrl) { location.href = backUrl; } else { location.reload(); }
            return;
        }
        g("btn-simpan").disabled = false;
        eoShowError(g("e-form"), r.message);
    });
})();
</script>');