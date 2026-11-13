<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../../api/helpers/ViewHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/EventHelper.php' : __DIR__ . '/../../../api/helpers/EventHelper.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EventHelper::requireDraft($db, $owner);
$eventId = (string) $event['id'];
$self = EventHelper::stepUrl(3, $eventId);

$uuid = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

/** Rundown acara, urut jam mulai (format "HH.MM" berurutan secara leksikografis). */
$loadRows = static function () use ($db, $eventId): array {
    $st = $db->prepare("
        SELECT r.*, c.nama_panitia AS pic_nama
        FROM rundowns r LEFT JOIN committees c ON c.id = r.committee_id
        WHERE r.event_id = :id
        ORDER BY r.waktu_mulai ASC, r.created_at ASC");
    $st->execute(['id' => $eventId]);
    return $st->fetchAll();
};

$st = $db->prepare("SELECT id, nama_panitia, posisi_jabatan FROM committees WHERE event_id = :id ORDER BY created_at ASC");
$st->execute(['id' => $eventId]);
$committees = $st->fetchAll();
$committeeIds = array_column($committees, 'id');

$topError = '';
$errors = [];
$form = ['id' => '', 'mulai' => '', 'selesai' => '', 'judul' => '', 'keterangan' => '', 'pic' => ''];
$rows = $loadRows();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string) ($_POST['aksi'] ?? '');
    $rid = (string) ($_POST['rundown_id'] ?? '');

    if (!ViewHelper::checkCsrf()) {
        $topError = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($aksi === 'hapus' || $aksi === 'naik' || $aksi === 'turun') {
        if (preg_match($uuid, $rid)) {
            if ($aksi === 'hapus') {
                $db->prepare("DELETE FROM rundowns WHERE id = :id AND event_id = :e")->execute(['id' => $rid, 'e' => $eventId]);
            } else {
                // Tukar slot jam dengan kegiatan di atas/bawahnya; urutan tetap kronologis dan tidak pernah bentrok.
                $pos = array_search($rid, array_column($rows, 'id'), true);
                $other = $pos === false ? null : ($rows[$aksi === 'naik' ? $pos - 1 : $pos + 1] ?? null);
                if ($pos !== false && $other && ($aksi === 'turun' || $pos > 0)) {
                    $a = $rows[$pos];
                    try {
                        $db->beginTransaction();
                        $upd = $db->prepare("UPDATE rundowns SET waktu_mulai = :m, waktu_selesai = :s WHERE id = :id AND event_id = :e");
                        $upd->execute(['m' => $other['waktu_mulai'], 's' => $other['waktu_selesai'], 'id' => $a['id'], 'e' => $eventId]);
                        $upd->execute(['m' => $a['waktu_mulai'], 's' => $a['waktu_selesai'], 'id' => $other['id'], 'e' => $eventId]);
                        $db->commit();
                    } catch (PDOException $e) {
                        if ($db->inTransaction()) {
                            $db->rollBack();
                        }
                        error_log('[TIXORA] rundown wizard swap: ' . $e->getMessage());
                    }
                }
            }
            $db->prepare("UPDATE events SET updated_at = CURRENT_TIMESTAMP WHERE id = :id")->execute(['id' => $eventId]);
        }
        EventHelper::redirect($self);
    } elseif ($aksi === 'simpan') {
        $form = [
            'id' => preg_match($uuid, $rid) ? $rid : '',
            'mulai' => trim((string) ($_POST['mulai'] ?? '')),
            'selesai' => trim((string) ($_POST['selesai'] ?? '')),
            'judul' => trim((string) ($_POST['judul'] ?? '')),
            'keterangan' => trim((string) ($_POST['keterangan'] ?? '')),
            'pic' => trim((string) ($_POST['pic'] ?? '')),
        ];
        $mulai = EventHelper::normalizeTime($form['mulai']);
        $selesai = EventHelper::normalizeTime($form['selesai']);

        if ($mulai === null) {
            $errors['mulai'] = 'Isi jam dengan format JJ.MM, mis. 08.30.';
        }
        if ($selesai === null) {
            $errors['selesai'] = 'Isi jam dengan format JJ.MM, mis. 09.30.';
        }
        if ($mulai !== null && $selesai !== null) {
            $m = EventHelper::timeToMinutes($mulai);
            $s = EventHelper::timeToMinutes($selesai);
            if ($s <= $m) {
                $errors['selesai'] = 'Jam selesai harus setelah jam mulai.';
            } else {
                foreach ($rows as $r) {
                    if ($r['id'] === $form['id']) {
                        continue;
                    }
                    $rm = EventHelper::timeToMinutes((string) $r['waktu_mulai']);
                    $rs = EventHelper::timeToMinutes((string) $r['waktu_selesai']);
                    if ($m < $rs && $s > $rm) {
                        $errors['mulai'] = 'Bentrok dengan ' . $r['judul_sesi'] . ' (' . $r['waktu_mulai'] . '–' . $r['waktu_selesai'] . ').';
                        break;
                    }
                }
            }
        }
        if ($form['judul'] === '') {
            $errors['judul'] = 'Nama kegiatan wajib diisi.';
        } elseif (mb_strlen($form['judul']) > 255) {
            $errors['judul'] = 'Nama kegiatan maksimal 255 karakter.';
        }
        if (mb_strlen($form['keterangan']) > 1000) {
            $errors['keterangan'] = 'Deskripsi maksimal 1000 karakter.';
        }
        if ($form['pic'] !== '' && !in_array($form['pic'], $committeeIds, true)) {
            $errors['pic'] = 'Penanggung jawab tidak valid untuk acara ini.';
        }

        if (!$errors) {
            try {
                $p = [
                    'm' => $mulai, 's' => $selesai, 'judul' => $form['judul'],
                    'ket' => $form['keterangan'], 'pic' => $form['pic'] !== '' ? $form['pic'] : null, 'e' => $eventId,
                ];
                if ($form['id'] !== '') {
                    $p['id'] = $form['id'];
                    $db->prepare("UPDATE rundowns SET waktu_mulai = :m, waktu_selesai = :s, judul_sesi = :judul, keterangan = :ket,
                                  committee_id = :pic WHERE id = :id AND event_id = :e")->execute($p);
                } else {
                    $db->prepare("INSERT INTO rundowns (event_id, committee_id, waktu_mulai, waktu_selesai, judul_sesi, pembicara, keterangan)
                                  VALUES (:e, :pic, :m, :s, :judul, '', :ket)")->execute($p);
                }
                $db->prepare("UPDATE events SET updated_at = CURRENT_TIMESTAMP WHERE id = :id")->execute(['id' => $eventId]);
                EventHelper::redirect($self);
            } catch (PDOException $e) {
                error_log('[TIXORA] rundown wizard simpan: ' . $e->getMessage());
                $topError = 'Kegiatan gagal disimpan karena kesalahan server. Silakan coba lagi.';
            }
        }
    }
} elseif (preg_match($uuid, (string) ($_GET['edit'] ?? ''))) {
    foreach ($rows as $r) {
        if ($r['id'] === $_GET['edit']) {
            $form = [
                'id' => $r['id'], 'mulai' => $r['waktu_mulai'], 'selesai' => $r['waktu_selesai'], 'judul' => $r['judul_sesi'],
                'keterangan' => (string) $r['keterangan'], 'pic' => (string) ($r['committee_id'] ?? ''),
            ];
        }
    }
}

// Kegiatan baru: jam mulai default = jam selesai kegiatan terakhir, durasi 1 jam.
if ($form['id'] === '' && $form['mulai'] === '' && $form['selesai'] === '') {
    $last = $rows ? end($rows) : null;
    $startMin = $last ? EventHelper::timeToMinutes((string) $last['waktu_selesai']) : 8 * 60;
    $startMin = min($startMin, 22 * 60 + 59);
    $form['mulai'] = sprintf('%02d.%02d', intdiv($startMin, 60), $startMin % 60);
    $endMin = min($startMin + 60, 23 * 60 + 59);
    $form['selesai'] = sprintf('%02d.%02d', intdiv($endMin, 60), $endMin % 60);
}

$h = [EventHelper::class, 'h'];
$cls = static fn(string $k): string => isset($errors[$k]) ? ' input-error' : '';
$msg = static function (string $k) use ($errors, $h): void {
    if (isset($errors[$k])) {
        echo '<p class="help help-error">' . $h($errors[$k]) . '</p>';
    }
};
$editing = $form['id'] !== '';
$last = count($rows) - 1;

EventHelper::renderTop($db, $owner, 3, $event);
?>
                <?php if ($topError): ?>
                    <div class="alert alert-error" style="width:1128px;margin-bottom:16px"><?= $h($topError) ?></div>
                <?php endif; ?>

                <div class="cols">
                    <div style="width:680px">
                        <?php foreach ($rows as $n => $r):
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
                            $aksiForm = static function (string $aksi, string $inner, string $extra = '') use ($r, $self): string {
                                return '<form method="POST" action="' . htmlspecialchars($self, ENT_QUOTES) . '" style="margin:0;display:inline-flex"' . $extra . '>'
                                    . ViewHelper::csrfField()
                                    . '<input type="hidden" name="aksi" value="' . $aksi . '">'
                                    . '<input type="hidden" name="rundown_id" value="' . htmlspecialchars((string) $r['id'], ENT_QUOTES) . '">'
                                    . $inner . '</form>';
                            };
                            $naik = '<svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 11V3M3.5 6.5L7 3l3.5 3.5" stroke="#4B5170" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round" /></svg>';
                            $turun = '<svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 3v8M3.5 7.5L7 11l3.5-3.5" stroke="#4B5170" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round" /></svg>';
                            ?>
                            <div class="run-row" style="margin-bottom:12px<?= $editing && $form['id'] === $r['id'] ? ';outline:2px solid #4B34DB' : '' ?>">
                                <span class="run-time" title="s.d. <?= $h($r['waktu_selesai']) ?>"><?= $h($r['waktu_mulai']) ?></span>
                                <div class="run-body">
                                    <b><?= $h($r['judul_sesi']) ?></b>
                                    <?php if ($sub): ?><p><?= $h(implode(' · ', $sub)) ?></p><?php endif; ?>
                                </div>
                                <?= $aksiForm('naik', '<button type="submit" class="button ghost-button small-button" style="padding:0 8px"'
                                    . ($n === 0 ? ' disabled' : '') . ' title="Tukar ke atas (jam mengikuti posisi)" aria-label="Naikkan">' . $naik . '</button>') ?>
                                <?= $aksiForm('turun', '<button type="submit" class="button ghost-button small-button" style="padding:0 8px"'
                                    . ($n === $last ? ' disabled' : '') . ' title="Tukar ke bawah (jam mengikuti posisi)" aria-label="Turunkan">' . $turun . '</button>') ?>
                                <a class="button ghost-button small-button" href="<?= $self ?>&amp;edit=<?= $h($r['id']) ?>">Edit</a>
                                <?= $aksiForm('hapus', '<button type="submit" class="button ghost-button small-button" style="color:#B91C1C">Hapus</button>',
                                    ' onsubmit="return confirm(\'Hapus kegiatan ini?\');"') ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$rows): ?>
                            <div class="card" style="text-align:center;color:#5F6483">Belum ada kegiatan. Tambahkan kegiatan pertama lewat formulir di samping.</div>
                        <?php endif; ?>
                    </div>

                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title"><?= $editing ? 'Edit kegiatan' : 'Kegiatan baru' ?></h2>
                        <form method="POST" action="<?= $self ?>" class="stack gap-16" novalidate>
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="aksi" value="simpan">
                            <input type="hidden" name="rundown_id" value="<?= $h($form['id']) ?>">
                            <div class="two-fields">
                                <div>
                                    <label class="field-label" for="f-mulai">Mulai</label>
                                    <input type="text" id="f-mulai" name="mulai" class="input<?= $cls('mulai') ?>" placeholder="08.00" maxlength="5" value="<?= $h($form['mulai']) ?>">
                                </div>
                                <div>
                                    <label class="field-label" for="f-selesai">Selesai</label>
                                    <input type="text" id="f-selesai" name="selesai" class="input<?= $cls('selesai') ?>" placeholder="09.00" maxlength="5" value="<?= $h($form['selesai']) ?>">
                                </div>
                            </div>
                            <?php $msg('mulai'); $msg('selesai'); ?>
                            <div>
                                <label class="field-label" for="f-judul">Kegiatan</label>
                                <input type="text" id="f-judul" name="judul" class="input<?= $cls('judul') ?>" placeholder="Nama kegiatan" maxlength="255" value="<?= $h($form['judul']) ?>">
                                <?php $msg('judul'); ?>
                            </div>
                            <div>
                                <label class="field-label" for="f-ket">Deskripsi</label>
                                <input type="text" id="f-ket" name="keterangan" class="input<?= $cls('keterangan') ?>" placeholder="Keterangan singkat" value="<?= $h($form['keterangan']) ?>">
                                <?php $msg('keterangan'); ?>
                            </div>
                            <div>
                                <label class="field-label" for="f-pic">Penanggung jawab</label>
                                <select id="f-pic" name="pic" class="input<?= $cls('pic') ?>">
                                    <option value="">Pilih anggota panitia</option>
                                    <?php foreach ($committees as $c): ?>
                                        <option value="<?= $h($c['id']) ?>" <?= $form['pic'] === $c['id'] ? 'selected' : '' ?>><?= $h($c['nama_panitia']) ?> — <?= $h($c['posisi_jabatan']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php $msg('pic'); ?>
                                <?php if (!$committees): ?>
                                    <p class="help">Belum ada panitia. <a href="<?= BASE_URL ?>/event-owner/panitia?id=<?= $h($eventId) ?>" target="_blank" rel="noopener" style="color:#4b34db">Tambah panitia</a> untuk memilih PIC (opsional).</p>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="button primary-button full-width"><?= $editing ? 'Simpan perubahan' : 'Simpan kegiatan' ?></button>
                            <?php if ($editing): ?>
                                <a href="<?= $self ?>" class="button secondary-button full-width" style="justify-content:center">Batal</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="wizard-footer" style="width:1128px;box-sizing:border-box;margin-top:24px">
                    <a href="<?= BASE_URL ?>/event-owner/acara-saya?draf=1" class="button ghost-button">Simpan draf</a>
                    <div class="row">
                        <a href="<?= EventHelper::stepUrl(2, $eventId) ?>" class="button secondary-button">Kembali</a>
                        <a href="<?= EventHelper::stepUrl(4, $eventId) ?>" class="button primary-button">Lanjut: Tinjau &amp; ajukan</a>
                    </div>
                </div>
<?php EventHelper::renderBottom(); ?>