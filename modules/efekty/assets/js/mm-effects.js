/*!
 * MarMal Effects – JS část (animace při scrollu, počítadla, 3D náklon, reflektor,
 * magnetická tlačítka, parallax). Bez závislostí, ~3 kB.
 */
(function () {
  'use strict';

  var doc = document;
  var html = doc.documentElement;
  var force = window.MM_EFFECTS_FORCE === true;

  // V editoru Breakdance nic neschováváme, aby prvky nebyly neviditelné při úpravách.
  var inBuilder = !force && (
    /[?&](breakdance|breakdance_iframe)=/.test(location.search) ||
    (window.self !== window.top && /breakdance/.test(document.referrer + location.href))
  );

  var reduced = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches;

  var REVEAL = '.mm-fade-up,.mm-fade-in,.mm-fade-left,.mm-fade-right,.mm-zoom-in,.mm-blur-in,' +
               '.mm-stagger,.mm-img-reveal,.mm-text-highlight,.mm-text-underline,.mm-counter';
  var POINTER = '.mm-card-tilt,.mm-card-spotlight,.mm-btn-magnetic';
  var ANIM_BG = '.mm-anim-gradient,.mm-anim-orb,.mm-anim-orbs,.mm-anim-aurora,.mm-anim-mesh,.mm-anim-conic,' +
                '.mm-anim-waves,.mm-anim-beam,.mm-anim-glow,.mm-anim-dots,.mm-anim-lines,.mm-anim-grain';

  // Animovaná pozadí mimo obrazovku pozastavíme (šetří procesor a baterii).
  var pauseIo = ('IntersectionObserver' in window) ? new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      entry.target.classList.toggle('mm-paused', !entry.isIntersecting);
    });
  }, { rootMargin: '100px 0px' }) : null;

  if (!inBuilder) html.classList.add('mm-js');

  /* ---------- Počítadlo ---------- */
  function runCounter(el) {
    var original = el.getAttribute('data-mm-final');
    if (original === null) {
      original = el.textContent;
      el.setAttribute('data-mm-final', original);
    }
    var m = original.match(/^(\D*?)(\d[\d\s .,]*)(.*)$/);
    if (!m) return;
    var numStr = m[2].trim();
    var grouped = /[\s ]/.test(numStr);
    var normalized = numStr.replace(/[\s ]/g, '').replace(',', '.');
    var decimals = (normalized.split('.')[1] || '').length;
    var target = parseFloat(normalized);
    if (isNaN(target)) return;
    if (reduced) { el.textContent = original; return; }

    var duration = parseFloat(el.getAttribute('data-mm-duration')) || 1600;
    var start = null;
    function fmt(v) {
      return v.toLocaleString('cs-CZ', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
        useGrouping: grouped
      });
    }
    function step(t) {
      if (start === null) start = t;
      var p = Math.min((t - start) / duration, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = m[1] + fmt(target * eased) + m[3];
      if (p < 1) requestAnimationFrame(step);
      else el.textContent = original;
    }
    requestAnimationFrame(step);
  }

  /* ---------- Odkrývání při scrollu ---------- */
  var io = null;
  if ('IntersectionObserver' in window) {
    io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        show(entry.target);
        io.unobserve(entry.target);
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });
  }

  function show(el) {
    el.classList.add('mm-in');
    if (el.classList.contains('mm-counter')) runCounter(el);
  }

  function prepare(el) {
    if (el.classList.contains('mm-stagger')) {
      Array.prototype.forEach.call(el.children, function (child, i) {
        child.style.setProperty('--mm-i', i);
      });
    }
  }

  /* ---------- Parallax ---------- */
  var parallaxEls = [];
  var ticking = false;
  function updateParallax() {
    ticking = false;
    var vh = window.innerHeight;
    parallaxEls.forEach(function (el) {
      var r = el.getBoundingClientRect();
      if (r.bottom < -100 || r.top > vh + 100) return;
      var offset = (r.top + r.height / 2 - vh / 2) * -0.12;
      offset = Math.max(-60, Math.min(60, offset));
      el.style.setProperty('--mm-py', offset.toFixed(1) + 'px');
    });
  }
  function requestParallax() {
    if (!ticking) { ticking = true; requestAnimationFrame(updateParallax); }
  }

  /* ---------- Inicializace (lze volat znovu na nový obsah) ---------- */
  function init(root) {
    root = root || doc;
    root.querySelectorAll(REVEAL).forEach(function (el) {
      if (el.hasAttribute('data-mm-ready')) return;
      el.setAttribute('data-mm-ready', '');
      prepare(el);
      if (inBuilder || !io || reduced) show(el);
      else io.observe(el);
    });
    if (pauseIo) {
      root.querySelectorAll(ANIM_BG).forEach(function (el) {
        if (el.hasAttribute('data-mm-anim')) return;
        el.setAttribute('data-mm-anim', '');
        pauseIo.observe(el);
      });
    }
    if (!reduced) {
      root.querySelectorAll('.mm-img-parallax').forEach(function (el) {
        if (parallaxEls.indexOf(el) === -1) parallaxEls.push(el);
      });
      if (parallaxEls.length) requestParallax();
    }
  }

  /* Přehraje animaci znovu (používá galerie náhledů). */
  function replay(el) {
    var targets = el.matches(REVEAL) ? [el] : Array.prototype.slice.call(el.querySelectorAll(REVEAL));
    targets.forEach(function (t) {
      t.classList.remove('mm-in');
      if (t.classList.contains('mm-counter') && t.hasAttribute('data-mm-final')) t.textContent = '0';
    });
    void el.offsetWidth;
    setTimeout(function () { targets.forEach(show); }, 60);
  }

  /* ---------- Efekty podle kurzoru (delegované, fungují i na obsah přidaný později) ---------- */
  var current = null;
  function reset(el) {
    ['--mm-rx', '--mm-ry', '--mm-mx', '--mm-my'].forEach(function (p) { el.style.removeProperty(p); });
  }
  if (finePointer && !reduced) {
    doc.addEventListener('pointermove', function (e) {
      var t = e.target && e.target.closest ? e.target.closest(POINTER) : null;
      if (current && current !== t) reset(current);
      current = t;
      if (!t) return;
      var r = t.getBoundingClientRect();
      var x = e.clientX - r.left;
      var y = e.clientY - r.top;
      var cl = t.classList;
      if (cl.contains('mm-card-tilt')) {
        t.style.setProperty('--mm-ry', ((x / r.width - 0.5) * 10).toFixed(2) + 'deg');
        t.style.setProperty('--mm-rx', ((0.5 - y / r.height) * 10).toFixed(2) + 'deg');
      }
      if (cl.contains('mm-card-spotlight')) {
        t.style.setProperty('--mm-x', x.toFixed(0) + 'px');
        t.style.setProperty('--mm-y', y.toFixed(0) + 'px');
      }
      if (cl.contains('mm-btn-magnetic')) {
        var clamp = function (v) { return Math.max(-12, Math.min(12, v)); };
        t.style.setProperty('--mm-mx', clamp((x - r.width / 2) * 0.3).toFixed(1) + 'px');
        t.style.setProperty('--mm-my', clamp((y - r.height / 2) * 0.3).toFixed(1) + 'px');
      }
    }, { passive: true });
    doc.addEventListener('mouseout', function (e) {
      if (!e.relatedTarget && current) { reset(current); current = null; }
    });
  }

  if (!reduced) {
    window.addEventListener('scroll', requestParallax, { passive: true });
    window.addEventListener('resize', requestParallax, { passive: true });
  }

  window.MMEffects = { init: init, replay: replay };

  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', function () { init(); });
  else init();
})();
