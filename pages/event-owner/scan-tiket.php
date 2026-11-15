<?php
declare(strict_types=1);

/**
 * Halaman Scan Tiket (Event Owner) — validasi kehadiran peserta di pintu masuk acara.
 *
 * Alur: EO membuka halaman ini -> memindai QR pada e-ticket mahasiswa (kamera, foto QR,
 * atau scanner USB/ketik manual) -> JS (scan-tiket.js) mengirim ke POST /api/scanner/scan
 * -> identitas peserta + notifikasi "Terdaftar" muncul, tiket otomatis menjadi SUDAH_PAKAI.
 */
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EO::loadEvent($db, $owner);
$id = $event['id'];

// Hanya acara yang sudah terbit yang punya tiket aktif untuk dipindai.
$canScan = in_array($event['status_verifikasi'], ['TERBIT', 'PUBLISHED'], true);

$stmt = $db->prepare("SELECT COUNT(*) FILTER (WHERE status_tiket = 'SUDAH_PAKAI') AS hadir,
                             COUNT(*) FILTER (WHERE status_tiket = 'AKTIF') AS belum
                      FROM tickets WHERE event_id = :id");
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();
$hadir = (int) ($row['hadir'] ?? 0);
$belum = (int) ($row['belum'] ?? 0);
$total = $hadir + $belum;
$persen = $total > 0 ? (int) round($hadir / $total * 100) : 0;

// Check-in terbaru (updated_at diperbarui saat tiket ditandai hadir).
$stmt = $db->prepare("SELECT t.kode_tiket, t.jenis_tiket, t.updated_at, u.nama, u.institusi
                      FROM tickets t JOIN users u ON u.id = t.user_id
                      WHERE t.event_id = :id AND t.status_tiket = 'SUDAH_PAKAI'
                      ORDER BY t.updated_at DESC LIMIT 8");
$stmt->execute(['id' => $id]);
$recent = $stmt->fetchAll();

$actions = '<a href="' . EO::e(EO::url('peserta', $id)) . '" class="button secondary-button" style="text-decoration:none;cursor:pointer">Daftar peserta</a>';
$subtitle = EO::badge(EO::eventBadge($event['status_verifikasi'])) . ' &nbsp;<span class="slate" style="font-size:14px">'
    . EO::e(EO::tanggal($event['tanggal_mulai'], true)) . ' · ' . EO::e($event['nama_tempat']) . ', ' . EO::e($event['kota']) . '</span>';

EO::open($db, $owner, $event, 'scan-tiket', $subtitle, $actions, 'Scan tiket', 'Scan tiket');
?>
                <?php if (!$canScan): ?>
                    <div class="alert alert-warning" style="margin-bottom:16px">
                        Scan tiket baru bisa dipakai setelah acara berstatus <b>Terbit</b>. Status acara ini saat ini:
                        <b><?= EO::e(EO::eventBadge($event['status_verifikasi'])[1]) ?></b>.
                    </div>
                <?php else: ?>
                <div id="scanner-app"
                    data-api="<?= EO::e(BASE_URL . '/api/scanner/scan') ?>"
                    data-event="<?= EO::e($id) ?>"
                    data-jsqr="<?= EO::e(BASE_URL . '/assets/jsQR.min.js') ?>"
                    data-hadir="<?= $hadir ?>"
                    data-belum="<?= $belum ?>">

                    <div class="metrics">
                        <div class="metric">
                            <div class="metric-label">SUDAH HADIR</div>
                            <div class="metric-value" id="stat-hadir"><?= $hadir ?></div>
                            <div class="metric-note" id="stat-note"><?= $persen ?>% dari tiket sah</div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">BELUM HADIR</div>
                            <div class="metric-value" id="stat-belum"><?= $belum ?></div>
                            <div class="metric-note">tiket sah menunggu check-in</div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">TOTAL TIKET SAH</div>
                            <div class="metric-value" id="stat-total"><?= $total ?></div>
                            <div class="metric-note">tidak termasuk yang batal / belum bayar</div>
                        </div>
                    </div>

                    <div class="scan-grid">
                        <div class="card scan-col">
                            <h2 class="card-title" style="margin-bottom:12px">Pindai QR tiket</h2>

                            <div class="scan-stage" id="scan-stage">
                                <video id="scan-video" playsinline muted></video>
                                <div class="scan-frame" aria-hidden="true"><span class="scan-line"></span></div>
                                <div class="scan-placeholder" id="scan-placeholder">
                                    <b>Kamera belum aktif</b>
                                    <span>Tekan "Mulai kamera", lalu arahkan ke QR pada tiket mahasiswa.</span>
                                </div>
                            </div>

                            <div class="help help-error" id="scan-cam-error" style="display:none;margin:8px 0 0"></div>

                            <div class="scan-controls">
                                <button type="button" class="button primary-button" id="btn-camera" style="cursor:pointer">Mulai kamera</button>
                                <select class="input" id="cam-select" style="display:none;width:auto;max-width:220px" aria-label="Pilih kamera"></select>
                                <button type="button" class="button secondary-button" id="btn-upload" style="cursor:pointer">Unggah foto QR</button>
                                <input type="file" id="file-qr" accept="image/*" hidden>
                            </div>

                            <form id="manual-form" class="scan-manual" autocomplete="off">
                                <label for="manual-code" class="metric-label">ATAU MASUKKAN KODE TIKET</label>
                                <div class="scan-manual-row">
                                    <input type="text" id="manual-code" class="input" style="flex:1;width:auto;min-width:0"
                                        placeholder="mis. TXR-20261115-ALYA01" maxlength="600" spellcheck="false" autocapitalize="characters">
                                    <button type="submit" class="button secondary-button" style="cursor:pointer">Verifikasi</button>
                                </div>
                                <p class="help">Scanner barcode USB/Bluetooth otomatis mengetik kode ke kolom ini lalu menekan Enter.</p>
                            </form>
                        </div>

                        <div class="scan-col scan-col-result">
                            <div class="card scan-result is-idle" id="scan-result" role="status" aria-live="polite">
                                <div class="scan-result-icon" id="scan-result-icon" aria-hidden="true"></div>
                                <div class="scan-result-body">
                                    <div class="scan-result-kicker" id="scan-result-kicker">MENUNGGU PEMINDAIAN</div>
                                    <div class="scan-result-title" id="scan-result-title">Belum ada tiket dipindai</div>
                                    <div class="scan-result-msg" id="scan-result-msg">Hasil validasi dan identitas peserta akan tampil di sini.</div>
                                    <dl class="scan-detail" id="scan-detail" style="display:none"></dl>
                                </div>
                            </div>

                            <div class="card" style="margin-top:16px">
                                <h2 class="card-title" style="font-size:16px;line-height:22px;margin-bottom:8px">Check-in terbaru</h2>
                                <ul class="scan-recent" id="recent-list">
                                    <?php foreach ($recent as $r): ?>
                                        <li>
                                            <div>
                                                <b><?= EO::e($r['nama']) ?></b>
                                                <div class="cell-sub"><?= EO::e($r['jenis_tiket']) ?> · <?= EO::e($r['kode_tiket']) ?></div>
                                            </div>
                                            <span class="scan-recent-time"><?= EO::e(date('H.i', strtotime((string) $r['updated_at']))) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <p class="empty-note" id="recent-empty" <?= $recent ? 'style="display:none"' : '' ?>>Belum ada peserta yang check-in.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="scan-toast" id="scan-toast" role="alert" aria-live="assertive" hidden></div>
                <?php endif; ?>
<?php
$script = $canScan
    ? '<script src="' . BASE_URL . '/event-owner/scan-tiket.js?v=' . (int) @filemtime(__DIR__ . '/scan-tiket.js') . '"></script>'
    : '';
EO::close($script);