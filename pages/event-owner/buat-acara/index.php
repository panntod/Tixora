<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../../api/helpers/ViewHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/EventHelper.php' : __DIR__ . '/../../../api/helpers/EventHelper.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);

// Langkah 1: tanpa ?id = acara baru; dengan ?id = lanjutkan draf (juga acara DITOLAK yang diperbaiki).
$event = (($_GET['id'] ?? $_POST['id'] ?? '') !== '') ? EventHelper::requireDraft($db, $owner) : null;

$fields = ['judul_event', 'kategori', 'deskripsi', 'tanggal_mulai', 'tanggal_selesai', 'nama_tempat', 'kota', 'nama_penyelenggara'];
$val = array_fill_keys($fields, '');
$val['nama_penyelenggara'] = (string) $owner['nama'];
$val['kategori'] = 'Seminar';
$val['kota'] = 'Malang';
if ($event) {
    foreach ($fields as $f) {
        $val[$f] = (string) ($event[$f] ?? '');
    }
    if ($val['nama_penyelenggara'] === '') {
        $val['nama_penyelenggara'] = (string) $owner['nama'];
    }
    // Draf yang hanya berisi nama acara menyimpan waktu sementara (mulai = selesai): tampilkan kosong.
    if ($event['tanggal_mulai'] === $event['tanggal_selesai']) {
        $val['tanggal_mulai'] = $val['tanggal_selesai'] = '';
    } else {
        $val['tanggal_mulai'] = EventHelper::toLocalInput($event['tanggal_mulai']);
        $val['tanggal_selesai'] = EventHelper::toLocalInput($event['tanggal_selesai']);
    }
}

$errors = [];
$topError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = ($_POST['aksi'] ?? '') === 'draf' ? 'draf' : 'lanjut';

    if (!ViewHelper::checkCsrf()) {
        $topError = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        foreach ($fields as $f) {
            $val[$f] = trim((string) ($_POST[$f] ?? ''));
        }
        $errors = EventHelper::validateInfo($val);
        if ($aksi === 'draf') {
            // Simpan draf boleh belum lengkap: hanya nama acara (dan kategori) yang wajib.
            $errors = array_intersect_key($errors, ['judul_event' => 1, 'kategori' => 1]);
        }
        $posterError = EventHelper::checkPoster($_FILES['poster'] ?? []);
        if ($posterError) {
            $errors['poster'] = $posterError;
        }

        if (!$errors) {
            [$posterPath, $uploadError] = EventHelper::storePoster($_FILES['poster'] ?? []);
            if ($uploadError) {
                $errors['poster'] = $uploadError;
            } else {
                $mulai = strtotime($val['tanggal_mulai']);
                $selesai = strtotime($val['tanggal_selesai']);
                // Hanya terjadi pada "Simpan draf" yang belum lengkap: isi waktu sementara agar kolom wajib terpenuhi.
                if ($mulai === false) {
                    $mulai = time();
                }
                if ($selesai === false || $selesai < $mulai) {
                    $selesai = $mulai;
                }
                $params = [
                    'judul' => $val['judul_event'],
                    'kategori' => $val['kategori'],
                    'deskripsi' => $val['deskripsi'],
                    'mulai' => date('Y-m-d H:i:s', $mulai),
                    'selesai' => date('Y-m-d H:i:s', $selesai),
                    'tempat' => $val['nama_tempat'],
                    'kota' => $val['kota'],
                    'penyelenggara' => $val['nama_penyelenggara'] !== '' ? mb_substr($val['nama_penyelenggara'], 0, 255) : null,
                ];
                try {
                    $db->beginTransaction();
                    if ($event) {
                        $sql = "UPDATE events SET judul_event = :judul, kategori = :kategori, deskripsi = :deskripsi,
                                    tanggal_mulai = :mulai, tanggal_selesai = :selesai, nama_tempat = :tempat, kota = :kota, nama_penyelenggara = :penyelenggara"
                            . ($posterPath ? ", banner_url = :banner" : "")
                            . ", updated_at = CURRENT_TIMESTAMP
                                WHERE id = :id AND owner_id = :owner";
                        $params['id'] = $event['id'];
                        $params['owner'] = $owner['id'];
                        if ($posterPath) {
                            $params['banner'] = $posterPath;
                        }
                        $db->prepare($sql)->execute($params);
                        $eventId = (string) $event['id'];
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO events (owner_id, judul_event, deskripsi, kategori, kuota, tanggal_mulai, tanggal_selesai,
                                                nama_tempat, kota, nama_penyelenggara, banner_url, status_verifikasi)
                            VALUES (:owner, :judul, :deskripsi, :kategori, 0, :mulai, :selesai, :tempat, :kota, :penyelenggara, :banner, 'DRAF')
                            RETURNING id");
                        $params['owner'] = $owner['id'];
                        $params['banner'] = $posterPath;
                        $stmt->execute($params);
                        $eventId = (string) $stmt->fetchColumn();
                        $db->prepare("INSERT INTO event_submission_logs (event_id, status, catatan) VALUES (:id, 'DRAF', 'Draf acara dibuat.')")
                            ->execute(['id' => $eventId]);
                    }
                    $db->commit();

                    if ($posterPath && $event) {
                        EventHelper::deletePoster($event['banner_url']);
                    }
                    EventHelper::redirect($aksi === 'draf'
                        ? BASE_URL . '/event-owner/acara-saya?draf=1'
                        : EventHelper::stepUrl(2, $eventId));
                } catch (PDOException $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log('[TIXORA] buat-acara langkah 1: ' . $e->getMessage());
                    $topError = 'Draf gagal disimpan karena kesalahan server. Silakan coba lagi.';
                }
            }
        }

        if (!$topError && $errors) {
            $topError = $aksi === 'draf'
                ? 'Isi nama acara terlebih dahulu untuk menyimpan draf.'
                : 'Lengkapi ' . count($errors) . ' kolom wajib sebelum melanjutkan.';
        }
    }
}

