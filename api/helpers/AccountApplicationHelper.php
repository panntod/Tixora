<?php
declare(strict_types=1);

/**
 * Pengajuan akun penyelenggara (migrasi 012).
 *
 * Penyelenggara yang mendaftar lewat form publik berstatus MENUNGGU sampai admin menyetujui.
 * Akun tanpa status (admin, peserta, akun lama) tidak terpengaruh.
 */
final class AccountApplicationHelper
{
    public const MENUNGGU  = 'MENUNGGU';
    public const DISETUJUI = 'DISETUJUI';
    public const DITOLAK   = 'DITOLAK';

    public const STATUSES = [self::MENUNGGU, self::DISETUJUI, self::DITOLAK];

    /** Menjalankan migrasi 012 bila kolom belum ada, sekali per request. */
    public static function ensureSchema(PDO $db): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $exists = $db->query("
            SELECT 1 FROM information_schema.columns
            WHERE table_schema = current_schema() AND table_name = 'users' AND column_name = 'status_pengajuan'
        ")->fetchColumn();

        if (!$exists) {
            $sql = file_get_contents(__DIR__ . '/../migrations/012_pengajuan_akun_penyelenggara.sql');
            if ($sql === false) {
                throw new RuntimeException('File migrasi 012_pengajuan_akun_penyelenggara.sql tidak ditemukan.');
            }
            $db->exec($sql);
        }
    }

    /** @return array{0: string, 1: string} [kelas badge, label] */
    public static function badge(?string $status): array
    {
        return match ($status) {
            self::DISETUJUI => ['badge-success', 'Disetujui'],
            self::DITOLAK   => ['badge-error', 'Ditolak'],
            self::MENUNGGU  => ['badge-warning', 'Menunggu'],
            default         => ['badge-neutral', '—'],
        };
    }
}