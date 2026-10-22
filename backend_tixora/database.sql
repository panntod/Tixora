-- Hapus tabel jika sudah ada untuk reset migrasi (berdasarkan urutan foreign key yang benar)
DROP TABLE IF EXISTS moderation_logs CASCADE;
DROP TABLE IF EXISTS event_submission_logs CASCADE;
DROP TABLE IF EXISTS rundowns CASCADE;
DROP TABLE IF EXISTS rab_details CASCADE;
DROP TABLE IF EXISTS committees CASCADE;
DROP TABLE IF EXISTS tickets CASCADE;
DROP TABLE IF EXISTS ticket_categories CASCADE;
DROP TABLE IF EXISTS events CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS roles CASCADE;

-- 1. Tabel Roles (Manajemen Hak Akses / RBAC)
CREATE TABLE roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode VARCHAR(50) UNIQUE NOT NULL,
    nama VARCHAR(100) UNIQUE NOT NULL,
    description TEXT
);

-- 2. Tabel Users (Pengguna Sistem: Admin, Event Owner, User)
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nama VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    no_whatsapp VARCHAR(20),
    institusi VARCHAR(255),
    role_id UUID NOT NULL REFERENCES roles(id) ON DELETE RESTRICT,
    is_active BOOLEAN DEFAULT TRUE,
    alasan_suspensi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_users_role_id ON users(role_id);

