<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Tinjauan Revisi</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:813px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div><span class="side-label">ADMIN</span><a
                    class="side-item" href="<?= BASE_URL ?>/admin">Dasbor</a><a class="side-item side-item-active"
                    href="<?= BASE_URL ?>/admin/antrean-acara">Antrean acara<span class="count-pill">4</span></a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/pengguna">Pengguna</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/penyelenggara">Penyelenggara</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/laporan">Laporan</a><a class="side-item" href="<?= BASE_URL ?>/admin/notifikasi">Notifikasi</a><span
                    class="side-item">Keluar</span>
                <div class="side-user"><b>Rahmat Hidayat</b>Administrator</div>
            </aside>
            <main class="main">
                <p class="crumb">Antrean acara / Tinjauan revisi</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Lomba Karya Tulis Ilmiah Nasional</h1>
                        <p class="page-subtitle"><span class="badge badge-warning"><span
                                    class="badge-dot"></span>Menunggu tinjauan</span> <span
                                class="badge badge-purple"><span class="badge-dot"></span>Revisi ke-2</span> &nbsp;<span
                                class="slate" style="font-size:14px">Diajukan ulang 8 Okt 2026</span></p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">← Kembali ke antrean</span></div>
                </div>
                <div class="cols">
                    <div class="stack gap-24" style="width:740px">
                        <div class="card" style="box-shadow:inset 0 0 0 1px #F3B6B6">
                            <h2 class="card-title">Penolakan sebelumnya</h2>
                            <p class="feed-time" style="font-weight:700;margin-bottom:8px">1 OKT 2026 · RAHMAT HIDAYAT
                            </p>
                            <p class="slate" style="font-size:16px;line-height:24px;width:620px">Rincian anggaran belum
                                dilampirkan dan kriteria penilaian lomba belum dijelaskan pada deskripsi.</p>
                        </div>
                        <div class="card">
                            <h2 class="card-title">Perubahan sejak penolakan</h2>
                            <div class="stack gap-10">
                                <div class="row-between" style="font-size:14px"><span>RAB dilampirkan
                                        (Rp21.500.000)</span><span class="badge badge-success"><span
                                            class="badge-dot"></span>Sudah</span></div>
                                <div class="row-between" style="font-size:14px"><span>Deskripsi diperbarui dengan
                                        kriteria penilaian</span><span class="badge badge-success"><span
                                            class="badge-dot"></span>Sudah</span></div>
                                <div class="row-between" style="font-size:14px"><span>Tanggal pengumpulan
                                        karya</span><span class="badge badge-neutral"><span
                                            class="badge-dot"></span>Tidak berubah</span></div>
                            </div>
                            <p class="help">Perbandingan versi kata demi kata belum tersedia di rancangan ini.</p>
                        </div>
                        <div class="card">
                            <h2 class="card-title">Informasi acara</h2>
                            <dl class="definition">
                                <div>
                                    <dt>PENYELENGGARA</dt>
                                    <dd>BEM Fakultas Teknik</dd>
                                </div>
                                <div>
                                    <dt>KATEGORI</dt>
                                    <dd>Kompetisi</dd>
                                </div>
                                <div>
                                    <dt>TANGGAL</dt>
                                    <dd>5 Des 2026</dd>
                                </div>
                                <div>
                                    <dt>LOKASI</dt>
                                    <dd>Gedung Fakultas Teknik, Malang</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    <div class="stack gap-16" style="width:364px">
                        <div class="card card-raised">
                            <h2 class="card-title">Keputusan</h2>
                            <div class="stack gap-8"><span class="button button-large primary-button full-width">Setujui
                                    acara</span><span class="button button-large danger-outline-button full-width">Tolak
                                    acara</span></div>
                        </div>
                        <div class="card">
                            <h2 class="card-title">Riwayat tinjauan</h2>
                            <div class="timeline">
                                <div class="timeline-item"><span class="timeline-dot"></span><b>8 Okt, 09.30</b> ·
                                    Diajukan ulang (revisi ke-2)</div>
                                <div class="timeline-item"><span class="timeline-dot timeline-dot-error"></span><b>1
                                        Okt, 16.10</b> · Ditolak oleh admin</div>
                                <div class="timeline-item"><span class="timeline-dot"></span><b>28 Sep, 11.00</b> ·
                                    Diajukan pertama kali</div>
                                <div class="timeline-item"><span class="timeline-dot"></span><b>25 Sep, 08.40</b> · Draf
                                    dibuat</div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>