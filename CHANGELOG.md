# Changelog

## 0.19.0 – 2026-11-23 (vydáno po Martinově testu v builderu na Žehrovických bytech)
- **Nový element „Marmal – Drobečková navigace“** (modul SEO a sdílení) s nastavením vzhledu v Design tabu:
  - Rozložení: zarovnání (vlevo / na střed / vpravo), mezera mezi položkou a oddělovačem, mezera mezi řádky (s breakpointy),
  - Odkazy: písmo, barva a barva po najetí, podtržení (jen po najetí / vždy / vždy, po najetí zmizí / nikdy), barva, tloušťka a odsazení podtržení, pozadí a pozadí po najetí, vnitřní odsazení, zaoblení,
  - První položka (Úvod): vlastní písmo a barvy, velikost a barva ikony,
  - Aktuální stránka: písmo, barva, krytí, pozadí, vnitřní odsazení, zaoblení,
  - Oddělovač: barva, velikost, krytí.
  Content tab: text první položky, první položka jako text / jen ikona / ikona a text (vlastní ikona nebo domeček), skrytí první položky nebo aktuální stránky, oddělovač (znak nebo vlastní ikona), dlouhá cesta zalomit / jeden řádek se zkrácením. V builderu ukáže ukázkovou cestu, aby šel stylovat i v šabloně.
- Drobečková navigace: oddělovač je samostatný prvek `.marmal-drobecky__odd` (dřív CSS `::before`), první položka má třídu `__polozka--uvod`, poslední `__polozka--aktualni`; CSS v souboru `modules/seo/assets/drobecky.css`.
- Viditelná drobečková navigace (shortcode i element) funguje i na webu s jiným SEO pluginem (Rank Math, Yoast) – strukturovaná data BreadcrumbList vypisuje modul jen bez něj.

## 0.18.0 – 2026-11-23 (vydáno po Martinově testu na Žehrovických bytech)
- **Nový modul SEO a sdílení** (`seo`, zapínatelný v MarMal → Moduly, výchozí vypnuto). Když běží Yoast, Rank Math, SEOPress, AIOSEO, The SEO Framework, Slim SEO nebo Squirrly, nic nevypisuje a v administraci upozorní.
  - **box „SEO a sdílení“** u stránek, příspěvků a veřejných typů obsahu (klasický i blokový editor): titulek (počítadlo, doporučeno do 60 znaků), popisek (120–160), obrázek pro sdílení, noindex; náhled ve výsledcích Googlu a při sdílení na Facebooku; prázdné pole ukáže šedě, co se použije místo něj,
  - **pořadí hodnot**: vyplněno u stránky → filtr `marmal_seo_data` (např. Developerské projekty) → výchozí (vzor titulku, ručně vyplněný výňatek / výchozí popisek, náhledový obrázek / výchozí obrázek webu),
  - **MarMal → SEO**: název webu v titulcích, oddělovač, vzor titulku (`%nazev%`, `%web%`, `%oddelovac%`, `%popis_webu%`), titulek úvodní stránky, výchozí popisek a obrázek, og:locale (automaticky podle jazyka stránky – Polylang / WPML), typy obsahu s boxem, neindexování archivů autorů, data, štítků a stránek příloh,
  - **hlavička**: `<title>` přes `pre_get_document_title`, meta description, canonical (výchozí WordPressu odebrán; stránkování archivů `/page/2/`), robots `noindex, follow` přes `wp_robots` (vyhledávání vždy), Open Graph (type, title, description, url, site_name, locale, image + rozměry, typ, alt; u příspěvků article:published/modified_time), Twitter `summary_large_image`. Archivy: titulek ze vzoru + „Strana N“; 404 a vyhledávání bez og značek,
  - **obrázek pro sdílení 1200 × 630 px (JPEG)** se vytváří jen pro opravdu použité obrázky (při uložení stránky / nastavení, jinak jednorázově na pozadí) a zapisuje se do metadat přílohy (smaže se s ní); do té doby velikost large,
  - **drobečková navigace**: shortcode `[marmal_drobecky]` (oddelovac, uvod, aktualni) + JSON-LD BreadcrumbList v hlavičce; cesta podle nadřazených stránek, u příspěvků stránka blogu → rubrika, u vlastních typů nadřazená stránka z nastavení (např. Jednotky → Přehled bytů) nebo archiv typu; filtr `marmal_seo_drobecky`,
  - **sitemap** `/wp-sitemap.xml` (WordPress): bez stránek s noindex (filtr `marmal_seo_sitemap_vyradit` pro další), bez autorů a štítků, když jsou neindexované; **robots.txt**: vlastní řádky (jen známé direktivy), upozornění na fyzický robots.txt / sitemap.xml v kořeni webu a na skrytý web,
  - **převod z Yoast SEO a Rank Math**: titulky, popisky, obrázky, noindex stránek; oddělovač a výchozí obrázek webu; proměnné (`%%title%%`, `%title%`, `%%sep%%`…) převedené na zástupné symboly; titulek shodný se vzorem se neukládá; data původního pluginu beze změny, vyplněné hodnoty se nepřepisují (volitelně ano),
  - upozornění: chybí favicon, výchozí obrázek, web skrytý před vyhledávači.
