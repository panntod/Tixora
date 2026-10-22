# TIXORA — struktur & alur routing (PHP native)

```
tixora/
├── index.php        ← front controller: satu-satunya pintu masuk, berisi tabel route
├── .htaccess        ← (Apache/Laragon) arahkan semua URL ke index.php
└── pages/           ← file tampilan; TIDAK diakses langsung dari browser
    ├── user/
    ├── admin/
    └── event-owner/
```

## Alur
URL → `.htaccess` → `index.php` → cocokkan dengan `$routes` → `require` SATU file di `pages/`.

## Aturan
1. File di `pages/` **tidak boleh** `require`/`include` `index.php` (itu penyebab layout acak + halaman Laragon muncul).
2. Halaman baru: taruh file di `pages/...`, lalu tambahkan 1 baris di `$routes` pada `index.php`.
3. Link antarhalaman pakai `<?= BASE_URL ?>/path` supaya jalan di `localhost/tixora` maupun di domain `.test`.

## Menjalankan di Laragon
Letakkan folder di `D:\polinema\laragon\www\tixora`, buka `http://localhost/tixora/`.
Contoh URL: `/tixora/jelajah-acara`, `/tixora/login`, `/tixora/admin`, `/tixora/event-owner/acara-saya`.
Pastikan `mod_rewrite` aktif (default Laragon sudah aktif).
