<?php
/**
 * TIXORA — Front Controller (satu-satunya pintu masuk aplikasi)
 *
 * Alur:
 *   1. Browser membuka URL, mis. localhost/tixora/jelajah-acara
 *   2. .htaccess mengarahkan semua URL (yang bukan file/folder asli) ke file ini
 *   3. File ini membaca path URL, mencocokkannya dengan tabel $routes
 *   4. Jika cocok  -> file di folder pages/ di-include (HANYA SATU file)
 *      Jika tidak  -> tampilkan 404
 *
 * Aturan penting: file di pages/ TIDAK BOLEH memanggil require ke index.php ini.
 * Router yang memanggil halaman, bukan sebaliknya.
 */

// Folder tempat proyek berada, dihitung dari lokasi file ini terhadap document root
// (mis. "/tixora_fixed" di Laragon, "" di Herd). Tidak bergantung pada SCRIPT_NAME.
$docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']), '/');
$dir = str_replace('\\', '/', __DIR__);
if ($docRoot !== '' && stripos($dir, $docRoot) === 0) {
    $base = substr($dir, strlen($docRoot));
} else {
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
}
$base = '/' . trim($base, '/');
if ($base === '/') {
    $base = '';
}
define('BASE_URL', $base);
define('PAGES_DIR', __DIR__ . '/');

// Inisialisasi session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil path bersih: buang query string, folder proyek, dan "index.php".
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
if ($base !== '' && stripos($path, $base) === 0) {
    $path = substr($path, strlen($base));
}
$path = trim($path, '/');
$path = preg_replace('#^index\.php(/|$)#', '', $path);

// Handle file statis jika menggunakan PHP built-in server
if (preg_match('#\.(css|js|png|jpg|jpeg|gif|svg|ico|webp)$#i', $path)) {
    $filePath = __DIR__ . '/' . $path;
    if (file_exists($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'webp' => 'image/webp'
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'text/plain'));
        readfile($filePath);
        exit;
    }
}

// Handle route logout
if ($path === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// Delegasikan API request ke api/index.php
if (str_starts_with($path, 'api/') || $path === 'api') {
    require __DIR__ . '/api/index.php';
    exit;
}

// Tabel route: URL bersih => file halaman (relatif terhadap folder pages/)
$routes = [
    '' => 'user/landing-page.php',
    'admin' => 'admin/index.php',
    'admin/antrean-acara' => 'admin/antrean-acara/index.php',
    'admin/detail-pengguna' => 'admin/detail-pengguna/index.php',
    'admin/dialog-dan-umpan-balik' => 'admin/dialog-dan-umpan-balik/index.php',
    'admin/laporan' => 'admin/laporan/index.php',
    'admin/notifikasi' => 'admin/notifikasi/index.php',
    'admin/pengguna' => 'admin/pengguna/index.php',
    'admin/penyelenggara' => 'admin/penyelenggara/index.php',
    'admin/riwayat-moderasi' => 'admin/riwayat-moderasi/index.php',
    'admin/setelah-disetujui' => 'admin/setelah-disetujui/index.php',
    'admin/setelah-ditolak' => 'admin/setelah-ditolak/index.php',
    'admin/tinjauan-acara' => 'admin/tinjauan-acara/index.php',
    'admin/tinjauan-revisi' => 'admin/tinjauan-revisi/index.php',
    'akun' => 'user/akun/index.php',
    'atur-ulang-kata-sandi' => 'user/atur-ulang-kata-sandi/index.php',
    'detail-acara' => 'user/detail-acara/index.php',
    'detail-acara/dibatalkan' => 'user/detail-acara/dibatalkan/index.php',
    'detail-acara/selesai' => 'user/detail-acara/selesai/index.php',
    'detail-acara/tiket-habis' => 'user/detail-acara/tiket-habis/index.php',
    'event-owner' => 'event-owner/index.php',
    'event-owner/acara-saya' => 'event-owner/acara-saya/index.php',
    'event-owner/analitik' => 'event-owner/analitik/index.php',
    'event-owner/buat-acara' => 'event-owner/buat-acara/index.php',
    'event-owner/buat-acara/rundown' => 'event-owner/buat-acara/buat-acara-rundown.php',
    'event-owner/buat-acara/tiket' => 'event-owner/buat-acara/buat-acara-tiket.php',
    'event-owner/buat-acara/tinjau' => 'event-owner/buat-acara/buat-acara-tinjau.php',
    'event-owner/dialog-dan-kondisi' => 'event-owner/dialog-dan-kondisi/index.php',
    'event-owner/dialog-publikasi' => 'event-owner/dialog-publikasi/index.php',
    'event-owner/edit-informasi' => 'event-owner/edit-informasi/index.php',
    'event-owner/panitia' => 'event-owner/panitia/index.php',
    'event-owner/pengajuan' => 'event-owner/pengajuan/index.php',
    'event-owner/pengaturan-acara' => 'event-owner/pengaturan-acara/index.php',
    'event-owner/peserta' => 'event-owner/peserta/index.php',
    'event-owner/rab' => 'event-owner/rab/index.php',
    'event-owner/ringkasan' => 'event-owner/ringkasan/index.php',
    'event-owner/rundown' => 'event-owner/rundown/index.php',
    'event-owner/tiket' => 'event-owner/tiket/index.php',
    'jelajah-acara' => 'user/jelajah-acara/index.php',
    'kondisi-antarmuka' => 'user/kondisi-antarmuka/index.php',
    'konfirmasi' => 'user/konfirmasi/index.php',
    'login' => 'user/login/index.php',
    'lupa-kata-sandi' => 'user/lupa-kata-sandi/index.php',
    'lupa-kata-sandi/terkirim' => 'user/lupa-kata-sandi/terkirim/index.php',
    'masuk-daftar' => 'user/masuk-daftar/index.php',
    'pembayaran' => 'user/pembayaran/index.php',
    'pendaftaran' => 'user/pendaftaran/index.php',
    'register' => 'user/register/index.php',
    'riwayat-pendaftaran' => 'user/riwayat-pendaftaran/index.php',
    'tiket-digital' => 'user/tiket-digital/index.php',
    'tiket-digital/dibatalkan' => 'user/tiket-digital/dibatalkan/index.php',
    'tiket-digital/menunggu-pembayaran' => 'user/tiket-digital/menunggu-pembayaran/index.php',
    'tiket-digital/sudah-dipakai' => 'user/tiket-digital/sudah-dipakai/index.php',
    'tiket-saya' => 'user/tiket-saya/index.php',
];

if (!isset($routes[$path])) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="id"><meta charset="utf-8"><title>404 — TIXORA</title>'
        . '<body style="font-family:Arial,sans-serif;text-align:center;padding:96px 16px">'
        . '<h1>404</h1><p>Halaman tidak ditemukan.</p>'
        . '<a href="' . BASE_URL . '/">Kembali ke beranda</a>'
        . '<p style="color:#888;font-size:12px;margin-top:32px">path: "' . htmlspecialchars($path)
        . '" | base: "' . htmlspecialchars(BASE_URL) . '" | uri: ' . htmlspecialchars($_SERVER['REQUEST_URI'])
        . '</p></body></html>';
    exit;
}

require PAGES_DIR . $routes[$path];