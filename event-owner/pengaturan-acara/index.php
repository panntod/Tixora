<?php
require_once __DIR__ . '/../_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Pengaturan Acara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:1147px">
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
                <p class="crumb">Acara saya / Workshop UI/UX untuk Pemula / Pengaturan</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Pengaturan acara</h1>
                        <p class="page-subtitle"><span class="badge badge-info"><span
                                    class="badge-dot"></span>Disetujui</span> &nbsp;<span class="slate"
                                style="font-size:14px">Workshop UI/UX untuk Pemula · 1 Nov 2026</span></p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">← Kembali ke acara saya</span></div>
                </div>
                <div class="stack gap-24" style="width:880px">
                    <div class="card">
                        <h2 class="card-title">Status acara</h2>
                        <div class="stepper" style="margin:0"><span class="step-item step-done"><span
                                    class="step-dot"><svg width="12" height="12" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg></span>Draf</span><span class="step-item step-done"><span
                                    class="step-dot"><svg width="12" height="12" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg></span>Menunggu tinjauan</span><span class="step-item step-active"><span
                                    class="step-dot">3</span>Disetujui</span><span class="step-item "><span
                                    class="step-dot">4</span>Terbit</span></div>
                        <p class="help" style="margin-top:12px">Disetujui admin pada 7 Okt 2026. Acara belum terlihat
                            oleh peserta sampai diterbitkan.</p>
                    </div>
                    <div class="card">
                        <div class="row-between">
                            <div>
                                <h2 class="card-title" style="margin-bottom:4px">Terbitkan acara</h2>
                                <p class="slate" style="font-size:14px;line-height:21px;width:560px">Acara akan tampil
                                    di halaman jelajah dan penjualan tiket dibuka sesuai periode yang Anda atur.</p>
                            </div><span class="button primary-button">Terbitkan acara</span>
                        </div>
                    </div>
                    <div class="card">
                        <h2 class="card-title">Zona berbahaya</h2>
                        <div class="row-between">
                            <p class="slate" style="font-size:14px;line-height:21px;width:560px">Membatalkan acara
                                menghentikan penjualan dan memberi tahu peserta. Dampak pada tiket yang sudah terjual
                                menunggu keputusan produk.</p><span class="button danger-outline-button">Batalkan
                                acara</span>
                        </div>
                    </div>
                    <div class="card" style="padding:20px 16px 8px">
                        <h2 class="card-title" style="padding:0 8px">Tindakan yang tersedia per status</h2>
                        <div style="padding:0">
                            <div class="tbl-head">
                                <div class="tbl-cell" style="width:220px">STATUS</div>
                                <div class="tbl-cell" style="width:628px">TINDAKAN</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:220px"><b>Draf</b></div>
                                <div class="tbl-cell" style="width:628px">Edit, ajukan untuk ditinjau, hapus</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:220px"><b>Menunggu tinjauan</b></div>
                                <div class="tbl-cell" style="width:628px">Lihat pengajuan, tarik pengajuan (tidak dapat
                                    diedit)</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:220px"><b>Disetujui</b></div>
                                <div class="tbl-cell" style="width:628px">Terbitkan, edit informasi, batalkan</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:220px"><b>Terbit</b></div>
                                <div class="tbl-cell" style="width:628px">Kelola, edit terbatas, batalkan acara</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:220px"><b>Ditolak</b></div>
                                <div class="tbl-cell" style="width:628px">Revisi dan ajukan ulang</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:220px"><b>Selesai</b></div>
                                <div class="tbl-cell" style="width:628px">Lihat laporan</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:220px"><b>Dibatalkan</b></div>
                                <div class="tbl-cell" style="width:628px">Lihat detail</div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>