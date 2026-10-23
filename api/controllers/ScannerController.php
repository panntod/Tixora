<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../models/Ticket.php';

/**
 * Controller ScannerController
 * Menangani validasi presensi e-ticket digital (Check-in) secara real-time di lokasi acara
 * dengan mekanisme pencegahan duplikasi (single-use validation) sesuai spesifikasi Tixora.
 */
class ScannerController {
    private PDO $db;
    private Ticket $ticketModel;

    public function __construct() {
        AuthHelper::initSession();
        // Hanya Event Owner atau Panitia (atau Admin) yang diizinkan melakukan scan tiket
        AuthHelper::checkAnyRole(['EVENT_OWNER', 'ADMIN']);
        
        $this->db = Database::getInstance()->getConnection();
        $this->ticketModel = new Ticket($this->db);
    }

    /**
     * Memproses pemindaian kode tiket (QR Code Check-in)
     */
    public function scanTicket(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $kodeTiket = trim($input['kode_tiket'] ?? '');

        if (empty($kodeTiket)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kode tiket wajib disertakan dalam pemindaian.']);
            return;
        }

        try {
            $this->db->beginTransaction();

            // 1. Cari tiket berdasarkan kode unik (beserta data event dan user)
            $ticket = $this->ticketModel->findByCode($kodeTiket);

            if (!$ticket) {
                throw new Exception('Tiket tidak valid atau tidak ditemukan dalam sistem.');
            }

            // 2. Periksa status keaktifan tiket
            if ($ticket['status_tiket'] !== 'AKTIF') {
                throw new Exception("Tiket ini sudah tidak aktif (Status: {$ticket['status_tiket']}).");
            }

            // 3. Mekanisme Single-Use Validation (Anti-duplikasi presensi)
            if ((bool)$ticket['status_kehadiran'] === true) {
                throw new Exception("PERINGATAN: Tiket ini SUDAH DIGUNAKAN (Check-in sebelumnya) oleh peserta {$ticket['user_nama']}!");
            }

            // 4. Ubah status kehadiran menjadi TRUE dan status tiket menjadi 'SUDAH_PAKAI'
            $updated = $this->ticketModel->markAsPresent($ticket['id']);

            if (!$updated) {
                throw new Exception('Gagal memperbarui status kehadiran peserta ke dalam database.');
            }

            $this->db->commit();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Check-in berhasil! Kehadiran peserta telah dicatat.',
                'data' => [
                    'kode_tiket' => $ticket['kode_tiket'],
                    'nama_peserta' => $ticket['user_nama'],
                    'email_peserta' => $ticket['user_email'],
                    'judul_event' => $ticket['judul_event'],
                    'waktu_check_in' => date('Y-m-d H:i:s')
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
}