-- Migrasi 006: soft delete, snapshot pembayaran, dan audit log admin.
-- Aman dijalankan berulang kali (idempotent). HANYA menambah struktur.
-- Baris lama tidak diperiksa ulang, tidak ada data yang dihapus.
--
-- Untuk rollback (jika diperlukan):
--   ALTER TABLE users DROP COLUMN deleted_at;
--   ALTER TABLE events DROP COLUMN deleted_at;
--   ALTER TABLE tickets DROP COLUMN deleted_at;
--   ALTER TABLE payments DROP COLUMN snapshot_nama, snapshot_email, snapshot_kategori, snapshot_harga;
--   DROP TABLE admin_action_logs;
--   ALTER TABLE moderation_logs ALTER COLUMN event_id SET NOT NULL;

-- =================================================================
-- 1. Soft delete: users, events, tickets
-- =================================================================
ALTER TABLE users ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP;
ALTER TABLE events ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP;
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP;

-- Partial index untuk query "active only" (efisien di PostgreSQL)
CREATE INDEX IF NOT EXISTS idx_users_active ON users(id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_events_active ON events(id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_tickets_active ON tickets(id) WHERE deleted_at IS NULL;

-- Index untuk query "yang sudah dihapus" (audit admin)
CREATE INDEX IF NOT EXISTS idx_users_deleted ON users(deleted_at) WHERE deleted_at IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_events_deleted ON events(deleted_at) WHERE deleted_at IS NOT NULL;

-- =================================================================
-- 2. Snapshot pembayaran (frozen state saat order dibuat)
-- =================================================================
ALTER TABLE payments ADD COLUMN IF NOT EXISTS snapshot_nama VARCHAR(255);
ALTER TABLE payments ADD COLUMN IF NOT EXISTS snapshot_email VARCHAR(255);
ALTER TABLE payments ADD COLUMN IF NOT EXISTS snapshot_kategori VARCHAR(100);
ALTER TABLE payments ADD COLUMN IF NOT EXISTS snapshot_harga NUMERIC(12,2);

-- =================================================================
-- 3. Admin action log (BP-06: audit suspend user & aksi admin lain)
-- =================================================================
CREATE TABLE IF NOT EXISTS admin_action_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    admin_id UUID REFERENCES users(id) ON DELETE SET NULL,
    action_type VARCHAR(50) NOT NULL,   -- user_suspend, user_activate, password_reset, event_approve, event_reject
    target_type VARCHAR(30) NOT NULL,   -- user, event
    target_id UUID NOT NULL,
    catatan TEXT,
    metadata JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_admin_action_logs_admin ON admin_action_logs(admin_id);
CREATE INDEX IF NOT EXISTS idx_admin_action_logs_target ON admin_action_logs(target_type, target_id);
CREATE INDEX IF NOT EXISTS idx_admin_action_logs_created ON admin_action_logs(created_at DESC);

-- =================================================================
-- 4. Moderation logs: izinkan event_id NULL (persiapan SET NULL di migrasi 007)
-- =================================================================
ALTER TABLE moderation_logs ALTER COLUMN event_id DROP NOT NULL;