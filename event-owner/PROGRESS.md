# TIXORA — Progress Integrasi

Tanggal audit: 2026-10-23

## Cakupan yang diterima
- Folder `event-owner/` saja, berisi 18 halaman PHP dan `styles.css`.
- Belum ada file konfigurasi utama, autentikasi, koneksi database, atau skema database pada arsip ini.

## Perubahan yang dilakukan
- [x] Menambahkan `_bootstrap.php` dengan fallback `BASE_URL` untuk mencegah konstanta URL tidak terdefinisi pada instalasi standalone.
- [x] Memasukkan bootstrap pada 18 halaman PHP; nilai `BASE_URL` dari aplikasi utama tetap dipakai jika sudah didefinisikan.
- [x] Mempertahankan halaman dan CSS yang ada.
- [x] Menjalankan pemeriksaan sintaks PHP untuk seluruh halaman.

## Hasil audit saat ini
- [x] 18 file PHP lolos `php -l` setelah perubahan.
- [x] Satu file CSS utama tersedia: `event-owner/styles.css`.
- [ ] Navigasi sidebar belum benar-benar menjadi tautan; label menu masih berupa elemen teks.
- [ ] Semua halaman masih tampak sebagai template/statis: tidak ditemukan form HTML pada 18 halaman.
- [ ] Tidak ditemukan integrasi PDO, MySQLi, PostgreSQL, atau include koneksi database di folder ini.
- [ ] Inline CSS masih cukup banyak (perkiraan awal: ratusan atribut `style`); belum dipindahkan otomatis karena pemetaan CSS tanpa pengujian visual berisiko mengubah desain.
- [ ] Belum bisa memverifikasi autentikasi/otorisasi event-owner.
- [ ] Belum bisa menguji CRUD, pemesanan, upload, dan penyimpanan data tanpa konfigurasi dan skema database.
- [ ] Belum ada pengujian browser atau alur end-to-end.

## File yang diperiksa
- `index.php`
- `acara-saya/index.php`
- `buat-acara/index.php`
- `buat-acara/buat-acara-tiket.php`
- `buat-acara/buat-acara-rundown.php`
- `buat-acara/buat-acara-tinjau.php`
- `edit-informasi/index.php`
- `pengaturan-acara/index.php`
- `ringkasan/index.php`
- `peserta/index.php`
- `tiket/index.php`
- `rundown/index.php`
- `rab/index.php`
- `panitia/index.php`
- `analitik/index.php`
- `pengajuan/index.php`
- `dialog-publikasi/index.php`
- `dialog-dan-kondisi/index.php`

## Pekerjaan berikutnya
1. Audit folder root project untuk menemukan bootstrap, konfigurasi `BASE_URL`, session/auth, dan koneksi database.
2. Cocokkan halaman event-owner dengan skema database yang sebenarnya sebelum menulis query atau mengubah skema.
3. Hubungkan menu sidebar ke rute yang benar dan tambahkan pemeriksaan role/ownership.
4. Implementasikan CRUD bertahap berdasarkan tabel yang tersedia.
5. Refactor inline CSS menjadi class di `event-owner/styles.css`, kemudian uji visual.
6. Uji alur create/edit/publish event, tiket, rundown, peserta, panitia, RAB, analitik, dan pengajuan dengan database uji.
