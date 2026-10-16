# TIXORA — migrasi HTML ke PHP

## Isi
- `index.php`: front controller yang memetakan URL ke layar PHP.
- `pages/user/*.php`: 19 layar HTML asli, dipertahankan tampilannya dan disimpan sebagai file PHP.
- Folder route (mis. `login/`, `detail-acara/selesai/`) berisi `index.php` kecil agar URL bersih dapat dikenali ketika server mencari index direktori.

## Cara menjalankan di Laravel Herd
1. Ekstrak folder proyek ini ke folder yang dipakai Herd.
2. Hubungkan/park folder proyek lewat Herd, sehingga domain misalnya `tixora.test` menunjuk ke root folder yang berisi `index.php`.
3. Buka `http://tixora.test/`.

## URL yang tersedia
- `/`
- `/jelajah-acara`
- `/detail-acara`
- `/masuk-daftar`
- `/login` (alias ke layar masuk/daftar gabungan)
- `/register` (alias ke layar masuk/daftar gabungan)
- `/pendaftaran`
- `/konfirmasi`
- `/tiket-saya`
- `/tiket-digital`
- `/akun`
- `/kondisi-antarmuka`
- `/riwayat-pendaftaran`
- `/detail-acara/selesai`
- `/detail-acara/dibatalkan`
- `/detail-acara/tiket-habis`
- `/tiket-digital/sudah-dipakai`
- `/tiket-digital/dibatalkan`
- `/tiket-digital/menunggu-pembayaran`
- `/lupa-kata-sandi`
- `/lupa-kata-sandi/terkirim`

## Catatan
- Tampilan asli dipertahankan. File HTML diubah menjadi PHP, tetapi kontennya masih UI statis.
- Form login/register/lupa kata sandi dan tombol belum terhubung ke autentikasi atau database.
- Route saat ini adalah layar pengguna. Route admin dan event-owner belum termasuk karena ZIP yang diberikan hanya berisi layar pengguna.
- `.htaccess` tidak diperlukan untuk routing ini pada Herd; Herd memakai Nginx. Folder route dengan `index.php` membantu melayani URL bersih tanpa bergantung pada `.htaccess`.


## Admin pages

Admin HTML screens from `Admin.zip` have been migrated to PHP and are available through the central router in `index.php`.

| URL | Screen |
|---|---|
| `/admin` | Dasbor admin |
| `/admin/antrean-acara` | Antrean acara |
| `/admin/tinjauan-acara` | Tinjauan acara |
| `/admin/dialog-dan-umpan-balik` | Dialog dan umpan balik |
| `/admin/setelah-disetujui` | Setelah disetujui |
| `/admin/setelah-ditolak` | Setelah ditolak |
| `/admin/tinjauan-revisi` | Tinjauan revisi |
| `/admin/pengguna` | Pengguna |
| `/admin/detail-pengguna` | Detail pengguna |
| `/admin/penyelenggara` | Penyelenggara |
| `/admin/laporan` | Laporan |
| `/admin/notifikasi` | Notifikasi |
| `/admin/riwayat-moderasi` | Riwayat moderasi |

Admin views are currently static presentation prototypes. Sidebar links to the main admin sections are wired up, but actions such as approve/reject, notifications, user management, and database persistence still need backend logic and role-based authorization. Herd uses Nginx, so this project routes URLs through `index.php`; `.htaccess` is not responsible for routing.


## Event Owner / Organizer

Layar organizer dari `Organization.zip` telah ditambahkan ke `pages/organization/` dan tersedia melalui URL berikut:

| URL | Layar |
|---|---|
| `/event-owner` | Dasbor |
| `/event-owner/acara-saya` | Acara Saya |
| `/event-owner/buat-acara` | Buat Acara |
| `/event-owner/ringkasan` | Ringkasan Acara |
| `/event-owner/tiket` | Tiket |
| `/event-owner/rundown` | Rundown |
| `/event-owner/peserta` | Peserta |
| `/event-owner/rab` | RAB |
| `/event-owner/panitia` | Panitia |
| `/event-owner/analitik` | Analitik |
| `/event-owner/pengajuan` | Pengajuan |
| `/event-owner/buat-acara/tiket` | Buat Acara: Tiket |
| `/event-owner/buat-acara/rundown` | Buat Acara: Rundown |
| `/event-owner/buat-acara/tinjau` | Buat Acara: Tinjau dan Ajukan |
| `/event-owner/dialog-dan-kondisi` | Dialog dan Kondisi |
| `/event-owner/edit-informasi` | Edit Informasi Acara |
| `/event-owner/pengaturan-acara` | Pengaturan Acara |
| `/event-owner/dialog-publikasi` | Dialog Penerbitan dan Pembatalan |

Layar-layar ini mempertahankan HTML/CSS asli dalam file `.php`. Aksi tombol dan data organizer masih berupa prototipe, belum terhubung ke database atau otorisasi akun. Folder route dengan `index.php` juga disediakan agar URL bersih bisa ditemukan Herd.
