<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Riwayat Moderasi</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:862px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div><span class="side-label">ADMIN</span><a
                    class="side-item" href="<?= BASE_URL ?>/admin">Dasbor</a><a class="side-item side-item-active"
                    href="<?= BASE_URL ?>/admin/antrean-acara">Antrean acara<span class="count-pill">4</span></a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/pengguna">Pengguna</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/penyelenggara">Penyelenggara</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/laporan">Laporan</a><a class="side-item" href="<?= BASE_URL ?>/admin/notifikasi">Notifikasi</a><span
                    class="side-item">Keluar</span>
                <div class="side-user"><b>Rahmat Hidayat</b>Administrator</div>
            </aside>
            <main class="main">
                <p class="crumb">Antrean acara / Riwayat moderasi</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Riwayat moderasi</h1>
                        <p class="page-subtitle">Keputusan tinjauan acara oleh admin</p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">Ekspor CSV</span></div>
                </div>
                <div class="filter-bar">
                    <div class="input" style="flex:1;width:auto"><span class="input-placeholder">Cari acara atau
                            penyelenggara</span></div>
                    <div class="input" style="width:170px"><span>Semua keputusan</span><svg width="16" height="16"
                            viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></div>
                    <div class="input" style="width:170px"><span>Semua admin</span><svg width="16" height="16"
                            viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></div>
                    <div class="input" style="width:200px"><span>1 Sep – 10 Okt 2026</span><svg width="16" height="16"
                            viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></div>
                </div>
                <div class="tbl-card">
                    <div class="tbl-head">
                        <div class="tbl-cell" style="width:130px">WAKTU</div>
                        <div class="tbl-cell" style="width:250px">ACARA</div>
                        <div class="tbl-cell" style="width:160px">PENYELENGGARA</div>
                        <div class="tbl-cell" style="width:120px">KEPUTUSAN</div>
                        <div class="tbl-cell" style="width:120px">ADMIN</div>
                        <div class="tbl-cell" style="width:316px">CATATAN</div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:130px"><span class="muted">10 Okt, 09.40</span></div>
                        <div class="tbl-cell" style="width:250px"><b>Konferensi Pendidikan Digital Jawa Timur</b></div>
                        <div class="tbl-cell" style="width:160px">HMTI FILKOM</div>
                        <div class="tbl-cell" style="width:120px"><span class="badge badge-info"><span
                                    class="badge-dot"></span>Disetujui</span></div>
                        <div class="tbl-cell" style="width:120px">Rahmat H.</div>
                        <div class="tbl-cell" style="width:316px">—</div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:130px"><span class="muted">7 Okt, 15.12</span></div>
                        <div class="tbl-cell" style="width:250px"><b>Workshop UI/UX untuk Pemula</b></div>
                        <div class="tbl-cell" style="width:160px">HMTI FILKOM</div>
                        <div class="tbl-cell" style="width:120px"><span class="badge badge-info"><span
                                    class="badge-dot"></span>Disetujui</span></div>
                        <div class="tbl-cell" style="width:120px">Rahmat H.</div>
                        <div class="tbl-cell" style="width:316px">—</div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:130px"><span class="muted">5 Okt, 16.40</span></div>
                        <div class="tbl-cell" style="width:250px"><b>Lomba Esai Nasional Mahasiswa</b></div>
                        <div class="tbl-cell" style="width:160px">HMTI FILKOM</div>
                        <div class="tbl-cell" style="width:120px"><span class="badge badge-error"><span
                                    class="badge-dot"></span>Ditolak</span></div>
                        <div class="tbl-cell" style="width:120px">Rahmat H.</div>
                        <div class="tbl-cell" style="width:316px">Rincian anggaran belum dilampirkan; kriteria penilaian
                            belum dijelaskan.</div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:130px"><span class="muted">3 Okt, 10.05</span></div>
                        <div class="tbl-cell" style="width:250px"><b>Festival Musik Kampus 2025</b></div>
                        <div class="tbl-cell" style="width:160px">UKM Seni dan Musik</div>
                        <div class="tbl-cell" style="width:120px"><span class="badge badge-info"><span
                                    class="badge-dot"></span>Disetujui</span></div>
                        <div class="tbl-cell" style="width:120px">Sinta W.</div>
                        <div class="tbl-cell" style="width:316px">—</div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:130px"><span class="muted">1 Okt, 16.10</span></div>
                        <div class="tbl-cell" style="width:250px"><b>Lomba Karya Tulis Ilmiah Nasional</b></div>
                        <div class="tbl-cell" style="width:160px">BEM Fakultas Teknik</div>
                        <div class="tbl-cell" style="width:120px"><span class="badge badge-error"><span
                                    class="badge-dot"></span>Ditolak</span></div>
                        <div class="tbl-cell" style="width:120px">Rahmat H.</div>
                        <div class="tbl-cell" style="width:316px">Rincian anggaran belum dilampirkan.</div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:130px"><span class="muted">28 Sep, 09.15</span></div>
                        <div class="tbl-cell" style="width:250px"><b>Workshop Data Science Dasar</b></div>
                        <div class="tbl-cell" style="width:160px">Komunitas Data Malang</div>
                        <div class="tbl-cell" style="width:120px"><span class="badge badge-info"><span
                                    class="badge-dot"></span>Disetujui</span></div>
                        <div class="tbl-cell" style="width:120px">Sinta W.</div>
                        <div class="tbl-cell" style="width:316px">—</div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:130px"><span class="muted">25 Sep, 14.30</span></div>
                        <div class="tbl-cell" style="width:250px"><b>Workshop Fotografi Dasar</b></div>
                        <div class="tbl-cell" style="width:160px">Klub Fotografi</div>
                        <div class="tbl-cell" style="width:120px"><span class="badge badge-info"><span
                                    class="badge-dot"></span>Disetujui</span></div>
                        <div class="tbl-cell" style="width:120px">Rahmat H.</div>
                        <div class="tbl-cell" style="width:316px">—</div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:130px"><span class="muted">20 Sep, 11.00</span></div>
                        <div class="tbl-cell" style="width:250px"><b>Pelatihan Desain Grafis</b></div>
                        <div class="tbl-cell" style="width:160px">UKM Seni dan Musik</div>
                        <div class="tbl-cell" style="width:120px"><span class="badge badge-error"><span
                                    class="badge-dot"></span>Ditolak</span></div>
                        <div class="tbl-cell" style="width:120px">Sinta W.</div>
                        <div class="tbl-cell" style="width:316px">Poster belum diunggah.</div>
                    </div>
                </div>
                <div class="row-between" style="padding:12px 16px;font-size:14px;color:#4B5170"><span>1–8 dari 62
                        keputusan</span><span class="row"><span
                            class="button secondary-button small-button">Sebelumnya</span><span
                            class="button secondary-button small-button">Berikutnya</span></span></div>
            </main>
        </div>
    </div>
</body>

</html>