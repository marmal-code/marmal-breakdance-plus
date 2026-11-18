/*
 * Modul Patička – Marmal Breakdance Plus.
 * 1) Rok v copyrightu: po Novém roce ho opraví i na stránce ze staré page cache
 *    (jen zvyšuje – nikdy neukáže starší rok, než poslal server).
 * 2) Tlačítko „Nastavení cookies“: Complianz a CookieYes reagují samy na svou třídu,
 *    Cookiebot se otevře přes Cookiebot.renew().
 */
(function () {
  'use strict';

  function roky() {
    var letos = new Date().getFullYear();
    var prvky = document.querySelectorAll('[data-marmal-rok-od]');
    for (var i = 0; i < prvky.length; i++) {
      var el = prvky[i];
      var od = parseInt(el.getAttribute('data-marmal-rok-od'), 10);
      var text = el.textContent || '';
      var konec = parseInt(text.slice(-4), 10);
      if (!od || !konec || letos <= konec) {
        continue;
      }
      el.textContent = od >= letos ? String(letos) : od + '–' + letos;
    }
  }

  document.addEventListener('click', function (e) {
    var btn = e.target && e.target.closest ? e.target.closest('[data-marmal-cookies]') : null;
    if (!btn) {
      return;
    }
    if (btn.getAttribute('data-marmal-cookies') === 'cookiebot' && window.Cookiebot && typeof window.Cookiebot.renew === 'function') {
      window.Cookiebot.renew();
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', roky);
  } else {
    roky();
  }
})();
