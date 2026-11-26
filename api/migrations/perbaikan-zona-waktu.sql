-- =====================================================================
-- Perbaikan zona waktu database TIXORA (WIB / Asia/Jakarta)
-- Jalankan MANUAL di SQL Editor Neon (bukan lewat folder migrations).
-- =====================================================================

-- LANGKAH 1 (wajib): jadikan WIB sebagai zona waktu default database.
-- Berlaku untuk koneksi baru, termasuk lewat connection pooler Neon.
-- Ganti "neondb" dan "neondb_owner" bila nama database/role Anda berbeda.
ALTER DATABASE neondb SET timezone TO 'Asia/Jakarta';
ALTER ROLE neondb_owner SET timezone TO 'Asia/Jakarta';

-- Cek (buka koneksi/sesi baru dulu): harus menampilkan Asia/Jakarta
SHOW timezone;

-- LANGKAH 2 (opsional): geser data LAMA yang tersimpan dalam UTC menjadi WIB (+7 jam).
-- Hanya jalankan SEKALI, dan hanya jika data lama tadi dibuat lewat domain (server UTC).
-- Ganti '2026-11-27 00:00:00' dengan waktu (WIB) saat perbaikan ini diterapkan,
-- agar baris baru yang sudah benar tidak ikut tergeser.
-- Kolom tanggal_mulai/tanggal_selesai acara TIDAK disentuh (diisi manual oleh EO dalam WIB).
/*
BEGIN;
UPDATE event_submission_logs SET created_at = created_at + INTERVAL '7 hours' WHERE created_at < '2026-11-27 00:00:00';
UPDATE moderation_logs       SET created_at = created_at + INTERVAL '7 hours' WHERE created_at < '2026-11-27 00:00:00';
UPDATE admin_action_logs     SET created_at = created_at + INTERVAL '7 hours' WHERE created_at < '2026-11-27 00:00:00';
UPDATE tickets               SET waktu_daftar = waktu_daftar + INTERVAL '7 hours' WHERE waktu_daftar < '2026-11-27 00:00:00';
UPDATE events                SET created_at = created_at + INTERVAL '7 hours', updated_at = updated_at + INTERVAL '7 hours' WHERE created_at < '2026-11-27 00:00:00';
UPDATE users                 SET created_at = created_at + INTERVAL '7 hours' WHERE created_at < '2026-11-27 00:00:00';
COMMIT;
*/
