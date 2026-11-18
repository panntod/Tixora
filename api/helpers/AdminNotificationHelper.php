<?php
declare(strict_types=1);

require_once __DIR__ . '/AuthHelper.php';
require_once __DIR__ . '/AccountApplicationHelper.php';

/**
 * Notifikasi admin.
 *
 * Daftar notifikasi TIDAK disimpan; ia diturunkan dari data yang sudah ada sehingga selalu sinkron
 * dengan keadaan sebenarnya dan tidak perlu "hook" di setiap tempat kejadian:
 *   - PERLU_TINDAKAN : ringkasan antrean + tiap pengajuan / pengajuan ulang acara (event_submission_logs)
 *   - SISTEM         : penyelenggara baru bergabung, laporan mingguan siap diunduh
 *
 * Yang disimpan hanya penanda "terakhir dibaca" per admin (tabel admin_notification_state, migrasi 011).
 * Notifikasi dianggap belum dibaca bila waktunya lebih baru dari penanda tersebut.
 */
final class AdminNotificationHelper
{
    public const FILTERS = ['semua', 'perlu-tindakan', 'sistem'];

    private const WINDOW_HARI    = 30;  // hanya kejadian 30 hari terakhir yang ditampilkan
    private const BATAS_ITEM     = 50;
    private const ANTREAN_LAMBAT = 3;   // hari; antrean terlama >= ini ditandai peringatan

