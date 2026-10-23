<?php
require_once __DIR__ . '/../_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Buat Acara: Tinjau dan Ajukan</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:991px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                        <path
                            d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z"
                            fill="#4B34DB" />
                        <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round"
                            fill="none" />
                    </svg><span class="logo-text">tixora</span></div><span class="side-item">Dasbor</span><span
                    class="side-item">Acara saya</span><span class="side-item side-item-active">Buat acara</span><span
                    class="side-item">Pengaturan</span><span class="side-item">Keluar</span>
                <div class="side-user"><b>Dimas Prakoso</b>HMTI Fakultas Ilmu Komputer</div>
            </aside>
            <main class="main">
                <p class="crumb">Acara saya / Buat acara</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Buat acara baru</h1>
                        <p class="page-subtitle">Langkah 4 dari 4 · Periksa sebelum diajukan</p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">Pratinjau</span></div>
                </div>
                <div class="stepper"><span class="step-item step-done"><span class="step-dot"><svg width="12"
                                height="12" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg></span>Informasi &amp; jadwal</span><span class="step-item step-done"><span
                            class="step-dot"><svg width="12" height="12" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg></span>Tiket &amp; kapasitas</span><span class="step-item step-done"><span
                            class="step-dot"><svg width="12" height="12" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg></span>Rundown</span><span class="step-item step-active"><span
                            class="step-dot">4</span>Tinjau &amp; ajukan</span></div>
                <div class="stack gap-24" style="width:880px">
                    <div class="alert alert-info">Setelah diajukan, acara berstatus <b>Menunggu tinjauan</b> dan tidak
                        dapat diedit sampai admin memberi keputusan.</div>
                    <div class="card">
                        <div class="row-between" style="margin-bottom:12px">
                            <h2 class="card-title" style="margin:0">Informasi &amp; jadwal</h2><span
                                class="badge badge-success"><span class="badge-dot"></span>Lengkap</span>
                        </div>
                        <dl class="definition">
                            <div>
                                <dt>NAMA ACARA</dt>
                                <dd>Konferensi Pendidikan Digital Jawa Timur</dd>
                            </div>
                            <div>
                                <dt>KATEGORI</dt>
                                <dd>Konferensi</dd>
                            </div>
                            <div>
                                <dt>WAKTU</dt>
                                <dd>12–13 Nov 2026</dd>
                            </div>
                            <div>
                                <dt>LOKASI</dt>
                                <dd>Gedung Serbaguna Kampus, Malang</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="card">
                        <div class="row-between" style="margin-bottom:12px">
                            <h2 class="card-title" style="margin:0">Tiket &amp; kapasitas</h2><span
                                class="badge badge-success"><span class="badge-dot"></span>Lengkap</span>
                        </div>
                        <p class="slate" style="font-size:14px;line-height:21px">2 kategori · Umum Rp250.000 (200) dan
                            Pelajar / Mahasiswa Rp150.000 (100) · kapasitas 300 kursi</p>
                    </div>
                    <div class="card">
                        <div class="row-between" style="margin-bottom:12px">
                            <h2 class="card-title" style="margin:0">Rundown</h2><span class="badge badge-warning"><span
                                    class="badge-dot"></span>Perlu dilengkapi</span>
                        </div>
                        <p class="slate" style="font-size:14px;line-height:21px">4 kegiatan hari pertama, 0 kegiatan
                            hari kedua</p>
                    </div>
                    <div class="card">
                        <div class="row-between" style="margin-bottom:12px">
                            <h2 class="card-title" style="margin:0">RAB</h2><span class="badge badge-success"><span
                                    class="badge-dot"></span>Lengkap</span>
                        </div>
                        <p class="slate" style="font-size:14px;line-height:21px">Rp21.500.000 terlampir</p>
                    </div>
                </div>
                <div class="wizard-footer" style="width:880px;box-sizing:border-box"><span
                        class="button ghost-button">Simpan draf</span>
                    <div class="row"><span class="button secondary-button">Kembali</span><span
                            class="button primary-button">Ajukan untuk ditinjau</span></div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>