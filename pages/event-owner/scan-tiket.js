/*
 * TIXORA — Scan tiket (Event Owner)
 *
 * Membaca QR/barcode pada e-ticket mahasiswa lalu memverifikasinya ke
 * POST /api/scanner/scan. Tiga cara input, semuanya berujung ke submit():
 *   1. Kamera       — BarcodeDetector bawaan browser, atau jsQR bila tidak tersedia.
 *   2. Foto QR      — untuk perangkat tanpa kamera / koneksi non-HTTPS.
 *   3. Kode manual  — juga dipakai scanner USB/Bluetooth (mengetik kode + Enter).
 *
 * Semua data peserta ditulis lewat textContent (bukan innerHTML) agar aman dari XSS.
 */
(function () {
    'use strict';

    var root = document.getElementById('scanner-app');
    if (!root) return;

    var cfg = {
        api: root.getAttribute('data-api'),
        eventId: root.getAttribute('data-event'),
        jsqrLocal: root.getAttribute('data-jsqr')
    };

    // Sumber pustaka jsQR (dipakai hanya bila browser tak punya BarcodeDetector).
    // Untuk presentasi offline: simpan jsQR.min.js ke assets/ (lihat README).
    var JSQR_SOURCES = [
        cfg.jsqrLocal,
        'https://cdnjs.cloudflare.com/ajax/libs/jsQR/1.4.0/jsQR.min.js',
        'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js'
    ].filter(Boolean);

    var SAME_CODE_GAP_MS = 2500;   // kode yang sama baru dibaca lagi setelah hilang dari kamera selama ini
    var FRAME_INTERVAL_MS = 120;   // jeda antar pembacaan frame kamera
    var TOAST_MS = 3500;
    var RECENT_MAX = 8;

    var IDS = ['scan-video', 'scan-stage', 'scan-cam-error', 'btn-camera', 'cam-select', 'btn-upload',
        'file-qr', 'manual-form', 'manual-code', 'scan-result', 'scan-result-icon',
        'scan-result-kicker', 'scan-result-title', 'scan-result-msg', 'scan-detail', 'recent-list',
        'recent-empty', 'scan-toast', 'stat-hadir', 'stat-belum', 'stat-total', 'stat-note'];
    var els = {};
    IDS.forEach(function (id) { els[id] = document.getElementById(id); });

    var ICONS = {
        ok: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
        warn: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v6"/><path d="M12 17h.01"/></svg>',
        bad: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>',
        loading: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M12 3a9 9 0 1 0 9 9"/></svg>',
        idle: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/><path d="M8 12h8"/></svg>'
    };

    /* ------------------------------------------------------------------ */
    /* Pembaca QR (decoder)                                                */
    /* ------------------------------------------------------------------ */

    var decoderPromise = null;

    function getDecoder() {
        if (!decoderPromise) {
            decoderPromise = nativeDecoder().then(function (native) {
                return native || jsqrDecoder();
            });
            // Jangan simpan kegagalan: coba lagi pada klik berikutnya (mis. internet baru nyala).
            decoderPromise.catch(function () { decoderPromise = null; });
        }
        return decoderPromise;
    }

    function nativeDecoder() {
        return Promise.resolve().then(function () {
            if (!('BarcodeDetector' in window)) return null;
            var supported = typeof window.BarcodeDetector.getSupportedFormats === 'function'
                ? window.BarcodeDetector.getSupportedFormats()
                : ['qr_code'];
            return Promise.resolve(supported).then(function (formats) {
                if (formats.indexOf('qr_code') === -1) return null;
                // QR untuk tiket Tixora; format barcode 1D ikut dibaca bila didukung perangkat.
                var wanted = ['qr_code', 'code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'itf']
                    .filter(function (f) { return formats.indexOf(f) !== -1; });
                var detector = new window.BarcodeDetector({ formats: wanted });
                return {
                    name: 'BarcodeDetector',
                    decode: function (src) {
                        return detector.detect(src).then(function (found) {
                            return found.length ? String(found[0].rawValue || '') : null;
                        });
                    }
                };
            });
        }).catch(function () { return null; });
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.onload = resolve;
            s.onerror = function () { s.remove(); reject(new Error('gagal memuat ' + src)); };
            document.head.appendChild(s);
        });
    }

    function jsqrDecoder() {
        function tryLoad(i) {
            if (typeof window.jsQR === 'function') return Promise.resolve();
            if (i >= JSQR_SOURCES.length) {
                var err = new Error('jsQR tidak tersedia');
                err.userMessage = 'Pembaca QR gagal dimuat (perlu koneksi internet sekali, atau simpan jsQR.min.js di folder assets). ' +
                    'Sementara, gunakan kolom kode tiket.';
                return Promise.reject(err);
            }
            return loadScript(JSQR_SOURCES[i]).then(function () {
                if (typeof window.jsQR !== 'function') throw new Error('jsQR tidak valid');
            }).catch(function () { return tryLoad(i + 1); });
        }

        return tryLoad(0).then(function () {
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d', { willReadFrequently: true });
            return {
                name: 'jsQR',
                decode: function (src, opts) {
                    opts = opts || {};
                    var sw = src.videoWidth || src.naturalWidth || src.width;
                    var sh = src.videoHeight || src.naturalHeight || src.height;
                    if (!sw || !sh) return Promise.resolve(null);
                    var scale = Math.min(1, (opts.maxSide || 800) / Math.max(sw, sh));
                    var w = Math.max(1, Math.round(sw * scale));
                    var h = Math.max(1, Math.round(sh * scale));
                    canvas.width = w;
                    canvas.height = h;
                    ctx.drawImage(src, 0, 0, w, h);
                    var image = ctx.getImageData(0, 0, w, h);
                    var res = window.jsQR(image.data, w, h, { inversionAttempts: opts.invert ? 'attemptBoth' : 'dontInvert' });
                    return Promise.resolve(res && res.data ? String(res.data) : null);
                }
            };
        });
    }

    /* ------------------------------------------------------------------ */
    /* Kamera                                                              */
    /* ------------------------------------------------------------------ */

    var stream = null;
    var decoder = null;
    var scanning = false;
    var rafId = 0;
    var lastTick = 0;
    var framePending = false;
    var lastCode = '';
    var lastSeenAt = 0;

    function camError(message) {
        els['scan-cam-error'].textContent = message || '';
        els['scan-cam-error'].style.display = message ? 'block' : 'none';
    }

    function setCameraUI(on) {
        els['scan-stage'].classList.toggle('is-live', on);
        els['btn-camera'].textContent = on ? 'Hentikan kamera' : 'Mulai kamera';
        els['btn-camera'].classList.toggle('primary-button', !on);
        els['btn-camera'].classList.toggle('secondary-button', on);
    }

    function describeCameraError(err) {
        if (err && err.userMessage) return err.userMessage;
        var name = err && err.name;
        if (name === 'NotAllowedError' || name === 'SecurityError') {
            return 'Izin kamera ditolak. Izinkan akses kamera lewat ikon gembok di address bar, lalu coba lagi.';
        }
        if (name === 'NotFoundError' || name === 'OverconstrainedError') {
            return 'Kamera tidak ditemukan di perangkat ini. Gunakan "Unggah foto QR" atau kolom kode tiket.';
        }
        if (name === 'NotReadableError') {
            return 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.';
        }
        return 'Kamera tidak dapat dibuka. Gunakan "Unggah foto QR" atau kolom kode tiket.';
    }

    function startCamera(deviceId) {
        camError('');
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            camError(window.isSecureContext === false
                ? 'Kamera hanya bisa dipakai lewat HTTPS atau http://localhost. Gunakan "Unggah foto QR" atau kolom kode tiket.'
                : 'Browser ini tidak mendukung akses kamera. Gunakan "Unggah foto QR" atau kolom kode tiket.');
            return Promise.resolve();
        }
        stopCamera();

        var video = { width: { ideal: 1280 }, height: { ideal: 720 } };
        if (deviceId) video.deviceId = { exact: deviceId };
        else video.facingMode = { ideal: 'environment' };

        els['btn-camera'].disabled = true;
        return getDecoder().then(function (dec) {
            decoder = dec;
            return navigator.mediaDevices.getUserMedia({ video: video, audio: false });
        }).then(function (s) {
            stream = s;
            els['scan-video'].srcObject = s;
            return els['scan-video'].play().catch(function () { /* autoplay terputus: abaikan */ });
        }).then(function () {
            setCameraUI(true);
            scanning = true;
            lastTick = 0;
            rafId = requestAnimationFrame(loop);
            return listCameras();
        }).catch(function (err) {
            stopCamera();
            camError(describeCameraError(err));
        }).then(function () {
            els['btn-camera'].disabled = false;
        });
    }

    function stopCamera() {
        scanning = false;
        if (rafId) cancelAnimationFrame(rafId);
        rafId = 0;
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        els['scan-video'].srcObject = null;
        setCameraUI(false);
    }

    function listCameras() {
        if (!navigator.mediaDevices.enumerateDevices) return null;
        return navigator.mediaDevices.enumerateDevices().then(function (devices) {
            var cams = devices.filter(function (d) { return d.kind === 'videoinput'; });
            var select = els['cam-select'];
            if (cams.length < 2) { select.style.display = 'none'; return; }
            var track = stream && stream.getVideoTracks()[0];
            var current = track && track.getSettings ? track.getSettings().deviceId : '';
            while (select.firstChild) select.removeChild(select.firstChild);
            cams.forEach(function (cam, i) {
                var opt = document.createElement('option');
                opt.value = cam.deviceId;
                opt.textContent = cam.label || ('Kamera ' + (i + 1));
                opt.selected = cam.deviceId === current;
                select.appendChild(opt);
            });
            select.style.display = '';
        }).catch(function () { /* daftar kamera opsional */ });
    }

    function loop(ts) {
        if (!scanning) return;
        rafId = requestAnimationFrame(loop);
        if (framePending || (ts - lastTick) < FRAME_INTERVAL_MS) return;
        lastTick = ts;
        if (els['scan-video'].readyState < 2) return;
        framePending = true;
        decoder.decode(els['scan-video'], { maxSide: 800 }).then(function (value) {
            if (value) onCameraRead(value);
        }).catch(function () { /* frame gagal dibaca: lanjut ke frame berikutnya */ })
            .then(function () { framePending = false; });
    }

    // QR yang masih di depan kamera terbaca berkali-kali per detik. Tanpa penjagaan ini
    // pembacaan kedua akan menimpa hasil "Terdaftar" dengan "Sudah digunakan".
    function onCameraRead(value) {
        var now = Date.now();
        if (value === lastCode) {
            var stillInView = (now - lastSeenAt) < SAME_CODE_GAP_MS;
            lastSeenAt = now;
            if (stillInView) return;
        }
        if (busy) return;
        lastCode = value;
        lastSeenAt = now;
        submit(value);
    }

    /* ------------------------------------------------------------------ */
    /* Kirim ke server                                                     */
    /* ------------------------------------------------------------------ */

    var busy = false;

    function submit(raw) {
        raw = String(raw || '').trim();
        if (!raw || busy) return Promise.resolve();
        busy = true;
        setResult('loading', 'MEMERIKSA…', 'Memverifikasi tiket', 'Menghubungi server, mohon tunggu sebentar.');

        return fetch(cfg.api, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ kode_tiket: raw, event_id: cfg.eventId })
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (json) {
                return { status: res.status, ok: res.ok, json: json || {} };
            });
        }).then(handleResponse, function () {
            showOutcome('bad', 'TIDAK TERHUBUNG', 'Server tidak dapat dihubungi',
                'Periksa koneksi internet / server, lalu pindai ulang tiketnya.', null);
        }).catch(function (err) {
            if (window.console) console.error('[TIXORA scan]', err);
        }).then(function () { busy = false; });
    }

    var ERROR_TITLES = {
        QR_TIDAK_VALID: ['QR TIDAK VALID', 'QR tidak valid'],
        TIDAK_DITEMUKAN: ['TIDAK TERDAFTAR', 'Tiket tidak ditemukan'],
        BUKAN_ACARA_ANDA: ['DITOLAK', 'Bukan tiket acara Anda'],
        ACARA_LAIN: ['ACARA BERBEDA', 'Tiket untuk acara lain'],
        TIDAK_AKTIF: ['TIDAK BISA MASUK', 'Tiket tidak aktif'],
        SERVER_ERROR: ['GANGGUAN SERVER', 'Gagal memproses tiket']
    };

    function handleResponse(r) {
        var json = r.json;
        var data = json.data || {};
        var reason = data.reason || '';
        var message = json.message || 'Terjadi kesalahan, coba lagi.';

        if (r.ok && json.success) {
            showOutcome('ok', 'TERDAFTAR', data.nama_peserta || 'Peserta', message, data);
            bumpStats();
            addRecent(data);
            return;
        }
        if (reason === 'SUDAH_DIGUNAKAN' || reason === 'BARU_DIGUNAKAN') {
            var when = data.waktu_check_in ? ' Check-in sebelumnya pukul ' + clock(data.waktu_check_in) + '.' : '';
            showOutcome('warn', 'SUDAH CHECK-IN', data.nama_peserta || 'Tiket sudah dipakai',
                'Tiket ini sudah digunakan.' + when + ' Jangan izinkan masuk dua kali.', data);
            return;
        }
        if (r.status === 401) {
            showOutcome('bad', 'SESI BERAKHIR', 'Silakan login ulang', message, null);
            return;
        }
        var titles = ERROR_TITLES[reason] || ['DITOLAK', 'Tiket ditolak'];
        showOutcome('bad', titles[0], titles[1], message, data.nama_peserta ? data : null);
    }

    /* ------------------------------------------------------------------ */
    /* Tampilan hasil                                                      */
    /* ------------------------------------------------------------------ */

    function clock(value) {
        var m = /(\d{2}):(\d{2})/.exec(String(value || ''));
        if (m) return m[1] + '.' + m[2];
        var d = new Date();
        return pad(d.getHours()) + '.' + pad(d.getMinutes());
    }

    function pad(n) { return n < 10 ? '0' + n : String(n); }

    function setResult(state, kicker, title, message, details) {
        var box = els['scan-result'];
        box.className = 'card scan-result is-' + state;
        els['scan-result-icon'].innerHTML = ICONS[state] || ICONS.idle;   // ikon statis, bukan data pengguna
        els['scan-result-kicker'].textContent = kicker;
        els['scan-result-title'].textContent = title;
        els['scan-result-msg'].textContent = message;

        var dl = els['scan-detail'];
        while (dl.firstChild) dl.removeChild(dl.firstChild);
        var rows = (details || []).filter(function (row) { return row[1] !== null && row[1] !== undefined; });
        rows.forEach(function (row) {
            var dt = document.createElement('dt');
            dt.textContent = row[0];
            var dd = document.createElement('dd');
            dd.textContent = row[1] === '' ? '-' : String(row[1]);
            dl.appendChild(dt);
            dl.appendChild(dd);
        });
        dl.style.display = rows.length ? '' : 'none';

        if (state !== 'loading') {
            box.classList.remove('is-pop');
            void box.offsetWidth;   // restart animasi
            box.classList.add('is-pop');
        }
    }

    // Data identitas yang kosong di database (NULL) tetap tampil sebagai "-";
    // baris yang memang opsional (acara, waktu) dihilangkan lewat null.
    function detailRows(d) {
        function val(v) { return v === null || v === undefined ? '' : v; }
        return [
            ['Surel', val(d.email_peserta)],
            ['Institusi', val(d.institusi_peserta)],
            ['WhatsApp', val(d.whatsapp_peserta)],
            ['Kategori tiket', val(d.jenis_tiket)],
            ['No. tiket', val(d.kode_tiket)],
            ['Acara tiket', d.judul_event || null],
            ['Waktu check-in', d.waktu_check_in ? clock(d.waktu_check_in) : null]
        ];
    }

    function showOutcome(state, kicker, title, message, data) {
        setResult(state, kicker, title, message, data ? detailRows(data) : null);
        var short = state === 'ok' ? 'Terdaftar — ' + title : kicker + ' — ' + title;
        toast(state, short);
    }

    var toastTimer = 0;
    function toast(state, text) {
        var t = els['scan-toast'];
        t.className = 'scan-toast is-' + state;
        t.textContent = text;
        t.hidden = false;
    }

    function toInt(el) { return parseInt(el.textContent, 10) || 0; }

    function bumpStats() {
        var hadir = toInt(els['stat-hadir']) + 1;
        var belum = Math.max(0, toInt(els['stat-belum']) - 1);
        var total = Math.max(hadir + belum, toInt(els['stat-total']));
        els['stat-hadir'].textContent = hadir;
        els['stat-belum'].textContent = belum;
        els['stat-total'].textContent = total;
        els['stat-note'].textContent = (total ? Math.round(hadir / total * 100) : 0) + '% dari tiket sah';
    }

    function addRecent(d) {
        var li = document.createElement('li');
        li.className = 'is-new';
        var left = document.createElement('div');
        var name = document.createElement('b');
        name.textContent = d.nama_peserta || '-';
        var sub = document.createElement('div');
        sub.className = 'cell-sub';
        sub.textContent = [d.jenis_tiket, d.kode_tiket].filter(Boolean).join(' · ');
        left.appendChild(name);
        left.appendChild(sub);
        var time = document.createElement('span');
        time.className = 'scan-recent-time';
        time.textContent = clock(d.waktu_check_in);
        li.appendChild(left);
        li.appendChild(time);

        var list = els['recent-list'];
        list.insertBefore(li, list.firstChild);
        while (list.children.length > RECENT_MAX) list.removeChild(list.lastChild);
        els['recent-empty'].style.display = 'none';
    }

    /* ------------------------------------------------------------------ */
    /* Foto QR & input manual                                              */
    /* ------------------------------------------------------------------ */

    function loadBitmap(file) {
        if (window.createImageBitmap) return createImageBitmap(file);
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('gambar tidak terbaca')); };
            img.src = url;
        });
    }

    function decodeFile(dec, file) {
        return loadBitmap(file).then(function (bmp) {
            return dec.decode(bmp, { maxSide: 1400, invert: true }).then(function (value) {
                return value || dec.decode(bmp, { maxSide: 700, invert: true });
            }).then(function (value) {
                if (bmp.close) bmp.close();
                return value;
            });
        });
    }

    els['btn-camera'].addEventListener('click', function () {
        if (scanning) stopCamera(); else startCamera();
    });

    els['cam-select'].addEventListener('change', function () {
        startCamera(els['cam-select'].value);
    });

    els['btn-upload'].addEventListener('click', function () { els['file-qr'].click(); });

    els['file-qr'].addEventListener('change', function () {
        var file = els['file-qr'].files && els['file-qr'].files[0];
        els['file-qr'].value = '';
        if (!file) return;
        setResult('loading', 'MEMBACA FOTO…', 'Mencari QR pada foto', 'Mohon tunggu sebentar.');
        getDecoder().then(function (dec) {
            return decodeFile(dec, file);
        }).then(function (value) {
            if (!value) {
                showOutcome('bad', 'QR TIDAK TERBACA', 'QR tidak ditemukan pada foto',
                    'Pastikan foto tajam dan QR terlihat utuh, lalu coba lagi atau ketik kode tiketnya.', null);
                return;
            }
            return submit(value);
        }).catch(function (err) {
            showOutcome('bad', 'GAGAL MEMBACA', 'Foto tidak dapat diproses',
                (err && err.userMessage) || 'Coba foto lain atau ketik kode tiketnya.', null);
        });
    });

    els['manual-form'].addEventListener('submit', function (e) {
        e.preventDefault();
        var input = els['manual-code'];
        var value = input.value;
        if (!value.trim()) { input.focus(); return; }
        submit(value).then(function () {
            input.value = '';
            input.focus();
        });
    });

    window.addEventListener('pagehide', stopCamera);

    // Di komputer, langsung siap menerima scanner USB (yang "mengetik" kode + Enter).
    if (!(window.matchMedia && window.matchMedia('(pointer: coarse)').matches)) {
        els['manual-code'].focus();
    }
    setResult('idle', 'MENUNGGU PEMINDAIAN', 'Belum ada tiket dipindai',
        'Hasil validasi dan identitas peserta akan tampil di sini.', null);
    els['scan-result'].classList.remove('is-pop');
})();