<?php

declare(strict_types=1);

/**
 * TIXORA — Front Controller
 *
 * Centralized routing untuk seluruh halaman aplikasi.
 *
 * Struktur:
 *
 * TIXORA/
 * ├── api/
 * │   └── index.php
 * ├── assets/
 * ├── pages/
 * │   ├── admin/
 * │   ├── event-owner/
 * │   └── user/
 * ├── .htaccess
 * └── index.php
 *
 * Alur:
 * 1. Browser mengakses URL.
 * 2. Apache meneruskan request ke index.php.
 * 3. Router mencocokkan URL dengan daftar route.
 * 4. Router memuat satu file halaman dari pages/.
 *
 * Catatan:
 * - File halaman tidak boleh memanggil front controller ini.
 * - API tetap menggunakan api/index.php.
 * - Konfigurasi database tidak diubah.
 */


/*
|--------------------------------------------------------------------------
| APPLICATION CONFIGURATION
|--------------------------------------------------------------------------
*/

define('ROOT_DIR', __DIR__);
define('PAGES_DIR', ROOT_DIR . '/pages/');
define('ASSETS_DIR', ROOT_DIR . '/assets/');


/*
|--------------------------------------------------------------------------
| BASE URL DETECTION
|--------------------------------------------------------------------------
|
| Mendukung:
|
| Laragon:
| http://localhost/tixora/
| BASE_URL = /tixora
|
| Virtual Host:
| http://tixora.test/
| BASE_URL = ""
|
*/

$documentRoot = realpath(
    $_SERVER['DOCUMENT_ROOT'] ?? ROOT_DIR
);

$projectRoot = realpath(ROOT_DIR);

$documentRoot = str_replace(
    '\\',
    '/',
    $documentRoot ?: ROOT_DIR
);

$projectRoot = str_replace(
    '\\',
    '/',
    $projectRoot ?: ROOT_DIR
);

$documentRoot = rtrim($documentRoot, '/');
$projectRoot = rtrim($projectRoot, '/');

$base = '';

if (
    $projectRoot === $documentRoot ||
    str_starts_with(
        $projectRoot,
        $documentRoot . '/'
    )
) {
    $base = substr(
        $projectRoot,
        strlen($documentRoot)
    );
} else {
    $scriptDirectory = dirname(
        $_SERVER['SCRIPT_NAME'] ?? '/'
    );

    $base = $scriptDirectory === '/'
        ? ''
        : $scriptDirectory;
}

$base = '/' . trim($base, '/');

if ($base === '/') {
    $base = '';
}

define('BASE_URL', $base);


/*
|--------------------------------------------------------------------------
| SESSION INITIALIZATION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_DIR . '/api/helpers/ViewHelper.php';


/*
|--------------------------------------------------------------------------
| REQUEST PATH NORMALIZATION
|--------------------------------------------------------------------------
*/

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

$requestPath = parse_url(
    $requestUri,
    PHP_URL_PATH
);

$requestPath = rawurldecode(
    $requestPath ?: '/'
);

// Hilangkan base URL aplikasi.
if (
    BASE_URL !== '' &&
    (
        $requestPath === BASE_URL ||
        str_starts_with(
            $requestPath,
            BASE_URL . '/'
        )
    )
) {
    $requestPath = substr(
        $requestPath,
        strlen(BASE_URL)
    );
}

$path = trim($requestPath, '/');

// Mendukung akses melalui /index.php.
$path = preg_replace(
    '#^index\.php(?:/|$)#',
    '',
    $path
);

$path = trim($path, '/');

// Tolak path yang mengandung null byte atau backslash.
if (
    str_contains($path, "\0") ||
    str_contains($path, '\\')
) {
    http_response_code(400);
    exit('400 - Bad Request');
}

// Normalisasi trailing slash sudah dilakukan oleh trim().
// Query parameter tetap tersedia melalui $_GET.


/*
|--------------------------------------------------------------------------
| STATIC FILE HANDLER
|--------------------------------------------------------------------------
|
| Fallback untuk PHP built-in server.
|
| Pada Apache, file statis sebaiknya dilayani langsung.
|
*/

$staticMimeTypes = [
    'css' => 'text/css; charset=utf-8',
    'js' => 'application/javascript; charset=utf-8',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
    'ico' => 'image/x-icon',
    'webp' => 'image/webp',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
];

$extension = strtolower(
    pathinfo($path, PATHINFO_EXTENSION)
);

