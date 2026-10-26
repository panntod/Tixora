<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/AuthHelper.php';
require_once __DIR__ . '/PaymentHelper.php';

// Resolusi base path untuk instalasi di root domain maupun subfolder.
if (!defined('BASE_URL')) {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = preg_replace('~/(?:user|admin|event-owner|api)(?:/.*)?$~', '', $script);
    if ($base === $script) {
        $base = rtrim(dirname($script), '/.');
    }
    define('BASE_URL', rtrim($base, '/'));
}


class ViewHelper {
    public static function getDb(): PDO {
        return Database::getInstance()->getConnection();
    }

    public static function getCurrentUser(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }

    public static function requireLogin(?string $role = null): array {
        $user = self::getCurrentUser();
        if (!$user) {
            // Simpan halaman tujuan agar setelah login pengguna kembali ke sini (mis. halaman pendaftaran/pembayaran).
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
            }
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        if ($role !== null && $user['role'] !== $role) {
            if ($user['role'] === 'ADMIN') {
                header('Location: ' . BASE_URL . '/admin');
            } elseif ($user['role'] === 'EVENT_OWNER') {
                header('Location: ' . BASE_URL . '/event-owner');
            } else {
                header('Location: ' . BASE_URL . '/jelajah-acara');
            }
            exit;
        }
        return $user;
    }

    /** Token CSRF untuk form POST. */
    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(AuthHelper::generateCsrfToken()) . '">';
    }

    /** Memeriksa token CSRF pada request POST saat ini. */
    public static function checkCsrf(): bool {
        return AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null);
    }

    /**
     * Hanya izinkan redirect ke path internal aplikasi (mencegah open redirect).
     * Mengembalikan null jika target tidak aman.
     */
    public static function safeRedirectTarget(?string $target): ?string {
        if (!$target || !str_starts_with($target, BASE_URL . '/')) {
            return null;
        }
        // "//host" menjadi URL protokol-relatif saat BASE_URL kosong.
        if (str_starts_with($target, '//')) {
            return null;
        }
        if (str_contains($target, '\\') || preg_match('/[\r\n]/', $target)) {
            return null;
        }
        return $target;
    }

    public static function renderFooter(): void {
        ?>
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
        <?php
    }

    /** True jika aplikasi diakses dari mesin yang sama (pengembangan lokal). */
    public static function isLocalRequest(): bool {
        return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    }

    public static function renderNavbar(string $active = ''): void {
        $user = self::getCurrentUser();
        ?>
        <header class="navbar">
            <div class="container" style="display:flex;align-items:center;justify-content:space-between;height:63px">
                <div style="display:flex;align-items:center;gap:32px">
                    <a href="<?= BASE_URL ?>/" class="logo" style="text-decoration:none">
                        <img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA">
                    </a>
                    <nav class="nav-links">
                        <a href="<?= BASE_URL ?>/jelajah-acara" style="color:<?= $active === 'jelajah' ? '#4B34DB' : '#4B5170' ?>;text-decoration:none">Jelajahi acara</a>
                        <?php if ($user && $user['role'] === 'USER'): ?>
                            <a href="<?= BASE_URL ?>/tiket-saya" style="color:<?= $active === 'tiket' ? '#4B34DB' : '#4B5170' ?>;text-decoration:none">Tiket Saya</a>
                        <?php endif; ?>
                        <?php if ($user && $user['role'] === 'EVENT_OWNER'): ?>
                            <a href="<?= BASE_URL ?>/event-owner" style="color:#4B34DB;text-decoration:none">Panel Event Owner</a>
                        <?php endif; ?>
                        <?php if ($user && $user['role'] === 'ADMIN'): ?>
                            <a href="<?= BASE_URL ?>/admin" style="color:#4B34DB;text-decoration:none">Panel Admin</a>
                        <?php endif; ?>
                    </nav>
                </div>

                <div class="nav-actions" style="display:flex;align-items:center;gap:12px">
                    <?php if ($user): ?>
                        <div class="user-menu" style="display:flex;align-items:center;gap:8px">
                            <span style="font-size:14px;font-weight:700;color:#0E1128"><?= htmlspecialchars($user['nama']) ?></span>
                            <span class="badge badge-purple" style="font-size:11px"><?= htmlspecialchars($user['role']) ?></span>
                            <div class="dropdown-menu">
                                <?php if ($user['role'] === 'USER'): ?>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/akun">Akun Saya</a>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/tiket-saya">Tiket Saya</a>
                                <?php elseif ($user['role'] === 'EVENT_OWNER'): ?>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/event-owner">Dasbor Acara</a>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/event-owner/acara-saya">Acara Saya</a>
                                <?php elseif ($user['role'] === 'ADMIN'): ?>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/admin">Dasbor Admin</a>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/admin/antrean-acara">Antrean Acara</a>
                                <?php endif; ?>
                                <hr style="border:0;border-top:1px solid #E4E4EE;margin:4px 0">
                                <a class="dropdown-item" href="<?= BASE_URL ?>/logout" style="color:#B91C1C">Keluar</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/login" class="button ghost-button">Masuk</a>
                        <a href="<?= BASE_URL ?>/register" class="button primary-button">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <?php
    }
}
