<?php
declare(strict_types=1);

/**
 * Pengiriman email TIXORA lewat SMTP (tanpa Composer) + pembatasan permintaan (rate limit).
 *
 * Konfigurasi lewat environment variable (Back4App: menu Environment Variables, lokal: file .env):
 *   SMTP_HOST          mis. smtp.gmail.com / smtp-relay.brevo.com   (wajib agar email terkirim)
 *   SMTP_PORT          587 (STARTTLS, bawaan) atau 465 (SSL)
 *   SMTP_ENCRYPTION    tls (STARTTLS, bawaan) | ssl | none
 *   SMTP_USERNAME      akun SMTP
 *   SMTP_PASSWORD      kata sandi SMTP / App Password
 *   MAIL_FROM_ADDRESS  alamat pengirim (bawaan: SMTP_USERNAME bila berbentuk email)
 *   MAIL_FROM_NAME     nama pengirim (bawaan: TIXORA)
 */
class MailHelper
{
    private static string $lastError = '';

    // ------------------------------------------------------------------ konfigurasi

    /** @var array<string,string>|null */
    private static ?array $dotenv = null;

    public static function env(string $key, string $default = ''): string
    {
        $v = getenv($key);
        if ($v === false || $v === '') {
            $v = $_ENV[$key] ?? $_SERVER[$key] ?? '';
        }
        $v = is_string($v) ? trim($v) : '';
        if ($v === '') {
            // Menjalankan php langsung (Laragon/terminal) tidak memuat .env otomatis; baca manual.
            $v = self::dotenv()[$key] ?? '';
        }
        return $v === '' ? $default : $v;
    }

