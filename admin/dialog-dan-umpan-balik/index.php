<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Admin — Dialog dan Umpan Balik</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:1318px">
        <div class="spec">
            <h1 class="page-title" style="margin-bottom:24px">Dialog dan umpan balik admin</h1>
            <div class="spec-grid">
                <div class="spec-cell">
                    <p class="spec-label">SETUJUI ACARA</p>
                    <div class="dialog">
                        <h3 class="dialog-title">Setujui acara ini?</h3>
                        <p class="dialog-text">Status berubah menjadi <b>Disetujui</b>. Persetujuan tidak menerbitkan
                            acara; penerbitan dilakukan terpisah sesuai alur produk. Penyelenggara akan diberi tahu.</p>
                        <div class="row" style="justify-content:flex-end"><span
                                class="button secondary-button">Batal</span><span class="button primary-button">Ya,
                                setujui</span></div>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">TOLAK ACARA (VALIDASI)</p>
                    <div class="dialog">
                        <h3 class="dialog-title">Tolak acara ini?</h3>
                        <p class="dialog-text">Alasan akan dikirim ke penyelenggara, yang dapat merevisi lalu mengajukan
                            ulang.</p>
                        <div><label class="field-label">Alasan penolakan (wajib)</label>
                            <div class="input input-error input-area"><span class=""></span></div>
                            <p class="help help-error">Alasan wajib diisi, minimal 20 karakter.</p>
                        </div>
                        <div class="row" style="justify-content:flex-end;margin-top:16px"><span
                                class="button secondary-button">Batal</span><span class="button danger-button">Tolak
                                acara</span></div>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">TANGGUHKAN AKUN PENGGUNA</p>
                    <div class="dialog">
                        <h3 class="dialog-title">Tangguhkan akun Citra Lestari?</h3>
                        <p class="dialog-text">Pengguna tidak dapat masuk selama ditangguhkan. Tiket yang sudah dimiliki
                            tidak dihapus (perlu klarifikasi produk).</p>
                        <div><label class="field-label">Alasan (wajib)</label>
                            <div class="input input-area"><span class=""></span></div>
                        </div>
                        <div class="row" style="justify-content:flex-end;margin-top:16px"><span
                                class="button secondary-button">Batal</span><span
                                class="button danger-button">Tangguhkan</span></div>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">TANGGUHKAN PENYELENGGARA</p>
                    <div class="dialog">
                        <h3 class="dialog-title">Tangguhkan penyelenggara Klub Fotografi?</h3>
                        <p class="dialog-text">Penyelenggara tidak dapat membuat atau mengelola acara selama
                            ditangguhkan. Acara yang sedang terbit perlu keputusan produk.</p>
                        <div><label class="field-label">Alasan (wajib)</label>
                            <div class="input input-area"><span class=""></span></div>
                        </div>
                        <div class="row" style="justify-content:flex-end;margin-top:16px"><span
                                class="button secondary-button">Batal</span><span
                                class="button danger-button">Tangguhkan</span></div>
                    </div>
                </div>
                <div class="spec-cell">
                    <p class="spec-label">UMPAN BALIK TINDAKAN</p>
                    <div class="stack gap-12" style="width:420px">
                        <div class="alert alert-success">Acara disetujui. Penyelenggara telah diberi tahu.</div>
                        <div class="alert alert-success">Akun Eka Wulandari ditangguhkan.</div>
                        <div class="alert alert-warning">Pengajuan ini sudah ditinjau oleh admin lain.</div>
                        <div class="alert alert-error">Alasan penolakan wajib diisi.</div>
                        <div class="alert alert-error">Keputusan belum tersimpan karena koneksi terputus. Coba lagi.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>