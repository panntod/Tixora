<?php
require_once __DIR__ . '/_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Dialog dan Kondisi</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:1245px">
        <div class="spec">
            <h1 class="page-title" style="margin-bottom:24px">Dialog dan kondisi organizer</h1>
            <div class="spec-grid">
                <div class="spec-cell">
                    <p class="spec-label">AJUKAN UNTUK DITINJAU</p>
                    <div class="dialog">
                        <h3 class="dialog-title">Ajukan acara untuk ditinjau?</h3>
                        <p class="dialog-text">Acara akan berstatus Menunggu tinjauan dan tidak dapat diedit sampai
                            admin memberi keputusan.</p>
                        <div class="row" style="justify-content:flex-end;margin-top:16px"><span
                                class="button secondary-button">Batal</span><span class="button primary-button">Ya,
                                ajukan</span></div>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">TARIK PENGAJUAN</p>
                    <div class="dialog">
                        <h3 class="dialog-title">Tarik pengajuan ini?</h3>
                        <p class="dialog-text">Acara kembali menjadi Draf dan dapat diedit. Anda perlu mengajukan ulang
                            untuk ditinjau.</p>
                        <div class="row" style="justify-content:flex-end;margin-top:16px"><span
                                class="button secondary-button">Batal</span><span class="button danger-button">Tarik
                                pengajuan</span></div>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">BATALKAN TIKET PESERTA</p>
                    <div class="dialog">
                        <h3 class="dialog-title">Batalkan tiket Alya Rahmawati?</h3>
                        <p class="dialog-text">Tiket TXR-2610-0482 tidak lagi sah. Peserta akan diberi tahu.
                            Pengembalian dana menunggu keputusan produk.</p>
                        <div class="row" style="justify-content:flex-end;margin-top:16px"><span
                                class="button secondary-button">Batal</span><span class="button danger-button">Batalkan
                                tiket</span></div>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">HAPUS ANGGOTA PANITIA</p>
                    <div class="dialog">
                        <h3 class="dialog-title">Hapus anggota Yoga Pratama?</h3>
                        <p class="dialog-text">Anggota dihapus dari kepanitiaan acara ini. Kegiatan rundown yang ia
                            tangani perlu diberi PIC baru.</p>
                        <div class="row" style="justify-content:flex-end;margin-top:16px"><span
                                class="button secondary-button">Batal</span><span class="button danger-button">Hapus
                                anggota</span></div>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">KOSONG</p>
                    <div class="card state-card" style="width:620px;box-sizing:border-box">
                        <div class="state-icon">0</div>
                        <h3 class="state-title">Belum ada acara</h3>
                        <p class="state-text">Buat acara pertama Anda. Draf tersimpan otomatis.</p><span
                            class="button primary-button">+ Buat acara</span>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">MEMUAT</p>
                    <div class="card" style="width:620px;box-sizing:border-box">
                        <h2 class="card-title">Memuat daftar acara</h2>
                        <div class="skeleton" style="height:18px"></div>
                        <div class="skeleton" style="height:18px;margin-top:10px"></div>
                        <div class="skeleton" style="height:18px;margin-top:10px;width:70%"></div>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">GALAT</p>
                    <div class="card state-card" style="width:620px;box-sizing:border-box">
                        <div class="state-icon" style="background:#FDECEC;color:#B91C1C">!</div>
                        <h3 class="state-title">Gagal memuat acara</h3>
                        <p class="state-text">Periksa koneksi internet Anda lalu coba lagi.</p><span
                            class="button secondary-button">Coba lagi</span>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">SUKSES</p>
                    <div class="stack gap-12" style="width:420px">
                        <div class="alert alert-success">Draf acara tersimpan.</div>
                        <div class="alert alert-success">Acara berhasil diajukan untuk ditinjau.</div>
                        <div class="alert alert-error">Pengajuan gagal dikirim. Coba lagi.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>