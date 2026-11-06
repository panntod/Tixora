# Panduan Deployment TIXORA ke Render

Repositori ini telah disiapkan dengan konfigurasi Docker lengkap sehingga siap di-deploy langsung ke [Render](https://render.com).

---

## 🚀 Ringkasan Konfigurasi Docker

- **Base Image:** `php:8.2-apache`
- **Ekstensi PHP:** `pdo`, `pdo_pgsql`, `pgsql`
- **Web Server:** Apache dengan `mod_rewrite` & `headers` aktif, serta `AllowOverride All`
- **Dynamic Port:** Mendukung port dinamis Render (`$PORT`, default 10000)
- **Database:** PostgreSQL (mendukung `DATABASE_URL` otomatis dari Render PostgreSQL)
- **Auto Migration:** Script `bin/migrate.php` otomatis dijalankan saat container pertama kali start untuk menginisialisasi skema tabel & data seed demo (berurutan: `001`, `002`, `003`, dan migrasi peran `004`).

---

## 🛠️ Pilihan 1: Deploy Otomatis (Render Blueprint / `render.yaml`) — Direkomendasikan

Render mendukung Blueprint Infrastructure-as-Code. File [render.yaml](render.yaml) sudah disediakan di root project.

1. **Push project ke GitHub/GitLab:**
   ```bash
   git add .
   git commit -m "Siapkan Docker & Render deployment"
   git push origin main
   ```
2. Buka dashboard [Render](https://dashboard.render.com).
3. Klik tombol **New +** > pilih **Blueprint**.
4. Hubungkan repositori GitHub/GitLab kamu.
5. Render akan otomatis mendeteksi file `render.yaml` dan membuat 2 resource:
   - **PostgreSQL Database** (`tixora-db`)
   - **Web Service Docker** (`tixora-app`)
6. Klik **Apply**.
7. Tunggu build selesai. Tixora langsung online dan database otomatis termigrasi! 🎉

---

## ⚙️ Pilihan 2: Deploy Manual di Render Dashboard

Jika ingin membuat service satu per satu secara manual:

### 1. Buat Database PostgreSQL di Render
1. Di Dashboard Render, klik **New +** > pilih **PostgreSQL**.
2. Beri nama: `tixora-db`.
3. Database Name: `db_tixora`.
4. User: `postgres`.
5. Region: Pilih yang terdekat (misal: `Singapore`).
6. Klik **Create Database**.
7. Setelah database selesai dibuat, salin nilai **Internal Database URL** (misal: `postgresql://postgres:...@.../db_tixora`).

### 2. Buat Web Service Docker di Render
1. Di Dashboard Render, klik **New +** > pilih **Web Service**.
2. Hubungkan repositori Git Tixora kamu.
3. Konfigurasi:
   - **Name:** `tixora-app`
   - **Region:** Samakan dengan region database kamu (misal: `Singapore`)
   - **Runtime:** `Docker`
   - **Instance Type:** `Free`
4. Di bagian **Environment Variables**, tambahkan:
   - `DATABASE_URL` = tempelkan *Internal Database URL* dari langkah 1
   - `AUTO_MIGRATE` = `true`
   - `TIXORA_QR_SECRET` = klik tombol *Generate* atau isi string acak rahasia
5. Di bagian **Health Check Path**, isi: `/`
6. Klik **Deploy Web Service**.

---

## 💻 Pengujian Lokal dengan Docker Compose

Kamu bisa menguji container secara lokal sebelum push ke cloud:

```bash
# Jalankan container web app dan PostgreSQL
docker compose up -d

# Cek logs migrasi & web server
docker compose logs -f app

# Akses aplikasi di browser
http://localhost:8080
```

Untuk mematikan:
```bash
docker compose down
```

---

## 👥 Akun Demo Bawaan (Hasil Migrasi Awal)

Setelah migrasi selesai, kamu bisa login dengan akun contoh:

| Peran | Email | Kata Sandi |
|---|---|---|
| **Admin Kampus** | `admin@tixora.ac.id` | `password123` |
| **Event Owner** | `hmti@polinema.ac.id` | `password123` |
| **Peserta** | `alyarahma@gmail.com` | `password123` |
