<?php
declare(strict_types=1);

/**
 * TIXORA front controller.
 */
$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
$path = "/" . trim($path, "/");
if ($path === "//") {
  $path = "/";
}

$routes = [
  "/" => __DIR__ . "/pages/user/landing-page.php",
  "/jelajah-acara" => __DIR__ . "/pages/user/jelajah-acara.php",
  "/detail-acara" => __DIR__ . "/pages/user/detail-acara.php",
  "/masuk-daftar" => __DIR__ . "/pages/user/masuk-daftar.php",
  "/pendaftaran" => __DIR__ . "/pages/user/pendaftaran.php",
  "/konfirmasi" => __DIR__ . "/pages/user/konfirmasi.php",
  "/tiket-saya" => __DIR__ . "/pages/user/tiket-saya.php",
  "/tiket-digital" => __DIR__ . "/pages/user/tiket-digital.php",
  "/akun" => __DIR__ . "/pages/user/akun.php",
  "/kondisi-antarmuka" => __DIR__ . "/pages/user/kondisi-antarmuka.php",
  "/riwayat-pendaftaran" => __DIR__ . "/pages/user/riwayat-pendaftaran.php",
  "/detail-acara/selesai" => __DIR__ . "/pages/user/detail-acara-selesai.php",
  "/detail-acara/dibatalkan" =>
    __DIR__ . "/pages/user/detail-acara-dibatalkan.php",
  "/detail-acara/tiket-habis" =>
    __DIR__ . "/pages/user/detail-acara-tiket-habis.php",
  "/tiket-digital/sudah-dipakai" =>
    __DIR__ . "/pages/user/tiket-digital-sudah-dipakai.php",
  "/tiket-digital/dibatalkan" =>
    __DIR__ . "/pages/user/tiket-digital-dibatalkan.php",
  "/tiket-digital/menunggu-pembayaran" =>
    __DIR__ . "/pages/user/tiket-digital-menunggu-pembayaran.php",
  "/lupa-kata-sandi" => __DIR__ . "/pages/user/lupa-kata-sandi.php",
  "/lupa-kata-sandi/terkirim" =>
    __DIR__ . "/pages/user/lupa-kata-sandi-terkirim.php",
  // Alias URL untuk layar masuk/daftar gabungan pada desain HTML asli
  "/login" => __DIR__ . "/pages/user/masuk.php",
  "/register" => __DIR__ . "/pages/user/daftar.php",

  // Event owner / organizer TIXORA
  "/event-owner" => __DIR__ . "/pages/organization/index.php",
  "/event-owner/acara-saya" => __DIR__ . "/pages/organization/acara-saya.php",
  "/event-owner/buat-acara" => __DIR__ . "/pages/organization/buat-acara.php",
  "/event-owner/ringkasan" => __DIR__ . "/pages/organization/ringkasan.php",
  "/event-owner/tiket" => __DIR__ . "/pages/organization/tiket.php",
  "/event-owner/rundown" => __DIR__ . "/pages/organization/rundown.php",
  "/event-owner/peserta" => __DIR__ . "/pages/organization/peserta.php",
  "/event-owner/rab" => __DIR__ . "/pages/organization/rab.php",
  "/event-owner/panitia" => __DIR__ . "/pages/organization/panitia.php",
  "/event-owner/analitik" => __DIR__ . "/pages/organization/analitik.php",
  "/event-owner/pengajuan" => __DIR__ . "/pages/organization/pengajuan.php",
  "/event-owner/buat-acara/tiket" =>
    __DIR__ . "/pages/organization/buat-acara-tiket.php",
  "/event-owner/buat-acara/rundown" =>
    __DIR__ . "/pages/organization/buat-acara-rundown.php",
  "/event-owner/buat-acara/tinjau" =>
    __DIR__ . "/pages/organization/buat-acara-tinjau.php",
  "/event-owner/dialog-dan-kondisi" =>
    __DIR__ . "/pages/organization/dialog-dan-kondisi.php",
  "/event-owner/edit-informasi" =>
    __DIR__ . "/pages/organization/edit-informasi.php",
  "/event-owner/pengaturan-acara" =>
    __DIR__ . "/pages/organization/pengaturan-acara.php",
  "/event-owner/dialog-publikasi" =>
    __DIR__ . "/pages/organization/dialog-publikasi.php",

  // Admin TIXORA
  "/admin" => __DIR__ . "/pages/admin/index.php",
  "/admin/antrean-acara" => __DIR__ . "/pages/admin/antrean-acara.php",
  "/admin/tinjauan-acara" => __DIR__ . "/pages/admin/tinjauan-acara.php",
  "/admin/dialog-dan-umpan-balik" =>
    __DIR__ . "/pages/admin/dialog-dan-umpan-balik.php",
  "/admin/setelah-disetujui" =>
    __DIR__ . "/pages/admin/setelah-disetujui.php",
  "/admin/setelah-ditolak" => __DIR__ . "/pages/admin/setelah-ditolak.php",
  "/admin/tinjauan-revisi" => __DIR__ . "/pages/admin/tinjauan-revisi.php",
  "/admin/pengguna" => __DIR__ . "/pages/admin/pengguna.php",
  "/admin/detail-pengguna" => __DIR__ . "/pages/admin/detail-pengguna.php",
  "/admin/penyelenggara" => __DIR__ . "/pages/admin/penyelenggara.php",
  "/admin/laporan" => __DIR__ . "/pages/admin/laporan.php",
  "/admin/notifikasi" => __DIR__ . "/pages/admin/notifikasi.php",
  "/admin/riwayat-moderasi" => __DIR__ . "/pages/admin/riwayat-moderasi.php",
];

$file = $routes[$path] ?? null;

if ($file !== null && is_file($file)) {
  require $file;
  exit();
}

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>404 — TIXORA</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 24px;
      background: #FAFAFC;
      color: #0E1128;
      font-family: Arial, Helvetica, sans-serif;
    }

    main {
      max-width: 520px;
      text-align: center;
    }

    h1 {
      margin: 0 0 12px;
      font-size: 48px;
    }

    p {
      color: #4B5170;
      line-height: 1.6;
    }

    a {
      color: #4B34DB;
      font-weight: 700;
    }
  </style>
</head>

<body>
  <main>
    <h1>404</h1>
    <p>Halaman yang kamu cari tidak ditemukan.</p>
    <a href="/">Kembali ke halaman utama</a>
  </main>
</body>

</html>