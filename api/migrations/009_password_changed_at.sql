-- Migrasi 009: revoke session di device lain saat password berubah.
-- Idempotent. Aman dijalankan ulang.
--
-- Rollback:
--   ALTER TABLE users DROP COLUMN IF EXISTS password_changed_at;

ALTER TABLE users ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP;

-- Initialize: set sama dengan created_at untuk user yang sudah ada
-- (supaya session mereka yang sedang aktif tidak tiba-tiba logout)
UPDATE users
SET password_changed_at = COALESCE(updated_at, created_at, CURRENT_TIMESTAMP)
WHERE password_changed_at IS NULL;