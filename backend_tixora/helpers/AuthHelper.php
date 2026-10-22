<?php
declare(strict_types=1);

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
            // Mencegah akses cookie session via JavaScript (XSS protection)
            ini_set('session.cookie_httponly', '1');
            // Menerapkan strict mode untuk mencegah penempatan session ID fiktif
            ini_set('session.use_strict_mode', '1');
            // Atur cookie secure jika sudah menggunakan HTTPS (ubah ke '1' di production)
            ini_set('session.cookie_secure', '0'); 
            
            session_start();
        }
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