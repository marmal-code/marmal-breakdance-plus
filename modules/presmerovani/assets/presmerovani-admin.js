/* Marmal – Breakdance Plus, modul Přesměrování: administrace (bez závislostí). */
(function () {
    'use strict';
    var t = window.marmalPresm || {};

    // Potvrzení mazání.
    document.querySelectorAll('.mp-potvrdit').forEach(function (a) {
        a.addEventListener('click', function (e) {
            if (!window.confirm(t.smazat || 'OK?')) {
                e.preventDefault();
            }
        });
    });

    // Zaškrtnout vše (jen viditelné a povolené řádky).
    document.querySelectorAll('.mp-vse').forEach(function (vse) {
        vse.addEventListener('change', function () {
            var tabulka = vse.closest('table');
            tabulka.querySelectorAll('tbody tr').forEach(function (tr) {
                var c = tr.querySelector('input[type=checkbox]');
                if (c && !c.disabled && !tr.hidden) {
                    c.checked = vse.checked;
                }
            });
            pocet();
        });
    });

    // Hromadné akce: nic nevybráno / potvrzení smazání.
    var hrom = document.getElementById('mp-hromadne');
    if (hrom) {
        hrom.addEventListener('submit', function (e) {
            if (!hrom.querySelector('input[name="ids[]"]:checked')) {
                e.preventDefault();
                window.alert(t.nic || '');
                return;
            }
            if (hrom.querySelector('select[name=co]').value === 'smazat' && !window.confirm(t.smazat || 'OK?')) {
                e.preventDefault();
            }
        });
    }

    // Formulář: vybraná stránka má přednost před ručně psanou adresou; 410 cíl nepotřebuje.
    var vyber = document.getElementById('mp-cil-post');
    var cil = document.getElementById('mp-cil');
    var kod = document.getElementById('mp-kod');
    function stavFormulare() {
        if (!cil) {
            return;
        }
        var zruseno = kod && kod.value === '410';
        cil.disabled = zruseno || (vyber && vyber.value !== '0');
        if (vyber) {
            vyber.disabled = zruseno;
        }
    }
    if (vyber) {
        vyber.addEventListener('change', stavFormulare);
    }
    if (kod) {
        kod.addEventListener('change', stavFormulare);
    }
    stavFormulare();

    // Převod ze starého webu.
    var stary = document.getElementById('mp-stary');
    if (!stary) {
        return;
    }
    var radky = Array.prototype.slice.call(stary.querySelectorAll('.mp-stary-radek'));
    var pocetEl = document.getElementById('mp-stary-pocet');

    function pocet() {
        if (!pocetEl) {
            return;
        }
        var n = radky.filter(function (tr) {
            var c = tr.querySelector('.mp-ano');
            return c && c.checked;
        }).length;
        pocetEl.textContent = (t.vybrano || '%d').replace('%d', n);
    }

    radky.forEach(function (tr) {
        var c = tr.querySelector('.mp-ano');
        var vstup = tr.querySelector('.mp-cil');
        var sel = tr.querySelector('.mp-kod');
        if (c) {
            c.addEventListener('change', pocet);
        }
        if (vstup && c) {
            vstup.addEventListener('input', function () {
                c.checked = vstup.value.trim() !== '' || (sel && sel.value === '410');
                pocet();
            });
        }
        if (sel && c) {
            sel.addEventListener('change', function () {
                if (vstup) {
                    vstup.disabled = sel.value === '410';
                }
                if (sel.value === '410') {
                    c.checked = true;
                }
                pocet();
            });
        }
    });

    document.querySelectorAll('.mp-filtr').forEach(function (b) {
        b.addEventListener('click', function () {
            var stav = b.getAttribute('data-stav');
            document.querySelectorAll('.mp-filtr').forEach(function (x) {
                x.classList.toggle('is-aktivni', x === b);
            });
            radky.forEach(function (tr) {
                tr.hidden = stav !== '' && tr.getAttribute('data-stav') !== stav;
            });
        });
    });

    stary.addEventListener('submit', function (e) {
        var data = [];
        radky.forEach(function (tr) {
            var c = tr.querySelector('.mp-ano');
            if (!c || !c.checked || c.disabled) {
                return;
            }
            var sel = tr.querySelector('.mp-kod');
            var vstup = tr.querySelector('.mp-cil');
            data.push({
                stara: tr.querySelector('.mp-stara').textContent,
                cil: vstup ? vstup.value.trim() : '',
                kod: sel ? parseInt(sel.value, 10) : 301
            });
        });
        if (!data.length) {
            e.preventDefault();
            window.alert(t.nic || '');
            return;
        }
        document.getElementById('mp-stary-json').value = JSON.stringify(data);
    });
    pocet();
}());
