-- Migrasi 011: status baca notifikasi admin (satu baris per admin).
-- Notifikasi sendiri diturunkan dari data yang sudah ada (pengajuan acara, penyelenggara baru, dst);
-- tabel ini hanya mencatat kapan terakhir admin menandai semua notifikasi dibaca.
-- Idempotent. Data lama tidak terpengaruh.

CREATE TABLE IF NOT EXISTS admin_notification_state (
    admin_id UUID PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    last_read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
