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
 * Body JSON: { "kode_tiket": "TXR-20261115-ABCD", "event_id": "<uuid acara, opsional>" }
 *        atau { "kode_tiket": "<isi QR berupa URL e-ticket lengkap>" }
 *
 * "event_id" dikirim oleh halaman scanner Event Owner (/event-owner/scan-tiket?id=...).
 * Bila diisi, tiket milik acara lain (walau masih milik owner yang sama) ditolak agar
 * peserta acara A tidak bisa masuk ke acara B.
 *
 * Respons gagal selalu menyertakan data.reason (kode alasan) supaya antarmuka bisa
 * menampilkan pesan yang tepat:
 *   QR_TIDAK_VALID | TIDAK_DITEMUKAN | BUKAN_ACARA_ANDA | ACARA_LAIN |
 *   SUDAH_DIGUNAKAN | TIDAK_AKTIF | BARU_DIGUNAKAN
 */
class ScannerController {
    private PDO $db;
    private Ticket $ticketModel;

    /** Padanan status tiket untuk pesan yang mudah dibaca petugas. */
    private const STATUS_LABEL = [
        'MENUNGGU_PEMBAYARAN' => 'menunggu pembayaran',
        'BATAL' => 'dibatalkan',
    ];

    public function __construct() {
        AuthHelper::initSession();
        // Hanya Event Owner (pemilik acara) atau Admin yang diizinkan melakukan scan tiket
        AuthHelper::checkAnyRole([AuthHelper::ROLE_EVENT_OWNER, AuthHelper::ROLE_ADMIN]);

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

    /** Respons gagal dengan kode alasan (+ data tambahan seperti identitas peserta). */
    private function fail(int $status, string $reason, string $message, array $extra = []): void {
        $this->respond($status, false, $message, ['reason' => $reason] + $extra);
    }

    /** Identitas peserta yang ditampilkan petugas setelah scan. */
    private function attendeeData(array $ticket): array {
        return [
            'kode_tiket' => $ticket['kode_tiket'],
            'nama_peserta' => $ticket['user_nama'],
            'email_peserta' => $ticket['user_email'],
            'institusi_peserta' => $ticket['user_institusi'] ?? null,
            'whatsapp_peserta' => $ticket['user_whatsapp'] ?? null,
            'jenis_tiket' => $ticket['jenis_tiket'],
            'judul_event' => $ticket['judul_event'],
        ];
    }

    /** "2026-11-15 09:14:03.123456" -> "2026-11-15 09:14:03" */
    private function formatTime(?string $timestamp): string {
        return $timestamp ? substr($timestamp, 0, 19) : date('Y-m-d H:i:s');
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
        $expectedEventId = trim((string)($input['event_id'] ?? ''));

        if ($raw === '' || strlen($raw) > 600) {
            $this->fail(400, 'QR_TIDAK_VALID', 'Kode tiket wajib disertakan dalam pemindaian.');
            return;
        }

        $signatureValid = true;
        $kodeTiket = $this->extractCode($raw, $signatureValid);
        if ($kodeTiket === '' || !$signatureValid) {
            $this->fail(400, 'QR_TIDAK_VALID', 'QR tiket tidak valid atau telah dimanipulasi.');
            return;
        }

        try {
            // 1. Cari tiket berdasarkan kode unik (beserta data event dan user)
            $ticket = $this->ticketModel->findByCode($kodeTiket);
            if (!$ticket) {
                $this->fail(404, 'TIDAK_DITEMUKAN', 'Tiket tidak valid atau tidak ditemukan dalam sistem.');
                return;
            }

            // 2. Event Owner hanya boleh memindai tiket untuk acaranya sendiri (Admin bebas)
            $user = $_SESSION['user'];
            if ($user['role'] !== AuthHelper::ROLE_ADMIN && $ticket['event_owner_id'] !== $user['id']) {
                $this->fail(403, 'BUKAN_ACARA_ANDA', 'Tiket ini bukan untuk acara yang Anda kelola.');
                return;
            }

            // 3. Halaman scanner terikat ke satu acara: tiket acara lain ditolak
            if ($expectedEventId !== '' && strcasecmp((string)$ticket['event_id'], $expectedEventId) !== 0) {
                $this->fail(
                    409,
                    'ACARA_LAIN',
                    "Tiket ini terdaftar untuk acara \"{$ticket['judul_event']}\", bukan acara yang sedang dipindai.",
                    $this->attendeeData($ticket)
                );
                return;
            }

            // 4. Tiket yang sudah dipakai (single-use)
            if ((bool)$ticket['status_kehadiran'] === true || $ticket['status_tiket'] === 'SUDAH_PAKAI') {
                $this->fail(
                    409,
                    'SUDAH_DIGUNAKAN',
                    "PERINGATAN: Tiket ini SUDAH DIGUNAKAN (check-in sebelumnya) oleh peserta {$ticket['user_nama']}!",
                    $this->attendeeData($ticket) + ['waktu_check_in' => $this->formatTime($ticket['updated_at'] ?? null)]
                );
                return;
            }

            // 5. Status keaktifan tiket (menunggu pembayaran / batal tidak boleh masuk)
            if ($ticket['status_tiket'] !== 'AKTIF') {
                $label = self::STATUS_LABEL[$ticket['status_tiket']]
                    ?? strtolower(str_replace('_', ' ', (string)$ticket['status_tiket']));
                $this->fail(
                    400,
                    'TIDAK_AKTIF',
                    "Tiket ini tidak dapat digunakan (status: {$label}).",
                    $this->attendeeData($ticket)
                );
                return;
            }

            // 6. Update atomik: hanya satu scan yang menang bila dua petugas memindai bersamaan
            if (!$this->ticketModel->markAsPresent($ticket['id'])) {
                $this->fail(409, 'BARU_DIGUNAKAN', 'PERINGATAN: Tiket ini baru saja digunakan oleh pemindaian lain.', $this->attendeeData($ticket));
                return;
            }

            $this->respond(200, true, 'Check-in berhasil! Peserta terdaftar, kehadiran telah dicatat.', $this->attendeeData($ticket) + [
                'waktu_check_in' => $this->formatTime($this->ticketModel->getCheckInTime($ticket['id']))
            ]);
        } catch (Throwable $e) {
            error_log('[TIXORA] Scan tiket gagal: ' . $e->getMessage());
            $this->fail(500, 'SERVER_ERROR', 'Terjadi kesalahan pada server saat memproses tiket.');
        }
    }
}