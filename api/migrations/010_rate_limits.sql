-- Migrasi 010: tabel rate limiting untuk login & password reset.
-- Idempotent. Data lama tidak terpengaruh.

CREATE TABLE IF NOT EXISTS rate_limits (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    identifier VARCHAR(255) NOT NULL,     -- email atau IP
    attempt_type VARCHAR(50) NOT NULL,    -- 'login', 'password_reset', 'register'
    success BOOLEAN NOT NULL DEFAULT FALSE,
    ip_address VARCHAR(45),               -- IPv4 atau IPv6
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_rate_limits_identifier_type_time
    ON rate_limits(identifier, attempt_type, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_rate_limits_cleanup
    ON rate_limits(created_at) WHERE created_at < CURRENT_TIMESTAMP - INTERVAL '1 hour';

-- Cleanup otomatis: hapus rows > 24 jam saat migrasi dijalankan
DELETE FROM rate_limits WHERE created_at < CURRENT_TIMESTAMP - INTERVAL '24 hours';