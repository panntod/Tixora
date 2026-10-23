<?php
require_once __DIR__ . '/../_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Peserta</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:852px">
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
                <p class="crumb">Acara saya / SENTEK 2026 / Peserta</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Peserta</h1>
                        <p class="page-subtitle">196 pendaftar</p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">Ekspor CSV</span></div>
                </div>
                <div class="tabs" style="margin-bottom:24px"><span class="tab">Ringkasan</span><span
                        class="tab">Tiket</span><span class="tab">Rundown</span><span
                        class="tab tab-active">Peserta</span><span class="tab">RAB</span><span
                        class="tab">Panitia</span><span class="tab">Analitik</span><span class="tab">Pengajuan</span>
                </div>
                <div class="filter-bar">
                    <div class="input" style="flex:1;width:auto"><span class="input-placeholder">Cari nama, surel, atau
                            nomor tiket</span></div>
                    <div class="input" style="width:170px"><span>Semua kategori</span><svg width="16" height="16"
                            viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></div>
                    <div class="input" style="width:200px"><span>Semua status</span><svg width="16" height="16"
                            viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></div>
                </div>
                <div class="cols">
                    <div class="col-wide">
                        <div class="tbl-card" style="width:680px;box-sizing:border-box">
                            <div class="tbl-head">
                                <div class="tbl-cell" style="width:200px">PESERTA</div>
                                <div class="tbl-cell" style="width:100px">KATEGORI</div>
                                <div class="tbl-cell" style="width:140px">NO. TIKET</div>
                                <div class="tbl-cell" style="width:60px">DAFTAR</div>
                                <div class="tbl-cell" style="width:148px">STATUS</div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:200px"><b>Alya Rahmawati</b>
                                    <div class="cell-sub">alya.rahmawati@email.com</div>
                                </div>
                                <div class="tbl-cell" style="width:100px">Umum</div>
                                <div class="tbl-cell" style="width:140px">TXR-2610-0482</div>
                                <div class="tbl-cell" style="width:60px">8 Okt</div>
                                <div class="tbl-cell" style="width:148px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Sah</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:200px"><b>Bima Aditya</b>
                                    <div class="cell-sub">bima.aditya@email.com</div>
                                </div>
                                <div class="tbl-cell" style="width:100px">Mahasiswa</div>
                                <div class="tbl-cell" style="width:140px">TXR-2610-0481</div>
                                <div class="tbl-cell" style="width:60px">8 Okt</div>
                                <div class="tbl-cell" style="width:148px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Sah</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:200px"><b>Citra Lestari</b>
                                    <div class="cell-sub">citra.l@email.com</div>
                                </div>
                                <div class="tbl-cell" style="width:100px">Umum</div>
                                <div class="tbl-cell" style="width:140px">TXR-2610-0470</div>
                                <div class="tbl-cell" style="width:60px">9 Okt</div>
                                <div class="tbl-cell" style="width:148px"><span class="badge badge-warning"><span
                                            class="badge-dot"></span>Menunggu pembayaran</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:200px"><b>Deni Kurniawan</b>
                                    <div class="cell-sub">deni.k@email.com</div>
                                </div>
                                <div class="tbl-cell" style="width:100px">VIP</div>
                                <div class="tbl-cell" style="width:140px">TXR-2610-0455</div>
                                <div class="tbl-cell" style="width:60px">9 Okt</div>
                                <div class="tbl-cell" style="width:148px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Sah</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:200px"><b>Eka Wulandari</b>
                                    <div class="cell-sub">eka.w@email.com</div>
                                </div>
                                <div class="tbl-cell" style="width:100px">Mahasiswa</div>
                                <div class="tbl-cell" style="width:140px">TXR-2610-0431</div>
                                <div class="tbl-cell" style="width:60px">7 Okt</div>
                                <div class="tbl-cell" style="width:148px"><span class="badge badge-error"><span
                                            class="badge-dot"></span>Dibatalkan</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:200px"><b>Fikri Ramadhan</b>
                                    <div class="cell-sub">fikri.r@email.com</div>
                                </div>
                                <div class="tbl-cell" style="width:100px">Umum</div>
                                <div class="tbl-cell" style="width:140px">TXR-2610-0420</div>
                                <div class="tbl-cell" style="width:60px">6 Okt</div>
                                <div class="tbl-cell" style="width:148px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Sah</span></div>
                            </div>
                        </div>
                        <div class="row-between"
                            style="width:680px;padding:12px 16px;box-sizing:border-box;font-size:14px;color:#4B5170">
                            <span>1–6 dari 196</span><span class="row"><span
                                    class="button secondary-button small-button">Sebelumnya</span><span
                                    class="button secondary-button small-button">Berikutnya</span></span></div>
                    </div>
                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Alya Rahmawati</h2>
                        <dl class="definition">
                            <div>
                                <dt>TIKET</dt>
                                <dd>Umum · TXR-2610-0482</dd>
                            </div>
                            <div>
                                <dt>STATUS</dt>
                                <dd><span class="badge badge-success"><span class="badge-dot"></span>Sah</span></dd>
                            </div>
                            <div>
                                <dt>WHATSAPP</dt>
                                <dd>0812-3456-7890</dd>
                            </div>
                            <div>
                                <dt>INSTITUSI</dt>
                                <dd>Universitas di Malang</dd>
                            </div>
                        </dl>
                        <div class="stack gap-8" style="margin-top:20px"><span class="button secondary-button">Kirim
                                ulang tiket</span><span class="button danger-outline-button">Batalkan tiket</span></div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>