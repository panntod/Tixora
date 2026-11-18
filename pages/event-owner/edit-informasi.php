<?php
declare(strict_types=1);

require_once defined('ROOT_DIR')
    ? ROOT_DIR . '/api/helpers/ViewHelper.php'
    : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once defined('ROOT_DIR')
    ? ROOT_DIR . '/api/helpers/EventHelper.php'
    : __DIR__ . '/../../api/helpers/EventHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EO::loadEvent($db, $owner);
$id = $event['id'];

$editable = in_array($event['status_verifikasi'], ['DRAF', 'DITOLAK'], true);
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ViewHelper::checkCsrf()) {
        $errorMsg = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (!$editable) {
        $errorMsg = 'Acara tidak dapat diedit pada status ini. Tarik pengajuan atau hubungi admin.';
    } 
    else {
        $judul          = trim($_POST['judul_event'] ?? '');
        $deskripsi      = trim($_POST['deskripsi'] ?? '');
        $tanggalMulai   = $_POST['tanggal_mulai'] ?? '';
        $tanggalSelesai = $_POST['tanggal_selesai'] ?? '';
        $tempat         = trim($_POST['nama_tempat'] ?? '');
        $kota           = trim($_POST['kota'] ?? '');
        $kategori       = trim($_POST['kategori'] ?? 'Seminar');
        
        // Validasi canonical — sama persis dengan wizard step 1
        $errors = EventHelper::validateInfo([
            'judul_event'     => $judul,
            'kategori'        => $kategori,
            'deskripsi'       => $deskripsi,
            'tanggal_mulai'   => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'nama_tempat'     => $tempat,
            'kota'            => $kota,
        ]);
            
        if (!empty($errors)) {
            // Gabungkan pesan error per field menjadi satu alert (minimal diff).
            // // Untuk UX lebih baik, lihat "Bonus — Error per field" di bawah.
            $errorMsg = implode(' ', array_values($errors));
        } else {
            // Normalisasi ke format TIMESTAMP yang aman untuk PostgreSQL.
            // Ini juga menerima "2026-12-01T08:00" dari <input type="datetime-local">.
            $mulaiDb   = date('Y-m-d H:i:s', strtotime($tanggalMulai));
            $selesaiDb = date('Y-m-d H:i:s', strtotime($tanggalSelesai));

            // Reset alasan_penolakan hanya jika status saat ini DITOLAK.
            // `CASE WHEN ... THEN NULL ELSE ... END` memastikan DRAF tidak terpengaruh.
            $db->prepare("
                UPDATE events SET
                    judul_event      = :judul,
                    deskripsi        = :deskripsi,
                    kategori         = :kategori,
                    tanggal_mulai    = :tm,
                    tanggal_selesai  = :ts,
                    nama_tempat      = :tempat,
                    kota             = :kota,
                    alasan_penolakan = CASE
                                     WHEN status_verifikasi = 'DITOLAK' THEN NULL
                                     ELSE alasan_penolakan
                                   END,
                    updated_at       = CURRENT_TIMESTAMP
                WHERE id = :id AND owner_id = :owner_id
        ")->execute([
            'judul'     => $judul,
            'deskripsi' => $deskripsi,
            'kategori'  => $kategori,
            'tm'        => $mulaiDb,
            'ts'        => $selesaiDb,
            'tempat'    => $tempat,
            'kota'      => $kota,
            'id'        => $id,
            'owner_id'  => $owner['id'],
        ]);
        header('Location: ' . EO::url('edit-informasi', $id) . '?saved=1');
        exit;
    }
}
}

$successMsg = isset($_GET['saved']) ? 'Perubahan berhasil disimpan.' : '';

$subtitle = EO::badge(EO::eventBadge($event['status_verifikasi']))
    . ' &nbsp;<span class="slate" style="font-size:14px">'
    . ($editable ? 'Dapat diedit' : 'Tidak dapat diedit pada status ini')
    . '</span>';

