-- Migrasi 007: foreign key constraint changes untuk integritas data.
-- PRASYARAT: kode versi Wave 4 Deploy B sudah deployed (soft delete aktif).
-- Idempotent. Aman dijalankan berulang (DROP + ADD).
--
-- Untuk rollback:
--   Lihat file ini, ubah RESTRICT/SET NULL kembali ke CASCADE.

DO $$
DECLARE
    orphan_count INT;
    fk_name TEXT;
BEGIN
    -- ============ PRE-VALIDATION ============
    SELECT COUNT(*) INTO orphan_count FROM events e
    WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.id = e.owner_id);
    IF orphan_count > 0 THEN
        RAISE EXCEPTION 'ABORT: % events have orphan owner_id. Fix before migration.', orphan_count;
    END IF;

    SELECT COUNT(*) INTO orphan_count FROM tickets t
    WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.id = t.user_id);
    IF orphan_count > 0 THEN
        RAISE EXCEPTION 'ABORT: % tickets have orphan user_id. Fix before migration.', orphan_count;
    END IF;

    SELECT COUNT(*) INTO orphan_count FROM tickets t
    WHERE t.ticket_category_id IS NOT NULL
      AND NOT EXISTS (SELECT 1 FROM ticket_categories c WHERE c.id = t.ticket_category_id);
    IF orphan_count > 0 THEN
        RAISE EXCEPTION 'ABORT: % tickets have orphan ticket_category_id. Fix before migration.', orphan_count;
    END IF;

    -- ============ events.owner_id: CASCADE -> RESTRICT ============
    SELECT conname INTO fk_name FROM pg_constraint
    WHERE conrelid = 'events'::regclass AND contype = 'f'
      AND confrelid = 'users'::regclass
      AND conkey = ARRAY[(SELECT attnum FROM pg_attribute
                          WHERE attrelid = 'events'::regclass AND attname = 'owner_id')];
    IF fk_name IS NOT NULL THEN
        EXECUTE format('ALTER TABLE events DROP CONSTRAINT %I', fk_name);
    END IF;
    ALTER TABLE events ADD CONSTRAINT events_owner_id_fkey
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT;

    -- ============ tickets.user_id: CASCADE -> RESTRICT ============
    SELECT conname INTO fk_name FROM pg_constraint
    WHERE conrelid = 'tickets'::regclass AND contype = 'f'
      AND confrelid = 'users'::regclass
      AND conkey = ARRAY[(SELECT attnum FROM pg_attribute
                          WHERE attrelid = 'tickets'::regclass AND attname = 'user_id')];
    IF fk_name IS NOT NULL THEN
        EXECUTE format('ALTER TABLE tickets DROP CONSTRAINT %I', fk_name);
    END IF;
    ALTER TABLE tickets ADD CONSTRAINT tickets_user_id_fkey
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT;

    -- ============ tickets.ticket_category_id: SET NULL -> RESTRICT ============
    SELECT conname INTO fk_name FROM pg_constraint
    WHERE conrelid = 'tickets'::regclass AND contype = 'f'
      AND confrelid = 'ticket_categories'::regclass
      AND conkey = ARRAY[(SELECT attnum FROM pg_attribute
                          WHERE attrelid = 'tickets'::regclass AND attname = 'ticket_category_id')];
    IF fk_name IS NOT NULL THEN
        EXECUTE format('ALTER TABLE tickets DROP CONSTRAINT %I', fk_name);
    END IF;
    ALTER TABLE tickets ADD CONSTRAINT tickets_ticket_category_id_fkey
        FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON DELETE RESTRICT;

    -- ============ moderation_logs.event_id: CASCADE -> SET NULL ============
    SELECT conname INTO fk_name FROM pg_constraint
    WHERE conrelid = 'moderation_logs'::regclass AND contype = 'f'
      AND confrelid = 'events'::regclass
      AND conkey = ARRAY[(SELECT attnum FROM pg_attribute
                          WHERE attrelid = 'moderation_logs'::regclass AND attname = 'event_id')];
    IF fk_name IS NOT NULL THEN
        EXECUTE format('ALTER TABLE moderation_logs DROP CONSTRAINT %I', fk_name);
    END IF;
    ALTER TABLE moderation_logs ADD CONSTRAINT moderation_logs_event_id_fkey
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL;

    RAISE NOTICE 'Migration 007: all FK constraints updated successfully.';
END $$;