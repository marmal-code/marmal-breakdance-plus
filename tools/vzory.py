#!/usr/bin/env python3
"""
Vzory mozaiky pro Galerii Plus – JEDINÝ ZDROJ.

Po úpravě seznamu P spusť:  python3 tools/vzory.py
Skript ověří, že každý vzor beze zbytku vyplní obdélník (i při opakování),
a přepíše mapu vzorů v elements/Galerie_Plus/css.twig a nabídku
v elements/Galerie_Plus/element.php (mezi značkami VZORY-START / VZORY-END).
Klíče existujících vzorů neměnit – jsou uložené ve stránkách.
"""
import os, re, sys

# Katalog vzorů: (klíč, popis, šablona sloupců (fr), počet řádků, dlaždice [šířka, výška])
P = [
 # 2 sloupce
 ('2-velka-3',      '2 sloupce – velká vlevo + 3 pod sebou',      [2,1], 3, [[1,3],[1,1],[1,1],[1,1]]),
 ('2-3-velka',      '2 sloupce – 3 pod sebou + velká vpravo',     [1,2], 3, [[1,1],[1,3],[1,1],[1,1]]),
 ('2-velka-2',      '2 sloupce – velká vlevo + 2 pod sebou',      [3,2], 2, [[1,2],[1,1],[1,1]]),
 ('2-2-velka',      '2 sloupce – 2 pod sebou + velká vpravo',     [2,3], 2, [[1,1],[1,2],[1,1]]),
 ('2-siroka-2',     '2 sloupce – široká nahoře + 2 dole',         [1,1], 2, [[2,1],[1,1],[1,1]]),
 ('2-2-siroka',     '2 sloupce – 2 nahoře + široká dole',         [1,1], 2, [[1,1],[1,1],[2,1]]),
 # 3 sloupce
 ('velky-2',        '3 sloupce – velká vlevo + 2 pod sebou',      [1,1,1], 2, [[2,2],[1,1],[1,1]]),
 ('3-2-velka',      '3 sloupce – 2 pod sebou + velká vpravo',     [1,1,1], 2, [[1,1],[2,2],[1,1]]),
 ('vysoky-vlevo',   '3 sloupce – vysoká vlevo + 4',               [1,1,1], 2, [[1,2],[1,1],[1,1],[1,1],[1,1]]),
 ('3-vysoka-2-vysoka','3 sloupce – vysoká + 2 + vysoká',          [1,1,1], 2, [[1,2],[1,1],[1,2],[1,1]]),
 ('3-velka-3',      '3 sloupce – velká vlevo + 3 pod sebou',      [1,1,1], 3, [[2,3],[1,1],[1,1],[1,1]]),
 ('3-siroka-3',     '3 sloupce – široká nahoře + 3 dole',         [1,1,1], 2, [[3,1],[1,1],[1,1],[1,1]]),
 ('3-mozaika',      '3 sloupce – mozaika 3 × 3 (7 fotek)',        [1,1,1], 3, [[1,2],[1,1],[1,1],[1,1],[1,2],[1,1],[1,1]]),
 # 4 sloupce
 ('vysoky-4-vysoky','4 sloupce – vysoká + 4 + vysoká',            [1,1,1,1], 2, [[1,2],[1,1],[1,1],[1,2],[1,1],[1,1]]),
 ('velky-vlevo',    '4 sloupce – velká vlevo + 4',                [1,1,1,1], 2, [[2,2],[1,1],[1,1],[1,1],[1,1]]),
 ('velky-vpravo',   '4 sloupce – 4 + velká vpravo',               [1,1,1,1], 2, [[1,1],[1,1],[2,2],[1,1],[1,1]]),
 ('4-velka-stred',  '4 sloupce – velká uprostřed',                [1,1,1,1], 2, [[1,1],[2,2],[1,1],[1,1],[1,1]]),
 ('4-velka-2-vysoke','4 sloupce – velká + 2 vysoké',              [1,1,1,1], 2, [[2,2],[1,2],[1,2]]),
 ('4-vysoka-6',     '4 sloupce – vysoká vlevo + 6',               [1,1,1,1], 2, [[1,2],[1,1],[1,1],[1,1],[1,1],[1,1],[1,1]]),
 ('4-siroke-6',     '4 sloupce – široké v rozích + 4',            [1,1,1,1], 2, [[2,1],[1,1],[1,1],[1,1],[1,1],[2,1]]),
]



def place(cols, rows, tiles):
    """Simulace CSS grid auto-placement (grid-auto-flow: row, sparse)."""
    occ = set()
    cr, cc = 0, 0
    for w, h in tiles:
        while True:
            if cc + w > cols:
                cr, cc = cr + 1, 0
                continue
            if all((cr + y, cc + x) not in occ for y in range(h) for x in range(w)):
                break
            cc += 1
        occ |= {(cr + y, cc + x) for y in range(h) for x in range(w)}
        cc += w
    return occ


def main():
    root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    ok = True
    keys = set()
    for key, name, tpl, rows, tiles in P:
        cols = len(tpl)
        good = all(len(place(cols, rows * n, tiles * n)) == rows * cols * n
                   and max(r for r, c in place(cols, rows * n, tiles * n)) == rows * n - 1 for n in (1, 2))
        if key in keys:
            good = False
        keys.add(key)
        print(('OK    ' if good else 'CHYBA ') + key + '  ' + name)
        ok &= good
    if not ok:
        sys.exit('Některý vzor nesedí – soubory nebyly změněny.')

    twig = "{% set vzory = {\n" + ",\n".join(
        "  '%s': { 'tpl': '%s', 'rows': %d, 'tiles': [%s] }" % (
            k, ' '.join('minmax(0, %sfr)' % f for f in tpl), r, ','.join('[%d,%d]' % tuple(t) for t in tl))
        for k, n, tpl, r, tl in P) + "\n} %}"
    php = "\n".join("            ['value' => '%s', 'text' => '%s (%d)']," % (k, n, len(tl)) for k, n, tpl, r, tl in P)
    php += "\n            ['value' => 'mrizka', 'text' => 'Rovnoměrná mřížka'],"

    def put(path, start, end, body):
        p = os.path.join(root, path)
        s = open(p, encoding='utf-8').read()
        a, b = s.index(start) + len(start), s.index(end)
        s = s[:a] + '\n' + body + '\n' + s[b:]
        open(p, 'w', encoding='utf-8').write(s)

    put('elements/Galerie_Plus/css.twig', '{# VZORY-START #}', '{# VZORY-END #}', twig)
    put('elements/Galerie_Plus/element.php', '// VZORY-START', '            // VZORY-END', php)
    print('Hotovo: css.twig a element.php aktualizovány.')


if __name__ == '__main__':
    main()
