<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/helpers/ViewHelper.php';
$db = ViewHelper::getDb();
$admin = ViewHelper::requireLogin(AuthHelper::ROLE_ADMIN);

// Validasi UUID
$userId = trim($_GET['id'] ?? '');
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $userId)) {
    header('Location: ' . BASE_URL . '/admin/pengguna');
    exit;
}

$stmt = $db->prepare("
    SELECT u.*, r.nama AS role_nama
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = :id
");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: ' . BASE_URL . '/admin/pengguna');
    exit;
}

$activePage    = 'pengguna';
$menungguCount = (int)$db->query("SELECT COUNT(*) FROM events WHERE status_verifikasi = 'MENUNGGU_TINJAUAN'")->fetchColumn();

$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!ViewHelper::checkCsrf()) {
        $errorMsg = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($action === 'toggle_suspend') {
        $isActive = (bool)$user['is_active'];
        $newState = !$isActive;
        $alasan   = trim($_POST['alasan'] ?? '');

        if (!$newState && $alasan === '') {
            $errorMsg = 'Alasan penangguhan wajib diisi.';
        } elseif ($user['role_id'] === AuthHelper::ROLE_ADMIN) {
            $errorMsg = 'Akun admin tidak dapat ditangguhkan dari halaman ini.';
        } elseif ($user['id'] === $admin['id']) {
            $errorMsg = 'Anda tidak dapat menangguhkan akun sendiri.';
        } else {
            $db->prepare("
                UPDATE users
                SET is_active = :active,
                    alasan_suspensi = :alasan,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ")->execute([
                'active'  => $newState ? 'true' : 'false',
                'alasan'  => $newState ? null : $alasan,
                'id'      => $userId,
            ]);
            header('Location: ' . BASE_URL . '/admin/detail-pengguna?id=' . urlencode($userId) . '&saved=1');
            exit;
        }
    } elseif ($action === 'kirim_reset') {
        $token = bin2hex(random_bytes(32));
        $db->beginTransaction();
        $db->prepare("UPDATE password_resets SET used_at = CURRENT_TIMESTAMP
            WHERE user_id = :uid AND used_at IS NULL")->execute(['uid' => $userId]);
        $db->prepare("
            INSERT INTO password_resets (user_id, token_hash, expires_at)
            VALUES (:uid, :hash, CURRENT_TIMESTAMP + interval '60 minutes')
        ")->execute([
            'uid'  => $userId,
            'hash' => hash('sha256', $token),
        ]);
        $db->commit();

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $link = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
            . BASE_URL . '/atur-ulang-kata-sandi?token=' . $token;

        if (ViewHelper::isLocalRequest()) {
            $_SESSION['admin_reset_link'] = $link;
        } else {
            @mail(
                $user['email'],
                'Atur ulang kata sandi TIXORA (diminta admin)',
                "Halo {$user['nama']},\n\nAdmin telah membuat tautan reset password untuk Anda:\n{$link}\n\nTautan berlaku 60 menit.",
                'Content-Type: text/plain; charset=UTF-8'
            );
        }
        header('Location: ' . BASE_URL . '/admin/detail-pengguna?id=' . urlencode($userId) . '&reset=1');
        exit;
    }
}

if (isset($_GET['saved'])) $successMsg = 'Perubahan tersimpan.';
if (isset($_GET['reset'])) $successMsg = 'Tautan reset kata sandi telah dibuat.';

$devResetLink = $_SESSION['admin_reset_link'] ?? null;
unset($_SESSION['admin_reset_link']);

