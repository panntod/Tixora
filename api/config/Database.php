<?php
declare(strict_types=1);

/**
 * Kelas Database menggunakan pola Singleton dan PDO
 * untuk koneksi aman ke PostgreSQL (PHP 8.2+)
 *
 * Urutan sumber konfigurasi (yang pertama ada yang dipakai):
 *   1. Environment variable: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
 *   2. File api/config/database.local.php (TIDAK di-commit, lihat .gitignore;
 *      contoh format ada di database.local.example.php)
 *   3. Nilai bawaan tanpa password (hanya cocok untuk server lokal yang memakai trust auth)
 */
class Database {
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    private function __construct() {
        $local = [];
        $file = __DIR__ . '/database.local.php';
        if (is_file($file)) {
            $loaded = require $file;
            if (is_array($loaded)) {
                $local = $loaded;
            }
        }

        $host     = getenv('DB_HOST')     ?: ($local['host']     ?? '127.0.0.1');
        $port     = getenv('DB_PORT')     ?: ($local['port']     ?? '5432');
        $dbName   = getenv('DB_DATABASE') ?: ($local['database'] ?? 'db_tixora');
        $username = getenv('DB_USERNAME') ?: ($local['username'] ?? 'postgres');
        $password = getenv('DB_PASSWORD');
        if ($password === false || $password === '') {
            $password = (string)($local['password'] ?? '');
        }

        try {
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";

            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Prepared statement asli di sisi server
            ]);
        } catch (PDOException $e) {
            // Detail (host, user, nama DB) hanya ke log server, tidak ke klien.
            error_log('[TIXORA] Koneksi basis data gagal: ' . $e->getMessage());
            http_response_code(500);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode([
                'success' => false,
                'message' => 'Layanan basis data sedang tidak tersedia. Silakan coba lagi nanti.'
            ]);
            exit;
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    // Mencegah kloning instance (Pola Singleton)
    private function __clone() {}

    // Mencegah unserialize instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
