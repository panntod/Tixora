<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Buat Acara: Tiket</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:1260px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div>
                <span class="side-item">Dasbor</span><span class="side-item">Acara saya</span><span
                    class="side-item side-item-active">Buat acara</span><span class="side-item">Pengaturan</span><span
                    class="side-item">Keluar</span>
                <div class="side-user"><b>Dimas Prakoso</b>HMTI Fakultas Ilmu Komputer</div>
            </aside>
            <main class="main">
                <p class="crumb">Acara saya / Buat acara</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Buat acara baru</h1>
                        <p class="page-subtitle">Langkah 2 dari 4 · Draf tersimpan 10 Okt, 09.12</p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">Pratinjau</span></div>
                </div>
                <div class="stepper"><span class="step-item step-done"><span class="step-dot"><svg width="12"
                                height="12" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg></span>Informasi &amp; jadwal</span><span class="step-item step-active"><span
                            class="step-dot">2</span>Tiket &amp; kapasitas</span><span class="step-item "><span
                            class="step-dot">3</span>Rundown</span><span class="step-item "><span
                            class="step-dot">4</span>Tinjau &amp; ajukan</span></div>
                <div class="stack gap-24" style="width:880px">
                    <div class="alert alert-info">Kapasitas total acara dihitung dari jumlah kuota seluruh kategori:
                        <b>250 kursi</b>.
                    </div>
                    <div class="card">
                        <div class="row-between" style="margin-bottom:12px"><b style="font-size:16px">Kategori
                                Umum</b><span class="button ghost-button small-button">Hapus</span></div>
                        <div class="two-fields">
                            <div><label class="field-label">Nama kategori</label>
                                <div class="input"><span class="">Umum</span></div>
                            </div>
                            <div><label class="field-label">Harga (Rp)</label>
                                <div class="input"><span class="">75.000</span></div>
                            </div>
                            <div><label class="field-label">Kuota</label>
                                <div class="input"><span class="">120</span></div>
                            </div>
                        </div>
                        <div class="two-fields" style="margin-top:16px">
                            <div><label class="field-label">Mulai dijual</label>
                                <div class="input"><span class="">1 Sep 2026</span></div>
                            </div>
                            <div><label class="field-label">Berakhir</label>
                                <div class="input"><span class="">22 Okt 2026</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="row-between" style="margin-bottom:12px"><b style="font-size:16px">Kategori
                                Mahasiswa</b><span class="button ghost-button small-button">Hapus</span></div>
                        <div class="two-fields">
                            <div><label class="field-label">Nama kategori</label>
                                <div class="input"><span class="">Mahasiswa</span></div>
                            </div>
                            <div><label class="field-label">Harga (Rp)</label>
                                <div class="input"><span class="">50.000</span></div>
                            </div>
                            <div><label class="field-label">Kuota</label>
                                <div class="input"><span class="">100</span></div>
                            </div>
                        </div>
                        <div class="two-fields" style="margin-top:16px">
                            <div><label class="field-label">Mulai dijual</label>
                                <div class="input"><span class="">1 Sep 2026</span></div>
                            </div>
                            <div><label class="field-label">Berakhir</label>
                                <div class="input"><span class="">22 Okt 2026</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="row-between" style="margin-bottom:12px"><b style="font-size:16px">Kategori
                                VIP</b><span class="button ghost-button small-button">Hapus</span></div>
                        <div class="two-fields">
                            <div><label class="field-label">Nama kategori</label>
                                <div class="input input-error"><span class=""></span></div>
                                <p class="help help-error">Nama kategori wajib diisi.</p>
                            </div>
                            <div><label class="field-label">Harga (Rp)</label>
                                <div class="input"><span class=""></span></div>
                            </div>
                            <div><label class="field-label">Kuota</label>
                                <div class="input"><span class=""></span></div>
                            </div>
                        </div>
                        <div class="two-fields" style="margin-top:16px">
                            <div><label class="field-label">Mulai dijual</label>
                                <div class="input"><span class="">1 Sep 2026</span></div>
                            </div>
                            <div><label class="field-label">Berakhir</label>
                                <div class="input"><span class="">22 Okt 2026</span></div>
                            </div>
                        </div>
                    </div>
                    <div><span class="button secondary-button">+ Tambah kategori</span></div>
                </div>
                <div class="wizard-footer" style="width:880px;box-sizing:border-box"><span
                        class="button ghost-button">Simpan draf</span>
                    <div class="row"><span class="button secondary-button">Kembali</span><span
                            class="button primary-button">Lanjut: Rundown</span></div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>