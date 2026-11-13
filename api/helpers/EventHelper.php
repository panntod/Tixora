<?php

declare(strict_types=1);

require_once __DIR__ . '/UploadHelper.php';
require_once __DIR__ . '/../../pages/event-owner/event-layout.php';

/**
 * Pendukung wizard "Buat acara baru" (4 langkah):
 *   1 Informasi & jadwal  -> /event-owner/buat-acara
 *   2 Tiket & kapasitas   -> /event-owner/buat-acara/tiket
 *   3 Rundown             -> /event-owner/buat-acara/rundown
 *   4 Tinjau & ajukan     -> /event-owner/buat-acara/tinjau
 *
 * Setiap langkah langsung disimpan ke database sebagai acara berstatus DRAF.
 * Baru di langkah 4 status berubah menjadi MENUNGGU_TINJAUAN.
 */
final class EventHelper
{
    public const KATEGORI = ['Seminar', 'Workshop', 'Konferensi', 'Kompetisi'];

    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    private const BULAN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    private const STEP_PATH = [
        1 => '/event-owner/buat-acara',
        2 => '/event-owner/buat-acara/tiket',
        3 => '/event-owner/buat-acara/rundown',
        4 => '/event-owner/buat-acara/tinjau',
    ];
    private const STEP_LABEL = [1 => 'Informasi & jadwal', 2 => 'Tiket & kapasitas', 3 => 'Rundown', 4 => 'Tinjau & ajukan'];

    // ------------------------------------------------------------------ dasar

