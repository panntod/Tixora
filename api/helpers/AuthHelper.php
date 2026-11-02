<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

/**
 * Kelas AuthHelper untuk menangani Inisialisasi Session, 
 * Perlindungan Session Hijacking, Token CSRF, dan RBAC (PHP 8.x+)
 */
class AuthHelper {
    
    /**
     * Inisialisasi session dengan pengamanan ketat terhadap Session Fixation & XSS
     */
    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

            // httponly: cookie tidak terbaca JavaScript (mitigasi XSS)
            // samesite=Lax: cookie tidak ikut request POST lintas-situs (mitigasi CSRF)
            // secure: otomatis aktif bila diakses lewat HTTPS
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $https,
            ]);
            // Strict mode mencegah penempatan session ID fiktif (session fixation)
            ini_set('session.use_strict_mode', '1');

            session_start();
        }
    }

    /**
     * Pastikan akun yang sedang login masih aktif. Dicek di setiap request terproteksi
     * supaya penangguhan oleh Admin langsung berlaku, tidak menunggu sesi berakhir.
     * Mengembalikan false (dan menghapus sesi) jika akun sudah ditangguhkan / dihapus.
     */
    public static function isSessionUserActive(): bool {
        self::initSession();
        if (!isset($_SESSION['user']['id'])) {
            return false;
        }
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT is_active FROM users WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['user']['id']]);
            $row = $stmt->fetch();
        } catch (Throwable $e) {
            error_log('[TIXORA] Cek status akun gagal: ' . $e->getMessage());
            return true; // gangguan sementara DB tidak boleh mengunci semua pengguna
        }
        if (!$row || ($row['is_active'] !== null && !$row['is_active'])) {
            $_SESSION = [];
            return false;
        }
        return true;
    }

    /**
     * Menghasilkan token CSRF unik untuk perlindungan formulir / request state-changing
     */
    public static function generateCsrfToken(): string {
        self::initSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Memverifikasi validitas token CSRF menggunakan hash_equals untuk mencegah timing attack
     */
    public static function verifyCsrfToken(?string $token): bool {
        self::initSession();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
    }

    /**
     * Pengecekan Otorisasi Berbasis Peran (RBAC Middleware)
     * Memastikan hanya role yang diizinkan yang dapat mengakses suatu endpoint/modul.
     */
    public static function checkRole(string $requiredRole): void {
        self::initSession();
        
        // Periksa apakah pengguna sudah login (session user tersedia)
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'Unauthorized: Silakan login terlebih dahulu untuk mengakses sumber daya ini.'
            ]);
            exit;
        }

        if (!self::isSessionUserActive()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Akun Anda tidak aktif atau telah ditangguhkan. Silakan hubungi admin.'
            ]);
            exit;
        }

        // Periksa kesesuaian peran (role) pengguna
        if ($_SESSION['user']['role'] !== $requiredRole) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Forbidden: Akses ditolak. Anda tidak memiliki hak akses ke modul ini.'
            ]);
            exit;
        }
    }

    /**
     * Pengecekan multi-role (jika suatu resource boleh diakses oleh beberapa role, misal: ADMIN atau EVENT_OWNER)
     */
    public static function checkAnyRole(array $allowedRoles): void {
        self::initSession();
        
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'Unauthorized: Silakan login terlebih dahulu.'
            ]);
            exit;
        }

        if (!self::isSessionUserActive()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Akun Anda tidak aktif atau telah ditangguhkan. Silakan hubungi admin.'
            ]);
            exit;
        }

        if (!in_array($_SESSION['user']['role'], $allowedRoles, true)) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Forbidden: Akses ditolak. Role Anda tidak diizinkan mengakses fitur ini.'
            ]);
            exit;
        }
    }
}