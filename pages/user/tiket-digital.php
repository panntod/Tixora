<?php

declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../../api/helpers/ViewHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/BannerHelper.php' : __DIR__ . '/../../../api/helpers/BannerHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/TicketLinkHelper.php' : __DIR__ . '/../../api/helpers/TicketLinkHelper.php';
$db = ViewHelper::getDb();
PaymentHelper::ensureSchema($db);
PaymentHelper::expireOverdue($db);

$currentUser = ViewHelper::requireLogin(AuthHelper::ROLE_USER);

$ticketId = trim($_GET['id'] ?? '');
$ticketCode = trim($_GET['code'] ?? '');

$ticket = null;
if (!empty($ticketId) || !empty($ticketCode)) {
    $stmt = $db->prepare("
        SELECT t.*, e.judul_event, e.kategori, e.banner_url, e.tanggal_mulai, e.tanggal_selesai, e.nama_tempat, e.kota, u.nama as user_nama
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
        SELECT t.*, e.judul_event, e.kategori, e.banner_url, e.tanggal_mulai, e.tanggal_selesai, e.nama_tempat, e.kota, u.nama as user_nama
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

// Tiket yang belum dibayar: redirect ke halaman pembayaran
if ($ticket['status_tiket'] === 'MENUNGGU_PEMBAYARAN') {
    $stmtOrder = $db->prepare("
        SELECT order_id FROM payments
        WHERE ticket_id = :tid AND user_id = :uid AND status = 'MENUNGGU'
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmtOrder->execute(['tid' => $ticket['id'], 'uid' => $currentUser['id']]);
    $orderId = $stmtOrder->fetchColumn();

    if ($orderId) {
        header('Location: ' . BASE_URL . '/pembayaran?order=' . urlencode($orderId));
        exit;
    }
    // Data inconsistency: tiket MENUNGGU_PEMBAYARAN tanpa payment aktif
    header('Location: ' . BASE_URL . '/tiket-saya');
    exit;
}

$isSuccess = isset($_GET['success']);
// URL QR dihitung SEBELUM HTML dikirim, supaya error konfigurasi tidak memotong halaman.
$qrUrl = '';
$qrError = '';
try {
    $qrUrl = TicketLinkHelper::url($ticket['kode_tiket']);
} catch (Throwable $e) {
    error_log('[TIXORA] Gagal membuat URL QR: ' . $e->getMessage());
    $qrError = 'QR belum dapat dibuat. Hubungi administrator.';
    http_response_code(200);
}

$kickerDate = strtoupper(date('D · d M Y', strtotime($ticket['tanggal_mulai'])));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>TIXORA — E-Ticket <?= htmlspecialchars($ticket['kode_tiket']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame tx-style-3627af4deb">
        <header class="tx-style-7ce961b601"
            style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;background:#fff;border-bottom:1px solid #E4E4EE">
            <a href="<?= BASE_URL ?>/tiket-saya"
                style="display:flex;align-items:center;gap:8px;text-decoration:none;color:#0E1128;font-weight:700">
                <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M12 4l-6 6 6 6M6 10h10" stroke="#0E1128" stroke-width="2" fill="none"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span>Tiket Saya</span>
            </a>
            <span class="badge badge-purple">TIXORA E-PASS</span>
        </header>

        <main class="tx-style-d84e93a035 ticket-page-main">
            <?php if ($isSuccess): ?>
                <div class="alert alert-success" style="margin-bottom:20px;text-align:center">
                    🎉 Pendaftaran Berhasil! E-Ticket Anda telah aktif dan siap digunakan.
                </div>
            <?php endif; ?>

            <div class="ticket ticket-fluid tx-style-d944635d3c">
                <?= BannerHelper::render($ticket, 'ticket', ['kicker' => $kickerDate]) ?>

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
                        <span
                            class="ticket-id tx-style-1169661891"><?= htmlspecialchars($ticket['kode_tiket']) ?></span>
                    </div>

                    <div class="qr-box tx-style-f63b87b3fb" style="text-align:center;padding:16px 0">
                        <!-- QR asli: berisi tautan e-ticket bertanda tangan, dirender oleh assets/qr.js -->
                        <div id="qr" class="qr-canvas"><?= $qrError !== '' ? htmlspecialchars($qrError) : '' ?></div>
                        <noscript><span class="help" style="display:block">Aktifkan JavaScript untuk menampilkan kode
                                QR.</span></noscript>
                        <span class="help tx-style-1169661891" style="display:block;margin-top:8px">Tunjukkan kode QR
                            ini ke panitia di meja registrasi</span>
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
                <button type="button" id="btn-unduh-png" class="button button-large primary-button full-width"
                    style="cursor:pointer">
                    Unduh Tiket (PNG)
                </button>
            </div>
            <p id="ticket-note" class="help tx-style-1294536864" style="text-align:center;margin-top:12px">Kode QR bersifat pribadi dan
                unik per peserta. Jangan dibagikan kepada orang lain.</p>
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
    <script src="<?= BASE_URL ?>/assets/qr.js"></script>
    <script>
        <?php if ($qrUrl !== ''): ?>TixoraQR.render('#qr', <?= json_encode($qrUrl, JSON_UNESCAPED_SLASHES) ?>, {
            label: 'QR Code <?= htmlspecialchars($ticket['kode_tiket'], ENT_QUOTES) ?>'
        });
        <?php endif; ?>
    </script>
    <script>
        // Unduh tiket sebagai PNG: kartu digambar langsung di <canvas> dari isi halaman (tanpa library tambahan).
        (function () {
            var btn = document.getElementById('btn-unduh-png');
            if (!btn) { return; }
            var qrUrl = <?= json_encode($qrUrl, JSON_UNESCAPED_SLASHES) ?>;
            if (qrUrl === '' || typeof TixoraQR === 'undefined') { btn.disabled = true; return; }

            var S = 3, PAD = 24, W = 400, BANNER_H = Math.round(W * 200 / 390), IN = 16;
            var FONT = getComputedStyle(document.body).fontFamily || 'Arial, Helvetica, sans-serif';
            var BG = '#FAFAFC', INK = '#0E1128', MUTED = '#5F6483', LINE = '#E4E4EE';

            function txt(el) { return el ? el.textContent.replace(/\s+/g, ' ').trim() : ''; }

            function setFont(ctx, size, weight, spacing) {
                ctx.font = weight + ' ' + size + 'px ' + FONT;
                if ('letterSpacing' in ctx) { ctx.letterSpacing = (spacing || 0) + 'px'; }
            }

            function wrap(ctx, text, maxW) {
                var words = String(text).split(' '), lines = [], line = '';
                words.forEach(function (w) {
                    var test = line ? line + ' ' + w : w;
                    if (ctx.measureText(test).width <= maxW || !line) { line = test; }
                    else { lines.push(line); line = w; }
                });
                if (line) { lines.push(line); }
                return lines.length ? lines : [''];
            }

            function path(ctx, x, y, w, h, r) {
                var tl = r[0], tr = r[1], br = r[2], bl = r[3];
                ctx.beginPath();
                ctx.moveTo(x + tl, y);
                ctx.lineTo(x + w - tr, y); ctx.arcTo(x + w, y, x + w, y + tr, tr);
                ctx.lineTo(x + w, y + h - br); ctx.arcTo(x + w, y + h, x + w - br, y + h, br);
                ctx.lineTo(x + bl, y + h); ctx.arcTo(x, y + h, x, y + h - bl, bl);
                ctx.lineTo(x, y + tl); ctx.arcTo(x, y, x + tl, y, tl);
                ctx.closePath();
            }

            function collect() {
                var badge = document.querySelector('.ticket-stub .badge');
                var bs = badge ? getComputedStyle(badge) : null;
                var defs = [];
                document.querySelectorAll('.ticket-stub dl.definition > div').forEach(function (d) {
                    defs.push({ label: txt(d.querySelector('dt')), value: txt(d.querySelector('dd')), wide: d.classList.contains('wide') });
                });
                return {
                    badge: txt(badge),
                    badgeBg: bs ? bs.backgroundColor : '#E8F6EC',
                    badgeFg: bs ? bs.color : '#15803D',
                    code: txt(document.querySelector('.ticket-stub .ticket-id')),
                    caption: txt(document.querySelector('.ticket-stub .qr-box .help')),
                    note: txt(document.getElementById('ticket-note')),
                    defs: defs,
                    banner: document.querySelector('.ticket-fluid > img')
                };
            }

            function draw(d, withBanner) {
                var m = document.createElement('canvas').getContext('2d');
                var BOX = 200, QR_BOX_H = IN + BOX + 8 + 18 + IN;

                // ---- ukur dulu (tinggi dinamis karena teks bisa membungkus) ----
                var rows = [], i = 0;
                while (i < d.defs.length) {
                    var a = d.defs[i];
                    if (a.wide) { rows.push([a]); i += 1; } else { rows.push(d.defs[i + 1] && !d.defs[i + 1].wide ? [a, d.defs[i + 1]] : [a]); i += rows[rows.length - 1].length; }
                }
                var innerW = W - IN * 2, colW = (innerW - 12) / 2;
                setFont(m, 14, 'bold', 0);
                var layout = rows.map(function (r) {
                    var cells = r.map(function (c) {
                        var lines = wrap(m, c.value, r.length === 1 ? innerW : colW);
                        return { c: c, lines: lines, h: 18 + 2 + 21 * lines.length };
                    });
                    return { cells: cells, h: Math.max.apply(null, cells.map(function (x) { return x.h; })) };
                });
                var defH = layout.reduce(function (s, r) { return s + r.h; }, 0) + 16 * Math.max(0, layout.length - 1);
                setFont(m, 12, 'normal', 0);
                var noteLines = d.note ? wrap(m, d.note, W) : [];
                var stubH = 20 + 22 + 16 + QR_BOX_H + 16 + defH + 20;
                var cardH = BANNER_H + stubH;
                var totalW = W + PAD * 2;
                var totalH = PAD + cardH + (noteLines.length ? 12 + noteLines.length * 18 : 0) + PAD;

                var canvas = document.createElement('canvas');
                canvas.width = totalW * S; canvas.height = Math.ceil(totalH * S);
                var ctx = canvas.getContext('2d');
                ctx.scale(S, S);
                ctx.textBaseline = 'alphabetic';
                ctx.fillStyle = BG; ctx.fillRect(0, 0, totalW, totalH);

                // ---- kartu ----
                var X = PAD, Y = PAD;
                ctx.save();
                ctx.shadowColor = 'rgba(14,17,40,0.14)'; ctx.shadowBlur = 24; ctx.shadowOffsetY = 8;
                ctx.fillStyle = '#fff'; path(ctx, X, Y, W, cardH, [16, 16, 16, 16]); ctx.fill();
                ctx.restore();

                // banner
                ctx.save();
                path(ctx, X, Y, W, BANNER_H, [16, 16, 0, 0]); ctx.clip();
                var img = d.banner, ok = withBanner && img && img.complete && img.naturalWidth > 0;
                if (ok) {
                    var sc = Math.max(W / img.naturalWidth, BANNER_H / img.naturalHeight);
                    var dw = img.naturalWidth * sc, dh = img.naturalHeight * sc;
                    ctx.drawImage(img, X, Y + (BANNER_H - dh) / 2, dw, dh);   // object-fit: cover, posisi kiri-tengah
                } else {
                    ctx.fillStyle = INK; ctx.fillRect(X, Y, W, BANNER_H);
                    ctx.fillStyle = '#fff'; setFont(ctx, 22, 'bold', 0);
                    wrap(ctx, d.defs.length ? d.defs[0].value : '', W - 48).slice(0, 4).forEach(function (l, k) {
                        ctx.fillText(l, X + 24, Y + 64 + k * 28);
                    });
                }
                ctx.restore();

                // garis sobekan + takik
                var sy = Y + BANNER_H;
                ctx.save();
                ctx.strokeStyle = LINE; ctx.lineWidth = 2; ctx.setLineDash([6, 4]);
                ctx.beginPath(); ctx.moveTo(X + 10, sy + 1); ctx.lineTo(X + W - 10, sy + 1); ctx.stroke();
                ctx.restore();
                ctx.fillStyle = BG;
                ctx.beginPath(); ctx.arc(X, sy, 10, 0, Math.PI * 2); ctx.fill();
                ctx.beginPath(); ctx.arc(X + W, sy, 10, 0, Math.PI * 2); ctx.fill();

                // ---- badan tiket ----
                var cy = sy + 20;
                setFont(ctx, 12, 'bold', 0);
                var bw = 9 + 6 + 6 + ctx.measureText(d.badge).width + 9;
                ctx.fillStyle = d.badgeBg; path(ctx, X + IN, cy, bw, 22, [11, 11, 11, 11]); ctx.fill();
                ctx.fillStyle = d.badgeFg;
                ctx.beginPath(); ctx.arc(X + IN + 9 + 3, cy + 11, 3, 0, Math.PI * 2); ctx.fill();
                ctx.fillText(d.badge, X + IN + 9 + 6 + 6, cy + 15);

                setFont(ctx, 12, 'bold', 0.7);
                ctx.fillStyle = MUTED; ctx.textAlign = 'right';
                ctx.fillText(d.code, X + W - IN, cy + 15);
                ctx.textAlign = 'left';

                // kotak QR
                var by = cy + 22 + 16;
                ctx.lineWidth = 1; ctx.strokeStyle = LINE; ctx.fillStyle = '#fff';
                path(ctx, X + IN + 0.5, by + 0.5, innerW - 1, QR_BOX_H - 1, [12, 12, 12, 12]); ctx.fill(); ctx.stroke();

                var qx = X + W / 2 - BOX / 2, qy = by + IN;
                var mat = TixoraQR.matrix(qrUrl), n = mat.length, quiet = 4;
                var ds = Math.floor(BOX * S / (n + quiet * 2));          // px perangkat per modul (bilangan bulat => tajam)
                var qrPx = ds * (n + quiet * 2);
                var ox = Math.round(qx * S + (BOX * S - qrPx) / 2), oy = Math.round(qy * S + (BOX * S - qrPx) / 2);
                ctx.save(); ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.fillStyle = '#fff'; ctx.fillRect(ox, oy, qrPx, qrPx);
                ctx.fillStyle = INK;
                for (var yy = 0; yy < n; yy++) for (var xx = 0; xx < n; xx++) {
                    if (mat[yy][xx]) { ctx.fillRect(ox + (xx + quiet) * ds, oy + (yy + quiet) * ds, ds, ds); }
                }
                ctx.restore();

                setFont(ctx, 12, 'normal', 0);
                ctx.fillStyle = MUTED; ctx.textAlign = 'center';
                ctx.fillText(d.caption, X + W / 2, qy + BOX + 8 + 13);
                ctx.textAlign = 'left';

                // definisi (ACARA, PESERTA, KATEGORI, WAKTU, LOKASI)
                var dy = by + QR_BOX_H + 16;
                layout.forEach(function (r) {
                    r.cells.forEach(function (cell, k) {
                        var cx = X + IN + (r.cells.length === 1 ? 0 : k * (colW + 12));
                        setFont(ctx, 12, 'bold', 0.7); ctx.fillStyle = MUTED;
                        ctx.fillText(cell.c.label, cx, dy + 13);
                        setFont(ctx, 14, 'bold', 0); ctx.fillStyle = INK;
                        cell.lines.forEach(function (l, li) { ctx.fillText(l, cx, dy + 18 + 2 + 15 + li * 21); });
                    });
                    dy += r.h + 16;
                });

                // catatan di bawah kartu
                setFont(ctx, 12, 'normal', 0); ctx.fillStyle = MUTED; ctx.textAlign = 'center';
                noteLines.forEach(function (l, k) { ctx.fillText(l, X + W / 2, Y + cardH + 12 + 13 + k * 18); });

                return canvas;
            }

            function save(canvas, name, done) {
                canvas.toBlob(function (blob) {
                    if (!blob) { done(false); return; }
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url; a.download = name;
                    document.body.appendChild(a); a.click(); a.remove();
                    setTimeout(function () { URL.revokeObjectURL(url); }, 5000);
                    done(true);
                }, 'image/png');
            }

            btn.addEventListener('click', function () {
                var label = btn.textContent;
                btn.disabled = true; btn.textContent = 'Menyiapkan…';
                var ready = (document.fonts && document.fonts.ready) ? document.fonts.ready : Promise.resolve();
                ready.then(function () {
                    var d = collect();
                    var name = 'Tiket-' + (d.code || 'TIXORA').replace(/[^A-Za-z0-9_-]+/g, '') + '.png';
                    var canvas;
                    try { canvas = draw(d, true); canvas.getContext('2d').getImageData(0, 0, 1, 1); }
                    catch (e) { canvas = draw(d, false); }   // banner dari domain lain membuat canvas "tainted": ulangi tanpa foto banner
                    save(canvas, name, function (ok) {
                        btn.disabled = false; btn.textContent = ok ? label : 'Gagal, coba lagi';
                        if (!ok) { setTimeout(function () { btn.textContent = label; }, 2500); }
                    });
                }).catch(function () { btn.disabled = false; btn.textContent = label; });
            });
        })();
    </script>
</body>

</html>