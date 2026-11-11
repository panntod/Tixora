<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EO::loadEvent($db, $owner);
$id = $event['id'];
$status = $event['status_verifikasi'];

$stmt = $db->prepare('SELECT status, catatan, created_at FROM event_submission_logs WHERE event_id = :id ORDER BY created_at DESC');
$stmt->execute(['id' => $id]);
$logs = $stmt->fetchAll();

$logLabel = static fn(string $s): string => match ($s) {
    'MENUNGGU_TINJAUAN' => 'Diajukan untuk ditinjau',
    'DITOLAK' => 'Ditolak oleh admin',
    'DISETUJUI' => 'Disetujui admin',
    'TERBIT', 'PUBLISHED' => 'Diterbitkan',
    'DRAF' => 'Dikembalikan ke draf',
    default => ucfirst(strtolower(str_replace('_', ' ', $s))),
};

$canSubmit = in_array($status, ['DRAF', 'DITOLAK'], true);
$canWithdraw = $status === 'MENUNGGU_TINJAUAN';
$canPublish = $status === 'DISETUJUI';

$actions = '';
if ($canSubmit) {
    $actions = '<button type="button" id="btn-ajukan" class="button primary-button" style="cursor:pointer">'
        . ($status === 'DITOLAK' ? 'Ajukan ulang' : 'Ajukan acara') . '</button>';
} elseif ($canPublish) {
    $actions = '<button type="button" id="btn-terbitkan" class="button primary-button" style="cursor:pointer">Terbitkan acara</button>';
}
$subtitle = EO::badge(EO::eventBadge($status)) . ' &nbsp;<span class="slate" style="font-size:14px">Diperbarui ' . EO::e(EO::tanggal($event['updated_at'])) . '</span>';

EO::open($db, $owner, $event, 'pengajuan', $subtitle, $actions, null, 'Pengajuan');
?>
                <div class="cols">
                    <div class="col-wide stack gap-24">
                        <?php if ($status === 'DITOLAK'): ?>
                            <div class="card" style="box-shadow:inset 0 0 0 1px #F3B6B6;box-sizing:border-box">
                                <h2 class="card-title">Catatan dari admin</h2>
                                <p class="slate" style="font-size:16px;line-height:24px"><?= EO::e(trim((string) $event['alasan_penolakan']) !== '' ? $event['alasan_penolakan'] : 'Admin tidak menyertakan catatan.') ?></p>
                                <p class="help">Perbaiki acara lalu ajukan ulang. <a href="<?= EO::url('edit-informasi', $id) ?>" class="link-text" style="text-decoration:none">Revisi acara</a></p>
                            </div>
                        <?php endif; ?>
                        <div class="card">
                            <h2 class="card-title">Riwayat</h2>
                            <?php if ($logs): ?>
                                <div class="timeline">
                                    <?php foreach ($logs as $l): ?>
                                        <div class="timeline-item">
                                            <span class="timeline-dot<?= $l['status'] === 'DITOLAK' ? ' timeline-dot-error' : '' ?>"></span>
                                            <b><?= EO::e(EO::tanggalPendek($l['created_at'])) ?>, <?= date('H.i', strtotime($l['created_at'])) ?></b> · <?= EO::e($logLabel($l['status'])) ?>
                                            <?php if (trim((string) $l['catatan']) !== ''): ?><div class="slate" style="font-size:13px;margin-top:2px"><?= EO::e($l['catatan']) ?></div><?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="slate" style="font-size:14px">Belum ada riwayat pengajuan.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Status pengajuan</h2>
                        <div style="margin:0 0 12px"><?= EO::badge(EO::eventBadge($status)) ?></div>
                        <?php if ($canPublish): ?>
                            <div class="alert alert-success" style="margin-bottom:12px;font-size:14px">
                                Acara sudah <b>disetujui admin</b>. Klik <b>Terbitkan acara</b> untuk menampilkannya di portal publik.
                            </div>
                        <?php endif; ?>
                        <p class="slate" style="font-size:14px;line-height:21px">
                            <?= match (true) {
                                $status === 'MENUNGGU_TINJAUAN' => 'Acara tidak dapat diedit selama ditinjau. Anda akan menerima pemberitahuan setelah ada keputusan.',
                                $status === 'DRAF' => 'Acara masih berupa draf dan belum terlihat oleh admin. Lengkapi tiket, rundown, dan RAB, lalu ajukan.',
                                $status === 'DITOLAK' => 'Acara perlu direvisi sebelum diajukan ulang.',
                                $status === 'DISETUJUI' => 'Acara sudah disetujui admin. Terbitkan untuk membuka pendaftaran peserta.',
                                in_array($status, ['TERBIT', 'PUBLISHED'], true) => 'Acara sudah terbit dan tampil di portal publik.',
                                $status === 'BATAL' => 'Acara dibatalkan.',
                                default => 'Tidak ada tindakan pengajuan untuk status ini.',
                            } ?>
                        </p>
                        <div class="help help-error" id="e-form" style="display:none"></div>
                        <?php if ($canWithdraw): ?>
                            <button type="button" id="btn-tarik" class="button secondary-button" style="margin-top:16px;cursor:pointer">Tarik pengajuan</button>
                        <?php endif; ?>
                    </div>
                </div>
<?php
EO::close('<script>
(function () {
    const eventId = ' . json_encode($id) . ';
    async function run(btn, path, confirmText) {
        if (!btn || (confirmText && !confirm(confirmText))) return;
        btn.disabled = true;
        const r = await eoApi(path + "/" + eventId, "PATCH");
        if (r.ok) { location.reload(); return; }
        btn.disabled = false;
        eoShowError(document.getElementById("e-form"), r.message);
    }
    const a = document.getElementById("btn-ajukan"), t = document.getElementById("btn-tarik"), p = document.getElementById("btn-terbitkan");
    if (a) a.addEventListener("click", () => run(a, "submit-event", "Ajukan acara ini untuk ditinjau admin?"));
    if (t) t.addEventListener("click", () => run(t, "withdraw-event", "Tarik pengajuan dan kembalikan acara ke draf?"));
    if (p) p.addEventListener("click", () => run(p, "publish-event", "Terbitkan acara ini ke portal publik? Setelah terbit, acara akan tampil di Jelajah Acara."));
})();
</script>');
