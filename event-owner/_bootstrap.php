<?php
/**
 * Bootstrap ringan untuk halaman event-owner.
 *
 * Jika aplikasi utama sudah mendefinisikan BASE_URL, nilai tersebut dipertahankan.
 * Ini hanya menyediakan fallback URL; tidak menggantikan autentikasi atau database.
 */
if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $marker = '/event-owner/';
    $position = strpos($scriptName, $marker);

    if ($position !== false) {
        define('BASE_URL', substr($scriptName, 0, $position));
    } else {
        define('BASE_URL', '');
    }
}
