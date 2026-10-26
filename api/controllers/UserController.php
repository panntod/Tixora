<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

/**
 * Controller Modul User & Manajemen Pengguna (Tixora)
 * Mengelola eksplorasi event, pendaftaran tiket, profil akun saya, ganti kata sandi, dan daftar pengguna.
 */
class UserController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * 1. Mengambil daftar event
     * Mengambil daftar event publik yang berstatus TERBIT / PUBLISHED
     * Mengoperasikan parameter Query: search, kategori, sortBy
     */
    public function getActiveEvents(): void {
        try {
            $search = trim($_GET['search'] ?? '');
            $kategori = trim($_GET['kategori'] ?? '');
            $sortBy = trim($_GET['sortBy'] ?? 'terbaru');

            // Kondisi dasar: hanya event yang terbit / disetujui untuk publik
            $sql = "
                SELECT e.*, 
                       u.nama as owner_nama,
                       (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') as total_pendaftar
                FROM events e
                JOIN users u ON e.owner_id = u.id
                WHERE e.status_verifikasi IN ('TERBIT', 'PUBLISHED', 'APPROVED', 'DISETUJUI')
            ";
            
            $params = [];

            // Filter Kategori (jika ada dan bukan 'Semua')
            if (!empty($kategori) && strtolower($kategori) !== 'semua') {
                $sql .= " AND e.kategori ILIKE :kategori";
                $params['kategori'] = $kategori;
            }

            // Filter Pencarian (Search berdasarkan Judul, Deskripsi, Tempat, atau Kota)
            if (!empty($search)) {
                $sql .= " AND (
                    e.judul_event ILIKE :search OR 
                    e.deskripsi ILIKE :search OR 
                    e.nama_tempat ILIKE :search OR 
                    e.kota ILIKE :search
                )";
                $params['search'] = "%{$search}%";
            }

            // Opsi Sorting
            if ($sortBy === 'terdekat') {
                $sql .= " ORDER BY e.tanggal_mulai ASC";
            } else {
                // Default: Terbaru
                $sql .= " ORDER BY e.created_at DESC";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $events = $stmt->fetchAll();

            // Hitung sisa kuota untuk setiap event
            foreach ($events as &$event) {
                $totalPendaftar = (int)($event['total_pendaftar'] ?? 0);
                $event['sisa_kuota'] = max(0, (int)$event['kuota'] - $totalPendaftar);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Daftar event berhasil diambil.',
                'data' => $events
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Gagal mengambil data event: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Mengambil detail event publik berdasarkan ID-nya
     */
    public function getEventById(string $eventId): void {
        try {
            // Ambil detail event yang statusnya TERBIT / PUBLISHED
            $stmt = $this->db->prepare("
                SELECT e.*, u.nama as owner_nama, u.email as owner_email 
                FROM events e
                JOIN users u ON e.owner_id = u.id
                WHERE e.id = :id AND e.status_verifikasi IN ('TERBIT', 'PUBLISHED')
            ");
            $stmt->execute(['id' => $eventId]);
            $event = $stmt->fetch();

            if (!$event) {
                http_response_code(404);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Event tidak ditemukan atau belum dipublikasikan.'
                ]);
                return;
            }

            // Ambil kategori tiket
            $stmtTickets = $this->db->prepare("
                SELECT id, nama_kategori, kuota, wajib_ktm 
                FROM ticket_categories 
                WHERE event_id = :event_id
            ");
            $stmtTickets->execute(['event_id' => $eventId]);
            $kategoriTiket = $stmtTickets->fetchAll();

            // Ambil rundown acara
            $stmtRundown = $this->db->prepare("
                SELECT id, waktu_mulai, waktu_selesai, judul_sesi, pembicara, keterangan 
                FROM rundowns 
                WHERE event_id = :event_id 
                ORDER BY waktu_mulai ASC
            ");
            $stmtRundown->execute(['event_id' => $eventId]);
            $rundowns = $stmtRundown->fetchAll();

            // Hitung sisa kuota total
            $stmtCount = $this->db->prepare("
                SELECT COUNT(*) FROM tickets 
                WHERE event_id = :event_id AND status_tiket != 'BATAL'
            ");
            $stmtCount->execute(['event_id' => $eventId]);
            $totalTerdaftar = (int)$stmtCount->fetchColumn();

            $event['sisa_kuota'] = max(0, (int)$event['kuota'] - $totalTerdaftar);
            $event['kategori_tiket'] = $kategoriTiket;
            $event['rundowns'] = $rundowns;

            echo json_encode([
                'success' => true,
                'message' => 'Detail event berhasil diambil.',
                'data' => $event
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Gagal mengambil detail event: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 2. Pendaftaran event / reservasi tiket gratis (Create transaksi/pendaftaran)
     * Dilengkapi pencegahan race condition menggunakan Transaction & Row Locking (FOR UPDATE)
     */
    public function registerEvent(): void {
        AuthHelper::initSession();
        AuthHelper::checkRole('USER'); // Hanya role USER yang dapat mendaftar

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $eventId = $input['event_id'] ?? '';
        $jenisTiket = $input['jenis_tiket'] ?? 'Gratis';
        $noWhatsApp = trim($input['no_whatsapp'] ?? '');
        $institusi = trim($input['institusi'] ?? '');

        if (empty($eventId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID Event wajib diisi.']);
            return;
        }

        try {
            $this->db->beginTransaction();

            // 1. Cek event dan kunci baris untuk mencegah race condition (kuota penuh bersamaan)
            $stmtEvent = $this->db->prepare("
                SELECT id, kuota, status_verifikasi 
                FROM events 
                WHERE id = :event_id FOR UPDATE
            ");
            $stmtEvent->execute(['event_id' => $eventId]);
            $event = $stmtEvent->fetch();

            if (!$event || !in_array($event['status_verifikasi'], ['TERBIT', 'PUBLISHED', 'APPROVED'])) {
                throw new Exception('Event tidak ditemukan atau belum disetujui/terbit oleh Admin.');
            }

            // 2. Hitung jumlah tiket aktif yang sudah terdaftar
            $stmtCount = $this->db->prepare("
                SELECT COUNT(*) as total 
                FROM tickets 
                WHERE event_id = :event_id AND status_tiket = 'AKTIF'
            ");
            $stmtCount->execute(['event_id' => $eventId]);
            $ticketCount = (int)$stmtCount->fetch()['total'];

            if ($ticketCount >= (int)$event['kuota']) {
                throw new Exception('Mohon maaf, kuota pendaftaran untuk event ini sudah penuh!');
            }

            // 3. Cek apakah user sudah terdaftar di event ini
            $stmtDup = $this->db->prepare("
                SELECT id FROM tickets 
                WHERE event_id = :event_id AND user_id = :user_id AND status_tiket = 'AKTIF'
            ");
            $stmtDup->execute(['event_id' => $eventId, 'user_id' => $userId]);
            if ($stmtDup->fetch()) {
                throw new Exception('Anda sudah terdaftar dalam event ini.');
            }

            // 4. Perbarui data profil opsional jika disertakan saat pendaftaran
            if (!empty($noWhatsApp) || !empty($institusi)) {
                $stmtUpdateUser = $this->db->prepare("
                    UPDATE users 
                    SET no_whatsapp = COALESCE(NULLIF(:no_whatsapp, ''), no_whatsapp),
                        institusi = COALESCE(NULLIF(:institusi, ''), institusi)
                    WHERE id = :user_id
                ");
                $stmtUpdateUser->execute([
                    'no_whatsapp' => $noWhatsApp,
                    'institusi' => $institusi,
                    'user_id' => $userId
                ]);
            }

            // 5. Generate Kode Tiket Unik (Format: TXR-YYYYMMDD-Random)
            $dateStr = date('Ymd');
            $randomCode = strtoupper(bin2hex(random_bytes(2)));
            $kodeTiket = "TXR-{$dateStr}-{$randomCode}";

            // 6. Simpan pendaftaran tiket ke database
            $stmtInsert = $this->db->prepare("
                INSERT INTO tickets (event_id, user_id, kode_tiket, jenis_tiket, status_tiket, status_kehadiran) 
                VALUES (:event_id, :user_id, :kode_tiket, :jenis_tiket, 'AKTIF', FALSE)
            ");
            $stmtInsert->execute([
                'event_id' => $eventId,
                'user_id' => $userId,
                'kode_tiket' => $kodeTiket,
                'jenis_tiket' => $jenisTiket
            ]);

            $this->db->commit();

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Pendaftaran event berhasil! E-Ticket digital telah diterbitkan.',
                'data' => [
                    'kode_tiket' => $kodeTiket,
                    'jenis_tiket' => $jenisTiket
                ]
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 3. Memperbarui Profil Pengguna (Halaman Akun Saya)
     */
    public function updateProfile(): void {
        AuthHelper::initSession();
        
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Silakan login terlebih dahulu.']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $nama = trim($input['nama'] ?? '');
        $noWhatsApp = trim($input['no_whatsapp'] ?? '');
        $institusi = trim($input['institusi'] ?? '');

        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET nama = COALESCE(NULLIF(:nama, ''), nama),
                    no_whatsapp = COALESCE(NULLIF(:no_whatsapp, ''), no_whatsapp),
                    institusi = COALESCE(NULLIF(:institusi, ''), institusi),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute([
                'nama' => $nama,
                'no_whatsapp' => $noWhatsApp,
                'institusi' => $institusi,
                'id' => $userId
            ]);

            // Ambil data terbaru untuk session
            $stmtUser = $this->db->prepare("SELECT id, nama, email, no_whatsapp, institusi FROM users WHERE id = :id");
            $stmtUser->execute(['id' => $userId]);
            $updatedUser = $stmtUser->fetch();

            $_SESSION['user']['nama'] = $updatedUser['nama'];

            echo json_encode([
                'success' => true,
                'message' => 'Profil berhasil diperbarui.',
                'data' => $updatedUser
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui profil: ' . $e->getMessage()]);
        }
    }

    /**
     * 4. Ganti Kata Sandi (Halaman Akun Saya)
     */
    public function changePassword(): void {
        AuthHelper::initSession();
        
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Silakan login terlebih dahulu.']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $kataSandiBaru = $input['kata_sandi_baru'] ?? '';

        if (strlen($kataSandiBaru) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kata sandi baru minimal harus 6 karakter.']);
            return;
        }

        try {
            $hashedPassword = password_hash($kataSandiBaru, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute(['password' => $hashedPassword, 'id' => $userId]);

            echo json_encode([
                'success' => true,
                'message' => 'Kata sandi berhasil diperbarui.'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengubah kata sandi: ' . $e->getMessage()]);
        }
    }

    /**
     * 5. Hapus Akun Pengguna secara Permanen
     */
    public function deleteAccount(): void {
        AuthHelper::initSession();
        
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Silakan login terlebih dahulu.']);
            return;
        }

        $userId = $_SESSION['user']['id'];

        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);

            session_destroy();

            echo json_encode([
                'success' => true,
                'message' => 'Akun dan seluruh riwayat berhasil dihapus permanen.'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus akun: ' . $e->getMessage()]);
        }
    }

    /**
     * 6. Mengambil Daftar Seluruh Pengguna (Fitur Manajemen Admin)
     */
    public function getAllUsers(): void {
        AuthHelper::initSession();
        AuthHelper::checkRole('ADMIN');

        try {
            $search = $_GET['search'] ?? '';
            $query = "
                SELECT u.id, u.nama, u.email, u.no_whatsapp, u.institusi, u.is_active, u.created_at, r.nama as role_nama, r.kode as role_kode
                FROM users u
                JOIN roles r ON u.role_id = r.id
            ";

            if (!empty($search)) {
                $query .= " WHERE u.nama ILIKE :search OR u.email ILIKE :search";
            }
            $query .= " ORDER BY u.created_at DESC";

            $stmt = $this->db->prepare($query);
            if (!empty($search)) {
                $stmt->execute(['search' => "%{$search}%"]);
            } else {
                $stmt->execute();
            }
            $users = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'message' => 'Daftar pengguna berhasil diambil.',
                'data' => $users
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil data pengguna: ' . $e->getMessage()]);
        }
    }
}