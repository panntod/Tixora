<?php
declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();

$owner = ViewHelper::requireLogin('EVENT_OWNER');

$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judulEvent = trim($_POST['judul_event'] ?? '');
    $kategori = trim($_POST['kategori'] ?? 'Seminar');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kuota = (int)($_POST['kuota'] ?? 100);
    $tanggalMulai = $_POST['tanggal_mulai'] ?? '';
    $tanggalSelesai = $_POST['tanggal_selesai'] ?? '';
    $namaTempat = trim($_POST['nama_tempat'] ?? '');
    $kota = trim($_POST['kota'] ?? 'Malang');
    $statusAksi = trim($_POST['status_aksi'] ?? 'DRAF'); // DRAF atau MENUNGGU_TINJAUAN

    if (!$judulEvent || !$deskripsi || !$tanggalMulai || !$tanggalSelesai || !$namaTempat || !$kota) {
        $errorMsg = 'Seluruh kolom bertanda bintang (*) wajib diisi.';
    } elseif (strtotime($tanggalSelesai) <= strtotime($tanggalMulai)) {
        $errorMsg = 'Waktu selesai harus setelah waktu mulai kegiatan.';
    } elseif ($kuota <= 0) {
        $errorMsg = 'Kuota peserta minimal harus 1 orang.';
    } else {
        try {
            $db->beginTransaction();

            $statusVerifikasi = ($statusAksi === 'MENUNGGU_TINJAUAN') ? 'MENUNGGU_TINJAUAN' : 'DRAF';

            $stmt = $db->prepare("
                INSERT INTO events (
                    owner_id, judul_event, deskripsi, kategori, kuota, 
                    tanggal_mulai, tanggal_selesai, nama_tempat, kota, status_verifikasi
                ) VALUES (
                    :owner_id, :judul_event, :deskripsi, :kategori, :kuota,
                    :tanggal_mulai, :tanggal_selesai, :nama_tempat, :kota, :status_verifikasi
                ) RETURNING id
            ");
            $stmt->execute([
                'owner_id' => $owner['id'],
                'judul_event' => $judulEvent,
                'deskripsi' => $deskripsi,
                'kategori' => $kategori,
                'kuota' => $kuota,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'nama_tempat' => $namaTempat,
                'kota' => $kota,
                'status_verifikasi' => $statusVerifikasi
            ]);
            $newEventId = $stmt->fetchColumn();

            // Buat kategori tiket default: Umum
            $stmtCat = $db->prepare("
                INSERT INTO ticket_categories (event_id, nama_kategori, kuota, wajib_ktm)
                VALUES (:event_id, 'Umum', :kuota, FALSE)
            ");
            $stmtCat->execute([
                'event_id' => $newEventId,
                'kuota' => $kuota
            ]);

            // Catat ke log pengajuan
            $logCatatan = ($statusVerifikasi === 'MENUNGGU_TINJAUAN') 
                ? 'Acara dibuat dan langsung diajukan ke admin kampus.' 
                : 'Draf acara berhasil disimpan.';
            $stmtLog = $db->prepare("
                INSERT INTO event_submission_logs (event_id, status, catatan)
                VALUES (:event_id, :status, :catatan)
            ");
            $stmtLog->execute([
                'event_id' => $newEventId,
                'status' => $statusVerifikasi,
                'catatan' => $logCatatan
            ]);

            $db->commit();

            header('Location: ' . BASE_URL . '/event-owner/acara-saya?created=1');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errorMsg = 'Gagal menyimpan acara: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Buat Acara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;min-height:1100px;height:auto">
        <div class="app">
            <aside class="sidebar">
                <div class="logo">
                    <svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                        <path d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z" fill="#4B34DB" />
                        <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" fill="none" />
                    </svg>
                    <span class="logo-text">tixora</span>
                </div>
                <a href="<?= BASE_URL ?>/event-owner" class="side-item" style="text-decoration:none">Dasbor</a>
                <a href="<?= BASE_URL ?>/event-owner/acara-saya" class="side-item" style="text-decoration:none">Acara saya</a>
                <a href="<?= BASE_URL ?>/event-owner/buat-acara" class="side-item side-item-active" style="text-decoration:none">Buat acara</a>
                <a href="<?= BASE_URL ?>/jelajah-acara" class="side-item" style="text-decoration:none">Jelajah Acara</a>
                <a href="<?= BASE_URL ?>/logout" class="side-item" style="text-decoration:none;color:#B91C1C">Keluar</a>
                <div class="side-user">
                    <b><?= htmlspecialchars($owner['nama']) ?></b>
                    <?= htmlspecialchars($owner['email']) ?>
                </div>
            </aside>

            <main class="main">
                <p class="crumb">Acara saya / Buat acara</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Buat Acara Baru</h1>
                        <p class="page-subtitle">Isi rincian kegiatan untuk dipublikasikan di Tixora</p>
                    </div>
                </div>

                <?php if ($errorMsg): ?>
                    <div class="alert alert-error" style="width:880px;margin-bottom:20px">
                        <?= htmlspecialchars($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" style="width:880px" class="stack gap-24">
                    <div class="card">
                        <h2 class="card-title">Informasi Dasar Acara</h2>
                        <div class="stack gap-16">
                            <div>
                                <label class="field-label">Nama Acara *</label>
                                <input type="text" name="judul_event" class="input" placeholder="Contoh: Workshop Cloud Computing 2026" required value="<?= htmlspecialchars($_POST['judul_event'] ?? '') ?>">
                            </div>
                            <div class="two-fields">
                                <div>
                                    <label class="field-label">Kategori Kegiatan *</label>
                                    <select name="kategori" class="input">
                                        <option value="Seminar" <?= ($_POST['kategori'] ?? '') === 'Seminar' ? 'selected' : '' ?>>Seminar</option>
                                        <option value="Workshop" <?= ($_POST['kategori'] ?? '') === 'Workshop' ? 'selected' : '' ?>>Workshop</option>
                                        <option value="Konferensi" <?= ($_POST['kategori'] ?? '') === 'Konferensi' ? 'selected' : '' ?>>Konferensi</option>
                                        <option value="Kompetisi" <?= ($_POST['kategori'] ?? '') === 'Kompetisi' ? 'selected' : '' ?>>Kompetisi</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="field-label">Penyelenggara</label>
                                    <input type="text" class="input" value="<?= htmlspecialchars($owner['nama']) ?>" readonly style="background:#F8F8FC">
                                </div>
                            </div>
                            <div>
                                <label class="field-label">Deskripsi Lengkap Acara *</label>
                                <textarea name="deskripsi" class="input input-area" style="height:120px;font-family:inherit;padding:12px" placeholder="Jelaskan tujuan, topik pembahasan, dan manfaat mengikuti acara ini..." required><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="field-label">Kapasitas / Kuota Peserta *</label>
                                <input type="number" name="kuota" class="input" min="1" max="10000" placeholder="100" required value="<?= htmlspecialchars((string)($_POST['kuota'] ?? '100')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h2 class="card-title">Waktu &amp; Lokasi Acara</h2>
                        <div class="stack gap-16">
                            <div class="two-fields">
                                <div>
                                    <label class="field-label">Waktu Mulai *</label>
                                    <input type="datetime-local" name="tanggal_mulai" class="input" required value="<?= htmlspecialchars($_POST['tanggal_mulai'] ?? '') ?>">
                                </div>
                                <div>
                                    <label class="field-label">Waktu Selesai *</label>
                                    <input type="datetime-local" name="tanggal_selesai" class="input" required value="<?= htmlspecialchars($_POST['tanggal_selesai'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="two-fields">
                                <div>
                                    <label class="field-label">Nama Tempat / Gedung *</label>
                                    <input type="text" name="nama_tempat" class="input" placeholder="Contoh: Auditorium Gedung AH" required value="<?= htmlspecialchars($_POST['nama_tempat'] ?? '') ?>">
                                </div>
                                <div>
                                    <label class="field-label">Kota *</label>
                                    <input type="text" name="kota" class="input" placeholder="Malang" required value="<?= htmlspecialchars($_POST['kota'] ?? 'Malang') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-footer" style="width:880px;box-sizing:border-box;display:flex;justify-content:space-between;align-items:center">
                        <button type="submit" name="status_aksi" value="DRAF" class="button ghost-button" style="cursor:pointer">
                            Simpan Sebagai Draf
                        </button>
                        <div class="row">
                            <a href="<?= BASE_URL ?>/event-owner/acara-saya" class="button secondary-button" style="text-decoration:none">Batal</a>
                            <button type="submit" name="status_aksi" value="MENUNGGU_TINJAUAN" class="button primary-button" style="cursor:pointer">
                                Ajukan ke Admin untuk Ditinjau
                            </button>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>
</body>

</html>