-- 3. Tabel Events (Manajemen Acara oleh Event Owner)
CREATE TABLE events (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    owner_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    judul_event VARCHAR(255) NOT NULL,
    deskripsi TEXT NOT NULL,
    kategori VARCHAR(100) DEFAULT 'Seminar',
    kuota INT NOT NULL DEFAULT 100,
    tanggal_mulai TIMESTAMP NOT NULL,
    tanggal_selesai TIMESTAMP NOT NULL,
    nama_tempat VARCHAR(255) NOT NULL,
    kota VARCHAR(100) NOT NULL,
    banner_url TEXT,
    status_verifikasi VARCHAR(50) DEFAULT 'DRAF', -- DRAF, MENUNGGU_TINJAUAN, DISETUJUI, TERBIT, DITOLAK, SELESAI, BATAL
    alasan_penolakan TEXT,
    alasan_pembatalan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_events_owner_id ON events(owner_id);

-- 4. Tabel Ticket Categories (Kategori Jenis Tiket Event Gratis)
CREATE TABLE ticket_categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    nama_kategori VARCHAR(100) NOT NULL, -- Contoh: Umum, Mahasiswa, VIP
    kuota INT NOT NULL DEFAULT 0,
    wajib_ktm BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_ticket_categories_event_id ON ticket_categories(event_id);

-- 5. Tabel Tickets (Pendaftaran E-Ticket Peserta / QR Code)
CREATE TABLE tickets (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    ticket_category_id UUID REFERENCES ticket_categories(id) ON DELETE SET NULL,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    kode_tiket VARCHAR(100) UNIQUE NOT NULL, -- Format: TXR-YYYYMMDD-Random
    jenis_tiket VARCHAR(100) DEFAULT 'Umum',
    status_tiket VARCHAR(50) DEFAULT 'AKTIF', -- AKTIF, BATAL
    status_kehadiran BOOLEAN DEFAULT FALSE,
    waktu_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_tickets_event_id ON tickets(event_id);
CREATE INDEX idx_tickets_user_id ON tickets(user_id);

-- 6. Tabel Committees (Struktur Panitia Internal Event)
CREATE TABLE committees (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    nama_panitia VARCHAR(255) NOT NULL,
    posisi_jabatan VARCHAR(100) NOT NULL,
    divisi VARCHAR(100), -- Inti, Acara, Humas, Konsumsi, dll
    kontak VARCHAR(50),
    surel VARCHAR(255),
    access_token VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_committees_event_id ON committees(event_id);

-- 7. Tabel RabDetails (Rincian Anggaran Biaya Internal Event)
CREATE TABLE rab_details (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    kategori VARCHAR(100) NOT NULL,
    rencana DECIMAL(12,2) DEFAULT 0,
    realisasi DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_rab_details_event_id ON rab_details(event_id);

-- 8. Tabel Rundowns (Susunan Acara / Sesi Kegiatan)
CREATE TABLE rundowns (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    committee_id UUID REFERENCES committees(id) ON DELETE SET NULL,
    waktu_mulai VARCHAR(20) NOT NULL,
    waktu_selesai VARCHAR(20) NOT NULL,
    judul_sesi VARCHAR(255) NOT NULL,
    pembicara VARCHAR(255),
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_rundowns_event_id ON rundowns(event_id);
CREATE INDEX idx_rundowns_committee_id ON rundowns(committee_id);

-- 9. Tabel EventSubmissionLogs (Riwayat Pengajuan / Revisi Event)
CREATE TABLE event_submission_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    status VARCHAR(50) NOT NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_event_submission_logs_event_id ON event_submission_logs(event_id);

-- 10. Tabel ModerationLogs (Catatan Validasi & Keputusan Admin)
CREATE TABLE moderation_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    admin_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    event_id UUID NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    keputusan VARCHAR(50) NOT NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_moderation_logs_admin_id ON moderation_logs(admin_id);
CREATE INDEX idx_moderation_logs_event_id ON moderation_logs(event_id);

-- Seed Data Role Default
INSERT INTO roles (kode, nama, description) VALUES
('ADMIN', 'Admin Kampus', 'Pengelola & Verifikator Sistem'),
('EVENT_OWNER', 'Event Owner', 'Penyelenggara Acara / Ormawa'),
('USER', 'Peserta', 'Mahasiswa / Peserta Acara');

-- =================================================================
-- SEED DATA AWAL (AKUN DEMO & SAMPLE EVENT SIAP PAKAI)
-- Password untuk semua akun demo adalah: password123
-- =================================================================

-- 1. Seed Data Users (Admin, Event Owner, Peserta)
INSERT INTO users (id, nama, email, password, no_whatsapp, institusi, role_id, is_active) VALUES
-- Admin Kampus: Budi Admin
('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 'Budi Admin', 'admin@tixora.ac.id', '$2y$10$e.wS/P/E/wOn1zV29Rk3i.3pL.cO4.uO2q2U3W.q8N7I.m6h9S2Si', '081234567890', 'Politeknik Negeri Malang', (SELECT id FROM roles WHERE kode = 'ADMIN'), TRUE),

-- Event Owner: HMTI Polinema
('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a22', 'HMTI Polinema', 'hmti@polinema.ac.id', '$2y$10$e.wS/P/E/wOn1zV29Rk3i.3pL.cO4.uO2q2U3W.q8N7I.m6h9S2Si', '089876543210', 'Himpunan Mahasiswa Teknologi Informasi', (SELECT id FROM roles WHERE kode = 'EVENT_OWNER'), TRUE),

-- User / Peserta: Alya Rahma
('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a33', 'Alya Rahma', 'alyarahma@gmail.com', '$2y$10$e.wS/P/E/wOn1zV29Rk3i.3pL.cO4.uO2q2U3W.q8N7I.m6h9S2Si', '085123456789', 'Politeknik Negeri Malang', (SELECT id FROM roles WHERE kode = 'USER'), TRUE);

-- 2. Seed Data Event Contoh (Diselenggarakan oleh HMTI Polinema dan Berstatus TERBIT)
INSERT INTO events (id, owner_id, judul_event, deskripsi, kategori, kuota, tanggal_mulai, tanggal_selesai, nama_tempat, kota, banner_url, status_verifikasi) VALUES
('e1234567-89ab-cdef-0123-456789abcdef', 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a22', 'Seminar Nasional Teknologi Informasi 2026', 'Seminar nasional tahunan HMTI Polinema yang membahas perkembangan Artificial Intelligence dan Future Tech.', 'Seminar', 150, '2026-11-15 08:00:00', '2026-11-15 15:00:00', 'Auditorium Gedung AH Polinema', 'Malang', '/uploads/banner_20261019095836_d57de3d4.png', 'TERBIT');

-- 3. Seed Data Kategori Tiket untuk Event Contoh
INSERT INTO ticket_categories (id, event_id, nama_kategori, kuota, wajib_ktm) VALUES
('c1234567-89ab-cdef-0123-456789abcdef', 'e1234567-89ab-cdef-0123-456789abcdef', 'Mahasiswa Polinema', 100, TRUE),
('c2234567-89ab-cdef-0123-456789abcdef', 'e1234567-89ab-cdef-0123-456789abcdef', 'Umum / Luar Kampus', 50, FALSE);

-- 4. Seed Data E-Ticket Contoh (Alya Rahma Mendaftar pada Event HMTI)
INSERT INTO tickets (id, event_id, ticket_category_id, user_id, kode_tiket, jenis_tiket, status_tiket, status_kehadiran) VALUES
('t1234567-89ab-cdef-0123-456789abcdef', 'e1234567-89ab-cdef-0123-456789abcdef', 'c1234567-89ab-cdef-0123-456789abcdef', 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a33', 'TXR-20261115-ALYA01', 'Mahasiswa Polinema', 'AKTIF', FALSE);
