<?php
require_once __DIR__ . '/../_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Buat Acara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:1373px">
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
                        <p class="page-subtitle">Langkah 1 dari 4 · Perubahan tersimpan otomatis sebagai draf</p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">Pratinjau</span></div>
                </div>
                <div class="stepper"><span class="step-item step-active"><span class="step-dot">1</span>Informasi &amp;
                        jadwal</span><span class="step-item "><span class="step-dot">2</span>Tiket &amp;
                        kapasitas</span><span class="step-item "><span class="step-dot">3</span>Rundown</span><span
                        class="step-item "><span class="step-dot">4</span>Tinjau &amp; ajukan</span></div>
                <div style="width:880px" class="stack gap-24">
                    <div class="alert alert-error">Lengkapi 3 kolom wajib sebelum melanjutkan.</div>
                    <div class="card">
                        <h2 class="card-title">Informasi dasar</h2>
                        <div class="stack gap-16">
                            <div><label class="field-label">Nama acara</label>
                                <div class="input input-error"><span class=""></span></div>
                                <p class="help help-error">Nama acara wajib diisi.</p>
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
                                <div class="input input-error input-area"><span class="">Seminar tentang AI.</span>
                                </div>
                                <p class="help help-error">Deskripsi minimal 50 karakter (saat ini 19).</p>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h2 class="card-title">Poster</h2>
                        <div class="dropzone"><b>Unggah poster acara</b><br>JPG atau PNG, rasio 16:9, maks. 2
                            MB<br><span class="button secondary-button small-button" style="margin-top:12px">Pilih
                                berkas</span></div>
                    </div>
                    <div class="card">
                        <h2 class="card-title">Waktu &amp; lokasi</h2>
                        <div class="stack gap-16">
                            <div class="two-fields">
                                <div><label class="field-label">Mulai</label>
                                    <div class="input"><span class="">24 Okt 2026, 07.30</span></div>
                                </div>
                                <div><label class="field-label">Selesai</label>
                                    <div class="input input-error"><span class="">23 Okt 2026, 16.00</span></div>
                                    <p class="help help-error">Waktu selesai harus setelah waktu mulai.</p>
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
                        class="button ghost-button">Simpan draf</span>
                    <div class="row"><span class="button secondary-button">Batal</span><span
                            class="button disabled-button">Lanjut: Tiket &amp; kapasitas</span></div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>