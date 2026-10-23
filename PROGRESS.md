# Tixora — Progress pengembangan

## Batasan dan prinsip
- Database PostgreSQL yang sudah terintegrasi tidak diubah.
- `api/database.sql` hanya menjadi referensi; jangan dieksekusi pada database yang sudah berisi data.
- Semua klaim status dibedakan antara pemeriksaan statis dan pengujian runtime.
- Desain dan fitur yang sudah ada dipertahankan; perubahan struktur besar harus didasarkan pada dependensi yang sudah diperiksa.

## API dan User — pekerjaan sebelumnya
- [x] Audit struktur API dan halaman User serta identifikasi halaman statis dan endpoint yang belum dipetakan.
- [x] Perbaiki definisi `BASE_URL` untuk halaman yang memuat `ViewHelper.php`, termasuk instalasi dalam subfolder.
- [x] Pindahkan 261 deklarasi inline `style` statis dari halaman User ke satu `user/styles.css` (121 aturan CSS unik), dengan nilai deklarasi asli dipertahankan.
- [x] Daftarkan 17 route Owner yang sebelumnya belum dapat dipanggil meskipun method controller tersedia.
- [x] Lint sintaks PHP pada folder API dan User: lulus.
- [ ] Detail acara, pendaftaran, tiket saya, tiket digital, dan akun masih sebagian besar berupa markup statis dan belum boleh dianggap berfungsi.
- [ ] Endpoint scanner masih memiliki dependensi `models/Ticket.php` yang belum tersedia.
- [ ] Endpoint Owner perlu diuji dengan request nyata, role, parameter, dan response.
- [ ] Koneksi PostgreSQL dan CRUD belum diuji dalam runtime pada lingkungan kerja ini.
- [ ] Pemeriksaan CSRF dan keamanan sesi perlu dilanjutkan sebelum produksi.

## Admin — audit awal
- [x] Audit 13 file PHP dan stylesheet utama `admin/styles.css`.
- [x] Pemeriksaan sintaks 13 file PHP lulus.
- [x] Catat halaman dashboard, antrean acara, tinjauan acara/revisi, status persetujuan/penolakan, pengguna, detail pengguna, penyelenggara, laporan, notifikasi, dialog/umpan balik, dan riwayat moderasi.
- [ ] Konten yang diperiksa masih banyak berupa data statis/mockup; koneksi database dan CRUD belum terbukti.
- [ ] Aksi moderasi, pencarian/filter, logout, autentikasi dan otorisasi perlu diuji.
- [ ] Inline CSS masih perlu ditinjau dan dipindahkan dengan mempertahankan tampilan.

## Event Owner — audit awal
- [x] Pemeriksaan sintaks pada 18 halaman PHP lulus.
- [x] Tambahkan `_bootstrap.php` dan fallback `BASE_URL` pada paket hasil perbaikan `event-owner`.
- [ ] Navigasi, autentikasi, dan role belum diuji end-to-end.
- [ ] CRUD, form, dan koneksi database belum terintegrasi/terbukti pada paket folder ini.
- [ ] Pengujian browser dan request HTTP belum dilakukan.

## Root project — file yang ditinjau sekarang
- [x] Tinjau `index.php`, `README.md`, dan `PROGRESS.md`.
- [x] `index.php` lolos `php -l` setelah perubahan.
- [x] Perbaiki pembatasan akses aset statis agar file yang dibaca harus berada di dalam direktori project.
- [x] Gunakan escaping HTML yang eksplisit pada halaman 404.
- [x] Selaraskan `README.md` dengan implementasi route yang sebenarnya: folder `user/`, `admin/`, dan `event-owner/` dirujuk langsung dari root, bukan dari `pages/`.
- [ ] Pastikan seluruh target file pada tabel route benar-benar ada dalam gabungan project final.
- [ ] Uji routing pada root domain dan subfolder Laragon.
- [ ] Uji request API, autentikasi, otorisasi, dan koneksi PostgreSQL pada lingkungan runtime.

## Pekerjaan berikutnya
1. Gabungkan root, API/User, Admin, dan Event Owner ke satu struktur project aktual.
2. Cocokkan setiap route di `index.php` dengan file halaman yang benar-benar tersedia; perbaiki route yang tidak cocok.
3. Audit helper, bootstrap, session, autentikasi, dan pemeriksaan role tanpa mengubah skema database secara spekulatif.
4. Cocokkan query/controller dengan skema PostgreSQL yang sudah digunakan.
5. Implementasikan dan uji alur register → login → jelajah acara → pesan tiket → lihat tiket.
6. Implementasikan dan uji CRUD event, peserta/tiket, serta moderasi admin.
7. Tinjau CSS per area (`user/styles.css`, `event-owner/styles.css`, `admin/styles.css`) dan pindahkan inline CSS secara bertahap.
8. Jalankan pengujian sintaks seluruh PHP, lalu pengujian HTTP dan database. Catat hasil yang benar-benar dijalankan.

## Hasil pengujian yang sudah tercatat
- `php -l` untuk `index.php` pada paket ini: **PASS**.
- Pemeriksaan sintaks API/User dari tahap sebelumnya: **PASS**.
- Pemeriksaan sintaks 13 file Admin dari audit sebelumnya: **PASS**.
- Pemeriksaan sintaks 18 halaman Event Owner dari audit sebelumnya: **PASS**.
- HTTP + PostgreSQL pada lingkungan sandbox: **NOT RUN**.
- Alur browser end-to-end: **NOT RUN**.
