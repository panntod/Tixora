<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

/**
 * Controller Modul Event Owner (Penyelenggara Acara) Tixora
 * Mengelola Dasbor, Wizard Pembuatan Acara, Tiket, Rundown, Panitia, RAB, Peserta, Analitik, hingga Pengaturan & Publikasi.
 */
class OwnerController {
    private PDO $db;
    private string $ownerId;

    public function __construct() {
        AuthHelper::initSession();
        AuthHelper::checkRole('EVENT_OWNER');
        
        $this->db = Database::getInstance()->getConnection();
        $this->ownerId = $_SESSION['user']['id'] ?? '';
    }

    /**
     * 1. Ringkasan Dasbor Event Owner (/event-owner)
     */
    public function getDashboardSummary(): void {
        try {
            $stmtTotal = $this->db->prepare("SELECT COUNT(*) FROM events WHERE owner_id = :owner_id");
            $stmtTotal->execute(['owner_id' => $this->ownerId]);
            $totalAcara = (int)$stmtTotal->fetchColumn();

            $stmtTerbit = $this->db->prepare("SELECT COUNT(*) FROM events WHERE owner_id = :owner_id AND status_verifikasi = 'TERBIT'");
            $stmtTerbit->execute(['owner_id' => $this->ownerId]);
            $acaraTerbit = (int)$stmtTerbit->fetchColumn();

            $stmtDraft = $this->db->prepare("SELECT COUNT(*) FROM events WHERE owner_id = :owner_id AND status_verifikasi = 'DRAF'");
            $stmtDraft->execute(['owner_id' => $this->ownerId]);
            $draftCount = (int)$stmtDraft->fetchColumn();

            $stmtPending = $this->db->prepare("SELECT COUNT(*) FROM events WHERE owner_id = :owner_id AND status_verifikasi = 'MENUNGGU_TINJAUAN'");
            $stmtPending->execute(['owner_id' => $this->ownerId]);
            $pendingCount = (int)$stmtPending->fetchColumn();

            // Total tiket terjual & pendaftar
            $stmtTickets = $this->db->prepare("
                SELECT COUNT(t.id) as total_pendaftar 
                FROM tickets t 
                JOIN events e ON t.event_id = e.id 
                WHERE e.owner_id = :owner_id AND t.status_tiket != 'BATAL'
            ");
            $stmtTickets->execute(['owner_id' => $this->ownerId]);
            $totalPendaftar = (int)$stmtTickets->fetchColumn();

            // Acara mendatang (tanggal mulai > hari ini)
            $stmtUpcoming = $this->db->prepare("
                SELECT * FROM events 
                WHERE owner_id = :owner_id AND tanggal_mulai > CURRENT_TIMESTAMP 
                ORDER BY tanggal_mulai ASC LIMIT 5
            ");
            $stmtUpcoming->execute(['owner_id' => $this->ownerId]);
            $acaraMendatang = $stmtUpcoming->fetchAll();

            echo json_encode([
                'success' => true,
                'message' => 'Ringkasan dasbor berhasil diambil.',
                'data' => [
                    'total_acara' => $totalAcara,
                    'acara_terbit' => $acaraTerbit,
                    'draft_count' => $draftCount,
                    'pending_count' => $pendingCount,
                    'total_pendaftar' => $totalPendaftar,
                    'total_tiket_terjual' => $totalPendaftar,
                    'acara_mendatang' => $acaraMendatang
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal memuat ringkasan dasbor: ' . $e->getMessage()]);
        }
    }

    /**
     * 2. Daftar Acara Saya (/event-owner/acara-saya)
     */
    public function getMyEvents(): void {
        try {
            $status = $_GET['status'] ?? null;
            $search = $_GET['search'] ?? null;

            $sql = "SELECT e.*, (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') as total_pendaftar FROM events e WHERE e.owner_id = :owner_id";
            $params = ['owner_id' => $this->ownerId];

            if ($status && $status !== 'SEMUA' && $status !== 'Semua') {
                $sql .= " AND e.status_verifikasi = :status";
                $params['status'] = strtoupper($status);
            }

            if ($search) {
                $sql .= " AND e.judul_event ILIKE :search";
                $params['search'] = "%{$search}%";
            }

            $sql .= " ORDER BY e.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $events = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'message' => 'Daftar acara berhasil diambil.',
                'data' => $events
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil data acara: ' . $e->getMessage()]);
        }
    }

    /**
     * 3. Buat Draf Acara Baru (/event-owner/buat-acara)
     */
    public function createEventDraft(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $judulEvent = trim($input['judul_event'] ?? $input['judulEvent'] ?? '');
        $deskripsi = trim($input['deskripsi'] ?? '');
        $kategori = trim($input['kategori'] ?? 'Seminar');
        $kuota = (int)($input['kuota'] ?? 100);
        $tanggalMulai = $input['tanggal_mulai'] ?? $input['tanggalMulai'] ?? '';
        $tanggalSelesai = $input['tanggal_selesai'] ?? $input['tanggalSelesai'] ?? '';
        $namaTempat = trim($input['nama_tempat'] ?? $input['namaTempat'] ?? '');
        $kota = trim($input['kota'] ?? '');
        $bannerUrl = trim($input['banner_url'] ?? $input['bannerUrl'] ?? $input['posterUrl'] ?? '');

        if (!$judulEvent || !$tanggalMulai || !$tanggalSelesai || !$namaTempat || !$kota) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Informasi utama event (Judul, Tanggal, Tempat, Kota) wajib diisi.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO events (
                    owner_id, judul_event, deskripsi, kategori, kuota, 
                    tanggal_mulai, tanggal_selesai, nama_tempat, kota, banner_url, status_verifikasi
                ) VALUES (
                    :owner_id, :judul_event, :deskripsi, :kategori, :kuota, 
                    :tanggal_mulai, :tanggal_selesai, :nama_tempat, :kota, :banner_url, 'DRAF'
                ) RETURNING id
            ");
            
            $stmt->execute([
                'owner_id' => $this->ownerId,
                'judul_event' => $judulEvent,
                'deskripsi' => $deskripsi,
                'kategori' => $kategori,
                'kuota' => $kuota,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'nama_tempat' => $namaTempat,
                'kota' => $kota,
                'banner_url' => $bannerUrl ?: null
            ]);

            $newId = $stmt->fetchColumn();

            // Catat log draf
            $stmtLog = $this->db->prepare("
                INSERT INTO event_submission_logs (event_id, status, catatan) VALUES (:event_id, 'DRAF', 'Draf acara berhasil dibuat.')
            ");
            $stmtLog->execute(['event_id' => $newId]);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Draf acara berhasil disimpan.',
                'data' => ['id' => $newId]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan draf event: ' . $e->getMessage()]);
        }
    }

    /**
     * 4. Sunting Informasi Acara (/event-owner/edit-informasi)
     */
    public function updateOwnerEvent(string $eventId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        try {
            $stmtCheck = $this->db->prepare("SELECT id, status_verifikasi FROM events WHERE id = :id AND owner_id = :owner_id");
            $stmtCheck->execute(['id' => $eventId, 'owner_id' => $this->ownerId]);
            $event = $stmtCheck->fetch();

            if (!$event) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Acara tidak ditemukan atau akses ditolak.']);
                return;
            }

            if ($event['status_verifikasi'] !== 'DRAF') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Acara hanya dapat disunting jika berstatus DRAF. Tarik pengajuan terlebih dahulu.']);
                return;
            }

            $stmt = $this->db->prepare("
                UPDATE events SET 
                    judul_event = COALESCE(:judul_event, judul_event),
                    deskripsi = COALESCE(:deskripsi, deskripsi),
                    kategori = COALESCE(:kategori, kategori),
                    kuota = COALESCE(:kuota, kuota),
                    tanggal_mulai = COALESCE(:tanggal_mulai, tanggal_mulai),
                    tanggal_selesai = COALESCE(:tanggal_selesai, tanggal_selesai),
                    nama_tempat = COALESCE(:nama_tempat, nama_tempat),
                    kota = COALESCE(:kota, kota),
                    banner_url = COALESCE(:banner_url, banner_url),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");

            $stmt->execute([
                'judul_event' => $input['judul_event'] ?? $input['judulEvent'] ?? null,
                'deskripsi' => $input['deskripsi'] ?? null,
                'kategori' => $input['kategori'] ?? null,
                'kuota' => isset($input['kuota']) ? (int)$input['kuota'] : null,
                'tanggal_mulai' => $input['tanggal_mulai'] ?? $input['tanggalMulai'] ?? null,
                'tanggal_selesai' => $input['tanggal_selesai'] ?? $input['tanggalSelesai'] ?? null,
                'nama_tempat' => $input['nama_tempat'] ?? $input['namaTempat'] ?? null,
                'kota' => $input['kota'] ?? null,
                'banner_url' => $input['banner_url'] ?? $input['bannerUrl'] ?? $input['posterUrl'] ?? null,
                'id' => $eventId
            ]);

            echo json_encode(['success' => true, 'message' => 'Detail draf acara berhasil diperbarui.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui draf acara: ' . $e->getMessage()]);
        }
    }

    /**
     * 5. Kelola Kategori Tiket (/event-owner/buat-acara/tiket & /tiket)
     */
    public function manageTicketCategory(string $eventId): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $namaKategori = trim($input['nama_kategori'] ?? $input['namaKategori'] ?? 'Umum');
        $harga = (float)($input['harga'] ?? 0);
        $kuota = (int)($input['kuota'] ?? 0);
        $mulaiJual = $input['mulai_jual'] ?? $input['mulaiJual'] ?? null;
        $berakhir = $input['berakhir'] ?? null;
        $wajibKtm = filter_var($input['wajib_ktm'] ?? $input['wajibKTM'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$namaKategori || $kuota <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama kategori dan kuota tiket wajib diisi dengan benar.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO ticket_categories (event_id, nama_kategori, harga, kuota, mulai_jual, berakhir, wajib_ktm)
                VALUES (:event_id, :nama_kategori, :harga, :kuota, :mulai_jual, :berakhir, :wajib_ktm)
                RETURNING id
            ");
            $stmt->execute([
                'event_id' => $eventId,
                'nama_kategori' => $namaKategori,
                'harga' => $harga,
                'kuota' => $kuota,
                'mulai_jual' => $mulaiJual ?: null,
                'berakhir' => $berakhir ?: null,
                'wajib_ktm' => $wajibKtm ? 1 : 0
            ]);

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Kategori tiket berhasil ditambahkan.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menambah kategori tiket: ' . $e->getMessage()]);
        }
    }

    /**
     * 6. Kelola Rundown Acara & PIC Panitia (/event-owner/rundown)
     */
    public function manageRundown(string $eventId): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $waktuMulai = trim($input['waktu_mulai'] ?? $input['waktuMulai'] ?? '');
        $waktuSelesai = trim($input['waktu_selesai'] ?? $input['waktuSelesai'] ?? '');
        $judulSesi = trim($input['judul_sesi'] ?? $input['judulSesi'] ?? '');
        $committeeId = $input['committee_id'] ?? $input['pic_id'] ?? $input['committeeId'] ?? null;
        $pembicara = trim($input['pembicara'] ?? '');
        $keterangan = trim($input['keterangan'] ?? '');

        if (!$waktuMulai || !$waktuSelesai || !$judulSesi) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Waktu mulai, selesai, dan judul sesi wajib diisi.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO rundowns (event_id, committee_id, waktu_mulai, waktu_selesai, judul_sesi, pembicara, keterangan)
                VALUES (:event_id, :committee_id, :waktu_mulai, :waktu_selesai, :judul_sesi, :pembicara, :keterangan)
                RETURNING id
            ");
            $stmt->execute([
                'event_id' => $eventId,
                'committee_id' => empty($committeeId) ? null : $committeeId,
                'waktu_mulai' => $waktuMulai,
                'waktu_selesai' => $waktuSelesai,
                'judul_sesi' => $judulSesi,
                'pembicara' => $pembicara,
                'keterangan' => $keterangan
            ]);

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Sesi rundown berhasil ditambahkan.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menambah rundown: ' . $e->getMessage()]);
        }
    }

    /**
     * 7. Kelola Panitia / Kepanitiaan (/event-owner/panitia)
     */
    public function managePanitia(string $eventId): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $namaPanitia = trim($input['nama_panitia'] ?? $input['namaPanitia'] ?? $input['nama'] ?? '');
        $posisiJabatan = trim($input['posisi_jabatan'] ?? $input['posisiJabatan'] ?? $input['peran'] ?? '');
        $divisi = trim($input['divisi'] ?? 'Inti');
        $kontak = trim($input['kontak'] ?? $input['noWhatsApp'] ?? '');
        $surel = trim($input['surel'] ?? '');

        if (!$namaPanitia || !$posisiJabatan) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nama panitia dan posisi jabatan wajib diisi.']);
            return;
        }

        try {
            $accessToken = bin2hex(random_bytes(16));
            $stmt = $this->db->prepare("
                INSERT INTO committees (event_id, nama_panitia, posisi_jabatan, divisi, kontak, surel, access_token)
                VALUES (:event_id, :nama_panitia, :posisi_jabatan, :divisi, :kontak, :surel, :access_token)
                RETURNING id
            ");
            $stmt->execute([
                'event_id' => $eventId,
                'nama_panitia' => $namaPanitia,
                'posisi_jabatan' => $posisiJabatan,
                'divisi' => $divisi,
                'kontak' => $kontak,
                'surel' => $surel,
                'access_token' => $accessToken
            ]);

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Anggota panitia berhasil ditambahkan.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menambah panitia: ' . $e->getMessage()]);
        }
    }

    /**
     * 8. Kelola RAB / Anggaran Biaya (/event-owner/rab)
     */
    public function manageRabItem(string $eventId): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $kategori = trim($input['kategori'] ?? '');
        $rencana = (float)($input['rencana'] ?? $input['estimasiBiaya'] ?? 0);
        $realisasi = (float)($input['realisasi'] ?? 0);

        if (!$kategori) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kategori RAB wajib diisi.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO rab_details (event_id, kategori, rencana, realisasi)
                VALUES (:event_id, :kategori, :rencana, :realisasi)
                RETURNING id
            ");
            $stmt->execute([
                'event_id' => $eventId,
                'kategori' => $kategori,
                'rencana' => $rencana,
                'realisasi' => $realisasi
            ]);

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Item RAB berhasil ditambahkan.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menambah item RAB: ' . $e->getMessage()]);
        }
    }

    /**
     * 9. Daftar Peserta Event (/event-owner/peserta)
     */
    public function getEventParticipants(string $eventId): void {
        try {
            $stmtCheck = $this->db->prepare("SELECT id FROM events WHERE id = :event_id AND owner_id = :owner_id");
            $stmtCheck->execute(['event_id' => $eventId, 'owner_id' => $this->ownerId]);
            if (!$stmtCheck->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Akses ditolak. Event bukan milik Anda.']);
                return;
            }

            $stmt = $this->db->prepare("
                SELECT t.id as ticket_id, t.kode_tiket, t.jenis_tiket, t.status_tiket, t.status_kehadiran, t.waktu_daftar,
                       u.nama, u.email, u.no_whatsapp, u.institusi 
                FROM tickets t
                JOIN users u ON t.user_id = u.id
                WHERE t.event_id = :event_id
                ORDER BY t.waktu_daftar DESC
            ");
            $stmt->execute(['event_id' => $eventId]);
            $participants = $stmt->fetchAll();

            echo json_encode(['success' => true, 'message' => 'Daftar peserta berhasil diambil.', 'data' => $participants]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil daftar peserta: ' . $e->getMessage()]);
        }
    }

    /**
     * 10. Batalkan Tiket Peserta Tertentu
     */
    public function cancelParticipantTicket(string $ticketId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
            return;
        }

        try {
            $stmtCheck = $this->db->prepare("
                SELECT t.id FROM tickets t
                JOIN events e ON t.event_id = e.id
                WHERE t.id = :ticket_id AND e.owner_id = :owner_id
            ");
            $stmtCheck->execute(['ticket_id' => $ticketId, 'owner_id' => $this->ownerId]);
            if (!$stmtCheck->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Tiket tidak ditemukan atau akses ditolak.']);
                return;
            }

            $stmtUpdate = $this->db->prepare("
                UPDATE tickets SET status_tiket = 'BATAL', updated_at = CURRENT_TIMESTAMP WHERE id = :id
            ");
            $stmtUpdate->execute(['id' => $ticketId]);

            echo json_encode(['success' => true, 'message' => 'Tiket peserta berhasil dibatalkan.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal membatalkan tiket: ' . $e->getMessage()]);
        }
    }

    /**
     * 11. Pengajuan Acara ke Admin (/event-owner/pengajuan)
     */
    public function submitEventForReview(string $eventId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
            return;
        }

        try {
            $stmtCheck = $this->db->prepare("SELECT id, status_verifikasi FROM events WHERE id = :id AND owner_id = :owner_id");
            $stmtCheck->execute(['id' => $eventId, 'owner_id' => $this->ownerId]);
            $event = $stmtCheck->fetch();

            if (!$event) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Acara tidak ditemukan.']);
                return;
            }

            if (!in_array($event['status_verifikasi'], ['DRAF', 'DITOLAK'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Acara hanya dapat diajukan jika berstatus DRAF atau DITOLAK.']);
                return;
            }

            $stmtUpdate = $this->db->prepare("
                UPDATE events SET status_verifikasi = 'MENUNGGU_TINJAUAN', updated_at = CURRENT_TIMESTAMP WHERE id = :id
            ");
            $stmtUpdate->execute(['id' => $eventId]);

            $stmtLog = $this->db->prepare("
                INSERT INTO event_submission_logs (event_id, status, catatan) VALUES (:event_id, 'MENUNGGU_TINJAUAN', 'Acara diajukan untuk peninjauan admin.')
            ");
            $stmtLog->execute(['event_id' => $eventId]);

            echo json_encode(['success' => true, 'message' => 'Acara berhasil diajukan kepada Admin untuk ditinjau.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengajukan acara: ' . $e->getMessage()]);
        }
    }

    /**
     * 12. Tarik Pengajuan Acara Kembali ke Draf
     */
    public function withdrawEventSubmission(string $eventId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
            return;
        }

        try {
            $stmtCheck = $this->db->prepare("SELECT id, status_verifikasi FROM events WHERE id = :id AND owner_id = :owner_id");
            $stmtCheck->execute(['id' => $eventId, 'owner_id' => $this->ownerId]);
            $event = $stmtCheck->fetch();

            if (!$event || $event['status_verifikasi'] !== 'MENUNGGU_TINJAUAN') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Acara tidak dapat ditarik kembali ke draf.']);
                return;
            }

            $stmtUpdate = $this->db->prepare("UPDATE events SET status_verifikasi = 'DRAF', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmtUpdate->execute(['id' => $eventId]);

            echo json_encode(['success' => true, 'message' => 'Pengajuan acara berhasil ditarik kembali ke draf.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menarik pengajuan: ' . $e->getMessage()]);
        }
    }

    /**
     * 13. Publikasi Acara Resmi (/event-owner/pengaturan-acara)
     */
    public function publishEvent(string $eventId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
            return;
        }

        try {
            $stmtCheck = $this->db->prepare("SELECT id, status_verifikasi FROM events WHERE id = :id AND owner_id = :owner_id");
            $stmtCheck->execute(['id' => $eventId, 'owner_id' => $this->ownerId]);
            $event = $stmtCheck->fetch();

            if (!$event || !in_array($event['status_verifikasi'], ['TERBIT', 'DISETUJUI', 'APPROVED'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Acara belum disetujui oleh admin atau tidak valid untuk diterbitkan.']);
                return;
            }

            $stmt = $this->db->prepare("UPDATE events SET status_verifikasi = 'TERBIT', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute(['id' => $eventId]);

            echo json_encode(['success' => true, 'message' => 'Acara berhasil dipublikasikan secara resmi.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mempublikasikan acara: ' . $e->getMessage()]);
        }
    }

    /**
     * 14. Pembatalan Acara Oleh Owner (/event-owner/dialog-publikasi)
     */
    public function cancelEventByOwner(string $eventId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $alasan = trim($input['alasan_pembatalan'] ?? $input['alasanPembatalan'] ?? '');

        if (strlen($alasan) < 20) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Alasan pembatalan wajib diisi, minimal 20 karakter.']);
            return;
        }

        try {
            $stmtCheck = $this->db->prepare("SELECT id FROM events WHERE id = :id AND owner_id = :owner_id");
            $stmtCheck->execute(['id' => $eventId, 'owner_id' => $this->ownerId]);
            if (!$stmtCheck->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Akses ditolak atau event tidak ditemukan.']);
                return;
            }

            $stmt = $this->db->prepare("
                UPDATE events SET status_verifikasi = 'BATAL', alasan_pembatalan = :alasan, updated_at = CURRENT_TIMESTAMP WHERE id = :id
            ");
            $stmt->execute(['alasan' => $alasan, 'id' => $eventId]);

            echo json_encode(['success' => true, 'message' => 'Acara berhasil dibatalkan. Peserta akan diberi tahu.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal membatalkan acara: ' . $e->getMessage()]);
        }
    }

    /**
     * 15. Tab Ringkasan Detail Acara (/event-owner/ringkasan)
     */
    public function getEventSummaryDetail(string $eventId): void {
        try {
            $stmtCheck = $this->db->prepare("SELECT * FROM events WHERE id = :id AND owner_id = :owner_id");
            $stmtCheck->execute(['id' => $eventId, 'owner_id' => $this->ownerId]);
            $event = $stmtCheck->fetch();

            if (!$event) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Acara tidak ditemukan atau akses ditolak.']);
                return;
            }

            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = :event_id AND status_tiket != 'BATAL'");
            $stmtCount->execute(['event_id' => $eventId]);
            $totalPendaftar = (int)$stmtCount->fetchColumn();

            $stmtHadir = $this->db->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = :event_id AND status_kehadiran = TRUE AND status_tiket != 'BATAL'");
            $stmtHadir->execute(['event_id' => $eventId]);
            $totalHadir = (int)$stmtHadir->fetchColumn();

            // Total Estimasi RAB
            $stmtRab = $this->db->prepare("SELECT SUM(rencana) as total_rencana, SUM(realisasi) as total_realisasi FROM rab_details WHERE event_id = :event_id");
            $stmtRab->execute(['event_id' => $eventId]);
            $rabSummary = $stmtRab->fetch();

            $stmtTickets = $this->db->prepare("SELECT * FROM ticket_categories WHERE event_id = :event_id");
            $stmtTickets->execute(['event_id' => $eventId]);
            $kategoriTiket = $stmtTickets->fetchAll();

            echo json_encode([
                'success' => true,
                'message' => 'Ringkasan detail acara berhasil diambil.',
                'data' => [
                    'event' => $event,
                    'statistik' => [
                        'total_pendaftar' => $totalPendaftar,
                        'total_hadir' => $totalHadir,
                        'persentase_kehadiran' => $totalPendaftar > 0 ? round(($totalHadir / $totalPendaftar) * 100, 1) . '%' : '0%',
                        'total_estimasi_rab' => (float)($rabSummary['total_rencana'] ?? 0),
                        'sisa_kuota' => max(0, (int)$event['kuota'] - $totalPendaftar)
                    ],
                    'kategori_tiket' => $kategoriTiket
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil ringkasan acara: ' . $e->getMessage()]);
        }
    }

    /**
     * 16. Tab Tiket Acara (/event-owner/tiket)
     */
    public function getEventTickets(string $eventId): void {
        try {
            $stmt = $this->db->prepare("SELECT * FROM ticket_categories WHERE event_id = :event_id");
            $stmt->execute(['event_id' => $eventId]);
            echo json_encode(['success' => true, 'message' => 'Daftar kategori tiket berhasil diambil.', 'data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil tiket: ' . $e->getMessage()]);
        }
    }

    /**
     * 17. Tab Rundown Acara (/event-owner/rundown)
     */
    public function getEventRundowns(string $eventId): void {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, c.nama_panitia as pic_nama 
                FROM rundowns r 
                LEFT JOIN committees c ON r.committee_id = c.id 
                WHERE r.event_id = :event_id 
                ORDER BY r.waktu_mulai ASC
            ");
            $stmt->execute(['event_id' => $eventId]);
            echo json_encode(['success' => true, 'message' => 'Daftar rundown berhasil diambil.', 'data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil rundown: ' . $e->getMessage()]);
        }
    }

    /**
     * 18. Tab RAB Anggaran Acara (/event-owner/rab)
     */
    public function getEventRab(string $eventId): void {
        try {
            $stmt = $this->db->prepare("SELECT * FROM rab_details WHERE event_id = :event_id");
            $stmt->execute(['event_id' => $eventId]);
            $rab = $stmt->fetchAll();

            $totalRencana = 0;
            $totalRealisasi = 0;
            foreach ($rab as $item) {
                $totalRencana += (float)$item['rencana'];
                $totalRealisasi += (float)$item['realisasi'];
            }

            echo json_encode([
                'success' => true,
                'message' => 'Rencana Anggaran Biaya berhasil diambil.',
                'data' => [
                    'items' => $rab,
                    'total_rencana' => $totalRencana,
                    'total_realisasi' => $totalRealisasi,
                    'sisa_anggaran' => $totalRencana - $totalRealisasi
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil RAB: ' . $e->getMessage()]);
        }
    }

    /**
     * 19. Tab Panitia Acara (/event-owner/panitia)
     */
    public function getEventCommittees(string $eventId): void {
        try {
            $stmt = $this->db->prepare("SELECT * FROM committees WHERE event_id = :event_id");
            $stmt->execute(['event_id' => $eventId]);
            echo json_encode(['success' => true, 'message' => 'Daftar panitia berhasil diambil.', 'data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil panitia: ' . $e->getMessage()]);
        }
    }

    /**
     * 20. Tab Riwayat Log Pengajuan Acara (/event-owner/pengajuan)
     */
    public function getEventSubmissionLogs(string $eventId): void {
        try {
            $stmt = $this->db->prepare("SELECT * FROM event_submission_logs WHERE event_id = :event_id ORDER BY created_at DESC");
            $stmt->execute(['event_id' => $eventId]);
            echo json_encode(['success' => true, 'message' => 'Riwayat pengajuan berhasil diambil.', 'data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil riwayat pengajuan: ' . $e->getMessage()]);
        }
    }

    /**
     * 21. Tab Analitik Acara (/event-owner/analitik)
     */
    public function getEventAnalytics(string $eventId): void {
        try {
            $stmtCheck = $this->db->prepare("SELECT * FROM events WHERE id = :id AND owner_id = :owner_id");
            $stmtCheck->execute(['id' => $eventId, 'owner_id' => $this->ownerId]);
            $event = $stmtCheck->fetch();

            if (!$event) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Acara tidak ditemukan atau akses ditolak.']);
                return;
            }

            $stmtPendaftar = $this->db->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = :event_id AND status_tiket != 'BATAL'");
            $stmtPendaftar->execute(['event_id' => $eventId]);
            $totalPendaftar = (int)$stmtPendaftar->fetchColumn();

            $stmtHadir = $this->db->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = :event_id AND status_kehadiran = TRUE AND status_tiket != 'BATAL'");
            $stmtHadir->execute(['event_id' => $eventId]);
            $totalHadir = (int)$stmtHadir->fetchColumn();

            $stmtRab = $this->db->prepare("SELECT SUM(rencana) as total_rencana, SUM(realisasi) as total_realisasi FROM rab_details WHERE event_id = :event_id");
            $stmtRab->execute(['event_id' => $eventId]);
            $rabSummary = $stmtRab->fetch();

            $stmtKategori = $this->db->prepare("
                SELECT tc.nama_kategori, tc.kuota, 
                       (SELECT COUNT(*) FROM tickets t WHERE t.event_id = tc.event_id AND t.jenis_tiket = tc.nama_kategori AND t.status_tiket != 'BATAL') as jumlah_terdaftar
                FROM ticket_categories tc
                WHERE tc.event_id = :event_id
            ");
            $stmtKategori->execute(['event_id' => $eventId]);
            $statistikTiket = $stmtKategori->fetchAll();

            echo json_encode([
                'success' => true,
                'message' => 'Data analitik acara berhasil diambil.',
                'data' => [
                    'event_id' => $eventId,
                    'judul_event' => $event['judul_event'],
                    'kuota_total' => (int)$event['kuota'],
                    'total_pendaftar' => $totalPendaftar,
                    'total_hadir' => $totalHadir,
                    'persentase_kehadiran' => $totalPendaftar > 0 ? round(($totalHadir / $totalPendaftar) * 100, 1) . '%' : '0%',
                    'sisa_kuota' => max(0, (int)$event['kuota'] - $totalPendaftar),
                    'anggaran_rab' => [
                        'total_rencana' => (float)($rabSummary['total_rencana'] ?? 0),
                        'total_realisasi' => (float)($rabSummary['total_realisasi'] ?? 0)
                    ],
                    'statistik_kategori_tiket' => $statistikTiket
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil data analitik: ' . $e->getMessage()]);
        }
    }
}