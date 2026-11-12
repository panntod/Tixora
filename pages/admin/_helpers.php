<?php
/**
 * Helper tampilan bersama untuk halaman admin (format tanggal Indonesia, label status, dsb).
 */
declare(strict_types=1);

if (!function_exists('adm_h')) {
    function adm_h($v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('adm_bulan')) {
    /** Singkatan bulan Indonesia (1-12). */
    function adm_bulan(int $m): string
    {
        static $b = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return $b[$m] ?? '';
    }
}

if (!function_exists('adm_tgl')) {
    /** "8 Okt" atau "8 Okt 2026". */
    function adm_tgl(?string $ts, bool $tahun = false): string
    {
        if (!$ts) {
            return '—';
        }
        $t = strtotime($ts);
        return (int) date('j', $t) . ' ' . adm_bulan((int) date('n', $t)) . ($tahun ? ' ' . date('Y', $t) : '');
    }
}

if (!function_exists('adm_tgl_jam')) {
    /** "8 Okt, 10.05". */
    function adm_tgl_jam(?string $ts): string
    {
        if (!$ts) {
            return '—';
        }
        return adm_tgl($ts) . ', ' . date('H.i', strtotime($ts));
    }
}

if (!function_exists('adm_rentang')) {
    /** "12–13 Nov 2026", "28 Okt – 2 Nov 2026" atau "15 Nov 2026". */
    function adm_rentang(?string $mulai, ?string $selesai): string
    {
        if (!$mulai) {
            return '—';
        }
        $a = strtotime($mulai);
        $b = $selesai ? strtotime($selesai) : $a;
        if (date('Y-m-d', $a) === date('Y-m-d', $b)) {
            return adm_tgl($mulai, true);
        }
        if (date('Y-n', $a) === date('Y-n', $b)) {
            return (int) date('j', $a) . '–' . (int) date('j', $b) . ' ' . adm_bulan((int) date('n', $a)) . ' ' . date('Y', $a);
        }
        return adm_tgl($mulai) . ' – ' . adm_tgl($selesai, true);
    }
}

if (!function_exists('adm_rupiah')) {
    function adm_rupiah($angka): string
    {
        return 'Rp' . number_format((float) $angka, 0, ',', '.');
    }
}

if (!function_exists('adm_status_norm')) {
    /** PUBLISHED dianggap sama dengan TERBIT. */
    function adm_status_norm(?string $s): string
    {
        $s = strtoupper((string) $s);
        return $s === 'PUBLISHED' ? 'TERBIT' : $s;
    }
}

if (!function_exists('adm_status_label')) {
    function adm_status_label(?string $s): string
    {
        $map = [
            'DRAF' => 'Draf',
            'MENUNGGU_TINJAUAN' => 'Menunggu tinjauan',
            'DISETUJUI' => 'Disetujui',
            'TERBIT' => 'Terbit',
            'DITOLAK' => 'Ditolak',
            'SELESAI' => 'Selesai',
            'BATAL' => 'Dibatalkan',
        ];
        $s = adm_status_norm($s);
        return $map[$s] ?? ucfirst(strtolower(str_replace('_', ' ', $s)));
    }
}

if (!function_exists('adm_status_badge')) {
    function adm_status_badge(?string $s): string
    {
        $map = [
            'MENUNGGU_TINJAUAN' => 'badge-warning',
            'DISETUJUI' => 'badge-info',
            'TERBIT' => 'badge-success',
            'DITOLAK' => 'badge-error',
            'BATAL' => 'badge-error',
            'SELESAI' => 'badge-cyan',
        ];
        return $map[adm_status_norm($s)] ?? 'badge-neutral';
    }
}

if (!function_exists('adm_jenis_pengajuan')) {
    /** Label jenis pengajuan dari jumlah pengajuan: 1 = "Pertama kali", >1 = "Revisi ke-N". */
    function adm_jenis_pengajuan(int $jumlahPengajuan): array
    {
        if ($jumlahPengajuan <= 1) {
            return ['Pertama kali', 'badge-neutral'];
        }
        return ['Revisi ke-' . ($jumlahPengajuan - 1), 'badge-purple'];
    }
}