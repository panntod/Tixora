-- Migrasi 005: jadwal penjualan per kategori tiket (wizard "Buat acara", langkah 2).
-- Aman dijalankan berulang kali (idempotent) dan hanya MENAMBAH kolom.
-- Aplikasi juga menjalankan file ini otomatis satu kali jika kolom belum ada
-- (lihat pages/event-owner/buat-acara/index.php), jadi menjalankannya manual bersifat opsional.

ALTER TABLE ticket_categories
    ADD COLUMN IF NOT EXISTS mulai_dijual DATE,
    ADD COLUMN IF NOT EXISTS berakhir_dijual DATE;
