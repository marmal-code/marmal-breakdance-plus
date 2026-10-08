# Marmal – Breakdance Plus

Moduly pro weby MarMal: vlastní [Breakdance](https://breakdance.com) elementy a knihovna CSS efektů.
Co web používá, se zapíná v **WP admin → MarMal → Moduly**. Autor: Martin Malý – [marmal.cz](https://marmal.cz)

## Moduly

| Modul | Zapínání | Popis |
|---|---|---|
| Galerie Plus | vždy zapnuto | Breakdance element: mozaika (20 vzorů), slider s miniaturami, efekty po najetí, video, ACF galerie, vlastní lightbox. CSS/JS jen na stránkách s galerií. |
| Efekty | podle webu | Knihovna CSS efektů (třídy `mm-…`, 89 efektů v 10 kategoriích), galerie s náhledy, barvy z Global Settings Breakdance. Nahrazuje plugin MarMal Effects. Na web se načítají jen zapnuté kategorie. |

Moduly s elementy z Element Studia jsou **zamčené** (vždy zapnuté) – vypnutím by elementy zmizely ze stránek.

## Přechod z pluginu MarMal Effects

1. Nainstaluj a aktivuj Breakdance Plus (0.7.0+).
2. **MarMal → Moduly** → u karty Efekty klikni na **Převést ze starého pluginu**.
   Starý plugin se deaktivuje, modul zapne. Nastavení barev (`mm_effects_settings`) i třídy na stránkách zůstanou.
3. Starý plugin MarMal Effects smaž v Pluginech.

Dokud starý plugin běží, modul se nenačte (jinak by se funkce deklarovaly dvakrát a web by spadl).

## Efekty – úpravy

- Zdroj CSS je jediný soubor `modules/efekty/assets/css/mm-effects.css` – upravuje se jen ten.
- Po každé úpravě spusť `python3 tools/efekty_casti.py` – rozdělí CSS na kategorie do `assets/css/casti/`.
- Nový efekt přidej i do `modules/efekty/data/effects.json` (galerie v adminu).
- Nová kategorie = nová sekce v CSS + doplnit `SEKCE` v `tools/efekty_casti.py` a `mm_effects_kategorie()` v `modules/efekty/modul.php`.
- Třídy `mm-…`, funkce `mm_effects_…` a option `mm_effects_settings` se **nepřejmenovávají** (jsou v obsahu stránek a nastavení webů).

## Nový modul

1. `modules/<id>/manifest.php` – vzor `modules/efekty/manifest.php` (id, soubor, výchozí stav, závislost na Breakdance, texty, odkazy).
2. `modules/<id>/modul.php` – kód modulu.
3. Zvýšit verzi. Dashboard i MarMal Agent ho uvidí samy.

## Pro MarMal Agenta

Veřejné funkce (vždy nejdřív `function_exists`):

- `marmal_bdp_verze()` – verze pluginu
- `marmal_bdp_moduly()` – seznam modulů: `id`, `nazev`, `popis`, `aktivni`, `zamceny`, `stav` (`zapnuto` / `vypnuto` / `konflikt` / `chybi_breakdance`), `konflikt`
- `marmal_bdp_nastavit_modul($id, $aktivni)` – `true` nebo `WP_Error`; projeví se od dalšího načtení stránky
- akce `marmal_bdp_modul_zmenen` ($id, $aktivni)

## Požadavky

- WordPress 6.0+, PHP 7.4+
- Breakdance 2.8+ (pro elementy; modul Efekty funguje i bez něj)

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
- CSS třídy: `marmal-…` + BEM (např. `.marmal-gallery__thumbs`); výjimka: efekty `mm-…` (historické)
- Verzování: MAJOR.MINOR.PATCH

## Licence

GPLv2 or later. Obsahuje [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (MIT).
