/*
 * Modul Cookies – Marmal Breakdance Plus.
 * Lišta, okno nastavení, uložení souhlasu (cookie marmal_consent), Consent Mode v2,
 * spuštění zablokovaných skriptů a vložených rámců po souhlasu, záznam souhlasu.
 * Texty vkládá jen přes textContent. Konfigurace: window.marmalCookies (PHP).
 */
(function () {
  'use strict';
  var C = window.marmalCookies;
  if (!C) {
    return;
  }
  var d = document;
  var w = window;
  var T = C.texty;
  var ulozeny = (!C.nahled && w.marmalCookiesUlozeny) ? w.marmalCookiesUlozeny : null;
  var stav = ulozeny ? ulozeny.k.slice() : null; // povolené kategorie, null = návštěvník ještě nerozhodl
  var okno = null;
  var navrat = null;

  var volitelne = C.kategorie.filter(function (k) { return !k.zamceno; }).map(function (k) { return k.id; });

  function el(tag, cls, text) {
    var e = d.createElement(tag);
    if (cls) { e.className = cls; }
    if (text != null) { e.textContent = text; }
    return e;
  }
  function tlacitko(cls, text, akce) {
    var b = el('button', cls, text);
    b.type = 'button';
    b.setAttribute('data-ck', akce);
    return b;
  }
  function nahodne() {
    var a = new Uint8Array(8);
    (w.crypto || w.msCrypto).getRandomValues(a);
    return Array.prototype.map.call(a, function (x) { return (x % 36).toString(36); }).join('');
  }

  /* ---------- barva textu tlačítek podle skutečné hlavní barvy webu (kontrast WCAG) ---------- */
  function autoInk() {
    if (!C.vzhled.autoInk) { return; }
    var s = el('span');
    s.style.cssText = 'position:absolute;width:0;height:0;overflow:hidden;color:var(--mck-brand)';
    d.body.appendChild(s);
    var m = getComputedStyle(s).color.match(/[\d.]+/g);
    s.remove();
    if (!m) { return; }
    var l = m.slice(0, 3).map(function (v) {
      var c = (+v) / 255;
      return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });
    var L = 0.2126 * l[0] + 0.7152 * l[1] + 0.0722 * l[2];
    var bila = 1.05 / (L + 0.05);
    var tmava = (L + 0.05) / (0.0056 + 0.05);
    d.documentElement.style.setProperty('--mck-btnfg-auto', bila >= tmava ? '#ffffff' : '#111111');
  }

  /* ---------- uložení souhlasu ---------- */
  function ulozit(k, akce) {
    var predtim = stav;
    var data = { r: C.rev, k: k, id: (ulozeny && ulozeny.id) || nahodne(), t: Math.floor(Date.now() / 1000) };
    if (!C.nahled) {
      d.cookie = C.cookie + '=' + encodeURIComponent(JSON.stringify(data)) + ';path=/;max-age=' + (C.platnost * 86400)
        + ';SameSite=Lax' + (location.protocol === 'https:' ? ';Secure' : '');
      zaznam(data.id, akce, k);
    }
    ulozeny = data;
    stav = k.slice();
    w.marmalCookiesStav = k.slice();
    if (typeof w.gtag === 'function' && w.marmalCookiesMapa) {
      w.gtag('consent', 'update', w.marmalCookiesMapa(k));
    }
    predat(k);
    try { d.dispatchEvent(new CustomEvent('marmal:cookies', { detail: { kategorie: k.slice() } })); } catch (e) { /* starý prohlížeč */ }
    zavritListu();
    zavritOkno();
    var odvolano = predtim ? predtim.filter(function (x) { return k.indexOf(x) < 0; }) : [];
    if (odvolano.length && !C.nahled) {
      // Odvolaný souhlas: smazat cookies dané kategorie a načíst stránku znovu (už spuštěné skripty nejde zastavit).
      smazat(odvolano);
      location.reload();
      return;
    }
    aktivovat();
    ikona();
  }

  function zaznam(id, akce, k) {
    if (!w.fetch || !C.rest) { return; }
    try {
      w.fetch(C.rest, {
        method: 'POST', credentials: 'omit', keepalive: true,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, akce: akce, kategorie: k, revize: C.rev })
      }).catch(function () {});
    } catch (e) { /* záznam je best-effort, volba návštěvníka platí i bez něj */ }
  }

  function smazat(kats) {
    var jmena = [];
    C.kategorie.forEach(function (k) {
      if (kats.indexOf(k.id) > -1) { k.cookies.forEach(function (c) { jmena.push(c[0]); }); }
    });
    var host = location.hostname;
    var casti = host.split('.');
    var domeny = ['', host, '.' + host];
    if (casti.length > 2) { domeny.push('.' + casti.slice(-2).join('.')); }
    d.cookie.split(';').forEach(function (r) {
      var n = r.split('=')[0].trim();
      var shoda = jmena.some(function (j) {
        return j.slice(-1) === '*' ? n.indexOf(j.slice(0, -1)) === 0 : n === j;
      });
      if (!shoda) { return; }
      domeny.forEach(function (dom) {
        d.cookie = n + '=;path=/;max-age=0' + (dom ? ';domain=' + dom : '');
      });
    });
  }

  /* ---------- spuštění zablokovaného obsahu ---------- */
  /* kat může být víc kategorií oddělených mezerou – stačí souhlas s kteroukoli (např. Google tag pro GA4 i Ads). */
  function povoleno(kat) {
    return String(kat || '').split(/\s+/).some(function (x) {
      return x === 'nezbytne' || (stav !== null && x !== '' && stav.indexOf(x) > -1);
    });
  }

  /* Změna souhlasu pro služby, které už běží: GTM (událost), Clarity a Sklik (vlastní consent API). */
  function predat(k) {
    w.dataLayer = w.dataLayer || [];
    if (w.marmalCookiesUdalost) { w.dataLayer.push(w.marmalCookiesUdalost(k)); }
    var m = w.marmalCookiesMapa ? w.marmalCookiesMapa(k) : null;
    if (!m) { return; }
    if (typeof w.clarity === 'function') {
      w.clarity('consentv2', { ad_Storage: m.ad_storage, analytics_Storage: m.analytics_storage });
    }
    if (typeof w.SEM === 'function') {
      w.SEM('updateConsent', { consent_mode: { ad_storage: m.ad_storage, ad_user_data: m.ad_user_data,
        ad_personalization: m.ad_personalization, functionality_storage: m.functionality_storage, analytics_storage: m.analytics_storage } });
    }
  }

  function aktivovat() {
    if (stav === null) { return; }
    Array.prototype.forEach.call(d.querySelectorAll('script[type="text/plain"][data-marmal-kat]'), function (s) {
      if (!povoleno(s.getAttribute('data-marmal-kat'))) { return; }
      var n = d.createElement('script');
      Array.prototype.forEach.call(s.attributes, function (a) {
        if (['type', 'data-marmal-kat', 'data-marmal-src', 'data-marmal-type'].indexOf(a.name) < 0) { n.setAttribute(a.name, a.value); }
      });
      if (s.getAttribute('data-marmal-type')) { n.type = s.getAttribute('data-marmal-type'); }
      if (s.getAttribute('data-marmal-src')) { n.src = s.getAttribute('data-marmal-src'); } else { n.text = s.text; }
      s.parentNode.replaceChild(n, s);
    });
    Array.prototype.forEach.call(d.querySelectorAll('.marmal-ck-ph[data-marmal-kat]'), function (p) {
      if (!povoleno(p.getAttribute('data-marmal-kat'))) { return; }
      var t = p.querySelector('template');
      if (t && t.content) { p.parentNode.replaceChild(t.content.cloneNode(true), p); }
    });
  }

  /* Náhled ve wrapperu s výškou 0 a paddingem (poměr stran přes padding) – roztáhnout přes celý obal. */
  function upravitNahledy() {
    Array.prototype.forEach.call(d.querySelectorAll('.marmal-ck-ph'), function (p) {
      var r = p.parentElement;
      if (!r) { return; }
      var cs = getComputedStyle(r);
      var vnitrni = r.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom) - p.offsetHeight;
      if (cs.position !== 'static' && parseFloat(cs.height) < 2 && vnitrni < 0) { p.classList.add('marmal-ck-ph--abs'); }
    });
  }

  /* ---------- lišta ---------- */
  function lista() {
    if (d.querySelector('.marmal-ck')) { return; }
    var v = C.vzhled;
    var b = el('div', 'marmal-ck marmal-ck--' + (v.layout === 'box' ? 'box marmal-ck--' + v.corner : v.layout));
    b.setAttribute('role', 'dialog');
    b.setAttribute('aria-labelledby', 'marmal-ck-nadpis');
    b.setAttribute('aria-describedby', 'marmal-ck-text');
    if (v.close) {
      var x = tlacitko('marmal-ck__x', '×', 'odlozit');
      x.setAttribute('aria-label', T.zavritX);
      b.appendChild(x);
    }
    var h = el('div', 'marmal-ck__title', T.title);
    h.id = 'marmal-ck-nadpis';
    var p = el('p', 'marmal-ck__text', T.text + ' ');
    p.id = 'marmal-ck-text';
    if (C.odkaz) {
      var a = el('a', 'marmal-ck__more', T.lMore);
      a.href = C.odkaz;
      p.appendChild(a);
    }
    var bt = el('div', 'marmal-ck__btns');
    bt.appendChild(tlacitko('marmal-ck__btn', T.lAccept, 'vse'));
    bt.appendChild(tlacitko('marmal-ck__btn', T.lReject, 'nic'));
    bt.appendChild(tlacitko('marmal-ck__link', T.lPrefs, 'nastaveni'));
    b.appendChild(h);
    b.appendChild(p);
    b.appendChild(bt);
    d.body.appendChild(b);
  }
  function zavritListu() {
    var b = d.querySelector('.marmal-ck');
    if (b) { b.remove(); }
  }

  function ikona() {
    var i = d.querySelector('.marmal-ck-ico');
    if (C.vzhled.icon === 'off' || stav === null || okno) { if (i) { i.remove(); } return; }
    if (i) { return; }
    i = tlacitko('marmal-ck-ico marmal-ck-ico--' + C.vzhled.icon, null, 'nastaveni');
    i.setAttribute('aria-label', T.ikona);
    i.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10 3.5 3.5 0 0 1-3.6-3.4A3.4 3.4 0 0 1 15 5.2 3.2 3.2 0 0 1 12 2Zm-4 7.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm3 6a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm5-1a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Zm-6.5-6a1 1 0 1 1 0 2 1 1 0 0 1 0-2Z"/></svg>';
    d.body.appendChild(i);
  }

  /* ---------- okno nastavení ---------- */
  function otevritOkno() {
    zavritOkno();
    navrat = d.activeElement;
    var o = el('div', 'marmal-ck-pf marmal-ck-pf--' + C.vzhled.prefs);
    o.setAttribute('data-ck', 'pozadi');
    var box = el('div', 'marmal-ck-pf__box');
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.setAttribute('aria-labelledby', 'marmal-ck-pf-nadpis');
    var hl = el('div', 'marmal-ck-pf__head');
    var h = el('div', 'marmal-ck-pf__title', T.pTitle);
    h.id = 'marmal-ck-pf-nadpis';
    hl.appendChild(h);
    hl.appendChild(tlacitko('marmal-ck__link', T.zavrit, 'zavrit'));
    var telo = el('div', 'marmal-ck-pf__body');
    var uvod = el('p', null, T.pIntro + ' ');
    if (C.odkaz) {
      var a = el('a', 'marmal-ck__more', T.lMore);
      a.href = C.odkaz;
      uvod.appendChild(a);
    }
    telo.appendChild(uvod);
    C.kategorie.forEach(function (k) {
      var c = el('div', 'marmal-ck-cat');
      var ch = el('div', 'marmal-ck-cat__head');
      var sw = el('label', 'marmal-ck-sw');
      var inp = el('input');
      inp.type = 'checkbox';
      inp.setAttribute('data-kat', k.id);
      inp.checked = k.zamceno || povoleno(k.id);
      inp.disabled = !!k.zamceno;
      inp.setAttribute('aria-label', k.nazev);
      sw.appendChild(inp);
      sw.appendChild(el('span'));
      var n = el('strong', 'marmal-ck-cat__name', k.nazev);
      if (k.zamceno) { n.appendChild(el('small', null, ' (' + T.vzdy + ')')); }
      var tg = tlacitko('marmal-ck-cat__more', T.seznam, 'seznam');
      tg.setAttribute('aria-expanded', 'false');
      ch.appendChild(sw);
      ch.appendChild(n);
      ch.appendChild(tg);
      c.appendChild(ch);
      c.appendChild(el('p', 'marmal-ck-cat__desc', k.popis));
      var tw = el('div', 'marmal-ck-cat__tab');
      tw.hidden = true;
      var tab = el('table');
      var th = el('tr');
      T.sloupce.forEach(function (s) { var x = el('th', null, s); x.scope = 'col'; th.appendChild(x); });
      var thead = el('thead');
      thead.appendChild(th);
      tab.appendChild(thead);
      var tb = el('tbody');
      k.cookies.forEach(function (r) {
        var tr = el('tr');
        r.forEach(function (v) { tr.appendChild(el('td', null, v)); });
        tb.appendChild(tr);
      });
      tab.appendChild(tb);
      tw.appendChild(tab);
      c.appendChild(tw);
      telo.appendChild(c);
    });
    var pa = el('div', 'marmal-ck-pf__foot');
    pa.appendChild(tlacitko('marmal-ck__btn', T.lAccept, 'vse'));
    pa.appendChild(tlacitko('marmal-ck__btn', T.lReject, 'nic'));
    pa.appendChild(tlacitko('marmal-ck__btn', T.ulozit, 'ulozit'));
    box.appendChild(hl);
    box.appendChild(telo);
    box.appendChild(pa);
    o.appendChild(box);
    d.body.appendChild(o);
    okno = o;
    ikona();
    box.querySelector('button').focus();
  }
  function zavritOkno() {
    if (!okno) { return; }
    okno.remove();
    okno = null;
    if (navrat && navrat.focus && d.contains(navrat)) { navrat.focus(); }
    ikona();
  }

  /* ---------- ovládání ---------- */
  d.addEventListener('click', function (e) {
    var t = e.target;
    var otevrit = t.closest && t.closest('[data-marmal-cookies="marmal"], .marmal-cookies-nastaveni, a[href$="#nastaveni-cookies"]');
    if (otevrit) {
      e.preventDefault();
      otevritOkno();
      return;
    }
    var pov = t.closest && t.closest('[data-marmal-povolit]');
    if (pov) {
      var k = (stav || []).slice();
      var kat = pov.getAttribute('data-marmal-povolit');
      if (volitelne.indexOf(kat) > -1 && k.indexOf(kat) < 0) { k.push(kat); }
      ulozit(k, 'vyber');
      return;
    }
    var b = t.closest && t.closest('[data-ck]');
    if (!b) { return; }
    var a = b.getAttribute('data-ck');
    if (a === 'pozadi' && t !== b) { return; }
    if (a === 'vse') { ulozit(volitelne.slice(), 'vse'); }
    else if (a === 'nic') { ulozit([], 'nic'); }
    else if (a === 'ulozit') {
      var vyber = [];
      Array.prototype.forEach.call(okno.querySelectorAll('input[data-kat]'), function (i) {
        if (i.checked && volitelne.indexOf(i.getAttribute('data-kat')) > -1) { vyber.push(i.getAttribute('data-kat')); }
      });
      ulozit(vyber, 'vyber');
    }
    else if (a === 'nastaveni') { otevritOkno(); }
    else if (a === 'odlozit') { zavritListu(); }
    else if (a === 'zavrit' || a === 'pozadi') { zavritOkno(); }
    else if (a === 'seznam') {
      var tw = b.closest('.marmal-ck-cat').querySelector('.marmal-ck-cat__tab');
      tw.hidden = !tw.hidden;
      b.setAttribute('aria-expanded', String(!tw.hidden));
    }
  });

  d.addEventListener('keydown', function (e) {
    if (!okno) { return; }
    if (e.key === 'Escape') { zavritOkno(); return; }
    if (e.key !== 'Tab') { return; }
    var f = okno.querySelectorAll('button, a[href], input:not([disabled])');
    if (!f.length) { return; }
    var prvni = f[0];
    var posledni = f[f.length - 1];
    if (e.shiftKey && d.activeElement === prvni) { e.preventDefault(); posledni.focus(); }
    else if (!e.shiftKey && d.activeElement === posledni) { e.preventDefault(); prvni.focus(); }
  });

  /* Veřejné API pro jiné skripty: MarmalCookies.otevrit(), MarmalCookies.souhlas('analyticke'). */
  w.MarmalCookies = {
    otevrit: otevritOkno,
    souhlas: function (kat) { return povoleno(kat); }
  };

  function start() {
    autoInk();
    upravitNahledy();
    if (stav === null) { lista(); } else { aktivovat(); ikona(); }
  }
  if (d.readyState === 'loading') { d.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
