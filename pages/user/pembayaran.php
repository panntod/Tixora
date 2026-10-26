<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();
PaymentHelper::ensureSchema($db);
PaymentHelper::expireOverdue($db);

$currentUser = ViewHelper::requireLogin('USER');

$orderId = trim($_GET['order'] ?? $_POST['order'] ?? '');
if (!preg_match('/^ORD-\d{8}-[0-9A-F]{6}$/', $orderId)) {
    header('Location: ' . BASE_URL . '/tiket-saya');
    exit;
}

$order = PaymentHelper::findOrderForUser($db, $orderId, $currentUser['id']);
if (!$order) {
    header('Location: ' . BASE_URL . '/tiket-saya');
    exit;
}

$self = BASE_URL . '/pembayaran?order=' . urlencode($orderId);
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!ViewHelper::checkCsrf()) {
        $errorMessage = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($action === 'pilih_metode') {
        $metode = $_POST['metode'] ?? '';
        if ($order['status'] !== 'MENUNGGU') {
            $errorMessage = 'Pesanan ini sudah tidak dapat diubah.';
        } elseif (!isset(PaymentHelper::METHODS[$metode])) {
            $errorMessage = 'Pilih salah satu metode pembayaran.';
        } else {
            $db->prepare("
                UPDATE payments
                SET metode = :metode, kode_pembayaran = :kode, updated_at = CURRENT_TIMESTAMP
                WHERE order_id = :order_id AND user_id = :user_id AND status = 'MENUNGGU'
            ")->execute([
                'metode' => $metode,
                'kode' => PaymentHelper::generatePaymentCode($metode),
                'order_id' => $orderId,
                'user_id' => $currentUser['id'],
            ]);
            header('Location: ' . $self);
            exit;
        }
    } elseif ($action === 'simulasi_bayar') {
        if (!PaymentHelper::SANDBOX) {
            $errorMessage = 'Simulasi pembayaran dinonaktifkan.';
        } else {
            $result = PaymentHelper::markPaid($db, $orderId, $currentUser['id']);
            if ($result === 'PAID') {
                header('Location: ' . BASE_URL . '/konfirmasi?order=' . urlencode($orderId));
                exit;
            }
            // EXPIRED / CANCELLED / NOT_FOUND: muat ulang agar status terbaru tampil
            header('Location: ' . $self);
            exit;
        }
    } elseif ($action === 'batal') {
        PaymentHelper::cancelOrder($db, $orderId, $currentUser['id']);
        header('Location: ' . BASE_URL . '/tiket-saya');
        exit;
    }

    // Muat ulang data pesanan setelah aksi yang gagal
    $order = PaymentHelper::findOrderForUser($db, $orderId, $currentUser['id']) ?? $order;
}

if ($order['status'] === 'BERHASIL') {
    header('Location: ' . BASE_URL . '/konfirmasi?order=' . urlencode($orderId));
    exit;
}

