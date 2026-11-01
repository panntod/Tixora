-- 000_reset.sql  --  HANYA UNTUK PENGEMBANGAN LOKAL.
-- Menghapus SEMUA tabel dan datanya agar bisa memulai dari nol.
-- JANGAN dijalankan di server demo/produksi yang sudah berisi data.
-- Setelah ini jalankan: 001_database.sql, 002_payment.sql, 003_constraints.sql

DROP TABLE IF EXISTS password_resets CASCADE;
DROP TABLE IF EXISTS payments CASCADE;
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
