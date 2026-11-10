<?php
declare(strict_types=1);

/**
 * TicketLinkHelper — membuat & memverifikasi tautan e-ticket yang dimuat di dalam QR.
 *
 * Isi QR = URL lengkap, mis.:
 *   https://tixora.example.id/e-ticket?code=TXR-20261026-7E4E&s=3f9a...
 * Parameter "s" adalah tanda tangan HMAC dari kode tiket.
 *
 * Di production (APP_ENV=production ATAU di belakang proxy HTTPS):
 *   - TIXORA_QR_SECRET WAJIB diset (min 32 karakter). Tidak ada fallback.
 *   - TIXORA_PUBLIC_URL WAJIB diset. Tidak ada fallback ke HTTP_HOST.
 * Di development lokal, secret boleh dari file, public URL boleh dari HTTP_HOST.
 */
class TicketLinkHelper
{
    private const SIG_LENGTH = 24;
    private static ?string $cachedSecret = null;

    private static function isProduction(): bool
    {
        if (getenv('APP_ENV') === 'production') {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return true;
        }
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }

    private static function secret(): string
    {
        if (self::$cachedSecret !== null) {
            return self::$cachedSecret;
        }

        // 1. Environment (wajib untuk production)
        $env = getenv('TIXORA_QR_SECRET');
        if ($env !== false && strlen($env) >= 32) {
            return self::$cachedSecret = $env;
        }

        // 2. File lokal (dev only)
        $file = __DIR__ . '/../config/qr_secret.key';
        if (is_file($file)) {
            $val = trim((string) file_get_contents($file));
            if ($val !== '' && strlen($val) >= 32) {
                return self::$cachedSecret = $val;
            }
        }

        // 3. Production: FAIL HARD, tidak ada fallback
        if (self::isProduction()) {
            error_log('[TIXORA] FATAL: TIXORA_QR_SECRET wajib diset di production (min 32 karakter).');
            http_response_code(500);
            throw new RuntimeException('Konfigurasi keamanan QR belum siap. Hubungi administrator.');
        }

        // 4. Dev lokal: generate + simpan file
        $val = bin2hex(random_bytes(32));
        if (@file_put_contents($file, $val, LOCK_EX) !== false) {
            return self::$cachedSecret = $val;
        }
        // Folder tidak writable — pakai ephemeral per-process (dev only)
        return self::$cachedSecret = $val;
    }

    public static function sign(string $kodeTiket): string
    {
        return substr(hash_hmac('sha256', $kodeTiket, self::secret()), 0, self::SIG_LENGTH);
    }

    public static function verify(string $kodeTiket, string $sig): bool
    {
        return $kodeTiket !== '' && $sig !== '' && hash_equals(self::sign($kodeTiket), $sig);
    }

    public static function publicBaseUrl(): string
    {
        // 1. Environment (wajib untuk production)
        $env = getenv('TIXORA_PUBLIC_URL');
        if ($env !== false && $env !== '') {
            return rtrim($env, '/');
        }

        // 2. Production: FAIL HARD
        if (self::isProduction()) {
            error_log('[TIXORA] FATAL: TIXORA_PUBLIC_URL wajib diset di production untuk QR e-ticket.');
            http_response_code(500);
            throw new RuntimeException('Konfigurasi URL publik belum siap.');
        }

        // 3. Dev lokal
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return ($https ? 'https' : 'http') . '://' . $host . (defined('BASE_URL') ? BASE_URL : '');
    }

    public static function url(string $kodeTiket): string
    {
        return self::publicBaseUrl() . '/e-ticket?code=' . rawurlencode($kodeTiket) . '&s=' . self::sign($kodeTiket);
    }
}
