<?php
require_once __DIR__ . '/../_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — RAB</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:976px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div><span class="side-item">Dasbor</span><span
                    class="side-item side-item-active">Acara saya</span><span class="side-item">Buat acara</span><span
                    class="side-item">Pengaturan</span><span class="side-item">Keluar</span>
                <div class="side-user"><b>Dimas Prakoso</b>HMTI Fakultas Ilmu Komputer</div>
            </aside>
            <main class="main">
                <p class="crumb">Acara saya / SENTEK 2026 / RAB</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Rencana anggaran biaya</h1>
                        <p class="page-subtitle">Bandingkan rencana dengan realisasi pengeluaran</p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">Ekspor RAB</span><span
                            class="button primary-button">+ Tambah pengeluaran</span></div>
                </div>
                <div class="tabs" style="margin-bottom:24px"><span class="tab">Ringkasan</span><span
                        class="tab">Tiket</span><span class="tab">Rundown</span><span class="tab">Peserta</span><span
                        class="tab tab-active">RAB</span><span class="tab">Panitia</span><span
                        class="tab">Analitik</span><span class="tab">Pengajuan</span></div>
                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">RENCANA</div>
                        <div class="metric-value" style="color:#3C29B8">Rp38.500.000</div>
                        <div class="metric-note">Total anggaran disetujui</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">REALISASI</div>
                        <div class="metric-value" style="">Rp30.000.000</div>
                        <div class="metric-note">78% dari rencana</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">SISA ANGGARAN</div>
                        <div class="metric-value" style="color:#15803D">Rp8.500.000</div>
                        <div class="metric-note">1 pos melebihi rencana</div>
                    </div>
                </div>
                <div style="margin-bottom:16px" class="alert alert-warning">Publikasi &amp; dokumentasi melebihi rencana
                    Rp600.000.</div>
                <div class="tbl-card">
                    <div class="tbl-head">
                        <div class="tbl-cell" style="width:400px">KATEGORI</div>
                        <div class="tbl-cell" style="width:200px"><span class="badge badge-purple"><span
                                    class="badge-dot"></span>Rencana</span></div>
                        <div class="tbl-cell" style="width:300px"><span class="badge"
                                style="background:#0E1128;color:#FFFFFF"><span class="badge-dot"></span>Realisasi</span>
                        </div>
                        <div class="tbl-cell" style="width:196px;text-align:right">SELISIH</div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:400px"><b>Konsumsi</b></div>
                        <div class="tbl-cell" style="width:200px">Rp12.000.000</div>
                        <div class="tbl-cell" style="width:300px"><b>Rp8.400.000</b>
                            <div style="margin-top:6px">
                                <div class="progress">
                                    <div class="progress-fill" style="width:70%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="tbl-cell" style="width:196px;text-align:right"><b
                                style="color:#15803D">+Rp3.600.000</b></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:400px"><b>Sewa tempat dan perlengkapan</b></div>
                        <div class="tbl-cell" style="width:200px">Rp9.000.000</div>
                        <div class="tbl-cell" style="width:300px"><b>Rp9.000.000</b>
                            <div style="margin-top:6px">
                                <div class="progress">
                                    <div class="progress-fill" style="width:100%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="tbl-cell" style="width:196px;text-align:right"><b style="color:#15803D">+Rp0</b>
                        </div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:400px"><b>Honor pembicara</b></div>
                        <div class="tbl-cell" style="width:200px">Rp7.500.000</div>
                        <div class="tbl-cell" style="width:300px"><b>Rp5.000.000</b>
                            <div style="margin-top:6px">
                                <div class="progress">
                                    <div class="progress-fill" style="width:67%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="tbl-cell" style="width:196px;text-align:right"><b
                                style="color:#15803D">+Rp2.500.000</b></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:400px"><b>Publikasi &amp; dokumentasi</b></div>
                        <div class="tbl-cell" style="width:200px">Rp4.000.000</div>
                        <div class="tbl-cell" style="width:300px"><b>Rp4.600.000</b>
                            <div style="margin-top:6px">
                                <div class="progress">
                                    <div class="progress-fill progress-over" style="width:100%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="tbl-cell" style="width:196px;text-align:right"><b
                                style="color:#B91C1C">−Rp600.000</b></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:400px"><b>Transportasi dan akomodasi</b></div>
                        <div class="tbl-cell" style="width:200px">Rp3.500.000</div>
                        <div class="tbl-cell" style="width:300px"><b>Rp2.100.000</b>
                            <div style="margin-top:6px">
                                <div class="progress">
                                    <div class="progress-fill" style="width:60%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="tbl-cell" style="width:196px;text-align:right"><b
                                style="color:#15803D">+Rp1.400.000</b></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:400px"><b>Lain-lain</b></div>
                        <div class="tbl-cell" style="width:200px">Rp2.500.000</div>
                        <div class="tbl-cell" style="width:300px"><b>Rp900.000</b>
                            <div style="margin-top:6px">
                                <div class="progress">
                                    <div class="progress-fill" style="width:36%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="tbl-cell" style="width:196px;text-align:right"><b
                                style="color:#15803D">+Rp1.600.000</b></div>
                    </div>
                    <div class="tbl-row">
                        <div class="tbl-cell" style="width:400px"><b>Total</b></div>
                        <div class="tbl-cell" style="width:200px"><b>Rp38.500.000</b></div>
                        <div class="tbl-cell" style="width:300px"><b>Rp30.000.000</b></div>
                        <div class="tbl-cell" style="width:196px;text-align:right"><b
                                style="color:#15803D">+Rp8.500.000</b></div>
                    </div>
                </div>
                <p class="help" style="margin-top:12px">Selisih = rencana − realisasi. Hijau: di bawah rencana. Merah:
                    melebihi rencana.</p>
            </main>
        </div>
    </div>
</body>

</html>