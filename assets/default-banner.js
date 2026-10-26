/*!
 * TIXORA — Default Banner Generator
 * Membuat banner bawaan (4 varian) untuk acara yang belum punya poster.
 *
 * PEMAKAIAN
 * ---------------------------------------------------------------
 * 1) Atribut data (disarankan, tanpa request ke server):
 *
 *    <img data-default-banner="1"
 *         data-kicker="SEMINAR"
 *         data-title="SENTEK 2026">
 *
 *    - data-default-banner : 1-4, atau "auto" (dipilih dari data-seed / judul)
 *    - data-kicker         : teks kecil di atas judul (kategori), otomatis huruf besar
 *    - data-title          : judul poster. Pisahkan baris dengan "|" atau "\n".
 *                            Tanpa pemisah => dibungkus otomatis.
 *    - data-dimmed="true"  : tampilan abu-abu (acara selesai / habis / batal)
 *    - data-seed           : (opsional) untuk mode "auto", mis. id acara
 *    - data-layout         : card (default, 270x168) | wide (1152x320, detail acara)
 *                            | ticket (390x200, e-ticket) | thumb (132x100, daftar tiket).
 *                            Seni diskalakan mengikuti tinggi dan tetap menempel di kanan.
 *
 *    Elemen selain <img> (div, dll) akan dipasangi background-image.
 *
 * 2) Pola URL aset (cocok kalau banner_url di database berbentuk path):
 *
 *    <img src="/assets/default-banner/3?kicker=KULIAH%20UMUM&title=Mulai%20dari|Kampus">
 *
 *    Catatan: browser sempat meminta URL itu sebelum diganti, jadi bisa muncul
 *    404 di Network tab. Untuk menghindarinya pakai cara nomor 1.
 *
 * 3) Lewat JavaScript:
 *
 *    DefaultBanner.svg(2, {kicker:'WORKSHOP', title:'UI/UX|Dasar'})      // string <svg>
 *    DefaultBanner.dataUri(2, {...})                                    // data:image/svg+xml
 *    DefaultBanner.render(element)                                      // render ulang 1 elemen
 *    DefaultBanner.pick('event-123')                                    // -> 1..4 (stabil per seed)
 *    DefaultBanner.init(rootElement)                                    // scan manual
 *
 * Elemen yang ditambahkan belakangan (mis. "Muat lebih banyak") otomatis diproses.
 */