$h = [EventHelper::class, 'h'];
$err = static fn(string $k): string => $errors[$k] ?? '';
$cls = static fn(string $k): string => isset($errors[$k]) ? ' input-error' : '';
$poster = $event ? EventHelper::posterUrl($event['banner_url']) : null;

EventHelper::renderTop($db, $owner, 1, $event);
?>
                <?php if ($event && $event['status_verifikasi'] === 'DITOLAK' && trim((string) $event['alasan_penolakan']) !== ''): ?>
                    <div class="alert alert-error" style="width:880px;margin-bottom:16px">
                        Ditolak admin: <?= $h($event['alasan_penolakan']) ?> — perbaiki lalu ajukan kembali di langkah 4.
                    </div>
                <?php endif; ?>
                <?php if ($topError): ?>
                    <div class="alert alert-error" style="width:880px;margin-bottom:16px"><?= $h($topError) ?></div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" class="stack gap-24" style="width:880px" novalidate>
                    <?= ViewHelper::csrfField() ?>
                    <?php if ($event): ?><input type="hidden" name="id" value="<?= $h($event['id']) ?>"><?php endif; ?>
                    <button type="submit" name="aksi" value="lanjut" style="display:none" tabindex="-1" aria-hidden="true"></button>

                    <div class="card">
                        <h2 class="card-title">Informasi dasar</h2>
                        <div class="stack gap-16">
                            <div>
                                <label class="field-label" for="judul_event">Nama acara</label>
                                <input type="text" id="judul_event" name="judul_event" class="input<?= $cls('judul_event') ?>"
                                    maxlength="255" value="<?= $h($val['judul_event']) ?>">
                                <?php if ($err('judul_event')): ?><p class="help help-error"><?= $h($err('judul_event')) ?></p><?php endif; ?>
                            </div>
                            <div class="two-fields">
                                <div>
                                    <label class="field-label" for="kategori">Kategori</label>
                                    <select id="kategori" name="kategori" class="input<?= $cls('kategori') ?>">
                                        <?php foreach (EventHelper::KATEGORI as $k): ?>
                                            <option value="<?= $h($k) ?>" <?= $val['kategori'] === $k ? 'selected' : '' ?>><?= $h($k) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($err('kategori')): ?><p class="help help-error"><?= $h($err('kategori')) ?></p><?php endif; ?>
                                </div>
                                <div>
                                    <label class="field-label" for="nama_penyelenggara">Penyelenggara</label>
                                    <input type="text" id="nama_penyelenggara" name="nama_penyelenggara" class="input" maxlength="255" value="<?= $h($val['nama_penyelenggara']) ?>">
                                </div>
                            </div>
                            <div>
                                <label class="field-label" for="deskripsi">Deskripsi</label>
                                <textarea id="deskripsi" name="deskripsi" class="input input-area<?= $cls('deskripsi') ?>"><?= $h($val['deskripsi']) ?></textarea>
                                <?php if ($err('deskripsi')): ?><p class="help help-error"><?= $h($err('deskripsi')) ?></p><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h2 class="card-title">Poster</h2>
                        <?php if ($poster): ?>
                            <img src="<?= $h($poster) ?>" alt="Poster saat ini" style="display:block;width:100%;max-width:360px;aspect-ratio:16/9;object-fit:cover;border-radius:8px;margin-bottom:12px">
                        <?php endif; ?>
                        <ul class="poster-guide">
                            <li>Format <b>JPG, PNG, atau WebP</b>, ukuran maksimal <b>2 MB</b>.</li>
                            <li>Rasio <b>16:9</b>. Ukuran ideal <b>1280 &times; 720 px</b> (minimal 800 &times; 450 px agar tidak buram).</li>
                            <li>Letakkan teks dan logo penting di bagian tengah; tepi gambar bisa terpotong di kartu dan banner acara.</li>
                            <li>Tanpa unggahan, Tixora memakai banner bawaan.</li>
                        </ul>
                        <div class="dropzone<?= $cls('poster') ?>" style="<?= $err('poster') ? 'border-color:#B91C1C' : '' ?>">
                            <b id="poster-judul" style="display:block"><?= $poster ? 'Ganti poster acara' : 'Unggah poster acara' ?></b>
                            <span class="slate" id="poster-info" style="display:block;margin-bottom:10px">JPG, PNG, atau WebP &middot; 16:9 &middot; maks. 2 MB</span>
                            <input type="file" id="poster" name="poster" accept="image/jpeg,image/png,image/webp" style="display:none">
                            <label for="poster" class="button secondary-button small-button" style="cursor:pointer">Pilih berkas</label>
                        </div>
                        <p class="help help-error" id="poster-error" style="display:none" role="alert"></p>
                        <p class="help" id="poster-warn" style="display:none;color:#92400e"></p>
                        <img id="poster-preview" alt="Pratinjau poster baru" style="display:none;width:100%;max-width:360px;aspect-ratio:16/9;object-fit:cover;border-radius:8px;margin-top:12px">
                        <?php if ($err('poster')): ?><p class="help help-error"><?= $h($err('poster')) ?></p><?php endif; ?>
                    </div>

                    <div class="card">
                        <h2 class="card-title">Waktu &amp; lokasi</h2>
                        <div class="stack gap-16">
                            <div class="two-fields">
                                <div>
                                    <label class="field-label" for="tanggal_mulai">Mulai</label>
                                    <input type="datetime-local" id="tanggal_mulai" name="tanggal_mulai" class="input<?= $cls('tanggal_mulai') ?>" value="<?= $h($val['tanggal_mulai']) ?>">
                                    <?php if ($err('tanggal_mulai')): ?><p class="help help-error"><?= $h($err('tanggal_mulai')) ?></p><?php endif; ?>
                                </div>
                                <div>
                                    <label class="field-label" for="tanggal_selesai">Selesai</label>
                                    <input type="datetime-local" id="tanggal_selesai" name="tanggal_selesai" class="input<?= $cls('tanggal_selesai') ?>" value="<?= $h($val['tanggal_selesai']) ?>">
                                    <?php if ($err('tanggal_selesai')): ?><p class="help help-error"><?= $h($err('tanggal_selesai')) ?></p><?php endif; ?>
                                </div>
                            </div>
                            <div class="two-fields">
                                <div>
                                    <label class="field-label" for="nama_tempat">Nama tempat</label>
                                    <input type="text" id="nama_tempat" name="nama_tempat" class="input<?= $cls('nama_tempat') ?>" maxlength="255" value="<?= $h($val['nama_tempat']) ?>">
                                    <?php if ($err('nama_tempat')): ?><p class="help help-error"><?= $h($err('nama_tempat')) ?></p><?php endif; ?>
                                </div>
                                <div>
                                    <label class="field-label" for="kota">Kota</label>
                                    <input type="text" id="kota" name="kota" class="input<?= $cls('kota') ?>" maxlength="100" value="<?= $h($val['kota']) ?>">
                                    <?php if ($err('kota')): ?><p class="help help-error"><?= $h($err('kota')) ?></p><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-footer" style="width:880px;box-sizing:border-box">
                        <button type="submit" name="aksi" value="draf" class="button ghost-button">Simpan draf</button>
                        <div class="row">
                            <a href="<?= BASE_URL ?>/event-owner/acara-saya" class="button secondary-button">Batal</a>
                            <button type="submit" name="aksi" value="lanjut" class="button primary-button">Lanjut: Tiket &amp; kapasitas</button>
                        </div>
                    </div>
                </form>

                <script>
                    (function () {
                        var input = document.getElementById('poster');
                        var errEl = document.getElementById('poster-error');
                        var warnEl = document.getElementById('poster-warn');
                        var prev = document.getElementById('poster-preview');
                        function reset() {
                            errEl.style.display = warnEl.style.display = prev.style.display = 'none';
                            errEl.textContent = warnEl.textContent = '';
                        }
                        function reject(msg) {
                            input.value = '';
                            errEl.textContent = msg;
                            errEl.style.display = 'block';
                            prev.style.display = 'none';
                            document.getElementById('poster-judul').textContent = 'Pilih poster lain';
                        }
                        input.addEventListener('change', function () {
                            reset();
                            var f = input.files[0];
                            if (!f) return;
                            document.getElementById('poster-judul').textContent = f.name;
                            document.getElementById('poster-info').textContent = (f.size / 1048576).toFixed(2) + ' MB';
                            if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) === -1) { reject('Poster harus berupa gambar JPG, PNG, atau WebP.'); return; }
                            if (f.size > 2097152) { reject('Ukuran poster ' + (f.size / 1048576).toFixed(2) + ' MB, melebihi batas 2 MB.'); return; }
                            var url = URL.createObjectURL(f);
                            var img = new Image();
                            img.onload = function () {
                                var ratio = img.width / Math.max(1, img.height);
                                if (ratio < 1.6 || ratio > 1.96) {
                                    reject('Rasio gambar ' + img.width + ' × ' + img.height + ' px belum 16:9. Pangkas gambar (contoh 1280 × 720 px).');
                                } else {
                                    prev.src = url;
                                    prev.style.display = 'block';
                                    if (img.width < 800) {
                                        warnEl.textContent = 'Resolusi ' + img.width + ' × ' + img.height + ' px cukup rendah dan bisa tampak buram. Disarankan 1280 × 720 px.';
                                        warnEl.style.display = 'block';
                                    }
                                    return;
                                }
                                URL.revokeObjectURL(url);
                            };
                            img.onerror = function () { reject('Berkas tidak dapat dibaca sebagai gambar.'); URL.revokeObjectURL(url); };
                            img.src = url;
                        });
                    })();
                </script>
<?php EventHelper::renderBottom(); ?>