- Anglické překlady textů na webu (Úvod, Strana, Výsledky hledání…); administrace modulu SEO zatím jen česky.

## 0.17.0 – 2026-11-22 (vydáno po Martinově testu na ticha-trida.cz; 0.17.0-beta1 z 2026-11-21)
Podle zadání z optimalizace rychlosti ticha-trida.cz (PageSpeed 21. 11. 2026: mobil 81, počítač 94).
- **Cookies – samostatné rozvržení pro mobil** (MarMal → Cookies → Vzhled): „Rozložení na mobilu“ *Kompaktní lišta dole* (výchozí) / *Okno uprostřed* / *Stejné jako na počítači* (chování do 0.16) a „Bod zlomu pro mobil“ (výchozí 767 px); na záložce Texty „Krátký text pro mobil“ (max. 160 znaků, výchozí „Používáme cookies. Analytické a marketingové jen s vaším souhlasem.“).
  - kompaktní lišta: plná šířka, přilepená dole, `env(safe-area-inset-bottom)`, nadpis skrytý, text 14 px (max. 3 řádky), za ním odkazy Více o cookies · Nastavení, Přijmout vše a Odmítnout vše vedle sebe (2 × 50 %, stejný vzhled, výška 44 px, nezalomí se ani na 320 px), křížek 44 × 44 px; okno nastavení na telefonu přes celou obrazovku. Na 375 × 812 px má lišta ~140 px (dřív ~330 px),
  - lišta se nově **vypisuje přímo v HTML** (`wp_footer`), ne JavaScriptem – nezhorší LCP; návštěvníkovi s platnou volbou ji skryje třída `marmal-ck-ma` nastavená skriptem v hlavičce ještě před vykreslením (bez probliknutí, funguje s page cache),
  - lišta není modální: `role="region"`, `aria-label="Souhlas s cookies"`; vjezd na mobilu jen `transform` 200 ms, vypnutý při `prefers-reduced-motion`; `position: fixed` bez posunu obsahu (žádný CLS).
- **Fonty – přednačtení podle webu** (MarMal → Fonty): místo checkboxu režimy *Automaticky podle webu* (výchozí) / *Ručně* / *Vypnuto*.
  - automaticky: písmo textu (`typography.body_font`) a nadpisů (`typography.heading_font`) z Global Settings Breakdance, 1 soubor na písmo (stejné písmo = 1 soubor), latin, normal; variabilní písmo bez ohledu na váhu, statické podle váhy z Global Settings (`advanced.body` / `advanced.headings.all_headings` → `fontWeight`), jinak 400; písmo, které mezi staženými není, se nepřednačítá a administrace i dashboard modulů upozorní,
  - ručně: tabulka stažených souborů (písmo, styl, váha / rozsah, znaky, velikost), max. 3; výběr se váže na písmo + styl + váhu + znakovou sadu, takže přežije „Smazat a stáhnout znovu“,
  - přednačte se jen soubor ze sady, kterou stránka opravdu načítá; `crossorigin` vždy,
  - u každého souboru se eviduje rodina, styl, váhy a znaková sada (sady stažené dřív se doplní jednou z lokálního CSS),
  - nový přehled **„Odkud Breakdance písma bere“** (na vyžádání): Global Settings, typografické presety, jiná nastavení Global Settings, konkrétní stránka / šablona / hlavička s typem a ID elementu a odkazem do builderu.
