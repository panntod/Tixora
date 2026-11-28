<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/EventHelper.php' : __DIR__ . '/../../api/helpers/EventHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);

// Kolom foto profil (migrasi 014) — dibuat otomatis bila belum ada.
try {
    $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS foto_profil VARCHAR(255)');
} catch (Throwable $e) {
    error_log('[TIXORA] ensure foto_profil: ' . $e->getMessage());
}

$successMsg = '';
$errorMsg = '';
$err = ['nama' => '', 'wa' => '', 'foto' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if (!ViewHelper::checkCsrf()) {
        $errorMsg = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
        $action = '';
    }

    if ($action === 'update_profile') {
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $wa = trim((string) ($_POST['no_whatsapp'] ?? ''));
        $digits = (string) preg_replace('/\D/', '', $wa);
        if ($nama === '') {
            $err['nama'] = 'Nama penyelenggara wajib diisi.';
        }
        if ($wa !== '' && (strlen($digits) < 10 || strlen($digits) > 14)) {
            $err['wa'] = 'Nomor tidak lengkap.';
        }
        if (!array_filter($err)) {
            $db->prepare('UPDATE users SET nama = :nama, no_whatsapp = :wa, updated_at = CURRENT_TIMESTAMP WHERE id = :id')
                ->execute(['nama' => $nama, 'wa' => $wa, 'id' => $owner['id']]);
            $_SESSION['user']['nama'] = $nama;
            $owner['nama'] = $nama;
            $successMsg = 'Perubahan berhasil disimpan.';
        }
    } elseif ($action === 'update_photo') {
        $file = $_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE];
        $info = ($file['error'] ?? 1) === UPLOAD_ERR_OK ? @getimagesize((string) $file['tmp_name']) : false;
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $err['foto'] = 'Pilih foto terlebih dahulu.';
        } elseif (($file['error'] ?? 0) === UPLOAD_ERR_INI_SIZE || ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            $err['foto'] = 'Ukuran foto maksimal 2 MB.';
        } elseif (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            $err['foto'] = 'Foto harus berupa gambar JPG, PNG, atau WebP.';
        } else {
            [$path, $upErr] = EventHelper::storePoster($file); // simpan ke assets/uploads/
            if ($path === null) {
                $err['foto'] = $upErr ?: 'Foto gagal diunggah. Silakan coba lagi.';
            } else {
                $stmt = $db->prepare('SELECT foto_profil FROM users WHERE id = :id');
                $stmt->execute(['id' => $owner['id']]);
                $old = (string) $stmt->fetchColumn();
                $db->prepare('UPDATE users SET foto_profil = :f, updated_at = CURRENT_TIMESTAMP WHERE id = :id')
                    ->execute(['f' => $path, 'id' => $owner['id']]);
                EventHelper::deletePoster($old);
                $successMsg = 'Foto profil berhasil diperbarui.';
            }
        }
    } elseif ($action === 'delete_account') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM events WHERE owner_id = :id AND status_verifikasi IN ('DRAF','MENUNGGU_TINJAUAN','DISETUJUI','TERBIT')");
        $stmt->execute(['id' => $owner['id']]);
        $aktif = (int) $stmt->fetchColumn();
        if ($aktif > 0) {
            $errorMsg = "Penyelenggara tidak dapat dihapus karena masih memiliki {$aktif} acara aktif. Hapus draf atau batalkan acara terlebih dahulu (tab Pengaturan pada acara).";
        } else {
            try {
                $stmt = $db->prepare('SELECT foto_profil FROM users WHERE id = :id');
                $stmt->execute(['id' => $owner['id']]);
                $foto = (string) $stmt->fetchColumn();
                $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $owner['id']]);
                EventHelper::deletePoster($foto);
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $p = session_get_cookie_params();
                    setcookie(session_name(), '', [
                        'expires' => time() - 42000, 'path' => $p['path'], 'domain' => $p['domain'],
                        'secure' => $p['secure'], 'httponly' => $p['httponly'], 'samesite' => $p['samesite'] ?? 'Lax',
                    ]);
                }
                session_destroy();
                header('Location: ' . BASE_URL . '/login');
                exit;
            } catch (PDOException $e) {
                error_log('[TIXORA] hapus akun EO: ' . $e->getMessage());
                $errorMsg = 'Penyelenggara tidak dapat dihapus saat ini. Coba lagi nanti.';
            }
        }
    }
}

$stmt = $db->prepare('SELECT nama, email, no_whatsapp, institusi, foto_profil FROM users WHERE id = :id');
$stmt->execute(['id' => $owner['id']]);
$u = $stmt->fetch() ?: ['nama' => (string) ($owner['nama'] ?? ''), 'email' => '', 'no_whatsapp' => '', 'institusi' => '', 'foto_profil' => ''];

