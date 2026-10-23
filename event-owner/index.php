<?php
require_once __DIR__ . '/_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Dasbor</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:808px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                        <path
                            d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z"
                            fill="#4B34DB" />
                        <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round"
                            fill="none" />
                    </svg><span class="logo-text">tixora</span></div><span
                    class="side-item side-item-active">Dasbor</span><span class="side-item">Acara saya</span><span
                    class="side-item">Buat acara</span><span class="side-item">Pengaturan</span><span
                    class="side-item">Keluar</span>
                <div class="side-user"><b>Dimas Prakoso</b>HMTI Fakultas Ilmu Komputer</div>
            </aside>
            <main class="main">
                <p class="crumb">Dasbor</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Halo, Dimas</h1>
                        <p class="page-subtitle">Ringkasan acara HMTI Fakultas Ilmu Komputer</p>
                    </div>
                    <div class="head-actions"><span class="button primary-button button-large">+ Buat acara</span></div>
                </div>
                <div style="margin-bottom:24px" class="alert alert-error">1 acara perlu revisi: Lomba Esai Nasional
                    Mahasiswa. <b>Buka</b></div>
                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">TOTAL ACARA</div>
                        <div class="metric-value" style="">12</div>
                        <div class="metric-note">3 draf · 1 menunggu</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">ACARA TERBIT</div>
                        <div class="metric-value" style="">3</div>
                        <div class="metric-note">2 berlangsung bulan ini</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">TIKET TERJUAL</div>
                        <div class="metric-value" style="">486</div>
                        <div class="metric-note">+38 minggu ini</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">PENDAFTAR</div>
                        <div class="metric-value" style="">541</div>
                        <div class="metric-note">Termasuk acara gratis</div>
                    </div>
                </div>
                <div class="cols">
                    <div class="col-wide tbl-card" style="padding:20px 16px 8px;width:680px;box-sizing:border-box">
                        <h2 class="card-title" style="padding:0 8px">Acara mendatang</h2>
                        <div style="box-shadow:none;padding:0">
                            <div class="tbl-head">
                                <div class="tbl-cell" style="width:360px">ACARA</div>
                                <div class="tbl-cell" style="width:150px">STATUS</div>
                                <div class="tbl-cell" style="width:170px">TIKET</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:360px"><b>Seminar Nasional Teknologi Informatika
                                        2026</b>
                                    <div class="cell-sub">Sab, 24 Okt 2026 · Auditorium Utama</div>
                                </div>
                                <div class="tbl-cell" style="width:150px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Terbit</span></div>
                                <div class="tbl-cell" style="width:170px">
                                    <div class="progress">
                                        <div class="progress-fill" style="width:78%"></div>
                                    </div>
                                    <div class="cell-sub" style="font-size:12px;margin-top:4px">196 / 250 tiket</div>
                                </div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:360px"><b>Kuliah Umum Kewirausahaan</b>
                                    <div class="cell-sub">Sab, 17 Okt 2026 · Aula Fakultas Ekonomi</div>
                                </div>
                                <div class="tbl-cell" style="width:150px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Terbit</span></div>
                                <div class="tbl-cell" style="width:170px">
                                    <div class="progress">
                                        <div class="progress-fill" style="width:94%"></div>
                                    </div>
                                    <div class="cell-sub" style="font-size:12px;margin-top:4px">94 / 100 tiket</div>
                                </div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:360px"><b>Konferensi Pendidikan Digital Jawa
                                        Timur</b>
                                    <div class="cell-sub">12–13 Nov 2026 · Gedung Serbaguna</div>
                                </div>
                                <div class="tbl-cell" style="width:150px"><span class="badge badge-warning"><span
                                            class="badge-dot"></span>Menunggu tinjauan</span></div>
                                <div class="tbl-cell" style="width:170px"><span class="muted"
                                        style="font-size:14px">Belum dijual</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Aktivitas terbaru</h2>
                        <div class="feed-item"><span class="feed-dot"></span>
                            <div><b>12 pendaftaran baru</b> untuk SENTEK 2026<div class="feed-time">Hari ini, 09.14
                                </div>
                            </div>
                        </div>
                        <div class="feed-item"><span class="feed-dot"></span>
                            <div><b>Admin menolak</b> Lomba Esai Nasional Mahasiswa — <b class="link-text">lihat
                                    alasan</b>
                                <div class="feed-time">Kemarin, 16.40</div>
                            </div>
                        </div>
                        <div class="feed-item"><span class="feed-dot"></span>
                            <div><b>Konferensi Pendidikan Digital Jawa Timur</b> diajukan untuk ditinjau<div
                                    class="feed-time">8 Okt, 10.05</div>
                            </div>
                        </div>
                        <div class="feed-item"><span class="feed-dot"></span>
                            <div><b>Tiket VIP SENTEK</b> habis terjual<div class="feed-time">7 Okt, 13.22</div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>