- **Obrázky – `sizes="auto"` u lazy obrázků** (MarMal → Obrázky → „Doplňovat sizes=auto u lazy obrázků“, výchozí zapnuto): output buffer od `template_redirect` doplní `auto, ` na začátek `sizes` u `<img>` s `loading="lazy"`, `srcset` a `sizes` bez auto. Obrázky bez lazy (logo, hero) beze změny. Chybí-li `width` i `height`, doplní se z metadat přílohy (třída `wp-image-ID`, jinak `attachment_url_to_postid`, max. 30 dotazů na stránku) s `data-marmal-rozmery` a stylem `:where(img[data-marmal-rozmery]){height:auto}` (nulová specifičnost – nezmění vzhled); bez zjistitelných rozměrů se obrázek nemění. Neběží v administraci, builderu, REST, AJAXu, cronu; HTML se upraví před uložením do page cache. Ověřeno v Chrome: karta 230 px při šířce 1920 px stáhne soubor 300 px (DPR 2: 768 px) místo 1920 px.
- **Obrázky – přehled obrázků na pozadí v plné velikosti** (na vyžádání): sekce, sloupce a divy, jejichž obrázek na pozadí používá velikost „Full“ (nebo ji nemá nastavenou), s velikostí souboru, breakpointy a odkazem do builderu.
- Anglické překlady nových textů.

## 0.16.0 – 2026-11-21
- **Nový modul Obrázky** (`obrazky`, zapínatelný v MarMal → Moduly, výchozí vypnuto) – aby na web nešly zbytečně těžké obrázky. Nastavení v **MarMal → Obrázky**:
  - **převod při nahrání** (filtr `wp_handle_upload` – knihovna médií, builder Breakdance, REST, `media_sideload_image`): JPG a PNG se uloží rovnou jako WebP (volitelně AVIF, když ho server umí), orientace podle EXIF, zmenšení na nastavené maximum (výchozí 1920 px), odstranění metadat (EXIF, GPS, XMP; profil ICC zůstane u Imagick), kvalita 80; zmenšeniny pak vzniknou ve stejném formátu. GIF, SVG, ICO, WebP a AVIF beze změny. Když převod selže nebo by WebP byl větší než originál, zůstane originál (chyba v logu modulu). Jen pro uživatele s právem `upload_files` (ne soubory z veřejných formulářů). Doplňkově `big_image_size_threshold`, `wp_editor_set_quality`, `image_editor_output_format`,
  - **přehled knihovny** (jen čtení): formáty, kandidáti na převod (používané JPG nad 200 kB a PNG nad 50 kB – prahy jdou nastavit; použití z dat Breakdance publikovaných, naplánovaných, soukromých a konceptových příspěvků bez revizí, obsahu, náhledových obrázků a Rank Math; celé názvy souborů, ne podřetězce), odhad úspory, 20 největších používaných obrázků s odkazem na stránku; logo a ikona webu se nepřevádějí,
  - **hromadný převod** po krocích (~20 s na AJAX požadavek, pozastavit / pokračovat i po zavření prohlížeče, zámek proti dvěma oknům): záloha všech souborů přílohy a evidence (soubor, metadata, MIME) → WebP ze `original_image` (název bez `-scaled`, při kolizi `-w`) → nové metadata → mapa URL → přepis odkazů: mediální objekty převedených příloh v `_breakdance_data` se sestaví znovu funkcí Breakdance `prepareMedia()` (url, sizes, srcset bez neexistující 2048w; alt a caption z builderu zůstanou), ostatní řetězce mapou (pozadí, text, vlastní CSS), dále `post_content`, Rank Math OG / Twitter a globální volby Breakdance; data, která nejdou dekódovat, se přeskočí (nic částečného). Před změnou záloha dat stránky do souboru. Pak regenerace CSS Breakdance (`regenerate_global_settings_cache`, `generateCacheForPost` – jako Settings → Tools), vymazání stránkové cache (WP Super Cache, Cache Enabler, WP Rocket, LiteSpeed, W3TC) a kontrola (nové soubory, zbylé odkazy na staré soubory),
  - **vrácení** celé dávky (od nejnovější) i jednoho obrázku: obnoví soubor, metadata a MIME, data stránek ze zálohy (když se stránka od převodu nezměnila, jinak zpětným přepisem – úpravy zůstanou), CSS, pak smaže WebP. Evidence nezávisí na verzi pluginu,
  - staré soubory zůstávají; **„Smazat staré soubory“** nejdřív 14 dní po převodu, záloha se maže zvlášť; záloha v `uploads/marmal-obrazky-zaloha/<datum>-<náhodný řetězec>/` (+ `.htaccess`, `index.php`),
  - sloupec „Převod“ v knihovně médií (původní → nová velikost), upozornění na konfliktní pluginy (Converter for Media, EWWW, ShortPixel, Imagify, WebP Express, Optimole, Smush) a na server bez WebP.
