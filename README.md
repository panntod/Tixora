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

## Alur login sampai pembayaran

```text
login -> jelajah-acara -> detail-acara -> pendaftaran -> pembayaran -> konfirmasi -> tiket-digital
```

| Route | Fungsi |
| --- | --- |
| `/login`, `/register` | Form dilindungi token CSRF; 5 kali gagal login mengunci 60 detik; setelah login peserta kembali ke halaman yang dituju; peran ADMIN tidak bisa didaftarkan dari form. |
| `/lupa-kata-sandi`, `/lupa-kata-sandi/terkirim`, `/atur-ulang-kata-sandi` | Reset kata sandi dengan token sekali pakai (hash SHA-256, berlaku 60 menit). Di localhost tautan ditampilkan di halaman "terkirim"; di server lain dikirim lewat `mail()` (butuh konfigurasi email server). |
| `/detail-acara` | Harga dan sisa kursi per kategori tiket dari database. |
| `/pendaftaran` | Kuota acara dan kategori dicek dengan row locking; harga selalu dibaca ulang dari database. Tiket gratis langsung `AKTIF`; tiket berbayar `MENUNGGU_PEMBAYARAN` dan membuat pesanan. |
| `/pembayaran?order=ORD-...` | Pilih metode, nomor VA/tagihan, hitung mundur, batalkan pesanan. |
| `/konfirmasi?order=...` / `?ticket=...` | Ringkasan setelah pembayaran berhasil / pendaftaran gratis. |
| `/tiket-digital/menunggu-pembayaran?id=...` | Tiket yang belum dibayar (QR belum aktif). |

### Pembayaran

- Kursi ditahan 24 jam (`PaymentHelper::PAYMENT_WINDOW_HOURS`). Pesanan yang lewat batas otomatis `KEDALUWARSA` dan tiketnya `BATAL` sehingga kursi kembali tersedia.
- `PaymentHelper::SANDBOX = true`: pembayaran **disimulasikan** lewat tombol "Simulasikan pembayaran berhasil". Untuk gateway sungguhan (Midtrans/Xendit), panggil `PaymentHelper::markPaid()` dari endpoint webhook setelah memverifikasi tanda tangan notifikasi, lalu set `SANDBOX` ke `false`.
- Tiket berbayar tidak bisa dipesan lewat `POST /api/user/register-event` (endpoint itu hanya untuk tiket gratis).

### Perubahan database (hanya menambah)

`api/migrations/002_payment.sql` menambah kolom `ticket_categories.harga`, tabel `payments`, dan tabel `password_resets`, serta memberi harga contoh pada kategori seed. Aplikasi menjalankannya otomatis satu kali bila kolom `harga` belum ada (`PaymentHelper::ensureSchema`); file ini juga aman dijalankan manual berulang kali. Tabel dan data yang sudah ada tidak diubah atau dihapus.
