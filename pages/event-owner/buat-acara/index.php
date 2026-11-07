<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../../api/helpers/ViewHelper.php';

$root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 3);
require_once $root . '/api/helpers/UploadHelper.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);

/**
 * Pastikan kolom harga (migrasi 002) dan jadwal penjualan tiket (migrasi 005) sudah ada.
 * Keduanya idempotent dan hanya menambah struktur.
 */
$pastikanSkema = static function (PDO $db) use ($root): void {
    PaymentHelper::ensureSchema($db);
    $ada = $db->query(
        "SELECT 1 FROM information_schema.columns
         WHERE table_name = 'ticket_categories' AND column_name = 'mulai_dijual' LIMIT 1"
    )->fetchColumn();
    if (!$ada) {
        $sql = file_get_contents($root . '/api/migrations/005_jadwal_penjualan_tiket.sql');
        if ($sql === false) {
            throw new RuntimeException('Berkas api/migrations/005_jadwal_penjualan_tiket.sql tidak ditemukan.');
        }
        $db->exec($sql);
    }
};

$kirimJson = static function (array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

/* ------------------------------------------------------------------ */
/* POST: simpan draf / ajukan (dipanggil lewat fetch dari wizard)      */
/* ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ViewHelper::checkCsrf()) {
        $kirimJson(['ok' => false, 'message' => 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.'], 419);
    }

    $aksi = (($_POST['aksi'] ?? 'draf') === 'ajukan') ? 'ajukan' : 'draf';
    $eventId = trim((string) ($_POST['event_id'] ?? ''));

    $judul = trim((string) ($_POST['judul_event'] ?? ''));
    $kategori = trim((string) ($_POST['kategori'] ?? 'Seminar'));
    $deskripsi = trim((string) ($_POST['deskripsi'] ?? ''));
    $mulaiRaw = trim((string) ($_POST['tanggal_mulai'] ?? ''));
    $selesaiRaw = trim((string) ($_POST['tanggal_selesai'] ?? ''));
    $tempat = trim((string) ($_POST['nama_tempat'] ?? ''));
    $kota = trim((string) ($_POST['kota'] ?? ''));

    if (!in_array($kategori, ['Seminar', 'Workshop', 'Konferensi', 'Kompetisi'], true)) {
        $kategori = 'Seminar';
    }

    $errors = [];

    /* --- Informasi & jadwal --- */
    if ($judul === '') {
        $errors[] = 'Nama acara wajib diisi.';
    } elseif (mb_strlen($judul) > 255) {
        $errors[] = 'Nama acara maksimal 255 karakter.';
    }
    if ($deskripsi === '') {
        $errors[] = 'Deskripsi wajib diisi.';
    } elseif ($aksi === 'ajukan' && mb_strlen($deskripsi) < 50) {
        $errors[] = 'Deskripsi minimal 50 karakter.';
    }
    $tsMulai = $mulaiRaw !== '' ? strtotime($mulaiRaw) : false;
    $tsSelesai = $selesaiRaw !== '' ? strtotime($selesaiRaw) : false;
    if ($tsMulai === false || $tsSelesai === false) {
        $errors[] = 'Waktu mulai dan waktu selesai wajib diisi dengan benar.';
    } elseif ($tsSelesai <= $tsMulai) {
        $errors[] = 'Waktu selesai harus setelah waktu mulai.';
    }
    if ($tempat === '' || mb_strlen($tempat) > 255) {
        $errors[] = 'Nama tempat wajib diisi (maksimal 255 karakter).';
    }
    if ($kota === '' || mb_strlen($kota) > 100) {
        $errors[] = 'Kota wajib diisi (maksimal 100 karakter).';
    }

    /* --- Tiket & kapasitas --- */
    $parseTanggal = static function ($s): ?string {
        if (!is_string($s) || $s === '') {
            return null;
        }
        $d = DateTime::createFromFormat('!Y-m-d', $s);
        return ($d && $d->format('Y-m-d') === $s) ? $s : null;
    };

    $tiketRaw = json_decode((string) ($_POST['tiket_json'] ?? '[]'), true);
    $rundownRaw = json_decode((string) ($_POST['rundown_json'] ?? '[]'), true);
    $tiketRaw = is_array($tiketRaw) ? array_slice($tiketRaw, 0, 10) : [];
    $rundownRaw = is_array($rundownRaw) ? array_slice($rundownRaw, 0, 100) : [];

    $tiket = [];
    $namaTerpakai = [];
    foreach ($tiketRaw as $t) {
        if (!is_array($t)) {
            continue;
        }
        $nama = trim((string) ($t['nama'] ?? ''));
        if ($nama === '') {
            if ($aksi === 'ajukan') {
                $errors[] = 'Nama kategori tiket wajib diisi.';
            }
            continue;
        }
        if (mb_strlen($nama) > 100) {
            $errors[] = 'Nama kategori tiket maksimal 100 karakter.';
            continue;
        }
        $kunci = mb_strtolower($nama);
        if (isset($namaTerpakai[$kunci])) {
            $errors[] = 'Nama kategori tiket "' . $nama . '" dipakai lebih dari sekali.';
            continue;
        }
        $namaTerpakai[$kunci] = true;

        $hargaStr = preg_replace('/\D/', '', (string) ($t['harga'] ?? ''));
        $kuotaStr = preg_replace('/\D/', '', (string) ($t['kuota'] ?? ''));
        if ($aksi === 'ajukan' && $hargaStr === '') {
            $errors[] = 'Harga kategori "' . $nama . '" wajib diisi (isi 0 jika gratis).';
        }
        $harga = $hargaStr === '' ? 0 : min((int) $hargaStr, 999999999);
        $kuota = $kuotaStr === '' ? 0 : min((int) $kuotaStr, 100000);
        if ($aksi === 'ajukan' && $kuota < 1) {
            $errors[] = 'Kuota kategori "' . $nama . '" minimal 1.';
        }

        $mulaiJual = $parseTanggal($t['mulai'] ?? null);
        $akhirJual = $parseTanggal($t['selesai'] ?? null);
        if ($aksi === 'ajukan' && ($mulaiJual === null || $akhirJual === null)) {
            $errors[] = 'Tanggal mulai dan berakhir penjualan kategori "' . $nama . '" wajib diisi.';
        } elseif ($mulaiJual !== null && $akhirJual !== null && $akhirJual < $mulaiJual) {
            $errors[] = 'Tanggal berakhir penjualan kategori "' . $nama . '" tidak boleh sebelum tanggal mulai.';
        }

        $tiket[] = [
            'nama' => $nama, 'harga' => $harga, 'kuota' => $kuota,
            'mulai' => $mulaiJual, 'selesai' => $akhirJual,
        ];
    }
    if ($aksi === 'ajukan' && !$tiket) {
        $errors[] = 'Tambahkan minimal satu kategori tiket.';
    }

    /* --- Rundown --- */
    $rundown = [];
    $reWaktu = '/^([01]\d|2[0-3]):[0-5]\d$/';
    foreach ($rundownRaw as $r) {
        if (!is_array($r)) {
            continue;
        }
        $judulSesi = trim((string) ($r['judul'] ?? ''));
        $wMulai = trim((string) ($r['mulai'] ?? ''));
        $wSelesai = trim((string) ($r['selesai'] ?? ''));
        $ket = trim((string) ($r['deskripsi'] ?? ''));
        $pic = trim((string) ($r['pic'] ?? ''));
        if ($judulSesi === '' || !preg_match($reWaktu, $wMulai) || !preg_match($reWaktu, $wSelesai) || $wSelesai <= $wMulai) {
            if ($aksi === 'ajukan') {
                $errors[] = 'Ada kegiatan rundown yang belum lengkap atau waktunya tidak valid.';
            }
            continue;
        }
        $rundown[] = [
            'mulai' => $wMulai, 'selesai' => $wSelesai,
            'judul' => mb_substr($judulSesi, 0, 255),
            'ket' => $ket !== '' ? $ket : null,
            'pic' => $pic !== '' ? mb_substr($pic, 0, 255) : null,
        ];
    }
    if ($aksi === 'ajukan' && !$rundown) {
        $errors[] = 'Tambahkan minimal satu kegiatan pada rundown.';
    }

    /* --- Poster (opsional) --- */
    $bannerBaru = null;
    $adaPoster = isset($_FILES['poster']) && (int) ($_FILES['poster']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if (!$errors && $adaPoster) {
        try {
            $info = @getimagesize((string) $_FILES['poster']['tmp_name']);
            if ($info === false) {
                throw new RuntimeException('Berkas poster bukan gambar yang valid.');
            }
            $rasio = $info[0] / max(1, $info[1]);
            if ($rasio < 1.6 || $rasio > 1.95) {
                throw new RuntimeException('Rasio poster harus 16:9.');
            }
            $uploader = new UploadHelper($root . '/assets/uploads/');
            $bannerBaru = 'assets/uploads/' . $uploader->uploadFile($_FILES['poster']);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    if ($errors) {
        $errors = array_values(array_unique($errors));
        $kirimJson(['ok' => false, 'message' => $errors[0], 'errors' => $errors], 422);
    }

    /* --- Simpan --- */
    try {
        $pastikanSkema($db);
        $db->beginTransaction();

        $status = ($aksi === 'ajukan') ? 'MENUNGGU_TINJAUAN' : 'DRAF';
        $kuotaTotal = (int) array_sum(array_column($tiket, 'kuota'));
        $mulaiSql = date('Y-m-d H:i:s', (int) $tsMulai);
        $selesaiSql = date('Y-m-d H:i:s', (int) $tsSelesai);
        $baru = false;

        if ($eventId !== '') {
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $eventId)) {
                throw new RuntimeException('Draf tidak valid.');
            }
            $cek = $db->prepare(
                "SELECT id FROM events WHERE id = :id AND owner_id = :owner AND status_verifikasi = 'DRAF' FOR UPDATE"
            );
            $cek->execute(['id' => $eventId, 'owner' => $owner['id']]);
            if (!$cek->fetchColumn()) {
                throw new RuntimeException('Draf tidak ditemukan atau sudah diajukan.');
            }
            $upd = $db->prepare("
                UPDATE events SET
                    judul_event = :judul, deskripsi = :deskripsi, kategori = :kategori, kuota = :kuota,
                    tanggal_mulai = :mulai, tanggal_selesai = :selesai, nama_tempat = :tempat, kota = :kota,
                    banner_url = COALESCE(:banner, banner_url),
                    status_verifikasi = :status, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $upd->execute([
                'judul' => $judul, 'deskripsi' => $deskripsi, 'kategori' => $kategori, 'kuota' => $kuotaTotal,
                'mulai' => $mulaiSql, 'selesai' => $selesaiSql, 'tempat' => $tempat, 'kota' => $kota,
                'banner' => $bannerBaru, 'status' => $status, 'id' => $eventId,
            ]);
            $db->prepare('DELETE FROM rundowns WHERE event_id = :e')->execute(['e' => $eventId]);
            $db->prepare('DELETE FROM ticket_categories WHERE event_id = :e')->execute(['e' => $eventId]);
            $newEventId = $eventId;
        } else {
            $baru = true;
            $ins = $db->prepare("
                INSERT INTO events (
                    owner_id, judul_event, deskripsi, kategori, kuota,
                    tanggal_mulai, tanggal_selesai, nama_tempat, kota, banner_url, status_verifikasi
                ) VALUES (
                    :owner, :judul, :deskripsi, :kategori, :kuota,
                    :mulai, :selesai, :tempat, :kota, :banner, :status
                ) RETURNING id
            ");
            $ins->execute([
                'owner' => $owner['id'], 'judul' => $judul, 'deskripsi' => $deskripsi, 'kategori' => $kategori,
                'kuota' => $kuotaTotal, 'mulai' => $mulaiSql, 'selesai' => $selesaiSql,
                'tempat' => $tempat, 'kota' => $kota,
                'banner' => $bannerBaru ?? ('default-' . random_int(1, 4)), 'status' => $status,
            ]);
            $newEventId = (string) $ins->fetchColumn();
        }

        // Kategori tiket
        $insCat = $db->prepare("
            INSERT INTO ticket_categories (event_id, nama_kategori, harga, kuota, wajib_ktm, mulai_dijual, berakhir_dijual)
            VALUES (:event_id, :nama, :harga, :kuota, FALSE, :mulai, :selesai)
        ");
        foreach ($tiket as $t) {
            $insCat->execute([
                'event_id' => $newEventId, 'nama' => $t['nama'], 'harga' => $t['harga'], 'kuota' => $t['kuota'],
                'mulai' => $t['mulai'], 'selesai' => $t['selesai'],
            ]);
        }

        // Rundown (penanggung jawab dicatat sebagai panitia acara ini bila belum ada)
        $cariPanitia = $db->prepare(
            'SELECT id FROM committees WHERE event_id = :e AND lower(nama_panitia) = lower(:n) LIMIT 1'
        );
        $buatPanitia = $db->prepare("
            INSERT INTO committees (event_id, nama_panitia, posisi_jabatan, divisi)
            VALUES (:e, :n, 'Penanggung jawab', 'Acara') RETURNING id
        ");
        $insRun = $db->prepare("
            INSERT INTO rundowns (event_id, committee_id, waktu_mulai, waktu_selesai, judul_sesi, keterangan)
            VALUES (:e, :c, :mulai, :selesai, :judul, :ket)
        ");
        $petaPanitia = [];
        foreach ($rundown as $r) {
            $committeeId = null;
            if ($r['pic'] !== null) {
                $k = mb_strtolower($r['pic']);
                if (!isset($petaPanitia[$k])) {
                    $cariPanitia->execute(['e' => $newEventId, 'n' => $r['pic']]);
                    $id = $cariPanitia->fetchColumn();
                    if (!$id) {
                        $buatPanitia->execute(['e' => $newEventId, 'n' => $r['pic']]);
                        $id = $buatPanitia->fetchColumn();
                    }
                    $petaPanitia[$k] = (string) $id;
                }
                $committeeId = $petaPanitia[$k];
            }
            $insRun->execute([
                'e' => $newEventId, 'c' => $committeeId, 'mulai' => $r['mulai'], 'selesai' => $r['selesai'],
                'judul' => $r['judul'], 'ket' => $r['ket'],
            ]);
        }

        // Log pengajuan: saat acara pertama kali dibuat atau saat diajukan
        if ($baru || $status === 'MENUNGGU_TINJAUAN') {
            $catatan = ($status === 'MENUNGGU_TINJAUAN')
                ? ($baru ? 'Acara dibuat dan langsung diajukan ke admin kampus.' : 'Draf acara diajukan ke admin kampus.')
                : 'Draf acara berhasil disimpan.';
            $db->prepare(
                'INSERT INTO event_submission_logs (event_id, status, catatan) VALUES (:e, :s, :c)'
            )->execute(['e' => $newEventId, 's' => $status, 'c' => $catatan]);
        }

        $db->commit();

        $kirimJson([
            'ok' => true,
            'event_id' => $newEventId,
            'redirect' => BASE_URL . '/event-owner/acara-saya?created=1',
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('[buat-acara] ' . $e->getMessage());
        $pesan = ($e instanceof RuntimeException)
            ? $e->getMessage()
            : 'Gagal menyimpan acara. Coba lagi beberapa saat.';
        $kirimJson(['ok' => false, 'message' => $pesan], 500);
    }
}

/* ------------------------------------------------------------------ */
/* GET: data pendukung tampilan                                        */
/* ------------------------------------------------------------------ */
try {
    $pastikanSkema($db);
} catch (Throwable $e) {
    error_log('[buat-acara] skema: ' . $e->getMessage());
}

$institusi = '';
$namaPanitiaLama = [];
try {
    $st = $db->prepare('SELECT institusi FROM users WHERE id = :id');
    $st->execute(['id' => $owner['id']]);
    $institusi = trim((string) $st->fetchColumn());

    $st = $db->prepare("
        SELECT DISTINCT c.nama_panitia
        FROM committees c
        JOIN events e ON e.id = c.event_id
        WHERE e.owner_id = :owner
        ORDER BY c.nama_panitia
        LIMIT 50
    ");
    $st->execute(['owner' => $owner['id']]);
    $namaPanitiaLama = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (Throwable $e) {
    error_log('[buat-acara] data pendukung: ' . $e->getMessage());
}
$penyelenggara = $institusi !== '' ? $institusi : (string) $owner['nama'];

$cfg = [
    'csrf' => AuthHelper::generateCsrfToken(),
    'action' => BASE_URL . '/event-owner/buat-acara',
    'listUrl' => BASE_URL . '/event-owner/acara-saya',
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Buat Acara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
    <style>
        [hidden] { display: none !important; }
        .button { cursor: pointer; }
        .button.is-disabled, .button:disabled { opacity: .45; cursor: not-allowed; }
        .wz-panel { max-width: 880px; }
        #wizard.is-wide #footer { max-width: 1128px; }
        #footer { max-width: 880px; box-sizing: border-box; }
        .step-item.is-link { cursor: pointer; }
        .dropzone { background: #efeff8; border: 2px solid #c4c4dc; border-radius: 12px; padding: 28px 16px; text-align: center; color: #4b5170; font-size: 14px; line-height: 21px; }
        .dropzone b { display: block; color: #4b5170; }
        .dropzone.input-error { border-color: #b91c1c; }
        .dropzone img { max-width: 240px; max-height: 135px; border-radius: 8px; display: block; margin: 0 auto 10px; object-fit: cover; }
        .dropzone .button { margin-top: 10px; height: 32px; }
        textarea.input { height: 120px; padding: 12px; resize: vertical; display: block; }
        .run-row[draggable="true"] { cursor: grab; }
        .run-empty { background: #fff; border-radius: 12px; padding: 24px; color: #4b5170; font-size: 14px; box-shadow: 0 1px 2px rgba(14,17,40,.06); }
        .rv-hint { font-size: 14px; line-height: 21px; color: #4b5170; }
        .alert { margin-bottom: 20px; }
    </style>
</head>

<body>
    <div class="frame">
        <div class="app">
            <aside class="sidebar">
                <div class="logo">
                    <img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA">
                </div>
                <a href="<?= BASE_URL ?>/event-owner" class="side-item" style="text-decoration:none">Dasbor</a>
                <a href="<?= BASE_URL ?>/event-owner/acara-saya" class="side-item" style="text-decoration:none">Acara saya</a>
                <a href="<?= BASE_URL ?>/event-owner/buat-acara" class="side-item side-item-active"
                    style="text-decoration:none">Buat acara</a>
                <a href="<?= BASE_URL ?>/jelajah-acara" class="side-item" style="text-decoration:none">Jelajah Acara</a>
                <a href="<?= BASE_URL ?>/logout" class="side-item" style="text-decoration:none;color:#B91C1C">Keluar</a>
                <div class="side-user">
                    <b><?= htmlspecialchars((string) $owner['nama']) ?></b>
                    <?= htmlspecialchars($institusi !== '' ? $institusi : (string) $owner['email']) ?>
                </div>
            </aside>

            <main class="main">
                <p class="crumb">Acara saya / Buat acara</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Buat acara baru</h1>
                        <p class="page-subtitle" id="subtitle"></p>
                    </div>
                    <div class="head-actions">
                        <button type="button" class="button secondary-button" id="btnPratinjau">Pratinjau</button>
                    </div>
                </div>

                <div class="stepper" id="stepper"></div>

                <form id="wizard" novalidate autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($cfg['csrf']) ?>">

                    <div id="flash" class="alert wz-panel" hidden></div>

                    <!-- ============ LANGKAH 1: Informasi & jadwal ============ -->
                    <section data-step="1" class="stack gap-24 wz-panel">
                        <div id="banner1" class="alert alert-error" hidden style="margin-bottom:0"></div>

                        <div class="card">
                            <h2 class="card-title">Informasi dasar</h2>
                            <div class="stack gap-16">
                                <div>
                                    <label class="field-label" for="f_judul">Nama acara</label>
                                    <input type="text" id="f_judul" class="input" maxlength="255"
                                        placeholder="Contoh: Workshop Cloud Computing 2026">
                                    <p class="help help-error" id="e_judul" hidden></p>
                                </div>
                                <div class="two-fields">
                                    <div>
                                        <label class="field-label" for="f_kategori">Kategori</label>
                                        <select id="f_kategori" class="input">
                                            <option>Seminar</option>
                                            <option>Workshop</option>
                                            <option>Konferensi</option>
                                            <option>Kompetisi</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="field-label">Penyelenggara</label>
                                        <input type="text" class="input" readonly style="background:#F8F8FC"
                                            value="<?= htmlspecialchars($penyelenggara) ?>">
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label" for="f_deskripsi">Deskripsi</label>
                                    <textarea id="f_deskripsi" class="input"
                                        placeholder="Jelaskan tujuan, topik pembahasan, dan manfaat mengikuti acara ini (minimal 50 karakter)"></textarea>
                                    <p class="help help-error" id="e_deskripsi" hidden></p>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <h2 class="card-title">Poster</h2>
                            <div class="dropzone" id="dropzone">
                                <div id="posterPreview" hidden></div>
                                <b id="posterTitle">Unggah poster acara</b>
                                <span id="posterHint">JPG atau PNG, rasio 16:9, maks. 2 MB</span><br>
                                <input type="file" id="f_poster" accept="image/jpeg,image/png" hidden>
                                <button type="button" class="button secondary-button small-button" id="btnPoster">Pilih berkas</button>
                            </div>
                            <p class="help" id="posterNote">Opsional. Jika tidak mengunggah poster, sistem otomatis memakai salah satu poster default (dipilih acak).</p>
                            <p class="help help-error" id="e_poster" hidden></p>
                        </div>

                        <div class="card">
                            <h2 class="card-title">Waktu &amp; lokasi</h2>
                            <div class="stack gap-16">
                                <div class="two-fields">
                                    <div>
                                        <label class="field-label" for="f_mulai">Mulai</label>
                                        <input type="datetime-local" id="f_mulai" class="input">
                                        <p class="help help-error" id="e_mulai" hidden></p>
                                    </div>
                                    <div>
                                        <label class="field-label" for="f_selesai">Selesai</label>
                                        <input type="datetime-local" id="f_selesai" class="input">
                                        <p class="help help-error" id="e_selesai" hidden></p>
                                    </div>
                                </div>
                                <div class="two-fields">
                                    <div>
                                        <label class="field-label" for="f_tempat">Nama tempat</label>
                                        <input type="text" id="f_tempat" class="input" maxlength="255"
                                            placeholder="Contoh: Auditorium Utama">
                                        <p class="help help-error" id="e_tempat" hidden></p>
                                    </div>
                                    <div>
                                        <label class="field-label" for="f_kota">Kota</label>
                                        <input type="text" id="f_kota" class="input" maxlength="100" value="Malang">
                                        <p class="help help-error" id="e_kota" hidden></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ============ LANGKAH 2: Tiket & kapasitas ============ -->
                    <section data-step="2" class="stack gap-24 wz-panel" hidden>
                        <div class="alert alert-info" style="margin-bottom:0">Kapasitas total acara dihitung dari jumlah kuota seluruh kategori:
                            <b id="kapasitas">0 kursi</b>.</div>
                        <div id="tiketList" class="stack gap-24"></div>
                        <p class="help help-error" id="e_tiket" hidden></p>
                        <div><button type="button" class="button secondary-button" id="btnTambahTiket">+ Tambah kategori</button></div>
                    </section>

                    <!-- ============ LANGKAH 3: Rundown ============ -->
                    <section data-step="3" hidden>
                        <div class="cols">
                            <div style="width:680px" id="rundownList"></div>
                            <div class="card col-narrow" style="box-sizing:border-box">
                                <h2 class="card-title" id="rdTitle">Kegiatan baru</h2>
                                <div class="stack gap-16" id="rdForm">
                                    <div class="two-fields">
                                        <div>
                                            <label class="field-label" for="r_mulai">Mulai</label>
                                            <input type="time" id="r_mulai" class="input">
                                        </div>
                                        <div>
                                            <label class="field-label" for="r_selesai">Selesai</label>
                                            <input type="time" id="r_selesai" class="input">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="field-label" for="r_judul">Kegiatan</label>
                                        <input type="text" id="r_judul" class="input" maxlength="255" placeholder="Nama kegiatan">
                                    </div>
                                    <div>
                                        <label class="field-label" for="r_deskripsi">Deskripsi</label>
                                        <input type="text" id="r_deskripsi" class="input" placeholder="Keterangan singkat">
                                    </div>
                                    <div>
                                        <label class="field-label" for="r_pic">Penanggung jawab</label>
                                        <input type="text" id="r_pic" class="input" maxlength="255" list="dl_pic"
                                            placeholder="Pilih atau ketik nama panitia">
                                        <datalist id="dl_pic">
                                            <?php foreach ($namaPanitiaLama as $np): ?>
                                                <option value="<?= htmlspecialchars((string) $np) ?>"></option>
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                    <p class="help help-error" id="e_rd" hidden></p>
                                    <button type="button" class="button primary-button" id="btnSimpanKegiatan">Simpan kegiatan</button>
                                    <button type="button" class="button ghost-button small-button" id="btnBatalEdit" hidden>Batal ubah</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ============ LANGKAH 4: Tinjau & ajukan ============ -->
                    <section data-step="4" class="stack gap-24 wz-panel" hidden>
                        <div class="alert alert-info" style="margin-bottom:0">Setelah diajukan, acara berstatus <b>Menunggu tinjauan</b> dan tidak
                            dapat diedit sampai admin memberi keputusan.</div>
                        <div id="reviewCards" class="stack gap-24"></div>
                    </section>

                    <div class="wizard-footer" id="footer">
                        <button type="button" class="button ghost-button" id="btnDraf">Simpan draf</button>
                        <div class="row">
                            <a href="<?= BASE_URL ?>/event-owner/acara-saya" class="button secondary-button" id="btnBatal"
                                style="text-decoration:none">Batal</a>
                            <button type="button" class="button secondary-button" id="btnBack" hidden>Kembali</button>
                            <button type="button" class="button primary-button" id="btnNext"></button>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script>
    (function () {
        'use strict';
        const CFG = <?= json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;
        const $ = (s, r) => (r || document).querySelector(s);
        const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
        const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const pad = n => String(n).padStart(2, '0');
        const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const CEK = '<svg width="12" height="12" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        const GRIP = '<svg width="14" height="18" viewBox="0 0 14 18" aria-hidden="true" fill="#8A90AB"><circle cx="4" cy="4" r="1.5"/><circle cx="10" cy="4" r="1.5"/><circle cx="4" cy="9" r="1.5"/><circle cx="10" cy="9" r="1.5"/><circle cx="4" cy="14" r="1.5"/><circle cx="10" cy="14" r="1.5"/></svg>';
        const PANAH_ATAS = '<svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 11V3M3.5 6.5L7 3l3.5 3.5" stroke="#4B5170" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        const PANAH_BAWAH = '<svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 3v8M3.5 7.5L7 11l3.5-3.5" stroke="#4B5170" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        const STEPS = ['Informasi & jadwal', 'Tiket & kapasitas', 'Rundown', 'Tinjau & ajukan'];
        const NEXT_LABEL = { 1: 'Lanjut: Tiket & kapasitas', 2: 'Lanjut: Rundown', 3: 'Lanjut: Tinjau & ajukan', 4: 'Ajukan untuk ditinjau' };

        const state = {
            step: 1, attempted: {}, touched: {}, eventId: '', savedAt: '', dirty: false, saving: false, leaving: false,
            tiket: [], rundown: [], editIdx: -1, nextId: 1, posterError: '', posterOk: false
        };

        /* ---------------- util ---------------- */
        function flash(msg, type) {
            const el = $('#flash');
            if (!msg) { el.hidden = true; return; }
            el.className = 'alert wz-panel ' + (type === 'ok' ? 'alert-info' : 'alert-error');
            el.textContent = msg;
            el.hidden = false;
        }
        function setDisabled(btn, off) {
            btn.classList.toggle('is-disabled', !!off);
            btn.setAttribute('aria-disabled', off ? 'true' : 'false');
        }
        function fmtRupiah(n) { return n === 0 ? 'Gratis' : 'Rp' + Number(n).toLocaleString('id-ID'); }
        function digits(s) { return String(s == null ? '' : s).replace(/\D/g, ''); }
        function fmtRibuan(s) { const d = digits(s); return d === '' ? '' : Number(d).toLocaleString('id-ID'); }
        function waktuTampil(t) { return String(t || '').replace(':', '.'); }
        function tglPendek(d) { return d.getDate() + ' ' + BULAN[d.getMonth()] + ' ' + d.getFullYear(); }
        function jam(d) { return pad(d.getHours()) + '.' + pad(d.getMinutes()); }
        function fmtRentang(a, b) {
            const da = new Date(a), db = new Date(b);
            if (isNaN(da) || isNaN(db)) return '—';
            if (da.toDateString() === db.toDateString()) return tglPendek(da) + ', ' + jam(da) + '–' + jam(db);
            if (da.getMonth() === db.getMonth() && da.getFullYear() === db.getFullYear()) return da.getDate() + '–' + tglPendek(db);
            return tglPendek(da) + ' – ' + tglPendek(db);
        }
        function gabung(arr) {
            if (arr.length <= 1) return arr.join('');
            return arr.slice(0, -1).join(', ') + ' dan ' + arr[arr.length - 1];
        }
        function hariIni() { const d = new Date(); return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
        function tambahJam(t, n) { const [h, m] = t.split(':').map(Number); return pad((h + n) % 24) + ':' + pad(m); }
        function markDirty() { state.dirty = true; }

        /* ---------------- langkah 1 ---------------- */
        const K1 = ['judul', 'deskripsi', 'mulai', 'selesai', 'tempat', 'kota', 'poster'];
        const val = id => $('#' + id).value;

        function check1() {
            const e = {};
            if (!val('f_judul').trim()) e.judul = 'Nama acara wajib diisi.';
            const dl = val('f_deskripsi').trim().length;
            if (dl < 50) e.deskripsi = 'Deskripsi minimal 50 karakter (saat ini ' + dl + ').';
            if (!val('f_mulai')) e.mulai = 'Waktu mulai wajib diisi.';
            if (!val('f_selesai')) e.selesai = 'Waktu selesai wajib diisi.';
            else if (val('f_mulai') && new Date(val('f_selesai')) <= new Date(val('f_mulai'))) e.selesai = 'Waktu selesai harus setelah waktu mulai.';
            if (!val('f_tempat').trim()) e.tempat = 'Nama tempat wajib diisi.';
            if (!val('f_kota').trim()) e.kota = 'Kota wajib diisi.';
            if (state.posterError) e.poster = state.posterError;
            return e;
        }
        // Syarat minimum agar draf bisa disimpan (kolom NOT NULL di database)
        function bisaDraf() {
            const e = check1();
            return !(e.judul || e.mulai || e.selesai || e.tempat || e.kota || val('f_deskripsi').trim() === '' || e.poster);
        }

        function render1() {
            const e = check1();
            let shown = 0;
            K1.forEach(k => {
                const msg = e[k];
                const vis = !!msg && (state.attempted[1] || state.touched[k]);
                const err = $('#e_' + k);
                err.textContent = vis ? msg : '';
                err.hidden = !vis;
                const inp = k === 'poster' ? $('#dropzone') : $('#f_' + k);
                inp.classList.toggle('input-error', vis);
                if (vis) shown++;
            });
            const b = $('#banner1');
            b.hidden = shown === 0;
            b.textContent = shown ? 'Lengkapi ' + shown + ' kolom wajib sebelum melanjutkan.' : '';
            if (state.step === 1) setDisabled($('#btnNext'), Object.keys(e).length > 0);
            return e;
        }

        K1.filter(k => k !== 'poster').forEach(k => {
            const el = $('#f_' + k);
            ['input', 'change'].forEach(ev => el.addEventListener(ev, () => {
                state.touched[k] = true;
                if (k === 'mulai' && val('f_selesai')) state.touched.selesai = true;
                markDirty();
                render1();
            }));
            el.addEventListener('blur', () => { state.touched[k] = true; render1(); });
        });
        // tanggal berakhir default kategori tiket mengikuti tanggal mulai acara
        $('#f_mulai').addEventListener('change', () => { state.tiket.forEach(t => { if (!t.selesaiManual && val('f_mulai')) t.selesai = val('f_mulai').slice(0, 10); }); });

        /* poster */
        $('#btnPoster').addEventListener('click', () => $('#f_poster').click());
        $('#f_poster').addEventListener('change', () => {
            const f = $('#f_poster').files[0];
            state.posterError = ''; state.posterOk = false; state.touched.poster = true;
            $('#posterPreview').hidden = true;
            if (!f) { render1(); return; }
            if (!/^image\/(jpeg|png)$/.test(f.type)) { state.posterError = 'Poster harus berformat JPG atau PNG.'; $('#f_poster').value = ''; render1(); return; }
            if (f.size > 2 * 1024 * 1024) { state.posterError = 'Ukuran poster maksimal 2 MB.'; $('#f_poster').value = ''; render1(); return; }
            const url = URL.createObjectURL(f);
            const img = new Image();
            img.onload = () => {
                const r = img.naturalWidth / img.naturalHeight;
                if (r < 1.6 || r > 1.95) {
                    state.posterError = 'Rasio poster harus 16:9 (poster Anda ' + img.naturalWidth + '×' + img.naturalHeight + ').';
                    $('#f_poster').value = '';
                } else {
                    state.posterOk = true;
                    $('#posterPreview').innerHTML = '<img src="' + url + '" alt="Pratinjau poster">';
                    $('#posterPreview').hidden = false;
                    $('#posterTitle').textContent = f.name;
                    $('#posterHint').textContent = 'Poster siap diunggah saat disimpan.';
                    $('#btnPoster').textContent = 'Ganti berkas';
                    markDirty();
                }
                render1();
            };
            img.onerror = () => { state.posterError = 'Berkas poster bukan gambar yang valid.'; $('#f_poster').value = ''; render1(); };
            img.src = url;
        });

        /* ---------------- langkah 2 ---------------- */
        function tiketBaru(nama) {
            return { id: state.nextId++, nama: nama || '', harga: '', kuota: '', mulai: hariIni(), selesai: (val('f_mulai') || '').slice(0, 10), selesaiManual: false };
        }

        function tiketCardHtml(t) {
            return '<div class="card" data-id="' + t.id + '">' +
                '<div class="row-between" style="margin-bottom:12px"><b style="font-size:16px" data-role="title"></b>' +
                '<button type="button" class="button ghost-button small-button" data-act="hapus">Hapus</button></div>' +
                '<div class="two-fields">' +
                '<div><label class="field-label">Nama kategori</label><input type="text" class="input" data-k="nama" maxlength="100" value="' + esc(t.nama) + '"><p class="help help-error" data-e="nama" hidden></p></div>' +
                '<div><label class="field-label">Harga (Rp)</label><input type="text" inputmode="numeric" class="input" data-k="harga" placeholder="0 = gratis" value="' + esc(fmtRibuan(t.harga)) + '"><p class="help help-error" data-e="harga" hidden></p></div>' +
                '<div><label class="field-label">Kuota</label><input type="text" inputmode="numeric" class="input" data-k="kuota" value="' + esc(t.kuota) + '"><p class="help help-error" data-e="kuota" hidden></p></div>' +
                '</div>' +
                '<div class="two-fields" style="margin-top:16px">' +
                '<div><label class="field-label">Mulai dijual</label><input type="date" class="input" data-k="mulai" value="' + esc(t.mulai) + '"><p class="help help-error" data-e="mulai" hidden></p></div>' +
                '<div><label class="field-label">Berakhir</label><input type="date" class="input" data-k="selesai" value="' + esc(t.selesai) + '"><p class="help help-error" data-e="selesai" hidden></p></div>' +
                '</div></div>';
        }

        function renderTiket() {
            $('#tiketList').innerHTML = state.tiket.map(tiketCardHtml).join('');
            refresh2();
        }

        function check2() {
            const per = {};
            const dipakai = {};
            state.tiket.forEach(t => {
                const e = {};
                const nm = t.nama.trim().toLowerCase();
                if (!nm) e.nama = 'Nama kategori wajib diisi.';
                else if (dipakai[nm]) e.nama = 'Nama kategori sudah dipakai.';
                if (nm) dipakai[nm] = true;
                if (digits(t.harga) === '') e.harga = 'Harga wajib diisi (isi 0 jika gratis).';
                if (!(parseInt(digits(t.kuota) || '0', 10) >= 1)) e.kuota = 'Kuota minimal 1.';
                if (!t.mulai) e.mulai = 'Tanggal mulai wajib diisi.';
                if (!t.selesai) e.selesai = 'Tanggal berakhir wajib diisi.';
                else if (t.mulai && t.selesai < t.mulai) e.selesai = 'Tanggal berakhir tidak boleh sebelum tanggal mulai.';
                per[t.id] = e;
            });
            return per;
        }
        function totalKapasitas() {
            return state.tiket.reduce((a, t) => a + (parseInt(digits(t.kuota) || '0', 10) || 0), 0);
        }
        function refresh2() {
            const per = check2();
            let adaError = state.tiket.length === 0;
            state.tiket.forEach(t => {
                const card = $('.card[data-id="' + t.id + '"]');
                if (!card) return;
                $('[data-role="title"]', card).textContent = 'Kategori ' + (t.nama.trim() || 'baru');
                ['nama', 'harga', 'kuota', 'mulai', 'selesai'].forEach(k => {
                    const msg = per[t.id][k];
                    if (msg) adaError = true;
                    const vis = !!msg && (state.attempted[2] || state.touched[t.id + '.' + k]);
                    const err = $('[data-e="' + k + '"]', card);
                    err.textContent = vis ? msg : '';
                    err.hidden = !vis;
                    $('[data-k="' + k + '"]', card).classList.toggle('input-error', vis);
                });
            });
            $('#kapasitas').textContent = totalKapasitas().toLocaleString('id-ID') + ' kursi';
            const et = $('#e_tiket');
            const kosong = state.tiket.length === 0 && state.attempted[2];
            et.hidden = !kosong;
            et.textContent = kosong ? 'Tambahkan minimal satu kategori tiket.' : '';
            if (state.step === 2) setDisabled($('#btnNext'), adaError);
            return !adaError;
        }

        $('#tiketList').addEventListener('input', ev => {
            const inp = ev.target.closest('[data-k]');
            if (!inp) return;
            const card = inp.closest('.card');
            const t = state.tiket.find(x => x.id === Number(card.dataset.id));
            const k = inp.dataset.k;
            if (k === 'harga') { const d = digits(inp.value); t.harga = d; inp.value = fmtRibuan(d); }
            else if (k === 'kuota') { t.kuota = digits(inp.value); inp.value = t.kuota; }
            else t[k] = inp.value;
            if (k === 'selesai') t.selesaiManual = true;
            state.touched[t.id + '.' + k] = true;
            markDirty();
            refresh2();
        });
        $('#tiketList').addEventListener('focusout', ev => {
            const inp = ev.target.closest('[data-k]');
            if (!inp) return;
            const t = state.tiket.find(x => x.id === Number(inp.closest('.card').dataset.id));
            if (t) { state.touched[t.id + '.' + inp.dataset.k] = true; refresh2(); }
        });
        $('#tiketList').addEventListener('click', ev => {
            const btn = ev.target.closest('[data-act="hapus"]');
            if (!btn) return;
            const id = Number(btn.closest('.card').dataset.id);
            state.tiket = state.tiket.filter(x => x.id !== id);
            markDirty();
            renderTiket();
        });
        $('#btnTambahTiket').addEventListener('click', () => {
            if (state.tiket.length >= 10) { flash('Maksimal 10 kategori tiket.'); return; }
            state.tiket.push(tiketBaru(''));
            markDirty();
            renderTiket();
        });

        /* ---------------- langkah 3 ---------------- */
        function defaultKegiatan() {
            const last = state.rundown[state.rundown.length - 1];
            const mulai = last ? last.selesai : '08:00';
            return { mulai: mulai, selesai: tambahJam(mulai, 1), judul: '', deskripsi: '', pic: '' };
        }
        function isiFormKegiatan(k) {
            $('#r_mulai').value = k.mulai; $('#r_selesai').value = k.selesai;
            $('#r_judul').value = k.judul; $('#r_deskripsi').value = k.deskripsi; $('#r_pic').value = k.pic;
            $('#e_rd').hidden = true;
        }
        function renderRundown() {
            const box = $('#rundownList');
            if (!state.rundown.length) {
                box.innerHTML = '<div class="run-empty">Belum ada kegiatan. Tambahkan kegiatan pertama lewat formulir di samping.</div>';
                return;
            }
            box.innerHTML = state.rundown.map((r, i) => {
                const sub = [r.deskripsi, r.pic ? 'PIC: ' + r.pic : ''].filter(Boolean).join(' · ');
                return '<div class="run-row" draggable="true" data-i="' + i + '">' + GRIP +
                    '<span class="run-time" title="' + esc(waktuTampil(r.mulai) + '–' + waktuTampil(r.selesai)) + '">' + esc(waktuTampil(r.mulai)) + '</span>' +
                    '<div class="run-body"><b>' + esc(r.judul) + '</b>' + (sub ? '<p>' + esc(sub) + '</p>' : '') + '</div>' +
                    '<button type="button" class="button ghost-button small-button" style="padding:0 8px" data-act="up" aria-label="Naikkan">' + PANAH_ATAS + '</button>' +
                    '<button type="button" class="button ghost-button small-button" style="padding:0 8px" data-act="down" aria-label="Turunkan">' + PANAH_BAWAH + '</button>' +
                    '<button type="button" class="button ghost-button small-button" data-act="edit">Edit</button>' +
                    '<button type="button" class="button ghost-button small-button" style="color:#B91C1C" data-act="hapus">Hapus</button></div>';
            }).join('');
        }
        function keluarModeEdit() {
            state.editIdx = -1;
            $('#rdTitle').textContent = 'Kegiatan baru';
            $('#btnSimpanKegiatan').textContent = 'Simpan kegiatan';
            $('#btnBatalEdit').hidden = true;
            isiFormKegiatan(defaultKegiatan());
        }
        $('#btnSimpanKegiatan').addEventListener('click', () => {
            const k = { mulai: val('r_mulai'), selesai: val('r_selesai'), judul: val('r_judul').trim(), deskripsi: val('r_deskripsi').trim(), pic: val('r_pic').trim() };
            let msg = '';
            if (!k.judul) msg = 'Nama kegiatan wajib diisi.';
            else if (!k.mulai || !k.selesai) msg = 'Waktu mulai dan selesai wajib diisi.';
            else if (k.selesai <= k.mulai) msg = 'Waktu selesai harus setelah waktu mulai.';
            const e = $('#e_rd');
            e.hidden = !msg; e.textContent = msg;
            if (msg) return;
            if (state.editIdx >= 0) state.rundown[state.editIdx] = k; else state.rundown.push(k);
            markDirty();
            renderRundown();
            keluarModeEdit();
        });
        $('#btnBatalEdit').addEventListener('click', keluarModeEdit);
        $('#rdForm').addEventListener('keydown', ev => {
            if (ev.key === 'Enter' && ev.target.tagName === 'INPUT') { ev.preventDefault(); $('#btnSimpanKegiatan').click(); }
        });
        $('#rundownList').addEventListener('click', ev => {
            const btn = ev.target.closest('[data-act]');
            if (!btn) return;
            const i = Number(btn.closest('.run-row').dataset.i);
            const act = btn.dataset.act;
            if (act === 'up' && i > 0) { const t = state.rundown[i]; state.rundown[i] = state.rundown[i - 1]; state.rundown[i - 1] = t; markDirty(); renderRundown(); }
            else if (act === 'down' && i < state.rundown.length - 1) { const t = state.rundown[i]; state.rundown[i] = state.rundown[i + 1]; state.rundown[i + 1] = t; markDirty(); renderRundown(); }
            else if (act === 'hapus') { state.rundown.splice(i, 1); markDirty(); if (state.editIdx === i) keluarModeEdit(); else if (state.editIdx > i) state.editIdx--; renderRundown(); }
            else if (act === 'edit') {
                state.editIdx = i;
                isiFormKegiatan(state.rundown[i]);
                $('#rdTitle').textContent = 'Ubah kegiatan';
                $('#btnSimpanKegiatan').textContent = 'Perbarui kegiatan';
                $('#btnBatalEdit').hidden = false;
            }
        });
        let dragFrom = -1;
        $('#rundownList').addEventListener('dragstart', ev => {
            const r = ev.target.closest && ev.target.closest('.run-row');
            if (!r) return;
            dragFrom = Number(r.dataset.i);
            ev.dataTransfer.effectAllowed = 'move';
            ev.dataTransfer.setData('text/plain', String(dragFrom));
        });
        $('#rundownList').addEventListener('dragover', ev => { if (dragFrom > -1) ev.preventDefault(); });
        $('#rundownList').addEventListener('drop', ev => {
            ev.preventDefault();
            const r = ev.target.closest('.run-row');
            if (!r || dragFrom < 0) return;
            const to = Number(r.dataset.i);
            if (to !== dragFrom) {
                const m = state.rundown.splice(dragFrom, 1)[0];
                state.rundown.splice(to, 0, m);
                state.editIdx = -1; keluarModeEdit();
                markDirty(); renderRundown();
            }
            dragFrom = -1;
        });
        $('#rundownList').addEventListener('dragend', () => { dragFrom = -1; });

        /* ---------------- langkah 4 ---------------- */
        function badge(ok, teks) {
            return ok ? '<span class="badge badge-success"><span class="badge-dot"></span>' + (teks || 'Lengkap') + '</span>'
                : '<span class="badge badge-warning"><span class="badge-dot"></span>Perlu dilengkapi</span>';
        }
        function kartu(judul, badgeHtml, isi, langkah) {
            return '<div class="card"><div class="row-between" style="margin-bottom:12px">' +
                '<h2 class="card-title" style="margin:0">' + judul + '</h2>' +
                '<span class="row" style="gap:8px">' + badgeHtml +
                (langkah ? '<button type="button" class="button ghost-button small-button" data-goto="' + langkah + '">Ubah</button>' : '') +
                '</span></div>' + isi + '</div>';
        }
        function status() {
            const s1 = Object.keys(check1()).length === 0;
            const per = check2();
            const s2 = state.tiket.length > 0 && Object.keys(per).every(id => Object.keys(per[id]).length === 0);
            const s3 = state.rundown.length > 0;
            return { s1, s2, s3, semua: s1 && s2 && s3 };
        }
        function renderReview() {
            const st = status();
            const info = '<dl class="definition">' +
                '<div><dt>NAMA ACARA</dt><dd>' + esc(val('f_judul').trim() || '—') + '</dd></div>' +
                '<div><dt>KATEGORI</dt><dd>' + esc(val('f_kategori')) + '</dd></div>' +
                '<div><dt>WAKTU</dt><dd>' + esc(val('f_mulai') && val('f_selesai') ? fmtRentang(val('f_mulai'), val('f_selesai')) : '—') + '</dd></div>' +
                '<div><dt>LOKASI</dt><dd>' + esc([val('f_tempat').trim(), val('f_kota').trim()].filter(Boolean).join(', ') || '—') + '</dd></div></dl>';

            const ringkas = state.tiket.filter(t => t.nama.trim()).map(t => esc(t.nama.trim()) + ' ' + fmtRupiah(parseInt(digits(t.harga) || '0', 10)) + ' (' + (parseInt(digits(t.kuota) || '0', 10) || 0) + ')');
            const tiketIsi = '<p class="rv-hint">' + (ringkas.length
                ? state.tiket.length + ' kategori · ' + gabung(ringkas) + ' · kapasitas ' + totalKapasitas().toLocaleString('id-ID') + ' kursi'
                : 'Belum ada kategori tiket.') + '</p>';

            const n = state.rundown.length;
            const rundownIsi = '<p class="rv-hint">' + (n ? n + ' kegiatan, dari ' + waktuTampil(state.rundown[0].mulai) + ' sampai ' + waktuTampil(state.rundown[n - 1].selesai) : 'Belum ada kegiatan pada rundown.') + '</p>';

            const rab = '<p class="rv-hint">RAB dapat dilengkapi di tab RAB setelah draf tersimpan.</p>';

            $('#reviewCards').innerHTML =
                kartu('Informasi &amp; jadwal', badge(st.s1), info, 1) +
                kartu('Tiket &amp; kapasitas', badge(st.s2), tiketIsi, 2) +
                kartu('Rundown', badge(st.s3), rundownIsi, 3) +
                kartu('RAB', '<span class="badge badge-neutral"><span class="badge-dot"></span>Opsional</span>', rab, 0);
            setDisabled($('#btnNext'), !st.semua);
        }
        $('#reviewCards').addEventListener('click', ev => {
            const b = ev.target.closest('[data-goto]');
            if (b) go(Number(b.dataset.goto));
        });

        /* ---------------- navigasi ---------------- */
        function renderStepper() {
            $('#stepper').innerHTML = STEPS.map((nama, i) => {
                const n = i + 1;
                const cls = n < state.step ? 'step-done is-link' : (n === state.step ? 'step-active' : '');
                const dot = n < state.step ? CEK : n;
                return '<span class="step-item ' + cls + '" data-n="' + n + '"><span class="step-dot">' + dot + '</span>' + esc(nama) + '</span>';
            }).join('');
        }
        $('#stepper').addEventListener('click', ev => {
            const s = ev.target.closest('.step-item');
            if (s && Number(s.dataset.n) < state.step) go(Number(s.dataset.n));
        });

        function renderSubtitle() {
            const info = state.savedAt ? 'Draf tersimpan ' + state.savedAt
                : (state.step === 4 ? 'Periksa sebelum diajukan' : 'Isi data lalu simpan sebagai draf kapan saja');
            $('#subtitle').textContent = 'Langkah ' + state.step + ' dari 4 · ' + info;
        }

        function go(n) {
            state.step = n;
            $$('[data-step]').forEach(s => { s.hidden = Number(s.dataset.step) !== n; });
            $('#wizard').classList.toggle('is-wide', n === 3);
            $('#btnBatal').hidden = n !== 1;
            $('#btnBack').hidden = n === 1;
            $('#btnNext').textContent = NEXT_LABEL[n];
            flash('');
            renderStepper();
            renderSubtitle();
            if (n === 1) render1();
            if (n === 2) renderTiket();
            if (n === 3) { setDisabled($('#btnNext'), false); renderRundown(); if (!state.rdInit) { isiFormKegiatan(defaultKegiatan()); state.rdInit = true; } }
            if (n === 4) renderReview();
            history.replaceState(null, '', '#' + ['', 'informasi', 'tiket', 'rundown', 'tinjau'][n]);
            window.scrollTo(0, 0);
        }

        $('#btnBack').addEventListener('click', () => go(state.step - 1));
        $('#btnPratinjau').addEventListener('click', () => go(4));
        $('#btnNext').addEventListener('click', () => {
            if (state.saving) return;
            const n = state.step;
            if (n === 1) {
                state.attempted[1] = true;
                const e = render1();
                if (Object.keys(e).length) return;
                go(2);
            } else if (n === 2) {
                state.attempted[2] = true;
                if (!refresh2()) return;
                go(3);
            } else if (n === 3) {
                go(4);
            } else {
                const st = status();
                if (!st.semua) {
                    flash('Lengkapi bagian yang bertanda “Perlu dilengkapi” sebelum mengajukan.');
                    window.scrollTo(0, 0);
                    return;
                }
                kirim('ajukan');
            }
        });
        $('#btnDraf').addEventListener('click', () => {
            if (state.saving) return;
            if (!bisaDraf()) {
                state.attempted[1] = true;
                go(1);
                render1();
                flash('Lengkapi dulu nama acara, deskripsi, waktu, tempat, dan kota agar draf dapat disimpan.');
                return;
            }
            kirim('draf');
        });

        /* ---------------- kirim ke server ---------------- */
        async function kirim(aksi) {
            state.saving = true;
            const nextBtn = $('#btnNext'), drafBtn = $('#btnDraf');
            const labelNext = nextBtn.textContent;
            nextBtn.disabled = drafBtn.disabled = true;
            if (aksi === 'ajukan') nextBtn.textContent = 'Mengajukan…'; else drafBtn.textContent = 'Menyimpan…';
            flash('');
            try {
                const fd = new FormData();
                fd.append('csrf_token', CFG.csrf);
                fd.append('aksi', aksi);
                fd.append('event_id', state.eventId);
                fd.append('judul_event', val('f_judul'));
                fd.append('kategori', val('f_kategori'));
                fd.append('deskripsi', val('f_deskripsi'));
                fd.append('tanggal_mulai', val('f_mulai'));
                fd.append('tanggal_selesai', val('f_selesai'));
                fd.append('nama_tempat', val('f_tempat'));
                fd.append('kota', val('f_kota'));
                fd.append('tiket_json', JSON.stringify(state.tiket.map(t => ({ nama: t.nama, harga: digits(t.harga), kuota: digits(t.kuota), mulai: t.mulai, selesai: t.selesai }))));
                fd.append('rundown_json', JSON.stringify(state.rundown));
                const file = $('#f_poster').files[0];
                if (file && state.posterOk) fd.append('poster', file);

                const res = await fetch(CFG.action, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                let data = null;
                try { data = await res.json(); } catch (_) { data = null; }
                if (!data) throw new Error('Respons server tidak dikenali. Coba muat ulang halaman.');
                if (!data.ok) throw new Error((data.errors && data.errors.length ? data.errors.join(' ') : data.message) || 'Gagal menyimpan.');

                state.eventId = data.event_id;
                state.dirty = false;
                if (aksi === 'ajukan') {
                    state.leaving = true;
                    window.location.href = data.redirect;
                    return;
                }
                { const now = new Date(); state.savedAt = now.getDate() + ' ' + BULAN[now.getMonth()] + ', ' + jam(now); }
                if ($('#f_poster').files[0]) { state.posterOk = false; $('#posterHint').textContent = 'Poster tersimpan.'; }
                renderSubtitle();
                flash('Draf berhasil disimpan.', 'ok');
            } catch (err) {
                flash(err.message || 'Gagal menyimpan.');
                window.scrollTo(0, 0);
            } finally {
                state.saving = false;
                nextBtn.disabled = drafBtn.disabled = false;
                nextBtn.textContent = labelNext;
                drafBtn.textContent = 'Simpan draf';
            }
        }

        /* ---------------- init ---------------- */
        $('#wizard').addEventListener('submit', ev => ev.preventDefault());
        window.addEventListener('beforeunload', ev => {
            if (state.dirty && !state.leaving) { ev.preventDefault(); ev.returnValue = ''; }
        });
        state.tiket.push(tiketBaru('Umum'));
        renderTiket();
        const awal = { informasi: 1, tiket: 2, rundown: 3, tinjau: 4 }[location.hash.replace('#', '')] || 1;
        go(1);
        if (awal > 1) go(awal);
    })();
    </script>
</body>

</html>