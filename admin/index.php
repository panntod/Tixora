<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Dasbor</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:1125px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                        <path
                            d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z"
                            fill="#4B34DB" />
                        <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round"
                            fill="none" />
                    </svg><span class="logo-text">tixora</span></div><span class="side-label">ADMIN</span><a
                    class="side-item side-item-active" href="<?= BASE_URL ?>/admin">Dasbor</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/antrean-acara">Antrean acara<span class="count-pill">4</span></a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/pengguna">Pengguna</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/penyelenggara">Penyelenggara</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/laporan">Laporan</a><a class="side-item" href="<?= BASE_URL ?>/admin/notifikasi">Notifikasi</a><span
                    class="side-item">Keluar</span>
                <div class="side-user"><b>Rahmat Hidayat</b>Administrator</div>
            </aside>
            <main class="main">
                <p class="crumb">Dasbor</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Dasbor admin</h1>
                        <p class="page-subtitle">Ringkasan platform per 10 Okt 2026</p>
                    </div>
                    <div class="head-actions"><a class="button primary-button" href="<?= BASE_URL ?>/admin/antrean-acara">Buka antrean
                            acara</a><a class="button secondary-button" href="<?= BASE_URL ?>/admin/pengguna">Kelola pengguna</a></div>
                </div>
                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">TOTAL PENGGUNA</div>
                        <div class="metric-value" style="">1.284</div>
                        <div class="metric-note">+46 minggu ini</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">TOTAL ACARA</div>
                        <div class="metric-value" style="">96</div>
                        <div class="metric-note">41 sedang terbit</div>
                    </div>
                    <div class="metric" style="box-shadow:inset 0 0 0 2px #F3D9A4">
                        <div class="metric-label">MENUNGGU TINJAUAN</div>
                        <div class="metric-value" style="color:#B45309">4</div>
                        <div class="metric-note">Terlama: 6 Okt</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">ACARA TERBIT</div>
                        <div class="metric-value" style="">41</div>
                        <div class="metric-note">6 disetujui, belum terbit</div>
                    </div>
                </div>
                <div class="cols">
                    <div class="card" style="padding:20px 16px 8px;width:680px;box-sizing:border-box">
                        <h2 class="card-title" style="padding:0 8px">Perlu tindakan</h2>
                        <div style="padding:0">
                            <div class="tbl-head">
                                <div class="tbl-cell" style="width:250px">ACARA</div>
                                <div class="tbl-cell" style="width:80px">DIAJUKAN</div>
                                <div class="tbl-cell" style="width:140px">JENIS</div>
                                <div class="tbl-cell" style="width:178px"></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:250px"><b>Konferensi Pendidikan Digital Jawa
                                        Timur</b>
                                    <div class="cell-sub">HMTI FILKOM</div>
                                </div>
                                <div class="tbl-cell" style="width:80px">8 Okt</div>
                                <div class="tbl-cell" style="width:140px"><span class="badge badge-neutral"><span
                                            class="badge-dot"></span>Pertama kali</span></div>
                                <div class="tbl-cell tbl-right" style="width:178px"><span
                                        class="button primary-button small-button">Tinjau</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:250px"><b>Lomba Karya Tulis Ilmiah Nasional</b>
                                    <div class="cell-sub">BEM Fakultas Teknik</div>
                                </div>
                                <div class="tbl-cell" style="width:80px">8 Okt</div>
                                <div class="tbl-cell" style="width:140px"><span class="badge badge-purple"><span
                                            class="badge-dot"></span>Revisi ke-2</span></div>
                                <div class="tbl-cell tbl-right" style="width:178px"><span
                                        class="button primary-button small-button">Tinjau</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:250px"><b>Festival Musik Kampus 2026</b>
                                    <div class="cell-sub">UKM Seni dan Musik</div>
                                </div>
                                <div class="tbl-cell" style="width:80px">7 Okt</div>
                                <div class="tbl-cell" style="width:140px"><span class="badge badge-neutral"><span
                                            class="badge-dot"></span>Pertama kali</span></div>
                                <div class="tbl-cell tbl-right" style="width:178px"><span
                                        class="button primary-button small-button">Tinjau</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:250px"><b>Workshop Data Science Dasar</b>
                                    <div class="cell-sub">Komunitas Data Malang</div>
                                </div>
                                <div class="tbl-cell" style="width:80px">6 Okt</div>
                                <div class="tbl-cell" style="width:140px"><span class="badge badge-neutral"><span
                                            class="badge-dot"></span>Pertama kali</span></div>
                                <div class="tbl-cell tbl-right" style="width:178px"><span
                                        class="button primary-button small-button">Tinjau</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Aktivitas platform</h2>
                        <div class="feed-item"><span class="feed-dot"></span>
                            <div><b>HMTI FILKOM</b> mengajukan Konferensi Pendidikan Digital<div class="feed-time">8
                                    Okt, 10.05</div>
                            </div>
                        </div>
                        <div class="feed-item"><span class="feed-dot"></span>
                            <div><b>BEM Fakultas Teknik</b> mengajukan ulang LKTI Nasional<div class="feed-time">8 Okt,
                                    09.30</div>
                            </div>
                        </div>
                        <div class="feed-item"><span class="feed-dot"></span>
                            <div><b>Admin</b> menyetujui Workshop UI/UX untuk Pemula<div class="feed-time">7 Okt, 15.12
                                </div>
                            </div>
                        </div>
                        <div class="feed-item"><span class="feed-dot"></span>
                            <div><b>18 pengguna baru</b> mendaftar hari ini<div class="feed-time">Hari ini</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cols" style="margin-top:24px">
                    <div class="card col-wide" style="box-sizing:border-box">
                        <h2 class="card-title">Pendaftaran peserta per hari</h2><svg width="616" height="190"
                            viewBox="0 0 580 190" role="img" aria-label="Pendaftaran peserta per hari, puncak 28">
                            <line x1="0" y1="170" x2="580" y2="170" stroke="#E4E4EE" />
                            <rect x="16" y="110" width="24" height="60" rx="4" fill="#C9C5FF" />
                            <rect x="56" y="95" width="24" height="75" rx="4" fill="#C9C5FF" />
                            <rect x="96" y="115" width="24" height="55" rx="4" fill="#C9C5FF" />
                            <rect x="136" y="80" width="24" height="90" rx="4" fill="#C9C5FF" />
                            <rect x="176" y="60" width="24" height="110" rx="4" fill="#C9C5FF" />
                            <rect x="216" y="100" width="24" height="70" rx="4" fill="#C9C5FF" />
                            <rect x="256" y="70" width="24" height="100" rx="4" fill="#C9C5FF" />
                            <rect x="296" y="40" width="24" height="130" rx="4" fill="#C9C5FF" />
                            <rect x="336" y="75" width="24" height="95" rx="4" fill="#C9C5FF" />
                            <rect x="376" y="105" width="24" height="65" rx="4" fill="#C9C5FF" />
                            <rect x="416" y="85" width="24" height="85" rx="4" fill="#C9C5FF" />
                            <rect x="456" y="50" width="24" height="120" rx="4" fill="#C9C5FF" />
                            <rect x="496" y="65" width="24" height="105" rx="4" fill="#C9C5FF" />
                            <rect x="536" y="30" width="24" height="140" rx="4" fill="#4B34DB" />
                        </svg>
                        <p class="help">14 hari terakhir · 261 pendaftaran</p>
                    </div>
                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Status acara</h2>
                        <div class="stack-bar">
                            <div style="width:159px;background:#15803D"></div>
                            <div style="width:78px;background:#0E7490"></div>
                            <div style="width:54px;background:#8A90AB"></div>
                            <div style="width:27px;background:#B91C1C"></div>
                            <div style="width:23px;background:#1D4ED8"></div>
                            <div style="width:16px;background:#B45309"></div>
                            <div style="width:16px;background:#E8A0A0"></div>
                        </div>
                        <div class="legend-grid">
                            <div class="legend-item"><span><span class="legend-swatch"
                                        style="background:#15803D"></span>Terbit</span><b style="color:#0E1128">41</b>
                            </div>
                            <div class="legend-item"><span><span class="legend-swatch"
                                        style="background:#0E7490"></span>Selesai</span><b style="color:#0E1128">20</b>
                            </div>
                            <div class="legend-item"><span><span class="legend-swatch"
                                        style="background:#8A90AB"></span>Draf</span><b style="color:#0E1128">14</b>
                            </div>
                            <div class="legend-item"><span><span class="legend-swatch"
                                        style="background:#B91C1C"></span>Ditolak</span><b style="color:#0E1128">7</b>
                            </div>
                            <div class="legend-item"><span><span class="legend-swatch"
                                        style="background:#1D4ED8"></span>Disetujui</span><b style="color:#0E1128">6</b>
                            </div>
                            <div class="legend-item"><span><span class="legend-swatch"
                                        style="background:#B45309"></span>Menunggu tinjauan</span><b
                                    style="color:#0E1128">4</b></div>
                            <div class="legend-item"><span><span class="legend-swatch"
                                        style="background:#E8A0A0"></span>Dibatalkan</span><b
                                    style="color:#0E1128">4</b></div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>