// Bila validasi gagal, tampilkan kembali isian pengguna
$formError = $err['nama'] || $err['wa'];
$val = [
    'nama' => $formError ? trim((string) ($_POST['nama'] ?? '')) : (string) $u['nama'],
    'wa' => $formError ? trim((string) ($_POST['no_whatsapp'] ?? '')) : (string) $u['no_whatsapp'],
];
$fotoUrl = EventHelper::posterUrl((string) ($u['foto_profil'] ?? ''));
$inisial = mb_strtoupper(mb_substr(trim((string) $u['nama']) !== '' ? trim((string) $u['nama']) : 'T', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Pengaturan</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame">
        <div class="app">
            <?= EO::sidebar($db, $owner, 'pengaturan') ?>

            <main class="main">
                <div class="head" style="margin-top:24px">
                    <h1 class="page-title">Penyelenggara saya</h1>
                </div>

                <div class="stack gap-24" style="max-width:880px">
                    <?php if ($successMsg): ?><div class="alert alert-success"><?= EO::e($successMsg) ?></div><?php endif; ?>
                    <?php if ($errorMsg): ?><div class="alert alert-error"><?= EO::e($errorMsg) ?></div><?php endif; ?>

                    <div class="card">
                        <h2 class="card-title">Profil</h2>

                        <form method="POST" enctype="multipart/form-data" class="profile-photo-row" id="form-foto">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="action" value="update_photo">
                            <div class="avatar-lg" aria-hidden="true">
                                <?php if ($fotoUrl): ?><img src="<?= EO::e($fotoUrl) ?>" alt=""><?php else: ?><?= EO::e($inisial) ?><?php endif; ?>
                            </div>
                            <input type="file" name="foto" id="foto" accept="image/jpeg,image/png,image/webp" hidden>
                            <button type="button" class="button secondary-button" id="btn-foto" style="cursor:pointer">Ganti foto</button>
                        </form>
                        <?php if ($err['foto']): ?><div class="help help-error" style="margin-top:8px"><?= EO::e($err['foto']) ?></div><?php endif; ?>

                        <form method="POST" novalidate style="margin-top:20px">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="action" value="update_profile">
                            <div class="form-grid-2">
                                <div>
                                    <label class="field-label" for="p-nama">Nama penyelenggara</label>
                                    <input class="input<?= $err['nama'] ? ' input-error' : '' ?>" id="p-nama" name="nama" type="text" maxlength="255" value="<?= EO::e($val['nama']) ?>" style="width:100%" required>
                                    <?php if ($err['nama']): ?><div class="help help-error"><?= EO::e($err['nama']) ?></div><?php endif; ?>
                                </div>
                                <div>
                                    <label class="field-label" for="p-wa">Nomor yang bisa dihubungi</label>
                                    <input class="input<?= $err['wa'] ? ' input-error' : '' ?>" id="p-wa" name="no_whatsapp" type="tel" maxlength="20" value="<?= EO::e($val['wa']) ?>" placeholder="0812-3456-7890" style="width:100%">
                                    <?php if ($err['wa']): ?><div class="help help-error"><?= EO::e($err['wa']) ?></div><?php endif; ?>
                                </div>
                                <div>
                                    <label class="field-label" for="p-email">Email</label>
                                    <input class="input" id="p-email" type="email" value="<?= EO::e($u['email']) ?>" style="width:100%" disabled>
                                    <div class="help">Email dipakai untuk masuk dan tidak dapat diubah.</div>
                                </div>
                            </div>
                            <div style="margin-top:16px"><button type="submit" class="button primary-button" style="cursor:pointer">Simpan perubahan</button></div>
                        </form>
                    </div>

                    <div class="card">
                        <form method="POST" class="row-between" style="margin:0"
                            data-dlg-title="Hapus penyelenggara ini?"
                            data-dlg-text="Akun penyelenggara beserta tiket dan riwayat acaranya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan."
                            data-dlg-confirm="Hapus penyelenggara" data-dlg-tone="danger">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="action" value="delete_account">
                            <div>
                                <h2 class="card-title" style="font-size:16px;margin:0 0 4px">Hapus penyelenggara</h2>
                                <p class="slate" style="font-size:14px;line-height:21px;margin:0">Tiket dan riwayat acara akan hilang permanen.</p>
                            </div>
                            <button type="submit" class="button danger-outline-button" style="cursor:pointer">Hapus penyelenggara</button>
                        </form>
                    </div>
                </div>

                <footer class="eo-footer">
                    <span>Platform acara dan tiket digital.</span>
                    <span class="eo-footer-links">
                        <a href="#">Bantuan</a>
                        <a href="#">Syarat &amp; ketentuan</a>
                        <a href="#">Kebijakan privasi</a>
                    </span>
                </footer>
            </main>
        </div>
    </div>
    <script src="<?= BASE_URL ?>/event-owner/sidebar.js"></script>
    <script>
        (function () {
            var input = document.getElementById('foto');
            var btn = document.getElementById('btn-foto');
            btn.addEventListener('click', function () { input.click(); });
            input.addEventListener('change', function () { if (input.files.length) { document.getElementById('form-foto').submit(); } });
        })();
    </script>
</body>

</html>