(function (root) {
  'use strict';

  // ---------- Ukuran & warna (diambil dari desain kartu 270 x 168) ----------
  var W = 270, H = 168, PAD = 18;
  var FONT = 'Arial,Helvetica,sans-serif';
  var C = {
    indigo: '#4B34DB',
    ink:    '#0E1128',
    cyan:   '#DDF4F9',
    amber:  '#F6B94A',
    sky:    '#5EC8E5',
    ring:   '#1C1458',
    white:  '#FFFFFF'
  };

  // Garis diagonal varian 4 (koordinat sama dengan desain)
  var STRIPES = [
    [127.3, -256.3, -126.3, 287.4], [158.1, -242.0, -95.5, 301.8],
    [188.9, -227.6, -64.7, 316.2],  [219.7, -213.2, -33.9, 330.5],
    [250.5, -198.9, -3.1, 344.9],   [281.3, -184.5, 27.8, 359.3]
  ];

  function stripes() {
    var s = '<svg x="102" y="0" width="168" height="109" viewBox="0 0 168 109">';
    for (var i = 0; i < STRIPES.length; i++) {
      var l = STRIPES[i];
      s += '<line x1="' + l[0] + '" y1="' + l[1] + '" x2="' + l[2] + '" y2="' + l[3] +
           '" stroke="' + C.indigo + '" stroke-width="14"/>';
    }
    return s + '</svg>';
  }

  // ---------- 4 varian ----------
  var VARIANTS = {
    // 1: indigo + lingkaran cyan + cincin gelap
    1: {
      bg: C.indigo, fg: C.white,
      art: '<circle cx="215" cy="55" r="105" fill="' + C.sky + '"/>' +
           '<circle cx="185" cy="105" r="38" fill="none" stroke="' + C.ring + '" stroke-width="14"/>'
    },
    // 2: cyan muda + lingkaran indigo + kotak gelap
    2: {
      bg: C.cyan, fg: C.ink,
      art: '<circle cx="225" cy="45" r="75" fill="' + C.indigo + '"/>' +
           '<rect x="176" y="96" width="70" height="70" fill="' + C.ink + '"/>',
      // teks yang menimpa bentuk gelap berubah putih
      clip: '<rect transform="{TF}" x="176" y="96" width="70" height="70"/>'
    },
    // 3: amber + lingkaran gelap besar
    3: {
      bg: C.amber, fg: C.ink,
      art: '<circle cx="230" cy="90" r="110" fill="' + C.ink + '"/>',
      clip: '<circle transform="{TF}" cx="230" cy="90" r="110"/>'
    },
    // 4: gelap + garis diagonal indigo
    4: {
      bg: C.ink, fg: C.white,
      art: stripes()
    }
  };

  // ---------- Util ----------
  function esc(s) {
    return String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&apos;');
  }

  function hash(str) {
    var h = 0;
    str = String(str == null ? '' : str);
    for (var i = 0; i < str.length; i++) h = (h * 31 + str.charCodeAt(i)) >>> 0;
    return h;
  }

  function pick(seed) { return (hash(seed) % 4) + 1; }

  function toBool(v) { return v === true || v === 'true' || v === '' || v === '1'; }

  // Perkiraan lebar teks tebal Arial (rata-rata ~0.58em per karakter)
  var CHAR_W = 0.58;

  // Bungkus judul -> { lines, size }
  function layoutTitle(title, maxW, maxSize, minSize, maxLines) {
    var explicit = /[|\n]/.test(title);
    var raw = String(title || '').trim();
    var size, lines = [''];

    for (size = maxSize; size >= minSize; size--) {
      var cpl = Math.floor(maxW / (CHAR_W * size));
      if (explicit) {
        lines = raw.split(/\s*[|\n]\s*/);
      } else {
        lines = [];
        var cur = '';
        raw.split(/\s+/).forEach(function (w) {
          var next = cur ? cur + ' ' + w : w;
          if (next.length <= cpl || !cur) cur = next;
          else { lines.push(cur); cur = w; }
        });
        if (cur) lines.push(cur);
      }
      var fits = lines.length <= maxLines && lines.every(function (l) { return l.length <= cpl; });
      if (fits) return { lines: lines, size: size };
    }

    // Tidak muat: potong dengan elipsis
    size = minSize;
    var max = Math.floor(maxW / (CHAR_W * size));
    var cut = lines.length > maxLines;
    lines = lines.slice(0, maxLines).map(function (l, i) {
      var last = cut && i === maxLines - 1;
      if (last) l += '\u2026';
      return l.length > max ? l.slice(0, max - 1) + '\u2026' : l;
    });
    return { lines: lines, size: size };
  }

  // Posisi baseline dalam kotak baris (meniru perilaku CSS line-height)
  function baseline(lineTop, lineHeight, size) {
    var ascent = 0.905 * size, descent = 0.212 * size;
    return lineTop + (lineHeight - (ascent + descent)) / 2 + ascent;
  }

  // ---------- Generator SVG ----------
  // Layout bawaan. Seni selalu menempel di kanan dan diskalakan mengikuti tinggi (h / 168).
  //   card  : kartu daftar acara
  //   wide  : banner halaman detail acara
  //   ticket: header e-ticket
  //   thumb : thumbnail kecil di daftar "Tiket Saya"
  // pad = jarak teks dari tepi, title/min = ukuran judul maks/min, kick = ukuran kicker,
  // inset = pengurang lebar judul otomatis (agar tidak masuk jauh ke area seni),
  // textW = lebar judul tetap (opsional), lines = maks baris judul otomatis
  var LAYOUTS = {
    card:   { w: 270,  h: 168, pad: 18, title: 22, min: 15, kick: 12, inset: 36,  lines: 2 },
    wide:   { w: 1152, h: 320, pad: 40, title: 35, min: 22, kick: 19, textW: 690, lines: 3 },
    ticket: { w: 390,  h: 200, pad: 22, title: 28, min: 18, kick: 14, inset: 120, lines: 3 },
    thumb:  { w: 132,  h: 100, pad: 10, title: 15, min: 11, kick: 9,  inset: 0,   lines: 2 }
  };

  function svg(variant, opts) {
    opts = opts || {};
    var L = LAYOUTS[opts.layout] || LAYOUTS.card;
    var w = L.w, h = L.h, pad = L.pad;
    var s = h / H;                              // skala seni (kartu = 1)
    var v = VARIANTS[variant] || VARIANTS[pick(opts.seed || opts.title)];
    var kicker = String(opts.kicker || '').trim().toUpperCase();

    // Judul otomatis dibatasi L.lines baris; judul dengan pemisah "|" dipakai apa adanya (maks 3 baris).
    var explicit = /[|\n]/.test(String(opts.title || ''));
    var maxW = L.textW || (explicit ? w - pad * 2 : w - pad * 2 - L.inset);
    var maxLines = explicit ? 3 : L.lines;
    var t = layoutTitle(opts.title || '', maxW, L.title, L.min, maxLines);
    var lh = Math.round(t.size * 23 / 22);
    var kSize = L.kick, kLh = Math.round(kSize * 1.5);
    var bottom = h - pad;
    var titleTop = bottom - lh * t.lines.length;

    // Teks (judul + kicker) dengan warna tertentu
    function texts(fg) {
      var o = '';
      t.lines.forEach(function (line, i) {
        o += '<text x="' + pad + '" y="' + baseline(titleTop + i * lh, lh, t.size).toFixed(1) +
             '" font-family="' + FONT + '" font-weight="700" font-size="' + t.size +
             '" letter-spacing="' + (-t.size * 0.0227).toFixed(2) + '" fill="' + fg + '">' + esc(line) + '</text>';
      });
      if (kicker) {
        o += '<text x="' + pad + '" y="' + baseline(titleTop - kLh, kLh, kSize).toFixed(1) +
             '" font-family="' + FONT + '" font-weight="700" font-size="' + kSize +
             '" letter-spacing="' + (kSize / 12).toFixed(2) + '"' +
             ' fill="' + fg + '" fill-opacity="0.85">' + esc(kicker) + '</text>';
      }
      return o;
    }

    var tf = 'translate(' + (w - W * s).toFixed(2) + ' 0) scale(' + s.toFixed(4) + ')';
    var out = texts(v.fg);
    // Bagian teks yang menimpa bentuk gelap digambar ulang putih, dipotong sesuai bentuknya
    if (v.clip) {
      out += '<clipPath id="ov">' + v.clip.replace('{TF}', tf) + '</clipPath>' +
             '<g clip-path="url(#ov)">' + texts(C.white) + '</g>';
    }

    var body = '<rect width="' + w + '" height="' + h + '" fill="' + v.bg + '"/>' +
               '<g transform="' + tf + '">' + v.art + '</g>' + out;
    if (toBool(opts.dimmed)) {
      body = '<defs><filter id="dim"><feColorMatrix type="saturate" values="0.25"/></filter></defs>' +
             '<g filter="url(#dim)">' + body + '</g>';
    }

    var wideish = w / h > 2;
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' + w + '" height="' + h +
           '" viewBox="0 0 ' + w + ' ' + h + '" preserveAspectRatio="' + (wideish ? 'xMinYMid' : 'xMinYMax') + ' slice"' +
           ' role="img" aria-label="' + esc(((kicker ? kicker + ' - ' : '') + (opts.title || 'Banner acara'))
             .replace(/[|\n]/g, ' ')) + '">' + body + '</svg>';
  }

  function dataUri(variant, opts) {
    return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg(variant, opts));
  }

  // ---------- Render ke DOM ----------
  var SRC_RE = /\/assets\/default-banner\/([1-4])(?:\.[a-z]+)?(?:\?([^#]*))?(?:#.*)?$/i;

  function readOptions(el) {
    var opts = {}, variant = el.getAttribute('data-default-banner');

    // Pola URL: /assets/default-banner/N?kicker=..&title=..
    var src = el.tagName === 'IMG' ? (el.getAttribute('src') || '') : '';
    var m = SRC_RE.exec(src);
    if (m) {
      if (!variant) variant = m[1];
      if (m[2]) {
        var q = new URLSearchParams(m[2]);
        ['kicker', 'title', 'dimmed', 'seed', 'layout'].forEach(function (k) {
          if (q.has(k)) opts[k] = q.get(k);
        });
      }
    }

    // Atribut data menimpa query
    ['kicker', 'title', 'dimmed', 'seed', 'layout'].forEach(function (k) {
      var a = el.getAttribute('data-' + k);
      if (a !== null) opts[k] = a;
    });
    if (opts.dimmed === undefined) opts.dimmed = false;

    var n = parseInt(variant, 10);
    if (!(n >= 1 && n <= 4)) n = pick(opts.seed || opts.title || opts.kicker);
    return { variant: n, opts: opts };
  }

  function render(el) {
    var r = readOptions(el);
    var uri = dataUri(r.variant, r.opts);
    if (el.tagName === 'IMG') {
      el.src = uri;
      if (!el.getAttribute('alt')) {
        el.alt = ((r.opts.kicker ? r.opts.kicker + ' ' : '') + (r.opts.title || 'Banner acara'))
          .replace(/[|\n]/g, ' ');
      }
    } else {
      el.style.backgroundImage = 'url("' + uri + '")';
      el.style.backgroundSize = 'cover';
      el.style.backgroundPosition = 'left bottom';
      el.style.backgroundRepeat = 'no-repeat';
    }
    el.setAttribute('data-banner-ready', '1');
    return el;
  }

  var SELECTOR = '[data-default-banner], img[src*="/assets/default-banner/"]';

  function init(scope) {
    var nodes = (scope || document).querySelectorAll(SELECTOR);
    for (var i = 0; i < nodes.length; i++) {
      if (!nodes[i].hasAttribute('data-banner-ready')) render(nodes[i]);
    }
  }

  var api = { svg: svg, dataUri: dataUri, render: render, init: init, pick: pick, variants: [1, 2, 3, 4] };

  if (typeof module !== 'undefined' && module.exports) module.exports = api;
  root.DefaultBanner = api;

  // Auto-init + pantau elemen yang ditambahkan belakangan
  if (typeof document !== 'undefined') {
    var start = function () {
      init(document);
      if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(function (muts) {
          for (var i = 0; i < muts.length; i++) {
            if (muts[i].addedNodes.length) { init(document); break; }
          }
        }).observe(document.documentElement, { childList: true, subtree: true });
      }
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
  }
})(typeof window !== 'undefined' ? window : this);