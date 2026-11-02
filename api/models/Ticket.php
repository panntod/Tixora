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
     * (menyertakan pemilik acara agar scanner bisa diperiksa hak aksesnya)
     */
    public function findByCode(string $code): ?array {
        $stmt = $this->db->prepare("
            SELECT t.*, u.nama AS user_nama, u.email AS user_email,
                   e.judul_event, e.owner_id AS event_owner_id
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
     * Menandai peserta hadir secara ATOMIK.
     * Kondisi di WHERE memastikan hanya satu scan yang berhasil, walaupun dua petugas
     * memindai tiket yang sama pada detik yang sama. Mengembalikan false bila tiket
     * ternyata sudah dipakai / tidak aktif.
     */
    public function markAsPresent(string $ticketId): bool {
        $stmt = $this->db->prepare("
            UPDATE tickets
            SET status_kehadiran = TRUE, status_tiket = 'SUDAH_PAKAI', updated_at = NOW()
            WHERE id = :id AND status_tiket = 'AKTIF' AND status_kehadiran = FALSE
        ");
        $stmt->execute([':id' => $ticketId]);
        return $stmt->rowCount() === 1;
    }
}
