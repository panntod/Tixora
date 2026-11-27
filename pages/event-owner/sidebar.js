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
    '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>' +
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

/* Dialog konfirmasi TIXORA (pengganti confirm()):
 *   eoConfirm({title, text, confirmLabel, tone: 'primary'|'danger', reason: {label, min, error, placeholder}})
 *   -> Promise: null bila dibatalkan, {reason} bila dikonfirmasi.
 * Form dapat memakai atribut: data-dlg-title, data-dlg-text, data-dlg-confirm, data-dlg-tone. */
(function () {
  'use strict';
  var active = null;

  window.eoConfirm = function (o) {
    return new Promise(function (resolve) {
      if (active) { active(null); }
      var prevFocus = document.activeElement;
      var back = document.createElement('div');
      back.className = 'dlg-backdrop';
      var box = document.createElement('div');
      box.className = 'dialog';
      box.setAttribute('role', 'dialog');
      box.setAttribute('aria-modal', 'true');

      var h = document.createElement('h3');
      h.className = 'dialog-title';
      h.id = 'dlg-title-' + Date.now();
      h.textContent = o.title || 'Konfirmasi';
      box.setAttribute('aria-labelledby', h.id);
      var p = document.createElement('p');
      p.className = 'dialog-text';
      p.textContent = o.text || '';
      box.appendChild(h);
      box.appendChild(p);

      var ta = null, err = null;
      if (o.reason) {
        var wrap = document.createElement('div');
        var lb = document.createElement('label');
        lb.className = 'field-label';
        lb.textContent = o.reason.label || 'Alasan';
        ta = document.createElement('textarea');
        ta.className = 'input input-area';
        ta.rows = 3;
        ta.maxLength = 500;
        if (o.reason.placeholder) { ta.placeholder = o.reason.placeholder; }
        err = document.createElement('p');
        err.className = 'help help-error';
        err.style.display = 'none';
        ta.addEventListener('input', function () { err.style.display = 'none'; ta.classList.remove('input-error'); });
        wrap.appendChild(lb);
        wrap.appendChild(ta);
        wrap.appendChild(err);
        box.appendChild(wrap);
      }

      var row = document.createElement('div');
      row.className = 'dlg-actions';
      var cancel = document.createElement('button');
      cancel.type = 'button';
      cancel.className = 'button secondary-button';
      cancel.style.cursor = 'pointer';
      cancel.textContent = o.cancelLabel || 'Batal';
      var ok = document.createElement('button');
      ok.type = 'button';
      ok.className = 'button ' + (o.tone === 'danger' ? 'danger-button' : 'primary-button');
      ok.style.cursor = 'pointer';
      ok.textContent = o.confirmLabel || 'Ya';
      row.appendChild(cancel);
      row.appendChild(ok);
      box.appendChild(row);
      back.appendChild(box);

      function onKey(e) { if (e.key === 'Escape') { finish(null); } }
      function finish(result) {
        document.removeEventListener('keydown', onKey);
        if (back.parentNode) { back.parentNode.removeChild(back); }
        active = null;
        if (prevFocus && prevFocus.focus) { prevFocus.focus(); }
        resolve(result);
      }
      active = finish;

      cancel.addEventListener('click', function () { finish(null); });
      back.addEventListener('mousedown', function (e) { if (e.target === back) { finish(null); } });
      ok.addEventListener('click', function () {
        if (o.reason) {
          var v = ta.value.trim();
          if (v.length < (o.reason.min || 1)) {
            err.textContent = o.reason.error || 'Alasan wajib diisi.';
            err.style.display = 'block';
            ta.classList.add('input-error');
            ta.focus();
            return;
          }
          finish({ reason: v });
          return;
        }
        finish({});
      });
      document.addEventListener('keydown', onKey);
      document.body.appendChild(back);
      (ta || ok).focus();
    });
  };

  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f || !f.hasAttribute || !f.hasAttribute('data-dlg-title') || f.getAttribute('data-dlg-ok') === '1') { return; }
    e.preventDefault();
    window.eoConfirm({
      title: f.getAttribute('data-dlg-title'),
      text: f.getAttribute('data-dlg-text') || '',
      confirmLabel: f.getAttribute('data-dlg-confirm') || 'Ya',
      tone: f.getAttribute('data-dlg-tone') || 'primary'
    }).then(function (r) {
      if (!r) { return; }
      f.setAttribute('data-dlg-ok', '1');
      HTMLFormElement.prototype.submit.call(f);
    });
  });
})();