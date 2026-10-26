<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Pengguna</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:680px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div><span class="side-label">ADMIN</span><a
                    class="side-item" href="<?= BASE_URL ?>/admin">Dasbor</a><a class="side-item" href="<?= BASE_URL ?>/admin/antrean-acara">Antrean
                    acara<span class="count-pill">4</span></a><a class="side-item side-item-active"
                    href="<?= BASE_URL ?>/admin/pengguna">Pengguna</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/penyelenggara">Penyelenggara</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/laporan">Laporan</a><a class="side-item" href="<?= BASE_URL ?>/admin/notifikasi">Notifikasi</a><span
                    class="side-item">Keluar</span>
                <div class="side-user"><b>Rahmat Hidayat</b>Administrator</div>
            </aside>
            <main class="main">
                <p class="crumb">Pengguna</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Pengguna</h1>
                        <p class="page-subtitle">1.284 akun terdaftar</p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">Ekspor daftar</span></div>
                </div>
                <div class="filter-bar">
                    <div class="input" style="flex:1;width:auto"><span class="input-placeholder">Cari nama atau
                            surel</span></div>
                    <div class="input" style="width:170px"><span>Semua peran</span><svg width="16" height="16"
                            viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></div>
                    <div class="input" style="width:180px"><span>Semua status</span><svg width="16" height="16"
                            viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></div>
                </div>
                <div class="tbl-card">
                    <div class="tbl-head">
                        <div class="tbl-cell" style="width:220px">NAMA</div>
                        <div class="tbl-cell" style="width:250px">SUREL</div>
                        <div class="tbl-cell" style="width:160px">PERAN</div>
                        <div class="tbl-cell" style="width:130px">TERDAFTAR</div>
                        <div class="tbl-cell" style="width:150px">STATUS AKUN</div>
                        <div class="tbl-cell" style="width:186px"></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:220px"><b>Alya Rahmawati</b></div>
                        <div class="tbl-cell" style="width:250px">alya.rahmawati@email.com</div>
                        <div class="tbl-cell" style="width:160px"><span class="badge badge-purple"><span
                                    class="badge-dot"></span>Peserta</span></div>
                        <div class="tbl-cell" style="width:130px">1 Sep 2026</div>
                        <div class="tbl-cell" style="width:150px"><span class="badge badge-success"><span
                                    class="badge-dot"></span>Aktif</span></div>
                        <div class="tbl-cell tbl-right" style="width:186px"><span
                                class="button secondary-button small-button">Detail</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:220px"><b>Citra Lestari</b></div>
                        <div class="tbl-cell" style="width:250px">citra.l@email.com</div>
                        <div class="tbl-cell" style="width:160px"><span class="badge badge-purple"><span
                                    class="badge-dot"></span>Peserta</span></div>
                        <div class="tbl-cell" style="width:130px">3 Sep 2026</div>
                        <div class="tbl-cell" style="width:150px"><span class="badge badge-success"><span
                                    class="badge-dot"></span>Aktif</span></div>
                        <div class="tbl-cell tbl-right" style="width:186px"><span
                                class="button secondary-button small-button">Detail</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:220px"><b>Dimas Prakoso</b></div>
                        <div class="tbl-cell" style="width:250px">dimas.p@email.com</div>
                        <div class="tbl-cell" style="width:160px"><span class="badge badge-info"><span
                                    class="badge-dot"></span>Penyelenggara</span></div>
                        <div class="tbl-cell" style="width:130px">12 Jan 2026</div>
                        <div class="tbl-cell" style="width:150px"><span class="badge badge-success"><span
                                    class="badge-dot"></span>Aktif</span></div>
                        <div class="tbl-cell tbl-right" style="width:186px"><span
                                class="button secondary-button small-button">Detail</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:220px"><b>Eka Wulandari</b></div>
                        <div class="tbl-cell" style="width:250px">eka.w@email.com</div>
                        <div class="tbl-cell" style="width:160px"><span class="badge badge-purple"><span
                                    class="badge-dot"></span>Peserta</span></div>
                        <div class="tbl-cell" style="width:130px">14 Sep 2026</div>
                        <div class="tbl-cell" style="width:150px"><span class="badge badge-error"><span
                                    class="badge-dot"></span>Ditangguhkan</span></div>
                        <div class="tbl-cell tbl-right" style="width:186px"><span
                                class="button secondary-button small-button">Detail</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:220px"><b>Rahmat Hidayat</b></div>
                        <div class="tbl-cell" style="width:250px">rahmat.h@tixora.id</div>
                        <div class="tbl-cell" style="width:160px"><span class="badge badge-neutral"><span
                                    class="badge-dot"></span>Admin</span></div>
                        <div class="tbl-cell" style="width:130px">2 Jan 2026</div>
                        <div class="tbl-cell" style="width:150px"><span class="badge badge-success"><span
                                    class="badge-dot"></span>Aktif</span></div>
                        <div class="tbl-cell tbl-right" style="width:186px"><span
                                class="button secondary-button small-button">Detail</span></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:220px"><b>Gilang Saputra</b></div>
                        <div class="tbl-cell" style="width:250px">gilang.s@email.com</div>
                        <div class="tbl-cell" style="width:160px"><span class="badge badge-info"><span
                                    class="badge-dot"></span>Penyelenggara</span></div>
                        <div class="tbl-cell" style="width:130px">20 Mar 2026</div>
                        <div class="tbl-cell" style="width:150px"><span class="badge badge-success"><span
                                    class="badge-dot"></span>Aktif</span></div>
                        <div class="tbl-cell tbl-right" style="width:186px"><span
                                class="button secondary-button small-button">Detail</span></div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>