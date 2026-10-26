-- Migrasi 002: harga tiket, pembayaran, dan reset kata sandi.
-- Aman dijalankan berulang kali (idempotent) dan hanya MENAMBAH struktur; tidak ada data yang dihapus.
-- Aplikasi juga menjalankan file ini otomatis satu kali jika kolom ticket_categories.harga belum ada
-- (lihat PaymentHelper::ensureSchema), jadi menjalankannya manual bersifat opsional.

-- 1. Harga per kategori tiket (0 = gratis, perilaku lama tetap berlaku)
ALTER TABLE ticket_categories
    ADD COLUMN IF NOT EXISTS harga NUMERIC(12,2) NOT NULL DEFAULT 0;

-- 2. Tabel pembayaran. Satu tiket berbayar memiliki satu pesanan (order).
--    tickets.status_tiket kini juga boleh bernilai 'MENUNGGU_PEMBAYARAN' (kolom VARCHAR, tanpa CHECK).
CREATE TABLE IF NOT EXISTS payments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_id UUID NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    order_id VARCHAR(40) UNIQUE NOT NULL,
    jumlah NUMERIC(12,2) NOT NULL,
    metode VARCHAR(30),                       -- VA_BCA, VA_BNI, VA_MANDIRI, EWALLET
    kode_pembayaran VARCHAR(40),              -- nomor Virtual Account / nomor tagihan
    status VARCHAR(20) NOT NULL DEFAULT 'MENUNGGU', -- MENUNGGU, BERHASIL, KEDALUWARSA, DIBATALKAN
    batas_waktu TIMESTAMP NOT NULL,
    dibayar_pada TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_payments_ticket_id ON payments(ticket_id);
CREATE INDEX IF NOT EXISTS idx_payments_user_id ON payments(user_id);
CREATE INDEX IF NOT EXISTS idx_payments_status_batas ON payments(status, batas_waktu);

-- 3. Token reset kata sandi (hanya hash token yang disimpan)
CREATE TABLE IF NOT EXISTS password_resets (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash VARCHAR(64) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_password_resets_user_id ON password_resets(user_id);

-- 4. Harga contoh untuk data seed agar alur pembayaran bisa langsung dicoba.
--    Hanya berjalan sebagai bagian dari migrasi pertama; ubah harga lewat panel Event Owner setelahnya.
UPDATE ticket_categories SET harga = 50000
    WHERE id = 'c1234567-89ab-cdef-0123-456789abcdef' AND harga = 0;
UPDATE ticket_categories SET harga = 75000
    WHERE id = 'c2234567-89ab-cdef-0123-456789abcdef' AND harga = 0;