- Anglické překlady modulu.

## 0.13.0 – 2026-11-18
- **Nový modul Fonty** (`fonty`, zapínatelný v MarMal → Moduly, výchozí vypnuto) – Google Fonts z vlastní domény (GDPR: Google nedostane IP adresy návštěvníků; rychlejší načtení):
  - ve výsledné stránce najde každý odkaz na `fonts.googleapis.com/css` i `/css2` (`<link>`, `@import`, přednačtení) – nezáleží, jestli ho přidal Breakdance, jiný plugin, nebo Custom Code,
  - při prvním výskytu sadu jednou stáhne do `uploads/marmal-fonty/` (woff2, jen latinka a latinka s diakritikou; cyrilice, řečtina a vietnamština volitelně) a odkaz nahradí lokálním CSS; další stránky už jen používají stažené soubory,
  - odstraní `preconnect` / `dns-prefetch` na servery Google Fonts, doplní `font-display: swap` a přednačte běžný řez (400) hlavních fontů,
  - stahuje jen z `fonts.googleapis.com` a `fonts.gstatic.com` přes HTTPS, každý soubor zkontroluje (podpis woff2/woff, velikost), nejvýš 3 nové sady na jeden požadavek, zámek proti souběžnému stahování; když stažení selže, odkaz na Google zůstane (web nepřijde o písmo) a další pokus nejdřív za hodinu,
  - MarMal → Fonty: přehled fontů (stav, počet a velikost souborů, kdy staženo, kdy naposledy na webu), „Projít web a stáhnout“ (načte úvodní stránku a vyčistí page cache WP Rocket / LiteSpeed / W3TC / WP Super Cache), „Smazat a stáhnout znovu“, znakové sady, swap, přednačtení, návod na kontrolu,
  - v builderu Breakdance se nic nemění – fonty se tam dál vybírají a načítají z Google Fonts.
- **Cookies**: upozornění v administraci (MarMal a Pluginy), když web načítá Google Fonts z Googlu – s odkazem na modul Fonty.

## 0.12.0 – 2026-11-18
- **Cookies – měřicí kódy** (MarMal → Cookies → Měření): kromě GA4 nově **Google Ads** (AW-…), **Google Tag Manager** (GTM-…), **Sklik – Seznam Event Measurement** (sul.js, SEM ID), **Meta Pixel**, **LinkedIn Insight Tag**, **Microsoft Clarity** a **Hotjar** (Contentsquare). Stačí vložit ID, kód vloží modul sám:
  - GA4 a Google Ads sdílí jeden Google tag; v základním Consent Mode se načte po souhlasu s analytickými nebo marketingovými, GA4 se nastaví jen s analytickými a Ads jen s marketingovými,
  - GTM: výchozí „až po souhlasu“ (analytické nebo marketingové), volitelně „vždy“ (Consent Mode hlídá značky); do dataLayer jde událost `marmal_consent` s proměnnými `marmal_analyticke`, `marmal_marketingove`, `marmal_preferencni` – při načtení stránky s uloženou volbou i při každé změně,
  - Sklik SEM po souhlasu s marketingovými, souhlas se předá `SEM('updateConsent')` před PageView,
  - Clarity po souhlasu s analytickými a s `clarity('consentv2')`; při změně volby modul aktualizuje Clarity i Sklik,
  - Meta Pixel a LinkedIn po souhlasu s marketingovými, Hotjar po souhlasu s analytickými,
  - každé ID se ověří podle tvaru (neplatné se neuloží a ukáže se chyba), do stránky jde jen ověřené ID,
  - služby se samy objeví v kategoriích a v tabulce cookies (`_gcl_au`, `IDE`, `sid`, `udid`, `li_fat_id`, `bcookie`, `li_sugr`, `UserMatchHistory`…).
