<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();
PaymentHelper::ensureSchema($db);
PaymentHelper::expireOverdue($db);

$currentUser = ViewHelper::requireLogin('USER');

$orderId = trim($_GET['order'] ?? '');
$ticketId = trim($_GET['ticket'] ?? '');
$data = null;

if ($orderId !== '') {
    // Konfirmasi pembayaran: hanya pesanan yang benar-benar sudah dibayar
    $order = PaymentHelper::findOrderForUser($db, $orderId, $currentUser['id']);
    if (!$order) {
        header('Location: ' . BASE_URL . '/tiket-saya');
        exit;
    }
    if ($order['status'] !== 'BERHASIL') {
        header('Location: ' . BASE_URL . '/pembayaran?order=' . urlencode($orderId));
        exit;
    }
    $data = [
        'no_pesanan' => $order['order_id'],
        'total' => PaymentHelper::rupiah($order['jumlah']),
        'total_label' => 'TOTAL DIBAYAR',
        'ticket_id' => $order['ticket_id'],
        'kode_tiket' => $order['kode_tiket'],
        'judul_event' => $order['judul_event'],
        'jenis_tiket' => $order['jenis_tiket'],
        'tanggal_mulai' => $order['tanggal_mulai'],
        'nama_tempat' => $order['nama_tempat'],
        'kota' => $order['kota'],
    ];
} elseif (PaymentHelper::isUuid($ticketId)) {
    // Konfirmasi pendaftaran tiket gratis
    $stmt = $db->prepare("
        SELECT t.id, t.kode_tiket, t.jenis_tiket, e.judul_event, e.tanggal_mulai, e.nama_tempat, e.kota
        FROM tickets t JOIN events e ON e.id = t.event_id
        WHERE t.id = :id AND t.user_id = :uid AND t.status_tiket = 'AKTIF'
    ");
    $stmt->execute(['id' => $ticketId, 'uid' => $currentUser['id']]);
    $t = $stmt->fetch();
    if ($t) {
        $data = [
            'no_pesanan' => $t['kode_tiket'],
            'total' => 'Gratis',
            'total_label' => 'BIAYA',
            'ticket_id' => $t['id'],
            'kode_tiket' => $t['kode_tiket'],
            'judul_event' => $t['judul_event'],
            'jenis_tiket' => $t['jenis_tiket'],
            'tanggal_mulai' => $t['tanggal_mulai'],
            'nama_tempat' => $t['nama_tempat'],
            'kota' => $t['kota'],
        ];
    }
}

if (!$data) {
    header('Location: ' . BASE_URL . '/tiket-saya');
    exit;
}

$hariId = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$ts = strtotime($data['tanggal_mulai']);
$waktuTempat = $hariId[(int)date('w', $ts)] . ', ' . date('d M Y', $ts) . ' · ' . date('H.i', $ts) . ' WIB · '
    . $data['nama_tempat'] . ', ' . $data['kota'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Konfirmasi Pendaftaran</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame tx-style-cd722f105b">
        <?php ViewHelper::renderNavbar('tiket'); ?>
        <main class="tx-style-a9cba441f2">
            <div class="card tx-style-06dd30d274">
                <div class="success-icon"><svg width="28" height="28" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg></div>
                <h1 class="page-title tx-style-c5fb5b6e9c">Pendaftaran berhasil</h1>
                <p class="slate tx-style-5bd78839ec">Tiket Anda sudah aktif dan tersimpan di akun Anda.</p>
                <dl class="definition tx-style-353c10ff2b">
                    <div>
                        <dt>NO. PESANAN</dt>
                        <dd><?= htmlspecialchars($data['no_pesanan']) ?></dd>
                    </div>
                    <div>
                        <dt><?= $data['total_label'] ?></dt>
                        <dd><?= htmlspecialchars($data['total']) ?></dd>
                    </div>
                    <div class="wide">
                        <dt>ACARA</dt>
                        <dd><?= htmlspecialchars($data['judul_event']) ?> · <?= htmlspecialchars($data['jenis_tiket']) ?> × 1</dd>
                    </div>
                    <div class="wide">
                        <dt>WAKTU DAN TEMPAT</dt>
                        <dd><?= htmlspecialchars($waktuTempat) ?></dd>
                    </div>
                </dl>
                <div class="alert alert-info tx-style-8e56756bee">Tunjukkan kode QR pada tiket saat registrasi ulang di lokasi.</div>
                <div class="row tx-style-03c4bb6fce">
                    <a href="<?= BASE_URL ?>/tiket-digital?id=<?= htmlspecialchars($data['ticket_id']) ?>" class="button button-large primary-button" style="text-decoration:none">Lihat tiket saya</a>
                    <a href="<?= BASE_URL ?>/jelajah-acara" class="button button-large secondary-button" style="text-decoration:none">Jelajahi acara lain</a>
                </div>
            </div>
        </main>
        <?php ViewHelper::renderFooter(); ?>
    </div>
</body>

</html>
