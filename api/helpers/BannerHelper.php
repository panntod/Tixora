<?php
declare(strict_types=1);

/**
 * Helper banner acara.
 *
 * Nilai kolom events.banner_url:
 *   "/default-1" .. "/default-4"  => banner dibuat oleh assets/default-banner.js
 *   URL / path lain               => gambar poster yang di-upload
 *   kosong (data lama)            => varian default otomatis dari id acara
 *
 * Pastikan halaman yang memakainya memuat:
 *   <script src="<?= BASE_URL ?>/assets/default-banner.js"></script>
 */
final class BannerHelper
{
    /** Gaya <img> per layout (harus sama dengan ukuran layout di default-banner.js). */
    private const STYLES = [
        'card' => 'display:block;width:100%;height:138px',
        'wide' => 'display:block;width:100%;height:320px',
        'ticket' => 'display:block;width:100%;height:200px;border-radius:16px 16px 0 0',
        'thumb' => 'display:block;flex:none;width:132px;height:100px;border-radius:8px',
    ];

    /** Mengembalikan "1".."4" untuk banner default, atau null bila gambar upload. */
    public static function variant(array $row): ?string
    {
        $url = trim((string) ($row['banner_url'] ?? ''));
        if ($url === '') {
            $seed = (string) ($row['event_id'] ?? $row['id'] ?? '');
            return (string) ((crc32($seed) % 4) + 1);
        }
        if (preg_match('#^/?default-([1-4])$#', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * @param array  $row    baris yang memuat banner_url, judul_event, kategori (+ event_id / id)
     * @param string $layout card | wide | ticket | thumb
     * @param array  $opt    kicker (string), title (string), dimmed (bool), style (CSS tambahan)
     */
    public static function render(array $row, string $layout = 'card', array $opt = []): string
    {
        $style = (self::STYLES[$layout] ?? self::STYLES['card']) . ';' . ($opt['style'] ?? '');
        $title = (string) ($opt['title'] ?? $row['judul_event'] ?? '');
        $e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES);

        $variant = self::variant($row);
        if ($variant !== null) {
            $kicker = $opt['kicker'] ?? strtoupper((string) ($row['kategori'] ?? ''));
            return '<img style="' . $e($style) . '"'
                . ' data-default-banner="' . $variant . '"'
                . ' data-layout="' . $e($layout) . '"'
                . (!empty($opt['dimmed']) ? ' data-dimmed="true"' : '')
                . ' data-kicker="' . $e($kicker) . '"'
                . ' data-title="' . $e($title) . '">';
        }

        $url = trim((string) $row['banner_url']);
        $src = preg_match('#^https?://#i', $url) ? $url : BASE_URL . '/' . ltrim($url, '/');
        return '<img style="' . $e($style . ';object-fit:cover' . (!empty($opt['dimmed']) ? ';filter:grayscale(75%)' : '')) . '"'
            . ' src="' . $e($src) . '" alt="' . $e($title) . '">';
    }
}