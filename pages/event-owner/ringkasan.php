<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Ringkasan Acara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:739px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div>
                <span class="side-item">Dasbor</span><span class="side-item side-item-active">Acara saya</span><span
                    class="side-item">Buat acara</span><span class="side-item">Pengaturan</span><span
                    class="side-item">Keluar</span>
                <div class="side-user"><b>Dimas Prakoso</b>HMTI Fakultas Ilmu Komputer</div>
            </aside>
            <main class="main">
                <p class="crumb">Acara saya / SENTEK 2026</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Seminar Nasional Teknologi Informatika 2026</h1>
                        <p class="page-subtitle"><span class="badge badge-success"><span
                                    class="badge-dot"></span>Terbit</span> &nbsp;<span class="slate"
                                style="font-size:14px">Sab, 24 Okt 2026 · Auditorium Utama, Malang</span></p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">Lihat halaman publik</span><span
                            class="button primary-button">Edit informasi</span></div>
                </div>
                <div class="tabs" style="margin-bottom:24px"><span class="tab tab-active">Ringkasan</span><span
                        class="tab">Tiket</span><span class="tab">Rundown</span><span class="tab">Peserta</span><span
                        class="tab">RAB</span><span class="tab">Panitia</span><span class="tab">Analitik</span><span
                        class="tab">Pengajuan</span></div>
                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">PENDAFTAR</div>
                        <div class="metric-value" style="">196</div>
                        <div class="metric-note">dari 250 kuota</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">PENDAPATAN TIKET</div>
                        <div class="metric-value" style="">Rp14.750.000</div>
                        <div class="metric-note">78 Umum · 88 Mhs · 30 VIP</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">SISA KAPASITAS</div>
                        <div class="metric-value" style="">54</div>
                        <div class="metric-note">78% terisi</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">HARI MENUJU ACARA</div>
                        <div class="metric-value" style="">14</div>
                        <div class="metric-note">Pendaftaran ditutup 22 Okt</div>
                    </div>
                </div>
                <div class="cols">
                    <div class="col-wide card" style="padding:20px 16px 8px;box-sizing:border-box">
                        <h2 class="card-title" style="padding:0 8px">Pendaftar terbaru</h2>
                        <div style="padding:0">
                            <div class="tbl-head">
                                <div class="tbl-cell" style="width:200px">NAMA</div>
                                <div class="tbl-cell" style="width:110px">TIKET</div>
                                <div class="tbl-cell" style="width:170px">WAKTU</div>
                                <div class="tbl-cell" style="width:168px">STATUS</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:200px"><b>Alya Rahmawati</b></div>
                                <div class="tbl-cell" style="width:110px">Umum</div>
                                <div class="tbl-cell" style="width:170px">Hari ini, 09.14</div>
                                <div class="tbl-cell" style="width:168px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Sah</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:200px"><b>Bima Aditya</b></div>
                                <div class="tbl-cell" style="width:110px">Mahasiswa</div>
                                <div class="tbl-cell" style="width:170px">Hari ini, 08.50</div>
                                <div class="tbl-cell" style="width:168px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Sah</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:200px"><b>Citra Lestari</b></div>
                                <div class="tbl-cell" style="width:110px">Umum</div>
                                <div class="tbl-cell" style="width:170px">Kemarin, 20.12</div>
                                <div class="tbl-cell" style="width:168px"><span class="badge badge-warning"><span
                                            class="badge-dot"></span>Menunggu pembayaran</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:200px"><b>Deni Kurniawan</b></div>
                                <div class="tbl-cell" style="width:110px">VIP</div>
                                <div class="tbl-cell" style="width:170px">Kemarin, 14.03</div>
                                <div class="tbl-cell" style="width:168px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Sah</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Ketersediaan tiket</h2>
                        <div style="margin-bottom:16px">
                            <div class="legend-row"><b>Umum</b><span class="slate">78 / 120 · sisa 42</span></div>
                            <div class="progress">
                                <div class="progress-fill" style="width:65%"></div>
                            </div>
                        </div>
                        <div style="margin-bottom:16px">
                            <div class="legend-row"><b>Mahasiswa</b><span class="slate">88 / 100 · sisa 12</span></div>
                            <div class="progress">
                                <div class="progress-fill" style="width:88%"></div>
                            </div>
                        </div>
                        <div style="margin-bottom:16px">
                            <div class="legend-row"><b>VIP</b><span class="slate">30 / 30 · habis</span></div>
                            <div class="progress">
                                <div class="progress-fill" style="width:100%"></div>
                            </div>
                        </div><span class="button secondary-button full-width">Kelola tiket</span>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>