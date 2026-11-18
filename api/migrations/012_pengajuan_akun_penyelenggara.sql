-- Migrasi 012: pengajuan akun penyelenggara (persetujuan admin).
-- Idempotent; hanya MENAMBAH kolom. status_pengajuan NULL = akun tidak melalui pengajuan (admin, peserta, akun lama).
--   MENUNGGU  : penyelenggara baru mendaftar, belum bisa masuk sampai disetujui admin
--   DISETUJUI : boleh masuk
--   DITOLAK   : tidak bisa masuk; alasan di alasan_penolakan_akun
-- Rollback manual:
--   ALTER TABLE users DROP COLUMN status_pengajuan, DROP COLUMN diajukan_pada, DROP COLUMN ditinjau_oleh,
--                     DROP COLUMN ditinjau_pada, DROP COLUMN alasan_penolakan_akun;

ALTER TABLE users ADD COLUMN IF NOT EXISTS status_pengajuan VARCHAR(20)
    CHECK (status_pengajuan IN ('MENUNGGU', 'DISETUJUI', 'DITOLAK'));
ALTER TABLE users ADD COLUMN IF NOT EXISTS diajukan_pada TIMESTAMP;
ALTER TABLE users ADD COLUMN IF NOT EXISTS ditinjau_oleh UUID REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS ditinjau_pada TIMESTAMP;
ALTER TABLE users ADD COLUMN IF NOT EXISTS alasan_penolakan_akun TEXT;

CREATE INDEX IF NOT EXISTS idx_users_status_pengajuan ON users(status_pengajuan) WHERE status_pengajuan IS NOT NULL;

-- Penyelenggara yang sudah ada dianggap sudah disetujui (id peran = AuthHelper::ROLE_EVENT_OWNER).
UPDATE users
SET status_pengajuan = 'DISETUJUI', diajukan_pada = created_at
WHERE role_id = 'b0000000-0000-4000-8000-000000000002' AND status_pengajuan IS NULL;
