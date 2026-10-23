<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();

$currentUser = ViewHelper::getCurrentUser();
if ($currentUser) {
    if ($currentUser['role'] === 'ADMIN') {
        header('Location: ' . BASE_URL . '/admin');
    } elseif ($currentUser['role'] === 'EVENT_OWNER') {
        header('Location: ' . BASE_URL . '/event-owner');
    } else {
        header('Location: ' . BASE_URL . '/jelajah-acara');
    }
    exit;
}

$errorMessage = '';
$successMessage = '';

if (isset($_GET['registered'])) {
    $successMessage = 'Registrasi berhasil! Silakan masuk menggunakan akun Anda.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $errorMessage = 'Surel dan kata sandi wajib diisi.';
    } else {
        $stmt = $db->prepare("
            SELECT u.*, r.kode as role_kode 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.email = :email
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errorMessage = 'Surel atau kata sandi salah. Coba lagi.';
        } elseif (isset($user['is_active']) && !$user['is_active']) {
            $errorMessage = 'Akun Anda ditangguhkan. Alasan: ' . ($user['alasan_suspensi'] ?? 'Kebijakan Admin.');
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'nama' => $user['nama'],
                'email' => $user['email'],
                'role' => $user['role_kode']
            ];

            if ($user['role_kode'] === 'ADMIN') {
                header('Location: ' . BASE_URL . '/admin');
            } elseif ($user['role_kode'] === 'EVENT_OWNER') {
                header('Location: ' . BASE_URL . '/event-owner');
            } else {
                header('Location: ' . BASE_URL . '/jelajah-acara');
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Masuk</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame">
        <?php ViewHelper::renderNavbar('login'); ?>

        <main class="tx-style-29d2027ab1">
            <div class="card stack gap-16">
                <h2 class="card-title tx-style-1169661891">Masuk</h2>
                <p class="slate tx-style-5db37930f2">Akses tiket dan riwayat pendaftaran Anda.</p>

                <?php if ($successMessage): ?>
                    <div class="alert alert-success tx-style-602d1a6aa5">
                        <?= htmlspecialchars($successMessage) ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-error tx-style-82cdadaa59">
                        <?= htmlspecialchars($errorMessage) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="stack gap-16">
                    <div>
                        <label class="field-label tx-style-113f846dc6">Surel</label>
                        <input type="email" name="email" class="input" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="field-label tx-style-113f846dc6">Kata sandi</label>
                        <input type="password" name="password" class="input" placeholder="••••••••" required>
                    </div>

                    <div class="tx-style-b5c9733a73">
                        <a href="<?= BASE_URL ?>/lupa-kata-sandi" class="link-text" style="font-size:13px;color:#4B34DB">Lupa kata sandi?</a>
                    </div>

                    <button type="submit" class="button button-large primary-button full-width tx-style-e6b96676ce">Masuk</button>
                </form>

                <p class="slate tx-style-94cf2aa250">
                    Belum punya akun? <a href="<?= BASE_URL ?>/register" class="link-text" style="color:#4B34DB;font-weight:700">Daftar</a>
                </p>

                <!-- Quick Login Demo for testing -->
                <div class="tx-style-7acd776f1c">
                    <p class="tx-style-e6c80ed621">AKUN DEMO (Password: password123):</p>
                    <div class="tx-style-6157e89086">
                        <button type="button" onclick="setLogin('alyarahma@gmail.com', 'password123')" class="button secondary-button tx-style-28f46b903f">Peserta (Alya)</button>
                        <button type="button" onclick="setLogin('hmti@polinema.ac.id', 'password123')" class="button secondary-button tx-style-28f46b903f">Event Owner (HMTI)</button>
                        <button type="button" onclick="setLogin('admin@tixora.ac.id', 'password123')" class="button secondary-button tx-style-28f46b903f">Admin (Budi)</button>
                    </div>
                </div>
            </div>
        </main>

        <footer class="footer">
            <div class="container tx-style-a3d12b9b83">
                <div>
                    <div class="logo">
                        <svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                            <path d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z" fill="#4B34DB" />
                            <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" fill="none" />
                        </svg>
                        <span class="logo-text">tixora</span>
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

    <script>
    function setLogin(email, pass) {
        document.querySelector('input[name="email"]').value = email;
        document.querySelector('input[name="password"]').value = pass;
    }
    </script>
</body>

</html>
