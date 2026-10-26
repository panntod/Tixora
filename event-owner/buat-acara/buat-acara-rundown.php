<?php
require_once __DIR__ . '/../_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Buat Acara: Rundown</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:795px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div><span class="side-item">Dasbor</span><span
                    class="side-item">Acara saya</span><span class="side-item side-item-active">Buat acara</span><span
                    class="side-item">Pengaturan</span><span class="side-item">Keluar</span>
                <div class="side-user"><b>Dimas Prakoso</b>HMTI Fakultas Ilmu Komputer</div>
            </aside>
            <main class="main">
                <p class="crumb">Acara saya / Buat acara</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Buat acara baru</h1>
                        <p class="page-subtitle">Langkah 3 dari 4 · Draf tersimpan 10 Okt, 09.20</p>
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
                            </svg></span>Tiket &amp; kapasitas</span><span class="step-item step-active"><span
                            class="step-dot">3</span>Rundown</span><span class="step-item "><span
                            class="step-dot">4</span>Tinjau &amp; ajukan</span></div>
                <div class="cols">
                    <div style="width:680px">
                        <div class="run-row"><svg width="14" height="18" viewBox="0 0 14 18" aria-hidden="true"
                                fill="#8A90AB">
                                <circle cx="4" cy="4" r="1.5" />
                                <circle cx="10" cy="4" r="1.5" />
                                <circle cx="4" cy="9" r="1.5" />
                                <circle cx="10" cy="9" r="1.5" />
                                <circle cx="4" cy="14" r="1.5" />
                                <circle cx="10" cy="14" r="1.5" />
                            </svg><span class="run-time">07.30</span>
                            <div class="run-body"><b>Registrasi ulang</b>
                                <p>Pemindaian kode QR peserta · PIC: Putri Ayuningtyas</p>
                            </div><span class="button ghost-button small-button" style="padding:0 8px"><svg width="14"
                                    height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 11V3M3.5 6.5L7 3l3.5 3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button" style="padding:0 8px"><svg
                                    width="14" height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 3v8M3.5 7.5L7 11l3.5-3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button">Edit</span><span
                                class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                        </div>
                        <div class="run-row"><svg width="14" height="18" viewBox="0 0 14 18" aria-hidden="true"
                                fill="#8A90AB">
                                <circle cx="4" cy="4" r="1.5" />
                                <circle cx="10" cy="4" r="1.5" />
                                <circle cx="4" cy="9" r="1.5" />
                                <circle cx="10" cy="9" r="1.5" />
                                <circle cx="4" cy="14" r="1.5" />
                                <circle cx="10" cy="14" r="1.5" />
                            </svg><span class="run-time">08.30</span>
                            <div class="run-body"><b>Pembukaan</b>
                                <p>Sambutan ketua pelaksana dan dekan · PIC: Dimas Prakoso</p>
                            </div><span class="button ghost-button small-button" style="padding:0 8px"><svg width="14"
                                    height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 11V3M3.5 6.5L7 3l3.5 3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button" style="padding:0 8px"><svg
                                    width="14" height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 3v8M3.5 7.5L7 11l3.5-3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button">Edit</span><span
                                class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                        </div>
                        <div class="run-row"><svg width="14" height="18" viewBox="0 0 14 18" aria-hidden="true"
                                fill="#8A90AB">
                                <circle cx="4" cy="4" r="1.5" />
                                <circle cx="10" cy="4" r="1.5" />
                                <circle cx="4" cy="9" r="1.5" />
                                <circle cx="10" cy="9" r="1.5" />
                                <circle cx="4" cy="14" r="1.5" />
                                <circle cx="10" cy="14" r="1.5" />
                            </svg><span class="run-time">09.00</span>
                            <div class="run-body"><b>Keynote: Arah AI di Industri Nasional</b>
                                <p>Dr. Rina Kusumawati, M.Kom. · PIC: Nabila Safitri</p>
                            </div><span class="button ghost-button small-button" style="padding:0 8px"><svg width="14"
                                    height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 11V3M3.5 6.5L7 3l3.5 3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button" style="padding:0 8px"><svg
                                    width="14" height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 3v8M3.5 7.5L7 11l3.5-3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button">Edit</span><span
                                class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                        </div>
                        <div class="run-row"><svg width="14" height="18" viewBox="0 0 14 18" aria-hidden="true"
                                fill="#8A90AB">
                                <circle cx="4" cy="4" r="1.5" />
                                <circle cx="10" cy="4" r="1.5" />
                                <circle cx="4" cy="9" r="1.5" />
                                <circle cx="10" cy="9" r="1.5" />
                                <circle cx="4" cy="14" r="1.5" />
                                <circle cx="10" cy="14" r="1.5" />
                            </svg><span class="run-time">11.00</span>
                            <div class="run-body"><b>Diskusi panel: Karier di Era Otomasi</b>
                                <p>Bagas Pratama dan Dewi Anggraeni · PIC: Fajar Nugroho</p>
                            </div><span class="button ghost-button small-button" style="padding:0 8px"><svg width="14"
                                    height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 11V3M3.5 6.5L7 3l3.5 3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button" style="padding:0 8px"><svg
                                    width="14" height="14" viewBox="0 0 14 14" aria-hidden="true">
                                    <path d="M7 3v8M3.5 7.5L7 11l3.5-3.5" stroke="#4B5170" stroke-width="1.6"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span class="button ghost-button small-button">Edit</span><span
                                class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                        </div>
                    </div>
                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Kegiatan baru</h2>
                        <div class="stack gap-16">
                            <div class="two-fields">
                                <div><label class="field-label">Mulai</label>
                                    <div class="input"><span class="">12.00</span></div>
                                </div>
                                <div><label class="field-label">Selesai</label>
                                    <div class="input"><span class="">13.00</span></div>
                                </div>
                            </div>
                            <div><label class="field-label">Kegiatan</label>
                                <div class="input"><span class="input-placeholder">Nama kegiatan</span></div>
                            </div>
                            <div><label class="field-label">Deskripsi</label>
                                <div class="input"><span class="input-placeholder">Keterangan singkat</span></div>
                            </div>
                            <div><label class="field-label">Penanggung jawab</label>
                                <div class="input"><span class="input-placeholder">Pilih anggota panitia</span><svg
                                        width="16" height="16" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg></div>
                            </div><span class="button primary-button">Simpan kegiatan</span>
                        </div>
                    </div>
                </div>
                <div class="wizard-footer" style="width:1128px;box-sizing:border-box"><span
                        class="button ghost-button">Simpan draf</span>
                    <div class="row"><span class="button secondary-button">Kembali</span><span
                            class="button primary-button">Lanjut: Tinjau &amp; ajukan</span></div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>