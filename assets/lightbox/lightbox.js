/*!
 * Marmal Lightbox – lehký lightbox pro Galerii Plus (Marmal – Breakdance Plus).
 * Bez závislostí. Obrázky + video (YouTube nocookie, Vimeo dnt, MP4).
 * Klávesnice: ← → Esc, swipe na dotykových zařízeních, focus trap.
 */
(function () {
  'use strict';

  if (window.MarmalLightbox) {
    return;
  }

  var T = {
    close: 'Zavřít',
    prev: 'Předchozí',
    next: 'Další',
    dialog: 'Galerie'
  };

  var root, stage, captionEl, counterEl, prevBtn, nextBtn, closeBtn;
  var items = [];
  var index = 0;
  var currentGallery = null;
  var lastFocus = null;
  var touchStartX = null;
  var touchStartY = null;

  function el(tag, cls, attrs) {
    var node = document.createElement(tag);
    if (cls) {
      node.className = cls;
    }
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        node.setAttribute(k, attrs[k]);
      });
    }
    return node;
  }

  function icon(path) {
    return '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="' + path + '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  }

  function build() {
    if (root) {
      return;
    }
    root = el('div', 'marmal-lb', { role: 'dialog', 'aria-modal': 'true', 'aria-label': T.dialog, hidden: '' });

    closeBtn = el('button', 'marmal-lb__btn marmal-lb__close', { type: 'button', 'aria-label': T.close });
    closeBtn.innerHTML = icon('M6 6l12 12M18 6L6 18');

    prevBtn = el('button', 'marmal-lb__btn marmal-lb__prev', { type: 'button', 'aria-label': T.prev });
    prevBtn.innerHTML = icon('M15 5l-7 7 7 7');

    nextBtn = el('button', 'marmal-lb__btn marmal-lb__next', { type: 'button', 'aria-label': T.next });
    nextBtn.innerHTML = icon('M9 5l7 7-7 7');

    stage = el('div', 'marmal-lb__stage');
    var footer = el('div', 'marmal-lb__footer');
    captionEl = el('div', 'marmal-lb__caption');
    counterEl = el('div', 'marmal-lb__counter', { 'aria-live': 'polite' });
    footer.appendChild(captionEl);
    footer.appendChild(counterEl);

    root.appendChild(stage);
    root.appendChild(prevBtn);
    root.appendChild(nextBtn);
    root.appendChild(closeBtn);
    root.appendChild(footer);
    document.body.appendChild(root);

    closeBtn.addEventListener('click', close);
    prevBtn.addEventListener('click', function () { go(-1); });
    nextBtn.addEventListener('click', function () { go(1); });

    // Klik mimo obsah zavře lightbox
    root.addEventListener('click', function (e) {
      if (e.target === root || e.target === stage) {
        close();
      }
    });

    root.addEventListener('touchstart', function (e) {
      if (e.touches.length === 1) {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
      }
    }, { passive: true });

    root.addEventListener('touchend', function (e) {
      if (touchStartX === null) {
        return;
      }
      var dx = e.changedTouches[0].clientX - touchStartX;
      var dy = e.changedTouches[0].clientY - touchStartY;
      touchStartX = null;
      if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
        go(dx < 0 ? 1 : -1);
      }
    });
  }

  function collect(container) {
    var nodes = container.querySelectorAll(':scope > [data-lb-item]');
    var list = [];
    Array.prototype.forEach.call(nodes, function (node) {
      if (node.hasAttribute('data-video')) {
        list.push({
          node: node,
          type: 'video',
          videoType: node.getAttribute('data-video-type') || 'iframe',
          src: node.getAttribute('data-video'),
          caption: node.getAttribute('data-caption') || ''
        });
      } else {
        var img = node.querySelector('img');
        list.push({
          node: node,
          type: 'image',
          src: node.getAttribute('href'),
          alt: img ? img.getAttribute('alt') || '' : '',
          caption: node.getAttribute('data-caption') || ''
        });
      }
    });
    return list;
  }

  function render() {
    var item = items[index];
    stage.innerHTML = '';

    var media;
    if (item.type === 'image') {
      media = el('img', 'marmal-lb__media', { src: item.src, alt: item.alt, decoding: 'async' });
    } else if (item.videoType === 'file') {
      media = el('video', 'marmal-lb__media marmal-lb__video', { src: item.src, controls: '', autoplay: '', playsinline: '' });
    } else {
      var wrap = el('div', 'marmal-lb__frame');
      wrap.appendChild(el('iframe', '', {
        src: item.src,
        title: item.caption || 'Video',
        allow: 'autoplay; fullscreen; picture-in-picture; encrypted-media',
        allowfullscreen: ''
      }));
      media = wrap;
    }
    stage.appendChild(media);

    captionEl.textContent = item.caption;
    counterEl.textContent = items.length > 1 ? (index + 1) + ' / ' + items.length : '';

    var multi = items.length > 1;
    prevBtn.hidden = !multi;
    nextBtn.hidden = !multi;

    preload(index + 1);
    preload(index - 1);

    // Slider se srovná na fotku, kterou si návštěvník prohlíží v lightboxu
    var di = item.node.getAttribute('data-index');
    if (currentGallery && di !== null) {
      currentGallery.dispatchEvent(new CustomEvent('marmal:lightbox-change', { detail: { index: parseInt(di, 10) } }));
    }
  }

  function preload(i) {
    var item = items[(i + items.length) % items.length];
    if (item && item.type === 'image') {
      var im = new Image();
      im.src = item.src;
    }
  }

  function go(step) {
    if (items.length < 2) {
      return;
    }
    index = (index + step + items.length) % items.length;
    render();
  }

  function onKey(e) {
    if (e.key === 'Escape') {
      e.preventDefault();
      close();
    } else if (e.key === 'ArrowRight') {
      go(1);
    } else if (e.key === 'ArrowLeft') {
      go(-1);
    } else if (e.key === 'Tab') {
      // Focus trap
      var focusable = Array.prototype.filter.call(
        root.querySelectorAll('button, iframe, video'),
        function (n) { return !n.hidden; }
      );
      if (!focusable.length) {
        return;
      }
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  }

  function open(gallery, startNode) {
    build();
    currentGallery = gallery;
    items = collect(gallery.querySelector('[data-lb-items]') || gallery);
    if (!items.length) {
      return;
    }
    index = 0;
    items.forEach(function (it, i) {
      if (it.node === startNode) {
        index = i;
      }
    });

    // Barva pozadí z nastavení elementu (CSS proměnná --marmal-lb-bg)
    var bg = getComputedStyle(gallery).getPropertyValue('--marmal-lb-bg').trim();
    root.style.setProperty('--marmal-lb-bg', bg || '');

    lastFocus = document.activeElement;
    render();
    root.hidden = false;
    document.documentElement.classList.add('marmal-lb-open');
    document.addEventListener('keydown', onKey);
    closeBtn.focus();
  }

  function close() {
    if (!root || root.hidden) {
      return;
    }
    root.hidden = true;
    stage.innerHTML = ''; // zastaví video
    document.documentElement.classList.remove('marmal-lb-open');
    document.removeEventListener('keydown', onKey);
    if (lastFocus && lastFocus.focus) {
      lastFocus.focus();
    }
  }

  // Delegace kliků – funguje pro libovolný počet galerií i pro obsah načtený později
  document.addEventListener('click', function (e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
      return;
    }
    var tile = e.target.closest ? e.target.closest('.marmal-gallery[data-marmal-lb] [data-lb-item]') : null;
    if (!tile) {
      return; // video bez embedu nemá data-lb-item a otevře se normálně v novém okně
    }
    e.preventDefault();
    open(tile.closest('.marmal-gallery'), tile);
  });

  window.MarmalLightbox = { open: open, close: close };
})();
