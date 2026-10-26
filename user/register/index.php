<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $roleKode = strtoupper(trim($_POST['role_kode'] ?? 'USER'));

    // Hanya peran publik yang boleh dipilih sendiri; ADMIN tidak pernah bisa didaftarkan dari form ini.
    if (!in_array($roleKode, ['USER', 'EVENT_OWNER'], true)) {
        $roleKode = 'USER';
    }

    if (!ViewHelper::checkCsrf()) {
        $errorMessage = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (!$nama || !$email || strlen($password) < 8) {
        $errorMessage = 'Nama, surel yang valid, dan kata sandi minimal 8 karakter wajib diisi.';
    } else {
        // Cek keberadaan role
        $stmtRole = $db->prepare("SELECT id FROM roles WHERE kode = :kode");
        $stmtRole->execute(['kode' => $roleKode]);
        $role = $stmtRole->fetch();

        if (!$role) {
            $errorMessage = 'Pilihan peran tidak valid.';
        } else {
            // Cek duplikasi email
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmtCheck->execute(['email' => $email]);
            if ($stmtCheck->fetch()) {
                $errorMessage = 'Surel tersebut sudah terdaftar di sistem. Silakan masuk.';
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmtInsert = $db->prepare("
                    INSERT INTO users (nama, email, password, role_id) 
                    VALUES (:nama, :email, :password, :role_id)
                ");
                $stmtInsert->execute([
                    'nama' => $nama,
                    'email' => $email,
                    'password' => $hashed,
                    'role_id' => $role['id']
                ]);

                header('Location: ' . BASE_URL . '/login?registered=1');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Buat Akun</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame">
        <?php ViewHelper::renderNavbar('register'); ?>

        <main class="tx-style-29d2027ab1">
            <div class="card stack gap-16">
                <h2 class="card-title tx-style-1169661891">Buat akun</h2>
                <p class="slate tx-style-5db37930f2">Daftar sekarang untuk memesan tiket atau mengelola acara.</p>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-error tx-style-82cdadaa59">
                        <?= htmlspecialchars($errorMessage) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="stack gap-16">
                    <?= ViewHelper::csrfField() ?>
                    <div>
                        <label class="field-label tx-style-113f846dc6">Nama lengkap</label>
                        <input type="text" name="nama" class="input" placeholder="Nama lengkap Anda" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="field-label tx-style-113f846dc6">Surel</label>
                        <input type="email" name="email" class="input" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="field-label tx-style-113f846dc6">Kata sandi</label>
                        <input type="password" name="password" class="input" placeholder="Minimal 8 karakter" required minlength="8">
                        <p class="help tx-style-0a3b2a9f9b">Minimal 8 karakter.</p>
                    </div>

                    <div>
                        <label class="field-label tx-style-113f846dc6">Daftar sebagai</label>
                        <select name="role_kode" class="input tx-style-0893029504">
                            <option value="USER" <?= (($_POST['role_kode'] ?? '') === 'USER') ? 'selected' : '' ?>>Peserta / Mahasiswa</option>
                            <option value="EVENT_OWNER" <?= (($_POST['role_kode'] ?? '') === 'EVENT_OWNER') ? 'selected' : '' ?>>Penyelenggara Acara (Event Owner)</option>
                        </select>
                    </div>

                    <div class="check-row tx-style-1dcd6d3af3">
                        <input type="checkbox" required checked id="terms">
                        <label for="terms">Saya menyetujui Syarat &amp; ketentuan dan Kebijakan privasi.</label>
                    </div>

                    <button type="submit" class="button button-large primary-button full-width tx-style-e6b96676ce">Daftar Akun</button>
                </form>

                <p class="slate tx-style-94cf2aa250">
                    Sudah punya akun? <a href="<?= BASE_URL ?>/login" class="link-text" style="color:#4B34DB;font-weight:700">Masuk</a>
                </p>
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
