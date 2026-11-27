<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);

$successMsg = '';
$errorMsg = '';
$profilErr = '';
$sandiErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if (!ViewHelper::checkCsrf()) {
        $errorMsg = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
        $action = '';
    }

    if ($action === 'update_profile') {
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $institusi = trim((string) ($_POST['institusi'] ?? ''));
        $wa = trim((string) ($_POST['no_whatsapp'] ?? ''));
        $digits = preg_replace('/\D/', '', $wa);
        if ($nama === '') {
            $profilErr = 'Nama lengkap wajib diisi.';
        } elseif ($wa !== '' && (strlen($digits) < 10 || strlen($digits) > 14)) {
            $profilErr = 'Nomor WhatsApp tidak lengkap.';
        } else {
            $db->prepare('UPDATE users SET nama = :nama, institusi = :institusi, no_whatsapp = :wa, updated_at = CURRENT_TIMESTAMP WHERE id = :id')
                ->execute(['nama' => $nama, 'institusi' => $institusi, 'wa' => $wa, 'id' => $owner['id']]);
            $_SESSION['user']['nama'] = $nama;
            $owner['nama'] = $nama;
            $successMsg = 'Perubahan profil berhasil disimpan.';
        }
    } elseif ($action === 'change_password') {
        $lama = (string) ($_POST['password_lama'] ?? '');
        $baru = (string) ($_POST['password_baru'] ?? '');
        $ulang = (string) ($_POST['ulangi_password'] ?? '');
        $stmt = $db->prepare('SELECT password FROM users WHERE id = :id');
        $stmt->execute(['id' => $owner['id']]);
        $hash = (string) $stmt->fetchColumn();
        if ($lama === '' || !password_verify($lama, $hash)) {
            $sandiErr = 'Kata sandi lama tidak sesuai.';
        } elseif (strlen($baru) < 8) {
            $sandiErr = 'Kata sandi baru minimal 8 karakter.';
        } elseif ($baru !== $ulang) {
            $sandiErr = 'Konfirmasi kata sandi baru tidak cocok.';
        } else {
            $db->prepare('UPDATE users SET password = :p, updated_at = CURRENT_TIMESTAMP WHERE id = :id')
                ->execute(['p' => password_hash($baru, PASSWORD_BCRYPT), 'id' => $owner['id']]);
            $successMsg = 'Kata sandi berhasil diperbarui.';
        }
    } elseif ($action === 'delete_account') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM events WHERE owner_id = :id AND status_verifikasi IN ('DRAF','MENUNGGU_TINJAUAN','DISETUJUI','TERBIT')");
        $stmt->execute(['id' => $owner['id']]);
        $aktif = (int) $stmt->fetchColumn();
        if ($aktif > 0) {
            $errorMsg = "Akun tidak dapat dihapus karena masih memiliki {$aktif} acara aktif. Hapus draf atau batalkan acara terlebih dahulu (tab Pengaturan pada acara).";
        } else {
            try {
                $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $owner['id']]);
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
                $errorMsg = 'Akun tidak dapat dihapus saat ini. Coba lagi nanti.';
            }
        }
    }
}

