<?php
declare(strict_types=1);

/**
 * Layout bersama halaman kelola acara Event Owner
 * (Ringkasan, Tiket, Rundown, Peserta, RAB, Panitia, Analitik, Pengajuan).
 *
 * File ini hanya di-include oleh halaman; BUKAN route.
 * Semua halaman memakai ?id=<UUID acara> dan hanya bisa membuka acara milik owner yang login.
 */
final class EO
{
    private const BULAN = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    private const HARI = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    /** Tab pada halaman acara: slug route => label. */
    public const TABS = [
        'ringkasan' => 'Ringkasan',
        'tiket' => 'Tiket',
        'rundown' => 'Rundown',
        'peserta' => 'Peserta',
        'rab' => 'RAB',
        'panitia' => 'Panitia',
        'analitik' => 'Analitik',
        'pengajuan' => 'Pengajuan',
    ];

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** "Rp14.750.000" */
    public static function rp(float|int|string|null $amount): string
    {
        return 'Rp' . number_format((float) $amount, 0, ',', '.');
    }

    /** "24 Okt 2026" atau, dengan $hari, "Sab, 24 Okt 2026". */
    public static function tanggal(string $ts, bool $hari = false): string
    {
        $t = strtotime($ts);
        if ($t === false) {
            return '-';
        }
        $s = (int) date('j', $t) . ' ' . self::BULAN[(int) date('n', $t)] . ' ' . date('Y', $t);
        return $hari ? self::HARI[(int) date('w', $t)] . ', ' . $s : $s;
    }

    /** "24 Okt" (tanpa tahun). */
    public static function tanggalPendek(string $ts): string
    {
        $t = strtotime($ts);
        return $t === false ? '-' : (int) date('j', $t) . ' ' . self::BULAN[(int) date('n', $t)];
    }

