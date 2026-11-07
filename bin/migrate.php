<?php
declare(strict_types=1);

/**
 * Runner migrasi TIXORA (CLI).
 * Menjalankan api/migrations/001..004 berurutan, sekali saja per file
 * (dicatat di tabel schema_migrations). 000_reset_dev.sql TIDAK pernah dijalankan.
 *
 * Pemakaian: php bin/migrate.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../api/config/Database.php';

echo "=== TIXORA Database Migration ===\n";

$pdo = Database::getInstance()->getConnection();
echo "[OK] Terhubung ke basis data PostgreSQL.\n";

// Cegah dua container menjalankan migrasi bersamaan.
$pdo->exec('SELECT pg_advisory_lock(727274)');

try {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            filename VARCHAR(255) PRIMARY KEY,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $files = glob(__DIR__ . '/../api/migrations/[0-9][0-9][0-9]_*.sql') ?: [];
    sort($files);

    foreach ($files as $file) {
        $name = basename($file);
        if (str_starts_with($name, '000_')) {
            continue; // reset dev: berbahaya, jangan pernah otomatis
        }

        $done = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE filename = ?');
        $done->execute([$name]);
        if ($done->fetchColumn()) {
            echo "[SKIP] $name sudah diaplikasikan.\n";
            continue;
        }

        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException("Tidak bisa membaca $name");
        }

        $pdo->beginTransaction();
        try {
            $pdo->exec($sql);
            $ins = $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');
            $ins->execute([$name]);
            $pdo->commit();
            echo "[OK] $name diaplikasikan.\n";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException("$name gagal: " . $e->getMessage(), 0, $e);
        }
    }

    echo "=== Semua migrasi berhasil diaplikasikan! ===\n";
} catch (Throwable $e) {
    fwrite(STDERR, '[ERROR] ' . $e->getMessage() . "\n");
    exit(1);
} finally {
    $pdo->exec('SELECT pg_advisory_unlock(727274)');
}