- **Blokování** ručně vložených kódů nově pozná i GTM, Google Ads (gtag.js s ID AW- = marketingové), Sklik (`rc.js`, `sul.js`, `retargetingHit`), LinkedIn a nový kód Hotjaru.
- Návod v MarMal → Cookies připomíná vypnutí vlastních cookies Breakdance (Breakdance → Settings → Privacy → „Disable Page & Session Cookies“).
- Prvky čekající na souhlas můžou mít víc kategorií (`data-marmal-kat="analyticke marketingove"` = stačí kterákoli).

## 0.11.1 – 2026-11-18
- Stejný obsah jako 0.11.0 (modul Cookies + rozvržení Patičky). Nové číslo verze, aby aktualizaci dostaly i weby s testovacím ZIPem 0.11.0 (bez rozvržení Patičky).

## 0.11.0 – 2026-11-18
- **Nový modul Cookies** (`cookies`, zapínatelný v MarMal → Moduly, výchozí vypnuto) – vlastní cookie lišta podle pravidel ÚOOÚ místo CookieYes, nastavení v **MarMal → Cookies**:
  - lišta jako box v rohu (výchozí vpravo dole), lišta dole / nahoře nebo okno uprostřed; okno „Nastavení cookies“ uprostřed nebo jako panel z boku; na mobilu spodní panel,
  - „Přijmout vše“ a „Odmítnout vše“ vždy stejně výrazné v první vrstvě, nic předem zaškrtnuté, křížek = zavřít bez souhlasu, ikonka pro změnu volby, ovládání klávesnicí,
  - barvy z Global Settings Breakdance nebo vlastní (předvolby světlá / tmavá / neutrální, kontrola kontrastu WCAG), zaoblení, šířka, stín, styl tlačítek,
  - texty a popisy kategorií jdou přepsat, prázdné pole = výchozí český text, překlad přes Polylang / WPML,
  - **Google Consent Mode v2** (výchozí `denied`, mapování kategorií na `analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization`) a vložení **GA4** (základní režim: tag až po souhlasu; rozšířený na přání), zkrácení GA cookies na 13 měsíců,
  - **blokování do souhlasu** přímo v HTML stránky: GA, Meta Pixel, Hotjar, Microsoft Clarity; iframy YouTube, Vimeo a Google Map nahrazuje náhledem „Povolit a zobrazit“; funguje i s page cache,
  - **autodetekce služeb**: WordPress, WooCommerce, Polylang a GA4 podle nastavení, ostatní podle nalezených skriptů a iframů na stránkách (štítek „nalezeno na webu“), vlastní cookies řádkem,
  - **záznam souhlasů** bez IP adres (náhodné ID, volba, kategorie, revize) přes REST s kontrolou původu a limitem, statistika 30 dní, export CSV, automatický úklid, revize textů (lišta se všem zobrazí znovu), platnost souhlasu 3 / 6 / 12 měsíců,
  - shortcody `[marmal_cookies_seznam]` (tabulka cookies pro stránku zásad) a `[marmal_cookies_nastaveni]`, JS API `MarmalCookies` a událost `marmal:cookies`,
  - náhled lišty pro správce `?marmal_cookies_nahled=1`, upozornění, když na webu běží jiný cookie plugin.
