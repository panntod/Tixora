<?php
declare(strict_types=1);

/**
 * TicketLinkHelper — membuat & memverifikasi tautan e-ticket yang dimuat di dalam QR.
 *
 * Isi QR = URL lengkap, mis.:
 *   http://192.168.1.10/Tixora/e-ticket?code=TXR-20261026-7E4E&s=3f9a...
 * Parameter "s" adalah tanda tangan HMAC dari kode tiket. Kode tiket saja pendek
 * (mudah ditebak), jadi tanpa tanda tangan yang benar halaman tidak akan menampilkan data peserta.
 */
class TicketLinkHelper {
    private const SIG_LENGTH = 24;

    private static function secret(): string {
        $env = getenv('TIXORA_QR_SECRET');
        if ($env) {
            return $env;
        }
        $file = __DIR__ . '/../config/qr_secret.key';
        if (is_file($file)) {
            $val = trim((string)file_get_contents($file));
            if ($val !== '') {
                return $val;
            }
        }
        $val = bin2hex(random_bytes(32));
        if (@file_put_contents($file, $val, LOCK_EX) === false) {
            // Folder tidak bisa ditulis: pakai turunan tetap supaya QR tetap valid antar-request.
            return hash('sha256', 'tixora-qr|' . (getenv('DB_PASSWORD') ?: __DIR__));
        }
        return $val;
    }

    public static function sign(string $kodeTiket): string {
        return substr(hash_hmac('sha256', $kodeTiket, self::secret()), 0, self::SIG_LENGTH);
    }

    public static function verify(string $kodeTiket, string $sig): bool {
        return $kodeTiket !== '' && $sig !== '' && hash_equals(self::sign($kodeTiket), $sig);
    }

    /**
     * Alamat dasar aplikasi yang bisa dibuka dari HP.
     * Bisa dipaksa lewat env TIXORA_PUBLIC_URL (mis. https://tixora.polinema.ac.id/Tixora).
     */
    public static function publicBaseUrl(): string {
        $env = getenv('TIXORA_PUBLIC_URL');
        if ($env) {
            return rtrim($env, '/');
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return ($https ? 'https' : 'http') . '://' . $host . BASE_URL;
    }

    public static function url(string $kodeTiket): string {
        return self::publicBaseUrl() . '/e-ticket?code=' . rawurlencode($kodeTiket) . '&s=' . self::sign($kodeTiket);
    }

    /** True jika URL QR memakai host yang tidak bisa dijangkau HP (localhost). */
    public static function isLocalhostUrl(): bool {
        $host = parse_url(self::publicBaseUrl(), PHP_URL_HOST) ?: '';
        return in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true);
    }
}