$stmt = $db->prepare('SELECT nama, email, no_whatsapp, institusi FROM users WHERE id = :id');
$stmt->execute(['id' => $owner['id']]);
$u = $stmt->fetch() ?: ['nama' => $owner['nama'] ?? '', 'email' => $owner['email'] ?? '', 'no_whatsapp' => '', 'institusi' => ''];
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
                <p class="crumb">Pengaturan</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Pengaturan akun</h1>
                        <p class="page-subtitle">Kelola profil, kata sandi, dan akun Event Owner Anda</p>
                    </div>
                </div>

                <div class="stack gap-24" style="max-width:640px">
                    <?php if ($successMsg): ?><div class="alert alert-success"><?= EO::e($successMsg) ?></div><?php endif; ?>
                    <?php if ($errorMsg): ?><div class="alert alert-error"><?= EO::e($errorMsg) ?></div><?php endif; ?>

                    <div class="card">
                        <h2 class="card-title">Profil</h2>
                        <form method="POST" class="stack gap-16" novalidate>
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="action" value="update_profile">
                            <div>
                                <label class="field-label" for="p-nama">Nama lengkap</label>
                                <input class="input" id="p-nama" name="nama" type="text" maxlength="255" value="<?= EO::e($u['nama']) ?>" style="width:100%" required>
                            </div>
                            <div>
                                <label class="field-label" for="p-inst">Penyelenggara / institusi</label>
                                <input class="input" id="p-inst" name="institusi" type="text" maxlength="255" value="<?= EO::e($u['institusi']) ?>" placeholder="Contoh: HMTI Fakultas Ilmu Komputer" style="width:100%">
                            </div>
                            <div>
                                <label class="field-label" for="p-wa">Nomor WhatsApp</label>
                                <input class="input" id="p-wa" name="no_whatsapp" type="tel" maxlength="20" value="<?= EO::e($u['no_whatsapp']) ?>" placeholder="0812-3456-7890" style="width:100%">
                            </div>
                            <div>
                                <label class="field-label" for="p-email">Email</label>
                                <input class="input" id="p-email" type="email" value="<?= EO::e($u['email']) ?>" style="width:100%" disabled>
                                <div class="help">Email dipakai untuk masuk dan tidak dapat diubah.</div>
                            </div>
                            <?php if ($profilErr): ?><div class="help help-error"><?= EO::e($profilErr) ?></div><?php endif; ?>
                            <div><button type="submit" class="button primary-button" style="cursor:pointer">Simpan perubahan</button></div>
                        </form>
                    </div>

                    <div class="card">
                        <h2 class="card-title">Ganti kata sandi</h2>
                        <form method="POST" class="stack gap-16" novalidate>
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="action" value="change_password">
                            <div>
                                <label class="field-label" for="s-lama">Kata sandi lama</label>
                                <input class="input" id="s-lama" name="password_lama" type="password" autocomplete="current-password" style="width:100%">
                            </div>
                            <div>
                                <label class="field-label" for="s-baru">Kata sandi baru</label>
                                <input class="input" id="s-baru" name="password_baru" type="password" autocomplete="new-password" minlength="8" style="width:100%">
                                <div class="help">Minimal 8 karakter.</div>
                            </div>
                            <div>
                                <label class="field-label" for="s-ulang">Ulangi kata sandi baru</label>
                                <input class="input" id="s-ulang" name="ulangi_password" type="password" autocomplete="new-password" style="width:100%">
                            </div>
                            <?php if ($sandiErr): ?><div class="help help-error"><?= EO::e($sandiErr) ?></div><?php endif; ?>
                            <div><button type="submit" class="button primary-button" style="cursor:pointer">Perbarui kata sandi</button></div>
                        </form>
                    </div>

                    <div class="card" style="box-shadow:inset 0 0 0 1px #F3B6B6;box-sizing:border-box">
                        <h2 class="card-title">Zona berbahaya</h2>
                        <form method="POST" class="row-between" style="margin:0"
                            data-dlg-title="Hapus akun ini?"
                            data-dlg-text="Akun dan seluruh data Anda akan dihapus permanen. Tindakan ini tidak dapat dibatalkan."
                            data-dlg-confirm="Hapus akun" data-dlg-tone="danger">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="action" value="delete_account">
                            <p class="slate" style="font-size:14px;line-height:21px;max-width:380px;margin:0">Menghapus akun menghilangkan profil dan seluruh data Anda secara permanen. Akun dengan acara aktif tidak dapat dihapus.</p>
                            <button type="submit" class="button danger-outline-button" style="cursor:pointer">Hapus akun</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="<?= BASE_URL ?>/event-owner/sidebar.js"></script>
</body>

</html>
