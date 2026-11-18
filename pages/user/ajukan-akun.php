<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/OwnerApplicationHelper.php' : __DIR__ . '/../../api/helpers/OwnerApplicationHelper.php';
$db = ViewHelper::getDb();

// Penyelenggara / admin yang sudah login tidak perlu mengajukan akun lagi.
$currentUser = ViewHelper::getCurrentUser();
if ($currentUser) {
    if ($currentUser['role'] === AuthHelper::ROLE_EVENT_OWNER) {
        header('Location: ' . BASE_URL . '/event-owner/buat-acara');
        exit;
    }
    if ($currentUser['role'] === AuthHelper::ROLE_ADMIN) {
        header('Location: ' . BASE_URL . '/admin');
        exit;
    }
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ViewHelper::checkCsrf()) {
        $errorMessage = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (trim((string) ($_POST['website'] ?? '')) !== '') {
        // Honeypot: hanya diisi bot.
        header('Location: ' . BASE_URL . '/');
        exit;
    } else {
        $result = OwnerApplicationHelper::register(
            $db,
            (string) ($_POST['nama_penyelenggara'] ?? ''),
            (string) ($_POST['catatan'] ?? ''),
            (string) ($_POST['kontak'] ?? ''),
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? '')
        );
        if ($result['ok']) {
            // // Langsung login sebagai Event Owner, lalu ke wizard pembuatan acara.
            // session_regenerate_id(true);
            // unset($_SESSION['login_failed'], $_SESSION['login_locked_until'], $_SESSION['after_login'], $_SESSION['tujuan_login']);
            // $_SESSION['user'] = $result['user'];
            header('Location: ' . BASE_URL . '/login?registered=1&tujuan=acara');
            exit;
        }
        $errorMessage = (string) $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TIXORA — Ajukan Akun</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame">
        <?php ViewHelper::renderNavbar('ajukan-akun'); ?>

        <main class="tx-style-29d2027ab1">
            <div class="card stack gap-16">
                
                    <h2 class="card-title tx-style-1169661891">Ajukan Akun</h2>
                    <p class="slate tx-style-5db37930f2">Ajukan akun dan buat event Anda sendiri.</p>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-error tx-style-82cdadaa59">
                            <?= htmlspecialchars($errorMessage) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="stack gap-16" autocomplete="off">
                        <?= ViewHelper::csrfField() ?>

                        <!-- Honeypot anti-bot: disembunyikan dari pengguna sungguhan -->
                        <div style="position:absolute;left:-9999px;height:0;overflow:hidden" aria-hidden="true">
                            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <div>
                            <label class="field-label tx-style-113f846dc6" for="nama_penyelenggara">Nama
                                penyelenggara/komunitas</label>
                            <input type="text" id="nama_penyelenggara" name="nama_penyelenggara" class="input"
                                placeholder="Masukkan nama penyelenggara/komunitas" required minlength="3" maxlength="255"
                                value="<?= htmlspecialchars($_POST['nama_penyelenggara'] ?? '') ?>">
                        </div>

                        <div>
                            <label class="field-label tx-style-113f846dc6" for="catatan">Notes</label>
                            <input type="text" id="catatan" name="catatan" class="input" placeholder="Notes.."
                                maxlength="255" value="<?= htmlspecialchars($_POST['catatan'] ?? '') ?>">
                        </div>

                        <div>
                            <label class="field-label tx-style-113f846dc6" for="kontak">Kontak yang bisa dihubungi</label>
                            <input type="tel" id="kontak" name="kontak" class="input" placeholder="+62" required
                                inputmode="tel" maxlength="25" pattern="[0-9+\-\s]{9,25}"
                                title="Nomor WhatsApp Indonesia, contoh: +62 897-9777-9879"
                                value="<?= htmlspecialchars($_POST['kontak'] ?? '') ?>">
                        </div>

                        <div>
                            <label class="field-label tx-style-113f846dc6" for="email">Surel (untuk masuk)</label>
                            <input type="email" id="email" name="email" class="input" placeholder="nama@email.com"
                                required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>

                        <div>
                            <label class="field-label tx-style-113f846dc6" for="password">Kata sandi</label>
                            <input type="password" id="password" name="password" class="input"
                                placeholder="Minimal 8 karakter" required minlength="8">
                        </div>

                        <button type="submit"
                            class="button button-large primary-button full-width tx-style-e6b96676ce">Ajukan</button>
                    </form>
            </div>
        </main>

        <?php ViewHelper::renderFooter(); ?>
    </div>
</body>

</html>