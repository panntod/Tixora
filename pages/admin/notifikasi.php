<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Notifikasi</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:605px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div><span class="side-label">ADMIN</span><a
                    class="side-item" href="<?= BASE_URL ?>/admin">Dasbor</a><a class="side-item" href="<?= BASE_URL ?>/admin/antrean-acara">Antrean
                    acara<span class="count-pill">4</span></a><a class="side-item" href="<?= BASE_URL ?>/admin/pengguna">Pengguna</a><a
                    class="side-item" href="<?= BASE_URL ?>/admin/penyelenggara">Penyelenggara</a><a class="side-item"
                    href="<?= BASE_URL ?>/admin/laporan">Laporan</a><a class="side-item side-item-active"
                    href="<?= BASE_URL ?>/admin/notifikasi">Notifikasi</a><span class="side-item">Keluar</span>
                <div class="side-user"><b>Rahmat Hidayat</b>Administrator</div>
            </aside>
            <main class="main">
                <p class="crumb">Notifikasi</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Notifikasi</h1>
                        <p class="page-subtitle">2 belum dibaca</p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">Tandai semua dibaca</span></div>
                </div>
                <div class="cols">
                    <div class="col-wide">
                        <div class="chip-row"><span class="chip chip-active">Semua</span><span class="chip">Perlu
                                tindakan</span><span class="chip">Sistem</span></div>
                        <div class="card" style="padding:8px 24px">
                            <div class="feed-item"><span class="feed-dot"></span>
                                <div><b>4 acara menunggu tinjauan</b>
                                    <div class="cell-sub">Pengajuan terlama sejak 6 Okt 2026.</div>
                                    <div class="feed-time">Hari ini, 08.00</div>
                                </div>
                            </div>
                            <div class="feed-item"><span class="feed-dot"></span>
                                <div><b>LKTI Nasional diajukan ulang</b>
                                    <div class="cell-sub">BEM Fakultas Teknik mengirim revisi ke-2.</div>
                                    <div class="feed-time">8 Okt, 09.30</div>
                                </div>
                            </div>
                            <div class="feed-item"><span class="feed-dot feed-dot-warning"></span>
                                <div><b>Pengiriman surel tiket tertunda</b>
                                    <div class="cell-sub">3 surel tiket belum terkirim; sistem mencoba ulang.</div>
                                    <div class="feed-time">8 Okt, 07.12</div>
                                </div>
                            </div>
                            <div class="feed-item"><span class="feed-dot feed-dot-muted"></span>
                                <div><b>Laporan mingguan siap diunduh</b>
                                    <div class="cell-sub">Periode 28 Sep – 4 Okt 2026.</div>
                                    <div class="feed-time">5 Okt, 06.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-narrow stack gap-12">
                        <p class="spec-label" style="margin:0">UMPAN BALIK TINDAKAN</p>
                        <div class="alert alert-success">Acara disetujui. Penyelenggara telah diberi tahu.</div>
                        <div class="alert alert-success">Akun Eka Wulandari ditangguhkan.</div>
                        <div class="alert alert-warning">Pengajuan ini sudah ditinjau oleh admin lain.</div>
                        <div class="alert alert-error">Alasan penolakan wajib diisi.</div>
                        <div class="alert alert-error">Keputusan belum tersimpan karena koneksi terputus. Coba lagi.
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>