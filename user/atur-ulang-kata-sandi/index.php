<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();
PaymentHelper::ensureSchema($db);

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$tokenHash = hash('sha256', $token);
$errorMessage = '';

/** Ambil data token yang masih berlaku (belum dipakai, belum kedaluwarsa). */
function findValidReset(PDO $db, string $tokenHash, bool $lock = false): ?array
{
    $stmt = $db->prepare("
        SELECT pr.id, pr.user_id
        FROM password_resets pr
        WHERE pr.token_hash = :hash AND pr.used_at IS NULL AND pr.expires_at > CURRENT_TIMESTAMP"
        . ($lock ? ' FOR UPDATE' : ''));
    $stmt->execute(['hash' => $tokenHash]);
    return $stmt->fetch() ?: null;
}

$valid = $token !== '' && strlen($token) === 64 && findValidReset($db, $tokenHash) !== null;

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (!ViewHelper::checkCsrf()) {
        $errorMessage = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (strlen($password) < 8) {
        $errorMessage = 'Kata sandi minimal 8 karakter.';
    } elseif ($password !== $confirm) {
        $errorMessage = 'Konfirmasi kata sandi tidak sama.';
    } else {
        $db->beginTransaction();
        $reset = findValidReset($db, $tokenHash, true);
        if (!$reset) {
            $db->rollBack();
            $valid = false;
        } else {
            $db->prepare("UPDATE users SET password = :pw, updated_at = CURRENT_TIMESTAMP WHERE id = :id")
               ->execute(['pw' => password_hash($password, PASSWORD_BCRYPT), 'id' => $reset['user_id']]);
            $db->prepare("UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE id = :id")
               ->execute(['id' => $reset['id']]);
            $db->commit();

            header('Location: ' . BASE_URL . '/login?reset=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Atur Ulang Kata Sandi</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame">
        <?php ViewHelper::renderNavbar('login'); ?>

        <main class="tx-style-29d2027ab1">
            <div class="card stack gap-16">
                <?php if (!$valid): ?>
                    <h2 class="card-title tx-style-1169661891">Tautan tidak berlaku</h2>
                    <div class="alert alert-error">Tautan atur ulang ini tidak valid, sudah dipakai, atau sudah kedaluwarsa.</div>
                    <a href="<?= BASE_URL ?>/lupa-kata-sandi" class="button button-large primary-button full-width" style="text-align:center">Minta tautan baru</a>
                <?php else: ?>
                    <h2 class="card-title tx-style-1169661891">Atur kata sandi baru</h2>
                    <p class="slate tx-style-5db37930f2">Pilih kata sandi baru untuk akun Anda.</p>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" class="stack gap-16">
                        <?= ViewHelper::csrfField() ?>
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <div>
                            <label class="field-label">Kata sandi baru</label>
                            <input type="password" name="password" class="input" placeholder="Minimal 8 karakter" required minlength="8">
                        </div>
                        <div>
                            <label class="field-label">Ulangi kata sandi baru</label>
                            <input type="password" name="password_confirm" class="input" placeholder="Ketik ulang kata sandi" required minlength="8">
                        </div>
                        <button type="submit" class="button button-large primary-button full-width">Simpan kata sandi</button>
                    </form>
                <?php endif; ?>
            </div>
        </main>

        <?php ViewHelper::renderFooter(); ?>
    </div>
</body>

</html>
