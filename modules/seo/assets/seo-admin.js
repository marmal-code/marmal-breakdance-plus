/* Marmal – Breakdance Plus, modul SEO: box „SEO a sdílení“ – počítadla, náhled Googlu a Facebooku,
 * výběr obrázku. Texty se do náhledu vkládají jen přes textContent (žádné HTML od uživatele). */
(function () {
  'use strict';

  var C = window.marmalSeo;
  var box = document.getElementById('marmal-seo');
  if (!C || !box) return;

  var T = C.i18n;
  var inTitle = box.querySelector('#marmal-seo-title');
  var inDesc = box.querySelector('#marmal-seo-desc');
  var inImg = box.querySelector('#marmal-seo-image');
  var noindex = box.querySelector('input[name="marmal_seo[noindex]"]');
  var obrazek = C.obrazek || '';

  function $(sel) { return box.querySelector(sel); }
  function text(sel, t) { var el = $(sel); if (el) el.textContent = t; }

  function escRe(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

  /* Stejná pravidla jako nahradit() v PHP. */
  function nahradit(t) {
    if (!t || t.indexOf('%') < 0) return t || '';
    var odd = C.oddelovac;
    t = t.split('%nazev%').join(C.nazev).split('%web%').join(C.web)
      .split('%popis_webu%').join(C.popisWebu).split('%oddelovac%').join(odd);
    t = t.replace(/\s+/g, ' ').trim();
    var q = escRe(odd);
    t = t.replace(new RegExp('^(' + q + '\\s*)+|(\\s*' + q + ')+$', 'g'), '');
    t = t.replace(new RegExp('(' + q + ')(\\s*' + q + ')+', 'g'), '$1');
    return t.trim();
  }

  function zkratit(t, n) {
    t = String(t || '');
    return t.length > n ? t.slice(0, n - 1).replace(/\s+\S*$/, '') + ' …' : t;
  }

  function pocet(el, typ) {
    var span = box.querySelector('[data-pocet="' + typ + '"]');
    if (!span) return;
    var hodnota = nahradit(el.value.trim());
    var n = hodnota.length;
    if (!n) { span.textContent = T.zaloha; span.className = 'marmal-seo__pocet'; return; }
    var stav = 'ok', pozn = '';
    if (typ === 'title' && n > C.limity.title) { stav = 'pozor'; pozn = ' ' + T.dlouhy; }
    if (typ === 'desc' && n > C.limity.descMax) { stav = 'pozor'; pozn = ' ' + T.dlouhy; }
    if (typ === 'desc' && n < C.limity.descMin) { stav = 'info'; pozn = ' ' + T.kratky; }
    span.textContent = T.znaku.replace('%d', n) + pozn;
    span.className = 'marmal-seo__pocet marmal-seo__pocet--' + stav;
  }

  function nahled() {
    var titulek = nahradit(inTitle.value.trim()) || C.zaloha.title || C.nazev;
    var popis = nahradit(inDesc.value.trim()) || C.zaloha.description || '';
    var obr = obrazek || C.zaloha.obrazek || '';

    text('.marmal-seo__g-nazev', C.web);
    text('.marmal-seo__g-url', C.url);
    text('.marmal-seo__g-titulek', zkratit(titulek, 62));
    text('.marmal-seo__g-popis', zkratit(popis, 158));
    var ik = $('.marmal-seo__g-ikona');
    if (ik && C.ikona) ik.style.backgroundImage = 'url("' + encodeURI(C.ikona) + '")';

    text('.marmal-seo__fb-domena', (C.domena || '').toUpperCase());
    text('.marmal-seo__fb-titulek', zkratit(titulek, 88));
    text('.marmal-seo__fb-popis', zkratit(popis, 110));
    var img = $('.marmal-seo__fb-obr img');
    var bez = $('.marmal-seo__fb-bez');
    if (obr) { img.src = obr; img.hidden = false; bez.hidden = true; } else { img.removeAttribute('src'); img.hidden = true; bez.hidden = false; }

    inTitle.placeholder = C.zaloha.title || '';
    inDesc.placeholder = C.zaloha.description || '';
    $('.marmal-seo__odebrat').hidden = !(+inImg.value);

    var neindex = (noindex && noindex.checked) || C.noindexFiltr;
    $('.marmal-seo__noindex-info').hidden = !neindex;
    $('.marmal-seo__google').classList.toggle('is-noindex', !!neindex);
    $('.marmal-seo__filtr-noindex').hidden = !C.noindexFiltr;

    pocet(inTitle, 'title');
    pocet(inDesc, 'desc');
  }

  box.addEventListener('input', nahled);
  box.addEventListener('change', nahled);
  inTitle.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });

  box.addEventListener('click', function (e) {
    var b = e.target.closest('[data-seo]');
    if (!b) return;
    e.preventDefault();
    if (b.getAttribute('data-seo') === 'odebrat') {
      inImg.value = '0';
      obrazek = '';
      nahled();
      return;
    }
    if (!window.wp || !wp.media) return;
    var frame = wp.media({ title: T.vybrat, button: { text: T.pouzit }, multiple: false, library: { type: 'image' } });
    frame.on('select', function () {
      var a = frame.state().get('selection').first().toJSON();
      inImg.value = String(a.id);
      var s = a.sizes || {};
      obrazek = (s.medium_large && s.medium_large.url) || (s.large && s.large.url) || a.url;
      nahled();
    });
    frame.open();
  });

  nahled();
})();
