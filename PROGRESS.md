# Tixora — Progress pengembangan

## Batasan dan prinsip
- Database PostgreSQL yang sudah terintegrasi tidak diubah.
- `api/database.sql` hanya menjadi referensi; skema database yang ada dihormati.
- Semua klaim status dibedakan antara pemeriksaan statis dan pengujian runtime.
- Desain dan fitur yang sudah ada dipertahankan secara konsisten.

## Status Pekerjaan Fungsionalitas
- [x] Web server PHP native telah dijalankan dan aktif di port local (http://127.0.0.1:8000).
- [x] Seluruh 52 routes di `index.php` telah diverifikasi eksistensi file fisiknya di disk.
- [x] Sinkronisasi query controller dengan skema aktual PostgreSQL (`db_tixora`):
  - Memperbaiki query kolom `ticket_categories` di `UserController.php` dan `OwnerController.php` (menghapus referensi kolom non-eksis `harga` sesuai skema tabel).
- [x] Autentikasi teruji dan berfungsi penuh (Login, Session, Role RBAC untuk `ADMIN`, `EVENT_OWNER`, dan `USER`).
- [x] Alur Peserta / User:
  - `jelajah-acara`: Filter kategori, pencarian kata kunci, dan daftar acara terhubung database PostgreSQL.
  - `detail-acara`: Menampilkan data riil acara, rundowns, kuota dinamis, dan kategori tiket dari database.
  - `pendaftaran`: Pemesanan tiket riil dengan pencegahan duplikasi pendaftaran, row locking, dan generate kode tiket unik format `TXR-YYYYMMDD-XXXX`.
  - `tiket-saya`: Menampilkan tiket peserta yang dipisahkan berdasarkan status (Akan Datang, Selesai, Dibatalkan).
  - `tiket-digital`: Tampilan E-Ticket resmi peserta lengkap dengan QR code dan detail acara.
  - `akun`: Profil dinamis dan fitur perbarui kata sandi pengguna.
- [x] Alur Penyelenggara / Event Owner:
  - `event-owner` (Dasbor): Metrik riil (Total Acara, Acara Terbit, Pendaftar, Draf) dan daftar acara mendatang.
  - `event-owner/buat-acara`: Form pembuatan acara baru dengan opsi Simpan Draf atau Ajukan ke Admin, otomatis membuat kategori tiket default.
  - `event-owner/acara-saya`: Daftar acara dengan filter status (`SEMUA`, `DRAF`, `MENUNGGU_TINJAUAN`, `TERBIT`, `DITOLAK`).
- [x] Alur Administrator:
  - `admin` (Dasbor): Ringkasan metrik riil sistem dan daftar acara yang memerlukan tindakan moderasi.
  - `admin/antrean-acara`: Antrean acara pengajuan dari Event Owner dengan filter status.
  - `admin/tinjauan-acara`: Peninjauan kelengkapan acara dengan form keputusan (Setujui & Terbitkan atau Tolak/Minta Revisi) serta pencatatan otomatis ke `moderation_logs`.

## Hasil Pengujian Runtime
- Sintaks seluruh file PHP (`php -l` pada 64 file): **PASS**.
- Koneksi PostgreSQL (`db_tixora`): **CONNECTED & VERIFIED**.
- HTTP Web Server (PHP 8.4 CLI Server): **RUNNING (http://127.0.0.1:8000)**.
- Siklus Lifecycle End-to-End:
  1. Event Owner membuat acara baru ("Workshop Web Development Modern 2026") dengan status `MENUNGGU_TINJAUAN`: **PASS**.
  2. Admin meninjau dan menyetujui acara (`APPROVE` -> status berubah ke `TERBIT` + log moderasi): **PASS**.
  3. User mendaftar ke acara baru tersebut dan sistem menerbitkan e-ticket unik: **PASS**.
  4. Tiket baru langsung muncul di halaman Tiket Saya dan Tiket Digital User: **PASS**.
