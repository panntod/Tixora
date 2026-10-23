<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Kondisi Antarmuka</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/user/styles.css">
</head>

<body>
    <div class="frame tx-style-ad2a66edca">
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
            <h1 class="page-title tx-style-8677744d08">Kondisi antarmuka</h1>
            <div class="tx-style-9ba5845674">
                <div class="card state-card tx-style-4bb61ee0a8">
                    <div class="state-icon">0</div>
                    <h3 class="state-title">Belum ada tiket</h3>
                    <p class="state-text">Tiket dari acara yang Anda daftar akan muncul di sini.</p><span
                        class="button primary-button">Jelajahi acara</span>
                </div>
                <div class="card state-card tx-style-4bb61ee0a8">
                    <div class="state-icon">?</div>
                    <h3 class="state-title">Tidak ada acara untuk “robotika”</h3>
                    <p class="state-text">Coba kata kunci lain atau hapus filter.</p><span
                        class="button secondary-button">Hapus semua filter</span>
                </div>
                <div class="card tx-style-4bb61ee0a8">
                    <h2 class="card-title">Memuat</h2>
                    <div class="skeleton tx-style-231645ac17"></div>
                    <div class="skeleton tx-style-a6a2499a49"></div>
                    <div class="skeleton tx-style-6e20399ac7"></div>
                </div>
                <div class="card state-card tx-style-4bb61ee0a8">
                    <div class="state-icon tx-style-b2fcb312af">!</div>
                    <h3 class="state-title">Gagal memuat acara</h3>
                    <p class="state-text">Periksa koneksi internet Anda lalu coba lagi.</p><span
                        class="button secondary-button">Coba lagi</span>
                </div>
            </div>
            <div class="alert alert-success tx-style-dcc936327e"
               >Tiket berhasil disimpan ke
                galeri.</div>
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