    /** @return array<string,string> isi file .env di root project (bila ada). */
    private static function dotenv(): array
    {
        if (self::$dotenv !== null) {
            return self::$dotenv;
        }
        self::$dotenv = [];
        $file = dirname(__DIR__, 2) . '/.env';
        if (is_readable($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                    continue;
                }
                [$k, $val] = explode('=', $line, 2);
                $val = trim($val);
                if (strlen($val) >= 2 && ($val[0] === '"' || $val[0] === "'") && substr($val, -1) === $val[0]) {
                    $val = substr($val, 1, -1);
                }
                self::$dotenv[trim($k)] = $val;
            }
        }
        return self::$dotenv;
    }

    public static function isConfigured(): bool
    {
        return self::env('SMTP_HOST') !== '';
    }

    public static function lastError(): string
    {
        return self::$lastError;
    }

    private static function fromAddress(): string
    {
        $from = self::env('MAIL_FROM_ADDRESS');
        if ($from === '') {
            $user = self::env('SMTP_USERNAME', self::env('SMTP_USER'));
            $from = filter_var($user, FILTER_VALIDATE_EMAIL) ? $user : 'no-reply@tixora.local';
        }
        return $from;
    }

    // ------------------------------------------------------------------ kirim email

    public static function send(string $to, string $toName, string $subject, string $text, string $html = ''): bool
    {
        self::$lastError = '';
        $clean = static fn(string $s): string => trim(str_replace(["\r", "\n"], ' ', $s));
        $to = $clean($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Alamat email tujuan tidak valid.';
            return false;
        }

        $from = self::fromAddress();
        $fromName = $clean(self::env('MAIL_FROM_NAME', 'TIXORA'));
        $domain = substr((string) strrchr($from, '@'), 1) ?: 'tixora.local';

        $headers = [
            'Date: ' . date('r'),
            'From: ' . self::formatAddress($fromName, $from),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
        ];

        if ($html !== '') {
            $boundary = 'tixora_' . bin2hex(random_bytes(12));
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
            $body = "--{$boundary}\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($text)) . "\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($html)) . "\r\n"
                . "--{$boundary}--\r\n";
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: base64';
            $body = chunk_split(base64_encode($text));
        }

        $encodedSubject = self::encodeHeader($clean($subject));

        if (self::isConfigured()) {
            $data = implode("\r\n", array_merge(
                $headers,
                ['To: ' . self::formatAddress($clean($toName), $to), 'Subject: ' . $encodedSubject]
            )) . "\r\n\r\n" . $body;
            $ok = self::smtp($from, $to, $data);
        } else {
            // mail() bawaan PHP sengaja tidak dipakai: ia melapor "sukses" walau email tidak pernah terkirim.
            self::$lastError = 'SMTP belum dikonfigurasi: isi SMTP_HOST, SMTP_USERNAME, dan SMTP_PASSWORD (lihat .env.example).';
            $ok = false;
        }

        if (!$ok) {
            error_log('[TIXORA][mail] Gagal mengirim ke ' . $to . ': ' . self::$lastError);
        }
        return $ok;
    }

    /** Email atur ulang kata sandi (teks + HTML). */
    public static function sendPasswordReset(string $to, string $nama, string $link, int $menit, bool $byAdmin = false): bool
    {
        $h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $intro = $byAdmin
            ? 'Admin TIXORA telah membuat tautan untuk mengatur ulang kata sandi akun Anda.'
            : 'Kami menerima permintaan untuk mengatur ulang kata sandi akun TIXORA Anda.';
        $subject = $byAdmin ? 'Atur ulang kata sandi TIXORA (diminta admin)' : 'Atur ulang kata sandi TIXORA';

        $text = "Halo {$nama},\n\n{$intro}\n\nKlik tautan berikut (berlaku {$menit} menit, hanya bisa dipakai sekali):\n{$link}\n\n"
            . "Jika Anda tidak memintanya, abaikan email ini. Kata sandi Anda tidak akan berubah.\n\nTIXORA";

        $html = '<div style="font-family:Arial,Helvetica,sans-serif;background:#f6f6fb;padding:24px">'
            . '<div style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;color:#0e1128">'
            . '<h2 style="margin:0 0 12px;font-size:20px">Atur ulang kata sandi</h2>'
            . '<p style="margin:0 0 12px;font-size:14px;line-height:22px">Halo ' . $h($nama) . ',</p>'
            . '<p style="margin:0 0 20px;font-size:14px;line-height:22px">' . $h($intro) . '</p>'
            . '<p style="margin:0 0 20px"><a href="' . $h($link) . '" style="display:inline-block;background:#4b34db;color:#ffffff;'
            . 'text-decoration:none;font-weight:700;font-size:14px;padding:12px 22px;border-radius:10px">Atur kata sandi baru</a></p>'
            . '<p style="margin:0 0 12px;font-size:12px;line-height:18px;color:#5f6483">Tautan berlaku ' . $menit . ' menit dan hanya bisa dipakai sekali. '
            . 'Jika tombol tidak berfungsi, salin tautan ini ke browser:<br><span style="word-break:break-all">' . $h($link) . '</span></p>'
            . '<p style="margin:0;font-size:12px;line-height:18px;color:#5f6483">Jika Anda tidak memintanya, abaikan email ini. Kata sandi Anda tidak akan berubah.</p>'
            . '</div></div>';

        return self::send($to, $nama, $subject, $text, $html);
    }

    // ------------------------------------------------------------------ SMTP

    private static function smtp(string $from, string $to, string $data): bool
    {
        $host = self::env('SMTP_HOST');
        $enc = strtolower(self::env('SMTP_ENCRYPTION', 'tls'));
        $port = (int) self::env('SMTP_PORT', $enc === 'ssl' ? '465' : '587');
        $user = self::env('SMTP_USERNAME', self::env('SMTP_USER'));
        $pass = self::env('SMTP_PASSWORD', self::env('SMTP_PASS'));

        $remote = ($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]]);
        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            self::$lastError = "Tidak bisa terhubung ke {$host}:{$port} ({$errstr})";
            return false;
        }
        stream_set_timeout($fp, 20);

        try {
            self::expect($fp, [220]);
            $ehlo = preg_replace('/[^A-Za-z0-9.\-]/', '', (string) ($_SERVER['SERVER_NAME'] ?? 'localhost')) ?: 'localhost';
            self::command($fp, "EHLO {$ehlo}", [250]);

            if ($enc === 'tls') {
                self::command($fp, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Gagal memulai koneksi TLS.');
                }
                self::command($fp, "EHLO {$ehlo}", [250]);
            }

            if ($user !== '') {
                self::command($fp, 'AUTH LOGIN', [334]);
                self::command($fp, base64_encode($user), [334], 'AUTH');
                self::command($fp, base64_encode($pass), [235], 'AUTH');
            }

            self::command($fp, "MAIL FROM:<{$from}>", [250]);
            self::command($fp, "RCPT TO:<{$to}>", [250, 251]);
            self::command($fp, 'DATA', [354]);

            $data = str_replace(["\r\n", "\r"], "\n", $data);
            $data = preg_replace('/^\./m', '..', $data);
            $data = str_replace("\n", "\r\n", (string) $data);
            fwrite($fp, $data . "\r\n.\r\n");
            self::expect($fp, [250]);

            try {
                self::command($fp, 'QUIT', [221]);
            } catch (Throwable $e) {
                // abaikan: email sudah diterima server
            }
            return true;
        } catch (Throwable $e) {
            self::$lastError = $e->getMessage();
            if (str_contains(self::$lastError, '5.7.9') || str_contains(self::$lastError, '5.7.8')) {
                self::$lastError .= ' | PETUNJUK: Gmail menolak kata sandi. Aktifkan verifikasi 2 langkah, buat App Password di '
                    . 'myaccount.google.com/apppasswords, lalu isi SMTP_PASSWORD dengan 16 karakter itu (tanpa spasi).';
            }
            return false;
        } finally {
            if (is_resource($fp)) {
                fclose($fp);
            }
        }
    }

    /** @param resource $fp */
    private static function command($fp, string $cmd, array $okCodes, string $label = ''): void
    {
        fwrite($fp, $cmd . "\r\n");
        // Label menjaga agar kredensial (base64 user/sandi) tidak pernah muncul di pesan error.
        self::expect($fp, $okCodes, $label !== '' ? $label : (str_starts_with($cmd, 'AUTH') ? 'AUTH' : $cmd));
    }

    /** @param resource $fp */
    private static function expect($fp, array $okCodes, string $context = ''): void
    {
        $resp = '';
        while (($line = fgets($fp, 515)) !== false) {
            $resp .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($resp, 0, 3);
        if (!in_array($code, $okCodes, true)) {
            $detail = trim($resp) !== '' ? trim($resp) : 'tidak ada balasan dari server (timeout)';
            throw new RuntimeException('SMTP' . ($context !== '' ? " [{$context}]" : '') . ': ' . $detail);
        }
    }

    // ------------------------------------------------------------------ util header

    private static function encodeHeader(string $s): string
    {
        return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    }

    private static function formatAddress(string $name, string $addr): string
    {
        if ($name === '') {
            return '<' . $addr . '>';
        }
        $n = preg_match('/[^\x20-\x7E]/', $name)
            ? '=?UTF-8?B?' . base64_encode($name) . '?='
            : '"' . addcslashes($name, '"\\') . '"';
        return $n . ' <' . $addr . '>';
    }

    // ------------------------------------------------------------------ rate limit & respons cepat

    public static function clientIp(): string
    {
        $xff = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($xff !== '') {
            $first = trim(explode(',', $xff)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    }

    /**
     * Catat satu percobaan dan kembalikan TRUE bila masih di bawah batas
     * ($max percobaan per $windowMinutes menit untuk identifier + tipe yang sama).
     */
    public static function throttle(PDO $db, string $identifier, string $type, int $max, int $windowMinutes): bool
    {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                identifier VARCHAR(255) NOT NULL,
                attempt_type VARCHAR(50) NOT NULL,
                success BOOLEAN NOT NULL DEFAULT FALSE,
                ip_address VARCHAR(45),
                user_agent VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $count = $db->prepare("SELECT COUNT(*) FROM rate_limits
                WHERE identifier = :id AND attempt_type = :t
                  AND created_at > CURRENT_TIMESTAMP - make_interval(mins => CAST(:m AS integer))");
            $count->execute(['id' => $identifier, 't' => $type, 'm' => (string) $windowMinutes]);
            $n = (int) $count->fetchColumn();

            $db->prepare("INSERT INTO rate_limits (identifier, attempt_type, ip_address, user_agent)
                VALUES (:id, :t, :ip, :ua)")->execute([
                'id' => $identifier,
                't' => $type,
                'ip' => self::clientIp(),
                'ua' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
            return $n < $max;
        } catch (Throwable $e) {
            error_log('[TIXORA][rate_limit] ' . $e->getMessage());
            return true; // jangan memblokir pengguna sah bila tabel bermasalah
        }
    }

    /**
     * Kirim redirect ke browser SEKARANG lalu lanjutkan proses di latar belakang
     * (pengiriman email bisa lambat; juga mencegah perbedaan waktu respons yang membocorkan email terdaftar).
     */
    public static function redirectThenContinue(string $location): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        ignore_user_abort(true);
        @set_time_limit(60);
        header('Location: ' . $location);
        header('Connection: close');
        header('Content-Length: 0');
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        flush();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
}