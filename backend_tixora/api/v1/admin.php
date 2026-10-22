<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

// Inisialisasi Sesi & Proteksi RBAC Ketat Khusus ADMIN
AuthHelper::initSession();
AuthHelper::checkRole('ADMIN');

$db = Database::getInstance()->getConnection();
$adminController = new AdminController();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id = $_GET['id'] ?? $_GET['event_id'] ?? $_GET['user_id'] ?? null;

// Routing Endpoint Modul Admin v1 (Sinkronisasi Penuh dengan AdminController)
switch ($action) {
    case 'stats':
    case 'dashboard':
        if ($method === 'GET') {
            $adminController->getDashboardStats();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
        }
        break;

    case 'event-queue':
    case 'events':
        if ($method === 'GET') {
            if (!empty($id) && isset($_GET['detail'])) {
                $adminController->getAdminEventDetail($id);
            } else {
                $adminController->getEventQueue();
            }
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
        }
        break;

    case 'event-detail':
        if ($method === 'GET' && !empty($id)) {
            $adminController->getAdminEventDetail($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID event wajib disertakan.']);
        }
        break;

    case 'verify-event':
    case 'approve-event':
    case 'reject-event':
        if (in_array($method, ['PATCH', 'POST']) && !empty($id)) {
            $adminController->verifyEvent($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID event atau metode HTTP tidak valid untuk verifikasi.']);
        }
        break;

    case 'users':
        if ($method === 'GET') {
            $adminController->getAllUsers();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
        }
        break;

    case 'suspend-user':
        if (in_array($method, ['PATCH', 'POST']) && !empty($id)) {
            $adminController->suspendUser($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID pengguna atau metode HTTP tidak valid untuk suspensi.']);
        }
        break;

    case 'reports':
        if ($method === 'GET') {
            $adminController->getAdminReports();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
        }
        break;

    case 'notifications':
        if ($method === 'GET') {
            $adminController->getAdminNotifications();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
        }
        break;

    case 'read-notifications':
        if ($method === 'POST' || $method === 'PATCH') {
            $adminController->markAllNotificationsAsRead();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Aksi endpoint Admin v1 tidak dikenali.'
        ]);
        break;
}