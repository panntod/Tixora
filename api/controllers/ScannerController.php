<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/TicketLinkHelper.php';
require_once __DIR__ . '/../models/Ticket.php';

/**
 * Controller ScannerController
 * Menangani validasi presensi e-ticket digital (Check-in) secara real-time di lokasi acara
 * dengan mekanisme pencegahan duplikasi (single-use validation) sesuai spesifikasi Tixora.
 *
 * Endpoint: POST /api/scanner/scan
 * Body JSON: { "kode_tiket": "TXR-20261115-ABCD" }
 *        atau { "kode_tiket": "<isi QR berupa URL e-ticket lengkap>" }
 */
class ScannerController {
    private PDO $db;
    private Ticket $ticketModel;

    public function __construct() {
        AuthHelper::initSession();
        // Hanya Event Owner (pemilik acara) atau Admin yang diizinkan melakukan scan tiket
        AuthHelper::checkAnyRole(['EVENT_OWNER', 'ADMIN']);

        $this->db = Database::getInstance()->getConnection();
        $this->ticketModel = new Ticket($this->db);
    }

    private function respond(int $status, bool $success, string $message, ?array $data = null): void {
        http_response_code($status);
        $body = ['success' => $success, 'message' => $message];
        if ($data !== null) {
            $body['data'] = $data;
        }
        echo json_encode($body);
    }

    /**
     * Isi QR berupa URL e-ticket ber-tanda-tangan (?code=...&s=...).
     * Jika yang dikirim URL, tanda tangannya wajib valid; jika kode polos, cukup dicari di DB.
     */
    private function extractCode(string $raw, bool &$signatureValid): string {
        $signatureValid = true;
        if (stripos($raw, 'code=') === false) {
            return $raw;
        }
        $query = parse_url($raw, PHP_URL_QUERY) ?: '';
        parse_str($query, $params);
        $code = trim((string)($params['code'] ?? ''));
        $sig = trim((string)($params['s'] ?? ''));
        $signatureValid = TicketLinkHelper::verify($code, $sig);
        return $code;
    }

    /**
     * Memproses pemindaian kode tiket (QR Code Check-in)
     */
    public function scanTicket(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond(405, false, 'Metode HTTP tidak diizinkan.');
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $raw = trim((string)($input['kode_tiket'] ?? ''));

        if ($raw === '' || strlen($raw) > 600) {
            $this->respond(400, false, 'Kode tiket wajib disertakan dalam pemindaian.');
            return;
        }

        $signatureValid = true;
        $kodeTiket = $this->extractCode($raw, $signatureValid);
        if ($kodeTiket === '' || !$signatureValid) {
            $this->respond(400, false, 'QR tiket tidak valid atau telah dimanipulasi.');
            return;
        }

        try {
            // 1. Cari tiket berdasarkan kode unik (beserta data event dan user)
            $ticket = $this->ticketModel->findByCode($kodeTiket);
            if (!$ticket) {
                $this->respond(404, false, 'Tiket tidak valid atau tidak ditemukan dalam sistem.');
                return;
            }

            // 2. Event Owner hanya boleh memindai tiket untuk acaranya sendiri (Admin bebas)
            $user = $_SESSION['user'];
            if ($user['role'] !== 'ADMIN' && $ticket['event_owner_id'] !== $user['id']) {
                $this->respond(403, false, 'Tiket ini bukan untuk acara yang Anda kelola.');
                return;
            }

            // 3. Tiket yang sudah dipakai (single-use)
            if ((bool)$ticket['status_kehadiran'] === true || $ticket['status_tiket'] === 'SUDAH_PAKAI') {
                $this->respond(409, false, "PERINGATAN: Tiket ini SUDAH DIGUNAKAN (check-in sebelumnya) oleh peserta {$ticket['user_nama']}!");
                return;
            }

            // 4. Status keaktifan tiket (menunggu pembayaran / batal tidak boleh masuk)
            if ($ticket['status_tiket'] !== 'AKTIF') {
                $this->respond(400, false, "Tiket ini tidak dapat digunakan (Status: {$ticket['status_tiket']}).");
                return;
            }

            // 5. Update atomik: hanya satu scan yang menang bila dua petugas memindai bersamaan
            if (!$this->ticketModel->markAsPresent($ticket['id'])) {
                $this->respond(409, false, 'PERINGATAN: Tiket ini baru saja digunakan oleh pemindaian lain.');
                return;
            }

            $this->respond(200, true, 'Check-in berhasil! Kehadiran peserta telah dicatat.', [
                'kode_tiket' => $ticket['kode_tiket'],
                'nama_peserta' => $ticket['user_nama'],
                'email_peserta' => $ticket['user_email'],
                'judul_event' => $ticket['judul_event'],
                'waktu_check_in' => date('Y-m-d H:i:s')
            ]);
        } catch (Throwable $e) {
            error_log('[TIXORA] Scan tiket gagal: ' . $e->getMessage());
            $this->respond(500, false, 'Terjadi kesalahan pada server saat memproses tiket.');
        }
    }
}
