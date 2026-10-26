<?php
require_once __DIR__ . '/_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Panitia</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:811px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div><span class="side-item">Dasbor</span><span
                    class="side-item side-item-active">Acara saya</span><span class="side-item">Buat acara</span><span
                    class="side-item">Pengaturan</span><span class="side-item">Keluar</span>
                <div class="side-user"><b>Dimas Prakoso</b>HMTI Fakultas Ilmu Komputer</div>
            </aside>
            <main class="main">
                <p class="crumb">Acara saya / SENTEK 2026 / Panitia</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Kepanitiaan</h1>
                        <p class="page-subtitle">7 anggota</p>
                    </div>
                    <div class="head-actions"><span class="button primary-button">+ Tambah anggota</span></div>
                </div>
                <div class="tabs" style="margin-bottom:24px"><span class="tab">Ringkasan</span><span
                        class="tab">Tiket</span><span class="tab">Rundown</span><span class="tab">Peserta</span><span
                        class="tab">RAB</span><span class="tab tab-active">Panitia</span><span
                        class="tab">Analitik</span><span class="tab">Pengajuan</span></div>
                <div class="cols">
                    <div class="col-wide">
                        <div class="tbl-card" style="width:680px;box-sizing:border-box">
                            <div class="tbl-head">
                                <div class="tbl-cell" style="width:128px">NAMA</div>
                                <div class="tbl-cell" style="width:150px">PERAN</div>
                                <div class="tbl-cell" style="width:96px">DIVISI</div>
                                <div class="tbl-cell" style="width:170px">KONTAK</div>
                                <div class="tbl-cell" style="width:104px"></div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:128px"><b>Dimas Prakoso</b></div>
                                <div class="tbl-cell" style="width:150px">Ketua Pelaksana</div>
                                <div class="tbl-cell" style="width:96px">Inti</div>
                                <div class="tbl-cell" style="width:170px">0812-5550-0101<div class="cell-sub">
                                        dimas.p@email.com</div>
                                </div>
                                <div class="tbl-cell tbl-right" style="width:104px"><span
                                        class="button ghost-button small-button">Edit</span><span
                                        class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                                </div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:128px"><b>Nabila Safitri</b></div>
                                <div class="tbl-cell" style="width:150px">Sekretaris</div>
                                <div class="tbl-cell" style="width:96px">Inti</div>
                                <div class="tbl-cell" style="width:170px">0813-5550-0102<div class="cell-sub">
                                        nabila.s@email.com</div>
                                </div>
                                <div class="tbl-cell tbl-right" style="width:104px"><span
                                        class="button ghost-button small-button">Edit</span><span
                                        class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                                </div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:128px"><b>Rizky Maulana</b></div>
                                <div class="tbl-cell" style="width:150px">Bendahara</div>
                                <div class="tbl-cell" style="width:96px">Inti</div>
                                <div class="tbl-cell" style="width:170px">0857-5550-0103<div class="cell-sub">
                                        rizky.m@email.com</div>
                                </div>
                                <div class="tbl-cell tbl-right" style="width:104px"><span
                                        class="button ghost-button small-button">Edit</span><span
                                        class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                                </div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:128px"><b>Putri Ayuningtyas</b></div>
                                <div class="tbl-cell" style="width:150px">Koordinator Acara</div>
                                <div class="tbl-cell" style="width:96px">Acara</div>
                                <div class="tbl-cell" style="width:170px">0812-5550-0104<div class="cell-sub">
                                        putri.a@email.com</div>
                                </div>
                                <div class="tbl-cell tbl-right" style="width:104px"><span
                                        class="button ghost-button small-button">Edit</span><span
                                        class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                                </div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:128px"><b>Fajar Nugroho</b></div>
                                <div class="tbl-cell" style="width:150px">Koordinator Humas</div>
                                <div class="tbl-cell" style="width:96px">Humas</div>
                                <div class="tbl-cell" style="width:170px">0821-5550-0105<div class="cell-sub">
                                        fajar.n@email.com</div>
                                </div>
                                <div class="tbl-cell tbl-right" style="width:104px"><span
                                        class="button ghost-button small-button">Edit</span><span
                                        class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                                </div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:128px"><b>Salsabila Rahma</b></div>
                                <div class="tbl-cell" style="width:150px">Koordinator Konsumsi</div>
                                <div class="tbl-cell" style="width:96px">Konsumsi</div>
                                <div class="tbl-cell" style="width:170px">0813-5550-0106<div class="cell-sub">
                                        salsa.r@email.com</div>
                                </div>
                                <div class="tbl-cell tbl-right" style="width:104px"><span
                                        class="button ghost-button small-button">Edit</span><span
                                        class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                                </div>
                            </div>
                            <div class="tbl-row">
                                <div class="tbl-cell" style="width:128px"><b>Yoga Pratama</b></div>
                                <div class="tbl-cell" style="width:150px">Koordinator Perlengkapan</div>
                                <div class="tbl-cell" style="width:96px">Perlengkapan</div>
                                <div class="tbl-cell" style="width:170px">0857-5550-0107<div class="cell-sub">
                                        yoga.p@email.com</div>
                                </div>
                                <div class="tbl-cell tbl-right" style="width:104px"><span
                                        class="button ghost-button small-button">Edit</span><span
                                        class="button ghost-button small-button" style="color:#B91C1C">Hapus</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Tambah anggota</h2>
                        <div class="stack gap-16">
                            <div><label class="field-label">Nama lengkap</label>
                                <div class="input"><span class=""></span></div>
                            </div>
                            <div><label class="field-label">Peran</label>
                                <div class="input"><span class="input-placeholder">Contoh: Koordinator
                                        Dokumentasi</span></div>
                            </div>
                            <div><label class="field-label">Nomor WhatsApp</label>
                                <div class="input input-error"><span class="">0812</span></div>
                                <p class="help help-error">Nomor WhatsApp tidak lengkap.</p>
                            </div>
                            <div><label class="field-label">Surel</label>
                                <div class="input"><span class=""></span></div>
                            </div><span class="button disabled-button">Simpan anggota</span>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>