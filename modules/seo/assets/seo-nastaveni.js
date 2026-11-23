/* Marmal – Breakdance Plus, modul SEO: výběr obrázků na stránce MarMal → SEO (výchozí i pro další jazyky). */
(function () {
  'use strict';
  var C = window.marmalSeoNast || {};

  document.querySelectorAll('[data-marmal-obr]').forEach(function (box) {
    var input = box.querySelector('input[type=hidden]');
    var img = box.querySelector('img');
    var vybrat = box.querySelector('[data-vybrat]');
    var odebrat = box.querySelector('[data-odebrat]');
    if (!input || !vybrat) return;

    vybrat.addEventListener('click', function (e) {
      e.preventDefault();
      if (!window.wp || !wp.media) return;
      var frame = wp.media({ title: C.vybrat, button: { text: C.pouzit }, multiple: false, library: { type: 'image' } });
      frame.on('select', function () {
        var a = frame.state().get('selection').first().toJSON();
        var s = a.sizes || {};
        input.value = String(a.id);
        img.src = (s.medium_large && s.medium_large.url) || (s.large && s.large.url) || a.url;
        img.hidden = false;
        odebrat.hidden = false;
      });
      frame.open();
    });

    odebrat.addEventListener('click', function (e) {
      e.preventDefault();
      input.value = '0';
      img.removeAttribute('src');
      img.hidden = true;
      odebrat.hidden = true;
    });
  });
})();