- **Patička**: nový režim tlačítka cookies „MarMal – Cookies“; při zapnutém modulu Cookies ho automatický režim zvolí sám.
- **Patička – rozvržení**:
  - nové rozložení **„Copyright vlevo, odkazy uprostřed, podpis vpravo“** (`rozlozeni="tri"`) – střed je přesně uprostřed i při různě dlouhých krajích,
  - element „Marmal – Patička“ → Content → **Rozvržení – podrobně**: umístění každé části (copyright, odkazy, podpis autora, údaje o podnikateli) vlevo / uprostřed / vpravo / na řádku pod, zarovnání řádku pod, od kdy skládat pod sebe (mobil / tablet / malý mobil / nikdy) a zarovnání pod sebou (vlevo / na střed); v shortcodu atributy `pozice_*`, `zarovnani_pod`, `skladat`, `mobil`,
  - údaje o podnikateli přesunuté do sloupce jsou na vlastním řádku; jejich písmo a barva z Design tabu platí všude,
  - podpis autora: „– marmal.cz“ se nezalomí, odkaz nezůstane sám na dalším řádku,
  - výchozí rozložení (copyright vlevo, odkazy a podpis vpravo, údaje pod) se nemění.

## 0.10.0 – 2026-11-18
- **Nový modul Patička** (`paticka`, zapínatelný v MarMal → Moduly, výchozí zapnuto) s nastavením v **MarMal → Patička**:
  - copyright s rokem spuštění webu a letošním rokem (`© 2019–2026 Název`, jen `© 2026` u webu spuštěného letos); rok spuštění se předvyplní podle nejstarší publikované stránky a jde přepsat; po Novém roce rok opraví skript i na stránce z page cache,
  - podpis autora „Web vytvořil Martin Malý – marmal.cz“ (výchozí texty z pluginu, na webu jdou přepsat nebo vypnout), UTM parametry (`utm_source` = doména webu), na úvodní stránce běžný odkaz, jinde `nofollow`,
  - údaje o podnikateli podle § 435 občanského zákoníku: firma / jméno, sídlo, IČO (kontrola kontrolní číslice), DIČ, zápis v rejstříku,
  - odkazy: ochrana osobních údajů (vybraná stránka nebo stránka z Nastavení → Soukromí, překlad přes Polylang / WPML) a „Nastavení cookies“ (Complianz, CookieYes, Cookiebot automaticky, jiný plugin přes CSS třídu),
  - **element „Marmal – Patička“** (Add → Patička): rozložení a výběr částí v Content, v Design písmo a barva copyrightu, **odkazy** (písmo, barva, barva po najetí, podtržení vždy / po najetí / zmizí po najetí / nikdy, barva podtržení a po najetí, tloušťka, odsazení), zvlášť **odkaz autora**, údaje o podnikateli, oddělovací tečky (barva / bez teček), mezery s breakpointy; element generuje `tools/paticka_element.py`,
  - výchozí barva odkazů = barva textu patičky i tam, kde web má globální barvu odkazů (dřív ji globální barva přebíjela a tlačítko cookies mělo jinou barvu než ostatní odkazy),
  - shortcody `[marmal_paticka]` (nově i `drzitel`, `pod_sebou`, `zasady`, `cookies`), `[marmal_copyright]`, `[marmal_autor]`, `[marmal_firma]`, `[marmal_odkazy]`; CSS a JS jen na stránkách s nimi,
  - dynamická data Breakdance (MarMal → Patička, 15 polí `marmal_paticka_*`),
  - vlastní texty jdou na vícejazyčném webu přeložit (Polylang – Překlady řetězců, WPML – String Translation).

## 0.9.0 – 2026-10-13
- **superHERO – nastavení vzhledu každé části** (Design, nové sekce):
  - **Mezery mezi prvky**: výchozí mezera a přesná mezera pod odznakem, eyebrow, nadpisem, textem, tlačítky a důvěrou; mezera mezi tlačítky, mezi výhodami a nad předělem / pruhem log (vše po breakpointech),
  - **Odznak – vzhled**: pozadí, barva a velikost ikony, barva blikající tečky, mezera ikona – text, rámeček (barva, tloušťka, zaoblení) a vnitřní odsazení,
  - **Eyebrow – vzhled**: pozadí pilulky, rámeček, odsazení; **Nadpis – zvýraznění**: barva a tloušťka podtržení / zvýrazňovače,
  - **Tlačítka – ikona**: velikost, mezera, pozice vlevo / vpravo; **Efekty tlačítek – barva efektu** (záře, pulz, výplň, lesk, stín),
  - **Výhody – vzhled**: barva a velikost ikony, barva a velikost kruhu, barva tečky, mezera, karty (pozadí, po najetí, rámeček, odsazení), barva přípony čísel,
  - **Důvěra a otevírací doba**: barva a velikost hvězdiček, kroužky zákazníků (velikost, okraj), výška a mezera log, barva tečky otevírací doby,
  - **Médium**: barva dekoru, rámeček obrázku, štítek (pozadí, ikona, rámeček, odsazení), tlačítko Play (pozadí, ikona, velikost), formulář (pozadí, text, rámeček, odsazení),
  - **Slider, šipka dolů a pauza**: barvy, aktivní tečka / průběh, pozadí a velikost šipek; **Efekty pozadí – barva dekorace**.
