-- Migrasi 004: hapus kolom roles.kode; peran dikenali hanya lewat id (UUID).
-- Aman dijalankan berulang kali (idempotent) dan dijalankan dalam satu transaksi (blok DO).
--
-- Untuk database LAMA (yang masih punya roles.kode):
--   1. Peran bawaan (ADMIN, EVENT_OWNER, USER) dipindahkan ke id tetap di bawah ini,
--      semua users.role_id diarahkan ulang, lalu baris peran lama dihapus.
--   2. Kolom roles.kode dihapus.
-- Untuk database BARU (001 sudah memakai id tetap): migrasi ini tidak mengubah apa pun.
--
-- Id tetap HARUS sama dengan konstanta di api/helpers/AuthHelper.php:
--   ADMIN       b0000000-0000-4000-8000-000000000001
--   EVENT_OWNER b0000000-0000-4000-8000-000000000002
--   USER        b0000000-0000-4000-8000-000000000003
--
-- Backup dulu bila ada data penting:  pg_dump db_tixora > backup.sql

DO $$
DECLARE
    m RECORD;
    old_id UUID;
    old_nama TEXT;
    old_desc TEXT;
BEGIN
    -- Pastikan ketiga peran bawaan ada dengan id tetap (database baru / peran yang terhapus).
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = current_schema() AND table_name = 'roles' AND column_name = 'kode'
    ) THEN
        -- Database lama: geser peran berdasarkan kode lama menuju id tetap.
        FOR m IN
            SELECT * FROM (VALUES
                ('ADMIN',       'b0000000-0000-4000-8000-000000000001'::uuid),
                ('EVENT_OWNER', 'b0000000-0000-4000-8000-000000000002'::uuid),
                ('USER',        'b0000000-0000-4000-8000-000000000003'::uuid)
            ) AS t(kode, new_id)
        LOOP
            EXECUTE 'SELECT id, nama, description FROM roles WHERE kode = $1'
                INTO old_id, old_nama, old_desc USING m.kode;

            IF old_id IS NULL OR old_id = m.new_id THEN
                CONTINUE; -- belum ada (akan diisi di bawah) atau sudah memakai id tetap
            END IF;

            -- nama bersifat UNIQUE, jadi baris baru dibuat dengan nama sementara dulu.
            INSERT INTO roles (id, nama, description)
                VALUES (m.new_id, old_nama || ' (migrasi)', old_desc)
                ON CONFLICT (id) DO NOTHING;
            UPDATE users SET role_id = m.new_id WHERE role_id = old_id;
            DELETE FROM roles WHERE id = old_id;
            UPDATE roles SET nama = old_nama WHERE id = m.new_id;
        END LOOP;

        ALTER TABLE roles DROP COLUMN kode;
    END IF;

    INSERT INTO roles (id, nama, description) VALUES
        ('b0000000-0000-4000-8000-000000000001', 'Admin Kampus', 'Pengelola & Verifikator Sistem'),
        ('b0000000-0000-4000-8000-000000000002', 'Event Owner',  'Penyelenggara Acara / Ormawa'),
        ('b0000000-0000-4000-8000-000000000003', 'Peserta',      'Mahasiswa / Peserta Acara')
    ON CONFLICT DO NOTHING;
END $$;