$pilihMetode = $order['status'] === 'MENUNGGU' && (empty($order['metode']) || isset($_GET['ganti']));
$metodeInfo = PaymentHelper::METHODS[$order['metode'] ?? ''] ?? null;
$kodeFormatted = $order['kode_pembayaran'] ? trim(chunk_split($order['kode_pembayaran'], 4, ' ')) : '';
$sisaDetik = max(0, (int)$order['sisa_detik']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Pembayaran <?= htmlspecialchars($order['order_id']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame">
        <?php ViewHelper::renderNavbar('tiket'); ?>

        <main class="page-body">
            <h1 class="page-title">Pembayaran</h1>

            <?php if ($errorMessage): ?>
                <div class="alert alert-error" style="margin-bottom:20px"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <?php if (PaymentHelper::SANDBOX && $order['status'] === 'MENUNGGU'): ?>
                <div class="alert alert-warning" style="margin-bottom:20px">
                    <b>Mode sandbox.</b> Pembayaran pada halaman ini disimulasikan; tidak ada uang sungguhan yang berpindah.
                </div>
            <?php endif; ?>

            <div class="two-col">
                <div class="col-main stack gap-24">

                    <?php if ($order['status'] === 'KEDALUWARSA' || $order['status'] === 'DIBATALKAN'): ?>
                        <div class="card">
                            <h2 class="card-title"><?= $order['status'] === 'KEDALUWARSA' ? 'Waktu pembayaran habis' : 'Pesanan dibatalkan' ?></h2>
                            <p class="slate">Kursi untuk pesanan ini sudah dilepas dan tiket tidak berlaku. Anda dapat memesan ulang selama kuota masih tersedia.</p>
                            <div class="row" style="margin-top:16px">
                                <a href="<?= BASE_URL ?>/detail-acara?id=<?= htmlspecialchars($order['event_id']) ?>" class="button button-large primary-button" style="text-decoration:none">Pesan lagi</a>
                                <a href="<?= BASE_URL ?>/tiket-saya" class="button button-large secondary-button" style="text-decoration:none">Tiket saya</a>
                            </div>
                        </div>

                    <?php elseif ($pilihMetode): ?>
                        <form method="POST" action="<?= htmlspecialchars($self) ?>" class="card stack gap-16">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="action" value="pilih_metode">
                            <input type="hidden" name="order" value="<?= htmlspecialchars($orderId) ?>">
                            <h2 class="card-title">Pilih metode pembayaran</h2>
                            <div class="stack gap-10">
                                <?php foreach (PaymentHelper::METHODS as $kode => $m): ?>
                                    <label class="option <?= ($order['metode'] ?? '') === $kode ? 'option-selected' : '' ?>" style="cursor:pointer;display:flex;align-items:center;gap:10px">
                                        <input type="radio" name="metode" value="<?= $kode ?>" required <?= ($order['metode'] ?? '') === $kode ? 'checked' : '' ?>>
                                        <b><?= htmlspecialchars($m['label']) ?></b>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="row">
                                <button type="submit" class="button button-large primary-button" style="cursor:pointer">Lanjutkan</button>
                                <?php if (!empty($order['metode'])): ?>
                                    <a href="<?= htmlspecialchars($self) ?>" class="button button-large secondary-button" style="text-decoration:none">Kembali</a>
                                <?php endif; ?>
                            </div>
                        </form>

                    <?php else: ?>
                        <div class="card stack gap-16">
                            <div class="row-between">
                                <h2 class="card-title" style="margin:0"><?= htmlspecialchars($metodeInfo['label'] ?? 'Pembayaran') ?></h2>
                                <span class="badge badge-warning"><span class="badge-dot"></span>Menunggu pembayaran</span>
                            </div>

                            <div>
                                <span class="help"><?= htmlspecialchars($metodeInfo['type'] ?? 'Kode pembayaran') ?></span>
                                <div style="font-size:26px;font-weight:700;letter-spacing:1px;color:#0E1128;margin-top:4px" id="kode-bayar"><?= htmlspecialchars($kodeFormatted) ?></div>
                                <button type="button" class="button secondary-button small-button" style="margin-top:8px;cursor:pointer"
                                    onclick="navigator.clipboard && navigator.clipboard.writeText('<?= htmlspecialchars($order['kode_pembayaran'] ?? '') ?>'); this.textContent='Tersalin'">Salin nomor</button>
                            </div>

                            <div class="summary-row summary-total">
                                <span>Total yang harus dibayar</span>
                                <b style="color:#4B34DB"><?= PaymentHelper::rupiah($order['jumlah']) ?></b>
                            </div>

                            <div class="alert alert-info">
                                Selesaikan pembayaran dalam <b id="countdown">--:--:--</b>. Setelah waktu habis, pesanan dibatalkan otomatis dan kursi dilepas.
                            </div>

                            <div>
                                <b>Cara membayar</b>
                                <ol class="slate" style="margin:8px 0 0 18px;line-height:22px">
                                    <li>Buka aplikasi bank atau e-wallet Anda.</li>
                                    <li>Pilih menu bayar / transfer, lalu masukkan nomor di atas.</li>
                                    <li>Pastikan nominal sesuai, lalu konfirmasi pembayaran.</li>
                                    <li>Tiket QR aktif otomatis setelah pembayaran diterima.</li>
                                </ol>
                            </div>

                            <div class="row">
                                <?php if (PaymentHelper::SANDBOX): ?>
                                    <form method="POST" action="<?= htmlspecialchars($self) ?>" style="display:inline">
                                        <?= ViewHelper::csrfField() ?>
                                        <input type="hidden" name="action" value="simulasi_bayar">
                                        <input type="hidden" name="order" value="<?= htmlspecialchars($orderId) ?>">
                                        <button type="submit" class="button button-large primary-button" style="cursor:pointer">Simulasikan pembayaran berhasil</button>
                                    </form>
                                <?php endif; ?>
                                <a href="<?= htmlspecialchars($self) ?>&ganti=1" class="button button-large secondary-button" style="text-decoration:none">Ganti metode</a>
                            </div>

                            <form method="POST" action="<?= htmlspecialchars($self) ?>" onsubmit="return confirm('Batalkan pesanan ini? Kursi Anda akan dilepas.')">
                                <?= ViewHelper::csrfField() ?>
                                <input type="hidden" name="action" value="batal">
                                <input type="hidden" name="order" value="<?= htmlspecialchars($orderId) ?>">
                                <button type="submit" class="button ghost-button" style="cursor:pointer;color:#B91C1C">Batalkan pesanan</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="col-side card">
                    <h3 style="margin:0"><?= htmlspecialchars($order['judul_event']) ?></h3>
                    <p class="slate" style="margin:4px 0 0">
                        <?= date('d M Y', strtotime($order['tanggal_mulai'])) ?> · <?= htmlspecialchars($order['nama_tempat']) ?>, <?= htmlspecialchars($order['kota']) ?>
                    </p>
                    <div class="stack gap-10" style="margin-top:16px;border-top:1px solid #E4E4EE;padding-top:12px">
                        <div class="summary-row"><span>No. pesanan</span><b><?= htmlspecialchars($order['order_id']) ?></b></div>
                        <div class="summary-row"><span>Kode tiket</span><b><?= htmlspecialchars($order['kode_tiket']) ?></b></div>
                        <div class="summary-row"><span>Kategori</span><b><?= htmlspecialchars($order['jenis_tiket']) ?></b></div>
                        <div class="summary-row"><span>Jumlah</span><b>1 Tiket</b></div>
                        <div class="summary-row summary-total"><span>Total</span><b style="color:#4B34DB"><?= PaymentHelper::rupiah($order['jumlah']) ?></b></div>
                    </div>
                </aside>
            </div>
        </main>

        <?php ViewHelper::renderFooter(); ?>
    </div>

    <?php if ($order['status'] === 'MENUNGGU'): ?>
    <script>
        (function () {
            var el = document.getElementById('countdown');
            if (!el) return;
            var sisa = <?= $sisaDetik ?>;
            var key = 'txr_reload_<?= htmlspecialchars($orderId) ?>';
            function pad(n) { return (n < 10 ? '0' : '') + n; }
            function tick() {
                if (sisa <= 0) {
                    el.textContent = '00:00:00';
                    // Muat ulang sekali agar status "kedaluwarsa" dari server tampil
                    try { if (!sessionStorage.getItem(key)) { sessionStorage.setItem(key, '1'); location.reload(); } } catch (e) {}
                    return;
                }
                el.textContent = pad(Math.floor(sisa / 3600)) + ':' + pad(Math.floor(sisa % 3600 / 60)) + ':' + pad(sisa % 60);
                sisa--;
                setTimeout(tick, 1000);
            }
            tick();
        })();
    </script>
    <?php endif; ?>
</body>

</html>