    /** "Hari ini, 09.14" / "Kemarin, 20.12" / "6 Okt". */
    public static function waktuRelatif(string $ts): string
    {
        $t = strtotime($ts);
        if ($t === false) {
            return '-';
        }
        $selisihHari = (int) ((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', $t))) / 86400);
        $jam = date('H.i', $t);
        return match (true) {
            $selisihHari === 0 => 'Hari ini, ' . $jam,
            $selisihHari === 1 => 'Kemarin, ' . $jam,
            default => self::tanggalPendek($ts),
        };
    }

    /** @return array{0:string,1:string} [kelas badge, label] untuk status acara. */
    public static function eventBadge(string $status): array
    {
        return match ($status) {
            'TERBIT', 'PUBLISHED' => ['badge-success', 'Terbit'],
            'MENUNGGU_TINJAUAN' => ['badge-warning', 'Menunggu tinjauan'],
            'DISETUJUI' => ['badge-info', 'Disetujui'],
            'DITOLAK' => ['badge-error', 'Ditolak'],
            'BATAL' => ['badge-error', 'Dibatalkan'],
            'SELESAI' => ['badge-neutral', 'Selesai'],
            default => ['badge-neutral', 'Draf'],
        };
    }

    /** @return array{0:string,1:string} [kelas badge, label] untuk status tiket peserta. */
    public static function ticketBadge(string $status): array
    {
        return match ($status) {
            'AKTIF' => ['badge-success', 'Sah'],
            'SUDAH_PAKAI' => ['badge-info', 'Sudah hadir'],
            'MENUNGGU_PEMBAYARAN' => ['badge-warning', 'Menunggu pembayaran'],
            'BATAL' => ['badge-error', 'Dibatalkan'],
            default => ['badge-neutral', ucfirst(strtolower(str_replace('_', ' ', $status)))],
        };
    }

    public static function badge(array $badge): string
    {
        return '<span class="badge ' . $badge[0] . '"><span class="badge-dot"></span>' . self::e($badge[1]) . '</span>';
    }

    public static function url(string $slug, string $eventId = '', array $extra = []): string
    {
        $query = $eventId !== '' ? ['id' => $eventId] + $extra : $extra;
        return BASE_URL . '/event-owner/' . $slug . ($query ? '?' . http_build_query($query) : '');
    }

    /**
     * Ambil acara dari ?id= dan pastikan milik owner yang login.
     * Jika id kosong / tidak valid / bukan milik owner, kembali ke daftar Acara saya.
     */
    public static function loadEvent(PDO $db, array $owner): array
    {
        $id = trim((string) ($_GET['id'] ?? ''));
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            $stmt = $db->prepare('SELECT * FROM events WHERE id = :id AND owner_id = :owner');
            $stmt->execute(['id' => $id, 'owner' => $owner['id']]);
            $event = $stmt->fetch();
            if ($event) {
                return $event;
            }
        }
        header('Location: ' . BASE_URL . '/event-owner/acara-saya');
        exit;
    }

    /** Institusi owner untuk ditampilkan di sidebar (fallback ke email). */
    private static function ownerSubtitle(PDO $db, array $owner): string
    {
        $stmt = $db->prepare('SELECT institusi FROM users WHERE id = :id');
        $stmt->execute(['id' => $owner['id']]);
        $institusi = trim((string) $stmt->fetchColumn());
        return $institusi !== '' ? $institusi : (string) ($owner['email'] ?? '');
    }

    /** Sidebar yang sama untuk semua halaman Acara saya. */
    public static function sidebar(PDO $db, array $owner, string $active = 'acara-saya'): string
    {
        $items = [
            'dasbor' => ['Dasbor', BASE_URL . '/event-owner'],
            'acara-saya' => ['Acara saya', BASE_URL . '/event-owner/acara-saya'],
            'buat-acara' => ['Buat acara', BASE_URL . '/event-owner/buat-acara'],
            'jelajah' => ['Jelajah Acara', BASE_URL . '/jelajah-acara'],
        ];
        $html = '<aside class="sidebar"><div class="logo"><img class="logo-img" src="' . BASE_URL
            . '/assets/logo.png" alt="Logo TIXORA"></div>';
        foreach ($items as $key => [$label, $href]) {
            $html .= '<a href="' . $href . '" class="side-item' . ($key === $active ? ' side-item-active' : '')
                . '" style="text-decoration:none">' . $label . '</a>';
        }
        $html .= '<a href="' . BASE_URL . '/logout" class="side-item" style="text-decoration:none;color:#B91C1C">Keluar</a>';
        $html .= '<div class="side-user"><b>' . self::e($owner['nama'] ?? '') . '</b>'
            . self::e(self::ownerSubtitle($db, $owner)) . '</div></aside>';
        return $html;
    }

    /**
     * Buka halaman: <head>, sidebar, breadcrumb, judul, tombol aksi, dan tab.
     *
     * @param string $subtitleHtml HTML sudah aman (di-escape pemanggil).
     * @param string $actionsHtml  HTML tombol di kanan judul.
     * @param string $title        Judul H1; default judul acara.
     * @param string $crumbLast    Segmen terakhir breadcrumb, mis. "Analitik".
     */
    public static function open(
        PDO $db,
        array $owner,
        array $event,
        string $activeTab,
        string $subtitleHtml,
        string $actionsHtml = '',
        ?string $title = null,
        string $crumbLast = ''
    ): void {
        $short = self::shortName((string) $event['judul_event']);
        $crumb = '<a href="' . BASE_URL . '/event-owner/acara-saya" class="crumb-link">Acara saya</a> / '
            . '<a href="' . self::url('ringkasan', $event['id']) . '" class="crumb-link">' . self::e($short) . '</a>'
            . ($crumbLast !== '' ? ' / ' . self::e($crumbLast) : '');
        $pageTitle = $title ?? (string) $event['judul_event'];
        ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — <?= self::e($crumbLast !== '' ? $crumbLast : 'Ringkasan Acara') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame">
        <div class="app">
            <?= self::sidebar($db, $owner) ?>
            <main class="main">
                <p class="crumb"><?= $crumb ?></p>
                <div class="head">
                    <div>
                        <h1 class="page-title"><?= self::e($pageTitle) ?></h1>
                        <?php if ($subtitleHtml !== ''): ?>
                            <p class="page-subtitle"><?= $subtitleHtml ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($actionsHtml !== ''): ?>
                        <div class="head-actions"><?= $actionsHtml ?></div>
                    <?php endif; ?>
                </div>
                <div class="tabs" style="margin-bottom:24px">
                    <?php foreach (self::TABS as $slug => $label): ?>
                        <a href="<?= self::url($slug, $event['id']) ?>"
                            class="tab<?= $slug === $activeTab ? ' tab-active' : '' ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                </div>
<?php
    }

    public static function close(string $script = ''): void
    {
        ?>
            </main>
        </div>
    </div>
    <script>
        /** Kirim JSON ke API owner; mengembalikan {ok, message}. */
        async function eoApi(path, method, data) {
            try {
                const res = await fetch('<?= BASE_URL ?>/api/owner/' + path, {
                    method: method,
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: data ? JSON.stringify(data) : undefined
                });
                const json = await res.json().catch(() => ({}));
                return { ok: res.ok && json.success !== false, message: json.message || 'Terjadi kesalahan, coba lagi.' };
            } catch (e) {
                return { ok: false, message: 'Tidak dapat terhubung ke server.' };
            }
        }
        function eoShowError(el, message) {
            if (!el) return;
            el.textContent = message || '';
            el.style.display = message ? 'block' : 'none';
        }
    </script>
    <script src="<?= BASE_URL ?>/event-owner/sidebar.js"></script>
    <?= $script ?>
</body>

</html>
<?php
    }

    /** Nama pendek untuk breadcrumb: tanda kurung / akronim bila ada, selain itu dipotong. */
    public static function shortName(string $title): string
    {
        return mb_strlen($title) > 32 ? rtrim(mb_substr($title, 0, 30)) . '…' : $title;
    }

    /** Hitung statistik pendaftaran satu acara. */
    public static function stats(PDO $db, array $event): array
    {
        PaymentHelper::ensureSchema($db);

        $q = $db->prepare("SELECT COUNT(*) FILTER (WHERE status_tiket <> 'BATAL') AS pendaftar,
                                  COUNT(*) FILTER (WHERE status_tiket = 'MENUNGGU_PEMBAYARAN') AS menunggu
                           FROM tickets WHERE event_id = :id");
        $q->execute(['id' => $event['id']]);
        $t = $q->fetch();

        $q = $db->prepare("SELECT COALESCE(SUM(p.jumlah) FILTER (WHERE p.status = 'BERHASIL'), 0) AS pendapatan,
                                  COALESCE(SUM(p.jumlah) FILTER (WHERE p.status = 'MENUNGGU'), 0) AS tertunda
                           FROM payments p JOIN tickets t ON t.id = p.ticket_id
                           WHERE t.event_id = :id AND t.status_tiket <> 'BATAL'");
        $q->execute(['id' => $event['id']]);
        $p = $q->fetch();

        $kuota = (int) $event['kuota'];
        $pendaftar = (int) $t['pendaftar'];
        return [
            'pendaftar' => $pendaftar,
            'menunggu' => (int) $t['menunggu'],
            'pendapatan' => (float) $p['pendapatan'],
            'tertunda' => (float) $p['tertunda'],
            'kuota' => $kuota,
            'sisa' => max(0, $kuota - $pendaftar),
            'persen' => $kuota > 0 ? (int) round($pendaftar / $kuota * 100) : 0,
        ];
    }

    /**
     * Kategori tiket + jumlah terjual (tiket tidak BATAL) + pendapatan terkonfirmasi.
     * Dikaitkan lewat ticket_category_id; tiket lama tanpa id dicocokkan lewat nama kategori.
     */
    public static function categories(PDO $db, string $eventId): array
    {
        PaymentHelper::ensureSchema($db);
        $q = $db->prepare("
            SELECT tc.id, tc.nama_kategori, tc.kuota, tc.wajib_ktm, tc.harga,
                   (SELECT COUNT(*) FROM tickets t
                     WHERE t.event_id = tc.event_id AND t.status_tiket <> 'BATAL'
                       AND (t.ticket_category_id = tc.id
                            OR (t.ticket_category_id IS NULL AND t.jenis_tiket = tc.nama_kategori))) AS terjual,
                   (SELECT COALESCE(SUM(p.jumlah), 0) FROM payments p JOIN tickets t ON t.id = p.ticket_id
                     WHERE t.event_id = tc.event_id AND t.status_tiket <> 'BATAL' AND p.status = 'BERHASIL'
                       AND (t.ticket_category_id = tc.id
                            OR (t.ticket_category_id IS NULL AND t.jenis_tiket = tc.nama_kategori))) AS pendapatan
            FROM ticket_categories tc
            WHERE tc.event_id = :id
            ORDER BY tc.created_at ASC, tc.nama_kategori ASC");
        $q->execute(['id' => $eventId]);
        return $q->fetchAll();
    }
}