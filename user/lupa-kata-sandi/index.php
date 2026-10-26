<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();
PaymentHelper::ensureSchema($db);

if (ViewHelper::getCurrentUser()) {
    header('Location: ' . BASE_URL . '/akun');
    exit;
}

const RESET_TOKEN_MINUTES = 60;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (!ViewHelper::checkCsrf()) {
        $errorMessage = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (!$email) {
        $errorMessage = 'Format surel tidak valid. Contoh: nama@email.com';
    } else {
        $stmt = $db->prepare("SELECT id, nama FROM users WHERE email = :email AND is_active = TRUE");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        $_SESSION['reset_link'] = null;
        if ($user) {
            $token = bin2hex(random_bytes(32));

            $db->beginTransaction();
            // Token lama yang belum dipakai dinonaktifkan; hanya tautan terbaru yang berlaku.
            $db->prepare("UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE user_id = :uid AND used_at IS NULL")
               ->execute(['uid' => $user['id']]);
            $db->prepare("
                INSERT INTO password_resets (user_id, token_hash, expires_at)
                VALUES (:uid, :hash, CURRENT_TIMESTAMP + make_interval(mins => CAST(:menit AS integer)))
            ")->execute([
                'uid' => $user['id'],
                'hash' => hash('sha256', $token),
                'menit' => (string) RESET_TOKEN_MINUTES,
            ]);
            $db->commit();

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $link = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL . '/atur-ulang-kata-sandi?token=' . $token;

            if (ViewHelper::isLocalRequest()) {
                // Mode pengembangan lokal: belum ada server email, tautan ditampilkan di halaman berikutnya.
                $_SESSION['reset_link'] = $link;
            } else {
                // Produksi: kirim lewat mail() (butuh konfigurasi SMTP/sendmail di server).
                @mail(
                    $email,
                    'Atur ulang kata sandi TIXORA',
                    "Halo {$user['nama']},\n\nKlik tautan berikut untuk mengatur ulang kata sandi (berlaku " . RESET_TOKEN_MINUTES . " menit):\n{$link}\n\nAbaikan email ini jika Anda tidak memintanya.",
                    'Content-Type: text/plain; charset=UTF-8'
                );
            }
        }

        // Respons selalu sama agar tidak membocorkan surel mana yang terdaftar.
        header('Location: ' . BASE_URL . '/lupa-kata-sandi/terkirim');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Lupa Kata Sandi</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame">
        <?php ViewHelper::renderNavbar('login'); ?>

        <main class="tx-style-29d2027ab1">
            <div class="card stack gap-16">
                <h2 class="card-title tx-style-1169661891">Lupa kata sandi</h2>
                <p class="slate tx-style-5db37930f2">Masukkan surel akun Anda. Kami akan membuat tautan untuk mengatur ulang kata sandi.</p>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>

                <form method="POST" action="" class="stack gap-16">
                    <?= ViewHelper::csrfField() ?>
                    <div>
                        <label class="field-label">Surel</label>
                        <input type="email" name="email" class="input" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <button type="submit" class="button button-large primary-button full-width">Kirim tautan atur ulang</button>
                </form>

                <p class="slate tx-style-94cf2aa250">
                    <a href="<?= BASE_URL ?>/login" class="link-text" style="color:#4B34DB;font-weight:700">← Kembali ke halaman masuk</a>
                </p>
            </div>
        </main>

        <?php ViewHelper::renderFooter(); ?>
    </div>
</body>

</html>
