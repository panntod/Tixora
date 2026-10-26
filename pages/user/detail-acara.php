<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../../api/helpers/ViewHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/BannerHelper.php' : __DIR__ . '/../../../api/helpers/BannerHelper.php';
$db = ViewHelper::getDb();
PaymentHelper::ensureSchema($db);
PaymentHelper::expireOverdue($db);

$eventId = trim($_GET['id'] ?? '');

$event = null;
if (!empty($eventId)) {
    $stmt = $db->prepare("
        SELECT e.*, u.nama as owner_nama,
               (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') as total_pendaftar
        FROM events e
        JOIN users u ON e.owner_id = u.id
        WHERE e.id = :id
    ");
    $stmt->execute(['id' => $eventId]);
    $event = $stmt->fetch();
}

if (!$event) {
    // Fallback ke event terbit pertama
    $stmt = $db->query("
        SELECT e.*, u.nama as owner_nama,
               (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') as total_pendaftar
        FROM events e
        JOIN users u ON e.owner_id = u.id
        WHERE e.status_verifikasi IN ('TERBIT', 'PUBLISHED', 'DISETUJUI')
        ORDER BY e.created_at DESC LIMIT 1
    ");
    $event = $stmt->fetch();
}

if (!$event) {
    header('Location: ' . BASE_URL . '/jelajah-acara');
    exit;
}

$stmtCategories = $db->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM tickets t WHERE t.ticket_category_id = c.id AND t.status_tiket != 'BATAL') AS terpakai
    FROM ticket_categories c
    WHERE c.event_id = :event_id
    ORDER BY c.created_at ASC
");
$stmtCategories->execute(['event_id' => $event['id']]);
$ticketCategories = $stmtCategories->fetchAll();

$stmtRundowns = $db->prepare("SELECT * FROM rundowns WHERE event_id = :event_id ORDER BY waktu_mulai ASC");
$stmtRundowns->execute(['event_id' => $event['id']]);
$rundowns = $stmtRundowns->fetchAll();

$sisaKuota = max(0, (int) $event['kuota'] - (int) $event['total_pendaftar']);
$eventTerbuka = in_array($event['status_verifikasi'], ['TERBIT', 'PUBLISHED', 'DISETUJUI'], true)
    && strtotime($event['tanggal_selesai']) > time();

// Kategori yang masih punya kursi; kategori pertama yang tersedia dipilih secara bawaan
$sisaKategori = [];
$defaultCatId = null;
foreach ($ticketCategories as $c) {
    $sisaKategori[$c['id']] = max(0, (int) $c['kuota'] - (int) $c['terpakai']);
    if ($defaultCatId === null && $sisaKategori[$c['id']] > 0 && $sisaKuota > 0) {
        $defaultCatId = $c['id'];
    }
}
$hargaDefault = 0.0;
foreach ($ticketCategories as $c) {
    if ($c['id'] === $defaultCatId) {
        $hargaDefault = (float) $c['harga'];
    }
}
$dateFormatted = date('d M Y · H:i', strtotime($event['tanggal_mulai'])) . '–' . date('H:i', strtotime($event['tanggal_selesai'])) . ' WIB';
$kickerDate = strtoupper(date('d M Y', strtotime($event['tanggal_mulai'])));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — <?= htmlspecialchars($event['judul_event']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
    <script>
        function formatRupiah(n) {
            return n > 0 ? 'Rp' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : 'Gratis (Rp0)';
        }
        function switchTab(evt, tabName) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('tab-active'));
            evt.target.classList.add('tab-active');
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            const target = document.getElementById('tab-' + tabName);
            if (target) target.style.display = 'block';
        }
    </script>
</head>

<body>
    <div class="frame tx-style-ec8b1bf3f3">
        <?php ViewHelper::renderNavbar('jelajah'); ?>

        <main class="page-body tx-style-3ed493afbb">
            <p class="breadcrumb"><a href="<?= BASE_URL ?>/jelajah-acara"
                    style="color:inherit;text-decoration:none">Jelajahi acara</a> /
                <?= htmlspecialchars($event['kategori']) ?>
            </p>

            <!-- Banner acara -->
            <div style="border-radius:16px;overflow:hidden;margin-bottom:24px;line-height:0;background:#F1F1F7">
                <?= BannerHelper::render($event, 'wide', ['kicker' => strtoupper((string) $event['kategori']) . ' · ' . $kickerDate]) ?>
            </div>

            <div class="two-col tx-style-c2f8546b67">
                <div class="col-main">
                    <span class="badge badge-purple"><span
                            class="badge-dot"></span><?= htmlspecialchars($event['status_verifikasi'] === 'TERBIT' ? 'Akan datang' : $event['status_verifikasi']) ?></span>
                    <h1 class="page-title tx-style-d60550f657"><?= htmlspecialchars($event['judul_event']) ?></h1>
                    <div class="meta-row">
                        <span><?= $dateFormatted ?></span>
                        <span><?= htmlspecialchars($event['nama_tempat']) ?>,
                            <?= htmlspecialchars($event['kota']) ?></span>
                        <span>Oleh <?= htmlspecialchars($event['owner_nama']) ?></span>
                    </div>

                    <div class="tabs tx-style-583f758bc5">
                        <span class="tab tab-active" onclick="switchTab(event, 'tentang')">Tentang</span>
                        <span class="tab" onclick="switchTab(event, 'rundown')">Rundown</span>
                        <span class="tab" onclick="switchTab(event, 'lokasi')">Lokasi</span>
                    </div>

                    <div id="tab-tentang" class="tab-content">
                        <p class="tx-style-251e645222"><?= nl2br(htmlspecialchars($event['deskripsi'])) ?></p>
                    </div>

                    <div id="tab-rundown" class="tab-content" style="display:none">
                        <h2 class="tx-style-050fbb4f52">Rundown Acara</h2>
                        <?php if (!empty($rundowns)): ?>
                            <?php foreach ($rundowns as $rd): ?>
                                <div class="rundown-row">
                                    <span class="rundown-time"><?= htmlspecialchars($rd['waktu_mulai']) ?></span>
                                    <div>
                                        <b><?= htmlspecialchars($rd['judul_sesi']) ?></b>
                                        <?php if (!empty($rd['pembicara'])): ?>
                                            · <?= htmlspecialchars($rd['pembicara']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($rd['keterangan'])): ?>
                                            <p class="slate" style="font-size:13px;margin:4px 0 0">
                                                <?= htmlspecialchars($rd['keterangan']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="slate">Rundown detail akan diumumkan oleh penyelenggara menjelang acara.</p>
                        <?php endif; ?>
                    </div>

                    <div id="tab-lokasi" class="tab-content" style="display:none">
                        <h2 class="tx-style-050fbb4f52">Lokasi Penyelenggaraan</h2>
                        <p class="tx-style-251e645222">
                            <b><?= htmlspecialchars($event['nama_tempat']) ?></b><br>
                            <?= htmlspecialchars($event['kota']) ?>, Indonesia.
                        </p>
                    </div>
                </div>

                <aside class="col-side card card-raised">
                    <h2 class="card-title">Pilih tiket</h2>
                    <form method="GET" action="<?= BASE_URL ?>/pendaftaran">
                        <input type="hidden" name="event_id" value="<?= htmlspecialchars($event['id']) ?>">
                        <div class="stack gap-10">
                            <?php if (!empty($ticketCategories)): ?>
                                <?php foreach ($ticketCategories as $idx => $cat): ?>
                                    <?php
                                    $sisaCat = $sisaKategori[$cat['id']];
                                    $habis = $sisaCat <= 0 || $sisaKuota <= 0;
                                    $dipilih = $cat['id'] === $defaultCatId;
                                    $hargaCat = (float) $cat['harga'];
                                    ?>
                                    <label class="option <?= $dipilih ? 'option-selected' : '' ?>"
                                        style="cursor:<?= $habis ? 'not-allowed' : 'pointer' ?>;display:flex;align-items:center;justify-content:space-between;<?= $habis ? 'opacity:.55' : '' ?>">
                                        <div style="display:flex;align-items:center;gap:10px">
                                            <input type="radio" name="category_id" value="<?= htmlspecialchars($cat['id']) ?>"
                                                data-harga="<?= $hargaCat ?>" <?= $dipilih ? 'checked' : '' ?>         <?= $habis ? 'disabled' : '' ?>
                                                onchange="document.querySelectorAll('.col-side .option').forEach(el=>el.classList.remove('option-selected'));this.closest('.option').classList.add('option-selected');document.getElementById('total-biaya').textContent=formatRupiah(parseFloat(this.dataset.harga))">
                                            <div>
                                                <b><?= htmlspecialchars($cat['nama_kategori']) ?></b>
                                                <div class="slate"><?= $habis ? 'Habis' : 'Sisa ' . $sisaCat . ' kursi' ?>
                                                    <?= $cat['wajib_ktm'] ? '· Wajib KTM' : '' ?>
                                                </div>
                                            </div>
                                        </div>
                                        <b
                                            class="option-price"><?= $hargaCat > 0 ? PaymentHelper::rupiah($hargaCat) : 'Gratis' ?></b>
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <label class="option option-selected"
                                    style="cursor:pointer;display:flex;align-items:center;justify-content:space-between">
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <input type="radio" name="category_id" value="" checked>
                                        <div>
                                            <b>Tiket Reguler</b>
                                            <div class="slate">Tersisa <?= $sisaKuota ?> kursi</div>
                                        </div>
                                    </div>
                                    <b class="option-price">Gratis</b>
                                </label>
                            <?php endif; ?>
                        </div>

                        <div class="row-between tx-style-bf720c4bb6" style="margin-top:16px">
                            <b>Sisa Kuota</b>
                            <span class="badge badge-purple"><?= $sisaKuota ?> kursi</span>
                        </div>

                        <div class="total-row" style="margin-top:16px">
                            <span>Biaya</span>
                            <b class="total-value"
                                id="total-biaya"><?= $hargaDefault > 0 ? PaymentHelper::rupiah($hargaDefault) : 'Gratis (Rp0)' ?></b>
                        </div>

                        <?php if ($eventTerbuka && $sisaKuota > 0 && (empty($ticketCategories) || $defaultCatId !== null)): ?>
                            <button type="submit" class="button button-large primary-button full-width tx-style-1b0f4999d2"
                                style="margin-top:16px;cursor:pointer">
                                Daftar Sekarang
                            </button>
                        <?php else: ?>
                            <button type="button" disabled class="button button-large disabled-button full-width"
                                style="margin-top:16px">
                                <?= $eventTerbuka ? 'Tiket Habis' : 'Pendaftaran Ditutup' ?>
                            </button>
                        <?php endif; ?>
                    </form>
                    <p class="help tx-style-91a87015f4" style="margin-top:12px">Tiket gratis langsung aktif. Tiket
                        berbayar aktif setelah pembayaran berhasil; kursi ditahan
                        <?= PaymentHelper::PAYMENT_WINDOW_HOURS ?> jam selama menunggu pembayaran.
                    </p>
                </aside>
            </div>
        </main>

        <footer class="footer">
            <div class="container tx-style-a3d12b9b83">
                <div>
                    <div class="logo">
                        <img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA">
                    </div>
                    <p class="footer-text">Platform acara dan tiket digital.</p>
                </div>
                <nav class="footer-links">
                    <a href="#">Bantuan</a>
                    <a href="#">Syarat &amp; ketentuan</a>
                    <a href="#">Kebijakan privasi</a>
                </nav>
            </div>
        </footer>
    </div>
    <script src="<?= BASE_URL ?>/assets/default-banner.js"></script>
</body>

</html>