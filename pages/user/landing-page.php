<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>TIXORA — Landing Page</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            margin: 0;
            background: #FAFAFC;
            font-family: Arial, Helvetica, sans-serif;
            color: #0E1128
        }

        .landing-page {
            width: 1440px;
            height: 2547px;
            background: #FAFAFC;
            position: relative;
            overflow: hidden
        }

        .container {
            width: 1152px;
            margin: 0 auto
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 9px
        }

        .logo-text {
            font-size: 22px;
            line-height: 28px;
            font-weight: 700;
            letter-spacing: -0.9px;
            color: #0E1128
        }

        .navbar {
            width: 1440px;
            height: 64px;
            background: #FAFAFC;
            border-bottom: 1px solid #E4E4EE
        }

        .navbar .container {
            height: 63px;
            display: flex;
            align-items: center;
            gap: 32px
        }

        .nav-links {
            flex: 1;
            display: flex;
            gap: 24px;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700;
            color: #4B5170
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 40px;
            padding: 0 16px;
            border: 0;
            border-radius: 8px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700;
            white-space: nowrap;
            cursor: default
        }

        .button-large {
            height: 48px;
            padding: 0 22px;
            font-size: 16px;
            line-height: 24px
        }

        .primary-button {
            background: #4B34DB;
            color: #FFFFFF
        }

        .secondary-button {
            background: #FFFFFF;
            color: #0E1128;
            border: 1px solid #C9CBDC
        }

        .ghost-button {
            background: transparent;
            color: #0E1128
        }

        .outline-light-button {
            background: transparent;
            color: #FFFFFF;
            border: 1px solid #6B6E84
        }

        .white-button {
            background: #FFFFFF;
            color: #0E1128
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border-radius: 99px;
            font-size: 12px;
            line-height: 12px;
            font-weight: 700
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 3px;
            background: currentColor
        }

        .badge-purple {
            background: #F1F0FF;
            color: #3C29B8
        }

        .badge-success {
            background: #E8F6EC;
            color: #15803D
        }

        .badge-warning {
            background: #FEF3E2;
            color: #B45309
        }

        .badge-info {
            background: #E8F0FE;
            color: #1D4ED8
        }

        .badge-error {
            background: #FDECEC;
            color: #B91C1C
        }

        .badge-neutral {
            background: #F1F1F7;
            color: #4B5170
        }

        .poster {
            position: relative;
            overflow: hidden;
            color: #FFFFFF;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 18px
        }

        .poster-art {
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            bottom: 0
        }

        .poster-kicker,
        .poster-title {
            position: relative
        }

        .poster-kicker {
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            opacity: .85
        }

        .poster-title {
            font-size: 26px;
            line-height: 27px;
            font-weight: 700;
            letter-spacing: -0.5px
        }

        .poster-title-card {
            font-size: 22px;
            line-height: 23px
        }

        .poster-indigo {
            background: #4B34DB
        }

        .poster-ink {
            background: #0E1128
        }

        .poster-cyan {
            background: #DDF4F9;
            color: #0E1128
        }

        .poster-amber {
            background: #F6B94A;
            color: #0E1128
        }

        .art-circle-cyan {
            position: absolute;
            right: -50px;
            top: -50px;
            width: 210px;
            height: 210px;
            border-radius: 105px;
            background: #5EC8E5
        }

        .art-ring-dark {
            position: absolute;
            right: 40px;
            top: 60px;
            width: 90px;
            height: 90px;
            border-radius: 45px;
            border: 14px solid #1C1458
        }

        .art-stripes {
            position: absolute;
            left: 102px;
            top: 0;
            width: 168px;
            height: 109px;
            overflow: hidden
        }

        .poster-stripes {
            display: block
        }

        .art-circle-indigo {
            position: absolute;
            right: -30px;
            top: -30px;
            width: 150px;
            height: 150px;
            border-radius: 75px;
            background: #4B34DB
        }

        .art-square-ink {
            position: absolute;
            right: 24px;
            top: 96px;
            width: 70px;
            height: 70px;
            background: #0E1128
        }

        .art-circle-ink {
            position: absolute;
            right: -70px;
            top: -20px;
            width: 220px;
            height: 220px;
            border-radius: 110px;
            background: #0E1128
        }

        .hero {
            width: 1440px;
            height: 640px;
            padding-top: 72px
        }

        .hero .container {
            display: flex;
            gap: 56px
        }

        .hero-copy {
            width: 592px;
            display: flex;
            flex-direction: column
        }

        .hero-badge-row {
            height: 22px;
            margin-bottom: 36px
        }

        .hero-title {
            font-size: 64px;
            line-height: 67px;
            font-weight: 700;
            letter-spacing: -2.2px;
            color: #0E1128;
            white-space: nowrap
        }

        .hero-title-accent {
            color: #4B34DB
        }

        .hero-lead {
            margin-top: 20px;
            font-size: 18px;
            line-height: 27px;
            color: #4B5170
        }

        .search-bar {
            margin-top: 32px;
            width: 592px;
            height: 64px;
            padding: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(14, 17, 40, .08)
        }

        .search-field {
            width: 276px;
            height: 48px;
            padding: 0 12px;
            display: flex;
            align-items: center;
            font-size: 15px;
            color: #5F6483
        }

        .search-select {
            width: 150px;
            height: 48px;
            padding: 0 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-left: 1px solid #E4E4EE;
            font-size: 15px;
            color: #0E1128
        }

        .popular-row {
            margin-top: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            line-height: 21px;
            color: #5F6483
        }

        .chip {
            padding: 6px 12px;
            border-radius: 99px;
            background: #FFFFFF;
            box-shadow: inset 0 0 0 1px #E4E4EE;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700;
            color: #4B5170
        }

        .hero-visual {
            width: 504px;
            display: flex;
            align-items: center;
            justify-content: flex-end
        }

        .ticket {
            width: 400px;
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 16px 40px rgba(14, 17, 40, .14);
            transform: rotate(2deg)
        }

        .ticket .poster {
            border-radius: 16px 16px 0 0
        }

        .ticket-stub {
            position: relative;
            border-top: 2px dashed #E4E4EE;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px
        }

        .ticket-notch-left,
        .ticket-notch-right {
            position: absolute;
            top: -11px;
            width: 20px;
            height: 20px;
            border-radius: 10px;
            background: #FAFAFC
        }

        .ticket-notch-left {
            left: -10px
        }

        .ticket-notch-right {
            right: -10px
        }

        .ticket-info {
            width: 260px
        }

        .ticket-title {
            margin: 10px 0 4px;
            font-size: 16px;
            line-height: 22px;
            font-weight: 700
        }

        .ticket-meta {
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .ticket-id {
            margin-top: 8px;
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            letter-spacing: .7px;
            color: #5F6483
        }

        .section {
            width: 1440px;
            padding: 56px 0
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 24px;
            height: 40px
        }

        .section-title {
            font-size: 32px;
            line-height: 37px;
            font-weight: 700;
            letter-spacing: -0.6px;
            color: #0E1128
        }

        .tabs {
            display: flex;
            gap: 24px;
            border-bottom: 1px solid #E4E4EE;
            margin-bottom: 24px;
            height: 32px
        }

        .tab {
            padding-bottom: 10px;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700;
            color: #5F6483
        }

        .tab-active {
            color: #0E1128;
            border-bottom: 2px solid #4B34DB
        }

        .event-grid {
            display: flex;
            gap: 24px
        }

        .event-card {
            width: 270px;
            height: 392px;
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(14, 17, 40, .06), 0 1px 3px rgba(14, 17, 40, .06);
            overflow: hidden;
            display: flex;
            flex-direction: column
        }

        .event-card-body {
            flex: 1;
            padding: 16px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px
        }

        .event-card-title {
            font-size: 16px;
            line-height: 22px;
            font-weight: 700
        }

        .event-card-meta {
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .event-card-footer {
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid #F1F1F7;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            line-height: 21px
        }

        .event-card-price {
            font-size: 16px;
            line-height: 24px
        }

        .price-free {
            color: #15803D
        }

        .event-card-left {
            color: #5F6483
        }

        .steps {
            display: flex;
            gap: 32px
        }

        .step {
            width: 362px
        }

        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #F1F0FF;
            color: #3C29B8;
            font-size: 16px;
            line-height: 32px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 12px
        }

        .step-title {
            font-size: 18px;
            line-height: 27px;
            font-weight: 700;
            margin-bottom: 6px
        }

        .step-text {
            font-size: 16px;
            line-height: 24px;
            color: #4B5170
        }

        .organizer-card {
            width: 1152px;
            height: 428px;
            padding: 48px;
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(14, 17, 40, .06), 0 1px 3px rgba(14, 17, 40, .06);
            display: flex;
            gap: 48px;
            align-items: center
        }

        .organizer-copy {
            width: 504px
        }

        .organizer-title {
            margin-top: 16px;
            font-size: 32px;
            line-height: 37px;
            font-weight: 700;
            letter-spacing: -0.6px
        }

        .feature-list {
            list-style: none;
            margin: 24px 0 28px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 16px;
            line-height: 24px;
            color: #4B5170
        }

        .feature-item {
            display: flex;
            gap: 10px
        }

        .feature-dot {
            flex: none;
            width: 6px;
            height: 6px;
            border-radius: 3px;
            background: #0E7490;
            margin-top: 9px
        }

        .approval-panel {
            width: 504px;
            background: #F1F1F7;
            border-radius: 12px;
            padding: 20px
        }

        .panel-label {
            font-size: 12px;
            line-height: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #5F6483
        }

        .status-row {
            display: flex;
            gap: 8px;
            margin: 14px 0 18px
        }

        .event-row {
            margin-top: 8px;
            padding: 12px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            background: #FFFFFF;
            border-radius: 8px;
            font-size: 14px;
            line-height: 21px;
            font-weight: 700
        }

        .panel-note {
            margin: 8px 2px 0;
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }

        .cta {
            width: 1152px;
            height: 186px;
            padding: 0 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #0E1128;
            border-radius: 16px
        }

        .cta-title {
            width: 560px;
            font-size: 32px;
            line-height: 37px;
            font-weight: 700;
            letter-spacing: -0.6px;
            color: #FFFFFF
        }

        .cta-actions {
            display: flex;
            gap: 12px
        }

        .footer {
            width: 1440px;
            height: 147px;
            padding-top: 48px
        }

        .footer .container {
            display: flex;
            justify-content: space-between
        }

        .footer-text {
            margin-top: 8px;
            font-size: 14px;
            line-height: 21px;
            color: #5F6483
        }

        .footer-links {
            display: flex;
            gap: 24px;
            font-size: 14px;
            line-height: 21px;
            color: #4B5170
        }
    </style>
</head>

<body>
    <div class="landing-page">
        <header class="navbar">
            <div class="container">
                <div class="logo"><svg width="30" height="30" viewBox="0 0 32 32" role="img" aria-label="Logo TIXORA">
                        <path
                            d="M8 2h16a6 6 0 0 1 6 6v5.5a2.5 2.5 0 0 0 0 5V24a6 6 0 0 1-6 6H8a6 6 0 0 1-6-6v-5.5a2.5 2.5 0 0 0 0-5V8a6 6 0 0 1 6-6z"
                            fill="#4B34DB" />
                        <path d="M11 11l10 10M21 11L11 21" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round"
                            fill="none" />
                    </svg><span class="logo-text">tixora</span></div>
                <nav class="nav-links"><span>Jelajahi acara</span><span>Cara kerja</span><span>Untuk
                        penyelenggara</span></nav><span class="button ghost-button">Masuk</span><span
                    class="button primary-button">Daftar</span>
            </div>
        </header>
        <section class="hero">
            <div class="container">
                <div class="hero-copy">
                    <div class="hero-badge-row"><span class="badge badge-purple"><span class="badge-dot"></span>Seminar,
                            workshop, konferensi</span></div>
                    <h1 class="hero-title">Temukan acaranya.<br>Simpan tiketnya.<br><span
                            class="hero-title-accent">Masuk tanpa antre.</span></h1>
                    <p class="hero-lead">TIXORA mempertemukan peserta dan penyelenggara acara<br>kampus maupun umum:
                        pendaftaran, tiket QR, dan<br>persetujuan acara dalam satu tempat.</p>
                    <div class="search-bar">
                        <div class="search-field">Cari acara, topik, atau penyelenggara</div>
                        <div class="search-select"><span>Semua kategori</span><svg width="16" height="16"
                                viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M4 6l4 4 4-4" stroke="#5F6483" stroke-width="1.5" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg></div><span class="button button-large primary-button">Cari acara</span>
                    </div>
                    <div class="popular-row"><span>Populer:</span><span class="chip">Seminar</span><span
                            class="chip">Workshop</span><span class="chip">Gratis</span><span class="chip">Akhir pekan
                            ini</span></div>
                </div>
                <div class="hero-visual">
                    <div class="ticket">
                        <div class="poster poster-indigo" style="height:230px">
                            <div class="poster-art"><span class="art-circle-cyan"></span><span
                                    class="art-ring-dark"></span></div><span class="poster-kicker">SABTU · 24 OKT
                                2026</span><span class="poster-title">SENTEK<br>2026</span>
                        </div>
                        <div class="ticket-stub"><span class="ticket-notch-left"></span><span
                                class="ticket-notch-right"></span>
                            <div class="ticket-info"><span class="badge badge-success"><span
                                        class="badge-dot"></span>Sah</span>
                                <h3 class="ticket-title">Seminar Nasional Teknologi Informatika</h3>
                                <p class="ticket-meta">Auditorium Utama, Malang · 08.00 WIB</p>
                                <p class="ticket-id">TXR-2610-0482</p>
                            </div><svg width="84" height="84" viewBox="0 0 9 9" role="img"
                                aria-label="Placeholder kode QR" shape-rendering="crispEdges">
                                <path fill="#0E1128" fill-rule="evenodd"
                                    d="M0 0h3v3H0zM1 1v1h1V1zM6 0h3v3H6zM7 1v1h1V1zM0 6h3v3H0zM1 7v1h1V7z" />
                                <path fill="#0E1128"
                                    d="M4 0h1v1H4zM4 2h1v2H4zM0 4h2v1H0zM3 4h1v1H3zM5 4h2v1H5zM8 4h1v2H8zM4 6h2v1H4zM6 7h1v2H6zM4 8h1v1H4z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="section" style="height:624px">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Acara pilihan minggu ini</h2><span class="button secondary-button">Lihat
                        semua acara</span>
                </div>
                <div class="tabs"><span class="tab tab-active">Semua</span><span class="tab">Seminar</span><span
                        class="tab">Workshop</span><span class="tab">Konferensi</span><span class="tab">Kompetisi</span>
                </div>
                <div class="event-grid">
                    <article class="event-card">
                        <div class="poster poster-indigo" style="height:168px">
                            <div class="poster-art"><span class="art-circle-cyan"></span><span
                                    class="art-ring-dark"></span></div><span class="poster-kicker">SEMINAR</span><span
                                class="poster-title poster-title-card">SENTEK 2026</span>
                        </div>
                        <div class="event-card-body"><span class="badge badge-purple"><span
                                    class="badge-dot"></span>Akan datang</span>
                            <h3 class="event-card-title">Seminar Nasional Teknologi Informatika 2026</h3>
                            <p class="event-card-meta">Sab, 24 Okt 2026 · Auditorium Utama, Malang</p>
                            <div class="event-card-footer"><b class="event-card-price ">Rp75.000</b><span
                                    class="event-card-left">Tersisa 42 kursi</span></div>
                        </div>
                    </article>
                    <article class="event-card">
                        <div class="poster poster-cyan" style="height:168px">
                            <div class="poster-art"><span class="art-circle-indigo"></span><span
                                    class="art-square-ink"></span></div><span class="poster-kicker">WORKSHOP</span><span
                                class="poster-title poster-title-card">UI/UX<br>Dasar</span>
                        </div>
                        <div class="event-card-body"><span class="badge badge-purple"><span
                                    class="badge-dot"></span>Akan datang</span>
                            <h3 class="event-card-title">Workshop UI/UX untuk Pemula: Dari Riset ke Prototipe</h3>
                            <p class="event-card-meta">Min, 1 Nov 2026 · Ruang Kolaborasi, Malang</p>
                            <div class="event-card-footer"><b class="event-card-price ">Rp150.000</b><span
                                    class="event-card-left">Tersisa 18 kursi</span></div>
                        </div>
                    </article>
                    <article class="event-card">
                        <div class="poster poster-ink" style="height:168px">
                            <div class="poster-art"><span class="art-stripes"><svg class="poster-stripes" width="168"
                                        height="109" viewBox="0 0 168 109" aria-hidden="true">
                                        <line x1="127.3" y1="-256.3" x2="-126.3" y2="287.4" stroke="#4B34DB"
                                            stroke-width="14" />
                                        <line x1="158.1" y1="-242.0" x2="-95.5" y2="301.8" stroke="#4B34DB"
                                            stroke-width="14" />
                                        <line x1="188.9" y1="-227.6" x2="-64.7" y2="316.2" stroke="#4B34DB"
                                            stroke-width="14" />
                                        <line x1="219.7" y1="-213.2" x2="-33.9" y2="330.5" stroke="#4B34DB"
                                            stroke-width="14" />
                                        <line x1="250.5" y1="-198.9" x2="-3.1" y2="344.9" stroke="#4B34DB"
                                            stroke-width="14" />
                                        <line x1="281.3" y1="-184.5" x2="27.8" y2="359.3" stroke="#4B34DB"
                                            stroke-width="14" />
                                    </svg></span></div><span class="poster-kicker">KONFERENSI</span><span
                                class="poster-title poster-title-card">EduDigital<br>Jatim</span>
                        </div>
                        <div class="event-card-body"><span class="badge badge-purple"><span
                                    class="badge-dot"></span>Akan datang</span>
                            <h3 class="event-card-title">Konferensi Pendidikan Digital Jawa Timur</h3>
                            <p class="event-card-meta">12–13 Nov 2026 · Gedung Serbaguna Kampus</p>
                            <div class="event-card-footer"><b class="event-card-price ">Rp250.000</b><span
                                    class="event-card-left">Tersisa 120 kursi</span></div>
                        </div>
                    </article>
                    <article class="event-card">
                        <div class="poster poster-amber" style="height:168px">
                            <div class="poster-art"><span class="art-circle-ink"></span></div><span
                                class="poster-kicker">KULIAH UMUM</span><span
                                class="poster-title poster-title-card">Mulai dari<br>Kampus</span>
                        </div>
                        <div class="event-card-body"><span class="badge badge-warning"><span
                                    class="badge-dot"></span>Hampir penuh</span>
                            <h3 class="event-card-title">Kuliah Umum Kewirausahaan: Memulai dari Kampus</h3>
                            <p class="event-card-meta">Sab, 17 Okt 2026 · Aula Fakultas Ekonomi</p>
                            <div class="event-card-footer"><b class="event-card-price price-free">Gratis</b><span
                                    class="event-card-left">Tersisa 6 kursi</span></div>
                        </div>
                    </article>
                </div>
            </div>
        </section>
        <section class="section" style="height:322px">
            <div class="container">
                <div class="section-header" style="height:37px">
                    <h2 class="section-title">Dari pendaftaran sampai pintu masuk</h2>
                </div>
                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Temukan acara</h3>
                        <p class="step-text">Cari berdasarkan kategori, tanggal, atau harga. Status kursi dan jadwal
                            terlihat sebelum Anda mendaftar.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Daftar atau beli tiket</h3>
                        <p class="step-text">Pilih kategori tiket, isi data peserta, lalu terima konfirmasi dan tiket
                            digital Anda.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Tunjukkan kode QR</h3>
                        <p class="step-text">Tiket tersimpan di akun Anda. Petugas memindai kode QR dan status tiket
                            langsung berubah.</p>
                    </div>
                </div>
            </div>
        </section>
        <section class="section" style="height:540px">
            <div class="container">
                <div class="organizer-card">
                    <div class="organizer-copy"><span class="badge badge-purple"><span class="badge-dot"></span>Untuk
                            penyelenggara</span>
                        <h2 class="organizer-title">Kelola acara dari draf sampai terbit.</h2>
                        <ul class="feature-list">
                            <li class="feature-item"><span class="feature-dot"></span><span>Wizard pembuatan acara
                                    dengan rundown dan kategori tiket.</span></li>
                            <li class="feature-item"><span class="feature-dot"></span><span>Pantau pendaftar, kuota, dan
                                    anggaran (RAB) di satu dasbor.</span></li>
                            <li class="feature-item"><span class="feature-dot"></span><span>Ajukan ke admin, dapatkan
                                    keputusan beserta alasannya,<br>lalu revisi bila perlu.</span></li>
                        </ul><span class="button button-large primary-button">Ajukan acara Anda</span>
                    </div>
                    <div class="approval-panel">
                        <div class="panel-label">ALUR PERSETUJUAN</div>
                        <div class="status-row"><span class="badge badge-neutral"><span
                                    class="badge-dot"></span>Draf</span><span class="badge badge-warning"><span
                                    class="badge-dot"></span>Menunggu tinjauan</span><span
                                class="badge badge-info"><span class="badge-dot"></span>Disetujui</span><span
                                class="badge badge-success"><span class="badge-dot"></span>Terbit</span></div>
                        <div class="event-row"><span>Lomba Esai Nasional Mahasiswa</span><span
                                class="badge badge-error"><span class="badge-dot"></span>Ditolak</span></div>
                        <p class="panel-note">Alasan: rincian anggaran belum dilampirkan. Revisi lalu ajukan ulang.</p>
                        <div class="event-row"><span>Konferensi Pendidikan Digital Jawa Timur</span><span
                                class="badge badge-warning"><span class="badge-dot"></span>Menunggu tinjauan</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section style="width:1440px;height:210px">
            <div class="container">
                <div class="cta">
                    <h2 class="cta-title">Punya acara berikutnya? Mulai dari satu draf.</h2>
                    <div class="cta-actions"><span class="button button-large white-button">Buat akun
                            penyelenggara</span><span class="button button-large outline-light-button">Jelajahi
                            acara</span></div>
                </div>
            </div>
        </section>
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