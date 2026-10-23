<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Penyelenggara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:819px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                        <path
                            d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z"
                            fill="#4B34DB" />
                        <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round"
                            fill="none" />
                    </svg><span class="logo-text">tixora</span></div><span class="side-label">ADMIN</span><a
                    class="side-item" href="<?= BASE_URL ?>/admin">Dasbor</a><a class="side-item" href="<?= BASE_URL ?>/admin/antrean-acara">Antrean
                    acara<span class="count-pill">4</span></a><a class="side-item" href="<?= BASE_URL ?>/admin/pengguna">Pengguna</a><a
                    class="side-item side-item-active" href="<?= BASE_URL ?>/admin/penyelenggara">Penyelenggara</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/laporan">Laporan</a><a class="side-item" href="<?= BASE_URL ?>/admin/notifikasi">Notifikasi</a><span
                    class="side-item">Keluar</span>
                <div class="side-user"><b>Rahmat Hidayat</b>Administrator</div>
            </aside>
            <main class="main">
                <p class="crumb">Penyelenggara</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Penyelenggara</h1>
                        <p class="page-subtitle">28 akun penyelenggara</p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">Ekspor daftar</span></div>
                </div>
                <div class="cols">
                    <div style="width:424px">
                        <div class="tbl-card" style="width:424px;box-sizing:border-box">
                            <div class="tbl-head">
                                <div class="tbl-cell" style="width:176px">PENYELENGGARA</div>
                                <div class="tbl-cell" style="width:56px">ACARA</div>
                                <div class="tbl-cell" style="width:160px">STATUS</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:176px"><b>HMTI FILKOM</b></div>
                                <div class="tbl-cell" style="width:56px">12</div>
                                <div class="tbl-cell" style="width:160px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Aktif</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:176px"><b>BEM Fakultas Teknik</b></div>
                                <div class="tbl-cell" style="width:56px">7</div>
                                <div class="tbl-cell" style="width:160px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Aktif</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:176px"><b>UKM Seni dan Musik</b></div>
                                <div class="tbl-cell" style="width:56px">5</div>
                                <div class="tbl-cell" style="width:160px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Aktif</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:176px"><b>Komunitas Data Malang</b></div>
                                <div class="tbl-cell" style="width:56px">3</div>
                                <div class="tbl-cell" style="width:160px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Aktif</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:176px"><b>Klub Fotografi</b></div>
                                <div class="tbl-cell" style="width:56px">2</div>
                                <div class="tbl-cell" style="width:160px"><span class="badge badge-error"><span
                                            class="badge-dot"></span>Ditangguhkan</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="stack gap-24" style="width:680px">
                        <div class="card">
                            <div class="row-between" style="margin-bottom:16px">
                                <h2 class="card-title" style="margin:0">HMTI FILKOM</h2><span
                                    class="badge badge-success"><span class="badge-dot"></span>Aktif</span>
                            </div>
                            <dl class="definition">
                                <div>
                                    <dt>PENANGGUNG JAWAB</dt>
                                    <dd>Dimas Prakoso</dd>
                                </div>
                                <div>
                                    <dt>KONTAK</dt>
                                    <dd>dimas.p@email.com · 0812-5550-0101</dd>
                                </div>
                                <div>
                                    <dt>BERGABUNG</dt>
                                    <dd>12 Jan 2026</dd>
                                </div>
                                <div>
                                    <dt>RIWAYAT PERSETUJUAN</dt>
                                    <dd>9 disetujui · 2 ditolak · 1 menunggu</dd>
                                </div>
                            </dl>
                            <div class="row" style="margin-top:20px"><span class="button secondary-button">Kirim
                                    surel</span><span class="button danger-outline-button">Tangguhkan
                                    penyelenggara</span></div>
                        </div>
                        <div class="card" style="padding:20px 16px 8px;width:680px;box-sizing:border-box">
                            <h2 class="card-title" style="padding:0 8px">Acara terkait</h2>
                            <div style="padding:0">
                                <div class="tbl-head">
                                    <div class="tbl-cell" style="width:320px">ACARA</div>
                                    <div class="tbl-cell" style="width:170px">STATUS</div>
                                    <div class="tbl-cell" style="width:158px">TANGGAL</div>
                                </div>
                                <div class="tbl-row">
                                    <div class="tbl-cell" style="width:320px"><b>Konferensi Pendidikan Digital Jawa
                                            Timur</b></div>
                                    <div class="tbl-cell" style="width:170px"><span class="badge badge-warning"><span
                                                class="badge-dot"></span>Menunggu tinjauan</span></div>
                                    <div class="tbl-cell" style="width:158px">12–13 Nov</div>
                                </div>
                                <div class="tbl-row">
                                    <div class="tbl-cell" style="width:320px"><b>Seminar Nasional Teknologi Informatika
                                            2026</b></div>
                                    <div class="tbl-cell" style="width:170px"><span class="badge badge-success"><span
                                                class="badge-dot"></span>Terbit</span></div>
                                    <div class="tbl-cell" style="width:158px">24 Okt</div>
                                </div>
                                <div class="tbl-row">
                                    <div class="tbl-cell" style="width:320px"><b>Workshop UI/UX untuk Pemula</b></div>
                                    <div class="tbl-cell" style="width:170px"><span class="badge badge-info"><span
                                                class="badge-dot"></span>Disetujui</span></div>
                                    <div class="tbl-cell" style="width:158px">1 Nov</div>
                                </div>
                                <div class="tbl-row">
                                    <div class="tbl-cell" style="width:320px"><b>Lomba Esai Nasional Mahasiswa</b></div>
                                    <div class="tbl-cell" style="width:170px"><span class="badge badge-error"><span
                                                class="badge-dot"></span>Ditolak</span></div>
                                    <div class="tbl-cell" style="width:158px">20 Nov</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>