if (isset($staticMimeTypes[$extension])) {
    $resolvedFile = false;
    $rootDirReal = realpath(ROOT_DIR);
    $pagesDirReal = realpath(PAGES_DIR);

    // 1. Cek langsung dari ROOT_DIR (misal assets/logo.png atau pages/user/styles.css)
    $candidateRoot = realpath(ROOT_DIR . '/' . $path);
    if (
        $candidateRoot !== false &&
        $rootDirReal !== false &&
        str_starts_with($candidateRoot, rtrim($rootDirReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) &&
        is_file($candidateRoot)
    ) {
        $resolvedFile = $candidateRoot;
    }

    // 2. Cek fallback di PAGES_DIR (misal user/styles.css -> pages/user/styles.css)
    if (!$resolvedFile) {
        $candidatePages = realpath(PAGES_DIR . $path);
        if (
            $candidatePages !== false &&
            $pagesDirReal !== false &&
            str_starts_with($candidatePages, rtrim($pagesDirReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) &&
            is_file($candidatePages)
        ) {
            $resolvedFile = $candidatePages;
        }
    }

    if ($resolvedFile) {
        header(
            'Content-Type: ' .
            $staticMimeTypes[$extension]
        );

        readfile($resolvedFile);
        exit;
    }

    http_response_code(404);
    exit('404 - Asset not found');
}


/*
|--------------------------------------------------------------------------
| LOGOUT ROUTE
|--------------------------------------------------------------------------
|
| Mempertahankan mekanisme logout berbasis PHP session.
|
*/

if ($path === 'logout') {

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();

    header(
        'Location: ' . BASE_URL . '/login',
        true,
        302
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| API ROUTING
|--------------------------------------------------------------------------
|
| Semua request /api ditangani oleh api/index.php.
|
| Tidak mengubah konfigurasi maupun koneksi database.
|
*/

if (
    $path === 'api' ||
    str_starts_with($path, 'api/')
) {
    require ROOT_DIR . '/api/index.php';
    exit;
}


/*
|--------------------------------------------------------------------------
| ROUTE REGISTRY
|--------------------------------------------------------------------------
|
| Format:
|
| 'public-url' => 'relative-file-path'
|
| Contoh:
|
| /admin/pengguna
| -> pages/admin/pengguna.php
|
| /event-owner/acara-saya
| -> pages/event-owner/acara-saya.php
|
*/

$routes = [

    /*
    |--------------------------------------------------------------------------
    | PUBLIC / USER
    |--------------------------------------------------------------------------
    */

    // Landing Page
    '' => 'user/landing-page.php',

    // Authentication
    'login' => 'user/login.php',
    'register' => 'user/register.php',
    'masuk' => 'user/masuk.php',
    'pendaftaran' => 'user/pendaftaran.php',

    // Password Recovery
    'lupa-kata-sandi' =>
        'user/lupa-kata-sandi/index.php',

    'lupa-kata-sandi/terkirim' =>
        'user/lupa-kata-sandi/terkirim/index.php',

    'atur-ulang-kata-sandi' =>
        'user/atur-ulang-kata-sandi.php',

    // Event Exploration
    'jelajah-acara' =>
        'user/jelajah-acara.php',

    // Event Details
    'detail-acara' =>
        'user/detail-acara.php',

    // Account
    'akun' =>
        'user/akun.php',

    // Tickets
    'tiket-saya' =>
        'user/tiket-saya.php',

    'tiket-digital' =>
        'user/tiket-digital.php',

    // E-ticket publik (tujuan QR Code, tanpa login, wajib tanda tangan valid)
    'e-ticket' =>
        'user/e-ticket.php',

    // Transactions
    'pembayaran' =>
        'user/pembayaran.php',

    'konfirmasi' =>
        'user/konfirmasi.php',

    'riwayat-pendaftaran' =>
        'user/riwayat-pendaftaran.php',

    // UI States
    'kondisi-antarmuka' =>
        'user/kondisi-antarmuka.php',


    /*
    |--------------------------------------------------------------------------
    | EVENT OWNER
    |--------------------------------------------------------------------------
    */

    // Dashboard
    'event-owner' =>
        'event-owner/index.php',

    // Event Management
    'event-owner/acara-saya' =>
        'event-owner/acara-saya.php',

    'event-owner/edit-informasi' =>
        'event-owner/edit-informasi.php',

    'event-owner/pengaturan-acara' =>
        'event-owner/pengaturan-acara.php',

    // Create Event
    'event-owner/buat-acara' =>
        'event-owner/buat-acara/index.php',

    'event-owner/buat-acara/rundown' =>
        'event-owner/buat-acara/buat-acara-rundown.php',

    'event-owner/buat-acara/tiket' =>
        'event-owner/buat-acara/buat-acara-tiket.php',

    'event-owner/buat-acara/tinjau' =>
        'event-owner/buat-acara/buat-acara-tinjau.php',

    // Event Operations
    'event-owner/rundown' =>
        'event-owner/rundown.php',

    'event-owner/tiket' =>
        'event-owner/tiket.php',

    'event-owner/peserta' =>
        'event-owner/peserta.php',

    'event-owner/panitia' =>
        'event-owner/panitia.php',

    'event-owner/rab' =>
        'event-owner/rab.php',

    // Submission & Publication
    'event-owner/pengajuan' =>
        'event-owner/pengajuan.php',

    'event-owner/dialog-publikasi' =>
        'event-owner/dialog-publikasi.php',

    'event-owner/dialog-dan-kondisi' =>
        'event-owner/dialog-dan-kondisi.php',

    // Analytics
    'event-owner/analitik' =>
        'event-owner/analitik.php',

    'event-owner/ringkasan' =>
        'event-owner/ringkasan.php',


    /*
    |--------------------------------------------------------------------------
    | ADMIN
    |--------------------------------------------------------------------------
    */

    // Dashboard
    'admin' =>
        'admin/index.php',

    // Event Moderation
    'admin/antrean-acara' =>
        'admin/antrean-acara.php',

    'admin/tinjauan-acara' =>
        'admin/tinjauan-acara.php',

    'admin/tinjauan-revisi' =>
        'admin/tinjauan-revisi.php',

    'admin/riwayat-moderasi' =>
        'admin/riwayat-moderasi.php',

    // Moderation Results
    'admin/setelah-disetujui' =>
        'admin/setelah-disetujui.php',

    'admin/setelah-ditolak' =>
        'admin/setelah-ditolak.php',

    // User Management
    'admin/pengguna' =>
        'admin/pengguna.php',

    'admin/detail-pengguna' =>
        'admin/detail-pengguna.php',

    'admin/penyelenggara' =>
        'admin/penyelenggara.php',

    // Communication
    'admin/notifikasi' =>
        'admin/notifikasi.php',

    'admin/dialog-dan-umpan-balik' =>
        'admin/dialog-dan-umpan-balik.php',

    // Reports
    'admin/laporan' =>
        'admin/laporan.php',

];


/*
|--------------------------------------------------------------------------
| 404 NOT FOUND HANDLER
|--------------------------------------------------------------------------
*/

if (!array_key_exists($path, $routes)) {

    http_response_code(404);

    header('Content-Type: text/html; charset=utf-8');

    $homeUrl = htmlspecialchars(
        BASE_URL . '/',
        ENT_QUOTES,
        'UTF-8'
    );

    echo <<<HTML
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, initial-scale=1.0">

        <title>404 — TIXORA</title>

        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: Arial, sans-serif;
                background: #f8fafc;
                color: #0f172a;
            }

            .container {
                max-width: 480px;
                padding: 32px;
                text-align: center;
            }

            h1 {
                font-size: 96px;
                margin: 0;
                color: #2563eb;
            }

            p {
                color: #64748b;
                line-height: 1.7;
            }

            a {
                display: inline-block;
                margin-top: 24px;
                padding: 12px 24px;
                background: #2563eb;
                color: white;
                text-decoration: none;
                border-radius: 8px;
            }

            a:hover {
                background: #1d4ed8;
            }
        </style>
    </head>

    <body>
        <main class="container">
            <h1>404</h1>

            <h2>Halaman Tidak Ditemukan</h2>

            <p>
                Halaman yang kamu cari tidak tersedia
                atau telah dipindahkan.
            </p>

            <a href="{$homeUrl}">
                Kembali ke Beranda
            </a>
        </main>
    </body>
    </html>
    HTML;

    exit;
}


/*
|--------------------------------------------------------------------------
| PAGE RESOLUTION
|--------------------------------------------------------------------------
*/

$pageFile = PAGES_DIR . $routes[$path];


/*
|--------------------------------------------------------------------------
| PAGE FILE VALIDATION
|--------------------------------------------------------------------------
*/

if (!is_file($pageFile)) {

    error_log(
        sprintf(
            '[TIXORA ROUTER] Missing page: %s | Route: %s',
            $pageFile,
            $path
        )
    );

    http_response_code(500);

    header('Content-Type: text/html; charset=utf-8');

    echo <<<HTML
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, initial-scale=1.0">

        <title>500 — TIXORA</title>
    </head>

    <body style="
        font-family: Arial, sans-serif;
        text-align: center;
        padding: 100px 20px;
    ">
        <h1>500</h1>

        <h2>Terjadi Kesalahan Server</h2>

        <p>
            Halaman tidak dapat dimuat saat ini.
        </p>
    </body>
    </html>
    HTML;

    exit;
}


/*
|--------------------------------------------------------------------------
| PAGE RENDERING
|--------------------------------------------------------------------------
|
| Hanya satu halaman yang dimuat.
|
| Jangan require front controller dari dalam pages/.
|
*/

require $pageFile;

exit;