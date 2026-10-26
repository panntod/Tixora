<?php
require_once __DIR__ . '/../_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Tiket</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:700px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div><span class="side-item">Dasbor</span><span
                    class="side-item side-item-active">Acara saya</span><span class="side-item">Buat acara</span><span
                    class="side-item">Pengaturan</span><span class="side-item">Keluar</span>
                <div class="side-user"><b>Dimas Prakoso</b>HMTI Fakultas Ilmu Komputer</div>
            </aside>
            <main class="main">
                <p class="crumb">Acara saya / SENTEK 2026 / Tiket</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Tiket &amp; kapasitas</h1>
                        <p class="page-subtitle">Kapasitas total 250 kursi · 196 terjual</p>
                    </div>
                    <div class="head-actions"><span class="button primary-button">+ Tambah kategori</span></div>
                </div>
                <div class="tabs" style="margin-bottom:24px"><span class="tab">Ringkasan</span><span
                        class="tab tab-active">Tiket</span><span class="tab">Rundown</span><span
                        class="tab">Peserta</span><span class="tab">RAB</span><span class="tab">Panitia</span><span
                        class="tab">Analitik</span><span class="tab">Pengajuan</span></div>
                <div class="cols">
                    <div class="col-wide">
                        <div class="tbl-card" style="width:680px;box-sizing:border-box">
                            <div class="tbl-head">
                                <div class="tbl-cell" style="width:96px">KATEGORI</div>
                                <div class="tbl-cell" style="width:92px">HARGA</div>
                                <div class="tbl-cell" style="width:56px">KUOTA</div>
                                <div class="tbl-cell" style="width:66px">TERJUAL</div>
                                <div class="tbl-cell" style="width:46px">SISA</div>
                                <div class="tbl-cell" style="width:150px">PERIODE PENJUALAN</div>
                                <div class="tbl-cell" style="width:92px">STATUS</div>
                                <div class="tbl-cell" style="width:50px"></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:96px"><b>Umum</b></div>
                                <div class="tbl-cell" style="width:92px">Rp75.000</div>
                                <div class="tbl-cell" style="width:56px">120</div>
                                <div class="tbl-cell" style="width:66px">78</div>
                                <div class="tbl-cell" style="width:46px">42</div>
                                <div class="tbl-cell" style="width:150px">1 Sep – 22 Okt 2026</div>
                                <div class="tbl-cell" style="width:92px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Dijual</span></div>
                                <div class="tbl-cell tbl-right" style="width:50px"><span
                                        class="button ghost-button small-button">Edit</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:96px"><b>Mahasiswa</b></div>
                                <div class="tbl-cell" style="width:92px">Rp50.000</div>
                                <div class="tbl-cell" style="width:56px">100</div>
                                <div class="tbl-cell" style="width:66px">88</div>
                                <div class="tbl-cell" style="width:46px">12</div>
                                <div class="tbl-cell" style="width:150px">1 Sep – 22 Okt 2026</div>
                                <div class="tbl-cell" style="width:92px"><span class="badge badge-success"><span
                                            class="badge-dot"></span>Dijual</span></div>
                                <div class="tbl-cell tbl-right" style="width:50px"><span
                                        class="button ghost-button small-button">Edit</span></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:96px"><b>VIP</b></div>
                                <div class="tbl-cell" style="width:92px">Rp150.000</div>
                                <div class="tbl-cell" style="width:56px">30</div>
                                <div class="tbl-cell" style="width:66px">30</div>
                                <div class="tbl-cell" style="width:46px">0</div>
                                <div class="tbl-cell" style="width:150px">1 Sep – 22 Okt 2026</div>
                                <div class="tbl-cell" style="width:92px"><span class="badge badge-neutral"><span
                                            class="badge-dot"></span>Habis</span></div>
                                <div class="tbl-cell tbl-right" style="width:50px"><span
                                        class="button ghost-button small-button">Edit</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Edit: Mahasiswa</h2>
                        <div class="stack gap-16">
                            <div><label class="field-label">Harga (Rp)</label>
                                <div class="input"><span class="">50.000</span></div>
                            </div>
                            <div><label class="field-label">Kuota</label>
                                <div class="input input-error"><span class="">80</span></div>
                                <p class="help help-error">Kuota tidak boleh di bawah jumlah terjual (88).</p>
                            </div>
                            <div class="two-fields">
                                <div><label class="field-label">Mulai dijual</label>
                                    <div class="input"><span class="">1 Sep 2026</span></div>
                                </div>
                                <div><label class="field-label">Berakhir</label>
                                    <div class="input"><span class="">22 Okt 2026</span></div>
                                </div>
                            </div>
                            <div class="check-row"><span class="checkbox"
                                    style="background:#4B34DB;border-color:#4B34DB;display:flex;align-items:center;justify-content:center"><svg
                                        width="11" height="11" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M3.5 8.5l3 3 6-6.5" stroke="#FFFFFF" stroke-width="2" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg></span><span>Wajib melampirkan KTM</span></div>
                            <div class="row"><span class="button disabled-button">Simpan</span><span
                                    class="button ghost-button">Batal</span></div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>