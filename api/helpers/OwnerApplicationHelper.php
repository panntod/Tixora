<?php
declare(strict_types=1);

require_once __DIR__ . '/AuthHelper.php';

/**
 * Pendaftaran Event Owner sederhana (tanpa persetujuan Admin).
 * Form "Ajukan Akun" langsung membuat user ber-role EVENT_OWNER.
 */
final class OwnerApplicationHelper
{
    /**
     * Normalisasi nomor WhatsApp Indonesia ke +62xxxxxxxxxx.
     * Menerima: +62-897-9777-9879, 0897 9777 9879, 62897..., 897...
     */
    public static function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '62')) {
            // sudah internasional
        } elseif (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        } else {
            return null;
        }
        return preg_match('/^628\d{8,11}$/', $digits) ? '+' . $digits : null;
    }

    /**
     * Buat akun Event Owner.
     *
     * @return array{ok:bool,error:?string,user?:array}
     */
    public static function register(PDO $db, string $nama, string $catatan, string $kontakRaw, string $emailRaw, string $password): array
    {
        $nama = trim(preg_replace('/\s+/u', ' ', $nama) ?? '');
        $catatan = trim($catatan);
        $kontak = self::normalizePhone($kontakRaw);
        $email = filter_var(trim($emailRaw), FILTER_VALIDATE_EMAIL);

        $len = mb_strlen($nama);
        if ($len < 3 || $len > 255) {
            return ['ok' => false, 'error' => 'Nama penyelenggara/komunitas wajib diisi (3–255 karakter).'];
        }
        if (mb_strlen($catatan) > 255) {
            return ['ok' => false, 'error' => 'Notes maksimal 255 karakter.'];
        }
        if ($kontak === null) {
            return ['ok' => false, 'error' => 'Kontak harus berupa nomor WhatsApp Indonesia yang valid, contoh: +62 897-9777-9879.'];
        }
        if (!$email) {
            return ['ok' => false, 'error' => 'Surel tidak valid.'];
        }
        if (strlen($password) < 8) {
            return ['ok' => false, 'error' => 'Kata sandi minimal 8 karakter.'];
        }
        $email = strtolower($email);

        $check = $db->prepare("SELECT 1 FROM users WHERE LOWER(email) = :email");
        $check->execute(['email' => $email]);
        if ($check->fetchColumn()) {
            return ['ok' => false, 'error' => 'Surel tersebut sudah terdaftar di sistem. Silakan masuk.'];
        }

        try {
            $ins = $db->prepare("
                INSERT INTO users (nama, email, password, no_whatsapp, institusi, role_id)
                VALUES (:nama, :email, :password, :wa, :institusi, :role_id)
                RETURNING id
            ");
            $ins->execute([
                'nama'      => $nama,
                'email'     => $email,
                'password'  => password_hash($password, PASSWORD_BCRYPT),
                'wa'        => $kontak,
                'institusi' => $catatan !== '' ? $catatan : null, // Notes disimpan di kolom institusi
                'role_id'   => AuthHelper::ROLE_EVENT_OWNER,
            ]);
            $id = $ins->fetchColumn();
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                return ['ok' => false, 'error' => 'Surel tersebut sudah terdaftar di sistem. Silakan masuk.'];
            }
            error_log('[TIXORA] ' . __METHOD__ . ': ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Pendaftaran gagal karena kesalahan server. Silakan coba lagi.'];
        }

        return ['ok' => true, 'error' => null, 'user' => [
            'id' => $id, 'nama' => $nama, 'email' => $email, 'role' => AuthHelper::ROLE_EVENT_OWNER,
        ]];
    }
}