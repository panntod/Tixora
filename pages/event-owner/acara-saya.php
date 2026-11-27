<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
require_once __DIR__ . '/event-layout.php';
$db = ViewHelper::getDb();

$owner = ViewHelper::requireLogin(AuthHelper::ROLE_EVENT_OWNER);

$status = trim($_GET['status'] ?? 'SEMUA');
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT e.*, 
           (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id AND t.status_tiket != 'BATAL') as total_pendaftar
    FROM events e 
    WHERE e.owner_id = :owner_id
";
$params = ['owner_id' => $owner['id']];

if (!empty($status) && strtoupper($status) !== 'SEMUA') {
    $sql .= " AND e.status_verifikasi = :status";
    $params['status'] = strtoupper($status);
}

if (!empty($search)) {
    $sql .= " AND (e.judul_event ILIKE :search OR e.nama_tempat ILIKE :search)";
    $params['search'] = "%{$search}%";
}

$sql .= " ORDER BY e.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Hitung counts per status
$stmtCounts = $db->prepare("
    SELECT status_verifikasi, COUNT(*) as cnt 
    FROM events 
    WHERE owner_id = :owner_id 
    GROUP BY status_verifikasi
");
$stmtCounts->execute(['owner_id' => $owner['id']]);
$rawCounts = $stmtCounts->fetchAll(PDO::FETCH_KEY_PAIR);
$totalSemua = array_sum($rawCounts);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Acara Saya</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame">
        <div class="app">
            <?= EO::sidebar($db, $owner, 'acara-saya') ?>

            <main class="main">
                <p class="crumb">Acara saya</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Acara saya</h1>
                        <p class="page-subtitle">Pilih acara untuk mengelola tiket, rundown, peserta, RAB, panitia, dan analitik</p>
                    </div>
                    <div class="head-actions">
                        <a href="<?= BASE_URL ?>/event-owner/buat-acara" class="button primary-button button-large"
                            style="text-decoration:none">+ Buat acara</a>
                    </div>
                </div>

                <?php if (isset($_GET['draf'])): ?>
                    <div class="alert alert-success" style="margin-bottom:16px">Draf acara tersimpan. Lanjutkan kapan saja lewat tombol "Lanjutkan".</div>
                <?php elseif (isset($_GET['dihapus'])): ?>
                    <div class="alert alert-success" style="margin-bottom:16px">Draf acara berhasil dihapus.</div>
                <?php elseif (isset($_GET['diajukan'])): ?>
                    <div class="alert alert-info" style="margin-bottom:16px"><span>Acara berhasil diajukan dan kini berstatus <b>Menunggu tinjauan</b>. Admin akan memberi keputusan.</span></div>
                <?php endif; ?>

                <form method="GET" action="" class="filter-bar">
                    <?php if ($status !== 'SEMUA'): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                    <?php endif; ?>
                    <input type="text" name="search" class="input" style="flex:1;width:auto"
                        placeholder="Cari nama acara..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="button secondary-button" style="cursor:pointer">Cari</button>
                </form>

                <div class="chip-row">
                    <a href="<?= BASE_URL ?>/event-owner/acara-saya"
                        class="chip <?= $status === 'SEMUA' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Semua <?= $totalSemua ?>
                    </a>
                    <a href="<?= BASE_URL ?>/event-owner/acara-saya?status=DRAF"
                        class="chip <?= $status === 'DRAF' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Draf <?= $rawCounts['DRAF'] ?? 0 ?>
                    </a>
                    <a href="<?= BASE_URL ?>/event-owner/acara-saya?status=MENUNGGU_TINJAUAN"
                        class="chip <?= $status === 'MENUNGGU_TINJAUAN' ? 'chip-active' : '' ?>"
                        style="text-decoration:none">
                        Menunggu <?= $rawCounts['MENUNGGU_TINJAUAN'] ?? 0 ?>
                    </a>
                    <a href="<?= BASE_URL ?>/event-owner/acara-saya?status=TERBIT"
                        class="chip <?= $status === 'TERBIT' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Terbit <?= ($rawCounts['TERBIT'] ?? 0) + ($rawCounts['PUBLISHED'] ?? 0) ?>
                    </a>
                    <a href="<?= BASE_URL ?>/event-owner/acara-saya?status=DITOLAK"
                        class="chip <?= $status === 'DITOLAK' ? 'chip-active' : '' ?>" style="text-decoration:none">
                        Ditolak <?= $rawCounts['DITOLAK'] ?? 0 ?>
                    </a>
                </div>

                <div class="tbl-card tbl-stack">
                    <div class="tbl-head">
                        <div class="tbl-cell" style="flex:3">ACARA</div>
                        <div class="tbl-cell" style="flex:1.2">TANGGAL</div>
                        <div class="tbl-cell" style="flex:1.2">PENDAFTAR</div>
                        <div class="tbl-cell" style="flex:1.5">STATUS</div>
                        <div class="tbl-cell tbl-right" style="flex:1.6">TINDAKAN</div>
                    </div>

                    <?php foreach ($events as $ev): ?>
                        <div class="tbl-row">
                            <div class="tbl-cell" style="flex:3;min-width:0">
                                <a href="<?= EO::url('ringkasan', $ev['id']) ?>" style="color:inherit;text-decoration:none"><b><?= EO::e($ev['judul_event']) ?></b></a>
                                <div class="cell-sub"><?= EO::e($ev['nama_tempat']) ?>, <?= EO::e($ev['kota']) ?></div>
                            </div>
                            <div class="tbl-cell" style="flex:1.2"><?= EO::e(EO::tanggal($ev['tanggal_mulai'])) ?></div>
                            <div class="tbl-cell" style="flex:1.2"><b><?= (int) $ev['total_pendaftar'] ?></b> / <?= (int) $ev['kuota'] ?></div>
                            <div class="tbl-cell" style="flex:1.5"><?= EO::badge(EO::eventBadge($ev['status_verifikasi'])) ?></div>
                            <div class="tbl-cell tbl-right" style="flex:1.6">
                                <?php if (in_array($ev['status_verifikasi'], ['DRAF', 'DITOLAK'], true)): ?>
                                    <a href="<?= BASE_URL ?>/event-owner/buat-acara?id=<?= EO::e($ev['id']) ?>"
                                        class="button secondary-button small-button"
                                        style="text-decoration:none;cursor:pointer"><?= $ev['status_verifikasi'] === 'DRAF' ? 'Edit &amp; lanjutkan' : 'Perbaiki' ?></a>
                                <?php endif; ?>
                                <?php if (in_array($ev['status_verifikasi'], ['TERBIT', 'PUBLISHED'], true)): ?>
                                    <a href="<?= EO::url('scan-tiket', $ev['id']) ?>"
                                        class="button secondary-button small-button"
                                        style="text-decoration:none;cursor:pointer">Scan tiket</a>
                                <?php endif; ?>
                                <a href="<?= EO::url('ringkasan', $ev['id']) ?>" class="button primary-button small-button"
                                    style="text-decoration:none;cursor:pointer">Kelola</a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($events)): ?>
                        <div style="padding:48px 16px;text-align:center">
                            <p class="slate">Tidak ada acara yang sesuai filter saat ini.</p>
                            <a href="<?= BASE_URL ?>/event-owner/buat-acara" class="button primary-button small-button"
                                style="margin-top:12px;display:inline-flex;text-decoration:none">+ Buat acara baru</a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    <script>
        document.querySelectorAll('.js-ajukan').forEach((btn) => btn.addEventListener('click', async () => {
            const ok = await eoConfirm({
                title: 'Ajukan acara untuk ditinjau?',
                text: 'Acara akan berstatus Menunggu tinjauan dan tidak dapat diedit sampai admin memberi keputusan.',
                confirmLabel: 'Ya, ajukan'
            });
            if (!ok) return;
            btn.disabled = true;
            try {
                const res = await fetch('<?= BASE_URL ?>/api/owner/submit-event/' + btn.dataset.id, { method: 'PATCH', credentials: 'same-origin' });
                const json = await res.json().catch(() => ({}));
                if (res.ok && json.success !== false) { location.reload(); return; }
                alert(json.message || 'Gagal mengajukan acara.');
            } catch (e) { alert('Tidak dapat terhubung ke server.'); }
            btn.disabled = false;
        }));
    </script>
    <script src="<?= BASE_URL ?>/event-owner/sidebar.js"></script>
</body>

</html>