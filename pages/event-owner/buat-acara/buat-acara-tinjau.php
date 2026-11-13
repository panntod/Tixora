<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../../api/helpers/ViewHelper.php';
require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/EventHelper.php' : __DIR__ . '/../../../api/helpers/EventHelper.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EventHelper::requireDraft($db, $owner);
EventHelper::ensureSchema($db);
$eventId = (string) $event['id'];

$review = EventHelper::review($db, $event);
$topError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ajukan') {
    if (!ViewHelper::checkCsrf()) {
        $topError = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (!$review['ready']) {
        $topError = 'Lengkapi bagian yang bertanda "Perlu dilengkapi" sebelum mengajukan.';
    } else {
        try {
            $db->beginTransaction();
            $upd = $db->prepare("
                UPDATE events
                SET status_verifikasi = 'MENUNGGU_TINJAUAN', alasan_penolakan = NULL, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND owner_id = :owner AND status_verifikasi IN ('DRAF', 'DITOLAK')");
            $upd->execute(['id' => $eventId, 'owner' => $owner['id']]);
            if ($upd->rowCount() === 1) {
                $db->prepare("INSERT INTO event_submission_logs (event_id, status, catatan)
                              VALUES (:id, 'MENUNGGU_TINJAUAN', 'Acara diajukan untuk peninjauan admin.')")
                    ->execute(['id' => $eventId]);
            }
            $db->commit();
            EventHelper::redirect(BASE_URL . '/event-owner/acara-saya?diajukan=1');
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[TIXORA] buat-acara langkah 4: ' . $e->getMessage());
            $topError = 'Acara gagal diajukan karena kesalahan server. Silakan coba lagi.';
        }
    }
}

$h = [EventHelper::class, 'h'];
$badge = static fn(bool $ok, string $okLabel = 'Lengkap', string $badLabel = 'Perlu dilengkapi'): string => $ok
    ? '<span class="badge badge-success"><span class="badge-dot"></span>' . $okLabel . '</span>'
    : '<span class="badge badge-warning"><span class="badge-dot"></span>' . $badLabel . '</span>';
$fix = static fn(int $step): string => '<a href="' . EventHelper::stepUrl($step, $eventId) . '" style="color:#4b34db;font-weight:600">Lengkapi di langkah ' . $step . '</a>';

// Ringkasan tiket: "Umum Rp250.000 (200), Mahasiswa Rp150.000 (100) dan VIP Gratis (20)".
$items = array_map(static fn($k) => $k['nama_kategori'] . ' '
    . ((float) $k['harga'] > 0 ? EO::rp($k['harga']) : 'Gratis') . ' (' . (int) $k['kuota'] . ')', $review['kategori']);
$daftarTiket = count($items) > 1 ? implode(', ', array_slice($items, 0, -1)) . ' dan ' . end($items) : ($items[0] ?? '');

$infoOk = !$review['info'];
$tiketOk = $review['total_kuota'] > 0;
$rundownOk = $review['rundown'] > 0;

EventHelper::renderTop($db, $owner, 4, $event);
?>
                <?php if ($topError): ?>
                    <div class="alert alert-error" style="width:880px;margin-bottom:16px"><?= $h($topError) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= EventHelper::stepUrl(4, $eventId) ?>">
                    <?= ViewHelper::csrfField() ?>
                    <input type="hidden" name="aksi" value="ajukan">

                    <div class="stack gap-24" style="width:880px">
                        <div class="alert alert-info">Setelah diajukan, acara berstatus <b>Menunggu tinjauan</b> dan tidak
                            dapat diedit sampai admin memberi keputusan.</div>

                        <div class="card">
                            <div class="row-between" style="margin-bottom:12px">
                                <h2 class="card-title" style="margin:0">Informasi &amp; jadwal</h2>
                                <?= $badge($infoOk) ?>
                            </div>
                            <dl class="definition">
                                <div>
                                    <dt>NAMA ACARA</dt>
                                    <dd><?= $h($event['judul_event']) ?></dd>
                                </div>
                                <div>
                                    <dt>KATEGORI</dt>
                                    <dd><?= $h($event['kategori']) ?></dd>
                                </div>
                                <div>
                                    <dt>WAKTU</dt>
                                    <dd><?= $event['tanggal_mulai'] !== $event['tanggal_selesai'] ? $h(EventHelper::rentang((string) $event['tanggal_mulai'], (string) $event['tanggal_selesai'])) : '—' ?></dd>
                                </div>
                                <div>
                                    <dt>LOKASI</dt>
                                    <dd><?= trim((string) $event['nama_tempat']) !== '' ? $h($event['nama_tempat'] . ', ' . $event['kota']) : '—' ?></dd>
                                </div>
                            </dl>
                            <?php if (!$infoOk): ?>
                                <p class="slate" style="font-size:14px;line-height:21px;margin-top:12px">
                                    <?= $h(implode(' ', array_values($review['info']))) ?> <?= $fix(1) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="card">
                            <div class="row-between" style="margin-bottom:12px">
                                <h2 class="card-title" style="margin:0">Tiket &amp; kapasitas</h2>
                                <?= $badge($tiketOk) ?>
                            </div>
                            <?php if ($tiketOk): ?>
                                <p class="slate" style="font-size:14px;line-height:21px"><?= count($review['kategori']) ?> kategori · <?= $h($daftarTiket) ?> · kapasitas <?= number_format($review['total_kuota'], 0, ',', '.') ?> kursi</p>
                            <?php else: ?>
                                <p class="slate" style="font-size:14px;line-height:21px">Belum ada kategori tiket. <?= $fix(2) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="card">
                            <div class="row-between" style="margin-bottom:12px">
                                <h2 class="card-title" style="margin:0">Rundown</h2>
                                <?= $badge($rundownOk) ?>
                            </div>
                            <p class="slate" style="font-size:14px;line-height:21px">
                                <?php if ($rundownOk): ?>
                                    <?= $review['rundown'] ?> kegiatan
                                <?php else: ?>
                                    Belum ada kegiatan. <?= $fix(3) ?>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="card">
                            <div class="row-between" style="margin-bottom:12px">
                                <h2 class="card-title" style="margin:0">RAB</h2>
                                <?= $badge($review['rab_count'] > 0, 'Lengkap', 'Opsional') ?>
                            </div>
                            <p class="slate" style="font-size:14px;line-height:21px">
                                <?= $review['rab_count'] > 0
                                    ? $h(EO::rp($review['rab_total'])) . ' terlampir'
                                    : 'Belum ada anggaran. RAB bersifat opsional dan tidak menghalangi pengajuan.' ?>
                            </p>
                        </div>
                    </div>

                    <div class="wizard-footer" style="width:880px;box-sizing:border-box;margin-top:24px">
                        <a href="<?= BASE_URL ?>/event-owner/acara-saya?draf=1" class="button ghost-button">Simpan draf</a>
                        <div class="row">
                            <a href="<?= EventHelper::stepUrl(3, $eventId) ?>" class="button secondary-button">Kembali</a>
                            <button type="submit" class="button primary-button"<?= $review['ready'] ? '' : ' disabled' ?>>Ajukan untuk ditinjau</button>
                        </div>
                    </div>
                </form>
<?php EventHelper::renderBottom(); ?>