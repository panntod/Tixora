<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Laporan</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:939px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div><span class="side-label">ADMIN</span><a
                    class="side-item" href="<?= BASE_URL ?>/admin">Dasbor</a><a class="side-item" href="<?= BASE_URL ?>/admin/antrean-acara">Antrean
                    acara<span class="count-pill">4</span></a><a class="side-item" href="<?= BASE_URL ?>/admin/pengguna">Pengguna</a><a
                    class="side-item" href="<?= BASE_URL ?>/admin/penyelenggara">Penyelenggara</a><a class="side-item side-item-active"
                    href="<?= BASE_URL ?>/admin/laporan">Laporan</a><a class="side-item" href="<?= BASE_URL ?>/admin/notifikasi">Notifikasi</a><span
                    class="side-item">Keluar</span>
                <div class="side-user"><b>Rahmat Hidayat</b>Administrator</div>
            </aside>
            <main class="main">
                <p class="crumb">Laporan</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Laporan platform</h1>
                        <p class="page-subtitle">Periode 1 Sep – 10 Okt 2026</p>
                    </div>
                    <div class="head-actions">
                        <div class="input" style="width:210px"><span>1 Sep – 10 Okt 2026</span><svg width="16"
                                height="16" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg></div><span class="button secondary-button">Ekspor CSV</span><span
                            class="button primary-button">Unduh laporan PDF</span>
                    </div>
                </div>
                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">PENGAJUAN ACARA</div>
                        <div class="metric-value" style="">38</div>
                        <div class="metric-note">27 disetujui · 7 ditolak</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">PENGGUNA BARU</div>
                        <div class="metric-value" style="">384</div>
                        <div class="metric-note">Total 1.284</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">TIKET TERJUAL</div>
                        <div class="metric-value" style="">2.940</div>
                        <div class="metric-note">Semua acara</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">NILAI TRANSAKSI</div>
                        <div class="metric-value" style="">Rp212.450.000</div>
                        <div class="metric-note">Tiket berbayar</div>
                    </div>
                </div>
                <div class="cols">
                    <div class="card" style="width:552px;box-sizing:border-box">
                        <h2 class="card-title">Pengajuan dan persetujuan per minggu</h2><svg width="504" height="170"
                            viewBox="0 0 480 170" role="img"
                            aria-label="Pengajuan (muda) dan disetujui (tua) per minggu">
                            <line x1="0" y1="150" x2="480" y2="150" stroke="#E4E4EE" />
                            <rect x="20" y="126" width="14" height="24" rx="3" fill="#C9C5FF" />
                            <rect x="36" y="134" width="14" height="16" rx="3" fill="#4B34DB" />
                            <rect x="72" y="118" width="14" height="32" rx="3" fill="#C9C5FF" />
                            <rect x="88" y="126" width="14" height="24" rx="3" fill="#4B34DB" />
                            <rect x="124" y="126" width="14" height="24" rx="3" fill="#C9C5FF" />
                            <rect x="140" y="134" width="14" height="16" rx="3" fill="#4B34DB" />
                            <rect x="176" y="110" width="14" height="40" rx="3" fill="#C9C5FF" />
                            <rect x="192" y="118" width="14" height="32" rx="3" fill="#4B34DB" />
                            <rect x="228" y="118" width="14" height="32" rx="3" fill="#C9C5FF" />
                            <rect x="244" y="126" width="14" height="24" rx="3" fill="#4B34DB" />
                            <rect x="280" y="102" width="14" height="48" rx="3" fill="#C9C5FF" />
                            <rect x="296" y="110" width="14" height="40" rx="3" fill="#4B34DB" />
                            <rect x="332" y="118" width="14" height="32" rx="3" fill="#C9C5FF" />
                            <rect x="348" y="126" width="14" height="24" rx="3" fill="#4B34DB" />
                            <rect x="384" y="110" width="14" height="40" rx="3" fill="#C9C5FF" />
                            <rect x="400" y="118" width="14" height="32" rx="3" fill="#4B34DB" />
                            <rect x="436" y="118" width="14" height="32" rx="3" fill="#C9C5FF" />
                            <rect x="452" y="126" width="14" height="24" rx="3" fill="#4B34DB" />
                        </svg>
                        <p class="help">Ungu muda: diajukan · ungu tua: disetujui. 9 minggu terakhir.</p>
                    </div>
                    <div class="card" style="width:552px;box-sizing:border-box">
                        <h2 class="card-title">Pertumbuhan pengguna</h2><svg width="504" height="180"
                            viewBox="0 0 480 180" role="img" aria-label="Total pengguna naik dari 900 menjadi 1.284">
                            <line x1="0" y1="170" x2="480" y2="170" stroke="#E4E4EE" />
                            <polyline fill="none" stroke="#4B34DB" stroke-width="3" stroke-linejoin="round"
                                points="20,153 78,140 136,126 194,111 252,97 310,82 368,66 426,47 484,30" />
                        </svg>
                        <p class="help">Total pengguna 900 → 1.284.</p>
                    </div>
                </div>
                <div style="margin-top:24px">
                    <div class="card" style="padding:20px 16px 8px;width:1128px;box-sizing:border-box">
                        <h2 class="card-title" style="padding:0 8px">Acara dengan pendaftar terbanyak</h2>
                        <div style="padding:0">
                            <div class="tbl-head">
                                <div class="tbl-cell" style="width:560px">ACARA</div>
                                <div class="tbl-cell" style="width:356px">PENYELENGGARA</div>
                                <div class="tbl-cell" style="width:180px;text-align:right">PENDAFTAR</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:560px"><b>Seminar Nasional Teknologi Informatika
                                        2026</b></div>
                                <div class="tbl-cell" style="width:356px">HMTI FILKOM</div>
                                <div class="tbl-cell" style="width:180px;text-align:right">196</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:560px"><b>Kuliah Umum Kewirausahaan</b></div>
                                <div class="tbl-cell" style="width:356px">Fakultas Ekonomi</div>
                                <div class="tbl-cell" style="width:180px;text-align:right">94</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:560px"><b>Festival Musik Kampus 2026</b></div>
                                <div class="tbl-cell" style="width:356px">UKM Seni dan Musik</div>
                                <div class="tbl-cell" style="width:180px;text-align:right">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>