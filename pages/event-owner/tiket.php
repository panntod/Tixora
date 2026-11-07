<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EO::loadEvent($db, $owner);
$id = $event['id'];

$stats = EO::stats($db, $event);
$categories = EO::categories($db, $id);
$isPublished = in_array($event['status_verifikasi'], ['TERBIT', 'PUBLISHED'], true);
$dialokasikan = array_sum(array_map(static fn($c) => (int) $c['kuota'], $categories));

$actions = '<a href="#form-kategori" class="button primary-button" style="text-decoration:none;cursor:pointer" onclick="document.getElementById(\'f-nama\').focus()">+ Tambah kategori</a>';
$subtitle = '<span class="slate" style="font-size:16px">Kapasitas total ' . $stats['kuota'] . ' kursi · ' . $stats['pendaftar'] . ' terjual</span>';

EO::open($db, $owner, $event, 'tiket', $subtitle, $actions, 'Tiket & kapasitas', 'Tiket');
?>
                <div class="cols">
                    <div class="tbl-card" style="flex:1;min-width:0;padding:8px 16px 16px">
                        <div class="tbl-head">
                            <div class="tbl-cell" style="flex:1.3">KATEGORI</div>
                            <div class="tbl-cell" style="flex:1.2">HARGA</div>
                            <div class="tbl-cell" style="flex:.8">KUOTA</div>
                            <div class="tbl-cell" style="flex:.9">TERJUAL</div>
                            <div class="tbl-cell" style="flex:.7">SISA</div>
                            <div class="tbl-cell" style="flex:1.1">KTM</div>
                            <div class="tbl-cell" style="flex:1">STATUS</div>
                        </div>
                        <?php foreach ($categories as $c):
                            $terjual = (int) $c['terjual'];
                            $kuota = (int) $c['kuota'];
                            $sisa = max(0, $kuota - $terjual);
                            if ($sisa === 0) {
                                $badge = ['badge-neutral', 'Habis'];
                            } elseif ($isPublished) {
                                $badge = ['badge-success', 'Dijual'];
                            } else {
                                $badge = ['badge-neutral', 'Belum dijual'];
                            }
                            ?>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="flex:1.3"><b><?= EO::e($c['nama_kategori']) ?></b></div>
                                <div class="tbl-cell" style="flex:1.2"><?= (float) $c['harga'] > 0 ? EO::e(EO::rp($c['harga'])) : 'Gratis' ?></div>
                                <div class="tbl-cell" style="flex:.8"><?= $kuota ?></div>
                                <div class="tbl-cell" style="flex:.9"><?= $terjual ?></div>
                                <div class="tbl-cell" style="flex:.7"><?= $sisa ?></div>
                                <div class="tbl-cell" style="flex:1.1"><?= $c['wajib_ktm'] ? 'Ya' : 'Tidak' ?></div>
                                <div class="tbl-cell" style="flex:1"><?= EO::badge($badge) ?></div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$categories): ?>
                            <div class="empty-note">Belum ada kategori tiket. Tambahkan lewat formulir di kanan.</div>
                        <?php endif; ?>
                        <?php if ($categories && $dialokasikan > $stats['kuota']): ?>
                            <p class="help help-error" style="padding:12px 12px 0">Total kuota kategori (<?= $dialokasikan ?>) melebihi kapasitas acara (<?= $stats['kuota'] ?>).</p>
                        <?php endif; ?>
                    </div>

                    <div class="card" id="form-kategori" style="width:424px;flex:none;box-sizing:border-box">
                        <h2 class="card-title">Tambah kategori</h2>
                        <form id="form-tiket" class="stack gap-16" novalidate>
                            <div>
                                <label class="field-label" for="f-nama">Nama kategori</label>
                                <input class="input" id="f-nama" name="nama_kategori" type="text" maxlength="100" placeholder="Contoh: Umum" style="width:100%">
                                <div class="help help-error" id="e-nama" style="display:none"></div>
                            </div>
                            <div>
                                <label class="field-label" for="f-harga">Harga (Rp)</label>
                                <input class="input" id="f-harga" name="harga" type="number" min="0" value="0" style="width:100%">
                                <div class="help">Isi 0 untuk tiket gratis.</div>
                                <div class="help help-error" id="e-harga" style="display:none"></div>
                            </div>
                            <div>
                                <label class="field-label" for="f-kuota">Kuota</label>
                                <input class="input" id="f-kuota" name="kuota" type="number" min="1" style="width:100%">
                                <div class="help help-error" id="e-kuota" style="display:none"></div>
                            </div>
                            <label class="check-row" style="cursor:pointer">
                                <input type="checkbox" id="f-ktm" style="width:18px;height:18px;accent-color:#4b34db">
                                <span>Wajib melampirkan KTM</span>
                            </label>
                            <div class="help help-error" id="e-form" style="display:none"></div>
                            <div style="display:flex;gap:12px;align-items:center">
                                <button type="submit" id="btn-simpan" class="button primary-button" style="cursor:pointer">Simpan</button>
                                <button type="reset" class="button ghost-button" style="cursor:pointer">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
<?php
$sisaKapasitas = max(0, $stats['kuota'] - $dialokasikan);
EO::close('<script>
(function () {
    const eventId = ' . json_encode($id) . ';
    const sisaKapasitas = ' . $sisaKapasitas . ';
    const f = document.getElementById("form-tiket");
    const g = (i) => document.getElementById(i);
    f.addEventListener("submit", async (ev) => {
        ev.preventDefault();
        ["nama", "harga", "kuota", "form"].forEach((k) => eoShowError(g("e-" + k), ""));
        ["f-nama", "f-harga", "f-kuota"].forEach((i) => g(i).classList.remove("input-error"));
        const nama = g("f-nama").value.trim();
        const harga = Number(g("f-harga").value || 0);
        const kuota = parseInt(g("f-kuota").value, 10);
        let ok = true;
        const fail = (key, msg) => { eoShowError(g("e-" + key), msg); g("f-" + key).classList.add("input-error"); ok = false; };
        if (!nama) fail("nama", "Nama kategori wajib diisi.");
        if (!(harga >= 0)) fail("harga", "Harga tidak boleh negatif.");
        if (!(kuota > 0)) fail("kuota", "Kuota harus lebih dari 0.");
        else if (kuota > sisaKapasitas) fail("kuota", "Kuota melebihi sisa kapasitas acara (" + sisaKapasitas + ").");
        if (!ok) return;
        g("btn-simpan").disabled = true;
        const r = await eoApi("ticket-category/" + eventId, "POST", { nama_kategori: nama, harga: harga, kuota: kuota, wajib_ktm: g("f-ktm").checked });
        if (r.ok) { location.reload(); return; }
        g("btn-simpan").disabled = false;
        eoShowError(g("e-form"), r.message);
    });
})();
</script>');
