<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Antrean Acara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:1148px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                        <path
                            d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z"
                            fill="#4B34DB" />
                        <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round"
                            fill="none" />
                    </svg><span class="logo-text">tixora</span></div><span class="side-label">ADMIN</span><a
                    class="side-item" href="<?= BASE_URL ?>/admin">Dasbor</a><a class="side-item side-item-active"
                    href="<?= BASE_URL ?>/admin/antrean-acara">Antrean acara<span class="count-pill">4</span></a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/pengguna">Pengguna</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/penyelenggara">Penyelenggara</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/laporan">Laporan</a><a class="side-item" href="<?= BASE_URL ?>/admin/notifikasi">Notifikasi</a><span
                    class="side-item">Keluar</span>
                <div class="side-user"><b>Rahmat Hidayat</b>Administrator</div>
            </aside>
            <main class="main">
                <p class="crumb">Antrean acara</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Antrean acara</h1>
                        <p class="page-subtitle">Tinjau pengajuan dan pantau status acara</p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">Ekspor daftar</span></div>
                </div>
                <div class="filter-bar">
                    <div class="input" style="flex:1;width:auto"><span class="input-placeholder">Cari acara atau
                            penyelenggara</span></div>
                    <div class="input" style="width:170px"><span>Semua kategori</span><svg width="16" height="16"
                            viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></div>
                    <div class="input" style="width:200px"><span>Pengajuan terlama</span><svg width="16" height="16"
                            viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></div>
                </div>
                <div class="chip-row"><span class="chip chip-active">Menunggu tinjauan 4</span><span
                        class="chip">Disetujui 6</span><span class="chip">Ditolak 7</span><span class="chip">Terbit
                        41</span><span class="chip">Dibatalkan 4</span><span class="chip">Semua 62</span></div>
                <div class="tbl-card">
                    <div class="tbl-head">
                        <div class="tbl-cell" style="width:280px">ACARA</div>
                        <div class="tbl-cell" style="width:160px">PENYELENGGARA</div>
                        <div class="tbl-cell" style="width:90px">DIAJUKAN</div>
                        <div class="tbl-cell" style="width:110px">TANGGAL ACARA</div>
                        <div class="tbl-cell" style="width:100px">KATEGORI</div>
                        <div class="tbl-cell" style="width:230px">STATUS</div>
                        <div class="tbl-cell" style="width:126px"></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:280px"><b>Konferensi Pendidikan Digital Jawa Timur</b></div>
                        <div class="tbl-cell" style="width:160px">HMTI FILKOM</div>
                        <div class="tbl-cell" style="width:90px">8 Okt</div>
                        <div class="tbl-cell" style="width:110px">12–13 Nov</div>
                        <div class="tbl-cell" style="width:100px">Konferensi</div>
                        <div class="tbl-cell" style="width:230px"><span class="badge badge-warning"><span
                                    class="badge-dot"></span>Menunggu tinjauan</span></div>
                        <div class="tbl-cell tbl-right" style="width:126px"><span
                                class="button secondary-button small-button">Tinjau</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:280px"><b>Lomba Karya Tulis Ilmiah Nasional</b></div>
                        <div class="tbl-cell" style="width:160px">BEM Fakultas Teknik</div>
                        <div class="tbl-cell" style="width:90px">8 Okt</div>
                        <div class="tbl-cell" style="width:110px">5 Des</div>
                        <div class="tbl-cell" style="width:100px">Kompetisi</div>
                        <div class="tbl-cell" style="width:230px"><span class="badge badge-warning"><span
                                    class="badge-dot"></span>Menunggu tinjauan</span> <span
                                class="badge badge-purple"><span class="badge-dot"></span>Revisi</span></div>
                        <div class="tbl-cell tbl-right" style="width:126px"><span
                                class="button secondary-button small-button">Tinjau</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:280px"><b>Festival Musik Kampus 2026</b></div>
                        <div class="tbl-cell" style="width:160px">UKM Seni dan Musik</div>
                        <div class="tbl-cell" style="width:90px">7 Okt</div>
                        <div class="tbl-cell" style="width:110px">28 Nov</div>
                        <div class="tbl-cell" style="width:100px">Hiburan</div>
                        <div class="tbl-cell" style="width:230px"><span class="badge badge-warning"><span
                                    class="badge-dot"></span>Menunggu tinjauan</span></div>
                        <div class="tbl-cell tbl-right" style="width:126px"><span
                                class="button secondary-button small-button">Tinjau</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:280px"><b>Workshop Data Science Dasar</b></div>
                        <div class="tbl-cell" style="width:160px">Komunitas Data Malang</div>
                        <div class="tbl-cell" style="width:90px">6 Okt</div>
                        <div class="tbl-cell" style="width:110px">14 Nov</div>
                        <div class="tbl-cell" style="width:100px">Workshop</div>
                        <div class="tbl-cell" style="width:230px"><span class="badge badge-warning"><span
                                    class="badge-dot"></span>Menunggu tinjauan</span></div>
                        <div class="tbl-cell tbl-right" style="width:126px"><span
                                class="button secondary-button small-button">Tinjau</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:280px"><b>Workshop UI/UX untuk Pemula</b></div>
                        <div class="tbl-cell" style="width:160px">HMTI FILKOM</div>
                        <div class="tbl-cell" style="width:90px">5 Okt</div>
                        <div class="tbl-cell" style="width:110px">1 Nov</div>
                        <div class="tbl-cell" style="width:100px">Workshop</div>
                        <div class="tbl-cell" style="width:230px"><span class="badge badge-info"><span
                                    class="badge-dot"></span>Disetujui</span></div>
                        <div class="tbl-cell tbl-right" style="width:126px"><span
                                class="button secondary-button small-button">Lihat</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:280px"><b>Seminar Nasional Teknologi Informatika 2026</b>
                        </div>
                        <div class="tbl-cell" style="width:160px">HMTI FILKOM</div>
                        <div class="tbl-cell" style="width:90px">1 Sep</div>
                        <div class="tbl-cell" style="width:110px">24 Okt</div>
                        <div class="tbl-cell" style="width:100px">Seminar</div>
                        <div class="tbl-cell" style="width:230px"><span class="badge badge-success"><span
                                    class="badge-dot"></span>Terbit</span></div>
                        <div class="tbl-cell tbl-right" style="width:126px"><span
                                class="button secondary-button small-button">Lihat</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:280px"><b>Lomba Esai Nasional Mahasiswa</b></div>
                        <div class="tbl-cell" style="width:160px">HMTI FILKOM</div>
                        <div class="tbl-cell" style="width:90px">2 Okt</div>
                        <div class="tbl-cell" style="width:110px">20 Nov</div>
                        <div class="tbl-cell" style="width:100px">Kompetisi</div>
                        <div class="tbl-cell" style="width:230px"><span class="badge badge-error"><span
                                    class="badge-dot"></span>Ditolak</span></div>
                        <div class="tbl-cell tbl-right" style="width:126px"><span
                                class="button secondary-button small-button">Lihat</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:280px"><b>Workshop Fotografi Dasar</b></div>
                        <div class="tbl-cell" style="width:160px">Klub Fotografi</div>
                        <div class="tbl-cell" style="width:90px">10 Sep</div>
                        <div class="tbl-cell" style="width:110px">25 Okt</div>
                        <div class="tbl-cell" style="width:100px">Workshop</div>
                        <div class="tbl-cell" style="width:230px"><span class="badge badge-error"><span
                                    class="badge-dot"></span>Dibatalkan</span></div>
                        <div class="tbl-cell tbl-right" style="width:126px"><span
                                class="button secondary-button small-button">Lihat</span></div>
                    </div>
                </div>
                <div class="cols" style="margin-top:24px">
                    <div class="card state-card" style="width:552px;box-sizing:border-box">
                        <div class="state-icon" style="background:#E8F6EC;color:#15803D"><svg width="22" height="22"
                                viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg></div>
                        <h3 class="state-title">Tidak ada pengajuan menunggu</h3>
                        <p class="state-text">Semua pengajuan sudah ditinjau. Pengajuan baru akan muncul di sini.</p>
                    </div>
                    <div class="card" style="width:552px;box-sizing:border-box">
                        <h2 class="card-title">Memuat</h2>
                        <div class="skeleton" style="height:18px"></div>
                        <div class="skeleton" style="height:18px;margin-top:10px"></div>
                        <div class="skeleton" style="height:18px;width:70%;margin-top:10px"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>