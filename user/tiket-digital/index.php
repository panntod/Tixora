<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();

$currentUser = ViewHelper::requireLogin('USER');

$ticketId = trim($_GET['id'] ?? '');
$ticketCode = trim($_GET['code'] ?? '');

$ticket = null;
if (!empty($ticketId) || !empty($ticketCode)) {
    $stmt = $db->prepare("
        SELECT t.*, e.judul_event, e.kategori, e.tanggal_mulai, e.tanggal_selesai, e.nama_tempat, e.kota, u.nama as user_nama
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        JOIN users u ON t.user_id = u.id
        WHERE (t.id = :id OR t.kode_tiket = :code) AND t.user_id = :user_id
    ");
    $stmt->execute([
        'id' => $ticketId ?: '00000000-0000-0000-0000-000000000000',
        'code' => $ticketCode ?: 'NONE',
        'user_id' => $currentUser['id']
    ]);
    $ticket = $stmt->fetch();
}

if (!$ticket) {
    // Ambil tiket terbaru milik user
    $stmt = $db->prepare("
        SELECT t.*, e.judul_event, e.kategori, e.tanggal_mulai, e.tanggal_selesai, e.nama_tempat, e.kota, u.nama as user_nama
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        JOIN users u ON t.user_id = u.id
        WHERE t.user_id = :user_id
        ORDER BY t.created_at DESC LIMIT 1
    ");
    $stmt->execute(['user_id' => $currentUser['id']]);
    $ticket = $stmt->fetch();
}

if (!$ticket) {
    header('Location: ' . BASE_URL . '/tiket-saya');
    exit;
}

$isSuccess = isset($_GET['success']);
$kickerDate = strtoupper(date('D · d M Y', strtotime($ticket['tanggal_mulai'])));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — E-Ticket <?= htmlspecialchars($ticket['kode_tiket']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame tx-style-3627af4deb">
        <header class="tx-style-7ce961b601" style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;background:#fff;border-bottom:1px solid #E4E4EE">
            <a href="<?= BASE_URL ?>/tiket-saya" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:#0E1128;font-weight:700">
                <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M12 4l-6 6 6 6M6 10h10" stroke="#0E1128" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span>Tiket Saya</span>
            </a>
            <span class="badge badge-purple">TIXORA E-PASS</span>
        </header>

        <main class="tx-style-d84e93a035">
            <?php if ($isSuccess): ?>
                <div class="alert alert-success" style="margin-bottom:20px;text-align:center">
                    🎉 Pendaftaran Berhasil! E-Ticket Anda telah aktif dan siap digunakan.
                </div>
            <?php endif; ?>

            <div class="ticket tx-style-d944635d3c">
                <div class="poster poster-indigo tx-style-d3596edbac">
                    <div class="poster-art"><span class="art-circle-cyan"></span><span class="art-ring-dark"></span></div>
                    <span class="poster-kicker"><?= $kickerDate ?></span>
                    <span class="poster-title"><?= htmlspecialchars(mb_strimwidth($ticket['judul_event'], 0, 24, '...')) ?></span>
                </div>

                <div class="ticket-stub tx-style-2a1b75c911">
                    <span class="ticket-notch-left"></span>
                    <span class="ticket-notch-right"></span>

                    <div class="row-between">
                        <?php if ($ticket['status_tiket'] === 'BATAL'): ?>
                            <span class="badge badge-error"><span class="badge-dot"></span>Dibatalkan</span>
                        <?php elseif ($ticket['status_kehadiran']): ?>
                            <span class="badge badge-neutral"><span class="badge-dot"></span>Sudah Hadir</span>
                        <?php else: ?>
                            <span class="badge badge-success"><span class="badge-dot"></span>Sah / Aktif</span>
                        <?php endif; ?>
                        <span class="ticket-id tx-style-1169661891"><?= htmlspecialchars($ticket['kode_tiket']) ?></span>
                    </div>

                    <div class="qr-box tx-style-f63b87b3fb" style="text-align:center;padding:16px 0">
                        <!-- SVG QR Code Visual Representation -->
                        <svg width="180" height="180" viewBox="0 0 25 25" role="img" aria-label="QR Code <?= htmlspecialchars($ticket['kode_tiket']) ?>" shape-rendering="crispEdges" style="background:#fff;padding:8px;border-radius:8px">
                            <!-- Outer Position Markers -->
                            <rect width="25" height="25" fill="#fff" />
                            <path fill="#0E1128" d="M1 1h7v7H1zM2 2v5h5V2H2zM3 3h3v3H3zM17 1h7v7h-7zM18 2v5h5V2h-5zM19 3h3v3h-3zM1 17h7v7H1zM2 18v5h5v-5H2zM3 19h3v3H3z" />
                            <!-- Inner decorative matrix dots -->
                            <path fill="#0E1128" d="M10 2h1v1h-1zM13 2h2v1h-2zM10 4h2v2h-2zM13 5h1v1h-1zM11 7h3v1h-3zM3 10h2v1H3zM7 10h1v2H7zM10 10h4v1h-4zM16 10h2v2h-2zM20 10h3v1h-3zM1 12h2v2H1zM4 13h2v1H4zM10 12h2v1h-2zM14 12h3v1h-3zM21 12h1v3h-1zM6 14h3v1H6zM11 14h2v2h-2zM18 14h2v1h-2zM1 15h1v1H1zM14 15h2v1h-2zM10 17h1v1h-1zM12 17h2v3h-2zM16 17h1v1h-1zM20 17h3v1h-3zM11 19h1v1h-1zM15 19h3v1h-3zM19 19h1v2h-1zM10 21h2v2h-2zM14 21h1v2h-1zM17 21h2v1h-2zM21 21h2v3h-2zM13 23h3v1h-3z" />
                        </svg>
                        <span class="help tx-style-1169661891" style="display:block;margin-top:8px">Tunjukkan kode QR ini ke panitia di meja registrasi</span>
                    </div>

                    <dl class="definition">
                        <div class="wide">
                            <dt>ACARA</dt>
                            <dd><?= htmlspecialchars($ticket['judul_event']) ?></dd>
                        </div>
                        <div>
                            <dt>PESERTA</dt>
                            <dd><?= htmlspecialchars($ticket['user_nama']) ?></dd>
                        </div>
                        <div>
                            <dt>KATEGORI</dt>
                            <dd><?= htmlspecialchars($ticket['jenis_tiket']) ?></dd>
                        </div>
                        <div>
                            <dt>WAKTU</dt>
                            <dd><?= date('H:i', strtotime($ticket['tanggal_mulai'])) ?> WIB</dd>
                        </div>
                        <div>
                            <dt>LOKASI</dt>
                            <dd><?= htmlspecialchars($ticket['nama_tempat']) ?></dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="stack gap-10 tx-style-1b0f4999d2" style="margin-top:20px">
                <button onclick="window.print()" class="button button-large primary-button full-width" style="cursor:pointer">
                    Cetak / Simpan Tiket (PDF)
                </button>
                <a href="<?= BASE_URL ?>/tiket-saya" class="button button-large secondary-button full-width" style="text-align:center;text-decoration:none">
                    Kembali ke Tiket Saya
                </a>
            </div>
            <p class="help tx-style-1294536864" style="text-align:center;margin-top:12px">Kode QR bersifat pribadi dan unik per peserta. Jangan dibagikan kepada orang lain.</p>
        </main>
    </div>
</body>

</html>
