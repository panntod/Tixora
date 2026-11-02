<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

/**
 * Controller Modul User & Manajemen Pengguna (Tixora)
 * Mengelola eksplorasi event, pendaftaran tiket, profil akun saya, ganti kata sandi, dan daftar pengguna.
 */
class UserController {
    /** Status acara yang tampil & bisa didaftar publik (disamakan dengan halaman web). */
    private const OPEN_STATUSES = ['TERBIT', 'PUBLISHED', 'DISETUJUI'];

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
                WHERE e.status_verifikasi IN ('TERBIT', 'PUBLISHED', 'DISETUJUI')
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
            error_log('[TIXORA] ' . __METHOD__ . ': ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Gagal mengambil data event. Terjadi kesalahan pada server.'
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
                WHERE e.id = :id AND e.status_verifikasi IN ('TERBIT', 'PUBLISHED', 'DISETUJUI')
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
                SELECT id, nama_kategori, kuota, wajib_ktm, harga 
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
            error_log('[TIXORA] ' . __METHOD__ . ': ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Gagal mengambil detail event. Terjadi kesalahan pada server.'
            ]);
        }
    }

    /**
     * 2. Pendaftaran event / reservasi TIKET GRATIS (Create transaksi/pendaftaran)
     * Dilengkapi pencegahan race condition menggunakan Transaction & Row Locking (FOR UPDATE).
     * Tiket berbayar wajib lewat halaman /pendaftaran -> /pembayaran.
     *
     * Body JSON: event_id (wajib), ticket_category_id (wajib jika acara punya kategori),
     *            no_whatsapp, institusi (opsional)
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
        $eventId = (string)($input['event_id'] ?? '');
        $categoryId = (string)($input['ticket_category_id'] ?? '');
        $noWhatsApp = trim($input['no_whatsapp'] ?? '');
        $institusi = trim($input['institusi'] ?? '');

        $uuid = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        if ($eventId === '' || !preg_match($uuid, $eventId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID Event wajib diisi dengan benar.']);
            return;
        }
        if ($categoryId !== '' && !preg_match($uuid, $categoryId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID kategori tiket tidak valid.']);
            return;
        }

        require_once __DIR__ . '/../helpers/PaymentHelper.php';

        try {
            PaymentHelper::ensureSchema($this->db);
            PaymentHelper::expireOverdue($this->db);

            $this->db->beginTransaction();

            // 1. Kunci baris event untuk mencegah race condition (kuota penuh bersamaan)
            $stmtEvent = $this->db->prepare("
                SELECT id, kuota, status_verifikasi, tanggal_selesai, (tanggal_selesai < CURRENT_TIMESTAMP) AS sudah_selesai
                FROM events
                WHERE id = :event_id FOR UPDATE
            ");
            $stmtEvent->execute(['event_id' => $eventId]);
            $event = $stmtEvent->fetch();

            if (!$event || !in_array($event['status_verifikasi'], self::OPEN_STATUSES, true)) {
                throw new DomainException('Event tidak ditemukan atau belum disetujui/terbit oleh Admin.');
            }
            if ($event['sudah_selesai']) {
                throw new DomainException('Pendaftaran untuk acara ini sudah ditutup.');
            }

            // 2. Kuota total event (semua tiket selain BATAL, termasuk yang sudah check-in)
            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = :event_id AND status_tiket != 'BATAL'");
            $stmtCount->execute(['event_id' => $eventId]);
            if ((int)$stmtCount->fetchColumn() >= (int)$event['kuota']) {
                throw new DomainException('Mohon maaf, kuota pendaftaran untuk event ini sudah penuh!');
            }

            // 3. Satu peserta = satu tiket per event (AKTIF, MENUNGGU_PEMBAYARAN, atau SUDAH_PAKAI)
            $stmtDup = $this->db->prepare("
                SELECT 1 FROM tickets
                WHERE event_id = :event_id AND user_id = :user_id
                  AND status_tiket IN ('AKTIF', 'MENUNGGU_PEMBAYARAN', 'SUDAH_PAKAI')
            ");
            $stmtDup->execute(['event_id' => $eventId, 'user_id' => $userId]);
            if ($stmtDup->fetchColumn()) {
                throw new DomainException('Anda sudah terdaftar dalam event ini.');
            }

            // 4. Kategori tiket: wajib dipilih bila acara punya kategori; kuota & harga dibaca dari DB
            $stmtCats = $this->db->prepare("SELECT COUNT(*) FROM ticket_categories WHERE event_id = :event_id");
            $stmtCats->execute(['event_id' => $eventId]);
            $punyaKategori = (int)$stmtCats->fetchColumn() > 0;

            $jenisTiket = 'Umum';
            $ticketCatId = null;

            if ($punyaKategori) {
                if ($categoryId === '') {
                    throw new DomainException('Pilih kategori tiket terlebih dahulu (ticket_category_id).');
                }
                $stmtCat = $this->db->prepare("
                    SELECT id, nama_kategori, kuota, harga
                    FROM ticket_categories
                    WHERE id = :id AND event_id = :event_id FOR UPDATE
                ");
                $stmtCat->execute(['id' => $categoryId, 'event_id' => $eventId]);
                $cat = $stmtCat->fetch();
                if (!$cat) {
                    throw new DomainException('Kategori tiket tidak ditemukan untuk acara ini.');
                }
                if ((float)$cat['harga'] > 0) {
                    throw new DomainException('Kategori tiket ini berbayar. Silakan pesan melalui halaman pendaftaran agar dapat membayar.');
                }
                $stmtCatCount = $this->db->prepare("SELECT COUNT(*) FROM tickets WHERE ticket_category_id = :id AND status_tiket != 'BATAL'");
                $stmtCatCount->execute(['id' => $cat['id']]);
                if ((int)$stmtCatCount->fetchColumn() >= (int)$cat['kuota']) {
                    throw new DomainException('Maaf, kuota untuk kategori tiket ini sudah penuh.');
                }
                $jenisTiket = $cat['nama_kategori'];
                $ticketCatId = $cat['id'];
            }

            // 5. Perbarui data profil opsional jika disertakan saat pendaftaran
            if ($noWhatsApp !== '' || $institusi !== '') {
                $this->db->prepare("
                    UPDATE users
                    SET no_whatsapp = COALESCE(NULLIF(:no_whatsapp, ''), no_whatsapp),
                        institusi = COALESCE(NULLIF(:institusi, ''), institusi),
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :user_id
                ")->execute([
                    'no_whatsapp' => $noWhatsApp,
                    'institusi' => $institusi,
                    'user_id' => $userId
                ]);
            }

            // 6. Kode tiket unik (Format: TXR-YYYYMMDD-Random), diulang bila bentrok
            $kodeTiket = '';
            $stmtKode = $this->db->prepare("SELECT 1 FROM tickets WHERE kode_tiket = :kode");
            for ($i = 0; $i < 5; $i++) {
                $kodeTiket = 'TXR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $stmtKode->execute(['kode' => $kodeTiket]);
                if (!$stmtKode->fetchColumn()) {
                    break;
                }
            }

            // 7. Simpan pendaftaran tiket
            $this->db->prepare("
                INSERT INTO tickets (event_id, ticket_category_id, user_id, kode_tiket, jenis_tiket, status_tiket, status_kehadiran)
                VALUES (:event_id, :ticket_category_id, :user_id, :kode_tiket, :jenis_tiket, 'AKTIF', FALSE)
            ")->execute([
                'event_id' => $eventId,
                'ticket_category_id' => $ticketCatId,
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

        } catch (DomainException $e) {
            // Pesan aturan bisnis aman ditampilkan ke pengguna
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[TIXORA] ' . __METHOD__ . ': ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Pendaftaran gagal diproses. Silakan coba lagi.']);
        }
    }

    /**
     * 3. Memperbarui Profil Pengguna (Halaman Akun Saya)
     */
    public function updateProfile(): void {
        AuthHelper::initSession();
        AuthHelper::checkAnyRole(['USER', 'EVENT_OWNER', 'ADMIN']);

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
            error_log('[TIXORA] ' . __METHOD__ . ': ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui profil. Terjadi kesalahan pada server, silakan coba lagi.']);
        }
    }

    /**
     * 4. Ganti Kata Sandi (Halaman Akun Saya)
     * Wajib menyertakan kata sandi lama, supaya sesi yang dibajak tidak langsung bisa
     * mengambil alih akun.
     */
    public function changePassword(): void {
        AuthHelper::initSession();
        AuthHelper::checkAnyRole(['USER', 'EVENT_OWNER', 'ADMIN']);

        $userId = $_SESSION['user']['id'];
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $kataSandiLama = (string)($input['kata_sandi_lama'] ?? '');
        $kataSandiBaru = (string)($input['kata_sandi_baru'] ?? '');

        if ($kataSandiLama === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kata sandi lama wajib diisi.']);
            return;
        }
        if (strlen($kataSandiBaru) < 8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kata sandi baru minimal harus 8 karakter.']);
            return;
        }

        try {
            $stmtOld = $this->db->prepare("SELECT password FROM users WHERE id = :id");
            $stmtOld->execute(['id' => $userId]);
            $hashLama = $stmtOld->fetchColumn();

            if (!$hashLama || !password_verify($kataSandiLama, (string)$hashLama)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Kata sandi lama tidak sesuai.']);
                return;
            }

            $hashedPassword = password_hash($kataSandiBaru, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute(['password' => $hashedPassword, 'id' => $userId]);

            echo json_encode([
                'success' => true,
                'message' => 'Kata sandi berhasil diperbarui.'
            ]);
        } catch (PDOException $e) {
            error_log('[TIXORA] ' . __METHOD__ . ': ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengubah kata sandi. Terjadi kesalahan pada server, silakan coba lagi.']);
        }
    }
}
