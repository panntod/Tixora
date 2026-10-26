<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();

$currentUser = ViewHelper::requireLogin();

$successMsg = '';
$errorMsg = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $nama = trim($_POST['nama'] ?? '');
        $noWhatsapp = trim($_POST['no_whatsapp'] ?? '');
        $institusi = trim($_POST['institusi'] ?? '');

        if (!$nama) {
            $errorMsg = 'Nama lengkap wajib diisi.';
        } else {
            $stmt = $db->prepare("
                UPDATE users 
                SET nama = :nama, no_whatsapp = :no_whatsapp, institusi = :institusi, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $stmt->execute([
                'nama' => $nama,
                'no_whatsapp' => $noWhatsapp,
                'institusi' => $institusi,
                'id' => $currentUser['id']
            ]);
            $_SESSION['user']['nama'] = $nama;
            $successMsg = 'Perubahan profil berhasil disimpan.';
        }
    } elseif ($action === 'change_password') {
        $passwordBaru = $_POST['password_baru'] ?? '';
        $ulangiPassword = $_POST['ulangi_password'] ?? '';

        if (strlen($passwordBaru) < 8) {
            $errorMsg = 'Kata sandi baru minimal 8 karakter.';
        } elseif ($passwordBaru !== $ulangiPassword) {
            $errorMsg = 'Konfirmasi kata sandi baru tidak cocok.';
        } else {
            $hashed = password_hash($passwordBaru, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute(['password' => $hashed, 'id' => $currentUser['id']]);
            $successMsg = 'Kata sandi berhasil diperbarui.';
        }
    }
}

// Ambil data user terkini
$stmt = $db->prepare("SELECT u.*, r.nama as role_nama FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id");
$stmt->execute(['id' => $currentUser['id']]);
$user = $stmt->fetch() ?: $currentUser;
$initials = strtoupper(mb_substr($user['nama'] ?? 'User', 0, 2));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Akun Saya</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame tx-style-c76485f1ad">
        <?php ViewHelper::renderNavbar('akun'); ?>

        <main class="page-body tx-style-e5d5285078">
            <h1 class="page-title tx-style-8677744d08">Akun Saya</h1>

            <div class="two-col">
                <nav class="side-nav stack tx-style-d767683936">
                    <span class="side-link side-link-active">Profil &amp; Keamanan</span>
                    <?php if ($user['role'] === 'USER'): ?>
                        <a href="<?= BASE_URL ?>/tiket-saya" class="side-link" style="text-decoration:none">Tiket Saya</a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/logout" class="side-link" style="text-decoration:none;color:#B91C1C">Keluar</a>
                </nav>

                <div class="stack gap-24 tx-style-88e7a5ed79">
                    <?php if ($successMsg): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
                    <?php endif; ?>
                    <?php if ($errorMsg): ?>
                        <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
                    <?php endif; ?>

                    <!-- Form Profil -->
                    <form method="POST" action="" class="card">
                        <input type="hidden" name="action" value="update_profile">
                        <h2 class="card-title">Profil Pengguna</h2>
                        <div class="row tx-style-49f14f8f83" style="margin-bottom:16px">
                            <span class="avatar avatar-large" style="display:inline-flex;align-items:center;justify-content:center;background:#4B34DB;color:#fff;font-weight:700"><?= $initials ?></span>
                            <div>
                                <b><?= htmlspecialchars($user['nama']) ?></b>
                                <div class="slate" style="font-size:13px"><?= htmlspecialchars($user['email']) ?> · Peran: <?= htmlspecialchars($user['role_nama'] ?? $user['role']) ?></div>
                            </div>
                        </div>

                        <div class="stack gap-16">
                            <div class="two-fields">
                                <div>
                                    <label class="field-label">Nama lengkap</label>
                                    <input type="text" name="nama" class="input" value="<?= htmlspecialchars($user['nama'] ?? '') ?>" required>
                                </div>
                                <div>
                                    <label class="field-label">Surel (Email)</label>
                                    <input type="email" class="input" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly style="background:#F8F8FC">
                                </div>
                            </div>
                            <div class="two-fields">
                                <div>
                                    <label class="field-label">Nomor WhatsApp</label>
                                    <input type="text" name="no_whatsapp" class="input" placeholder="081234567890" value="<?= htmlspecialchars($user['no_whatsapp'] ?? '') ?>">
                                </div>
                                <div>
                                    <label class="field-label">Institusi / Instansi</label>
                                    <input type="text" name="institusi" class="input" placeholder="Politeknik Negeri Malang" value="<?= htmlspecialchars($user['institusi'] ?? '') ?>">
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="button primary-button" style="cursor:pointer">Simpan Perubahan</button>
                            </div>
                        </div>
                    </form>

                    <!-- Form Ganti Kata Sandi -->
                    <form method="POST" action="" class="card">
                        <input type="hidden" name="action" value="change_password">
                        <h2 class="card-title">Ganti Kata Sandi</h2>
                        <div class="two-fields">
                            <div>
                                <label class="field-label">Kata sandi baru</label>
                                <input type="password" name="password_baru" class="input" placeholder="Minimal 8 karakter" required minlength="8">
                            </div>
                            <div>
                                <label class="field-label">Ulangi kata sandi baru</label>
                                <input type="password" name="ulangi_password" class="input" placeholder="Ulangi kata sandi" required minlength="8">
                            </div>
                        </div>
                        <p class="help" style="margin:8px 0 16px">Gunakan kombinasi huruf, angka, dan karakter untuk keamanan optimal.</p>
                        <button type="submit" class="button secondary-button tx-style-1b0f4999d2" style="cursor:pointer">Perbarui Kata Sandi</button>
                    </form>
                </div>
            </div>
        </main>

        <footer class="footer">
            <div class="container tx-style-a3d12b9b83">
                <div>
                    <div class="logo">
                        <img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA">
                    </div>
                    <p class="footer-text">Platform acara dan tiket digital.</p>
                </div>
                <nav class="footer-links">
                    <a href="#">Bantuan</a>
                    <a href="#">Syarat &amp; ketentuan</a>
                    <a href="#">Kebijakan privasi</a>
                </nav>
            </div>
        </footer>
    </div>
</body>

</html>
