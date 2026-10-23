<?php
require_once __DIR__ . '/../_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Dialog Penerbitan dan Pembatalan</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:813px">
        <div class="spec">
            <h1 class="page-title" style="margin-bottom:24px">Dialog penerbitan dan pembatalan</h1>
            <div class="spec-grid">
                <div class="spec-cell">
                    <p class="spec-label">TERBITKAN ACARA</p>
                    <div class="dialog">
                        <h3 class="dialog-title">Terbitkan acara ini?</h3>
                        <p class="dialog-text">Acara tampil di halaman jelajah dan penjualan tiket dibuka. Setelah
                            terbit, acara tidak dapat dihapus.</p>
                        <div class="row" style="justify-content:flex-end;margin-top:16px"><span
                                class="button secondary-button">Batal</span><span class="button primary-button">Ya,
                                terbitkan</span></div>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">BATALKAN ACARA (VALIDASI)</p>
                    <div class="dialog">
                        <h3 class="dialog-title">Batalkan acara ini?</h3>
                        <p class="dialog-text">Penjualan dihentikan dan peserta diberi tahu. Tindakan ini tidak dapat
                            dibatalkan.</p>
                        <div><label class="field-label">Alasan pembatalan (wajib)</label>
                            <div class="input input-error input-area"><span class=""></span></div>
                            <p class="help help-error">Alasan wajib diisi, minimal 20 karakter.</p>
                        </div>
                        <div class="row" style="justify-content:flex-end;margin-top:16px"><span
                                class="button secondary-button">Batal</span><span class="button danger-button">Batalkan
                                acara</span></div>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">SIMPAN PERUBAHAN ACARA TERBIT</p>
                    <div class="dialog">
                        <h3 class="dialog-title">Simpan perubahan?</h3>
                        <p class="dialog-text">Perubahan tanggal atau lokasi mungkin perlu ditinjau ulang. Peserta yang
                            sudah terdaftar akan diberi tahu (perlu klarifikasi produk).</p>
                        <div class="row" style="justify-content:flex-end;margin-top:16px"><span
                                class="button secondary-button">Batal</span><span class="button primary-button">Simpan
                                perubahan</span></div>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">UMPAN BALIK</p>
                    <div class="stack gap-12" style="width:420px">
                        <div class="alert alert-success">Acara berhasil diterbitkan.</div>
                        <div class="alert alert-success">Acara dibatalkan. Peserta telah diberi tahu.</div>
                        <div class="alert alert-error">Gagal menerbitkan acara. Coba lagi.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>