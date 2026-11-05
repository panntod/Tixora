<?php
declare(strict_types=1);

/**
 * TIXORA — Database Migration Runner
 * Menjalankan migrasi SQL secara otomatis berurutan:
 * 1. api/migrations/001_database.sql
 * 2. api/migrations/002_payment.sql
 * 3. api/migrations/003_constraints.sql
 */

echo "=== TIXORA Database Migration ===\n";

require_once __DIR__ . '/../api/config/Database.php';

$maxRetries = 15;
$retryDelay = 2; // detik
$db = null;

for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
    try {
        echo "[INFO] Menghubungkan ke database (percobaan {$attempt}/{$maxRetries})...\n";
        $db = Database::getInstance()->getConnection();
        echo "[OK] Terhubung ke basis data PostgreSQL.\n";
        break;
    } catch (Throwable $e) {
        echo "[WARN] Gagal terhubung: " . $e->getMessage() . "\n";
        if ($attempt < $maxRetries) {
            echo "[INFO] Menunggu {$retryDelay} detik sebelum mencoba lagi...\n";
            sleep($retryDelay);
        } else {
            echo "[ERROR] Batas percobaan habis. Tidak dapat terhubung ke database.\n";
            exit(1);
        }
    }
}

$migrations = [
    '001_database.sql',
    '002_payment.sql',
    '003_constraints.sql',
];

$migrationDir = __DIR__ . '/../api/migrations';

foreach ($migrations as $file) {
    $path = $migrationDir . '/' . $file;
    if (!is_file($path)) {
        echo "[ERROR] File migrasi tidak ditemukan: {$path}\n";
        exit(1);
    }

    echo "[RUN] Menjalankan {$file}...\n";
    $sql = file_get_contents($path);

    try {
        $db->exec($sql);
        echo "[OK] {$file} berhasil dijalankan.\n";
    } catch (PDOException $e) {
        echo "[ERROR] Gagal saat menjalankan {$file}: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "=== Semua migrasi berhasil diaplikasikan! ===\n";
