# TIXORA — Struktur dan alur routing (PHP native)

## Struktur yang digunakan oleh `index.php`

```text
tixora/
├── index.php
├── .htaccess
├── api/
│   └── index.php
├── user/
├── admin/
├── event-owner/
└── PROGRESS.md
```

Folder `user/`, `admin/`, dan `event-owner/` berada langsung di root project pada struktur yang saat ini dirujuk oleh tabel `$routes` di `index.php`. README ini tidak menggunakan folder `pages/`, karena `PAGES_DIR` saat ini menunjuk ke direktori tempat `index.php` berada.

## Alur request halaman

1. Browser meminta URL, misalnya `/tixora/jelajah-acara`.
2. Apache/Laragon meneruskan URL bersih ke `index.php` melalui `.htaccess`.
3. `index.php` menghapus prefix subfolder project dari URL.
4. Router mencocokkan path dengan `$routes`.
5. Router memuat satu file halaman yang sesuai. Route yang tidak terdaftar menghasilkan HTTP 404.

Request `/api/...` diteruskan ke `api/index.php`. Aset statis yang ditangani front controller hanya akan dibaca jika file berada di dalam direktori project.

## Aturan pengembangan

- Jangan mengubah skema database tanpa meninjau dependensi dan skema yang sedang digunakan.
- Jangan membuat halaman langsung meng-include `index.php`; `index.php` adalah front controller.
- Untuk menambahkan halaman, pastikan file benar-benar tersedia di lokasi yang sesuai, lalu daftarkan route di `$routes`.
- Gunakan `<?= BASE_URL ?>/nama-route` untuk tautan internal agar dapat berjalan di subfolder seperti `/tixora` maupun pada virtual host.
- Pastikan nama route dan path file sama dengan struktur folder sebenarnya.
- Jangan menganggap halaman atau aksi berfungsi hanya karena sintaks PHP lolos pemeriksaan; uji request HTTP dan alur database secara terpisah.

## Menjalankan di Laragon

1. Letakkan project di `D:\polinema\laragon\www\tixora` (sesuaikan dengan lokasi Laragon kamu).
2. Pastikan Apache dan `mod_rewrite` aktif.
3. Buka `http://localhost/tixora/`.

Contoh route: `/tixora/jelajah-acara`, `/tixora/login`, `/tixora/admin`, dan `/tixora/event-owner/acara-saya`.

## Status pengujian

- Pemeriksaan sintaks `index.php` dilakukan setelah perubahan.
- Pengujian HTTP, autentikasi, dan koneksi PostgreSQL harus dilakukan di lingkungan project yang menjalankan server dan database sebenarnya.

> CARA RUN : php -S 127.0.0.1:8000 -t /Users/pandhuarya/Projects/tixora /Users/pandhuarya/Projects/tixora/index.php