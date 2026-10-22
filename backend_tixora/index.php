<?php
declare(strict_types=1);

// Konfigurasi Header CORS & Response JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Panggil Controller yang dibutuhkan
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/OwnerController.php';
require_once __DIR__ . '/controllers/AdminController.php';

// Ambil jalur URI dan bersihkan dari query string
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = dirname($_SERVER['SCRIPT_NAME']);

// Normalisasi URI berdasarkan direktori dasar proyek
$path = str_replace($scriptName, '', $requestUri);
$path = trim($path, '/');

// Pecah path menjadi segmen-segmen array
$segments = explode('/', $path);

// Endpoint Routing Sederhana (Router-Controller Pattern)
$endpoint = $segments[0] ?? '';
$action = $segments[1] ?? '';
$param = $segments[2] ?? null;

// Routing Berdasarkan Endpoint API V1
switch ($endpoint) {
    case 'auth':
        $authController = new AuthController();
        if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->register();
        } elseif ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->login();
        } elseif ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->logout();
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint Auth tidak ditemukan.']);
        }
        break;

    case 'user':
        $userController = new UserController();
        if ($action === 'events' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $userController->getActiveEvents();
        } elseif ($action === 'register-event' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $userController->registerEvent();
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint User tidak ditemukan.']);
        }
        break;

    case 'owner':
        $ownerController = new OwnerController();
        if ($action === 'events' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ownerController->createEventDraft();
        } elseif ($action === 'events' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $ownerController->getMyEvents();
        } elseif ($action === 'submit-event' && !empty($param) && $_SERVER['REQUEST_METHOD'] === 'PATCH') {
            $ownerController->submitEventForReview($param);
        } elseif ($action === 'participants' && !empty($param) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $ownerController->getEventParticipants($param);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint Event Owner tidak ditemukan.']);
        }
        break;

    case 'admin':
        $adminController = new AdminController();
        if ($action === 'stats' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminController->getDashboardStats();
        } elseif ($action === 'event-queue' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminController->getEventQueue();
        } elseif ($action === 'verify-event' && !empty($param) && $_SERVER['REQUEST_METHOD'] === 'PATCH') {
            $adminController->verifyEvent($param);
        } elseif ($action === 'users' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminController->getAllUsers();
        } elseif ($action === 'suspend-user' && !empty($param) && $_SERVER['REQUEST_METHOD'] === 'PATCH') {
            $adminController->suspendUser($param);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint Admin tidak ditemukan.']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Selamat datang di API Tixora (Backend PHP Native). Endpoint tidak dikenali.'
        ]);
        break;
}