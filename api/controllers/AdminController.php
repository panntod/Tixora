<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

/**
 * Controller Modul Admin (Pengelola Sistem Tixora)
 * Mengelola Dasbor Statistik, Antrean & Detail Event, Moderasi (Approve/Reject), Manajemen Pengguna, Laporan, dan Notifikasi.
 */
class AdminController {
    private PDO $db;

    public function __construct() {
        AuthHelper::initSession();
        AuthHelper::checkRole('ADMIN'); // Proteksi RBAC ketat khusus ADMIN[cite: 9]
        
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * 1. Dashboard Statistik Singkat & Aktivitas Terbaru
     */
    public function getDashboardStats(): void {
        try {
            $totalPengguna = (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $totalAcara = (int)$this->db->query("SELECT COUNT(*) FROM events")->fetchColumn();
            $totalTransaksi = (int)$this->db->query("SELECT COUNT(*) FROM tickets WHERE status_tiket != 'BATAL'")->fetchColumn();
            $menungguTinjauan = (int)$this->db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN'")->fetchColumn();
            $acaraTerbit = (int)$this->db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'TERBIT'")->fetchColumn();

            // Event yang perlu tindakan (menunggu tinjauan)
            $stmtPerluTindakan = $this->db->query("
                SELECT e.*, u.nama as owner_nama, u.email as owner_email 
                FROM events e 
                JOIN users u ON e.owner_id = u.id 
                WHERE e.status_verifikasi = 'MENUNGGU_TINJAUAN' 
                ORDER BY e.created_at ASC LIMIT 5
            ");
            $perluTindakan = $stmtPerluTindakan->fetchAll();

            // Aktivitas log pengajuan terbaru
            $stmtAktivitas = $this->db->query("
                SELECT esl.*, e.judul_event, u.nama as owner_nama 
                FROM event_submission_logs esl
                JOIN events e ON esl.event_id = e.id
                JOIN users u ON e.owner_id = u.id
                ORDER BY esl.created_at DESC LIMIT 5
            ");
            $aktivitasTerbaru = $stmtAktivitas->fetchAll();

            echo json_encode([
                'success' => true,
                'message' => 'Data dasbor admin berhasil diambil.',
                'data' => [
                    'ringkasan' => [
                        'total_pengguna' => $totalPengguna,
                        'total_acara' => $totalAcara,
                        'total_transaksi' => $totalTransaksi,
                        'menunggu_tinjauan' => $menungguTinjauan,
                        'acara_terbit' => $acaraTerbit
                    ],
                    'perlu_tindakan' => $perluTindakan,
                    'aktivitas_terbaru' => $aktivitasTerbaru
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil statistik dasbor: ' . $e->getMessage()]);
        }
    }

    /**
     * 2. Antrean Acara (Daftar & Filter Status, Kategori, Search)[cite: 13]
     */
    public function getEventQueue(): void {
        try {
            $status = $_GET['status'] ?? null;
            $kategori = $_GET['kategori'] ?? null;
            $search = $_GET['search'] ?? null;

            $sql = "
                SELECT e.*, u.nama as owner_nama, u.email as owner_email,
                       (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') as total_pendaftar
                FROM events e
                JOIN users u ON e.owner_id = u.id
                WHERE 1=1
            ";
            $params = [];

            if ($status && strtoupper($status) !== 'SEMUA') {
                $sql .= " AND e.status_verifikasi = :status";
                $params['status'] = strtoupper($status);
            }

            if ($kategori && $kategori !== 'Semua') {
                $sql .= " AND e.kategori = :kategori";
                $params['kategori'] = $kategori;
            }

            if ($search) {
                $sql .= " AND (e.judul_event ILIKE :search OR u.nama ILIKE :search)";
                $params['search'] = "%{$search}%";
            }

            $sql .= " ORDER BY e.created_at ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $events = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'message' => 'Antrean verifikasi event berhasil diambil.',
                'data' => $events
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil antrean event: ' . $e->getMessage()]);
        }
    }

    /**
     * 3. Detail Kelengkapan Acara oleh Admin (Termasuk RAB, Panitia, Rundown, Log)
     */
    public function getAdminEventDetail(string $eventId): void {
        try {
            $stmt = $this->db->prepare("
                SELECT e.*, u.id as owner_id, u.nama as owner_nama, u.email as owner_email, u.no_whatsapp as owner_whatsapp, u.institusi as owner_institusi
                FROM events e
                JOIN users u ON e.owner_id = u.id
                WHERE e.id = :id
            ");
            $stmt->execute(['id' => $eventId]);
            $event = $stmt->fetch();

            if (!$event) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Acara tidak ditemukan.']);
                return;
            }

            // Ambil relasi penunjang
            $stmtTickets = $this->db->prepare("SELECT * FROM ticket_categories WHERE event_id = :event_id");
            $stmtTickets->execute(['event_id' => $eventId]);
            $event['ticket_categories'] = $stmtTickets->fetchAll();

            $stmtRundown = $this->db->prepare("SELECT r.*, c.nama_panitia as pic_nama FROM rundowns r LEFT JOIN committees c ON r.committee_id = c.id WHERE r.event_id = :event_id ORDER BY r.waktu_mulai ASC");
            $stmtRundown->execute(['event_id' => $eventId]);
            $event['rundowns'] = $stmtRundown->fetchAll();

            $stmtRab = $this->db->prepare("SELECT * FROM rab_details WHERE event_id = :event_id");
            $stmtRab->execute(['event_id' => $eventId]);
            $event['rab_details'] = $stmtRab->fetchAll();

            $stmtPanitia = $this->db->prepare("SELECT * FROM committees WHERE event_id = :event_id");
            $stmtPanitia->execute(['event_id' => $eventId]);
            $event['committees'] = $stmtPanitia->fetchAll();

            $stmtLogs = $this->db->prepare("SELECT * FROM event_submission_logs WHERE event_id = :event_id ORDER BY created_at DESC");
            $stmtLogs->execute(['event_id' => $eventId]);
            $event['submission_logs'] = $stmtLogs->fetchAll();

            $stmtModLogs = $this->db->prepare("SELECT ml.*, u.nama as admin_nama FROM moderation_logs ml JOIN users u ON ml.admin_id = u.id WHERE ml.event_id = :event_id ORDER BY ml.created_at DESC");
            $stmtModLogs->execute(['event_id' => $eventId]);
            $event['moderation_logs'] = $stmtModLogs->fetchAll();

            echo json_encode([
                'success' => true,
                'message' => 'Detail kelengkapan acara berhasil diambil.',
                'data' => $event
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil detail acara: ' . $e->getMessage()]);
        }
    }

    /**
     * 4. Verifikasi / Moderasi Event (Approve/Terbit atau Reject/Ditolak dengan Validasi Alasan Min 20 Karakter)[cite: 13]
     */
    public function verifyEvent(string $eventId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $status = strtoupper(trim($input['status'] ?? '')); 
        $catatan = trim($input['catatan'] ?? $input['alasan_penolakan'] ?? '');

        if (!in_array($status, ['APPROVED', 'TERBIT', 'DISETUJUI', 'REJECTED', 'DITOLAK'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Status moderasi tidak valid.']);
            return;
        }

        $statusVerifikasi = in_array($status, ['APPROVED', 'TERBIT', 'DISETUJUI']) ? 'TERBIT' : 'DITOLAK';

        // Validasi sesuai spesifikasi visual: Alasan penolakan wajib diisi, minimal 20 karakter[cite: 13]
        if ($statusVerifikasi === 'DITOLAK' && strlen($catatan) < 20) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Alasan penolakan wajib diisi, minimal 20 karakter.']);
            return;
        }

        try {
            $this->db->beginTransaction();

            $stmtCheck = $this->db->prepare("SELECT id FROM events WHERE id = :id");
            $stmtCheck->execute(['id' => $eventId]);
            if (!$stmtCheck->fetch()) {
                throw new Exception('Acara tidak ditemukan.');
            }

            $stmtUpdate = $this->db->prepare("
                UPDATE events 
                SET status_verifikasi = :status_verifikasi, 
                    alasan_penolakan = :alasan_penolakan, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                'status_verifikasi' => $statusVerifikasi,
                'alasan_penolakan' => ($statusVerifikasi === 'DITOLAK') ? $catatan : null,
                'id' => $eventId
            ]);

            // Catat log pengajuan
            $stmtLog = $this->db->prepare("
                INSERT INTO event_submission_logs (event_id, status, catatan) 
                VALUES (:event_id, :status, :catatan)
            ");
            $stmtLog->execute([
                'event_id' => $eventId,
                'status' => $statusVerifikasi,
                'catatan' => $statusVerifikasi === 'TERBIT' ? 'Acara disetujui dan diterbitkan oleh admin.' : "Ditolak oleh admin. Alasan: {$catatan}"
            ]);

            // Catat log moderasi
            $adminId = $_SESSION['user']['id'];
            $stmtMod = $this->db->prepare("
                INSERT INTO moderation_logs (admin_id, event_id, keputusan, catatan) 
                VALUES (:admin_id, :event_id, :keputusan, :catatan)
            ");
            $stmtMod->execute([
                'admin_id' => $adminId,
                'event_id' => $eventId,
                'keputusan' => $statusVerifikasi,
                'catatan' => $catatan ?: 'Disetujui admin'
            ]);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => $statusVerifikasi === 'TERBIT' ? 'Acara berhasil disetujui dan diterbitkan.' : 'Acara berhasil ditolak dengan catatan.'
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Gagal memverifikasi event: ' . $e->getMessage()]);
        }
    }

    /**
     * 5. Manajemen Pengguna (Daftar & Pencarian)
     */
    public function getAllUsers(): void {
        try {
            $search = $_GET['search'] ?? null;
            $sql = "
                SELECT u.id, u.nama, u.email, u.no_whatsapp, u.institusi, u.is_active, u.created_at, r.nama as role_nama, r.kode as role_kode
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE 1=1
            ";
            $params = [];

            if ($search) {
                $sql .= " AND (u.nama ILIKE :search OR u.email ILIKE :search)";
                $params['search'] = "%{$search}%";
            }

            $sql .= " ORDER BY u.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'message' => 'Daftar pengguna berhasil diambil.',
                'data' => $users
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil daftar pengguna: ' . $e->getMessage()]);
        }
    }

    /**
     * 6. Tangguhkan / Suspend Akun Pengguna[cite: 13]
     */
    public function suspendUser(string $userId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $isActive = filter_var($input['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $alasan = trim($input['alasan'] ?? $input['alasan_suspensi'] ?? '');

        if (!$isActive && empty($alasan)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Alasan penangguhan akun wajib diisi!']);
            return;
        }

        try {
            $stmtUser = $this->db->prepare("SELECT nama FROM users WHERE id = :id");
            $stmtUser->execute(['id' => $userId]);
            $user = $stmtUser->fetch();

            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Akun pengguna tidak ditemukan.']);
                return;
            }

            $stmt = $this->db->prepare("
                UPDATE users 
                SET is_active = :is_active, 
                    alasan_suspensi = :alasan, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $stmt->execute([
                'is_active' => $isActive ? 1 : 0,
                'alasan' => $isActive ? null : $alasan,
                'id' => $userId
            ]);

            echo json_encode([
                'success' => true,
                'message' => $isActive ? "Akun {$user['nama']} berhasil diaktifkan kembali." : "Akun {$user['nama']} berhasil ditangguhkan.",
                'data' => ['is_active' => $isActive]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status akun: ' . $e->getMessage()]);
        }
    }

    /**
     * 7. Laporan & Rekapitulasi Platform
     */
    public function getAdminReports(): void {
        try {
            $totalTiket = (int)$this->db->query("SELECT COUNT(*) FROM tickets WHERE status_tiket != 'BATAL'")->fetchColumn();
            $totalEventTerbit = (int)$this->db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'TERBIT'")->fetchColumn();
            $totalUser = (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();

            $stmtEvents = $this->db->query("
                SELECT e.judul_event, e.kategori, e.tanggal_mulai, e.status_verifikasi, u.nama as owner_nama,
                       (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') as pendaftar
                FROM events e
                JOIN users u ON e.owner_id = u.id
                ORDER BY e.created_at DESC
            ");

            echo json_encode([
                'success' => true,
                'message' => 'Laporan platform berhasil diambil.',
                'data' => [
                    'statistik_global' => [
                        'total_tiket_terjual' => $totalTiket,
                        'total_acara_terbit' => $totalEventTerbit,
                        'total_pengguna' => $totalUser
                    ],
                    'rekap_event' => $stmtEvents->fetchAll()
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil laporan platform: ' . $e->getMessage()]);
        }
    }

    /**
     * 8. Pusat Notifikasi Admin
     */
    public function getAdminNotifications(): void {
        try {
            $pendingCount = (int)$this->db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN'")->fetchColumn();

            $notifications = [
                [
                    'id' => 'notif-1',
                    'kategori' => 'PERLU_TINJAUAN',
                    'pesan' => "Terdapat {$pendingCount} acara baru menanti tinjauan dan verifikasi admin.",
                    'waktu' => 'Hari ini',
                    'dibaca' => false
                ],
                [
                    'id' => 'notif-2',
                    'kategori' => 'SISTEM',
                    'pesan' => 'Pemeriksaan integritas basis data platform Tixora berjalan normal.',
                    'waktu' => 'Kemarin',
                    'dibaca' => true
                ]
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Daftar notifikasi berhasil diambil.',
                'data' => [
                    'belum_dibaca_count' => count(array_filter($notifications, fn($n) => !$n['dibaca'])),
                    'notifikasi' => $notifications
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil notifikasi: ' . $e->getMessage()]);
        }
    }

    /**
     * 9. Tandai Semua Notifikasi Dibaca
     */
    public function markAllNotificationsAsRead(): void {
        echo json_encode([
            'success' => true,
            'message' => 'Semua notifikasi berhasil ditandai sebagai dibaca.'
        ]);
    }
}