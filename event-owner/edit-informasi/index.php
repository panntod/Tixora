<?php
require_once __DIR__ . '/../_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Edit Informasi Acara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:1194px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div><span class="side-item">Dasbor</span><span
                    class="side-item side-item-active">Acara saya</span><span class="side-item">Buat acara</span><span
                    class="side-item">Pengaturan</span><span class="side-item">Keluar</span>
                <div class="side-user"><b>Dimas Prakoso</b>HMTI Fakultas Ilmu Komputer</div>
            </aside>
            <main class="main">
                <p class="crumb">Acara saya / SENTEK 2026 / Edit informasi</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Edit informasi acara</h1>
                        <p class="page-subtitle"><span class="badge badge-success"><span
                                    class="badge-dot"></span>Terbit</span> &nbsp;<span class="slate"
                                style="font-size:14px">Seminar Nasional Teknologi Informatika 2026</span></p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">← Kembali ke ringkasan</span></div>
                </div>
                <div class="stack gap-24" style="width:880px">
                    <div class="alert alert-warning">Acara ini berstatus <b>Terbit</b>. Perubahan tanggal, lokasi, dan
                        harga tiket mungkin perlu ditinjau ulang oleh admin (perlu klarifikasi produk).</div>
                    <div class="card">
                        <h2 class="card-title">Informasi dasar</h2>
                        <div class="stack gap-16">
                            <div><label class="field-label">Nama acara</label>
                                <div class="input"><span class="">Seminar Nasional Teknologi Informatika 2026</span>
                                </div>
                            </div>
                            <div class="two-fields">
                                <div><label class="field-label">Kategori</label>
                                    <div class="input"><span class="">Seminar</span><svg width="16" height="16"
                                            viewBox="0 0 16 16" aria-hidden="true">
                                            <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg></div>
                                </div>
                                <div><label class="field-label">Penyelenggara</label>
                                    <div class="input"><span class="">HMTI Fakultas Ilmu Komputer</span></div>
                                </div>
                            </div>
                            <div><label class="field-label">Deskripsi</label>
                                <div class="input input-area"><span class="">Satu hari penuh membahas arah teknologi
                                        informasi di Indonesia: dari kecerdasan buatan sampai kesiapan karier
                                        lulusan.</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h2 class="card-title">Poster</h2>
                        <div style="display:flex;gap:16px;align-items:center">
                            <div class="poster poster-indigo"
                                style="width:160px;height:90px;border-radius:8px;padding:10px">
                                <div class="poster-art"><span class="art-circle-cyan"
                                        style="width:100px;height:100px;right:-25px;top:-25px;border-radius:50px"></span>
                                </div><span class="poster-title poster-title-card"
                                    style="font-size:14px;line-height:15px">SENTEK 2026</span>
                            </div>
                            <div><b style="font-size:14px">poster-sentek-2026.png</b>
                                <div class="slate" style="font-size:12px;margin:2px 0 8px">1,2 MB · 16:9</div><span
                                    class="button secondary-button small-button">Ganti poster</span>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h2 class="card-title">Waktu &amp; lokasi</h2>
                        <div class="stack gap-16">
                            <div class="two-fields">
                                <div><label class="field-label">Mulai</label>
                                    <div class="input"><span class="">24 Okt 2026, 07.30</span></div>
                                </div>
                                <div><label class="field-label">Selesai</label>
                                    <div class="input"><span class="">24 Okt 2026, 16.00</span></div>
                                </div>
                            </div>
                            <div class="two-fields">
                                <div><label class="field-label">Nama tempat</label>
                                    <div class="input"><span class="">Auditorium Utama</span></div>
                                </div>
                                <div><label class="field-label">Kota</label>
                                    <div class="input"><span class="">Malang</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="wizard-footer" style="width:880px;box-sizing:border-box"><span
                        class="button ghost-button">Batal</span><span class="button primary-button">Simpan
                        perubahan</span></div>
            </main>
        </div>
    </div>
</body>

</html>