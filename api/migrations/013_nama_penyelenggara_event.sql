-- Nama penyelenggara per acara (boleh berbeda dari nama akun owner).
ALTER TABLE events ADD COLUMN IF NOT EXISTS nama_penyelenggara VARCHAR(255);
