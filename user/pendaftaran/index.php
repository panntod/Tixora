<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Pendaftaran</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame tx-style-f60fce6ec7">
        <header class="navbar">
            <div class="container">
                <div class="logo"><svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                        <path
                            d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z"
                            fill="#4B34DB" />
                        <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round"
                            fill="none" />
                    </svg><span class="logo-text">tixora</span></div>
                <nav class="nav-links"><span>Jelajahi acara</span><span>Tiket saya</span></nav><span
                    class="nav-actions"><span class="avatar">AR</span><span
                        class="button ghost-button small-button">Keluar</span></span>
            </div>
        </header>
        <main class="page-body tx-style-e5d5285078">
            <h1 class="page-title tx-style-49f14f8f83">Pendaftaran</h1>
            <div class="stepper"><span class="step-item step-done"><span class="step-dot"><svg width="12" height="12"
                            viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M3.5 8.5l3 3 6-6.5" stroke="#15803D" stroke-width="2" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg></span>Pilih tiket</span><span class="step-item step-active"><span
                        class="step-dot">2</span>Data peserta</span><span class="step-item "><span
                        class="step-dot">3</span>Pembayaran</span><span class="step-item "><span
                        class="step-dot">4</span>Selesai</span></div>
            <div class="two-col">
                <div class="col-main stack gap-24">
                    <div class="card">
                        <h2 class="card-title">Data peserta</h2>
                        <div class="stack gap-16">
                            <div class="two-fields">
                                <div><label class="field-label">Nama lengkap</label>
                                    <div class="input"><span class="">Alya Rahmawati</span></div>
                                </div>
                                <div><label class="field-label">Surel</label>
                                    <div class="input input-error"><span class="">alya.rahmawati@</span></div>
                                    <p class="help help-error">Lengkapi alamat surel Anda.</p>
                                </div>
                            </div>
                            <div class="two-fields">
                                <div><label class="field-label">Nomor WhatsApp</label>
                                    <div class="input"><span class="">0812-3456-7890</span></div>
                                </div>
                                <div><label class="field-label">Institusi</label>
                                    <div class="input"><span class="input-placeholder">Nama kampus atau instansi</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h2 class="card-title">Metode pembayaran</h2>
                        <div class="stack gap-10">
                            <div class="option option-selected"><b>Transfer virtual account</b><span
                                    class="badge badge-purple"><span class="badge-dot"></span>Dipilih</span></div>
                            <div class="option"><b>QRIS</b></div>
                            <div class="option"><b>Dompet digital</b></div>
                        </div>
                        <p class="help">Metode di atas contoh; menunggu keputusan penyedia pembayaran.</p>
                    </div>
                    <div class="check-row"><span class="checkbox"></span><span>Data yang saya isi sudah benar dan saya
                            menyetujui Syarat &amp; ketentuan.</span></div>
                    <div class="row"><span class="button button-large secondary-button">Kembali</span><span
                            class="button button-large disabled-button">Bayar Rp75.000</span></div>
                </div>
                <aside class="col-side card">
                    <div class="poster poster-indigo tx-style-63c893b1c4">
                        <div class="poster-art"><span class="art-circle-cyan"></span><span class="art-ring-dark"></span>
                        </div><span class="poster-title poster-title-card">SENTEK 2026</span>
                    </div>
                    <h3 class="tx-style-e9c0b256f0">Seminar Nasional Teknologi Informatika
                        2026</h3>
                    <p class="slate tx-style-95668ca799">Sab, 24 Okt 2026 · Auditorium Utama, Malang
                    </p>
                    <div class="stack gap-10 tx-style-b8fb61d376">
                        <div class="summary-row"><span>Tiket Umum × 1</span><span>Rp75.000</span></div>
                        <div class="summary-row summary-total"><span>Total</span><span>Rp75.000</span></div>
                    </div>
                </aside>
            </div>
        </main>
        <footer class="footer">
            <div class="container">
                <div>
                    <div class="logo"><svg width="30" height="30" viewBox="0 0 32 32" role="img"
                            aria-label="Logo TIXORA">
                            <path
                                d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z"
                                fill="#4B34DB" />
                            <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round"
                                fill="none" />
                        </svg><span class="logo-text">tixora</span></div>
                    <p class="footer-text">Platform acara dan tiket digital.</p>
                </div>
                <nav class="footer-links"><span>Bantuan</span><span>Syarat &amp; ketentuan</span><span>Kebijakan
                        privasi</span></nav>
            </div>
        </footer>
    </div>
</body>

</html>