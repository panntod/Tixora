<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Detail Pengguna</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:738px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div><span class="side-label">ADMIN</span><a
                    class="side-item" href="<?= BASE_URL ?>/admin">Dasbor</a><a class="side-item" href="<?= BASE_URL ?>/admin/antrean-acara">Antrean
                    acara<span class="count-pill">4</span></a><a class="side-item side-item-active"
                    href="<?= BASE_URL ?>/admin/pengguna">Pengguna</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/penyelenggara">Penyelenggara</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/laporan">Laporan</a><a class="side-item" href="<?= BASE_URL ?>/admin/notifikasi">Notifikasi</a><span
                    class="side-item">Keluar</span>
                <div class="side-user"><b>Rahmat Hidayat</b>Administrator</div>
            </aside>
            <main class="main">
                <p class="crumb">Pengguna / Citra Lestari</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Citra Lestari</h1>
                        <p class="page-subtitle"><span class="badge badge-purple"><span
                                    class="badge-dot"></span>Peserta</span> <span class="badge badge-success"><span
                                    class="badge-dot"></span>Aktif</span></p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">← Kembali ke pengguna</span><span
                            class="button danger-outline-button">Tangguhkan akun</span></div>
                </div>
                <div class="cols">
                    <div class="stack gap-24" style="width:424px">
                        <div class="card">
                            <div class="row" style="margin-bottom:16px"><span class="avatar avatar-large"
                                    style="width:56px;height:56px;line-height:56px;font-size:18px">CL</span>
                                <div><b>Citra Lestari</b>
                                    <div class="slate" style="font-size:14px">citra.l@email.com</div>
                                </div>
                            </div>
                            <dl class="definition">
                                <div>
                                    <dt>WHATSAPP</dt>
                                    <dd>0813-5550-0220</dd>
                                </div>
                                <div>
                                    <dt>INSTITUSI</dt>
                                    <dd>Universitas di Malang</dd>
                                </div>
                                <div>
                                    <dt>TERDAFTAR</dt>
                                    <dd>3 Sep 2026</dd>
                                </div>
                                <div>
                                    <dt>TERAKHIR MASUK</dt>
                                    <dd>9 Okt 2026</dd>
                                </div>
                            </dl>
                        </div>
                        <div class="card">
                            <h2 class="card-title">Tindakan admin</h2>
                            <div class="stack gap-8"><span class="button secondary-button">Kirim tautan atur ulang kata
                                    sandi</span><span class="button danger-outline-button">Tangguhkan akun</span></div>
                            <p class="help">Perubahan peran belum tersedia; menunggu keputusan produk.</p>
                        </div>
                    </div>
                    <div class="stack gap-24" style="width:680px">
                        <div class="card" style="padding:20px 16px 8px;width:680px;box-sizing:border-box">
                            <h2 class="card-title" style="padding:0 8px">Aktivitas tiket</h2>
                            <div style="padding:0">
                                <div class="tbl-head">
                                    <div class="tbl-cell" style="width:220px">ACARA</div>
                                    <div class="tbl-cell" style="width:140px">NO. TIKET</div>
                                    <div class="tbl-cell" style="width:110px">KATEGORI</div>
                                    <div class="tbl-cell" style="width:178px">STATUS</div>
                                </div>
                                <div class="tbl-row">
                                    <div class="tbl-cell" style="width:220px"><b>SENTEK 2026</b></div>
                                    <div class="tbl-cell" style="width:140px">TXR-2610-0470</div>
                                    <div class="tbl-cell" style="width:110px">Umum</div>
                                    <div class="tbl-cell" style="width:178px"><span class="badge badge-warning"><span
                                                class="badge-dot"></span>Menunggu pembayaran</span></div>
                                </div>
                                <div class="tbl-row">
                                    <div class="tbl-cell" style="width:220px"><b>Seminar Karier dan Magang 2026</b>
                                    </div>
                                    <div class="tbl-cell" style="width:140px">TXR-2609-0231</div>
                                    <div class="tbl-cell" style="width:110px">Umum</div>
                                    <div class="tbl-cell" style="width:178px"><span class="badge badge-neutral"><span
                                                class="badge-dot"></span>Sudah dipakai</span></div>
                                </div>
                                <div class="tbl-row">
                                    <div class="tbl-cell" style="width:220px"><b>Workshop Fotografi Dasar</b></div>
                                    <div class="tbl-cell" style="width:140px">TXR-2610-0390</div>
                                    <div class="tbl-cell" style="width:110px">Mahasiswa</div>
                                    <div class="tbl-cell" style="width:178px"><span class="badge badge-error"><span
                                                class="badge-dot"></span>Dibatalkan</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <h2 class="card-title">Riwayat akun</h2>
                            <div class="timeline">
                                <div class="timeline-item"><span class="timeline-dot"></span><b>9 Okt, 20.12</b> ·
                                    Mendaftar SENTEK 2026</div>
                                <div class="timeline-item"><span class="timeline-dot"></span><b>20 Sep, 10.02</b> ·
                                    Membatalkan tiket Workshop Fotografi</div>
                                <div class="timeline-item"><span class="timeline-dot"></span><b>3 Sep, 14.30</b> · Akun
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