<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/BannerHelper.php' : __DIR__ . '/../../api/helpers/BannerHelper.php';
$db = ViewHelper::getDb();
PaymentHelper::ensureSchema($db);
PaymentHelper::expireOverdue($db);

// Ambil event yang berstatus TERBIT
$stmt = $db->query("
    SELECT e.*, u.nama as owner_nama,
           (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') as total_pendaftar,
           COALESCE((SELECT MIN(c.harga) FROM ticket_categories c WHERE c.event_id = e.id), 0) as harga_mulai
    FROM events e
    JOIN users u ON e.owner_id = u.id
    WHERE e.status_verifikasi IN ('TERBIT', 'PUBLISHED', 'DISETUJUI')
    ORDER BY e.tanggal_mulai ASC
    LIMIT 8
");
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Platform Acara &amp; Tiket Digital</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css?v=<?= @filemtime(__DIR__ . '/styles.css') ?: time() ?>">
</head>

<body>
    <div class="landing-page">
        <?php ViewHelper::renderNavbar('beranda'); ?>

        <section class="hero">
            <div class="container">
                <div class="hero-copy">
                    <div class="hero-badge-row">
                        <span class="badge badge-purple"><span class="badge-dot"></span>Seminar, workshop,
                            konferensi</span>
                    </div>
                    <h1 class="hero-title">Temukan acaranya.<br>Simpan tiketnya.<br><span
                            class="hero-title-accent">Masuk tanpa antre.</span></h1>
                    <p class="hero-lead">TIXORA mempertemukan peserta dan penyelenggara acara<br>kampus maupun umum:
                        pendaftaran, tiket QR, dan<br>persetujuan acara dalam satu tempat.</p>

                    <form action="<?= BASE_URL ?>/jelajah-acara" method="GET" class="search-bar"
                        style="display:flex;align-items:center;padding-right:6px">
                        <input type="text" name="search" class="search-field tx-style-cba6e71c35"
                            placeholder="Cari acara, topik, atau penyelenggara">
                        <button type="submit" class="button button-large primary-button tx-style-2eda1daa91">Cari
                            acara</button>
                    </form>

                    <div class="popular-row">
                        <span>Populer:</span>
                        <a href="<?= BASE_URL ?>/jelajah-acara?kategori=Seminar" class="chip">Seminar</a>
                        <a href="<?= BASE_URL ?>/jelajah-acara?kategori=Workshop" class="chip">Workshop</a>
                        <a href="<?= BASE_URL ?>/jelajah-acara" class="chip">Gratis</a>
                        <a href="<?= BASE_URL ?>/jelajah-acara?sortBy=terdekat" class="chip">Akan Datang</a>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="ticket">
                        <!-- Contoh tiket di hero: banner statis dari generator -->
                        <img style="display:block;width:100%;height:200px;border-radius:16px 16px 0 0"
                            data-default-banner="1" data-layout="ticket" data-kicker="SABTU · 24 OKT 2026"
                            data-title="SENTEK|2026">
                        <div class="ticket-stub">
                            <span class="ticket-notch-left"></span>
                            <span class="ticket-notch-right"></span>
                            <div class="ticket-info">
                                <span class="badge badge-success"><span class="badge-dot"></span>Sah</span>
                                <h3 class="ticket-title">Seminar Nasional Teknologi Informatika</h3>
                                <p class="ticket-meta">Auditorium Utama, Malang · 08.00 WIB</p>
                                <p class="ticket-id">TXR-2610-0482</p>
                            </div>
                            <svg width="84" height="84" viewBox="0 0 9 9" role="img" aria-label="Placeholder kode QR"
                                shape-rendering="crispEdges">
                                <path d="M0 0h3v3H0zM6 0h3v3H6zM0 6h3v3H0z" fill="#0E1128" />
                                <path d="M1 1h1v1H1zM7 1h1v1H7zM1 7h1v1H1z" fill="#FFFFFF" />
                                <path d="M4 0h1v2H4zM4 3h2v1H4zM4 5h1v1H4zM4 7h1v2H4zM6 4h1v1H6zM8 4h1v2H8zM6 7h2v1H6z"
                                    fill="#0E1128" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section tx-style-c1bf29204c">
            <div class="container">
                <div class="section-header tx-style-535c87a53e">
                    <div>
                        <h2 class="section-title">Acara pilihan minggu ini</h2>
                        <p class="section-subtitle">Daftar sekarang sebelum kuota habis.</p>
                    </div>
                    <a href="<?= BASE_URL ?>/jelajah-acara" class="link-text"
                        style="color:#4B34DB;font-weight:700">Lihat semua acara &rarr;</a>
                </div>

                <div class="event-grid tx-style-7044f63f91">
                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $idx => $ev): ?>
                            <?php
                            $sisaKuota = max(0, (int) $ev['kuota'] - (int) $ev['total_pendaftar']);
                            $dateStr = date('d M Y', strtotime($ev['tanggal_mulai']));
                            ?>
                            <article class="event-card tx-style-30e741d98e">
                                <?= BannerHelper::render($ev, 'card') ?>
                                <div class="event-card-body">
                                    <span class="badge badge-purple"><span class="badge-dot"></span>Akan datang</span>
                                    <h3 class="event-card-title"><?= htmlspecialchars($ev['judul_event']) ?></h3>
                                    <p class="event-card-meta"><?= $dateStr ?> · <?= htmlspecialchars($ev['nama_tempat']) ?></p>
                                    <div class="event-card-footer tx-style-34c37090dc">
                                        <?php if ((float) $ev['harga_mulai'] > 0): ?>
                                            <b class="event-card-price">Mulai <?= PaymentHelper::rupiah($ev['harga_mulai']) ?></b>
                                        <?php else: ?>
                                            <b class="event-card-price price-free">Gratis</b>
                                        <?php endif; ?>
                                        <span class="event-card-left">Sisa <?= $sisaKuota ?> kursi</span>
                                    </div>
                                    <a href="<?= BASE_URL ?>/detail-acara?id=<?= $ev['id'] ?>" class="button primary-button"
                                        style="width:100%;margin-top:12px;text-align:center">Lihat Detail &amp; Tiket</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="tx-style-8771fb4e0a">
                            <p class="slate">Belum ada acara yang dipublikasikan saat ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="section tx-style-8e4344d65e">
            <div class="container">
                <div class="section-header tx-style-d07120d3fa">
                    <h2 class="section-title">Dari pendaftaran sampai pintu masuk</h2>
                </div>
                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Temukan acara</h3>
                        <p class="step-text">Cari berdasarkan kategori, tanggal, atau penyelenggara. Status kursi dan
                            jadwal terlihat sebelum mendaftar.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Daftar &amp; Dapatkan Tiket</h3>
                        <p class="step-text">Pilih kategori tiket, isi data peserta, lalu terima e-ticket digital
                            lengkap dengan kode unik.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Tunjukkan kode QR</h3>
                        <p class="step-text">Tiket tersimpan di akun Anda. Petugas memindai kode tiket dan kehadiran
                            Anda langsung terkonfirmasi.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section tx-style-ba11ce87eb">
            <div class="container">
                <div class="organizer-card">
                    <div class="organizer-copy">
                        <span class="badge badge-purple"><span class="badge-dot"></span>Untuk penyelenggara</span>
                        <h2 class="organizer-title">Kelola acara dari draf sampai terbit.</h2>
                        <ul class="feature-list">
                            <li class="feature-item"><span class="feature-dot"></span><span>Wizard pembuatan acara
                                    dengan rundown dan kategori tiket.</span></li>
                            <li class="feature-item"><span class="feature-dot"></span><span>Pantau pendaftar, kuota, dan
                                    anggaran (RAB) di satu dasbor.</span></li>
                            <li class="feature-item"><span class="feature-dot"></span><span>Ajukan ke admin, dapatkan
                                    keputusan, lalu publikasikan.</span></li>
                        </ul>
                        <a href="<?= BASE_URL ?>/event-owner/buat-acara" class="button button-large primary-button"
                            style="display:inline-flex">Ajukan acara Anda</a>
                    </div>
                    <div class="approval-panel">
                        <div class="panel-label">ALUR PERSETUJUAN</div>
                        <div class="status-row">
                            <span class="badge badge-neutral"><span class="badge-dot"></span>Draf</span>
                            <span class="badge badge-warning"><span class="badge-dot"></span>Menunggu tinjauan</span>
                            <span class="badge badge-info"><span class="badge-dot"></span>Disetujui</span>
                            <span class="badge badge-success"><span class="badge-dot"></span>Terbit</span>
                        </div>
                        <div class="event-row">
                            <span>Seminar Nasional Teknologi Informasi</span>
                            <span class="badge badge-success"><span class="badge-dot"></span>Terbit</span>
                        </div>
                        <p class="panel-note">Alur transparan: ajukan draf acara dan pantau proses verifikasi langsung
                            oleh Admin Kampus.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="tx-style-61ee32661d">
            <div class="container">
                <div class="cta tx-style-13313b1a3a">
                    <h2 class="cta-title">Punya acara berikutnya? Mulai dari satu draf.</h2>
                    <div class="cta-actions">
                        <a href="<?= BASE_URL ?>/register" class="button button-large white-button">Hubungi Admin</a>
                    </div>
                </div>
            </div>
        </section>

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