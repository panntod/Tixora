/**
 * Responsive admin: menyuntik top bar + tombol menu dan mengatur sidebar
 * sebagai drawer pada layar <= 1024px. Tidak butuh perubahan markup sidebar.
 */
(function () {
  'use strict';

  var MOBILE_MQ = window.matchMedia('(max-width: 1024px)');

  function init() {
    var sidebar = document.querySelector('.sidebar');
    var app = document.querySelector('.app');
    if (!sidebar || !app || document.querySelector('.adm-topbar')) {
      return;
    }

    if (!sidebar.id) {
      sidebar.id = 'admin-sidebar';
    }

    var logo = sidebar.querySelector('.logo-img');
    var topbar = document.createElement('div');
    topbar.className = 'adm-topbar';
    topbar.innerHTML =
      '<button type="button" class="adm-menu-btn" aria-label="Buka menu" ' +
      'aria-expanded="false" aria-controls="' + sidebar.id + '">' +
      '<span></span><span></span><span></span></button>' +
      '<span class="logo"></span>' +
      '<span class="adm-topbar-label">ADMIN</span>';

    if (logo) {
      topbar.querySelector('.logo').appendChild(logo.cloneNode(true));
    }

    var backdrop = document.createElement('div');
    backdrop.className = 'adm-backdrop';

    app.parentNode.insertBefore(topbar, app);
    app.parentNode.insertBefore(backdrop, app);

    var btn = topbar.querySelector('.adm-menu-btn');

    function setOpen(open) {
      sidebar.classList.toggle('is-open', open);
      backdrop.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
      document.body.style.overflow = open && MOBILE_MQ.matches ? 'hidden' : '';
    }

    btn.addEventListener('click', function () {
      setOpen(!sidebar.classList.contains('is-open'));
    });
    backdrop.addEventListener('click', function () { setOpen(false); });
    sidebar.addEventListener('click', function (e) {
      if (e.target.closest && e.target.closest('a')) { setOpen(false); }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { setOpen(false); }
    });

    var onChange = function () { if (!MOBILE_MQ.matches) { setOpen(false); } };
    if (MOBILE_MQ.addEventListener) {
      MOBILE_MQ.addEventListener('change', onChange);
    } else if (MOBILE_MQ.addListener) {
      MOBILE_MQ.addListener(onChange);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