- Ikony z knihovny Breakdance se barví podle zvolené barvy (výplň `currentColor`).
- Opakovaný efekt tlačítka (pulz, zahoupání) se při najetí zastaví, aby byl vidět efekt po najetí.

## 0.8.0 – 2026-10-13
- **Nový element superHERO** (modul `hero`, vždy zapnutý) – úvodní sekce stránky postavená podle odladěného prototypu:
  - 5 rozložení, poměr sloupců, svislé zarovnání, minimální výška, šířka a mezery z design systému webu nebo vlastní,
  - pozadí: barva, přechod, živý přechod, fotka (Ken Burns), video (načte se jen na desktopu a bez šetření dat), slider jen pozadí nebo celé sekce (prolnutí, posun, zoom; tečky, šipky, čísla, průběh; swipe; termíny slidů),
  - text: odznak (blikající tečka / obrys / sklo, ikona), eyebrow, nadpis se *zvýrazněním* (barva, podtržení, zvýrazňovač, přelévání) a [rotujícími|slovy],
  - 2 tlačítka (6 stylů, ikony z knihovny Breakdance, 7 efektů po najetí vč. magnetu, upoutání pozornosti),
  - důvěra (hodnocení s hvězdičkami, fotky zákazníků, loga i jako jedoucí pruh), otevírací doba s živým stavem, výhody (seznam, karty, napočítávaná čísla),
  - médium: obrázek, video, formulář (shortcode), 10 masek, dekor, plovoucí štítek, play → video v lightboxu Galerie Plus,
  - efekty: 10 vstupních animací, dekorace pozadí (tečky, mřížka, zrno, světla, částice, paprsky, světlo za kurzorem), animace obrázku (vznášení, morph, zoom, 3D náklon), zeslábnutí / paralaxa při odscrollování, styl efektů na jedno kliknutí (Klidný / Moderní / Výrazný),
  - 9 dividerů (vlny i v pohybu), přichycené CTA na mobilu, tlačítko „Pozastavit pohyb“ (WCAG 2.2.2), typografie pro 10 textových prvků (presety Breakdance),
  - dynamická data: nadpis, text a obrázek z příspěvku nebo ACF (hero v šabloně),
  - výkon: LCP obrázek s `fetchpriority="high"` a srcset, ostatní lazy; JS jen se superHERO; respektuje prefers-reduced-motion.
- Všechny barvy superHERO přes výběr barvy Breakdance (kapátko, globální barvy), šířka elementu (celé okno / šířka obsahu / vlastní), vlastní styl tlačítek (pozadí, text, okraj, odsazení, písmo, stavy po najetí), výhody ve stylu „Předěl“.
- Nástroj `tools/hero_prvky.py` – generuje ovládací prvky elementu a výchozí hodnoty.
- Překlady nových textů (en_US, en_GB).

## 0.7.0 – 2026-10-08
- **Modulový systém**: nové menu **MarMal → Moduly** – karty modulů se stavem, zapnutí/vypnutí jedním kliknutím. Vypnutý modul nenačte žádný kód ani CSS.
- **Modul Efekty** (převzatý plugin MarMal Effects 1.1.0): 89 CSS efektů, galerie s náhledy a nastavení barev pod menu MarMal.
  - Nastavení `mm_effects_settings` i třídy `mm-…` zůstávají – přechod beze ztráty.
  - Tlačítko **Převést ze starého pluginu** deaktivuje MarMal Effects a zapne modul. Dokud starý plugin běží, modul se nenačte.
  - Nově volba **Kategorie načítané na webu** – na web jde jen CSS zapnutých kategorií (spojené do jednoho souboru v `uploads/marmal-bdp/`, při chybě zápisu po částech).
  - Výchozí stav: zapnuto na webech, kde se MarMal Effects už používal, jinde vypnuto.
