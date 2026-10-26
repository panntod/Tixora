<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();
PaymentHelper::ensureSchema($db);
PaymentHelper::expireOverdue($db);

$currentUser = ViewHelper::requireLogin('USER');

$ticketId = trim($_GET['id'] ?? '');
$order = null;
if (PaymentHelper::isUuid($ticketId)) {
    $order = PaymentHelper::findOrderByTicket($db, $ticketId, $currentUser['id']);
}
if (!$order) {
    header('Location: ' . BASE_URL . '/tiket-saya');
    exit;
}
// Sudah dibayar -> tampilkan tiket aktif; kedaluwarsa/dibatalkan -> halaman pembayaran menjelaskan statusnya
if ($order['status'] === 'BERHASIL') {
    header('Location: ' . BASE_URL . '/tiket-digital?id=' . urlencode($ticketId));
    exit;
}
if ($order['status'] !== 'MENUNGGU') {
    header('Location: ' . BASE_URL . '/pembayaran?order=' . urlencode($order['order_id']));
    exit;
}
$ts = strtotime($order['tanggal_mulai']);
$kicker = strtoupper(date('D · d M Y', $ts));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Tiket Digital — Menunggu Pembayaran</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame tx-style-5b746db366">
        <header
            class="tx-style-7ce961b601">
            <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true">
                <path d="M12 4l-6 6 6 6M6 10h10" stroke="#0E1128" stroke-width="2" fill="none" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg><a href="<?= BASE_URL ?>/tiket-saya" class="tx-style-a1b26d50e6" style="text-decoration:none">Tiket saya</a></header>
        <main class="tx-style-d84e93a035" style="max-width:390px;margin:0 auto">
            <div class="ticket tx-style-d944635d3c">
                <div class="">
                    <div class="poster poster-indigo tx-style-d3596edbac">
                        <div class="poster-art"><span class="art-circle-cyan"></span><span class="art-ring-dark"></span>
                        </div><span class="poster-kicker"><?= htmlspecialchars($kicker) ?></span><span class="poster-title"><?= htmlspecialchars(mb_strimwidth($order['judul_event'], 0, 24, '...')) ?></span>
                    </div>
                </div>
                <div class="ticket-stub tx-style-2a1b75c911"><span class="ticket-notch-left"></span><span
                        class="ticket-notch-right"></span>
                    <div class="row-between"><span class="badge badge-warning"><span class="badge-dot"></span>Menunggu
                            pembayaran</span><span class="ticket-id tx-style-1169661891"><?= htmlspecialchars($order['kode_tiket']) ?></span></div>
                    <div class="tx-style-f63b87b3fb">
                        <div class="qr-box tx-style-5d2f459eac"><b
                                class="tx-style-1444c6eaca">Kode QR belum aktif</b><span class="help tx-style-594d005c22"
                               >Selesaikan pembayaran untuk mengaktifkan tiket.</span>
                        </div>
                    </div>
                    <dl class="definition">
                        <div class="wide">
                            <dt>ACARA</dt>
                            <dd><?= htmlspecialchars($order['judul_event']) ?></dd>
                        </div>
                        <div>
                            <dt>PESERTA</dt>
                            <dd><?= htmlspecialchars($order['user_nama']) ?></dd>
                        </div>
                        <div>
                            <dt>KATEGORI</dt>
                            <dd><?= htmlspecialchars($order['jenis_tiket']) ?></dd>
                        </div>
                        <div>
                            <dt>WAKTU</dt>
                            <dd><?= date('H.i', $ts) ?> WIB</dd>
                        </div>
                        <div>
                            <dt>LOKASI</dt>
                            <dd><?= htmlspecialchars($order['nama_tempat']) ?></dd>
                        </div>
                    </dl>
                </div>
            </div>
            <div class="stack gap-10 tx-style-1b0f4999d2"><a
                    href="<?= BASE_URL ?>/pembayaran?order=<?= urlencode($order['order_id']) ?>"
                    class="button button-large primary-button full-width" style="text-align:center;text-decoration:none">Bayar sekarang · <?= PaymentHelper::rupiah($order['jumlah']) ?></a></div>
        </main>
    </div>
</body>

</html>