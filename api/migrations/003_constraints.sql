-- Migrasi 003: integritas data (constraint, index, trigger updated_at).
-- Jalankan SETELAH 001 dan 002. Aman dijalankan berulang kali (idempotent) dan tidak menghapus data.
-- Constraint dibuat NOT VALID: baris lama tidak diperiksa ulang (tidak mungkin gagal karena data lama),
-- tetapi setiap INSERT/UPDATE baru wajib lolos. Setelah data dipastikan bersih, boleh dijalankan:
--   ALTER TABLE <tabel> VALIDATE CONSTRAINT <nama>;

-- 1. CHECK constraint ---------------------------------------------------------
DO $$
BEGIN
    -- Status acara: satu daftar nilai yang sah untuk seluruh aplikasi
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_events_status') THEN
        ALTER TABLE events ADD CONSTRAINT chk_events_status
            CHECK (status_verifikasi IN ('DRAF','MENUNGGU_TINJAUAN','DISETUJUI','TERBIT','PUBLISHED','DITOLAK','SELESAI','BATAL')) NOT VALID;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_events_kuota') THEN
        ALTER TABLE events ADD CONSTRAINT chk_events_kuota CHECK (kuota >= 0) NOT VALID;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_events_tanggal') THEN
        ALTER TABLE events ADD CONSTRAINT chk_events_tanggal CHECK (tanggal_selesai >= tanggal_mulai) NOT VALID;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_ticket_categories_kuota') THEN
        ALTER TABLE ticket_categories ADD CONSTRAINT chk_ticket_categories_kuota CHECK (kuota >= 0) NOT VALID;
    END IF;

    -- kolom harga ditambahkan oleh 002
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_ticket_categories_harga') THEN
        ALTER TABLE ticket_categories ADD CONSTRAINT chk_ticket_categories_harga CHECK (harga >= 0) NOT VALID;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_tickets_status') THEN
        ALTER TABLE tickets ADD CONSTRAINT chk_tickets_status
            CHECK (status_tiket IN ('AKTIF','MENUNGGU_PEMBAYARAN','SUDAH_PAKAI','BATAL')) NOT VALID;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_payments_status') THEN
        ALTER TABLE payments ADD CONSTRAINT chk_payments_status
            CHECK (status IN ('MENUNGGU','BERHASIL','KEDALUWARSA','DIBATALKAN')) NOT VALID;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_payments_jumlah') THEN
        ALTER TABLE payments ADD CONSTRAINT chk_payments_jumlah CHECK (jumlah >= 0) NOT VALID;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_rab_nilai') THEN
        ALTER TABLE rab_details ADD CONSTRAINT chk_rab_nilai CHECK (rencana >= 0 AND realisasi >= 0) NOT VALID;
    END IF;
END $$;

-- 2. Satu peserta hanya boleh punya SATU tiket "hidup" per acara -----------------
--    (sebelumnya hanya dijaga di kode aplikasi). Dilewati otomatis bila data lama sudah ada duplikat.
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM tickets
        WHERE status_tiket IN ('AKTIF','MENUNGGU_PEMBAYARAN','SUDAH_PAKAI')
        GROUP BY event_id, user_id HAVING COUNT(*) > 1
    ) THEN
        RAISE NOTICE 'uq_tickets_one_per_user_event dilewati: ada tiket ganda pada data lama. Rapikan dulu lalu jalankan ulang.';
    ELSE
        CREATE UNIQUE INDEX IF NOT EXISTS uq_tickets_one_per_user_event
            ON tickets (event_id, user_id)
            WHERE status_tiket IN ('AKTIF','MENUNGGU_PEMBAYARAN','SUDAH_PAKAI');
    END IF;
END $$;

-- 3. Index tambahan untuk query yang sering dipakai ------------------------------
CREATE INDEX IF NOT EXISTS idx_tickets_category_id      ON tickets (ticket_category_id);
CREATE INDEX IF NOT EXISTS idx_events_status_tanggal    ON events (status_verifikasi, tanggal_mulai);
CREATE INDEX IF NOT EXISTS idx_users_is_active          ON users (is_active);

-- 4. updated_at otomatis ---------------------------------------------------------
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS trigger AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DO $$
DECLARE
    t text;
BEGIN
    FOREACH t IN ARRAY ARRAY['users','events','ticket_categories','tickets','committees','rab_details','rundowns','payments']
    LOOP
        EXECUTE format('DROP TRIGGER IF EXISTS trg_%1$s_updated_at ON %1$I', t);
        EXECUTE format('CREATE TRIGGER trg_%1$s_updated_at BEFORE UPDATE ON %1$I FOR EACH ROW EXECUTE FUNCTION set_updated_at()', t);
    END LOOP;
END $$;
