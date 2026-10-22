<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../controllers/UserController.php';

// Inisialisasi Sesi & Keamanan Dasar
AuthHelper::initSession();

$db = Database::getInstance()->getConnection();
$userController = new UserController();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id = $_GET['id'] ?? $_POST['id'] ?? null;

// Routing Internal Modul User v1 (Sinkron 100% dengan UserController)
switch ($action) {
    case 'events':
    case 'active-events':
        if ($method === 'GET') {
            $userController->getActiveEvents();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
        }
        break;

    case 'event-detail':
        if ($method === 'GET') {
            $eventId = $_GET['id'] ?? '';
            if (empty($eventId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID Event wajib disertakan.']);
                break;
            }
            $userController->getEventById($eventId);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
        }
        break;

    case 'register-event':
        if ($method === 'POST') {
            $userController->registerEvent();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan. Gunakan POST.']);
        }
        break;

    case 'my-tickets':
        if ($method === 'GET') {
            $userController->getMyTickets();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan. Gunakan GET.']);
        }
        break;

    case 'ticket-detail':
        if ($method === 'GET') {
            $userController->getTicketDetail($id);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan. Gunakan GET.']);
        }
        break;

    case 'profile':
        if ($method === 'GET') {
            $userController->getProfile();
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            $userController->updateProfile();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan. Gunakan GET atau PUT/PATCH.']);
        }
        break;

    case 'change-password':
        if ($method === 'PUT' || $method === 'PATCH') {
            $userController->changePassword();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan. Gunakan PUT atau PATCH.']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Aksi endpoint User v1 tidak dikenali. Gunakan action: events, register-event, my-tickets, ticket-detail, profile, atau change-password.'
        ]);
        break;
}