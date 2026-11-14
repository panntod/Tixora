/*!
 * TIXORA — tombol "lihat kata sandi".
 * Otomatis menambahkan ikon mata pada setiap <input type="password">.
 * Cara pakai: <script src="<?= BASE_URL ?>/assets/password-toggle.js"></script> sebelum </body>.
 * Input yang tidak ingin diberi tombol: tambahkan atribut data-no-toggle.
 */
(function () {
    'use strict';

    var EYE = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    var EYE_OFF = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19C5 19 1 12 1 12a18.5 18.5 0 0 1 5.06-5.94"/><path d="M9.9 4.24A10.9 10.9 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    function attach(input) {
        if (input.hasAttribute('data-no-toggle') || input.dataset.pwToggle) return;
        input.dataset.pwToggle = '1';

        var wrap = document.createElement('div');
        wrap.className = 'pw-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pw-toggle';
        btn.setAttribute('aria-label', 'Tampilkan kata sandi');
        btn.setAttribute('aria-pressed', 'false');
        btn.innerHTML = EYE;
        wrap.appendChild(btn);

        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = show ? EYE_OFF : EYE;
            btn.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            input.focus();
        });
    }

    function init() {
        var list = document.querySelectorAll('input[type="password"]');
        for (var i = 0; i < list.length; i++) attach(list[i]);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
