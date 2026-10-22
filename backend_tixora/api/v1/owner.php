<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../helpers/UploadHelper.php';
require_once __DIR__ . '/../../controllers/OwnerController.php';

// Inisialisasi Sesi & Proteksi Akses Khusus EVENT_OWNER / ADMIN
AuthHelper::initSession();
AuthHelper::checkAnyRole(['EVENT_OWNER', 'ADMIN']);

$db = Database::getInstance()->getConnection();
$ownerController = new OwnerController();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id = $_GET['id'] ?? $_GET['event_id'] ?? $_GET['ticket_id'] ?? null;

// Tangkap parameter ID pendukung dari query string atau body
$eventId = $_GET['event_id'] ?? $_POST['event_id'] ?? $id;
$ticketId = $_GET['ticket_id'] ?? $_POST['ticket_id'] ?? $id;

// Routing Internal Modul Event Owner v1 (Full-Mapping dengan OwnerController)
switch ($action) {
    case 'dashboard':
    case 'summary':
        if ($method === 'GET') {
            $ownerController->getDashboardSummary();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
        }
        break;

    case 'events':
        if ($method === 'GET') {
            if (!empty($id) && isset($_GET['detail'])) {
                $ownerController->getEventSummaryDetail($id);
            } else {
                $ownerController->getMyEvents();
            }
        } elseif ($method === 'POST') {
            // Tangani unggahan banner jika ada berkas yang dikirimkan
            if (isset($_FILES['banner']) && $_FILES['banner']['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $uploadHelper = new UploadHelper();
                    $filename = $uploadHelper->uploadFile($_FILES['banner']);
                    $_POST['banner_url'] = '/uploads/' . $filename;
                } catch (Exception $e) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Gagal mengunggah banner: ' . $e->getMessage()]);
                    exit;
                }
            }
            $ownerController->createEventDraft();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
        }
        break;

    case 'event-detail':
        if ($method === 'GET' && !empty($id)) {
            $ownerController->getEventSummaryDetail($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID event wajib disertakan.']);
        }
        break;

    case 'update-event':
        if (in_array($method, ['PUT', 'PATCH']) && !empty($id)) {
            $ownerController->updateOwnerEvent($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Metode atau ID event tidak valid untuk pembaruan.']);
        }
        break;

    case 'tickets':
        if ($method === 'GET' && !empty($eventId)) {
            $ownerController->getEventTickets($eventId);
        } elseif ($method === 'POST' && !empty($eventId)) {
            $ownerController->manageTicketCategory($eventId);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parameter event_id wajib disertakan untuk manajemen tiket.']);
        }
        break;

    case 'rundowns':
        if ($method === 'GET' && !empty($eventId)) {
            $ownerController->getEventRundowns($eventId);
        } elseif ($method === 'POST' && !empty($eventId)) {
            $ownerController->manageRundown($eventId);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parameter event_id wajib disertakan untuk manajemen rundown.']);
        }
        break;

    case 'panitia':
    case 'committees':
        if ($method === 'GET' && !empty($eventId)) {
            $ownerController->getEventCommittees($eventId);
        } elseif ($method === 'POST' && !empty($eventId)) {
            $ownerController->managePanitia($eventId);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parameter event_id wajib disertakan untuk manajemen panitia.']);
        }
        break;

    case 'rab':
        if ($method === 'GET' && !empty($eventId)) {
            $ownerController->getEventRab($eventId);
        } elseif ($method === 'POST' && !empty($eventId)) {
            $ownerController->manageRabItem($eventId);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parameter event_id wajib disertakan untuk manajemen RAB.']);
        }
        break;

    case 'participants':
        if ($method === 'GET' && !empty($eventId)) {
            $ownerController->getEventParticipants($eventId);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID event wajib disertakan untuk melihat daftar peserta.']);
        }
        break;

    case 'cancel-ticket':
        if ($method === 'PATCH' && !empty($ticketId)) {
            $ownerController->cancelParticipantTicket($ticketId);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID tiket wajib disertakan untuk pembatalan tiket.']);
        }
        break;

    case 'submit-event':
        if ($method === 'PATCH' && !empty($id)) {
            $ownerController->submitEventForReview($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID event wajib disertakan untuk pengajuan peninjauan.']);
        }
        break;

    case 'withdraw-event':
        if ($method === 'PATCH' && !empty($id)) {
            $ownerController->withdrawEventSubmission($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID event wajib disertakan untuk menarik pengajuan.']);
        }
        break;

    case 'publish-event':
        if ($method === 'PATCH' && !empty($id)) {
            $ownerController->publishEvent($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID event wajib disertakan untuk publikasi acara.']);
        }
        break;

    case 'cancel-event':
        if ($method === 'PATCH' && !empty($id)) {
            $ownerController->cancelEventByOwner($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID event wajib disertakan untuk pembatalan acara.']);
        }
        break;

    case 'submission-logs':
        if ($method === 'GET' && !empty($id)) {
            $ownerController->getEventSubmissionLogs($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID event wajib disertakan untuk melihat riwayat log.']);
        }
        break;

    case 'analytics':
        if ($method === 'GET' && !empty($id)) {
            $ownerController->getEventAnalytics($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID event wajib disertakan untuk analitik acara.']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Aksi endpoint Event Owner v1 tidak dikenali.'
        ]);
        break;
}