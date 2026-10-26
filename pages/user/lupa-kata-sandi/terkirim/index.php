<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../../../api/helpers/ViewHelper.php';
ViewHelper::getCurrentUser(); // memastikan session aktif

// Tautan hanya ada saat dijalankan lokal (lihat lupa-kata-sandi/index.php); ditampilkan sekali.
$devLink = $_SESSION['reset_link'] ?? null;
unset($_SESSION['reset_link']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Tautan Atur Ulang Dibuat</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame">
        <?php ViewHelper::renderNavbar('login'); ?>

        <main class="tx-style-29d2027ab1">
            <div class="card stack gap-16">
                <h2 class="card-title tx-style-1169661891">Periksa surel Anda</h2>
                <p class="slate tx-style-5db37930f2">Jika surel tersebut terdaftar, tautan untuk mengatur ulang kata
                    sandi telah dibuat dan berlaku 60 menit.</p>

                <?php if ($devLink): ?>
                    <div class="alert alert-warning" style="word-break:break-all">
                        <b>Mode pengembangan lokal</b> — server email belum dikonfigurasi, jadi tautan ditampilkan di
                        sini:<br>
                        <a href="<?= htmlspecialchars($devLink) ?>" class="link-text"
                            style="color:#4B34DB"><?= htmlspecialchars($devLink) ?></a>
                    </div>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>/login" class="button button-large secondary-button full-width"
                    style="text-align:center">Kembali ke halaman masuk</a>
            </div>
        </main>

        <?php ViewHelper::renderFooter(); ?>
    </div>
</body>

</html>