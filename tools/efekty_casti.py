#!/usr/bin/env python3
"""
Rozdělí CSS efektů na části podle kategorií.

Zdroj (jediný, který se upravuje ručně):
    modules/efekty/assets/css/mm-effects.css

Výstup (generovaný, neupravovat ručně):
    modules/efekty/assets/css/casti/<id>.css

Plugin pak na webu načte jen kategorie zapnuté v MarMal → Efekty – nastavení.
Když jsou zapnuté všechny, načte se rovnou celý mm-effects.css.

Spuštění po každé úpravě mm-effects.css:
    python3 tools/efekty_casti.py
"""

import pathlib
import re
import sys

KOREN = pathlib.Path(__file__).resolve().parent.parent
ZDROJ = KOREN / "modules/efekty/assets/css/mm-effects.css"
CIL = KOREN / "modules/efekty/assets/css/casti"

# číslo sekce v mm-effects.css → soubor části
# (pořadí odpovídá pořadí v PHP funkci mm_effects_kategorie())
SEKCE = {
    0: "00-zaklad",
    1: "scroll",
    2: "ramecky",
    3: "tlacitka",
    4: "karty",
    5: "obrazky",
    6: "hero",
    7: "text",
    8: "pozadi",
    9: "animbg",
    10: "ikony",
    11: "99-omezeny-pohyb",
}

HLAVICKA = "/* Generováno nástrojem tools/efekty_casti.py z mm-effects.css – neupravovat ručně. */\n"


def main() -> int:
    css = ZDROJ.read_text(encoding="utf-8")
    nadpisy = list(re.finditer(r"^/\* =+ (\d+)\. ", css, flags=re.M))
    nalezene = [int(m.group(1)) for m in nadpisy]
    if sorted(nalezene) != sorted(SEKCE):
        print(f"Chyba: v CSS jsou sekce {nalezene}, nástroj čeká {sorted(SEKCE)}.", file=sys.stderr)
        print("Přidal jsi novou sekci? Doplň ji do SEKCE v tomto souboru i do mm_effects_kategorie().", file=sys.stderr)
        return 1

    CIL.mkdir(parents=True, exist_ok=True)
    for stary in CIL.glob("*.css"):
        stary.unlink()

    for i, m in enumerate(nadpisy):
        cislo = int(m.group(1))
        # sekce 0 dostane i úvodní komentář souboru
        zacatek = 0 if cislo == 0 else m.start()
        konec = nadpisy[i + 1].start() if i + 1 < len(nadpisy) else len(css)
        obsah = css[zacatek:konec].rstrip() + "\n"
        (CIL / f"{SEKCE[cislo]}.css").write_text(HLAVICKA + obsah, encoding="utf-8")
        print(f"{SEKCE[cislo]}.css  ({len(obsah)} B)")

    return 0


if __name__ == "__main__":
    sys.exit(main())
