/*!
 * TIXORA QR — generator QR Code kecil tanpa dependensi (offline, tanpa CDN).
 * Mode byte (UTF-8), koreksi galat level M, versi 1–10 (cukup untuk URL tiket).
 *
 * Pemakaian:
 *   TixoraQR.render(document.getElementById('qr'), 'https://contoh/...');
 *   TixoraQR.svg('teks')  -> string <svg>
 */
(function (global) {
    'use strict';

    // [ecPerBlock, [[jumlahBlok, dataPerBlok], ...]] untuk level M, indeks = versi
    var EC_M = [null,
        [10, [[1, 16]]], [16, [[1, 28]]], [26, [[1, 44]]], [18, [[2, 32]]], [24, [[2, 43]]],
        [16, [[4, 27]]], [18, [[4, 31]]], [22, [[2, 38], [2, 39]]], [22, [[3, 36], [2, 37]]],
        [26, [[4, 43], [1, 44]]]
    ];
    var ALIGN = [null, [], [6, 18], [6, 22], [6, 26], [6, 30], [6, 34], [6, 22, 38], [6, 24, 42], [6, 26, 46], [6, 28, 50]];
    var MAX_VERSION = 10;

    // ---- Aritmetika GF(256) ----
    var EXP = new Array(512), LOG = new Array(256);
    (function () {
        var x = 1;
        for (var i = 0; i < 255; i++) {
            EXP[i] = x; LOG[x] = i;
            x <<= 1; if (x & 0x100) x ^= 0x11D;
        }
        for (i = 255; i < 512; i++) EXP[i] = EXP[i - 255];
    })();
    function gfMul(a, b) { return (a === 0 || b === 0) ? 0 : EXP[LOG[a] + LOG[b]]; }

    function rsGenerator(degree) {
        var poly = [1];
        for (var i = 0; i < degree; i++) {
            var next = new Array(poly.length + 1).fill(0);
            for (var j = 0; j < poly.length; j++) {
                next[j] ^= poly[j];
                next[j + 1] ^= gfMul(poly[j], EXP[i]);
            }
            poly = next;
        }
        return poly; // koefisien tertinggi lebih dulu
    }
    function rsRemainder(data, degree) {
        var gen = rsGenerator(degree);
        var rem = new Array(degree).fill(0);
        data.forEach(function (b) {
            var factor = b ^ rem.shift();
            rem.push(0);
            for (var i = 0; i < degree; i++) rem[i] ^= gfMul(gen[i + 1], factor);
        });
        return rem;
    }

    function utf8Bytes(str) {
        var s = unescape(encodeURIComponent(str)), out = [];
        for (var i = 0; i < s.length; i++) out.push(s.charCodeAt(i));
        return out;
    }

    function dataCapacity(version) {
        var info = EC_M[version], total = 0;
        info[1].forEach(function (g) { total += g[0] * g[1]; });
        return total;
    }

    function buildCodewords(bytes) {
        var version = 0;
        for (var v = 1; v <= MAX_VERSION; v++) {
            var countBits = v < 10 ? 8 : 16;
            if (4 + countBits + bytes.length * 8 <= dataCapacity(v) * 8) { version = v; break; }
        }
        if (!version) throw new Error('Teks terlalu panjang untuk QR (maks ~213 byte).');

        var bits = [];
        function put(val, len) { for (var i = len - 1; i >= 0; i--) bits.push((val >>> i) & 1); }
        put(4, 4);
        put(bytes.length, version < 10 ? 8 : 16);
        bytes.forEach(function (b) { put(b, 8); });
        var capBits = dataCapacity(version) * 8;
        put(0, Math.min(4, capBits - bits.length));
        while (bits.length % 8) bits.push(0);
        var data = [];
        for (var i = 0; i < bits.length; i += 8) {
            var byte = 0;
            for (var k = 0; k < 8; k++) byte = (byte << 1) | bits[i + k];
            data.push(byte);
        }
        for (var pad = 0xEC; data.length < dataCapacity(version); pad ^= 0xEC ^ 0x11) data.push(pad);

        // Pecah ke blok + hitung ECC, lalu interleave
        var info = EC_M[version], ecLen = info[0], blocks = [], pos = 0;
        info[1].forEach(function (g) {
            for (var n = 0; n < g[0]; n++) {
                var d = data.slice(pos, pos + g[1]); pos += g[1];
                blocks.push({ d: d, e: rsRemainder(d, ecLen) });
            }
        });
        var out = [], maxD = 0;
        blocks.forEach(function (b) { maxD = Math.max(maxD, b.d.length); });
        for (i = 0; i < maxD; i++) blocks.forEach(function (b) { if (i < b.d.length) out.push(b.d[i]); });
        for (i = 0; i < ecLen; i++) blocks.forEach(function (b) { out.push(b.e[i]); });
        return { version: version, codewords: out };
    }

    function getBit(x, i) { return ((x >>> i) & 1) !== 0; }

    function makeMatrix(version, codewords) {
        var size = 17 + 4 * version, i, j;
        var mod = [], fn = [];
        for (i = 0; i < size; i++) { mod.push(new Array(size).fill(false)); fn.push(new Array(size).fill(false)); }
        function setFn(x, y, dark) { mod[y][x] = dark; fn[y][x] = true; }

        // Pola timing
        for (i = 0; i < size; i++) { setFn(6, i, i % 2 === 0); setFn(i, 6, i % 2 === 0); }
        // Finder + separator
        function finder(cx, cy) {
            for (var dy = -4; dy <= 4; dy++) for (var dx = -4; dx <= 4; dx++) {
                var dist = Math.max(Math.abs(dx), Math.abs(dy)), x = cx + dx, y = cy + dy;
                if (x >= 0 && x < size && y >= 0 && y < size) setFn(x, y, dist !== 2 && dist !== 4);
            }
        }
        finder(3, 3); finder(size - 4, 3); finder(3, size - 4);
        // Alignment
        var ap = ALIGN[version];
        for (i = 0; i < ap.length; i++) for (j = 0; j < ap.length; j++) {
            if ((i === 0 && j === 0) || (i === 0 && j === ap.length - 1) || (i === ap.length - 1 && j === 0)) continue;
            for (var dy = -2; dy <= 2; dy++) for (var dx = -2; dx <= 2; dx++)
                setFn(ap[i] + dx, ap[j] + dy, Math.max(Math.abs(dx), Math.abs(dy)) !== 1);
        }
        // Format (placeholder) + versi
        function drawFormat(mask) {
            var data = (0 << 3) | mask; // level M = 00
            var rem = data;
            for (var k = 0; k < 10; k++) rem = (rem << 1) ^ ((rem >>> 9) * 0x537);
            var bits = ((data << 10) | rem) ^ 0x5412;
            for (k = 0; k <= 5; k++) setFn(8, k, getBit(bits, k));
            setFn(8, 7, getBit(bits, 6)); setFn(8, 8, getBit(bits, 7)); setFn(7, 8, getBit(bits, 8));
            for (k = 9; k < 15; k++) setFn(14 - k, 8, getBit(bits, k));
            for (k = 0; k < 8; k++) setFn(size - 1 - k, 8, getBit(bits, k));
            for (k = 8; k < 15; k++) setFn(8, size - 15 + k, getBit(bits, k));
            setFn(8, size - 8, true);
        }
        drawFormat(0);
        if (version >= 7) {
            var rem = version;
            for (i = 0; i < 12; i++) rem = (rem << 1) ^ ((rem >>> 11) * 0x1F25);
            var vbits = (version << 12) | rem;
            for (i = 0; i < 18; i++) {
                var a = size - 11 + (i % 3), b = Math.floor(i / 3), bit = getBit(vbits, i);
                setFn(a, b, bit); setFn(b, a, bit);
            }
        }

        // Tempatkan data (zig-zag)
        var idx = 0, total = codewords.length * 8;
        for (var right = size - 1; right >= 1; right -= 2) {
            if (right === 6) right = 5;
            for (var vert = 0; vert < size; vert++) for (j = 0; j < 2; j++) {
                var x = right - j, upward = ((right + 1) & 2) === 0, y = upward ? size - 1 - vert : vert;
                if (!fn[y][x] && idx < total) {
                    mod[y][x] = getBit(codewords[idx >>> 3], 7 - (idx & 7));
                    idx++;
                }
            }
        }

        // Pilih mask dengan penalti terendah
        var MASKS = [
            function (x, y) { return (x + y) % 2 === 0; },
            function (x, y) { return y % 2 === 0; },
            function (x, y) { return x % 3 === 0; },
            function (x, y) { return (x + y) % 3 === 0; },
            function (x, y) { return (Math.floor(x / 3) + Math.floor(y / 2)) % 2 === 0; },
            function (x, y) { return x * y % 2 + x * y % 3 === 0; },
            function (x, y) { return (x * y % 2 + x * y % 3) % 2 === 0; },
            function (x, y) { return ((x + y) % 2 + x * y % 3) % 2 === 0; }
        ];
        function applyMask(m) {
            for (var y = 0; y < size; y++) for (var x = 0; x < size; x++)
                if (!fn[y][x] && MASKS[m](x, y)) mod[y][x] = !mod[y][x];
        }
        var best = 0, bestScore = Infinity;
        for (var m = 0; m < 8; m++) {
            applyMask(m); drawFormat(m);
            var s = penalty(mod, size);
            if (s < bestScore) { bestScore = s; best = m; }
            applyMask(m); // batalkan (XOR)
        }
        applyMask(best); drawFormat(best);
        return mod;
    }

    function penalty(mod, size) {
        var score = 0, x, y, run, lines = [], dark = 0;
        for (y = 0; y < size; y++) {
            var row = '', col = '';
            for (x = 0; x < size; x++) { row += mod[y][x] ? '1' : '0'; col += mod[x][y] ? '1' : '0'; if (mod[y][x]) dark++; }
            lines.push(row, col);
        }
        lines.forEach(function (ln) {
            run = 1;
            for (var i = 1; i <= ln.length; i++) {
                if (i < ln.length && ln[i] === ln[i - 1]) run++;
                else { if (run >= 5) score += 3 + (run - 5); run = 1; }
            }
            ['10111010000', '00001011101'].forEach(function (p) {
                var at = ln.indexOf(p);
                while (at !== -1) { score += 40; at = ln.indexOf(p, at + 1); }
            });
        });
        for (y = 0; y < size - 1; y++) for (x = 0; x < size - 1; x++) {
            var c = mod[y][x];
            if (c === mod[y][x + 1] && c === mod[y + 1][x] && c === mod[y + 1][x + 1]) score += 3;
        }
        var totalMods = size * size;
        score += (Math.ceil(Math.abs(dark * 20 - totalMods * 10) / totalMods) - 1) * 10;
        return score;
    }

    function matrix(text) {
        var cw = buildCodewords(utf8Bytes(String(text)));
        return makeMatrix(cw.version, cw.codewords);
    }

    function svg(text, opts) {
        opts = opts || {};
        var m = matrix(text), n = m.length, quiet = opts.quiet == null ? 4 : opts.quiet;
        var dark = opts.dark || '#0E1128', light = opts.light || '#FFFFFF', d = '';
        for (var y = 0; y < n; y++) for (var x = 0; x < n; x++)
            if (m[y][x]) d += 'M' + (x + quiet) + ' ' + (y + quiet) + 'h1v1h-1z';
        var dim = n + quiet * 2;
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + dim + ' ' + dim + '" ' +
            'shape-rendering="crispEdges" role="img" aria-label="' + (opts.label || 'Kode QR') + '" ' +
            'style="width:100%;height:100%;display:block">' +
            '<rect width="' + dim + '" height="' + dim + '" fill="' + light + '"/>' +
            '<path fill="' + dark + '" d="' + d + '"/></svg>';
    }

    function render(el, text, opts) {
        if (typeof el === 'string') el = document.querySelector(el);
        if (!el) return false;
        try { el.innerHTML = svg(text, opts); return true; }
        catch (e) { el.textContent = 'QR gagal dibuat'; return false; }
    }

    global.TixoraQR = { matrix: matrix, svg: svg, render: render };
    if (typeof module !== 'undefined' && module.exports) module.exports = global.TixoraQR;
})(typeof window !== 'undefined' ? window : globalThis);