    private const BULAN = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /** Membuat tabel penanda baca bila belum ada (migrasi 011), sekali per request. */
    public static function ensureSchema(PDO $db): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $exists = $db->query("SELECT to_regclass('public.admin_notification_state')")->fetchColumn();
        if (!$exists) {
            $sql = file_get_contents(__DIR__ . '/../migrations/011_admin_notification_state.sql');
            if ($sql === false) {
                throw new RuntimeException('File migrasi 011_admin_notification_state.sql tidak ditemukan.');
            }
            $db->exec($sql);
        }
    }

    /** Menandai semua notifikasi yang ada sekarang sebagai sudah dibaca oleh admin ini. */
    public static function markAllRead(PDO $db, string $adminId): void
    {
        self::ensureSchema($db);
        $db->prepare("
            INSERT INTO admin_notification_state (admin_id, last_read_at, updated_at)
            VALUES (:id, LOCALTIMESTAMP, LOCALTIMESTAMP)
            ON CONFLICT (admin_id)
            DO UPDATE SET last_read_at = LOCALTIMESTAMP, updated_at = LOCALTIMESTAMP
        ")->execute(['id' => $adminId]);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, belum_dibaca: int, sekarang: string}
     *
     * Setiap item: id, kategori (PERLU_TINDAKAN|SISTEM), tingkat (PENTING|PERINGATAN|INFO),
     * aktif (masih menunggu tindakan admin), judul, pesan, waktu, waktu_label, tautan (path, tanpa BASE_URL), dibaca.
     */
    public static function feed(PDO $db, string $adminId): array
    {
        self::ensureSchema($db);
        AccountApplicationHelper::ensureSchema($db);

        $now = (string) $db->query("SELECT LOCALTIMESTAMP")->fetchColumn();

        $stmtRead = $db->prepare("SELECT last_read_at FROM admin_notification_state WHERE admin_id = :id");
        $stmtRead->execute(['id' => $adminId]);
        $lastRead = $stmtRead->fetchColumn();
        $lastReadTs = $lastRead ? strtotime((string) $lastRead) : null;

        $pinned = [];
        $items  = [];

        // 1. Ringkasan antrean (disematkan di paling atas).
        $row = $db->query("
            SELECT COUNT(*) AS jml,
                   MIN(COALESCE(s.terakhir, e.created_at)) AS terlama,
                   MAX(COALESCE(s.terakhir, e.created_at)) AS terbaru
            FROM events e
            LEFT JOIN (
                SELECT event_id, MAX(created_at) AS terakhir
                FROM event_submission_logs
                WHERE status = 'MENUNGGU_TINJAUAN'
                GROUP BY event_id
            ) s ON s.event_id = e.id
            WHERE e.status_verifikasi = 'MENUNGGU_TINJAUAN'
        ")->fetch(PDO::FETCH_ASSOC);

        $jml = (int) ($row['jml'] ?? 0);
        if ($jml > 0 && !empty($row['terbaru']) && !empty($row['terlama'])) {
            $hariTerlama = (int) (new DateTimeImmutable($now))->diff(new DateTimeImmutable((string) $row['terlama']))->days;
            $pinned[] = [
                'id'       => 'antrean',
                'kategori' => 'PERLU_TINDAKAN',
                'tingkat'  => $hariTerlama >= self::ANTREAN_LAMBAT ? 'PERINGATAN' : 'PENTING',
                'aktif'    => true,
                'judul'    => $jml . ' acara menunggu tinjauan',
                'pesan'    => 'Pengajuan terlama sejak ' . self::tgl((string) $row['terlama'], true) . '.',
                'waktu'    => (string) $row['terbaru'],
                'tautan'   => '/admin/antrean-acara?status=MENUNGGU_TINJAUAN',
            ];
        }

        // 1b. Pengajuan akun penyelenggara yang menunggu persetujuan.
        $akun = $db->query("
            SELECT COUNT(*) AS jml, MIN(diajukan_pada) AS terlama, MAX(diajukan_pada) AS terbaru
            FROM users WHERE status_pengajuan = 'MENUNGGU' AND diajukan_pada IS NOT NULL
        ")->fetch(PDO::FETCH_ASSOC);
        $jmlAkun = (int) ($akun['jml'] ?? 0);
        if ($jmlAkun > 0) {
            $hariAkun = (int) (new DateTimeImmutable($now))->diff(new DateTimeImmutable((string) $akun['terlama']))->days;
            $pinned[] = [
                'id'       => 'antrean-akun',
                'kategori' => 'PERLU_TINDAKAN',
                'tingkat'  => $hariAkun >= self::ANTREAN_LAMBAT ? 'PERINGATAN' : 'PENTING',
                'aktif'    => true,
                'judul'    => $jmlAkun . ' pengajuan akun penyelenggara menunggu',
                'pesan'    => 'Pengajuan terlama sejak ' . self::tgl((string) $akun['terlama'], true) . '.',
                'waktu'    => (string) $akun['terbaru'],
                'tautan'   => '/admin/permohonan-akun?status=MENUNGGU',
            ];

            $stmtAkun = $db->query("
                SELECT id, nama, COALESCE(NULLIF(institusi, ''), email) AS asal, diajukan_pada
                FROM users
                WHERE status_pengajuan = 'MENUNGGU' AND diajukan_pada IS NOT NULL
                ORDER BY diajukan_pada DESC
                LIMIT 10
            ");
            foreach ($stmtAkun->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $items[] = [
                    'id'       => 'akun-' . $r['id'],
                    'kategori' => 'PERLU_TINDAKAN',
                    'tingkat'  => 'PENTING',
                    'aktif'    => true,
                    'judul'    => $r['nama'] . ' mengajukan akun penyelenggara',
                    'pesan'    => $r['asal'] . ' menunggu persetujuan.',
                    'waktu'    => (string) $r['diajukan_pada'],
                    'tautan'   => '/admin/permohonan-akun?status=MENUNGGU',
                ];
            }
        }

        // 2. Pengajuan / pengajuan ulang acara.
        $stmtSub = $db->query(sprintf("
            SELECT l.id, l.created_at, e.id AS event_id, e.judul_event, e.status_verifikasi,
                   COALESCE(NULLIF(u.institusi, ''), u.nama) AS owner_tampil,
                   (SELECT COUNT(*) FROM event_submission_logs p
                     WHERE p.event_id = l.event_id AND p.status = 'MENUNGGU_TINJAUAN'
                       AND p.created_at <= l.created_at) AS urutan,
                   (SELECT MAX(x.created_at) FROM event_submission_logs x
                     WHERE x.event_id = l.event_id AND x.status = 'MENUNGGU_TINJAUAN') AS terakhir
            FROM event_submission_logs l
            JOIN events e ON e.id = l.event_id
            JOIN users u ON u.id = e.owner_id
            WHERE l.status = 'MENUNGGU_TINJAUAN'
              AND l.created_at >= LOCALTIMESTAMP - INTERVAL '%d days'
            ORDER BY l.created_at DESC
            LIMIT 30
        ", self::WINDOW_HARI));
        foreach ($stmtSub->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $urutan  = (int) $r['urutan'];
            $aktif   = $r['status_verifikasi'] === 'MENUNGGU_TINJAUAN'
                && strtotime((string) $r['created_at']) === strtotime((string) $r['terakhir']);
            $judul   = (string) $r['judul_event'];
            $pemilik = (string) $r['owner_tampil'];

            if ($urutan > 1) {
                $judul .= ' diajukan ulang';
                $pesan  = $pemilik . ' mengirim revisi ke-' . ($urutan - 1) . '.';
            } else {
                $judul .= ' diajukan';
                $pesan  = $pemilik . ' mengajukan acara baru untuk ditinjau.';
            }
            if (!$aktif) {
                $pesan .= ' Sudah ditinjau.';
            }

            $items[] = [
                'id'       => 'pengajuan-' . $r['id'],
                'kategori' => 'PERLU_TINDAKAN',
                'tingkat'  => $aktif ? 'PENTING' : 'INFO',
                'aktif'    => $aktif,
                'judul'    => $judul,
                'pesan'    => $pesan,
                'waktu'    => (string) $r['created_at'],
                'tautan'   => '/admin/tinjauan-acara?id=' . rawurlencode((string) $r['event_id']),
            ];
        }

        // 3. Sistem: penyelenggara baru bergabung.
        $stmtOwner = $db->prepare(sprintf("
            SELECT id, nama, COALESCE(NULLIF(institusi, ''), nama) AS nama_tampil,
                   COALESCE(ditinjau_pada, created_at) AS bergabung
            FROM users
            WHERE role_id = :role
              AND (status_pengajuan IS NULL OR status_pengajuan = 'DISETUJUI')
              AND COALESCE(ditinjau_pada, created_at) >= LOCALTIMESTAMP - INTERVAL '%d days'
            ORDER BY bergabung DESC
            LIMIT 10
        ", self::WINDOW_HARI));
        $stmtOwner->execute(['role' => AuthHelper::ROLE_EVENT_OWNER]);
        foreach ($stmtOwner->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $items[] = [
                'id'       => 'penyelenggara-' . $r['id'],
                'kategori' => 'SISTEM',
                'tingkat'  => 'INFO',
                'aktif'    => false,
                'judul'    => 'Penyelenggara baru: ' . $r['nama_tampil'],
                'pesan'    => $r['nama'] . ' bergabung sebagai penyelenggara.',
                'waktu'    => (string) $r['bergabung'],
                'tautan'   => '/admin/penyelenggara?id=' . rawurlencode((string) $r['id']),
            ];
        }

        // 4. Sistem: laporan mingguan (terbit tiap Senin 06.00, periode Senin-Minggu sebelumnya).
        $tsLaporan = (string) $db->query("
            SELECT CASE
                     WHEN date_trunc('week', LOCALTIMESTAMP) + INTERVAL '6 hours' <= LOCALTIMESTAMP
                       THEN date_trunc('week', LOCALTIMESTAMP) + INTERVAL '6 hours'
                     ELSE date_trunc('week', LOCALTIMESTAMP) - INTERVAL '7 days' + INTERVAL '6 hours'
                   END AS ts
        ")->fetchColumn();
        if ($tsLaporan !== '') {
            $terbit  = new DateTimeImmutable($tsLaporan);
            $mulai   = $terbit->modify('-7 days');
            $selesai = $terbit->modify('-1 day');
            $items[] = [
                'id'       => 'laporan-' . $terbit->format('Ymd'),
                'kategori' => 'SISTEM',
                'tingkat'  => 'INFO',
                'aktif'    => false,
                'judul'    => 'Laporan mingguan siap diunduh',
                'pesan'    => 'Periode ' . self::tgl($mulai->format('Y-m-d'), $mulai->format('Y') !== $selesai->format('Y'))
                              . ' – ' . self::tgl($selesai->format('Y-m-d'), true) . '.',
                'waktu'    => $terbit->format('Y-m-d H:i:s'),
                'tautan'   => '/admin/laporan?periode=7',
            ];
        }

        // Urutkan: terbaru dulu (yang disematkan tetap di atas), lalu batasi jumlahnya.
        usort($items, fn(array $a, array $b): int => strtotime($b['waktu']) <=> strtotime($a['waktu']));
        $items = array_slice(array_merge($pinned, $items), 0, self::BATAS_ITEM);

        $belumDibaca = 0;
        foreach ($items as &$it) {
            $ts          = strtotime($it['waktu']);
            $it['dibaca'] = $lastReadTs !== null && $ts <= $lastReadTs;
            $it['waktu_label'] = self::waktuLabel($it['waktu'], $now);
            if (!$it['dibaca']) {
                $belumDibaca++;
            }
        }
        unset($it);

        return ['items' => $items, 'belum_dibaca' => $belumDibaca, 'sekarang' => $now];
    }

    /** Menyaring item sesuai filter: semua | perlu-tindakan (hanya yang masih menunggu tindakan) | sistem. */
    public static function filter(array $items, string $filter): array
    {
        return match ($filter) {
            'perlu-tindakan' => array_values(array_filter($items, fn(array $i): bool => $i['kategori'] === 'PERLU_TINDAKAN' && $i['aktif'])),
            'sistem'         => array_values(array_filter($items, fn(array $i): bool => $i['kategori'] === 'SISTEM')),
            default          => $items,
        };
    }

    /** "Hari ini, 08.00" / "Kemarin, 08.00" / "8 Okt, 09.30" (+ tahun bila bukan tahun ini). */
    public static function waktuLabel(string $ts, string $now): string
    {
        $t = new DateTimeImmutable($ts);
        $n = new DateTimeImmutable($now);
        $jam = $t->format('H.i');

        $selisih = $t > $n ? 0 : (int) $t->setTime(0, 0)->diff($n->setTime(0, 0))->days;
        if ($selisih === 0) {
            return 'Hari ini, ' . $jam;
        }
        if ($selisih === 1) {
            return 'Kemarin, ' . $jam;
        }
        return self::tgl($ts, $t->format('Y') !== $n->format('Y')) . ', ' . $jam;
    }

    /** "8 Okt" atau "8 Okt 2026". */
    private static function tgl(string $ts, bool $tahun): string
    {
        $t = strtotime($ts);
        return (int) date('j', $t) . ' ' . self::BULAN[(int) date('n', $t)] . ($tahun ? ' ' . date('Y', $t) : '');
    }
}
