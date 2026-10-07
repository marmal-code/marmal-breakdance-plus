# Marmal – Breakdance Plus

Vlastní a vylepšené elementy pro [Breakdance](https://breakdance.com). Autor: Martin Malý – [marmal.cz](https://marmal.cz)

## Elementy

| Element | Stav | Popis |
|---|---|---|
| Galerie Plus | připravuje se | Mozaika se zarovnanými okraji, slider s miniaturami, lightbox |

## Požadavky

- WordPress 6.0+, PHP 7.4+
- Breakdance 2.8+ (testováno na 2.8.x)

## Instalace

1. Stáhni `marmal-breakdance-plus.zip` z posledního [Release](https://github.com/marmal-code/marmal-breakdance-plus/releases/latest).
2. WordPress → Pluginy → Přidat nový → Nahrát plugin → aktivovat.
3. Další verze se nabídnou automaticky v Pluginy → Aktualizace.

## Vydání nové verze

1. Zvýšit `Version:` v `marmal-breakdance-plus.php` (a konstantu `MARMAL_BDP_VERSION`).
2. Commit a push (GitHub Desktop).
3. GitHub → Releases → Draft a new release → tag `vX.Y.Z` (stejné číslo jako Version) → Publish.
4. GitHub Action sám vytvoří a přiloží ZIP.

## Konvence

- PHP namespace: `MarmalElements` (neměnit)
- CSS třídy: `marmal-…` + BEM (např. `.marmal-gallery__thumbs`)
- Verzování: MAJOR.MINOR.PATCH

## Licence

GPLv2 or later. Obsahuje [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (MIT).
