<?php
declare(strict_types=1);

/**
 * Kelas Database menggunakan pola Singleton dan PDO
 * untuk koneksi aman ke PostgreSQL (PHP 8.2+)
 */
class Database {
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    private string $host = '127.0.0.1';
    private string $port = '5432';
    private string $dbName = 'db_tixora';
    private string $username = 'postgres';
    private string $password = '';

    private function __construct() {
        // Ambil konfigurasi dari environment jika tersedia (.env)
        $host = getenv('DB_HOST') ?: $this->host;
        $port = getenv('DB_PORT') ?: $this->port;
        $dbName = getenv('DB_DATABASE') ?: $this->dbName;
        $username = getenv('DB_USERNAME') ?: $this->username;
        $password = getenv('DB_PASSWORD') ?: $this->password;

        try {
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";
            
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Mencegah SQL Injection secara ketat[cite: 1, 2]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Koneksi basis data gagal: ' . $e->getMessage()
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