<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

// Inisialisasi Sesi secara aman
AuthHelper::initSession();

$db = Database::getInstance()->getConnection();
$authController = new AuthController();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Routing Internal Modul Autentikasi v1
switch ($action) {
    case 'register':
        if ($method === 'POST') {
            $authController->register();
        } else {
            http_response_code(405);
            echo json_encode([
                'success' => false, 
                'message' => 'Metode HTTP tidak diizinkan. Gunakan POST.'
            ]);
        }
        break;

    case 'login':
        if ($method === 'POST') {
            $authController->login();
        } else {
            http_response_code(405);
            echo json_encode([
                'success' => false, 
                'message' => 'Metode HTTP tidak diizinkan. Gunakan POST.'
            ]);
        }
        break;

    case 'logout':
        if ($method === 'POST') {
            $authController->logout();
        } else {
            http_response_code(405);
            echo json_encode([
                'success' => false, 
                'message' => 'Metode HTTP tidak diizinkan. Gunakan POST.'
            ]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Aksi endpoint Auth v1 tidak dikenali. Gunakan action=register, action=login, atau action=logout.'
        ]);
        break;
}