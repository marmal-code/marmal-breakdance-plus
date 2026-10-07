# Marmal – Breakdance Plus

Vlastní a vylepšené elementy pro [Breakdance](https://breakdance.com). Autor: Martin Malý – [marmal.cz](https://marmal.cz)

## Elementy

| Element | Stav | Popis |
|---|---|---|
| Galerie Plus | hotovo | Mozaika (20 vzorů), slider s miniaturami, efekty po najetí, video, ACF galerie, vlastní lightbox |

## Požadavky

- WordPress 6.0+, PHP 7.4+
- Breakdance 2.8+ (testováno na 2.8.x)

## Instalace

1. Stáhni `marmal-breakdance-plus.zip` z posledního [Release](https://github.com/marmal-code/marmal-breakdance-plus/releases/latest).
2. WordPress → Pluginy → Přidat nový → Nahrát plugin → aktivovat.
3. Další verze se nabídnou automaticky v Pluginy → Aktualizace.

## Vydání nové verze

1. Zvýšit `Version:` v `marmal-breakdance-plus.php` (a konstantu `MARMAL_BDP_VERSION`).
2. Commit a push do `main` (GitHub Desktop).
3. Hotovo – GitHub Action sama vytvoří tag `vX.Y.Z`, Release a přiloží ZIP. Commity bez zvýšení verze nic nevydají.

## Překlady

Zdrojový jazyk je čeština, angličtina je v `languages/`. Po přidání nebo změně textů v PHP spusť `python3 tools/i18n.py`, doplň prázdné překlady v `.po` souborech a spusť skript znovu (zkompiluje `.mo`).

## Konvence

- PHP namespace: `MarmalElements` (neměnit)
- CSS třídy: `marmal-…` + BEM (např. `.marmal-gallery__thumbs`)
- Verzování: MAJOR.MINOR.PATCH

## Licence

GPLv2 or later. Obsahuje [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (MIT).
