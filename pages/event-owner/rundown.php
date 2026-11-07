<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EO::loadEvent($db, $owner);
$id = $event['id'];

$stmt = $db->prepare("
    SELECT r.*, c.nama_panitia AS pic_nama
    FROM rundowns r LEFT JOIN committees c ON c.id = r.committee_id
    WHERE r.event_id = :id
    ORDER BY r.waktu_mulai ASC, r.created_at ASC");
$stmt->execute(['id' => $id]);
$rundowns = $stmt->fetchAll();

$stmt = $db->prepare('SELECT id, nama_panitia, posisi_jabatan FROM committees WHERE event_id = :id ORDER BY created_at ASC');
$stmt->execute(['id' => $id]);
$committees = $stmt->fetchAll();

$actions = '<a href="#form-kegiatan" class="button primary-button" style="text-decoration:none;cursor:pointer" onclick="document.getElementById(\'f-judul\').focus()">+ Tambah kegiatan</a>';
$subtitle = '<span class="slate" style="font-size:16px">Kegiatan diurutkan otomatis berdasarkan jam mulai · ' . count($rundowns) . ' kegiatan</span>';

EO::open($db, $owner, $event, 'rundown', $subtitle, $actions, 'Rundown acara', 'Rundown');

$jsRundown = array_map(static fn($r) => [
    'judul' => $r['judul_sesi'], 'mulai' => $r['waktu_mulai'], 'selesai' => $r['waktu_selesai'],
], $rundowns);
?>
                <div class="cols">
                    <div class="stack gap-12" style="flex:1;min-width:0">
                        <?php foreach ($rundowns as $r):
                            $sub = [];
                            if (trim((string) $r['pembicara']) !== '') {
                                $sub[] = $r['pembicara'];
                            }
                            if (trim((string) $r['keterangan']) !== '') {
                                $sub[] = $r['keterangan'];
                            }
                            if (!empty($r['pic_nama'])) {
                                $sub[] = 'PIC: ' . $r['pic_nama'];
                            }
                            ?>
                            <div class="card" style="padding:16px 20px;display:flex;gap:20px;align-items:center">
                                <div style="width:72px;flex:none;font-size:15px;font-weight:700;color:#3c29b8"><?= EO::e($r['waktu_mulai']) ?><div class="slate" style="font-size:12px;font-weight:400">s.d. <?= EO::e($r['waktu_selesai']) ?></div></div>
                                <div style="min-width:0">
                                    <b style="font-size:15px"><?= EO::e($r['judul_sesi']) ?></b>
                                    <?php if ($sub): ?><div class="slate" style="font-size:14px;margin-top:2px"><?= EO::e(implode(' · ', $sub)) ?></div><?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$rundowns): ?>
                            <div class="card empty-note">Belum ada kegiatan. Tambahkan lewat formulir di kanan.</div>
                        <?php endif; ?>
                    </div>

                    <div class="card" id="form-kegiatan" style="width:424px;flex:none;box-sizing:border-box">
                        <h2 class="card-title">Kegiatan baru</h2>
                        <form id="form-rundown" class="stack gap-16" novalidate>
                            <div class="two-fields">
                                <div>
                                    <label class="field-label" for="f-mulai">Mulai</label>
                                    <input class="input" id="f-mulai" type="text" placeholder="08.00" maxlength="5" style="width:100%">
                                </div>
                                <div>
                                    <label class="field-label" for="f-selesai">Selesai</label>
                                    <input class="input" id="f-selesai" type="text" placeholder="09.00" maxlength="5" style="width:100%">
                                </div>
                            </div>
                            <div class="help help-error" id="e-waktu" style="display:none;margin-top:-8px"></div>
                            <div>
                                <label class="field-label" for="f-judul">Kegiatan</label>
                                <input class="input" id="f-judul" type="text" maxlength="255" placeholder="Nama kegiatan" style="width:100%">
                                <div class="help help-error" id="e-judul" style="display:none"></div>
                            </div>
                            <div>
                                <label class="field-label" for="f-pic">Penanggung jawab</label>
                                <select class="input" id="f-pic" style="width:100%">
                                    <option value="">Pilih anggota panitia</option>
                                    <?php foreach ($committees as $c): ?>
                                        <option value="<?= EO::e($c['id']) ?>"><?= EO::e($c['nama_panitia']) ?> — <?= EO::e($c['posisi_jabatan']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!$committees): ?><div class="help">Belum ada panitia. <a href="<?= EO::url('panitia', $id) ?>" style="color:#4b34db">Tambah panitia</a> lebih dulu untuk memilih PIC.</div><?php endif; ?>
                            </div>
                            <div class="help help-error" id="e-form" style="display:none"></div>
                            <button type="submit" id="btn-simpan" class="button primary-button full-width" style="cursor:pointer">Simpan kegiatan</button>
                        </form>
                    </div>
                </div>
<?php
EO::close('<script>
(function () {
    const eventId = ' . json_encode($id) . ';
    const existing = ' . json_encode($jsRundown, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';
    const g = (i) => document.getElementById(i);
    // "8.5" / "08:30" / "0830" -> "08.30"; null bila tidak valid.
    function norm(v) {
        const m = String(v).trim().match(/^(\d{1,2})[.:]?(\d{2})$/);
        if (!m) return null;
        const h = +m[1], mi = +m[2];
        return h > 23 || mi > 59 ? null : String(h).padStart(2, "0") + "." + m[2];
    }
    const mins = (t) => +t.slice(0, 2) * 60 + +t.slice(3);
    g("form-rundown").addEventListener("submit", async (ev) => {
        ev.preventDefault();
        ["waktu", "judul", "form"].forEach((k) => eoShowError(g("e-" + k), ""));
        ["f-mulai", "f-selesai", "f-judul"].forEach((i) => g(i).classList.remove("input-error"));
        const mulai = norm(g("f-mulai").value), selesai = norm(g("f-selesai").value);
        const judul = g("f-judul").value.trim();
        let ok = true;
        if (!mulai || !selesai) {
            eoShowError(g("e-waktu"), "Isi jam dengan format JJ.MM, mis. 08.30.");
            if (!mulai) g("f-mulai").classList.add("input-error");
            if (!selesai) g("f-selesai").classList.add("input-error");
            ok = false;
        } else if (mins(selesai) <= mins(mulai)) {
            eoShowError(g("e-waktu"), "Jam selesai harus setelah jam mulai.");
            g("f-selesai").classList.add("input-error"); ok = false;
        } else {
            const bentrok = existing.find((r) => norm(r.mulai) && norm(r.selesai) && mins(mulai) < mins(norm(r.selesai)) && mins(selesai) > mins(norm(r.mulai)));
            if (bentrok) {
                eoShowError(g("e-waktu"), "Bentrok dengan " + bentrok.judul + " (" + bentrok.mulai + "–" + bentrok.selesai + ").");
                g("f-mulai").classList.add("input-error"); ok = false;
            }
        }
        if (!judul) { eoShowError(g("e-judul"), "Nama kegiatan wajib diisi."); g("f-judul").classList.add("input-error"); ok = false; }
        if (!ok) return;
        g("btn-simpan").disabled = true;
        const r = await eoApi("rundown/" + eventId, "POST", { waktu_mulai: mulai, waktu_selesai: selesai, judul_sesi: judul, committee_id: g("f-pic").value || null });
        if (r.ok) { location.reload(); return; }
        g("btn-simpan").disabled = false;
        eoShowError(g("e-form"), r.message);
    });
})();
</script>');
