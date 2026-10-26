<?php
declare(strict_types=1);

/**
 * Model Ticket untuk operasi validasi dan pemindaian tiket (Scanner / Check-in)
 */
class Ticket {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Mencari tiket berdasarkan kode tiket unik
     */
    public function findByCode(string $code): ?array {
        $stmt = $this->db->prepare("
            SELECT t.*, u.nama AS user_nama, u.email AS user_email, e.judul_event
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            JOIN events e ON t.event_id = e.id
            WHERE t.kode_tiket = :code
            LIMIT 1
        ");
        $stmt->execute([':code' => $code]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Memperbarui status kehadiran peserta menjadi telah hadir
     */
    public function markAsPresent(string $ticketId): bool {
        $stmt = $this->db->prepare("
            UPDATE tickets
            SET status_kehadiran = TRUE, status_tiket = 'SUDAH_PAKAI', updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $ticketId]);
    }
}
