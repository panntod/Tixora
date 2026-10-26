<?php
declare(strict_types=1);

/**
 * PaymentHelper — logika pemesanan tiket berbayar.
 *
 * Alur status:
 *   tickets.status_tiket : MENUNGGU_PEMBAYARAN -> AKTIF (dibayar) | BATAL (kedaluwarsa / dibatalkan)
 *   payments.status      : MENUNGGU -> BERHASIL | KEDALUWARSA | DIBATALKAN
 *
 * Tiket yang menunggu pembayaran tetap menahan kursi (kuota menghitung semua status selain BATAL),
 * sehingga tiket yang kedaluwarsa dijadikan BATAL agar kursinya kembali tersedia.
 *
 * Gateway: SANDBOX = true berarti pembayaran disimulasikan (tombol "Simulasikan pembayaran berhasil").
 * Untuk gateway sungguhan (mis. Midtrans/Xendit), panggil PaymentHelper::markPaid() dari endpoint
 * webhook setelah memverifikasi tanda tangan notifikasi, lalu set SANDBOX menjadi false.
 */
class PaymentHelper
{
    public const SANDBOX = true;
    public const PAYMENT_WINDOW_HOURS = 24;

    /** kode => [label, prefix nomor, jenis] */
    public const METHODS = [
        'VA_BCA'     => ['label' => 'BCA Virtual Account',     'prefix' => '80777', 'type' => 'Nomor Virtual Account'],
        'VA_BNI'     => ['label' => 'BNI Virtual Account',     'prefix' => '8810',  'type' => 'Nomor Virtual Account'],
        'VA_MANDIRI' => ['label' => 'Mandiri Virtual Account', 'prefix' => '88608', 'type' => 'Nomor Virtual Account'],
        'EWALLET'    => ['label' => 'E-Wallet',                'prefix' => '0812',  'type' => 'Nomor Tagihan E-Wallet'],
    ];

    public static function isUuid(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
    }

    public static function rupiah(float|int|string $amount): string
    {
        return 'Rp' . number_format((float) $amount, 0, ',', '.');
    }

    /**
     * Menjalankan api/migrations/002_payment.sql satu kali jika kolom harga belum ada.
     * Hanya menambah struktur baru; tidak mengubah atau menghapus data yang sudah ada.
     */
    public static function ensureSchema(PDO $db): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $exists = $db->query(
            "SELECT 1 FROM information_schema.columns
             WHERE table_name = 'ticket_categories' AND column_name = 'harga'
             LIMIT 1"
        )->fetchColumn();

