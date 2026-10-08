/* MarMal Effects – galerie náhledů. Data: window.MM_EFFECTS_DATA = { categories, effects } */
(function () {
  'use strict';

  var IMG = 'data:image/svg+xml,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 320">' +
    '<defs><linearGradient id="s" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f9c784"/><stop offset=".55" stop-color="#e98a6b"/><stop offset="1" stop-color="#8e5f86"/></linearGradient></defs>' +
    '<rect width="480" height="320" fill="url(#s)"/>' +
    '<circle cx="340" cy="120" r="46" fill="#fff4d6" opacity=".9"/>' +
    '<path d="M0 230 L90 150 L170 215 L260 120 L360 210 L420 170 L480 205 V320 H0Z" fill="#5a3f6b"/>' +
    '<path d="M0 262 L110 205 L210 255 L310 196 L400 250 L480 228 V320 H0Z" fill="#3a2b4d"/>' +
    '<path d="M0 290 C120 270 220 300 320 282 S440 290 480 280 V320 H0Z" fill="#241c33"/>' +
    '</svg>'
  );

  var ICONS = {
    bolt: '<path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/>',
    bell: '<path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22zm7-6v-5a7 7 0 0 0-5.5-6.84V3a1.5 1.5 0 0 0-3 0v1.16A7 7 0 0 0 5 11v5l-2 2v1h18v-1l-2-2z"/>',
    arrow: '<path d="M4 11h12.17l-5.58-5.59L12 4l8 8-8 8-1.41-1.41L16.17 13H4z"/>',
    'arrow-down': '<path d="M11 4h2v12.17l5.59-5.58L20 12l-8 8-8-8 1.41-1.41L11 16.17z"/>',
    sun: '<circle cx="12" cy="12" r="4"/><path d="M11 1h2v4h-2zM11 19h2v4h-2zM1 11h4v2H1zM19 11h4v2h-4zM4.2 5.6l1.4-1.4 2.8 2.8-1.4 1.4zM15.6 17l1.4-1.4 2.8 2.8-1.4 1.4zM4.2 18.4l2.8-2.8 1.4 1.4-2.8 2.8zM15.6 7l2.8-2.8 1.4 1.4-2.8 2.8z"/>'
  };
  function svg(name) {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' + (ICONS[name] || ICONS.bolt) + '</svg>';
  }

  function esc(s) {
    return String(s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  function demo(e) {
    var c = esc(e.cls + (e.extra ? ' ' + e.extra : ''));
    switch (e.demo) {
      case 'btn':
        return '<div class="' + c + '"><a href="#" class="button-atom mm-d-btn">Nezávazná poptávka</a></div>';
      case 'link':
        return '<p class="mm-d-p"><a href="#" class="' + c + '">Zobrazit reference</a></p>';
      case 'card': case 'card-dark': case 'card-glass':
        return '<div class="mm-d-card ' + c + '"><span class="mm-d-ico"></span><h4>Tvorba webu</h4><p>WordPress a Breakdance, na míru vaší firmě.</p></div>';
      case 'cols':
        return '<div class="mm-d-cols ' + c + '"><div><b>12</b><small>let praxe</small></div><div><b>80+</b><small>webů</small></div><div><b>24 h</b><small>odezva</small></div></div>';
      case 'img':
        return '<div class="mm-d-img ' + c + '"><img src="' + IMG + '" alt="Ukázková fotka krajiny"></div>';
      case 'hero':
        return '<section class="mm-d-hero ' + c + '"><small>Černošice · Praha</small><h3>Weby, které vydělávají</h3></section>';
      case 'hero-photo':
        return '<section class="mm-d-hero mm-d-hero--photo ' + c + '" style="background-image:url(\'' + IMG + '\')"><small>Černošice · Praha</small><h3>Weby, které vydělávají</h3></section>';
      case 'heading':
        return '<h3 class="mm-d-h ' + c + '">Weby pro malé firmy</h3>';
      case 'heading-span':
        return '<h3 class="mm-d-h">Weby, které <span class="' + c + '">přivedou zákazníky</span></h3>';
      case 'eyebrow':
        return '<div><div class="mm-d-small ' + c + '">Naše služby</div><h3 class="mm-d-h">Co pro vás uděláme</h3></div>';
      case 'counter':
        return '<div class="mm-d-counter"><div class="mm-d-num ' + c + '">250+</div><span>spuštěných webů</span></div>';
      case 'bg':
        return '<div class="mm-d-bg ' + c + '">Sekce s pozadím</div>';
      case 'bg-color':
        return '<div class="mm-d-bg mm-d-bg--color ' + c + '">Sekce s pozadím</div>';
      case 'anim':
        return '<div class="mm-d-bg mm-d-anim ' + c + '"><div><small>Nová nabídka</small><strong>Weby na míru</strong></div></div>';
      case 'anim-dark':
        return '<div class="mm-d-bg mm-d-anim mm-d-anim--dark ' + c + '"><div><small>Nová nabídka</small><strong>Weby na míru</strong></div></div>';
      case 'icon':
        return '<span class="mm-d-icon ' + c + '">' + svg(e.icon) + '</span>';
      case 'live':
        return '<span class="mm-d-live ' + c + '">Volné termíny na listopad</span>';
      case 'group':
        return '<div class="mm-d-card ' + c + '"><span class="mm-d-icon mm-d-icon--sm mm-icon-circle">' + svg('bolt') + '</span><h4>Rychlé weby</h4><p>Najeď kamkoliv na kartu.</p></div>';
      default:
        return '<div class="mm-d-box ' + c + '"><strong>Ukázkový prvek</strong><span>Najeď myší</span></div>';
    }
  }

  function stageClass(e) {
    if (e.demo === 'card-dark') return ' mm-g-stage--dark';
    if (e.demo === 'card-glass') return ' mm-g-stage--photo';
    return '';
  }

  function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(text).catch(function () { return fallbackCopy(text); });
    }
    return Promise.resolve(fallbackCopy(text));
  }
  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (err) { /* ruční kopie */ }
    document.body.removeChild(ta);
  }

  function mount(root) {
    var data = window.MM_EFFECTS_DATA;
    if (!root || !data) return;

    var cats = data.categories;
    var effects = data.effects;
    var state = { cat: 'all', q: '' };

    root.innerHTML =
      '<div class="mm-g-head">' +
        '<div><h1>MarMal Efekty</h1><p>Klikni na název třídy, zkopíruje se. V Breakdance ji vlož do pole Classes u vybraného prvku. Víc tříd odděl mezerou.</p></div>' +
        '<label class="mm-g-search"><span class="screen-reader-text">Hledat efekt</span><input id="mm-g-q" type="search" placeholder="Hledat: tlačítko, záře, scroll…" autocomplete="off"></label>' +
      '</div>' +
      '<div class="mm-g-colors" role="group" aria-label="Barvy náhledu">' +
        '<span class="mm-g-colors-label">Barvy náhledu</span>' +
        '<label><input type="color" id="mm-g-c1" data-var="--mm-accent"> Barva 1</label>' +
        '<label><input type="color" id="mm-g-c2" data-var="--mm-accent-2"> Barva 2</label>' +
        '<label><input type="color" id="mm-g-c3" data-var="--mm-accent-3"> Barva 3</label>' +
        '<button type="button" class="mm-g-reset">Obnovit</button>' +
        '<span class="mm-g-colors-note">Jen pro zkoušku. Barvy webu nastavíš v Nastavení.</span>' +
      '</div>' +
      '<div class="mm-g-filters" role="toolbar" aria-label="Kategorie"></div>' +
      '<div class="mm-g-list"></div>' +
      '<div class="mm-g-toast" role="status" aria-live="polite"></div>';

    var byCls = {};
    effects.forEach(function (e) { byCls[e.cls] = e; });

    var colorInputs = root.querySelectorAll('.mm-g-colors input[type="color"]');
    function toHex(value) {
      var ctx = document.createElement('canvas').getContext('2d');
      ctx.fillStyle = '#000000';
      ctx.fillStyle = (value || '').trim() || '#000000';
      var out = ctx.fillStyle;
      if (out.charAt(0) === '#') return out;
      var m = out.match(/\d+(\.\d+)?/g);
      if (!m) return '#000000';
      return '#' + m.slice(0, 3).map(function (n) { return ('0' + Math.round(+n).toString(16)).slice(-2); }).join('');
    }
    function syncColors() {
      var cs = getComputedStyle(root);
      colorInputs.forEach(function (inp) { inp.value = toHex(cs.getPropertyValue(inp.getAttribute('data-var'))); });
    }
    colorInputs.forEach(function (inp) {
      inp.addEventListener('input', function () { root.style.setProperty(inp.getAttribute('data-var'), inp.value); });
    });
    root.querySelector('.mm-g-reset').addEventListener('click', function () {
      colorInputs.forEach(function (inp) { root.style.removeProperty(inp.getAttribute('data-var')); });
      syncColors();
    });
    syncColors();

    var filtersEl = root.querySelector('.mm-g-filters');
    var listEl = root.querySelector('.mm-g-list');
    var toastEl = root.querySelector('.mm-g-toast');
    var toastTimer;

    function count(catId) {
      return effects.filter(function (e) { return catId === 'all' || e.cat === catId; }).length;
    }
    filtersEl.innerHTML = [{ id: 'all', name: 'Vše' }].concat(cats).map(function (c) {
      return '<button type="button" class="mm-g-filter" data-cat="' + c.id + '" aria-pressed="' + (c.id === 'all') + '">' +
        esc(c.name) + '<span>' + count(c.id) + '</span></button>';
    }).join('');

    function matches(e) {
      if (state.cat !== 'all' && e.cat !== state.cat) return false;
      if (!state.q) return true;
      var hay = (e.cls + ' ' + e.name + ' ' + e.desc).toLowerCase();
      return state.q.split(/\s+/).every(function (w) { return hay.indexOf(w) !== -1; });
    }

    function render() {
      var html = '';
      cats.forEach(function (cat) {
        var items = effects.filter(function (e) { return e.cat === cat.id && matches(e); });
        if (!items.length) return;
        html += '<section class="mm-g-section" id="mm-cat-' + cat.id + '">' +
          '<div class="mm-g-section-head"><h2>' + esc(cat.name) + '</h2><p>' + esc(cat.hint) + '</p></div>' +
          '<div class="mm-g-grid">' + items.map(function (e) {
            var badges = (e.js ? '<span class="mm-g-badge" title="Potřebuje JavaScript pluginu (načítá se automaticky)">JS</span>' : '');
            var fullClass = e.cls + (e.extra ? ' ' + e.extra : '');
            return '<article class="mm-g-item" data-cls="' + esc(e.cls) + '">' +
              '<div class="mm-g-stage' + stageClass(e) + '">' + demo(e) + '</div>' +
              '<div class="mm-g-body">' +
                '<div class="mm-g-title"><h3>' + esc(e.name) + '</h3><div class="mm-g-badges">' + badges + '</div></div>' +
                '<p class="mm-g-desc">' + esc(e.desc) + '</p>' +
                '<div class="mm-g-actions">' +
                  '<button type="button" class="mm-g-copy" data-copy="' + esc(fullClass) + '">' + esc(fullClass) + '</button>' +
                  (e.replay ? '<button type="button" class="mm-g-replay">Přehrát</button>' : '') +
                  (e.variants ? '<div class="mm-g-variants" role="group" aria-label="Intenzita">' + e.variants.map(function (v, i) {
                    return '<button type="button" class="mm-g-variant" data-v="' + i + '" aria-pressed="' + (v.cls === '') + '">' + esc(v.label) + '</button>';
                  }).join('') + '</div>' : '') +
                '</div>' +
              '</div>' +
            '</article>';
          }).join('') + '</div></section>';
      });
      listEl.innerHTML = html || '<p class="mm-g-empty">Nic nenalezeno. Zkus jiné slovo.</p>';
      if (window.MMEffects) window.MMEffects.init(listEl);
    }

    function toast(msg) {
      toastEl.textContent = msg;
      toastEl.classList.add('is-on');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(function () { toastEl.classList.remove('is-on'); }, 1600);
    }

    filtersEl.addEventListener('click', function (ev) {
      var b = ev.target.closest('.mm-g-filter');
      if (!b) return;
      state.cat = b.getAttribute('data-cat');
      filtersEl.querySelectorAll('.mm-g-filter').forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
      render();
    });

    root.querySelector('#mm-g-q').addEventListener('input', function (ev) {
      state.q = ev.target.value.trim().toLowerCase();
      render();
    });

    listEl.addEventListener('click', function (ev) {
      var copy = ev.target.closest('.mm-g-copy');
      if (copy) {
        var text = copy.getAttribute('data-copy');
        copyText(text).then(function () {
          copy.classList.add('is-copied');
          setTimeout(function () { copy.classList.remove('is-copied'); }, 1400);
          toast('Zkopírováno: ' + text);
        });
        return;
      }
      var vb = ev.target.closest('.mm-g-variant');
      if (vb) {
        var item = vb.closest('.mm-g-item');
        var eff = byCls[item.getAttribute('data-cls')];
        var chosen = eff.variants[+vb.getAttribute('data-v')];
        var target = item.querySelector('.mm-g-stage .' + eff.cls);
        eff.variants.forEach(function (v) { if (v.cls) target.classList.remove(v.cls); });
        if (chosen.cls) target.classList.add(chosen.cls);
        item.querySelectorAll('.mm-g-variant').forEach(function (b) { b.setAttribute('aria-pressed', String(b === vb)); });
        var chip = item.querySelector('.mm-g-copy');
        var full = eff.cls + (chosen.cls ? ' ' + chosen.cls : '');
        chip.setAttribute('data-copy', full);
        chip.textContent = full;
        return;
      }
      var rep = ev.target.closest('.mm-g-replay');
      if (rep && window.MMEffects) {
        window.MMEffects.replay(rep.closest('.mm-g-item').querySelector('.mm-g-stage'));
        return;
      }
      if (ev.target.closest('.mm-g-stage a')) ev.preventDefault();
    });

    render();
  }

  function start() { mount(document.getElementById('mm-gallery')); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();
