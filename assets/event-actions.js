/**
 * TIXORA - aksi halaman acara: bagikan tautan, pengingat kalender (.ics), dan popup pemesanan berhasil.
 * Tanpa dependensi; semua elemen dikenali lewat atribut data-* / kelas.
 */
(function () {
    'use strict';

    // ---------- Pengingat kalender (.ics) ----------
    function icsEscape(s) {
        return String(s || '').replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/,/g, '\\,').replace(/\r?\n/g, '\\n');
    }

    function fold(line) {
        var out = [line.slice(0, 70)];
        line = line.slice(70);
        while (line.length) {
            out.push(' ' + line.slice(0, 69));
            line = line.slice(69);
        }
        return out.join('\r\n');
    }

    function buildIcs(d) {
        var stamp = new Date().toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');
        var lines = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//TIXORA//Event//ID', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' + d.uid + '@tixora',
            'DTSTAMP:' + stamp,
            'DTSTART:' + d.start,
            'DTEND:' + d.end,
            'SUMMARY:' + icsEscape(d.title),
            'LOCATION:' + icsEscape(d.location),
            'DESCRIPTION:' + icsEscape(d.desc),
            'URL:' + d.url,
            'BEGIN:VALARM', 'TRIGGER:-P1D', 'ACTION:DISPLAY', 'DESCRIPTION:' + icsEscape('Besok: ' + d.title), 'END:VALARM',
            'BEGIN:VALARM', 'TRIGGER:-PT1H', 'ACTION:DISPLAY', 'DESCRIPTION:' + icsEscape('1 jam lagi: ' + d.title), 'END:VALARM',
            'END:VEVENT', 'END:VCALENDAR'
        ];
        return lines.map(fold).join('\r\n') + '\r\n';
    }

    document.querySelectorAll('.js-ics').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var d = {
                title: btn.getAttribute('data-title'),
                start: btn.getAttribute('data-start'),
                end: btn.getAttribute('data-end'),
                location: btn.getAttribute('data-location'),
                desc: btn.getAttribute('data-desc'),
                url: btn.getAttribute('data-url'),
                uid: btn.getAttribute('data-uid')
            };
            if (!d.start || !d.end) return;
            var blob = new Blob([buildIcs(d)], { type: 'text/calendar;charset=utf-8' });
            var href = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = href;
            a.download = (d.title || 'acara').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 50) + '.ics';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(function () { URL.revokeObjectURL(href); }, 1000);
        });
    });

    // ---------- Bagikan tautan ----------
    var note = document.getElementById('share-note');

    document.querySelectorAll('[data-share-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-url');
            function ok() { if (note) note.textContent = 'Tautan acara disalin.'; }
            function manual() { window.prompt('Salin tautan acara:', url); }
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(ok, manual);
                return;
            }
            try {
                var t = document.createElement('textarea');
                t.value = url;
                t.style.position = 'fixed';
                t.style.opacity = '0';
                document.body.appendChild(t);
                t.select();
                var done = document.execCommand('copy');
                document.body.removeChild(t);
                done ? ok() : manual();
            } catch (e) { manual(); }
        });
    });

    document.querySelectorAll('[data-share-native]').forEach(function (btn) {
        if (!navigator.share) return;
        btn.hidden = false;
        btn.addEventListener('click', function () {
            navigator.share({
                title: btn.getAttribute('data-title'),
                text: btn.getAttribute('data-text'),
                url: btn.getAttribute('data-url')
            }).catch(function () { /* dibatalkan pengguna */ });
        });
    });

    // ---------- Popup pemesanan berhasil (tampil sekali per tab) ----------
    var modal = document.getElementById('booking-modal');
    if (modal) {
        var key = 'tixora_booking_' + modal.getAttribute('data-key');
        var seen = false;
        try { seen = sessionStorage.getItem(key) === '1'; } catch (e) { /* abaikan */ }
        var close = function () { modal.classList.remove('is-open'); };
        if (!seen) {
            modal.classList.add('is-open');
            try { sessionStorage.setItem(key, '1'); } catch (e) { /* abaikan */ }
            var first = modal.querySelector('.primary-button');
            if (first) first.focus();
        }
        modal.addEventListener('click', function (e) {
            if (e.target === modal || (e.target.closest && e.target.closest('[data-close]'))) close();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') close();
        });
    }
})();