$stmtTickets = $db->prepare("
    SELECT t.id, t.kode_tiket, t.jenis_tiket, t.status_tiket, t.status_kehadiran, t.created_at,
           e.id AS event_id, e.judul_event, e.tanggal_mulai
    FROM tickets t
    JOIN events e ON e.id = t.event_id
    WHERE t.user_id = :uid
    ORDER BY t.created_at DESC LIMIT 50
");
$stmtTickets->execute(['uid' => $userId]);
$tickets = $stmtTickets->fetchAll();

$initials = strtoupper(mb_substr($user['nama'], 0, 2));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TIXORA — Admin — <?= htmlspecialchars($user['nama']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;min-height:900px;height:auto">
        <div class="app">
            <?php include __DIR__ . '/_sidebar.php'; ?>

            <main class="main">
                <p class="crumb">
                    <a href="<?= BASE_URL ?>/admin/pengguna" style="color:inherit;text-decoration:none">Pengguna</a>
                    / <?= htmlspecialchars($user['nama']) ?>
                </p>
                <div class="head">
                    <div>
                        <h1 class="page-title"><?= htmlspecialchars($user['nama']) ?></h1>
                        <p class="page-subtitle">
                            <span class="badge badge-purple"><span class="badge-dot"></span><?= htmlspecialchars($user['role_nama']) ?></span>
                            <span class="badge <?= $user['is_active'] ? 'badge-success' : 'badge-error' ?>">
                                <span class="badge-dot"></span><?= $user['is_active'] ? 'Aktif' : 'Ditangguhkan' ?>
                            </span>
                        </p>
                    </div>
                    <div class="head-actions">
                        <a href="<?= BASE_URL ?>/admin/pengguna" class="button secondary-button" style="text-decoration:none">← Kembali</a>
                    </div>
                </div>

                <?php if ($successMsg): ?>
                    <div class="alert alert-success" style="margin-bottom:20px"><?= htmlspecialchars($successMsg) ?></div>
                <?php endif; ?>
                <?php if ($errorMsg): ?>
                    <div class="alert alert-error" style="margin-bottom:20px"><?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>
                <?php if ($devResetLink): ?>
                    <div class="alert alert-warning" style="margin-bottom:20px;word-break:break-all">
                        <b>Mode lokal</b> — Tautan reset (tampil sekali):
                        <a href="<?= htmlspecialchars($devResetLink) ?>"><?= htmlspecialchars($devResetLink) ?></a>
                    </div>
                <?php endif; ?>

                <div class="cols">
                    <div class="stack gap-24" style="width:424px">
                        <div class="card">
                            <div class="row" style="margin-bottom:16px">
                                <span class="avatar avatar-large"
                                      style="width:56px;height:56px;line-height:56px;font-size:18px"><?= $initials ?></span>
                                <div>
                                    <b><?= htmlspecialchars($user['nama']) ?></b>
                                    <div class="slate" style="font-size:14px"><?= htmlspecialchars($user['email']) ?></div>
                                </div>
                            </div>
                            <dl class="definition">
                                <div><dt>WHATSAPP</dt><dd><?= htmlspecialchars($user['no_whatsapp'] ?: '—') ?></dd></div>
                                <div><dt>INSTITUSI</dt><dd><?= htmlspecialchars($user['institusi'] ?: '—') ?></dd></div>
                                <div><dt>TERDAFTAR</dt><dd><?= date('d M Y', strtotime($user['created_at'])) ?></dd></div>
                                <div><dt>TERAKHIR UPDATE</dt><dd><?= date('d M Y', strtotime($user['updated_at'])) ?></dd></div>
                                <?php if (!$user['is_active'] && !empty($user['alasan_suspensi'])): ?>
                                    <div class="wide">
                                        <dt>ALASAN SUSPENSI</dt>
                                        <dd><?= htmlspecialchars($user['alasan_suspensi']) ?></dd>
                                    </div>
                                <?php endif; ?>
                            </dl>
                        </div>

                        <div class="card">
                            <h2 class="card-title">Tindakan Admin</h2>

                            <form method="POST" action="" class="stack gap-8">
                                <?= ViewHelper::csrfField() ?>
                                <input type="hidden" name="action" value="kirim_reset">
                                <button type="submit" class="button secondary-button" style="cursor:pointer"
                                    onclick="return confirm('Kirim tautan reset password ke <?= htmlspecialchars($user['email'], ENT_QUOTES) ?>?')">
                                    Kirim tautan reset password
                                </button>
                            </form>

                            <?php if ($user['role_id'] !== AuthHelper::ROLE_ADMIN && $user['id'] !== $admin['id']): ?>
                                <form method="POST" action="" class="stack gap-8"
                                      style="margin-top:16px;border-top:1px solid #E4E4EE;padding-top:16px">
                                    <?= ViewHelper::csrfField() ?>
                                    <input type="hidden" name="action" value="toggle_suspend">
                                    <?php if ($user['is_active']): ?>
                                        <div>
                                            <label class="field-label">Alasan penangguhan</label>
                                            <textarea name="alasan" class="input" style="height:70px;width:100%" required
                                                placeholder="Contoh: Melanggar ketentuan platform..."></textarea>
                                        </div>
                                        <button type="submit" class="button danger-button" style="cursor:pointer"
                                            onclick="return confirm('Tangguhkan akun ini? User tidak dapat login sampai diaktifkan kembali.')">
                                            Tangguhkan akun
                                        </button>
                                    <?php else: ?>
                                        <p class="help">Akun saat ini ditangguhkan. Aktifkan kembali untuk mengizinkan login.</p>
                                        <button type="submit" class="button primary-button" style="cursor:pointer">
                                            Aktifkan kembali
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="stack gap-24" style="width:680px">
                        <div class="card" style="padding:20px 16px 8px;box-sizing:border-box">
                            <h2 class="card-title" style="padding:0 8px">Riwayat Tiket (<?= count($tickets) ?>)</h2>
                            <?php if (!empty($tickets)): ?>
                                <div class="tbl-head">
                                    <div class="tbl-cell" style="width:280px">ACARA</div>
                                    <div class="tbl-cell" style="width:140px">KODE TIKET</div>
                                    <div class="tbl-cell" style="width:100px">KATEGORI</div>
                                    <div class="tbl-cell" style="width:160px">STATUS</div>
                                </div>
                                <?php foreach ($tickets as $t):
                                    $badge = match ($t['status_tiket']) {
                                        'AKTIF'                 => ['badge-success', 'Aktif'],
                                        'SUDAH_PAKAI'           => ['badge-neutral', 'Sudah Dipakai'],
                                        'MENUNGGU_PEMBAYARAN'   => ['badge-warning', 'Menunggu Bayar'],
                                        'BATAL'                 => ['badge-error', 'Dibatalkan'],
                                        default                 => ['badge-neutral', $t['status_tiket']],
                                    };
                                ?>
                                    <div class="tbl-row">
                                        <div class="tbl-cell" style="width:280px">
                                            <a href="<?= BASE_URL ?>/admin/tinjauan-acara?id=<?= urlencode($t['event_id']) ?>"
                                               style="color:inherit;text-decoration:none">
                                                <b><?= htmlspecialchars($t['judul_event']) ?></b>
                                            </a>
                                        </div>
                                        <div class="tbl-cell" style="width:140px"><?= htmlspecialchars($t['kode_tiket']) ?></div>
                                        <div class="tbl-cell" style="width:100px"><?= htmlspecialchars($t['jenis_tiket']) ?></div>
                                        <div class="tbl-cell" style="width:160px">
                                            <span class="badge <?= $badge[0] ?>"><span class="badge-dot"></span><?= $badge[1] ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding:32px 16px;text-align:center">
                                    <p class="slate">User ini belum memiliki tiket.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>