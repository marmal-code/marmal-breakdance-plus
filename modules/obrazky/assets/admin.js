/* MarMal → Obrázky: inventura, hromadný převod po krocích, pozastavení, vrácení, úklid. */
(function () {
  'use strict';
  var D = window.MARMAL_OBRAZKY;
  if (!D) {
    return;
  }
  var T = D.texty;
  var bezi = false;
  var stop = false;

  function el(id) {
    return document.getElementById(id);
  }

  function pozadavek(ukon, data) {
    var fd = new FormData();
    fd.append('action', 'marmal_obrazky');
    fd.append('nonce', D.nonce);
    fd.append('ukon', ukon);
    Object.keys(data || {}).forEach(function (k) {
      fd.append(k, data[k]);
    });
    return fetch(D.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) {
      return r.json().catch(function () {
        throw new Error('http ' + r.status);
      });
    });
  }

  function zprava(text) {
    var z = el('marmal-ob-zprava');
    if (z) {
      z.textContent = text || '';
    }
  }

  function vykreslit(s) {
    var box = el('marmal-ob-prubeh');
    if (!box || !s || !s.faze) {
      return;
    }
    box.hidden = false;
    el('marmal-ob-faze').textContent = s.text || '';
    var hotovo = s.celkem ? (s.celkem - s.zbyva) : 0;
    var podil = s.celkem ? hotovo / s.celkem : 0;
    if (s.faze !== 'prevod' && s.faze !== 'vraceni') {
      podil = s.prispevky_celkem ? 0.6 + 0.4 * (s.prispevky / s.prispevky_celkem) : 0.9;
      if (s.faze === 'hotovo') {
        podil = 1;
      }
    } else {
      podil = podil * 0.6;
    }
    el('marmal-ob-lista').style.width = Math.round(podil * 100) + '%';
    var radky = [];
    if (s.operace === 'prevod') {
      radky.push('Převedeno ' + s.hotove + ' / ' + s.celkem + ', přeskočeno ' + s.preskocene + ', zbývá ' + s.zbyva);
      radky.push('Objem ' + s.pred_text + ' → ' + s.po_text);
    } else {
      radky.push('Vráceno ' + s.hotove + ' / ' + s.celkem);
    }
    if (s.prispevky_celkem) {
      radky.push('Stránky a šablony ' + s.prispevky + ' / ' + s.prispevky_celkem);
    }
    el('marmal-ob-cisla').textContent = radky.join(' · ');
    var ul = el('marmal-ob-chyby');
    ul.textContent = '';
    (s.chyby || []).forEach(function (c) {
      var li = document.createElement('li');
      li.textContent = c;
      ul.appendChild(li);
    });
  }

  function smycka() {
    if (stop) {
      bezi = false;
      zprava(T.pozastaveno);
      return;
    }
    bezi = true;
    pozadavek('krok').then(function (r) {
      if (!r.success) {
        bezi = false;
        vykreslit(r.data && r.data.stav);
        zprava(T.chyba + ' ' + ((r.data && r.data.zprava) || ''));
        return;
      }
      vykreslit(r.data);
      if (r.data.faze === 'hotovo' || !r.data.faze) {
        bezi = false;
        zprava(T.hotovo);
        window.location.reload();
        return;
      }
      window.setTimeout(smycka, 300);
    }).catch(function () {
      bezi = false;
      zprava(T.spojeni);
    });
  }

  function jednoduchy(ukon, data, tlacitko) {
    if (tlacitko) {
      tlacitko.disabled = true;
    }
    zprava(T.pracuji);
    return pozadavek(ukon, data).then(function (r) {
      if (!r.success) {
        if (tlacitko) {
          tlacitko.disabled = false;
        }
        var t = T.chyba + ' ' + ((r.data && r.data.zprava) || '');
        zprava(t);
        window.alert(t);
        return null;
      }
      return r.data;
    }).catch(function () {
      if (tlacitko) {
        tlacitko.disabled = false;
      }
      zprava(T.spojeni);
      return null;
    });
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-marmal-ob]');
    if (!b) {
      return;
    }
    var ukon = b.getAttribute('data-marmal-ob');
    var data = {};
    if (b.hasAttribute('data-davka')) {
      data.davka = b.getAttribute('data-davka');
    }
    if (b.hasAttribute('data-priloha')) {
      data.priloha = b.getAttribute('data-priloha');
    }

    if (ukon === 'inventura') {
      b.textContent = T.pracuji;
      jednoduchy('inventura', data, b).then(function (r) {
        if (r) {
          window.location.reload();
        }
      });
    } else if (ukon === 'start') {
      if (!window.confirm(T.potvrditStart)) {
        return;
      }
      jednoduchy('start', data, b).then(function (r) {
        if (r) {
          vykreslit(r);
          stop = false;
          smycka();
        }
      });
    } else if (ukon === 'pokracovat') {
      if (!bezi) {
        stop = false;
        zprava(T.pracuji);
        smycka();
      }
    } else if (ukon === 'pauza') {
      stop = true;
      pozadavek('pauza');
      zprava(T.pozastaveno);
    } else if (ukon === 'vratit') {
      if (!window.confirm(T.potvrditVratit)) {
        return;
      }
      jednoduchy('vratit', data, b).then(function (r) {
        if (r) {
          vykreslit(r);
          stop = false;
          smycka();
        }
      });
    } else if (ukon === 'uklid' || ukon === 'zaloha') {
      if (!window.confirm(ukon === 'uklid' ? T.potvrditUklid : T.potvrditZaloha)) {
        return;
      }
      jednoduchy(ukon, data, b).then(function (r) {
        if (r) {
          window.location.reload();
        }
      });
    }
  });

  if (D.stav && D.stav.faze && D.stav.faze !== 'hotovo') {
    vykreslit(D.stav);
    if (D.stav.pozastaveno) {
      zprava(T.pozastaveno);
    }
  }
})();
