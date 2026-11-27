<?php
declare(strict_types=1);

require_once defined('ROOT_DIR') ? ROOT_DIR . '/api/helpers/ViewHelper.php' : __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/event-layout.php';

$db = ViewHelper::getDb();
$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);
$event = EO::loadEvent($db, $owner);
$id = $event['id'];

$status = (string) $event['status_verifikasi'];
if ($status === 'PUBLISHED') {
    $status = 'TERBIT';
}
$editable = in_array($status, ['DRAF', 'DITOLAK'], true);
$canPublish = $status === 'DISETUJUI';
$canCancel = in_array($status, ['DISETUJUI', 'TERBIT'], true);
$canDelete = $status === 'DRAF';

// Tanggal persetujuan admin (untuk keterangan status)
$approvedAt = '';
if (in_array($status, ['DISETUJUI', 'TERBIT'], true)) {
    $stmt = $db->prepare("SELECT created_at FROM event_submission_logs WHERE event_id = :id AND status = 'DISETUJUI' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute(['id' => $id]);
    $approvedAt = (string) $stmt->fetchColumn();
}

// Posisi pada stepper: Draf > Menunggu tinjauan > Disetujui > Terbit
$steps = ['Draf', 'Menunggu tinjauan', 'Disetujui', 'Terbit'];
$current = match ($status) {
    'MENUNGGU_TINJAUAN' => 1,
    'DISETUJUI' => 2,
    'TERBIT' => 3,
    default => 0,
};
$showStepper = !in_array($status, ['BATAL', 'SELESAI'], true);

$caption = match ($status) {
    'DRAF' => 'Acara masih berupa draf. Lengkapi tiket, rundown, dan RAB, lalu ajukan untuk ditinjau.',
    'MENUNGGU_TINJAUAN' => 'Acara sedang ditinjau admin dan tidak dapat diedit sampai ada keputusan.',
    'DISETUJUI' => ($approvedAt !== '' ? 'Disetujui admin pada ' . EO::tanggal($approvedAt) . '. ' : 'Disetujui admin. ')
        . 'Acara belum terlihat oleh peserta sampai diterbitkan.',
    'TERBIT' => 'Acara sudah terbit dan tampil di halaman jelajah.',
    'DITOLAK' => 'Acara ditolak admin. Revisi informasi acara lalu ajukan ulang.',
    'BATAL' => 'Acara dibatalkan.' . (trim((string) $event['alasan_pembatalan']) !== '' ? ' Alasan: ' . $event['alasan_pembatalan'] : ''),
    'SELESAI' => 'Acara sudah selesai.',
    default => '',
};

$hasil = (string) ($_GET['hasil'] ?? '');

$subtitle = EO::badge(EO::eventBadge($status))
    . ' &nbsp;<span class="slate" style="font-size:14px">' . EO::e($event['judul_event']) . ' · ' . EO::e(EO::tanggal((string) $event['tanggal_mulai'])) . '</span>';
$actions = '<a href="' . BASE_URL . '/event-owner/acara-saya" class="button secondary-button" style="text-decoration:none">&larr; Kembali ke acara saya</a>';

EO::open($db, $owner, $event, 'pengaturan-acara', $subtitle, $actions, 'Pengaturan acara', 'Pengaturan');

$tableRows = [
    'DRAF' => ['Draf', 'Edit, ajukan untuk ditinjau, hapus'],
    'MENUNGGU_TINJAUAN' => ['Menunggu tinjauan', 'Lihat pengajuan, tarik pengajuan (tidak dapat diedit)'],
    'DISETUJUI' => ['Disetujui', 'Terbitkan, lihat informasi, batalkan'],
    'TERBIT' => ['Terbit', 'Kelola, batalkan acara'],
    'DITOLAK' => ['Ditolak', 'Revisi dan ajukan ulang'],
    'SELESAI' => ['Selesai', 'Lihat laporan'],
    'BATAL' => ['Dibatalkan', 'Lihat detail'],
];
?>
                <div class="stack gap-24" style="max-width:880px">
                    <div class="card">
                        <h2 class="card-title">Status acara</h2>
                        <?php if ($showStepper): ?>
                            <div class="stepper" style="margin:0">
                                <?php foreach ($steps as $n => $label): ?>
                                    <?php $done = $n < $current || ($n === $current && $n === 3); ?>
                                    <span class="step-item <?= $done ? 'step-done' : ($n === $current ? 'step-active' : '') ?>">
                                        <span class="step-dot">
                                            <?php if ($done): ?>
                                                <svg width="12" height="12" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                            <?php else: ?><?= $n + 1 ?><?php endif; ?>
                                        </span><?= EO::e($label) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <p class="help" style="margin-top:12px"><?= EO::e($caption) ?></p>
                    </div>

                    <?php if ($hasil === 'terbit'): ?>
                        <div class="alert alert-success">Acara berhasil diterbitkan.</div>
                    <?php elseif ($hasil === 'batal'): ?>
                        <div class="alert alert-success">Acara dibatalkan. Tiket peserta telah dibatalkan.</div>
                    <?php endif; ?>
                    <div class="alert alert-error" id="fb-error" role="alert" style="display:none"></div>

                    <div class="card">
                        <div class="row-between">
                            <div>
                                <h2 class="card-title" style="margin-bottom:4px">Informasi acara</h2>
                                <p class="slate" style="font-size:14px;line-height:21px;max-width:560px">
                                    <?= $editable ? 'Ubah nama, jadwal, lokasi, dan poster acara.' : 'Acara pada status ini tidak dapat diedit. Anda tetap bisa melihat informasinya.' ?>
                                </p>
                            </div>
                            <?php if ($editable): ?>
                                <a href="<?= EO::url('edit-informasi', $id) ?>" class="button primary-button" style="text-decoration:none">Edit informasi</a>
                            <?php else: ?>
                                <a href="<?= EO::url('edit-informasi', $id) ?>" class="button secondary-button" style="text-decoration:none">Lihat informasi</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($canPublish): ?>
                        <div class="card">
                            <div class="row-between">
                                <div>
                                    <h2 class="card-title" style="margin-bottom:4px">Terbitkan acara</h2>
                                    <p class="slate" style="font-size:14px;line-height:21px;max-width:560px">Acara akan tampil di halaman jelajah dan penjualan tiket dibuka sesuai periode yang Anda atur.</p>
                                </div>
                                <button type="button" id="btn-terbitkan" class="button primary-button" style="cursor:pointer">Terbitkan acara</button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($canDelete || $canCancel): ?>
                        <div class="card" style="box-shadow:inset 0 0 0 1px #F3B6B6;box-sizing:border-box">
                            <h2 class="card-title">Zona berbahaya</h2>
                            <?php if ($canDelete): ?>
                                <div class="row-between">
                                    <p class="slate" style="font-size:14px;line-height:21px;max-width:560px">Menghapus draf acara menghilangkan seluruh data acara ini secara permanen, termasuk tiket, rundown, RAB, dan panitia.</p>
                                    <button type="button" id="btn-hapus" class="button danger-outline-button" style="cursor:pointer">Hapus acara</button>
                                </div>
                            <?php endif; ?>
                            <?php if ($canCancel): ?>
                                <div class="row-between">
                                    <p class="slate" style="font-size:14px;line-height:21px;max-width:560px">Membatalkan acara menghentikan penjualan dan membatalkan semua tiket yang sudah terjual. Tindakan ini tidak dapat dibatalkan.</p>
                                    <button type="button" id="btn-batalkan" class="button danger-outline-button" style="cursor:pointer">Batalkan acara</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card" style="padding:20px 16px 8px">
                        <h2 class="card-title" style="padding:0 8px">Tindakan yang tersedia per status</h2>
                        <div class="tbl-head">
                            <div class="tbl-cell" style="flex:1">STATUS</div>
                            <div class="tbl-cell" style="flex:2.5">TINDAKAN</div>
                        </div>
                        <?php foreach ($tableRows as $key => [$label, $tindakan]): ?>
                            <div class="tbl-row"<?= $key === $status ? ' style="background:#F6F5FF"' : '' ?>>
                                <div class="tbl-cell" style="flex:1"><b><?= EO::e($label) ?></b></div>
                                <div class="tbl-cell" style="flex:2.5"><?= EO::e($tindakan) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
<?php
EO::close('<script>
(function () {
    const eventId = ' . json_encode($id) . ';
    const judul = ' . json_encode((string) $event['judul_event']) . ';
    const listUrl = ' . json_encode(BASE_URL . '/event-owner/acara-saya?dihapus=1') . ';
    const g = (i) => document.getElementById(i);
    async function run(btn, path, method, dlg, failText, done) {
        if (!btn) return;
        eoShowError(g("fb-error"), "");
        const ok = await eoConfirm(dlg);
        if (!ok) return;
        btn.disabled = true;
        const r = await eoApi(path + "/" + eventId, method, dlg.reason ? { alasan_pembatalan: ok.reason } : undefined);
        if (r.ok) { done(); return; }
        btn.disabled = false;
        eoShowError(g("fb-error"), r.message || failText);
        window.scrollTo({ top: 0, behavior: "smooth" });
    }
    const back = (hasil) => () => { location.href = location.pathname + "?id=" + eventId + "&hasil=" + hasil; };
    const p = g("btn-terbitkan"), c = g("btn-batalkan"), d = g("btn-hapus");
    if (p) p.addEventListener("click", () => run(p, "publish-event", "PATCH", {
        title: "Terbitkan acara ini?",
        text: "Acara tampil di halaman jelajah dan penjualan tiket dibuka. Setelah terbit, acara tidak dapat dihapus.",
        confirmLabel: "Ya, terbitkan"
    }, "Gagal menerbitkan acara. Coba lagi.", back("terbit")));
    if (c) c.addEventListener("click", () => run(c, "cancel-event", "PATCH", {
        title: "Batalkan acara ini?",
        text: "Penjualan dihentikan dan semua tiket peserta dibatalkan. Tindakan ini tidak dapat dibatalkan.",
        confirmLabel: "Batalkan acara",
        tone: "danger",
        reason: { label: "Alasan pembatalan (wajib)", min: 20, error: "Alasan wajib diisi, minimal 20 karakter.", placeholder: "Jelaskan alasan pembatalan acara" }
    }, "Gagal membatalkan acara. Coba lagi.", back("batal")));
    if (d) d.addEventListener("click", () => run(d, "delete-event", "DELETE", {
        title: "Hapus acara ini?",
        text: "Draf \u201c" + judul + "\u201d akan dihapus permanen beserta tiket, rundown, RAB, dan panitia di dalamnya. Tindakan ini tidak dapat dibatalkan.",
        confirmLabel: "Hapus acara",
        tone: "danger"
    }, "Gagal menghapus acara. Coba lagi.", () => { location.href = listUrl; }));
})();
</script>');
