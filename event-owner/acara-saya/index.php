<?php
require_once __DIR__ . '/../_bootstrap.php';
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
    <div class="frame" style="width:1440px;height:881px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                        <path
                            d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z"
                            fill="#4B34DB" />
                        <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round"
                            fill="none" />
                    </svg><span class="logo-text">tixora</span></div><span class="side-item">Dasbor</span><span
                    class="side-item side-item-active">Acara saya</span><span class="side-item">Buat acara</span><span
                    class="side-item">Pengaturan</span><span class="side-item">Keluar</span>
                <div class="side-user"><b>Dimas Prakoso</b>HMTI Fakultas Ilmu Komputer</div>
            </aside>
            <main class="main">
                <p class="crumb">Acara saya</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Acara saya</h1>
                        <p class="page-subtitle">Kelola semua acara dalam satu daftar</p>
                    </div>
                    <div class="head-actions"><span class="button primary-button button-large">+ Buat acara</span></div>
                </div>
                <div class="filter-bar">
                    <div class="input" style="flex:1;width:auto"><span class="input-placeholder">Cari nama acara</span>
                    </div>
                    <div class="input" style="width:180px"><span>Tanggal terdekat</span><svg width="16" height="16"
                            viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></div>
                </div>
                <div class="chip-row"><span class="chip chip-active">Semua 12</span><span class="chip">Draf
                        3</span><span class="chip">Menunggu tinjauan 1</span><span class="chip">Disetujui 1</span><span
                        class="chip">Terbit 3</span><span class="chip">Ditolak 1</span><span class="chip">Selesai
                        2</span><span class="chip">Dibatalkan 1</span></div>
                <div class="tbl-card">
                    <div class="tbl-head">
                        <div class="tbl-cell" style="width:410px">ACARA</div>
                        <div class="tbl-cell" style="width:130px">TANGGAL</div>
                        <div class="tbl-cell" style="width:160px">TIKET TERJUAL</div>
                        <div class="tbl-cell" style="width:190px">STATUS</div>
                        <div class="tbl-cell tbl-right" style="width:206px">TINDAKAN</div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:410px"><b>Seminar Nasional Teknologi Informatika 2026</b>
                        </div>
                        <div class="tbl-cell" style="width:130px">24 Okt 2026</div>
                        <div class="tbl-cell" style="width:160px">
                            <div class="progress">
                                <div class="progress-fill" style="width:78%"></div>
                            </div>
                        </div>
                        <div class="tbl-cell" style="width:190px"><span class="badge badge-success"><span
                                    class="badge-dot"></span>Terbit</span></div>
                        <div class="tbl-cell tbl-right" style="width:206px"><span
                                class="button secondary-button small-button">Kelola</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:410px"><b>Kuliah Umum Kewirausahaan</b></div>
                        <div class="tbl-cell" style="width:130px">17 Okt 2026</div>
                        <div class="tbl-cell" style="width:160px">
                            <div class="progress">
                                <div class="progress-fill" style="width:94%"></div>
                            </div>
                        </div>
                        <div class="tbl-cell" style="width:190px"><span class="badge badge-success"><span
                                    class="badge-dot"></span>Terbit</span></div>
                        <div class="tbl-cell tbl-right" style="width:206px"><span
                                class="button secondary-button small-button">Kelola</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:410px"><b>Konferensi Pendidikan Digital Jawa Timur</b></div>
                        <div class="tbl-cell" style="width:130px">12 Nov 2026</div>
                        <div class="tbl-cell" style="width:160px"><span class="muted">—</span></div>
                        <div class="tbl-cell" style="width:190px"><span class="badge badge-warning"><span
                                    class="badge-dot"></span>Menunggu tinjauan</span></div>
                        <div class="tbl-cell tbl-right" style="width:206px"><span
                                class="button ghost-button small-button">Lihat pengajuan</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:410px"><b>Workshop UI/UX untuk Pemula</b></div>
                        <div class="tbl-cell" style="width:130px">1 Nov 2026</div>
                        <div class="tbl-cell" style="width:160px"><span class="muted">—</span></div>
                        <div class="tbl-cell" style="width:190px"><span class="badge badge-info"><span
                                    class="badge-dot"></span>Disetujui</span></div>
                        <div class="tbl-cell tbl-right" style="width:206px"><span
                                class="button primary-button small-button">Terbitkan</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:410px"><b>Lomba Esai Nasional Mahasiswa</b></div>
                        <div class="tbl-cell" style="width:130px">20 Nov 2026</div>
                        <div class="tbl-cell" style="width:160px"><span class="muted">—</span></div>
                        <div class="tbl-cell" style="width:190px"><span class="badge badge-error"><span
                                    class="badge-dot"></span>Ditolak</span></div>
                        <div class="tbl-cell tbl-right" style="width:206px"><span
                                class="button primary-button small-button">Revisi &amp; ajukan ulang</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:410px"><b>Pelatihan Public Speaking</b></div>
                        <div class="tbl-cell" style="width:130px">31 Okt 2026</div>
                        <div class="tbl-cell" style="width:160px"><span class="muted">—</span></div>
                        <div class="tbl-cell" style="width:190px"><span class="badge badge-neutral"><span
                                    class="badge-dot"></span>Draf</span></div>
                        <div class="tbl-cell tbl-right" style="width:206px"><span
                                class="button secondary-button small-button">Lanjutkan</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:410px"><b>Seminar Karier dan Magang 2026</b></div>
                        <div class="tbl-cell" style="width:130px">3 Okt 2026</div>
                        <div class="tbl-cell" style="width:160px">
                            <div class="progress">
                                <div class="progress-fill" style="width:100%"></div>
                            </div>
                        </div>
                        <div class="tbl-cell" style="width:190px"><span class="badge badge-cyan"><span
                                    class="badge-dot"></span>Selesai</span></div>
                        <div class="tbl-cell tbl-right" style="width:206px"><span
                                class="button secondary-button small-button">Lihat laporan</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:410px"><b>Workshop Fotografi Dasar</b></div>
                        <div class="tbl-cell" style="width:130px">25 Okt 2026</div>
                        <div class="tbl-cell" style="width:160px">
                            <div class="progress">
                                <div class="progress-fill" style="width:22%"></div>
                            </div>
                        </div>
                        <div class="tbl-cell" style="width:190px"><span class="badge badge-error"><span
                                    class="badge-dot"></span>Dibatalkan</span></div>
                        <div class="tbl-cell tbl-right" style="width:206px"><span
                                class="button secondary-button small-button">Lihat detail</span></div>
                    </div>
                </div>
                <p class="help" style="margin-top:12px">Tindakan menyesuaikan status: acara menunggu tinjauan tidak
                    dapat diedit; acara terbit tidak dapat dihapus.</p>
            </main>
        </div>
    </div>
</body>

</html>