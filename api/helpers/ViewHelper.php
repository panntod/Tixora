<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/AuthHelper.php';
require_once __DIR__ . '/PaymentHelper.php';
require_once __DIR__ . '/MailHelper.php';

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
        AuthHelper::initSession();
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
        // Akun yang ditangguhkan Admin langsung kehilangan akses, tanpa menunggu sesi habis.
        if (!AuthHelper::isSessionUserActive()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        if ($role !== null && $user['role'] !== $role) {
            if ($user['role'] === AuthHelper::ROLE_ADMIN) {
                header('Location: ' . BASE_URL . '/admin');
            } elseif ($user['role'] === AuthHelper::ROLE_EVENT_OWNER) {
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

    /** Acara dianggap ditutup bila waktu selesainya sudah lewat (sama dengan aturan di halaman detail & pendaftaran). */
    public static function isEventClosed(array $ev): bool {
        return !empty($ev['tanggal_selesai']) && strtotime((string) $ev['tanggal_selesai']) <= time();
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

    /** URL absolut situs (untuk tautan bagikan dan meta Open Graph). */
    public static function absoluteUrl(string $path = ''): string {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        if (!preg_match('/^[A-Za-z0-9.\-]+(:\d+)?$/', $host)) {
            $host = 'localhost';
        }
        return ($https ? 'https' : 'http') . '://' . $host . BASE_URL . $path;
    }

    /** Waktu lokal WIB dari database ke format kalender UTC, mis. "20261130T020000Z". */
    public static function toCalendarUtc(string $local): string {
        try {
            $d = new DateTime($local, new DateTimeZone('Asia/Jakarta'));
        } catch (Throwable $e) {
            return '';
        }
        return $d->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }

    /** Atribut data-* untuk tombol .js-ics (unduh pengingat kalender). */
    public static function calendarAttrs(array $ev, string $url, string $uid): string {
        $h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        return ' data-title="' . $h($ev['judul_event']) . '"'
            . ' data-start="' . $h(self::toCalendarUtc((string) $ev['tanggal_mulai'])) . '"'
            . ' data-end="' . $h(self::toCalendarUtc((string) $ev['tanggal_selesai'])) . '"'
            . ' data-location="' . $h($ev['nama_tempat'] . ', ' . $ev['kota']) . '"'
            . ' data-desc="' . $h('Info dan tiket: ' . $url) . '"'
            . ' data-url="' . $h($url) . '"'
            . ' data-uid="' . $h($uid) . '"';
    }

    /**
     * Popup setelah pemesanan tiket berhasil; tombol utamanya menuju halaman Tiket Saya.
     * Hanya tampil sekali per tab (sessionStorage). Memerlukan assets/event-actions.js.
     */
    public static function renderBookingSuccess(array $ev, string $title): void {
        $h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        ?>
        <div class="modal-backdrop" id="booking-modal" role="dialog" aria-modal="true" aria-labelledby="booking-modal-title" data-key="<?= $h($ev['ticket_id']) ?>">
            <div class="modal-card">
                <div class="success-icon"><svg width="28" height="28" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                    </svg></div>
                <h2 class="modal-title" id="booking-modal-title"><?= $h($title) ?></h2>
                <p class="modal-text"><b><?= $h($ev['judul_event']) ?></b> &middot; <?= $h($ev['jenis_tiket']) ?></p>
                <p class="modal-text slate">E-ticket Anda tersimpan di halaman Tiket Saya.</p>
                <p class="modal-hint">Ketuk di mana saja untuk menutup</p>
            </div>
        </div>
        <script src="<?= BASE_URL ?>/assets/event-actions.js"></script>
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
                    <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Buka menu" aria-expanded="false" aria-controls="nav-links">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                    <a href="<?= BASE_URL ?>/" class="logo" style="text-decoration:none">
                        <img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA">
                    </a>
                    <nav class="nav-links" id="nav-links" aria-label="Menu utama">
                        <div class="nav-drawer-head">
                            <img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA">
                        </div>
                        <a href="<?= BASE_URL ?>/jelajah-acara" class="<?= $active === 'jelajah' ? 'is-active' : '' ?>" style="color:<?= $active === 'jelajah' ? '#4B34DB' : '#4B5170' ?>;text-decoration:none">Jelajahi acara</a>
                        <?php if ($user && $user['role'] === AuthHelper::ROLE_USER): ?>
                            <a href="<?= BASE_URL ?>/tiket-saya" class="<?= $active === 'tiket' ? 'is-active' : '' ?>" style="color:<?= $active === 'tiket' ? '#4B34DB' : '#4B5170' ?>;text-decoration:none">Tiket Saya</a>
                            <a href="<?= BASE_URL ?>/akun" class="nav-drawer-only <?= $active === 'akun' ? 'is-active' : '' ?>" style="text-decoration:none">Akun Saya</a>
                        <?php endif; ?>
                        <?php if ($user && $user['role'] === AuthHelper::ROLE_EVENT_OWNER): ?>
                            <a href="<?= BASE_URL ?>/event-owner" style="color:#4B34DB;text-decoration:none">Panel Event Owner</a>
                        <?php endif; ?>
                        <?php if ($user && $user['role'] === AuthHelper::ROLE_ADMIN): ?>
                            <a href="<?= BASE_URL ?>/admin" style="color:#4B34DB;text-decoration:none">Panel Admin</a>
                        <?php endif; ?>
                        <?php if ($user): ?>
                            <a href="<?= BASE_URL ?>/logout" class="nav-drawer-only nav-logout" style="text-decoration:none">Keluar</a>
                            <div class="nav-drawer-only nav-drawer-user">
                                <b><?= htmlspecialchars((string) ($user['nama'] ?? '')) ?></b>
                                <?= htmlspecialchars((string) ($user['email'] ?? '')) ?>
                            </div>
                        <?php endif; ?>
                    </nav>
                </div>

                <div class="nav-actions<?= $user ? ' nav-actions-auth' : '' ?>" style="display:flex;align-items:center;gap:12px">
                    <?php if ($user): ?>
                        <?php $initials = strtoupper(mb_substr($user['nama'] ?? 'User', 0, 2)); ?>
                        <div class="user-menu" style="display:flex;align-items:center;gap:12px">
                            <span class="avatar" title="<?= htmlspecialchars($user['nama']) ?>"><?= htmlspecialchars($initials) ?></span>
                            <a href="<?= BASE_URL ?>/logout" class="button ghost-button small-button" style="text-decoration:none">Keluar</a>
                            <div class="dropdown-menu">
                                <?php if ($user['role'] === AuthHelper::ROLE_USER): ?>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/akun">Akun Saya</a>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/tiket-saya">Tiket Saya</a>
                                <?php elseif ($user['role'] === AuthHelper::ROLE_EVENT_OWNER): ?>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/event-owner">Dasbor Acara</a>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/event-owner/acara-saya">Acara Saya</a>
                                <?php elseif ($user['role'] === AuthHelper::ROLE_ADMIN): ?>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/admin">Dasbor Admin</a>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/admin/antrean-acara">Antrean Acara</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/login" class="button ghost-button">Masuk</a>
                        <a href="<?= BASE_URL ?>/register" class="button primary-button">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <div class="nav-backdrop" id="nav-backdrop"></div>
        <script>
        (function () {
            var btn = document.getElementById('nav-toggle');
            var menu = document.getElementById('nav-links');
            var backdrop = document.getElementById('nav-backdrop');
            if (!btn || !menu) return;
            function setOpen(open) {
                document.body.classList.toggle('nav-open', open);
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
            btn.addEventListener('click', function () { setOpen(true); });
            if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
            menu.addEventListener('click', function (e) { if (e.target.closest && e.target.closest('a')) setOpen(false); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
            window.addEventListener('resize', function () { if (window.innerWidth > 768) setOpen(false); });
        })();
        </script>
        <?php
    }
}