- Galerie Plus je zamčený modul (vždy zapnutý – elementy nesmí zmizet ze stránek).
- Veřejné funkce pro MarMal Agenta: `marmal_bdp_verze()`, `marmal_bdp_moduly()`, `marmal_bdp_nastavit_modul()` – moduly jde přepínat z aplikace sprava.marmal.cz.
- Nástroj `tools/efekty_casti.py` – rozdělí CSS efektů na kategorie.
- Překlady nových textů (en_US, en_GB).

## 0.6.0 – 2026-10-07
- Překlady: plugin načítá jazykové soubory z `languages/`, přidána angličtina (en_US, en_GB) pro CZ + EN weby (Polylang / WPML).
- Lightbox přebírá přeložené texty z PHP (Zavřít, Předchozí, Další…).
- Galerie Plus: volba „Galerie je v horní části stránky“ – první fotka se načte s vysokou prioritou (lepší LCP), další hned, zbytek líně.
- Nástroj `tools/i18n.py` – najde texty, aktualizuje .pot/.po a zkompiluje .mo.

## 0.5.0 – 2026-10-07
- Galerie Plus – nový typ **Slider s miniaturami** (Design → Rozložení → Typ galerie):
  - pás miniatur pod hlavní fotkou, aktivní miniatura s rámečkem, pás se sám posouvá za aktivní fotkou,
  - šipky, swipe na dotyku, šipky na klávesnici, volitelné počítadlo a automatické přehrávání,
  - poměr stran nebo pevná výška hlavní fotky, režim „celá fotka“, počet a poměr miniatur po breakpointech,
  - video jako snímek, lightbox sdílený s mozaikou (slider se srovná na fotku z lightboxu),
  - bez externí knihovny (CSS scroll-snap + vlastní skript, načte se jen u slideru).

## 0.4.0 – 2026-10-07
- Galerie Plus – efekty po najetí (Design → Dlaždice):
  - pohyb fotky: Přiblížení / Pomalý posun / Žádný,
  - kombinovatelné doplňky: Světelný přejezd, Barvy na dotek, Zaostření, Popisek zespodu (+ písmo popisku),
  - efekty reagují i na fokus z klávesnice, pohyb respektuje „omezit pohyb“, na dotykových zařízeních jsou popisky vidět trvale.
- Změna volby „Na mobilu“ se v builderu projeví hned.

## 0.3.0 – 2026-10-07
- Galerie Plus – Mozaika:
  - fotka vždy vyplní celou dlaždici bez ohledu na poměr stran (odolné vůči obecnému `img { height: auto }`),
  - 20 vzorů pro 2, 3 a 4 sloupce (např. velká vlevo + 3 pod sebou, široká nahoře + 3 dole, mozaika 3 × 3),
  - „Výška galerie“ místo výšky řádku – všechny vzory mají stejnou celkovou výšku,
  - mřížka s volitelným poměrem stran dlaždic, volba výřezu fotky (střed, nahoře, dole…),
  - vzory se generují z jednoho zdroje: `tools/vzory.py`.

## 0.2.0 – 2026-10-07
- Nový element **Galerie Plus** – varianta Mozaika:
  - vzory se zarovnanými okraji (Vysoký–4–vysoký, Velký vlevo/vpravo + 4, Vysoký vlevo + 4, Velký + 2, rovnoměrná mřížka), nastavitelné po breakpointech,
  - opakování vzoru, dlaždice „+N“ s počtem dalších fotek, neúplné řady se skrývají,
  - na mobilu automaticky mřížka 2 sloupce (lze vypnout),
  - zdroj: výběr z knihovny médií nebo ACF pole galerie,
  - video dlaždice (YouTube bez cookies, Vimeo, MP4),
  - vlastní lightbox bez závislostí (klávesnice, swipe, popisky).

## 0.1.1 – 2026-10-07
- Test automatických aktualizací z GitHubu.
- Přidán CHANGELOG.

## 0.1.0 – 2026-10-07
- Výchozí verze: kostra pluginu, složky pro Element Studio (`MarmalElements`), automatické aktualizace z GitHubu.
