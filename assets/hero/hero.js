/**
 * superHERO – chování elementu (Marmal – Breakdance Plus).
 * Načte se jen na stránkách se superHERO. Bez externích knihoven.
 *
 * Nastavení čte z atributu data-mh (JSON z PHP). Respektuje prefers-reduced-motion,
 * šetření dat a tlačítko „Pozastavit pohyb“. Věci závislé na čase (otevírací doba,
 * termíny slidů) počítá až tady, aby je nezamrazila page cache.
 */
(function () {
  'use strict';

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var canHover = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var conn = navigator.connection || {};
  var slowNet = !!(conn.saveData || /(^|-)(2g|3g)$/.test(conn.effectiveType || ''));

  function isMobile() {
    return window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
  }

  function pad(n) {
    return (n < 10 ? '0' : '') + n;
  }

  function each(list, fn) {
    Array.prototype.forEach.call(list, fn);
  }

  /* Dnešní den v týdnu a minuty v časové zóně webu (fallback: čas návštěvníka). */
  function now(tz) {
    var d = new Date();
    if (tz) {
      try {
        var parts = new Intl.DateTimeFormat('en-US', { timeZone: tz, weekday: 'short', hour: 'numeric', minute: 'numeric', month: 'numeric', day: 'numeric', hourCycle: 'h23' }).formatToParts(d);
        var o = {};
        parts.forEach(function (p) { o[p.type] = p.value; });
        var days = { Sun: 0, Mon: 1, Tue: 2, Wed: 3, Thu: 4, Fri: 5, Sat: 6 };
        return { day: days[o.weekday], min: (+o.hour % 24) * 60 + (+o.minute), md: (+o.month) * 100 + (+o.day) };
      } catch (e) { /* neznámá zóna – použijeme místní čas */ }
    }
    return { day: d.getDay(), min: d.getHours() * 60 + d.getMinutes(), md: (d.getMonth() + 1) * 100 + d.getDate() };
  }

  /* Termín slidu "MMDD-MMDD" (i přes Nový rok). */
  function inTerm(term, md) {
    var m = /^(\d{4})-(\d{4})$/.exec(term || '');
    if (!m) {
      return true;
    }
    var a = +m[1], b = +m[2];
    return a <= b ? (md >= a && md <= b) : (md >= a || md <= b);
  }

  function init(hero) {
    if (hero.getAttribute('data-mh-init')) {
      return;
    }
    hero.setAttribute('data-mh-init', '1');

    var cfg = {};
    try { cfg = JSON.parse(hero.getAttribute('data-mh') || '{}') || {}; } catch (e) { cfg = {}; }
    var builder = hero.classList.contains('marmal-hero--builder');
    var state = { paused: reduce, hover: false };

    /* ---------- video pozadí: jen když dává smysl ---------- */
    var bgVideo = hero.querySelector('.marmal-hero__bg-video');
    if (bgVideo) {
      var vcfg = cfg.video || {};
      var allow = !reduce && !(vcfg.setrit && slowNet) && (!isMobile() || vcfg.mobil);
      if (allow && bgVideo.getAttribute('data-src')) {
        bgVideo.src = bgVideo.getAttribute('data-src');
        bgVideo.addEventListener('playing', function () { bgVideo.classList.add('is-playing'); });
        var pr = bgVideo.play();
        if (pr && pr.catch) { pr.catch(function () {}); }
      }
    }
    if (reduce) {
      each(hero.querySelectorAll('.marmal-hero__pic video'), function (v) { try { v.pause(); } catch (e) {} });
    }

    /* ---------- slider ---------- */
    var slider = null;
    if (cfg.slider && cfg.slider.n > 1) {
      slider = initSlider(hero, cfg, state);
    }

    /* ---------- rotující slova ---------- */
    if (!reduce) {
      each(hero.querySelectorAll('.marmal-hero-rot'), function (r) { initRotator(r, state); });
    }

    /* ---------- napočítání čísel ---------- */
    var counters = hero.querySelectorAll('.marmal-hero-cnt');
    if (counters.length && !reduce && 'IntersectionObserver' in window) {
      var lang = document.documentElement.lang || 'cs-CZ';
      var fmt = function (v, dec) {
        try { return v.toLocaleString(lang, { minimumFractionDigits: dec, maximumFractionDigits: dec }); } catch (e) { return v.toFixed(dec); }
      };
      each(counters, function (el) { el.textContent = fmt(0, +el.getAttribute('data-dec') || 0); });
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (!en.isIntersecting) { return; }
          io.disconnect();
          var t0 = performance.now(), D = 1600;
          (function step(t) {
            var p = Math.min(1, (t - t0) / D), e = 1 - Math.pow(1 - p, 3);
            each(counters, function (el) {
              el.textContent = fmt((parseFloat(el.getAttribute('data-to')) || 0) * e, +el.getAttribute('data-dec') || 0);
            });
            if (p < 1) { requestAnimationFrame(step); }
          })(t0);
        });
      }, { threshold: .3 });
      io.observe(hero);
    }

    /* ---------- pozastavit pohyb (WCAG 2.2.2) ---------- */
    var pauseBtn = hero.querySelector('.marmal-hero__pause');
    function setPaused(on) {
      state.paused = on;
      hero.classList.toggle('marmal-hero--paused', on);
      each(hero.querySelectorAll('video'), function (v) {
        try { if (on) { v.pause(); } else if (v.src) { var p = v.play(); if (p && p.catch) { p.catch(function () {}); } } } catch (e) {}
      });
      if (pauseBtn) {
        var label = pauseBtn.getAttribute(on ? 'data-l-play' : 'data-l-pause') || '';
        pauseBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
        pauseBtn.setAttribute('aria-label', label);
        pauseBtn.setAttribute('title', label);
      }
    }
    if (pauseBtn) {
      if (reduce) { pauseBtn.hidden = true; }
      pauseBtn.addEventListener('click', function () { setPaused(!state.paused); });
    }

    /* ---------- video v lightboxu (sdílený lightbox Galerie Plus) ---------- */
    hero.addEventListener('click', function (e) {
      var t = e.target.closest ? e.target.closest('[data-mh-video]') : null;
      if (!t || !hero.contains(t)) { return; }
      var item = hero.querySelector('[data-lb-item]');
      if (!item) { return; }
      e.preventDefault();
      if (window.MarmalLightbox && window.MarmalLightbox.open) {
        window.MarmalLightbox.open(hero, item);
      } else {
        window.open(item.getAttribute('href'), '_blank', 'noopener');
      }
    });

    /* ---------- přichycené CTA na mobilu ---------- */
    var sticky = hero.querySelector('.marmal-hero__sticky');
    if (sticky && !builder && 'IntersectionObserver' in window) {
      var cs = getComputedStyle(hero);
      ['--mh-brand', '--mh-accent', '--mh-dark', '--mh-b-r', '--mh-b-py', '--mh-b-px', '--mh-b-fs'].forEach(function (p) {
        var val = cs.getPropertyValue(p).trim();
        if (val) { sticky.style.setProperty(p, val); }
      });
      document.body.appendChild(sticky);
      sticky.classList.add('is-mounted');
      var link = sticky.querySelector('a');
      new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          var show = !en.isIntersecting && en.boundingClientRect.top < 0;
          sticky.classList.toggle('is-visible', show);
          sticky.setAttribute('aria-hidden', show ? 'false' : 'true');
          if (link) { link.setAttribute('tabindex', show ? '0' : '-1'); }
        });
      }).observe(hero);
    }

    /* ---------- otevírací doba (živý stav) ---------- */
    var open = hero.querySelector('[data-open]');
    if (open && cfg.otevreno) {
      var updateOpen = function () { renderOpen(open, cfg); };
      updateOpen();
      setInterval(updateOpen, 60000);
    }

    /* ---------- efekt při odscrollování ---------- */
    if (cfg.odjezd && cfg.odjezd !== 'zadny' && !reduce && !builder) {
      var ticking = false;
      var onScroll = function () {
        if (ticking) { return; }
        ticking = true;
        requestAnimationFrame(function () {
          ticking = false;
          var r = hero.getBoundingClientRect();
          var p = r.height ? Math.min(1, Math.max(0, -r.top / r.height)) : 0;
          hero.style.setProperty('--mh-sp', p.toFixed(3));
        });
      };
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();
    }

    /* ---------- efekty za kurzorem ---------- */
    if (canHover && !reduce) {
      if (cfg.svetlo) {
        hero.addEventListener('mousemove', function (e) {
          var r = hero.getBoundingClientRect();
          hero.style.setProperty('--mh-mx', ((e.clientX - r.left) / r.width * 100).toFixed(1) + '%');
          hero.style.setProperty('--mh-my', ((e.clientY - r.top) / r.height * 100).toFixed(1) + '%');
        });
      }
      if (cfg.magnet) {
        each(hero.querySelectorAll('.marmal-hero__btns .marmal-hero-btn'), function (b) {
          b.addEventListener('mousemove', function (e) {
            var r = b.getBoundingClientRect();
            b.style.transform = 'translate(' + ((e.clientX - r.left - r.width / 2) * .3).toFixed(1) + 'px,' + ((e.clientY - r.top - r.height / 2) * .4).toFixed(1) + 'px)';
          });
          b.addEventListener('mouseleave', function () { b.style.transform = ''; });
        });
      }
      if (cfg.naklon) {
        each(hero.querySelectorAll('.marmal-hero__mwrap'), function (m) {
          m.addEventListener('mousemove', function (e) {
            var r = m.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
            m.style.transform = 'perspective(900px) rotateY(' + (x * 14).toFixed(2) + 'deg) rotateX(' + (-y * 14).toFixed(2) + 'deg) scale(1.02)';
          });
          m.addEventListener('mouseleave', function () { m.style.transform = ''; });
        });
      }
    }

    return slider;
  }

  function initSlider(hero, cfg, state) {
    var n = cfg.slider.n;
    var md = now(cfg.tz).md;
    var allowed = [];
    for (var i = 0; i < n; i++) {
      var sc = hero.querySelector('.marmal-hero__sc[data-s="' + i + '"]');
      if (!sc || inTerm(sc.getAttribute('data-term'), md)) { allowed.push(i); }
    }
    var nav = hero.querySelector('.marmal-hero__nav');
    each(hero.querySelectorAll('.marmal-hero__dots [data-go]'), function (b) {
      if (allowed.indexOf(+b.getAttribute('data-go')) < 0) { b.hidden = true; }
    });
    var total = hero.querySelector('[data-total]');
    if (total) { total.textContent = pad(allowed.length); }
    if (allowed.length < 2) {
      if (nav) { nav.hidden = true; }
      return null;
    }

    var pos = 0;
    function restart(el) {
      el.style.animation = 'none';
      void el.offsetWidth;
      el.style.animation = '';
    }
    function go(k) {
      pos = (k + allowed.length) % allowed.length;
      var idx = allowed[pos];
      each(hero.querySelectorAll('[data-s]'), function (el) {
        var len = 0;
        each(el.parentNode.children, function (c) { if (c.hasAttribute('data-s')) { len++; } });
        var on = +el.getAttribute('data-s') === idx % len;
        if (el.classList.contains('is-on') && !on) { el.classList.add('is-prev'); } else { el.classList.remove('is-prev'); }
        el.classList.toggle('is-on', on);
        if (el.classList.contains('marmal-hero__sc')) {
          el.setAttribute('aria-hidden', on ? 'false' : 'true');
          if (on) { each(el.querySelectorAll('.marmal-hero-a, .marmal-hero-w'), restart); }
        }
      });
      each(hero.querySelectorAll('.marmal-hero__dots [data-go]'), function (b) {
        b.setAttribute('aria-current', +b.getAttribute('data-go') === idx ? 'true' : 'false');
      });
      var cur = hero.querySelector('[data-cur]');
      if (cur) { cur.textContent = pad(pos + 1); }
      var prog = hero.querySelector('.marmal-hero__prog i');
      if (prog) { restart(prog); }
    }
    each(hero.querySelectorAll('.marmal-hero__sc'), function (el) {
      if (!el.classList.contains('is-on')) { el.setAttribute('aria-hidden', 'true'); }
    });

    hero.addEventListener('click', function (e) {
      var g = e.target.closest ? e.target.closest('[data-go]') : null;
      var s = e.target.closest ? e.target.closest('[data-step]') : null;
      if (g) { go(allowed.indexOf(+g.getAttribute('data-go'))); }
      if (s) { go(pos + (+s.getAttribute('data-step'))); }
    });

    var tx = null;
    hero.addEventListener('touchstart', function (e) { tx = e.touches[0].clientX; }, { passive: true });
    hero.addEventListener('touchend', function (e) {
      if (tx === null) { return; }
      var dx = e.changedTouches[0].clientX - tx;
      tx = null;
      if (Math.abs(dx) > 60) { go(pos + (dx < 0 ? 1 : -1)); }
    });

    if (cfg.slider.pauza) {
      var prog = function (st) { var p = hero.querySelector('.marmal-hero__prog i'); if (p) { p.style.animationPlayState = st; } };
      hero.addEventListener('mouseenter', function () { state.hover = true; prog('paused'); });
      hero.addEventListener('mouseleave', function () { state.hover = false; prog(''); });
      hero.addEventListener('focusin', function () { state.hover = true; });
      hero.addEventListener('focusout', function () { state.hover = false; });
    }

    if (!reduce && !hero.classList.contains('marmal-hero--builder')) {
      setInterval(function () {
        if (!state.paused && !state.hover && !document.hidden) { go(pos + 1); }
      }, Math.max(3, +cfg.slider.int || 6) * 1000);
    }
    return { go: go };
  }

  function initRotator(r, state) {
    var words;
    try { words = JSON.parse(r.getAttribute('data-words') || '[]'); } catch (e) { return; }
    if (!words || words.length < 2) { return; }
    var w = r.querySelector('.marmal-hero-rot__w');
    var mode = r.classList.contains('marmal-hero-rot--psaci') ? 'psaci' : r.classList.contains('marmal-hero-rot--prolnuti') ? 'prolnuti' : 'vyjeti';
    var idx = 0;
    setInterval(function () {
      if (state.paused || document.hidden) { return; }
      idx = (idx + 1) % words.length;
      var next = words[idx];
      if (mode === 'psaci') {
        var cur = w.textContent;
        (function del() {
          if (cur.length) {
            cur = cur.slice(0, -1);
            w.textContent = cur;
            setTimeout(del, 45);
          } else {
            var k = 0;
            setTimeout(function typ() {
              k++;
              w.textContent = next.slice(0, k);
              if (k < next.length) { setTimeout(typ, 80); }
            }, 180);
          }
        })();
      } else if (mode === 'vyjeti') {
        w.classList.add('out');
        setTimeout(function () {
          w.textContent = next;
          w.classList.remove('out');
          w.classList.add('pre');
          void w.offsetWidth;
          w.classList.remove('pre');
        }, 360);
      } else {
        w.classList.add('out');
        setTimeout(function () { w.textContent = next; w.classList.remove('out'); }, 320);
      }
    }, 2800);
  }

  function fill(tpl, a, b) {
    return String(tpl).replace('%1$s', a).replace('%2$s', b).replace('%s', a);
  }

  function renderOpen(el, cfg) {
    var d;
    try { d = JSON.parse(el.getAttribute('data-open') || '{}'); } catch (e) { return; }
    var t = cfg.otevreno;
    var tm = function (s) { var m = /(\d{1,2})(?::(\d{2}))?/.exec(s || ''); return m ? (+m[1]) * 60 + (+(m[2] || 0)) : 0; };
    var od = tm(d.od), doo = tm(d['do']), dny = d.dny || [];
    var n = now(cfg.tz), text, isOpen = false;
    if (dny.indexOf(n.day) > -1 && n.min >= od && n.min < doo) {
      isOpen = true;
      text = fill(t[0], d['do']);
    } else if (dny.indexOf(n.day) > -1 && n.min < od) {
      text = fill(t[1], d.od);
    } else {
      text = '';
      for (var k = 1; k <= 7; k++) {
        var day = (n.day + k) % 7;
        if (dny.indexOf(day) > -1) {
          text = k === 1 ? fill(t[2], d.od) : fill(t[3], (t[4] || [])[day] || '', d.od);
          break;
        }
      }
    }
    var label = el.querySelector('.marmal-hero__open-t');
    if (label && text) { label.textContent = text; }
    var dot = el.querySelector('i');
    if (dot) { dot.className = isOpen ? 'marmal-hero-live' : 'marmal-hero-off'; }
  }

  function start() {
    each(document.querySelectorAll('.marmal-hero[data-mh]'), init);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
  window.MarmalHero = { init: start };
})();