EO::open($db, $owner, $event, '', $subtitle, '', 'Edit informasi acara', 'Edit');
?>
<div class="stack gap-24" style="width:880px">
    <?php if ($successMsg): ?>
        <div class="alert alert-success"><?= EO::e($successMsg) ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert-error"><?= EO::e($errorMsg) ?></div>
    <?php endif; ?>

    <?php if (!$editable): ?>
        <div class="alert alert-warning">
            Acara berstatus <b><?= EO::e($event['status_verifikasi']) ?></b> — tidak dapat diedit.
            <?php if ($event['status_verifikasi'] === 'MENUNGGU_TINJAUAN'): ?>
                <a href="<?= EO::url('pengajuan', $id) ?>" class="link-text" style="text-decoration:none">Tarik pengajuan</a>
                untuk mengedit.
            <?php elseif ($event['status_verifikasi'] === 'TERBIT'): ?>
                Hubungi admin untuk perubahan pada acara yang sudah terbit.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="stack gap-24">
        <?= ViewHelper::csrfField() ?>

        <div class="card">
            <h2 class="card-title">Informasi dasar</h2>
            <div class="stack gap-16">
                <div>
                    <label class="field-label">Nama acara</label>
                    <input type="text" name="judul_event" class="input" required maxlength="255"
                        value="<?= EO::e($event['judul_event']) ?>" <?= $editable ? '' : 'disabled' ?>>
                </div>
                <div class="two-fields">
                    <div>
                        <label class="field-label">Kategori</label>
                        <select name="kategori" class="input" <?= $editable ? '' : 'disabled' ?>>
                            <?php foreach (['Seminar', 'Workshop', 'Konferensi', 'Kompetisi'] as $k): ?>
                                <option value="<?= $k ?>" <?= $event['kategori'] === $k ? 'selected' : '' ?>><?= $k ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Penyelenggara</label>
                        <input type="text" class="input" value="<?= EO::e($owner['nama']) ?>" readonly
                            style="background:#F8F8FC">
                    </div>
                </div>
                <div>
                    <label class="field-label">Deskripsi</label>
                    <textarea name="deskripsi" class="input input-area" style="height:120px" required
                        <?= $editable ? '' : 'disabled' ?>><?= EO::e($event['deskripsi']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">Waktu &amp; lokasi</h2>
            <div class="stack gap-16">
                <div class="two-fields">
                    <div>
                        <label class="field-label">Mulai</label>
                        <input type="datetime-local" name="tanggal_mulai" class="input" required
                            value="<?= date('Y-m-d\TH:i', strtotime($event['tanggal_mulai'])) ?>"
                            <?= $editable ? '' : 'disabled' ?>>
                    </div>
                    <div>
                        <label class="field-label">Selesai</label>
                        <input type="datetime-local" name="tanggal_selesai" class="input" required
                            value="<?= date('Y-m-d\TH:i', strtotime($event['tanggal_selesai'])) ?>"
                            <?= $editable ? '' : 'disabled' ?>>
                    </div>
                </div>
                <div class="two-fields">
                    <div>
                        <label class="field-label">Nama tempat</label>
                        <input type="text" name="nama_tempat" class="input" required maxlength="255"
                            value="<?= EO::e($event['nama_tempat']) ?>" <?= $editable ? '' : 'disabled' ?>>
                    </div>
                    <div>
                        <label class="field-label">Kota</label>
                        <input type="text" name="kota" class="input" required maxlength="100"
                            value="<?= EO::e($event['kota']) ?>" <?= $editable ? '' : 'disabled' ?>>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($editable): ?>
            <div class="wizard-footer" style="width:880px;box-sizing:border-box;display:flex;justify-content:flex-end;gap:12px">
                <a href="<?= EO::url('ringkasan', $id) ?>" class="button secondary-button" style="text-decoration:none">Batal</a>
                <button type="submit" class="button primary-button" style="cursor:pointer">Simpan perubahan</button>
            </div>
        <?php endif; ?>
    </form>
</div>
<?php EO::close(); ?>