    public static function h(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    /** Menambah kolom masa penjualan (migrasi 005) bila belum ada, plus kolom harga (migrasi 002). */
    public static function ensureSchema(PDO $db): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (class_exists('PaymentHelper')) {
            PaymentHelper::ensureSchema($db);
        }
        $has = (int) $db->query("
            SELECT COUNT(*) FROM information_schema.columns
            WHERE table_name = 'ticket_categories' AND column_name IN ('mulai_dijual', 'berakhir_dijual')
        ")->fetchColumn();
        if ($has < 2) {
            $sql = file_get_contents(__DIR__ . '/../migrations/005_jadwal_penjualan_tiket.sql');
            if ($sql === false) {
                throw new RuntimeException('File migrasi 005_jadwal_penjualan_tiket.sql tidak ditemukan.');
            }
            $db->exec($sql);
        }
    }

    public static function stepUrl(int $step, ?string $eventId = null): string
    {
        return BASE_URL . self::STEP_PATH[$step] . ($eventId ? '?id=' . rawurlencode($eventId) : '');
    }

    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /** Acara milik owner (null jika id salah / bukan miliknya). */
    public static function findOwnedEvent(PDO $db, string $id, string $ownerId): ?array
    {
        if (!preg_match(self::UUID, $id)) {
            return null;
        }
        $stmt = $db->prepare("SELECT * FROM events WHERE id = :id AND owner_id = :owner");
        $stmt->execute(['id' => $id, 'owner' => $ownerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Memuat acara dari ?id= untuk diedit lewat wizard. Id tidak valid -> kembali ke langkah 1 (acara baru).
     * Hanya DRAF dan DITOLAK yang bisa diedit; yang sedang ditinjau/terbit -> ke Acara saya.
     */
    public static function requireDraft(PDO $db, array $owner): array
    {
        $id = (string) ($_GET['id'] ?? $_POST['id'] ?? '');
        $event = self::findOwnedEvent($db, $id, (string) $owner['id']);
        if (!$event) {
            self::redirect(BASE_URL . '/event-owner/buat-acara');
        }
        if (!in_array($event['status_verifikasi'], ['DRAF', 'DITOLAK'], true)) {
            self::redirect(BASE_URL . '/event-owner/acara-saya');
        }
        return $event;
    }

    // -------------------------------------------------------------- format

    public static function tgl(string $ts, bool $withTime = false): string
    {
        $t = strtotime($ts);
        if ($t === false) {
            return '';
        }
        $s = date('j', $t) . ' ' . self::BULAN[(int) date('n', $t)] . ' ' . date('Y', $t);
        return $withTime ? $s . ', ' . date('H.i', $t) : $s;
    }

    /** "12–13 Nov 2026", "30 Okt – 2 Nov 2026" atau "24 Okt 2026, 07.30–16.00" bila satu hari. */
    public static function rentang(string $mulai, string $selesai): string
    {
        $a = strtotime($mulai);
        $b = strtotime($selesai);
        if ($a === false || $b === false) {
            return '';
        }
        if (date('Y-m-d', $a) === date('Y-m-d', $b)) {
            return self::tgl($mulai) . ', ' . date('H.i', $a) . '–' . date('H.i', $b);
        }
        if (date('Y-m', $a) === date('Y-m', $b)) {
            return date('j', $a) . '–' . date('j', $b) . ' ' . self::BULAN[(int) date('n', $b)] . ' ' . date('Y', $b);
        }
        if (date('Y', $a) === date('Y', $b)) {
            return date('j', $a) . ' ' . self::BULAN[(int) date('n', $a)] . ' – ' . self::tgl($selesai);
        }
        return self::tgl($mulai) . ' – ' . self::tgl($selesai);
    }

    public static function draftSaved(?string $updatedAt): string
    {
        $t = $updatedAt ? strtotime($updatedAt) : false;
        if ($t === false) {
            return 'Draf tersimpan';
        }
        return 'Draf tersimpan ' . date('j', $t) . ' ' . self::BULAN[(int) date('n', $t)] . ', ' . date('H.i', $t);
    }

    public static function toLocalInput(?string $ts): string
    {
        $t = $ts ? strtotime($ts) : false;
        return $t === false ? '' : date('Y-m-d\TH:i', $t);
    }

    // ----------------------------------------------------------- validasi

    /**
     * Validasi langkah 1. $in memakai kunci kolom tabel events.
     * @return array<string,string> kolom => pesan
     */
    public static function validateInfo(array $in): array
    {
        $e = [];
        $judul = trim((string) ($in['judul_event'] ?? ''));
        if ($judul === '') {
            $e['judul_event'] = 'Nama acara wajib diisi.';
        } elseif (mb_strlen($judul) > 255) {
            $e['judul_event'] = 'Nama acara maksimal 255 karakter.';
        }

        if (!in_array((string) ($in['kategori'] ?? ''), self::KATEGORI, true)) {
            $e['kategori'] = 'Pilih salah satu kategori.';
        }

        $len = mb_strlen(trim((string) ($in['deskripsi'] ?? '')));
        if ($len < 50) {
            $e['deskripsi'] = "Deskripsi minimal 50 karakter (saat ini {$len}).";
        }

        $mulai = strtotime((string) ($in['tanggal_mulai'] ?? ''));
        $selesai = strtotime((string) ($in['tanggal_selesai'] ?? ''));
        if ($mulai === false) {
            $e['tanggal_mulai'] = 'Waktu mulai wajib diisi.';
        }
        if ($selesai === false) {
            $e['tanggal_selesai'] = 'Waktu selesai wajib diisi.';
        } elseif ($mulai !== false && $selesai <= $mulai) {
            $e['tanggal_selesai'] = 'Waktu selesai harus setelah waktu mulai.';
        }

        $tempat = trim((string) ($in['nama_tempat'] ?? ''));
        if ($tempat === '') {
            $e['nama_tempat'] = 'Nama tempat wajib diisi.';
        } elseif (mb_strlen($tempat) > 255) {
            $e['nama_tempat'] = 'Nama tempat maksimal 255 karakter.';
        }

        $kota = trim((string) ($in['kota'] ?? ''));
        if ($kota === '') {
            $e['kota'] = 'Kota wajib diisi.';
        } elseif (mb_strlen($kota) > 100) {
            $e['kota'] = 'Kota maksimal 100 karakter.';
        }
        return $e;
    }

    /** Normalisasi "07:30" / "7.30" -> "07.30"; null bila tidak valid. */
    public static function normalizeTime(string $raw): ?string
    {
        if (!preg_match('/^\s*([01]?\d|2[0-3])[:.]([0-5]\d)\s*$/', $raw, $m)) {
            return null;
        }
        return sprintf('%02d.%s', (int) $m[1], $m[2]);
    }

    public static function timeToMinutes(string $t): int
    {
        [$h, $m] = array_map('intval', explode('.', str_replace(':', '.', $t)) + [0, 0]);
        return $h * 60 + $m;
    }

    // -------------------------------------------------------------- poster

    /** Validasi poster tanpa menyimpan. Mengembalikan pesan error atau null (juga null bila tidak ada berkas). */
    public static function checkPoster(array $file): ?string
    {
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            return 'Ukuran poster maksimal 2 MB.';
        }
        if ($err !== UPLOAD_ERR_OK) {
            return 'Poster gagal diunggah. Silakan coba lagi.';
        }
        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return 'Ukuran poster maksimal 2 MB.';
        }
        $info = @getimagesize((string) $file['tmp_name']);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return 'Poster harus berupa gambar JPG atau PNG.';
        }
        $ratio = $info[0] / max(1, $info[1]);
        if ($ratio < 1.6 || $ratio > 1.96) {
            return 'Rasio poster harus 16:9 (contoh 1280×720 px).';
        }
        return null;
    }

    /** @return array{0:?string,1:?string} [path relatif untuk banner_url, error] */
    public static function storePoster(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        try {
            $up = new UploadHelper(ROOT_DIR . '/assets/uploads/');
            return ['assets/uploads/' . $up->uploadFile($file), null];
        } catch (Throwable $e) {
            return [null, $e->getMessage()];
        }
    }

    /** URL poster hasil unggahan (null untuk banner bawaan / kosong). */
    public static function posterUrl(?string $bannerUrl): ?string
    {
        $u = trim((string) $bannerUrl);
        if ($u === '' || preg_match('#^/?default-[1-4]$#', $u)) {
            return null;
        }
        return preg_match('#^https?://#i', $u) ? $u : BASE_URL . '/' . ltrim($u, '/');
    }

    public static function deletePoster(?string $bannerUrl): void
    {
        $u = trim((string) $bannerUrl);
        if (!str_starts_with($u, 'assets/uploads/') || str_contains($u, '..')) {
            return;
        }
        $file = ROOT_DIR . '/' . $u;
        if (is_file($file)) {
            @unlink($file);
        }
    }

    // --------------------------------------------------------------- tinjau

    /**
     * Ringkasan kelengkapan untuk langkah 4.
     * @return array{info:array,kategori:array,total_kuota:int,rundown:int,rab_total:float,rab_count:int,ready:bool}
     */
    public static function review(PDO $db, array $event): array
    {
        $info = self::validateInfo($event);

        $st = $db->prepare("SELECT nama_kategori, harga, kuota FROM ticket_categories WHERE event_id = :id ORDER BY created_at ASC, nama_kategori ASC");
        $st->execute(['id' => $event['id']]);
        $kategori = $st->fetchAll();
        $totalKuota = (int) array_sum(array_column($kategori, 'kuota'));

        $st = $db->prepare("SELECT COUNT(*) FROM rundowns WHERE event_id = :id");
        $st->execute(['id' => $event['id']]);
        $rundown = (int) $st->fetchColumn();

        $st = $db->prepare("SELECT COUNT(*), COALESCE(SUM(rencana), 0) FROM rab_details WHERE event_id = :id");
        $st->execute(['id' => $event['id']]);
        [$rabCount, $rabTotal] = $st->fetch(PDO::FETCH_NUM);

        return [
            'info'        => $info,
            'kategori'    => $kategori,
            'total_kuota' => $totalKuota,
            'rundown'     => $rundown,
            'rab_total'   => (float) $rabTotal,
            'rab_count'   => (int) $rabCount,
            'ready'       => !$info && $totalKuota > 0 && $rundown > 0,
        ];
    }

    // ----------------------------------------------------------- kerangka

    /** Membuka halaman: <head>, sidebar, judul, tombol Pratinjau, dan stepper. Tutup dengan renderBottom(). */
    public static function renderTop(PDO $db, array $owner, int $step, ?array $event): void
    {
        $h = [self::class, 'h'];
        $id = $event['id'] ?? null;
        $subtitle = match ($step) {
            1 => 'Langkah 1 dari 4 · Perubahan tersimpan otomatis sebagai draf',
            4 => 'Langkah 4 dari 4 · Periksa sebelum diajukan',
            default => "Langkah {$step} dari 4" . ($event ? ' · ' . self::draftSaved($event['updated_at'] ?? null) : ''),
        };
        $check = '<svg width="12" height="12" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" /></svg>';
?>
        <!DOCTYPE html>
        <html lang="id">

        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta charset="utf-8">
            <title>TIXORA — Organizer — Buat Acara</title>
            <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
            <style>
                input.input,
                select.input,
                textarea.input {
                    width: 100%;
                    box-sizing: border-box;
                    font-family: inherit;
                }

                textarea.input {
                    height: auto;
                    min-height: 96px;
                    padding: 10px 12px;
                    display: block;
                    resize: vertical;
                }

                a.button {
                    text-decoration: none;
                }

                .button[disabled],
                .button.is-disabled {
                    opacity: .5;
                    cursor: not-allowed;
                    pointer-events: none;
                }

                button.button {
                    cursor: pointer;
                    font-family: inherit;
                }

                .step-item {
                    text-decoration: none;
                }
            </style>
        </head>

        <body>
            <div class="frame">
                <div class="app">
                    <?= EO::sidebar($db, $owner, 'buat-acara') ?>

                    <main class="main">
                        <p class="crumb">Acara saya / Buat acara</p>
                        <div class="head">
                            <div>
                                <h1 class="page-title">Buat acara baru</h1>
                                <p class="page-subtitle"><?= $h($subtitle) ?></p>
                            </div>
                            <div class="head-actions">
                                <?php if ($id): ?>
                                    <a class="button secondary-button" href="<?= BASE_URL ?>/detail-acara?id=<?= $h($id) ?>" target="_blank" rel="noopener">Pratinjau</a>
                                <?php else: ?>
                                    <span class="button secondary-button is-disabled" title="Simpan langkah 1 terlebih dahulu">Pratinjau</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="stepper">
                            <?php foreach (self::STEP_LABEL as $n => $label): ?>
                                <?php
                                $cls = $n < $step ? 'step-done' : ($n === $step ? 'step-active' : '');
                                $dot = $n < $step ? $check : (string) $n;
                                $inner = '<span class="step-dot">' . $dot . '</span>' . $h($label);
                                ?>
                                <?php if ($n < $step && $id): ?>
                                    <a class="step-item <?= $cls ?>" href="<?= self::stepUrl($n, $id) ?>" style="color:inherit"><?= $inner ?></a>
                                <?php else: ?>
                                    <span class="step-item <?= $cls ?>"><?= $inner ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                <?php
            }

            public static function renderBottom(): void
            {
                echo "            </main>\n        </div>\n    </div>\n    <script src=\"" . BASE_URL . "/event-owner/sidebar.js\"></script>\n</body>\n\n</html>\n";
            }
        }
