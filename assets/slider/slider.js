/*!
 * Marmal Slider – slider s miniaturami pro Galerii Plus (Marmal – Breakdance Plus).
 * Bez závislostí. Posun řeší prohlížeč (CSS scroll-snap, swipe na dotyku),
 * tento skript přidává šipky, miniatury, klávesnici, počítadlo a autoplay.
 */
(function () {
  'use strict';

  if (window.MarmalSlider) {
    return;
  }

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function init(root) {
    if (root.__marmalSlider) {
      return;
    }
    root.__marmalSlider = true;

    var track = root.querySelector('.marmal-slider__track');
    if (!track) {
      return;
    }
    var slides = Array.prototype.slice.call(track.children);
    var thumbsWrap = root.querySelector('.marmal-slider__thumbs');
    var thumbs = thumbsWrap ? Array.prototype.slice.call(thumbsWrap.querySelectorAll('.marmal-slider__thumb')) : [];
    var prev = root.querySelector('.marmal-slider__prev');
    var next = root.querySelector('.marmal-slider__next');
    var current = root.querySelector('.marmal-slider__current');
    var n = slides.length;
    var index = 0;
    var timer = null;
    var paused = false;
    var target = null;      // cíl programového posunu (šipky, miniatury)
    var targetTimer = 0;

    if (n < 2) {
      return;
    }

    function setActive(i, smoothThumbs) {
      index = i;
      thumbs.forEach(function (t, j) {
        var on = j === i;
        t.classList.toggle('is-active', on);
        if (on) {
          t.setAttribute('aria-current', 'true');
        } else {
          t.removeAttribute('aria-current');
        }
      });
      slides.forEach(function (s, j) {
        s.toggleAttribute('inert', j !== i);
      });
      if (current) {
        current.textContent = String(i + 1);
      }
      // Aktivní miniaturu udržet ve viditelné části pásu
      var t = thumbs[i];
      if (t && thumbsWrap.scrollWidth > thumbsWrap.clientWidth) {
        var left = t.offsetLeft - (thumbsWrap.clientWidth - t.offsetWidth) / 2;
        thumbsWrap.scrollTo({ left: Math.max(0, left), behavior: smoothThumbs && !reduceMotion ? 'smooth' : 'auto' });
      }
    }

    function go(i, smooth) {
      i = ((i % n) + n) % n;
      // Během plynulého posunu nepřepočítávat snímek z pozice (rychlé klikání)
      target = i;
      clearTimeout(targetTimer);
      targetTimer = setTimeout(function () { target = null; }, 1200);
      track.scrollTo({ left: slides[i].offsetLeft, behavior: smooth !== false && !reduceMotion ? 'smooth' : 'auto' });
      setActive(i, smooth !== false);
    }

    // Aktuální snímek podle posunu (swipe, trackpad)
    var raf = 0;
    track.addEventListener('scroll', function () {
      cancelAnimationFrame(raf);
      raf = requestAnimationFrame(function () {
        var i = Math.round(track.scrollLeft / Math.max(1, track.clientWidth));
        i = Math.min(n - 1, Math.max(0, i));
        if (target !== null) {
          if (i === target) {
            target = null;
          }
          return;
        }
        if (i !== index) {
          setActive(i, true);
        }
      });
    }, { passive: true });

    if (prev) {
      prev.addEventListener('click', function () { go(index - 1); restart(); });
    }
    if (next) {
      next.addEventListener('click', function () { go(index + 1); restart(); });
    }
    thumbs.forEach(function (t) {
      t.addEventListener('click', function () {
        go(parseInt(t.getAttribute('data-index'), 10) || 0);
        restart();
      });
    });

    // Klávesnice: šipky, když je fokus ve slideru
    root.addEventListener('keydown', function (e) {
      if (document.documentElement.classList.contains('marmal-lb-open')) {
        return;
      }
      if (e.key === 'ArrowRight') {
        e.preventDefault();
        go(index + 1);
        restart();
      } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        go(index - 1);
        restart();
      }
    });

    // Lightbox posouvá i slider
    root.addEventListener('marmal:lightbox-change', function (e) {
      if (e.detail && typeof e.detail.index === 'number') {
        go(e.detail.index, false);
      }
    });

    // Po změně velikosti okna srovnat na aktuální snímek
    var resizeTimer = 0;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () { go(index, false); }, 120);
    });

    // Autoplay (vypne se při „omezit pohyb“, pozastaví při najetí, fokusu a skryté záložce)
    var delay = parseFloat(root.getAttribute('data-autoplay') || '0') * 1000;

    function stop() {
      clearInterval(timer);
      timer = null;
    }
    function start() {
      stop();
      if (delay >= 1000 && !reduceMotion && !paused && !document.hidden) {
        timer = setInterval(function () { go(index + 1); }, delay);
      }
    }
    function restart() {
      if (timer) {
        start();
      }
    }

    if (delay >= 1000 && !reduceMotion) {
      root.addEventListener('mouseenter', function () { paused = true; stop(); });
      root.addEventListener('mouseleave', function () { paused = false; start(); });
      root.addEventListener('focusin', function () { paused = true; stop(); });
      root.addEventListener('focusout', function (e) {
        if (!root.contains(e.relatedTarget)) {
          paused = false;
          start();
        }
      });
      document.addEventListener('visibilitychange', function () {
        if (document.hidden) { stop(); } else { start(); }
      });
      start();
    }

    setActive(0, false);
  }

  function initAll(scope) {
    Array.prototype.forEach.call((scope || document).querySelectorAll('[data-marmal-slider]'), init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { initAll(); });
  } else {
    initAll();
  }

  window.MarmalSlider = { init: initAll };
})();
