-- Migrasi 008: hapus 'PUBLISHED' dari CHECK constraint events.status_verifikasi.
-- Nilai 'PUBLISHED' adalah sisa desain lama yang tidak pernah di-set kode.
-- Migrasi 003 memasang CHECK yang menyertakan PUBLISHED (via COMMENT) tapi
-- tidak di-enforce; jika ada row dengan 'PUBLISHED' di production, migrasi ini
-- akan gagal — periksa dulu.
--
-- Rollback:
--   Lihat definisi 003_constraints.sql, re-add 'PUBLISHED' ke CHECK.

-- Guard: pastikan tidak ada data PUBLISHED
DO $$
DECLARE
    cnt INT;
BEGIN
    SELECT COUNT(*) INTO cnt FROM events WHERE status_verifikasi = 'PUBLISHED';
    IF cnt > 0 THEN
        RAISE EXCEPTION 'ABORT: % events masih berstatus PUBLISHED. Migrasi ke TERBIT dulu.', cnt;
    END IF;
END $$;

-- Drop CHECK lama, add baru (tanpa PUBLISHED)
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_events_status') THEN
        ALTER TABLE events DROP CONSTRAINT chk_events_status;
    END IF;
END $$;

ALTER TABLE events ADD CONSTRAINT chk_events_status
    CHECK (status_verifikasi IN (
        'DRAF','MENUNGGU_TINJAUAN','DISETUJUI','TERBIT','DITOLAK','SELESAI','BATAL'
    )) NOT VALID;