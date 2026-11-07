# Panduan Deploy TIXORA ke Back4App (dengan Neon PostgreSQL & Docker Hub)

Panduan lengkap untuk mempublikasikan Docker Image TIXORA ke Docker Hub dan mendeploynya ke **Back4App Containers** dengan database serverless **Neon PostgreSQL**.

---

## 📋 Ringkasan Arsitektur

- **Aplikasi Web**: Kontainer Docker TIXORA (PHP 8.2 + Apache) berjalan di Back4App Containers.
- **Database**: PostgreSQL di [Neon.tech](https://neon.tech) (Serverless, gratis & mendukung SSL).
- **Registry**: Docker Hub (`<username>/tixora:latest`).
- **Migrasi Otomatis**: Skrip `bin/migrate.php` akan otomatis menginisialisasi skema (001–004) saat container pertama kali start.

---

## 🗄️ Langkah 1: Siapkan Database di Neon.tech

1. Buka [neon.tech](https://neon.tech) dan login / register.
2. Klik **New Project**, beri nama misal `tixora-db`.
3. Pilih Region terdekat (misal: `ap-southeast-1` Singapore atau `aws-ap-southeast-1`).
4. Setelah project dibuat, pada Dashboard Neon di bagian **Connection Details**:
   - Pilih tab **Connection string**.
   - Centang opsi **Direct connection** (disarankan, tanpa embel-embel `-pooler` agar eksekusi migrasi DDL dan PDO prepared statement lancar).
   - Salin connection string yang dihasilkan, bentuknya:
     ```
     postgresql://neondb_owner:npg_xxxx@ep-cool-fog-123456.ap-southeast-1.aws.neon.tech/neondb?sslmode=require
     ```

---

## 💻 Langkah 2: Uji Coba Lokal Terlebih Dahulu

1. Salin file contoh `.env`:
   ```bash
   cp .env.example .env
   ```
2. Buka `.env` dan tempelkan connection string Neon Anda ke `DATABASE_URL`:
   ```env
   DATABASE_URL=postgresql://neondb_owner:npg_xxxx@ep-cool-fog-123456.ap-southeast-1.aws.neon.tech/neondb?sslmode=require
   AUTO_MIGRATE=true
   TIXORA_QR_SECRET=kunci-rahasia-qr-lokal-minimal-32-karakter
   ```
3. Jalankan container lokal:
   ```bash
   docker compose up -d --build
   ```
4. Cek log migrasi:
   ```bash
   docker compose logs -f app
   ```
   Tixora akan otomatis terhubung ke Neon dan mengaplikasikan file migrasi `001` sampai `004`.
5. Buka `http://localhost:8080` untuk memastikan aplikasi berjalan normal.
6. Hentikan container setelah selesai pengujian:
   ```bash
   docker compose down
   ```

---

## 🐳 Langkah 3: Build & Publish Docker Image ke Docker Hub

> [!IMPORTANT]
> Karena sebagian besar server cloud Back4App menggunakan arsitektur **linux/amd64 (x86_64)**, jika Anda melakukan build dari Mac Apple Silicon (M1/M2/M3/M4), **WAJIB** menyertakan flag `--platform linux/amd64` agar tidak terjadi error `exec format error` di server.

1. Login ke Docker Hub melalui terminal:
   ```bash
   docker login
   ```
   *(Masukkan username dan personal access token / password Docker Hub Anda)*

2. Build dan Push image:
   Ganti `USERNAME_DOCKERHUB` dengan username Docker Hub Anda:
   ```bash
   docker buildx build --platform linux/amd64 -t USERNAME_DOCKERHUB/tixora:latest --push .
   ```
   *(Contoh: `docker buildx build --platform linux/amd64 -t pandhuarya/tixora:latest --push .`)*

3. Setelah proses selesai, pastikan repository image Anda sudah muncul di dashboard [hub.docker.com](https://hub.docker.com).

---

## ☁️ Langkah 4: Deploy di Back4App Containers

1. Masuk ke [Back4App Dashboard](https://www.back4app.com/) dan login.
2. Klik tombol **Build new app** (atau **Create new app**).
3. Pilih opsi **Containers as a Service** (Back4App Web App).
4. Di bagian sumber deployment, pilih **Docker Hub** (atau pilih public image jika image publik):
   - Masukkan nama image: `USERNAME_DOCKERHUB/tixora:latest`
   - Jika image bersifat private, hubungkan Docker Hub credentials Anda.
5. Konfigurasi App:
   - **App Name**: `tixora`
   - **Port**: `80` (container Tixora mengekspos port 80 secara internal)
6. Di bagian **Environment Variables**, tambahkan 3 variabel penting berikut:

   | Key | Value | Keterangan |
   |---|---|---|
   | `DATABASE_URL` | `postgresql://neondb_owner:npg_xxxx@ep-....neon.tech/neondb?sslmode=require` | Connection string Neon Anda |
   | `AUTO_MIGRATE` | `true` | Menjalankan migrasi database otomatis saat pertama start |
   | `TIXORA_QR_SECRET` | `string-acak-rahasia-minimal-32-karakter` | Kunci enkripsi token QR tiket |
   | `TIXORA_PUBLIC_URL` | `https://tixora-xxxx.b4a.run` | (Opsional) URL publik untuk isi QR e-ticket, tanpa `/` di akhir |

7. Klik **Create App** (Deploy).
8. Tunggu Back4App menarik (*pull*) image dan memulai container.
9. Di tab **Logs**, Anda akan melihat output:
   ```text
   [TIXORA] Menyiapkan Apache untuk mendengarkan port 80...
   [TIXORA] Menjalankan migrasi database...
   === TIXORA Database Migration ===
   [OK] Terhubung ke basis data PostgreSQL.
   ...
   === Semua migrasi berhasil diaplikasikan! ===
   [TIXORA] Menjalankan web server pada port 80...
   ```
10. Setelah status menjadi **Ready / Running**, klik URL domain yang disediakan Back4App (misal `https://tixora-xxxx.b4a.run`)! 🎉

---

## 👥 Akun Demo Bawaan

| Peran | Email | Kata Sandi |
|---|---|---|
| **Admin Kampus** | `admin@tixora.ac.id` | `password123` |
| **Event Owner** | `hmti@polinema.ac.id` | `password123` |
| **Peserta** | `alyarahma@gmail.com` | `password123` |


---

## 🔐 Catatan Keamanan Sebelum Go-Live

- `api/config/database.local.php` dan `api/config/qr_secret.key` **tidak ikut** ke image/zip dan sudah di `.gitignore`. Di server semua konfigurasi dibaca dari environment variable.
- Jika password database lokal pernah ikut ter-commit/terbagikan, **ganti password-nya**.
- Akun demo (`password123`) berasal dari seed migrasi 001. Setelah demo/presentasi, ubah atau hapus akun tersebut.
- Migrasi tercatat di tabel `schema_migrations`, jadi tidak diulang di setiap restart. `000_reset_dev.sql` tidak pernah dijalankan otomatis.
