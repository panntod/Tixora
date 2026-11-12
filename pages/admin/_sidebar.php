<?php
/**
 * Shared sidebar untuk semua halaman admin.
 *
 * Variabel yang harus ada di scope pemanggil:
 *   - $admin        (array) — dari ViewHelper::requireLogin(AuthHelper::ROLE_ADMIN)
 *   - $activePage   (string) — 'dashboard' | 'antrean' | 'pengguna' | 'penyelenggara' | 'laporan'
 *   - $menungguCount (int) — jumlah event MENUNGGU_TINJAUAN (untuk badge notifikasi)
 */
declare(strict_types=1);

$activePage = $activePage ?? 'dashboard';
$menungguCount = (int)($menungguCount ?? 0);
?>
<aside class="sidebar">
    <div class="logo">
        <img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA">
    </div>
    <span class="side-label">ADMIN</span>

    <a class="side-item <?= $activePage === 'dashboard' ? 'side-item-active' : '' ?>"
       href="<?= BASE_URL ?>/admin">Dasbor</a>

    <a class="side-item <?= $activePage === 'antrean' ? 'side-item-active' : '' ?>"
       href="<?= BASE_URL ?>/admin/antrean-acara">
        Antrean acara<span class="count-pill"><?= $menungguCount ?></span>
    </a>

    <a class="side-item <?= $activePage === 'pengguna' ? 'side-item-active' : '' ?>"
       href="<?= BASE_URL ?>/admin/pengguna">Pengguna</a>

    <a class="side-item <?= $activePage === 'penyelenggara' ? 'side-item-active' : '' ?>"
       href="<?= BASE_URL ?>/admin/penyelenggara">Penyelenggara</a>

    <a class="side-item <?= $activePage === 'laporan' ? 'side-item-active' : '' ?>"
       href="<?= BASE_URL ?>/admin/laporan">Laporan</a>

    <a class="side-item" href="<?= BASE_URL ?>/jelajah-acara">Jelajah Acara</a>

    <a class="side-item" href="<?= BASE_URL ?>/logout" style="color:#B91C1C">Keluar</a>

    <div class="side-user">
        <b><?= htmlspecialchars($admin['nama'] ?? '') ?></b>
        Administrator
    </div>
</aside>