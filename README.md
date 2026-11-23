# Marmal – Breakdance Plus

Moduly pro weby MarMal: vlastní [Breakdance](https://breakdance.com) elementy a knihovna CSS efektů.
Co web používá, se zapíná v **WP admin → MarMal → Moduly**. Autor: Martin Malý – [marmal.cz](https://marmal.cz)

## Moduly

| Modul | Zapínání | Popis |
|---|---|---|
| Galerie Plus | vždy zapnuto | Breakdance element: mozaika (20 vzorů), slider s miniaturami, efekty po najetí, video, ACF galerie, vlastní lightbox. CSS/JS jen na stránkách s galerií. |
| superHERO | vždy zapnuto | Breakdance element pro úvodní sekci: 5 rozložení, pozadí (barva, přechod, živý přechod, fotka, video, slider), tlačítka s efekty, důvěra (hodnocení, zákazníci, loga), otevírací doba, výhody / čísla, masky obrázků, dividery, typografie každého prvku, dynamická data. JS jen na stránkách s elementem. |
| Efekty | podle webu | Knihovna CSS efektů (třídy `mm-…`, 89 efektů v 10 kategoriích), galerie s náhledy, barvy z Global Settings Breakdance. Nahrazuje plugin MarMal Effects. Na web se načítají jen zapnuté kategorie. |
| Patička | zapnuto | Copyright s rokem spuštění webu (© 2019–2026), podpis autora „Web vytvořil Martin Malý – marmal.cz“ (UTM, nofollow mimo úvodní stránku), údaje o podnikateli podle § 435 OZ, odkazy na ochranu osobních údajů a nastavení cookies. Nastavení v **MarMal → Patička**, výstup shortcody a dynamickými daty (MarMal → Patička). |
| Cookies | vypnuto | Vlastní cookie lišta podle pravidel ÚOOÚ místo CookieYes: souhlas předem, „Odmítnout vše“ v první vrstvě, okno nastavení po kategoriích, Google Consent Mode v2 + GA4, blokování skriptů a náhledy videí/map do souhlasu, autodetekce služeb, seznam cookies `[marmal_cookies_seznam]`, záznam souhlasů. Nastavení v **MarMal → Cookies**. |
| Ochrana osobních údajů | vypnuto | Stránka Zásady ochrany osobních údajů podle čl. 13 GDPR: údaje o správci (Patička je přebírá odsud), účely zaškrtávátky, zpracovatelé, práva, ÚOOÚ; chybějící údaje žlutě „[DOPLŇTE]“; koncept → zveřejnění na adrese dosavadní stránky zásad. Nastavení v **MarMal → Ochrana os. údajů**. |
| Fonty | vypnuto | Google Fonts z vlastní domény: odkazy na fonts.googleapis.com ve stránce nahradí lokálně staženými woff2 (uploads/marmal-fonty), odstraní preconnect na Google, font-display swap, přednačtení písma textu a nadpisů podle Global Settings (nebo ručně, max. 3 soubory), přehled, kde se které písmo používá. Fonty se dál vybírají v Breakdance. Nastavení v **MarMal → Fonty**. |
| Obrázky | vypnuto | JPG a PNG se při nahrání uloží jako WebP (volitelně AVIF), zmenšené na max. 1920 px, bez EXIF/GPS. Hromadný převod používaných obrázků do WebP s přepisem odkazů v datech Breakdance (mediální objekty včetně srcset, pozadí, text, vlastní CSS), v obsahu, Rank Math a globálních volbách; záloha souborů i dat stránek, pozastavení, vrácení dávky i jednoho obrázku, úklid starých souborů po 14 dnech; `sizes="auto"` u lazy obrázků (prohlížeč stáhne velikost podle místa na stránce), přehled obrázků na pozadí v plné velikosti. Nastavení v **MarMal → Obrázky**. |
| SEO a sdílení | vypnuto | Titulek, popisek, canonical, noindex, Open Graph a Twitter u každé stránky (box „SEO a sdílení“ s náhledem Googlu a Facebooku), výchozí hodnoty webu, automatické texty z Developerských projektů (filtr `marmal_seo_data`), obrázek 1200 × 630 pro sdílení, drobečková navigace – element **Marmal – Drobečková navigace** s kompletním nastavením vzhledu nebo `[marmal_drobecky]` + BreadcrumbList, úklid sitemap, vlastní řádky robots.txt, převod z Yoastu / Rank Mathu, vícejazyčné weby (Polylang, WPML – vlastní název webu, vzor, popisek, obrázek a „Úvod“ pro každý jazyk, og:locale:alternate), GEO – data o firmě (Organization / LocalBusiness z údajů GDPR / Patičky, otevírací doba, profily), pravidla pro AI roboty v robots.txt, IndexNow (Seznam, Bing) a volitelný llms.txt. Když běží jiný SEO plugin, nic nevypisuje. Nastavení v **MarMal → SEO**. |
| Přesměrování | vypnuto | Přesměrování 301 / 302 / 307 a 410 (zrušeno) – jednotlivé adresy i celé sekce (`/blog/*`), cíl jako adresa, jiný web nebo stránka webu (vede vždy na její aktuální adresu), automaticky při změně adresy publikované stránky (i podstránek), hlídání chyb 404 (bez IP adres) s tlačítkem „Přesměrovat“, převod ze starého webu (adresy ze sitemapy nebo seznamu, návrh nové stránky podle názvu), import / export CSV. Nastavení v **MarMal → Přesměrování**. |

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

## superHERO – úpravy

- Ovládací prvky se **negenerují v Element Studiu**, ale nástrojem `tools/hero_prvky.py` (jediný zdroj prvků i výchozích hodnot). Po změně spusť `python3 tools/hero_prvky.py` – přepíše `elements/Super_Hero/element.php` a `includes/hero-vychozi.php`.
- Vykreslení: `includes/hero.php`, styly: `elements/Super_Hero/default.css`, typografie: `css.twig`, chování: `assets/hero/hero.js`.
- Slug `MarmalElements\SuperHero` a cesty prvků (`content.obsah.nadpis`…) se **nemění** – rozbily by se vložené elementy.
- Barvy bez nastavení berou Global Settings (`--bde-brand-primary-color`, `--bde-links-color`, `--bde-headings-color`), mezery proměnné webu (`--content-width`, `--section-space`, `--gap-l`, `--radius-m`, `--btn-radius`). Pro celý web jde přepsat `--marmal-accent`, `--marmal-dark`, `--marmal-light`, `--marmal-header-h`.
- Otevírací doba a termíny slidů se počítají v prohlížeči (funguje s page cache).

## Patička – použití

- **MarMal → Patička**: rok spuštění (předvyplní se podle nejstarší stránky), držitel práv, podpis autora, údaje o podnikateli, stránka zásad, odkaz na nastavení cookies. Prázdné textové pole = výchozí text z pluginu (změna výchozího textu v kódu se po aktualizaci projeví na všech webech, které ho nepřepsaly).
- Do patičky v Breakdance: element **Marmal – Patička** (Content: rozložení a co zobrazit; Design: písmo a barva copyrightu, odkazy – písmo, barva, barva po najetí, podtržení (vždy / po najetí / zmizí po najetí / nikdy), jeho barva, tloušťka a odsazení –, zvlášť odkaz autora, údaje o podnikateli, tečky, mezery s breakpointy). Element generuje `tools/paticka_element.py` (neupravovat v Element Studiu), jeho `default.css` je kopie `modules/paticka/assets/paticka.css` – po změně CSS modulu skript spustit znovu.
- Rozvržení: Content → Patička → Rozložení (vlevo + vpravo / **vlevo + uprostřed + vpravo** / pod sebou / na střed) a Content → **Rozvržení – podrobně** (umístění každé části, zarovnání řádku pod, od kdy pod sebe, zarovnání na mobilu). V shortcodu `rozlozeni="tri"`, `pozice_copyright|odkazy|autor|udaje="vlevo|stred|vpravo|pod"`, `zarovnani_pod`, `skladat="mobil|tablet|maly|nikdy"`, `mobil="stred"`.
- Nebo element **Shortcode** s `[marmal_paticka]` (řádek 1: copyright vlevo, odkazy a autor vpravo; řádek 2: údaje o podnikateli). Rozložení `rozlozeni="radek|sloupec|stred"`, části vypnout `copyright|firma|odkazy|autor="ne"`.
- Jednotlivě: `[marmal_copyright drzitel="ne"]`, `[marmal_autor]`, `[marmal_firma pod_sebou="ano"]`, `[marmal_odkazy zasady="ne" cookies="ne"]`.
- Dynamická data Breakdance: kategorie MarMal → Patička, slugy `marmal_paticka_*` (copyright, roky, rok spuštění, aktuální rok, držitel, podpis autora – text a odkaz s UTM, údaje o podnikateli po jednom i celý řádek, zásady – text a odkaz).
- Vzhled: písmo a barva se dědí z elementu; proměnné `--marmal-paticka-gap` (nebo `-gap-row` / `-gap-col`), `--marmal-paticka-gap-cast`, `--marmal-paticka-odkaz`, `-odkaz-hover`, `-podtrzeni` / `-podtrzeni-hover` (`underline` / `none`), `-podtrzeni-barva` / `-podtrzeni-barva-hover`, `-podtrzeni-tloustka` / `-podtrzeni-tloustka-hover`, `-podtrzeni-odsazeni`, `--marmal-paticka-udaje-size`, `--marmal-paticka-udaje-opacity`. Odkazy mají silnější selektor než globální barva odkazů Breakdance.
- Rok se po Novém roce opraví skriptem i na stránce ze staré page cache (jen u shortcodů).
- Nastavení cookies: Complianz (`cmplz-show-banner`), CookieYes (`cky-banner-element`), Cookiebot (`Cookiebot.renew()`), jiný plugin přes vlastní CSS třídu.

## Ochrana osobních údajů – použití

- Zapnout v **MarMal → Moduly → Ochrana osobních údajů**. Údaje o správci (firma, sídlo, IČO, DIČ, rejstřík) se vyplňují tady a Patička je přebírá; bez tohoto modulu se vyplňují v Patičce.
- **MarMal → Ochrana os. údajů**: e-mail pro ochranu údajů, účely, zpracovatelé → Uložit → „Vytvořit návrh stránky“ → Náhled → doplnit vše žluté → „Zveřejnit návrh“.
- Shortcody: `[marmal_gdpr_spravce]`, `[marmal_gdpr_kontakt]`, `[marmal_gdpr_prijemci]`, `[marmal_gdpr_cookies]`, `[marmal_gdpr_ucinnost]`, pod formuláře `[marmal_gdpr_formular text="…"]`.

## Cookies – použití

- Zapnout v **MarMal → Moduly → Cookies**, vypnout starý cookie plugin (CookieYes apod. – modul na něj upozorní).
- **MarMal → Cookies**: Vzhled (box / lišta / okno, na mobilu kompaktní lišta dole / okno / stejné jako na počítači s nastavitelným bodem zlomu, barvy z Global Settings nebo vlastní s kontrolou kontrastu), Texty (krátký text pro mobil; prázdné = výchozí, překlad přes Polylang / WPML), Kategorie a cookies (služby automaticky / nalezené / ručně, vlastní cookies řádkem `název | poskytovatel | účel | platnost | kategorie`), Měření (ID GA4, Consent Mode v2 základní / rozšířený, zkrácení GA cookies na 13 měsíců), Záznam souhlasů (platnost souhlasu, revize, export CSV), Jak zapojit.
- Měřicí kódy vkládá modul sám podle ID v záložce Měření: GA4, Google Ads, Google Tag Manager, Sklik (SEM), Meta Pixel, LinkedIn, Clarity, Hotjar – vše s Consent Mode v2 (výchozí stav `denied`) a až po souhlasu s příslušnou kategorií. Pro GTM událost `marmal_consent` v dataLayer. Známé skripty (GA, Meta Pixel, Hotjar, Clarity) a iframy (YouTube, Vimeo, Google Mapy) v HTML stránky zablokuje do souhlasu; iframy nahradí náhledem s tlačítkem „Povolit a zobrazit“. Rozhoduje se v prohlížeči, takže to funguje i s page cache.
- Stránka Zásady cookies: MarMal → Cookies → Texty → „Vytvořit stránku Zásady cookies“ (koncept se vzorovým textem a tabulkou), po publikování na ni vede „Více o cookies“ v liště a odkaz „Zásady cookies“ v Patičce.
- Odkaz na otevření okna: tlačítko cookies v elementu **Marmal – Patička** (režim „MarMal – Cookies“ se zvolí sám), třída `marmal-cookies-nastaveni`, odkaz `#nastaveni-cookies`, shortcode `[marmal_cookies_nastaveni]`. Tabulka cookies na stránku zásad: `[marmal_cookies_seznam]`.
- JS API: `MarmalCookies.otevrit()`, `MarmalCookies.souhlas('analyticke')`, událost `marmal:cookies` (detail = povolené kategorie). Vlastní skript do souhlasu: `<script type="text/plain" data-marmal-kat="analyticke">…</script>`.
- Souhlas návštěvníka: cookie `marmal_consent` (revize, kategorie, náhodné ID, čas); záznamy bez IP v tabulce `{prefix}marmal_souhlasy`, úklid podle nastavení (cron `marmal_cookies_uklid`). Náhled lišty: `?marmal_cookies_nahled=1` (jen správce, nic neukládá).
- Zatím neřeší: iframy vložené až JavaScriptem (např. lazy video Breakdance) a reCAPTCHA.

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
- Breakdance 2.8+ (pro elementy a dynamická data; moduly Efekty, Patička, Cookies, Fonty a Ochrana osobních údajů fungují i bez něj)

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
