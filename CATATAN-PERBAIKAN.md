# Catatan Perbaikan Backend & Database — Tixora (1 Nov 2026)

Status: diperiksa statis saja (keseimbangan sintaks + pembacaan ulang). **Belum dijalankan** terhadap PHP + PostgreSQL.
Jalankan `php -l` pada file yang diubah dan uji alur di bawah sebelum presentasi.

## Langkah pasang (urut)
1. Backup database lama bila ada isi penting (`pg_dump db_tixora > backup.sql`).
2. Jalankan `api/migrations/003_constraints.sql` (001 & 002 sudah ada di DB Anda; keduanya aman dijalankan ulang).
3. Pastikan `api/config/database.local.php` ada (sudah disertakan, berisi kredensial lama). Segera ganti password DB
   dan `api/config/qr_secret.key` karena sebelumnya sempat ada di repo.
4. Hapus `database.local.php` dan `qr_secret.key` dari riwayat Git bila repo pernah publik.

## Daftar perubahan per file
| File | Perubahan |
|---|---|
| `api/controllers/OwnerController.php` | `assertOwnsEvent()` di 9 endpoint (tiket, rundown, panitia, RAB, daftar/riwayat); PIC rundown harus panitia acara yang sama; error DB tidak bocor |
| `api/controllers/UserController.php` | `registerEvent` ditulis ulang (kategori wajib, kuota kategori, tolak kategori berbayar, acara selesai, `SUDAH_PAKAI` = duplikat); `changePassword` wajib sandi lama + min 8; status publik disatukan; kode mati (`deleteAccount`, `getAllUsers`) dihapus |
| `api/controllers/AdminController.php` | `suspendUser`: validasi UUID, tidak bisa suspend diri sendiri; error tidak bocor |
| `api/controllers/AuthController.php` | duplikat email saat race -> 409; error tidak bocor |
| `api/controllers/ScannerController.php`, `api/models/Ticket.php` | check-in: cek pemilik acara, verifikasi tanda tangan QR, update atomik |
| `api/index.php` | route `POST /api/scanner/scan`; CORS dibatasi same-origin |
| `api/helpers/AuthHelper.php` | cookie session httponly/SameSite/secure; cek `is_active` tiap request |
| `api/helpers/ViewHelper.php`, `index.php` | memakai session aman; halaman web ikut cek akun aktif |
| `api/config/Database.php` | kredensial dari env / `database.local.php`; error koneksi tidak bocor |
| `api/.htaccess`, `.gitignore` | blokir `.key` dan config lokal; rahasia tidak ikut git |
| `pages/user/akun.php` | CSRF + wajib kata sandi lama |
| `pages/user/pendaftaran.php` | `SUDAH_PAKAI` dihitung sebagai tiket yang sudah dimiliki |
| `pages/admin/tinjauan-acara.php` | CSRF; hanya acara `MENUNGGU_TINJAUAN` yang bisa dimoderasi; error tidak bocor |
| `api/migrations/` | `000_reset_dev.sql` (DROP dipindah), 001 idempoten, **`003_constraints.sql`** baru, `optional_app_user.sql` |

## Checklist uji sebelum presentasi
- [ ] Login 3 akun demo (`password123`) -> masing-masing masuk ke dasbor yang benar.
- [ ] Jelajah acara -> detail acara (event seed muncul, tidak 404) -> pendaftaran -> pembayaran sandbox -> tiket digital.
- [ ] Owner A membuka `/api/owner/event-rab/<id event milik owner lain>` -> harus 404.
- [ ] Admin: setujui acara `MENUNGGU_TINJAUAN`; coba setujui lagi -> ditolak ("tidak sedang menunggu tinjauan").
- [ ] Admin suspend user -> user tersebut langsung ditolak di request berikutnya; suspend akun sendiri -> ditolak.
- [ ] `POST /api/scanner/scan` dengan kode tiket aktif -> sukses; ulang -> 409 "SUDAH DIGUNAKAN".
- [ ] Akun > Ganti Kata Sandi: sandi lama salah -> ditolak.
- [ ] Buka `/api/config/qr_secret.key` di browser -> harus 403.

## Belum dikerjakan (sengaja)
Rate-limit login API; `rundowns.waktu_*` masih VARCHAR (mengubah tipe mengubah format tampilan); validasi total kuota kategori <= kuota acara (bisa memutus wizard pembuatan acara); gateway pembayaran sungguhan; verifikasi KTM.

---

# Perubahan: kolom `roles.kode` dihapus (4 Nov 2026)

Status: diperiksa statis (pencarian sisa referensi `kode` pada peran + pembacaan ulang diff). **Belum dijalankan** terhadap PHP + PostgreSQL.

Peran sekarang dikenali **hanya lewat `roles.id` (UUID)**. Id ketiga peran bawaan bersifat tetap dan harus sama antara database dan `AuthHelper`:

| Peran | UUID | Konstanta |
|---|---|---|
| Admin Kampus | `b0000000-0000-4000-8000-000000000001` | `AuthHelper::ROLE_ADMIN` |
| Event Owner | `b0000000-0000-4000-8000-000000000002` | `AuthHelper::ROLE_EVENT_OWNER` |
| Peserta | `b0000000-0000-4000-8000-000000000003` | `AuthHelper::ROLE_USER` |

## Langkah pasang
- **Database lama (sudah berisi data):** backup, lalu jalankan HANYA `api/migrations/004_hapus_roles_kode.sql`. Migrasi memindahkan peran lama ke id tetap, mengarahkan ulang `users.role_id`, lalu menghapus kolom `kode`. Aman dijalankan ulang. Jangan menjalankan ulang 001 sebelum 004 pada database lama.
- **Database baru:** `000` (opsional) -> `001` -> `002` -> `003` -> `004` (004 tidak mengubah apa pun pada database baru).
- Semua pengguna yang sedang login harus login ulang (isi `$_SESSION['user']['role']` kini berupa UUID, bukan teks `ADMIN`/`USER`).

## Daftar perubahan
| File | Perubahan |
|---|---|
| `api/migrations/001_database.sql` | tabel `roles` tanpa `kode`; seed peran dengan id tetap; seed users memakai id peran langsung |
| `api/migrations/004_hapus_roles_kode.sql` | baru: migrasi database lama + hapus kolom `kode` |
| `api/helpers/AuthHelper.php` | konstanta `ROLE_ADMIN`, `ROLE_EVENT_OWNER`, `ROLE_USER`, `PUBLIC_ROLES` |
| `api/helpers/ViewHelper.php` | pengecekan peran memakai konstanta |
| `api/controllers/AuthController.php` | register menerima `role_id` (UUID, hanya peran publik); login tanpa JOIN `roles`; sesi/respons `role` = id peran |
| `api/controllers/{Admin,Owner,User,Scanner}Controller.php` | `checkRole`/`checkAnyRole` memakai konstanta; `getAllUsers` mengembalikan `role_id` (bukan `role_kode`) |
| `pages/user/login.php`, `register.php`, `akun.php` | memakai id peran; field form `role_kode` -> `role_id` (value UUID) |
| `pages/admin/*`, `pages/event-owner/*`, `pages/user/*` | `requireLogin(AuthHelper::ROLE_...)` |
