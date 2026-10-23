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

// Ambil jalur URI dan bersihkan dari query string & base path
$requestUri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
if (defined('BASE_URL') && BASE_URL !== '' && stripos($requestUri, BASE_URL) === 0) {
    $path = substr($requestUri, strlen(BASE_URL));
} else {
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    if ($scriptDir !== '' && stripos($requestUri, $scriptDir) === 0) {
        $path = substr($requestUri, strlen($scriptDir));
    } else {
        $path = $requestUri;
    }
}
$path = trim($path, '/');
$path = preg_replace('#^index\.php(/|$)#', '', $path);
$path = preg_replace('#^api(/|$)#', '', $path);
$path = trim($path, '/');

// Pecah path menjadi segmen-segmen array
$segments = $path !== '' ? explode('/', $path) : [];

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
            if (!empty($param)) {
                $userController->getEventById($param);
            } else {
                $userController->getActiveEvents();
            }
        } elseif ($action === 'register-event' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $userController->registerEvent();
        } elseif ($action === 'update-profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $userController->updateProfile();
        } elseif ($action === 'change-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $userController->changePassword();
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint User tidak ditemukan.']);
        }
        break;

    case 'owner':
        $ownerController = new OwnerController();
        if (($action === 'dashboard' || $action === 'summary') && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $ownerController->getDashboardSummary();
        } elseif ($action === 'events' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $ownerController->createEventDraft();
        } elseif ($action === 'events' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $ownerController->getMyEvents();
        } elseif ($action === 'submit-event' && !empty($param) && in_array($_SERVER['REQUEST_METHOD'], ['PATCH', 'POST'])) {
            $ownerController->submitEventForReview($param);
        } elseif ($action === 'event-detail' && !empty($param) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $ownerController->getEventSummaryDetail($param);
        } elseif ($action === 'event-tickets' && !empty($param) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $ownerController->getEventTickets($param);
        } elseif ($action === 'event-rundowns' && !empty($param) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $ownerController->getEventRundowns($param);
        } elseif ($action === 'event-rab' && !empty($param) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $ownerController->getEventRab($param);
        } elseif ($action === 'event-committees' && !empty($param) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $ownerController->getEventCommittees($param);
        } elseif ($action === 'event-logs' && !empty($param) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $ownerController->getEventSubmissionLogs($param);
        } elseif ($action === 'analytics' && !empty($param) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $ownerController->getEventAnalytics($param);
        } elseif ($action === 'update-event' && !empty($param) && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PATCH'], true)) {
            $ownerController->updateOwnerEvent($param);
        } elseif ($action === 'ticket-category' && !empty($param) && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PATCH'], true)) {
            $ownerController->manageTicketCategory($param);
        } elseif ($action === 'rundown' && !empty($param) && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PATCH'], true)) {
            $ownerController->manageRundown($param);
        } elseif ($action === 'committee' && !empty($param) && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PATCH'], true)) {
            $ownerController->managePanitia($param);
        } elseif ($action === 'rab' && !empty($param) && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PATCH'], true)) {
            $ownerController->manageRabItem($param);
        } elseif ($action === 'cancel-ticket' && !empty($param) && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PATCH'], true)) {
            $ownerController->cancelParticipantTicket($param);
        } elseif ($action === 'withdraw-event' && !empty($param) && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PATCH'], true)) {
            $ownerController->withdrawEventSubmission($param);
        } elseif ($action === 'publish-event' && !empty($param) && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PATCH'], true)) {
            $ownerController->publishEvent($param);
        } elseif ($action === 'cancel-event' && !empty($param) && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PATCH'], true)) {
            $ownerController->cancelEventByOwner($param);
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
        } elseif ($action === 'event-detail' && !empty($param) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminController->getAdminEventDetail($param);
        } elseif ($action === 'verify-event' && !empty($param) && in_array($_SERVER['REQUEST_METHOD'], ['PATCH', 'POST'])) {
            $adminController->verifyEvent($param);
        } elseif ($action === 'users' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $adminController->getAllUsers();
        } elseif ($action === 'suspend-user' && !empty($param) && in_array($_SERVER['REQUEST_METHOD'], ['PATCH', 'POST'])) {
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