        if (!$exists) {
            $sql = file_get_contents(__DIR__ . '/../migrations/002_payment.sql');
            if ($sql === false) {
                throw new RuntimeException('File migrasi api/migrations/002_payment.sql tidak ditemukan.');
            }
            $db->exec($sql);
        }
    }

    /**
     * Kedaluwarsakan pesanan yang melewati batas waktu dan lepaskan kursinya.
     * Dipanggil di halaman yang menampilkan kuota atau status pembayaran.
     */
    public static function expireOverdue(PDO $db): void
    {
        $db->exec("
            WITH expired AS (
                UPDATE payments
                SET status = 'KEDALUWARSA', updated_at = CURRENT_TIMESTAMP
                WHERE status = 'MENUNGGU' AND batas_waktu < CURRENT_TIMESTAMP
                RETURNING ticket_id
            )
            UPDATE tickets
            SET status_tiket = 'BATAL', updated_at = CURRENT_TIMESTAMP
            WHERE status_tiket = 'MENUNGGU_PEMBAYARAN'
              AND id IN (SELECT ticket_id FROM expired)
        ");
    }

    public static function generateOrderId(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    public static function generatePaymentCode(string $method): string
    {
        $prefix = self::METHODS[$method]['prefix'];
        $digits = '';
        for ($i = 0; $i < 10; $i++) {
            $digits .= (string) random_int(0, 9);
        }
        return $prefix . $digits;
    }

    /** Ambil pesanan milik user beserta data tiket dan acara. Mengembalikan null jika tidak ada. */
    public static function findOrderForUser(PDO $db, string $orderId, string $userId): ?array
    {
        $stmt = $db->prepare("
            SELECT p.*,
                   EXTRACT(EPOCH FROM (p.batas_waktu - CURRENT_TIMESTAMP))::int AS sisa_detik,
                   t.kode_tiket, t.jenis_tiket, t.status_tiket, t.event_id,
                   e.judul_event, e.tanggal_mulai, e.tanggal_selesai, e.nama_tempat, e.kota,
                   u.nama AS user_nama, u.email AS user_email
            FROM payments p
            JOIN tickets t ON t.id = p.ticket_id
            JOIN events e ON e.id = t.event_id
            JOIN users u ON u.id = p.user_id
            WHERE p.order_id = :order_id AND p.user_id = :user_id
        ");
        $stmt->execute(['order_id' => $orderId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Pesanan terbaru untuk sebuah tiket (dipakai halaman tiket). */
    public static function findOrderByTicket(PDO $db, string $ticketId, string $userId): ?array
    {
        $stmt = $db->prepare("
            SELECT order_id FROM payments
            WHERE ticket_id = :ticket_id AND user_id = :user_id
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute(['ticket_id' => $ticketId, 'user_id' => $userId]);
        $orderId = $stmt->fetchColumn();
        return $orderId ? self::findOrderForUser($db, (string) $orderId, $userId) : null;
    }

    /**
     * Tandai pembayaran berhasil dan aktifkan tiket. Aman dipanggil berulang (idempoten).
     * Mengembalikan: 'PAID' (berhasil / sudah dibayar), 'EXPIRED', 'CANCELLED', atau 'NOT_FOUND'.
     */
    public static function markPaid(PDO $db, string $orderId, string $userId): string
    {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                SELECT id, ticket_id, status, metode, (batas_waktu < CURRENT_TIMESTAMP) AS lewat
                FROM payments
                WHERE order_id = :order_id AND user_id = :user_id
                FOR UPDATE
            ");
            $stmt->execute(['order_id' => $orderId, 'user_id' => $userId]);
            $payment = $stmt->fetch();

            if (!$payment) {
                $db->rollBack();
                return 'NOT_FOUND';
            }
            if ($payment['status'] === 'BERHASIL') {
                $db->commit();
                return 'PAID';
            }
            if ($payment['status'] === 'DIBATALKAN') {
                $db->rollBack();
                return 'CANCELLED';
            }
            if ($payment['status'] === 'KEDALUWARSA' || $payment['lewat']) {
                $db->rollBack();
                self::expireOverdue($db);
                return 'EXPIRED';
            }
            if (empty($payment['metode'])) {
                // Metode belum dipilih: tidak boleh dibayar.
                $db->rollBack();
                return 'NOT_FOUND';
            }

            $db->prepare("
                UPDATE payments
                SET status = 'BERHASIL', dibayar_pada = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ")->execute(['id' => $payment['id']]);

            $db->prepare("
                UPDATE tickets
                SET status_tiket = 'AKTIF', updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND status_tiket = 'MENUNGGU_PEMBAYARAN'
            ")->execute(['id' => $payment['ticket_id']]);

            $db->commit();
            return 'PAID';
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /** Batalkan pesanan yang belum dibayar dan lepaskan kursinya. */
    public static function cancelOrder(PDO $db, string $orderId, string $userId): bool
    {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                SELECT id, ticket_id, status FROM payments
                WHERE order_id = :order_id AND user_id = :user_id
                FOR UPDATE
            ");
            $stmt->execute(['order_id' => $orderId, 'user_id' => $userId]);
            $payment = $stmt->fetch();

            if (!$payment || $payment['status'] !== 'MENUNGGU') {
                $db->rollBack();
                return false;
            }

            $db->prepare("
                UPDATE payments SET status = 'DIBATALKAN', updated_at = CURRENT_TIMESTAMP WHERE id = :id
            ")->execute(['id' => $payment['id']]);
            $db->prepare("
                UPDATE tickets SET status_tiket = 'BATAL', updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND status_tiket = 'MENUNGGU_PEMBAYARAN'
            ")->execute(['id' => $payment['ticket_id']]);

            $db->commit();
            return true;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
