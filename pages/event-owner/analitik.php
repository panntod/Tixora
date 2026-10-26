<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <title>TIXORA — Organizer — Analitik</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/event-owner/styles.css">
</head>

<body>
    <div class="frame" style="width:1440px;height:715px">
        <div class="app">
            <aside class="sidebar">
                <div class="logo"><img class="logo-img" src="<?= BASE_URL ?>/assets/logo.png" alt="Logo TIXORA"></div>
                <span class="side-item">Dasbor</span><span class="side-item side-item-active">Acara saya</span><span
                    class="side-item">Buat acara</span><span class="side-item">Pengaturan</span><span
                    class="side-item">Keluar</span>
                <div class="side-user"><b>Dimas Prakoso</b>HMTI Fakultas Ilmu Komputer</div>
            </aside>
            <main class="main">
                <p class="crumb">Acara saya / SENTEK 2026 / Analitik</p>
                <div class="head">
                    <div>
                        <h1 class="page-title">Analitik acara</h1>
                        <p class="page-subtitle">1–10 Okt 2026</p>
                    </div>
                    <div class="head-actions"><span class="button secondary-button">Ekspor CSV</span><span
                            class="button primary-button">Unduh laporan PDF</span></div>
                </div>
                <div class="tabs" style="margin-bottom:24px"><span class="tab">Ringkasan</span><span
                        class="tab">Tiket</span><span class="tab">Rundown</span><span class="tab">Peserta</span><span
                        class="tab">RAB</span><span class="tab">Panitia</span><span
                        class="tab tab-active">Analitik</span><span class="tab">Pengajuan</span></div>
                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">PENDAPATAN</div>
                        <div class="metric-value" style="">Rp14.750.000</div>
                        <div class="metric-note">Tiket Umum, Mahasiswa, VIP</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">TIKET TERJUAL</div>
                        <div class="metric-value" style="">196</div>
                        <div class="metric-note">78% dari kapasitas</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">PENDAFTARAN / HARI</div>
                        <div class="metric-value" style="">14</div>
                        <div class="metric-note">Rata-rata 10 hari terakhir</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">MENUNGGU PEMBAYARAN</div>
                        <div class="metric-value" style="">9</div>
                        <div class="metric-note">Rp675.000 tertunda</div>
                    </div>
                </div>
                <div class="cols">
                    <div class="card col-wide" style="box-sizing:border-box">
                        <h2 class="card-title">Pendaftaran per hari</h2><svg width="616" height="190"
                            viewBox="0 0 580 190" role="img"
                            aria-label="Grafik batang pendaftaran per hari, puncak 26 pendaftar">
                            <line x1="0" y1="170" x2="580" y2="170" stroke="#E4E4EE" />
                            <rect x="16" y="146" width="24" height="24" rx="4" fill="#C9C5FF" />
                            <rect x="56" y="128" width="24" height="42" rx="4" fill="#C9C5FF" />
                            <rect x="96" y="116" width="24" height="54" rx="4" fill="#C9C5FF" />
                            <rect x="136" y="134" width="24" height="36" rx="4" fill="#C9C5FF" />
                            <rect x="176" y="98" width="24" height="72" rx="4" fill="#C9C5FF" />
                            <rect x="216" y="80" width="24" height="90" rx="4" fill="#C9C5FF" />
                            <rect x="256" y="104" width="24" height="66" rx="4" fill="#C9C5FF" />
                            <rect x="296" y="62" width="24" height="108" rx="4" fill="#C9C5FF" />
                            <rect x="336" y="38" width="24" height="132" rx="4" fill="#C9C5FF" />
                            <rect x="376" y="86" width="24" height="84" rx="4" fill="#C9C5FF" />
                            <rect x="416" y="50" width="24" height="120" rx="4" fill="#C9C5FF" />
                            <rect x="456" y="14" width="24" height="156" rx="4" fill="#4B34DB" />
                            <rect x="496" y="56" width="24" height="114" rx="4" fill="#C9C5FF" />
                            <rect x="536" y="92" width="24" height="78" rx="4" fill="#C9C5FF" />
                        </svg>
                        <p class="help">Puncak: 26 pendaftar pada 5 Okt setelah publikasi ke media sosial.</p>
                    </div>
                    <div class="card col-narrow" style="box-sizing:border-box">
                        <h2 class="card-title">Pendapatan per kategori</h2>
                        <div style="margin-bottom:16px">
                            <div class="legend-row"><b>Umum</b><span class="slate">Rp5.850.000</span></div>
                            <div class="progress">
                                <div class="progress-fill" style="width:40%"></div>
                            </div>
                        </div>
                        <div style="margin-bottom:16px">
                            <div class="legend-row"><b>Mahasiswa</b><span class="slate">Rp4.400.000</span></div>
                            <div class="progress">
                                <div class="progress-fill" style="width:30%"></div>
                            </div>
                        </div>
                        <div style="margin-bottom:16px">
                            <div class="legend-row"><b>VIP</b><span class="slate">Rp4.500.000</span></div>
                            <div class="progress">
                                <div class="progress-fill" style="width:31%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>