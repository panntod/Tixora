/* Hamburger untuk sidebar Event Owner (aktif di layar <= 900px lewat CSS). */
(function () {
  var sidebar = document.querySelector('.sidebar');
  var app = document.querySelector('.app');
  if (!sidebar || !app) return;

  var logo = sidebar.querySelector('.logo-img');
  var bar = document.createElement('div');
  bar.className = 'mobile-bar';
  bar.innerHTML =
    '<button type="button" class="hamburger" aria-label="Buka menu" aria-expanded="false" aria-controls="sidebar-menu">' +
    '<span></span><span></span><span></span></button>' +
    (logo ? '<img class="logo-img" src="' + logo.getAttribute('src') + '" alt="TIXORA">' : '');
  var backdrop = document.createElement('div');
  backdrop.className = 'sidebar-backdrop';
  sidebar.id = 'sidebar-menu';
  app.parentNode.insertBefore(bar, app);
  document.body.appendChild(backdrop);

  var btn = bar.querySelector('.hamburger');
  function setOpen(open) {
    document.body.classList.toggle('sidebar-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
  }
  btn.addEventListener('click', function () { setOpen(!document.body.classList.contains('sidebar-open')); });
  backdrop.addEventListener('click', function () { setOpen(false); });
  sidebar.addEventListener('click', function (e) { if (e.target.closest('a')) setOpen(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
  window.addEventListener('resize', function () { if (window.innerWidth > 900) setOpen(false); });
})();
