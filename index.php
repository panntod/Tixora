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
    // Resolve the requested asset and ensure it stays inside the project directory.
    // This prevents paths such as ../private.css from reading files outside Tixora.
    $projectRoot = realpath(__DIR__);
    $resolvedPath = realpath(__DIR__ . '/' . $path);
    if (
        $projectRoot !== false &&
        $resolvedPath !== false &&
        is_file($resolvedPath) &&
        str_starts_with($resolvedPath, $projectRoot . DIRECTORY_SEPARATOR)
    ) {
        $filePath = $resolvedPath;
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
