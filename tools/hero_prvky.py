#!/usr/bin/env python3
"""
superHERO – jediný zdroj ovládacích prvků elementu.

Z tohoto seznamu se vygeneruje:
  - elements/Super_Hero/element.php   (třída elementu ve formátu Element Studia),
  - includes/hero-vychozi.php         (výchozí hodnoty pro PHP vykreslení).

Spuštění (z kořene pluginu):  python3 tools/hero_prvky.py
Po přidání nebo změně prvku vždy spustit znovu, ať element i výchozí hodnoty sedí.

Cesty prvků (tab.sekce.slug) odpovídají prototypu superHERO – NEMĚNIT,
jinak se rozbijí hodnoty už vložených elementů.
"""
import os

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

# ------------------------------------------------------------------ pomocné konstruktory
def text(k, l, d=''):            return dict(k=k, l=l, t='text', d=d)
def dd(k, l, items, d):          return dict(k=k, l=l, t='dropdown', items=items, d=d)
def tog(k, l, d=False):          return dict(k=k, l=l, t='toggle', d=d)
def num(k, l, mn, mx, st, d):    return dict(k=k, l=l, t='number', min=mn, max=mx, step=st, d=d)
def unit(k, l, d):               return dict(k=k, l=l, t='unit', d=d)
def color(k, l, d=''):           return dict(k=k, l=l, t='color', d=d)
def media(k, l, multi=False):    return dict(k=k, l=l, t='wpmedia', multi=multi, d=None)
def icon(k, l):                  return dict(k=k, l=l, t='icon', d=None)
def typo(k, l):                  return dict(k=k, l=l, t='typography', d=None)

BTN = [('primary', 'Primary'), ('secondary', 'Secondary'), ('outline', 'Outline'), ('sklo', 'Sklo'), ('prechod', 'Přechod'), ('link', 'Odkaz')]
ANO_NE = None

CONTENT = [
    ('zdroj', 'Zdroj obsahu', [
        dd('rezim', 'Obsah', [('rucne', 'Ručně'), ('dynamicky', 'Dynamická data (šablona)')], 'rucne'),
        dd('nadpis_pole', 'Nadpis z', [('post_title', 'Název příspěvku'), ('acf', 'ACF pole'), ('none', 'Ručně zadaný nadpis')], 'post_title'),
        text('nadpis_acf', 'ACF pole nadpisu (název)', 'hero_nadpis'),
        dd('text_pole', 'Text z', [('post_excerpt', 'Výňatek příspěvku'), ('acf', 'ACF pole'), ('none', 'Ručně zadaný text')], 'post_excerpt'),
        text('text_acf', 'ACF pole textu (název)', 'hero_text'),
        dd('obrazek_pole', 'Obrázek z', [('featured_image', 'Náhledový obrázek'), ('acf', 'ACF pole'), ('none', 'Ručně vybraný obrázek')], 'featured_image'),
        text('obrazek_acf', 'ACF pole obrázku (název)', 'hero_obrazek'),
    ]),
    ('obsah', 'Text', [
        text('odznak', 'Odznak nad nadpisem (prázdné = skryto)'),
        dd('odznak_styl', 'Styl odznaku', [('obrys', 'Obrys'), ('tecka', 'Blikající tečka'), ('sklo', 'Sklo')], 'tecka'),
        icon('odznak_ikona', 'Ikona odznaku (u stylu Obrys / Sklo)'),
        text('eyebrow', 'Nadnadpis (eyebrow)', 'Nadnadpis'),
        text('nadpis', 'Nadpis – *slovo* = zvýraznění, [a|b|c] = rotující slova', 'Hlavní sdělení, které *zaujme*'),
        dd('nadpis_tag', 'HTML tag nadpisu', [('h1', 'H1'), ('h2', 'H2'), ('p', 'p')], 'h1'),
        text('text', 'Podpůrný text', 'Jedna až dvě věty o tom, co nabízíte a proč právě vy.'),
    ]),
    ('tlacitka', 'Tlačítka', [
        text('btn1_text', 'Tlačítko 1 – text (prázdné = skryto)', 'Nezávazná poptávka'),
        text('btn1_url', 'Tlačítko 1 – odkaz', '#kontakt'),
        tog('btn1_nove_okno', 'Tlačítko 1 – otevřít v novém okně'),
        icon('btn1_ikona', 'Tlačítko 1 – ikona'),
        text('btn2_text', 'Tlačítko 2 – text (prázdné = skryto)', 'Naše reference'),
        text('btn2_url', 'Tlačítko 2 – odkaz', '#reference'),
        tog('btn2_nove_okno', 'Tlačítko 2 – otevřít v novém okně'),
        icon('btn2_ikona', 'Tlačítko 2 – ikona'),
    ]),
    ('video', 'Video v lightboxu', [
        dd('spoustec', 'Otevřít video', [('zadny', 'Ne'), ('tlacitko2', 'Tlačítkem 2'), ('medium', 'Play na obrázku'), ('oboje', 'Obojí')], 'zadny'),
        text('url', 'Odkaz na video (YouTube / Vimeo / MP4)'),
        text('soubor', 'Nebo adresa MP4 z knihovny médií (Média → Kopírovat URL)'),
    ]),
    ('duvera', 'Důvěra', [
        dd('typ', 'Typ', [('zadna', 'Žádná'), ('hodnoceni', 'Hodnocení'), ('avatary', 'Zákazníci (fotky)'), ('loga', 'Loga')], 'zadna'),
        text('hodnoceni_cislo', 'Hodnocení (např. 4,9)', '4,9'),
        text('hodnoceni_text', 'Text u hodnocení', 'z 87 recenzí na Googlu'),
        text('avatary_text', 'Text u fotek', '+120 spokojených zákazníků'),
        num('avatary_pocet', 'Počet kroužků bez fotek', 3, 6, 1, 4),
        media('avatary_foto', 'Fotky zákazníků (jen se souhlasem)', True),
        text('loga_nadpis', 'Nadpis u log', 'Pracovali jsme pro'),
        media('loga_soubory', 'Loga', True),
        text('loga', 'Nebo názvy oddělené čárkou'),
        dd('loga_pozice', 'Umístění log', [('dole', 'Pruh dole'), ('pod', 'Pod tlačítky')], 'dole'),
        tog('loga_pohyb', 'Loga pomalu jedou (jen pruh dole)'),
    ]),
    ('oteviraci', 'Otevírací doba', [
        tog('zapnout', 'Zobrazit „Teď máme otevřeno“'),
        dd('dny', 'Dny', [('po-pa', 'Po–Pá'), ('po-so', 'Po–So'), ('denne', 'Denně')], 'po-pa'),
        text('od', 'Od (např. 7:00)', '7:00'),
        text('do', 'Do (např. 17:00)', '17:00'),
        text('telefon', 'Telefon'),
        dd('pozice', 'Umístění', [('pod', 'Pod tlačítky'), ('nahore', 'Nad nadpisem')], 'pod'),
    ]),
    ('vyhody', 'Výhody / čísla', [
        text('polozka1', 'Položka 1 (pro čísla „120+ | dokončených zakázek“; prázdné = skryto)', 'Výhoda číslo jedna'),
        text('polozka2', 'Položka 2', 'Výhoda číslo dva'),
        text('polozka3', 'Položka 3', 'Výhoda číslo tři'),
        text('polozka4', 'Položka 4'),
        text('polozka5', 'Položka 5'),
        text('polozka6', 'Položka 6'),
        icon('ikona', 'Ikona položek (prázdné = fajfka)'),
    ]),
    ('medium', 'Médium vedle textu', [
        dd('typ', 'Typ', [('obrazek', 'Obrázek'), ('video', 'Video'), ('formular', 'Formulář'), ('zadne', 'Nic')], 'obrazek'),
        media('obrazek', 'Obrázek'),
        text('alt', 'Alt text (prázdné = z knihovny médií)'),
        text('video', 'Video – adresa MP4 bez zvuku (Média → Kopírovat URL)'),
        text('form_nadpis', 'Nadpis nad formulářem'),
        text('form_shortcode', 'Formulář – shortcode (např. Global Block s formulářem)'),
        text('stitek', 'Plovoucí štítek u obrázku (prázdné = skryto)'),
        icon('stitek_ikona', 'Ikona štítku (prázdné = hvězda)'),
    ]),
    ('pozadi', 'Pozadí sekce', [
        dd('typ', 'Typ pozadí', [('zadne', 'Žádné'), ('barva', 'Barva'), ('gradient', 'Přechod'), ('zivy', 'Živý přechod (animovaný)'), ('obrazek', 'Obrázek'), ('video', 'Video')], 'barva'),
        media('obrazek', 'Obrázek / poster videa'),
        text('video', 'Video pozadí – adresa MP4 bez zvuku, 2–4 MB (Média → Kopírovat URL)'),
    ]),
    ('slider', 'Slider', [
        dd('rezim', 'Slider', [('vypnuto', 'Vypnuto'), ('pozadi', 'Jen pozadí'), ('cely', 'Celá sekce')], 'vypnuto'),
        media('obrazky', 'Obrázky slidů', True),
        text('slide2_nadpis', 'Slide 2 – nadpis (prázdné = slide se nezobrazí)'),
        text('slide2_text', 'Slide 2 – text'),
        text('slide2_termin', 'Slide 2 – zobrazit jen v termínu (např. 1.12.–31.12.)'),
        text('slide3_nadpis', 'Slide 3 – nadpis (prázdné = slide se nezobrazí)'),
        text('slide3_text', 'Slide 3 – text'),
        text('slide3_termin', 'Slide 3 – zobrazit jen v termínu (např. 1.12.–31.12.)'),
        text('slide4_nadpis', 'Slide 4 – nadpis (prázdné = slide se nezobrazí)'),
        text('slide4_text', 'Slide 4 – text'),
        text('slide4_termin', 'Slide 4 – zobrazit jen v termínu (např. 1.12.–31.12.)'),
        text('slide5_nadpis', 'Slide 5 – nadpis (prázdné = slide se nezobrazí)'),
        text('slide5_text', 'Slide 5 – text'),
        text('slide5_termin', 'Slide 5 – zobrazit jen v termínu (např. 1.12.–31.12.)'),
    ]),
    ('doplnky', 'Doplňky', [
        dd('sipka', 'Ukazatel „posunout dolů“', [('zadna', 'Žádný'), ('kruh', 'Šipka'), ('mys', 'Myš'), ('cara', 'Čára')], 'zadna'),
        tog('header', 'Nad sekcí je průhledný header (přidá odsazení)'),
        tog('pauza', 'Tlačítko „Pozastavit pohyb“ (WCAG)', True),
        tog('sticky', 'Přichycené CTA na mobilu'),
        text('sticky_text', 'Text přichyceného tlačítka', 'Zavolat'),
        text('sticky_url', 'Odkaz přichyceného tlačítka', 'tel:+420'),
        icon('sticky_ikona', 'Ikona přichyceného tlačítka (prázdné = telefon)'),
    ]),
]

DESIGN = [
    ('rozlozeni', 'Rozložení', [
        dd('varianta', 'Varianta', [('split', 'Text vlevo, médium vpravo'), ('split-rev', 'Médium vlevo, text vpravo'), ('center', 'Na střed, médium pod textem'), ('stack', 'Vlevo, médium pod textem'), ('text', 'Jen text')], 'split'),
        dd('pomer', 'Poměr sloupců (text : médium)', [('1fr 1fr', '1:1'), ('1.2fr 1fr', '6:5'), ('1.5fr 1fr', '3:2'), ('1fr 1.2fr', '5:6')], '1.2fr 1fr'),
        dd('zarovnani', 'Svislé zarovnání', [('start', 'Nahoru'), ('center', 'Na střed'), ('end', 'Dolů')], 'center'),
        dd('min_vyska', 'Minimální výška', [('auto', 'Auto'), ('60', '60 % výšky okna'), ('75', '75 %'), ('85', '85 %'), ('100', '100 %')], '85'),
        tog('z_webu', 'Šířka, mezery a zaoblení z design systému webu', True),
        unit('sirka', 'Šířka obsahu (vlastní)', '1200px'),
        unit('odsazeni', 'Svislé odsazení sekce (vlastní)', '120px'),
        unit('mezera', 'Mezera mezi sloupci (vlastní)', '64px'),
    ]),
    ('barvy', 'Barvy (prázdné = z Global Settings)', [
        color('brand', 'Hlavní barva (brand)'),
        color('accent', 'Zvýrazňující barva (accent)'),
        color('tmava', 'Tmavá barva'),
        color('svetla', 'Světlá barva'),
    ]),
    ('pozadi', 'Pozadí a překryv', [
        dd('barva', 'Barva pozadí', [('tmava', 'Tmavá'), ('brand', 'Brand'), ('accent', 'Accent'), ('svetla', 'Světlá'), ('bila', 'Bílá')], 'tmava'),
        dd('prekryv', 'Barva překryvu', [('tmava', 'Tmavá'), ('brand', 'Brand')], 'tmava'),
        num('prekryv_sila', 'Síla překryvu (%)', 0, 90, 5, 60),
        dd('prekryv_styl', 'Tvar překryvu', [('prechod', 'Přechod od textu'), ('plny', 'Plný')], 'prechod'),
        dd('barva_textu', 'Barva textu', [('auto', 'Auto'), ('svetly', 'Světlý'), ('tmavy', 'Tmavý')], 'auto'),
    ]),
    ('typografie', 'Nadpis a eyebrow', [
        dd('velikost', 'Velikost nadpisu', [('S', 'S'), ('M', 'M'), ('L', 'L'), ('XL', 'XL')], 'L'),
        num('nadpis_max', 'Max. šířka nadpisu (znaků)', 10, 40, 1, 16),
        dd('eyebrow_styl', 'Styl eyebrow', [('kapitalky', 'Kapitálky'), ('pilulka', 'Pilulka')], 'kapitalky'),
    ]),
    ('tlacitka', 'Tlačítka', [
        dd('btn1_styl', 'Tlačítko 1 – styl', BTN, 'primary'),
        dd('btn2_styl', 'Tlačítko 2 – styl', BTN, 'outline'),
        tog('z_presetu', 'Tvar a velikost z Button Presets webu', True),
        dd('tvar', 'Tvar (vlastní)', [('hranaty', 'Hranatý'), ('zaobleny', 'Zaoblený'), ('pilulka', 'Pilulka')], 'zaobleny'),
        dd('velikost', 'Velikost (vlastní)', [('S', 'S'), ('M', 'M'), ('L', 'L')], 'M'),
    ]),
    ('vyhody', 'Výhody / čísla', [
        dd('styl', 'Styl', [('seznam', 'Seznam'), ('karty', 'Karty'), ('cisla', 'Čísla')], 'seznam'),
        dd('ikona_styl', 'Ikona', [('kruh', 'Ikona v kruhu'), ('ikona', 'Ikona'), ('tecka', 'Tečka'), ('blikajici', 'Blikající tečka')], 'kruh'),
    ]),
    ('medium', 'Médium', [
        dd('maska', 'Maska (tvar obrázku)', [('zadna', 'Obdélník'), ('oblouk', 'Oblouk'), ('blob', 'Organický tvar'), ('kruh', 'Kruh'), ('kapsle', 'Kapsle'), ('list', 'List'), ('sestiuhelnik', 'Šestiúhelník'), ('sikmo', 'Šikmý řez'), ('roh', 'Zkosené rohy'), ('vlna', 'Vlnitá hrana')], 'zadna'),
        dd('pomer', 'Poměr stran', [('4/3', '4:3'), ('auto', 'Podle obrázku'), ('1/1', '1:1'), ('3/4', '3:4'), ('16/9', '16:9')], '4/3'),
        unit('zaobleni', 'Zaoblení (vlastní)', '16px'),
        tog('stin', 'Stín', True),
        dd('dekor', 'Dekor za obrázkem', [('zadny', 'Žádný'), ('plocha', 'Plocha'), ('ramecek', 'Rámeček'), ('tecky', 'Tečky'), ('kruh', 'Kruh')], 'zadny'),
    ]),
    ('slider', 'Slider', [
        num('interval', 'Interval (s)', 3, 12, 1, 6),
        dd('prechod', 'Přechod', [('prolnuti', 'Prolnutí'), ('posun', 'Posun'), ('zoom', 'Zoom')], 'prolnuti'),
        dd('navigace', 'Navigace', [('tecky', 'Tečky'), ('sipky', 'Šipky'), ('cisla', 'Čísla + šipky'), ('progres', 'Průběh'), ('zadna', 'Žádná')], 'tecky'),
        tog('pauza', 'Zastavit při najetí myší', True),
    ]),
    ('vykon', 'Výkon', [
        tog('lcp', 'Hlavní obrázek načíst přednostně (LCP)', True),
        tog('setrit_data', 'Bez videa při šetření dat a pomalém připojení', True),
    ]),
    ('pismo', 'Písmo jednotlivých prvků', [
        typo('odznak', 'Odznak'),
        typo('eyebrow', 'Eyebrow'),
        typo('nadpis', 'Nadpis'),
        typo('text', 'Podpůrný text'),
        typo('tlacitka', 'Tlačítka'),
        typo('vyhody', 'Výhody (text položek)'),
        typo('cisla', 'Čísla'),
        typo('duvera', 'Důvěra'),
        typo('oteviraci', 'Otevírací doba'),
        typo('stitek', 'Plovoucí štítek'),
        color('barva_hl', 'Barva zvýraznění a rotujících slov'),
    ]),
    ('styl', 'Styl efektů', [
        dd('preset', 'Styl efektů (Klidný / Moderní / Výrazný přebije volby níže)', [('vlastni', 'Vlastní'), ('klidny', 'Klidný'), ('moderni', 'Moderní'), ('vyrazny', 'Výrazný')], 'vlastni'),
    ]),
    ('animace', 'Vstupní animace', [
        dd('vstup', 'Typ', [('zadna', 'Žádná'), ('fade', 'Prolnutí'), ('nahoru', 'Vyjetí zdola'), ('zleva', 'Příjezd zleva'), ('zprava', 'Příjezd zprava'), ('zoom', 'Přiblížení'), ('rozostreni', 'Z rozostření'), ('odkryti', 'Odkrytí maskou'), ('preklopeni', '3D překlopení'), ('slova', 'Nadpis po slovech')], 'nahoru'),
        tog('postupne', 'Prvky postupně za sebou', True),
        dd('rychlost', 'Rychlost', [('normal', 'Normálně'), ('rychle', 'Rychle'), ('pomalu', 'Pomalu')], 'normal'),
        dd('medium_vstup', 'Animace média', [('stejne', 'Stejná'), ('zoom', 'Přiblížení'), ('zprava', 'Zprava'), ('odkryti', 'Odkrytí'), ('rotace', 'Pootočení')], 'stejne'),
    ]),
    ('nadpis_efekt', 'Efekty nadpisu', [
        dd('zvyrazneni', 'Zvýraznění *slova*', [('barva', 'Barva'), ('podtrzeni', 'Podtržení'), ('zvyraznovac', 'Zvýrazňovač'), ('prechod', 'Přelévání')], 'barva'),
        dd('rotace', 'Rotující [slova]', [('vyjeti', 'Vyjetí'), ('prolnuti', 'Prolnutí'), ('psaci', 'Psací stroj')], 'vyjeti'),
    ]),
    ('tlacitka_efekt', 'Efekty tlačítek', [
        dd('hover', 'Efekt po najetí', [('zadny', 'Žádný'), ('zvednuti', 'Zvednutí se stínem'), ('sipka', 'Posun ikony'), ('vypln', 'Výplň zleva'), ('lesk', 'Přejezd lesku'), ('zare', 'Záře'), ('magnet', 'Magnet')], 'sipka'),
        dd('upoutat', 'Upoutat pozornost (tlačítko 1)', [('zadne', 'Ne'), ('pulz', 'Pulz'), ('lesk', 'Lesk'), ('houpnuti', 'Zahoupání')], 'zadne'),
    ]),
    ('pozadi_efekt', 'Efekty pozadí', [
        dd('dekor', 'Dekorace pozadí', [('zadny', 'Žádná'), ('tecky', 'Tečkový rastr'), ('mrizka', 'Mřížka'), ('sum', 'Filmové zrno'), ('bloby', 'Plovoucí světla'), ('castice', 'Stoupající částice'), ('paprsky', 'Otáčející se paprsky'), ('reflektor', 'Světlo za kurzorem')], 'zadny'),
        tog('dekor_pohyb', 'Dekorace v pohybu', True),
        tog('kenburns', 'Pomalý zoom fotky (Ken Burns)', True),
    ]),
    ('medium_efekt', 'Efekty média', [
        dd('animace', 'Animace obrázku', [('zadna', 'Žádná'), ('vznaseni', 'Vznášení'), ('morph', 'Měnící se tvar'), ('zoom', 'Pomalé přiblížení uvnitř'), ('naklon', '3D náklon za kurzorem')], 'zadna'),
        tog('stitek_anim', 'Plovoucí štítek se pohupuje', True),
    ]),
    ('odjezd', 'Při odscrollování', [
        dd('typ', 'Efekt', [('zadny', 'Žádný'), ('zeslabnuti', 'Zeslábnutí'), ('paralaxa', 'Paralaxa')], 'zadny'),
    ]),
    ('divider', 'Přechod do další sekce (divider)', [
        dd('tvar', 'Tvar', [('zadny', 'Žádný'), ('vlna', 'Vlna'), ('vlny', 'Vrstvené vlny'), ('oblouk', 'Oblouk'), ('kopec', 'Kopec'), ('sikmo', 'Šikmo'), ('krivka', 'Asymetrická křivka'), ('hory', 'Hory'), ('zuby', 'Zuby'), ('papir', 'Natržený papír')], 'zadny'),
        unit('vyska', 'Výška', '90px'),
        color('barva', 'Barva (= pozadí další sekce, prázdné = bílá)'),
        tog('otocit', 'Zrcadlit vodorovně'),
        tog('pohyb', 'Vlny v pohybu', True),
    ]),
    ('mobil', 'Tablet a mobil', [
        dd('stack_od', 'Skládat pod sebe od', [('tablet', 'Tabletu'), ('mobil', 'Mobilu')], 'tablet'),
        dd('medium_mobil', 'Médium po složení', [('pod', 'Pod textem'), ('nad', 'Nad textem'), ('skryt', 'Skrýt')], 'pod'),
        dd('zarovnani_mobil', 'Zarovnání textu po složení', [('vlevo', 'Vlevo'), ('stred', 'Na střed')], 'vlevo'),
        tog('tlacitka_full', 'Tlačítka na celou šířku (mobil)', True),
        dd('video_mobil', 'Video pozadí na mobilu', [('obrazek', 'Jen poster'), ('video', 'Přehrávat')], 'obrazek'),
        dd('animace_mobil', 'Animace pozadí a obrázku na mobilu', [('vypnout', 'Vypnout'), ('ponechat', 'Ponechat')], 'vypnout'),
        dd('min_vyska_mobil', 'Minimální výška na mobilu', [('auto', 'Auto'), ('60', '60 %'), ('75', '75 %'), ('100', '100 %')], 'auto'),
    ]),
]

# ------------------------------------------------------------------ PHP výstup
def q(s):
    return "'" + str(s).replace('\\', '\\\\').replace("'", "\\'") + "'"

def control(c, ind):
    p = ' ' * ind
    t = c['t']
    if t == 'typography':
        return (f'{p}getPresetSection(\n{p}    "EssentialElements\\\\typography_with_effects_and_align",\n'
                f'{p}    {q(c["l"])},\n{p}    {q(c["k"])},\n{p}    [\'type\' => \'popout\']\n{p})')
    if t == 'text':
        opts = "['type' => 'text', 'layout' => 'vertical']"
    elif t == 'dropdown':
        items = ', '.join(f"['value' => {q(v)}, 'text' => {q(x)}]" for v, x in c['items'])
        opts = f"['type' => 'dropdown', 'layout' => 'vertical', 'items' => [{items}]]"
    elif t == 'toggle':
        opts = "['type' => 'toggle', 'layout' => 'inline']"
    elif t == 'number':
        opts = f"['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => {c['min']}, 'max' => {c['max']}, 'step' => {c['step']}]]"
    elif t == 'unit':
        opts = "['type' => 'unit', 'layout' => 'inline']"
    elif t == 'color':
        opts = "['type' => 'color', 'layout' => 'inline']"
    elif t == 'wpmedia':
        multi = 'true' if c['multi'] else 'false'
        opts = f"['type' => 'wpmedia', 'layout' => 'vertical', 'mediaOptions' => ['acceptedFileTypes' => ['image'], 'multiple' => {multi}]]"
    elif t == 'icon':
        opts = "['type' => 'icon', 'layout' => 'vertical']"
    else:
        raise ValueError(t)
    return f'{p}c(\n{p}    {q(c["k"])},\n{p}    {q(c["l"])},\n{p}    [],\n{p}    {opts},\n{p}    false,\n{p}    false,\n{p}    [],\n{p})'

def sections(spec):
    out = []
    for sid, label, ctrls in spec:
        inner = ',\n'.join(control(c, 16) for c in ctrls)
        out.append(f"            c(\n                {q(sid)},\n                {q(label)},\n                [\n{inner}\n                ],\n"
                   f"                ['type' => 'section', 'layout' => 'vertical'],\n                false,\n                false,\n                [],\n            )")
    return '[\n' + ',\n'.join(out) + '\n        ]'

def ssr_paths():
    paths = [f'content.{s}' for s, _, _ in CONTENT]
    paths += [f'design.{s}' for s, _, _ in DESIGN if s != 'pismo']
    return paths

ELEMENT = '''<?php
/**
 * superHERO (MarmalElements\\SuperHero) – úvodní sekce stránky.
 *
 * VYGENEROVÁNO nástrojem tools/hero_prvky.py – ovládací prvky neupravuj ručně,
 * uprav seznam v nástroji a spusť ho znovu. Logika vykreslení je v includes/hero.php.
 * Slug MarmalElements\\SuperHero a cesty prvků se už nesmí měnit (rozbily by se vložené elementy).
 */

namespace MarmalElements;

use function Breakdance\\Elements\\c;
use function Breakdance\\Elements\\PresetSections\\getPresetSection;


\\Breakdance\\ElementStudio\\registerElementForEditing(
    "MarmalElements\\\\SuperHero",
    \\Breakdance\\Util\\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class SuperHero extends \\Breakdance\\Elements\\Element
{
    static function uiIcon()
    {
        return 'BracketsIcon';
    }

    static function tag()
    {
        return 'section';
    }

    static function tagOptions()
    {
        return ['section', 'header', 'div'];
    }

    static function tagControlPath()
    {
        return false;
    }

    static function name()
    {
        return 'superHERO';
    }

    static function className()
    {
        return 'marmal-hero-element';
    }

    static function category()
    {
        return 'basic';
    }

    static function badge()
    {
        return false;
    }

    static function slug()
    {
        return __CLASS__;
    }

    static function template()
    {
        return file_get_contents(__DIR__ . '/html.twig');
    }

    static function defaultCss()
    {
        return file_get_contents(__DIR__ . '/default.css');
    }

    static function defaultProperties()
    {
        return false;
    }

    static function defaultChildren()
    {
        return false;
    }

    static function cssTemplate()
    {
        $template = file_get_contents(__DIR__ . '/css.twig');
        return $template;
    }

    static function designControls()
    {
        return %DESIGN%;
    }

    static function contentControls()
    {
        return %CONTENT%;
    }

    static function settingsControls()
    {
        return [];
    }

    static function dependencies()
    {
        return false;
    }

    static function settings()
    {
        return false;
    }

    static function addPanelRules()
    {
        return false;
    }

    static public function actions()
    {
        return false;
    }

    static function nestingRule()
    {
        return ['type' => 'final'];
    }

    static function spacingBars()
    {
        return false;
    }

    static function attributes()
    {
        return false;
    }

    static function experimental()
    {
        return false;
    }

    static function availableIn()
    {
        return ['breakdance'];
    }


    static function order()
    {
        return 101;
    }

    static function dynamicPropertyPaths()
    {
        return false;
    }

    static function additionalClasses()
    {
        return false;
    }

    static function projectManagement()
    {
        return false;
    }

    static function propertyPathsToWhitelistInFlatProps()
    {
        return false;
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return %SSR%;
    }
}
'''

def php_val(v):
    if isinstance(v, bool):
        return 'true' if v else 'false'
    if v is None:
        return 'null'
    if isinstance(v, (int, float)):
        return str(v)
    return q(v)

def main():
    el = (ELEMENT.replace('%DESIGN%', sections(DESIGN))
                 .replace('%CONTENT%', sections(CONTENT))
                 .replace('%SSR%', '[' + ', '.join(q(p) for p in ssr_paths()) + ']'))
    os.makedirs(os.path.join(ROOT, 'elements/Super_Hero'), exist_ok=True)
    with open(os.path.join(ROOT, 'elements/Super_Hero/element.php'), 'w', encoding='utf-8') as f:
        f.write(el)

    hodnoty, textove, vlastnosti = [], [], {}
    for tab, spec in (('content', CONTENT), ('design', DESIGN)):
        for sid, _, ctrls in spec:
            for c in ctrls:
                if c['t'] == 'typography':
                    continue
                path = f"{tab}.{sid}.{c['k']}"
                hodnoty.append(f"        '{path}' => {php_val(c['d'])},")
                if c['t'] == 'text':
                    textove.append(f"        '{path}',")
                # výchozí vlastnosti nově vloženého elementu (jen jednoduché typy)
                if c['t'] in ('text', 'dropdown', 'toggle', 'number') and c['d'] not in (None, ''):
                    vlastnosti.setdefault(tab, {}).setdefault(sid, {})[c['k']] = c['d']
    vych = ('<?php\n/**\n * superHERO – výchozí hodnoty ovládacích prvků.\n'
            ' * VYGENEROVÁNO nástrojem tools/hero_prvky.py – neupravuj ručně.\n'
            ' * hodnoty: výchozí hodnota, když prvek nemá uloženou hodnotu.\n'
            ' * textove: textová pole – prázdný text znamená „skrýt“, výchozí text dostane jen nově vložený element.\n */\n\n'
            "if (!defined('ABSPATH')) {\n    exit;\n}\n\nreturn [\n    'hodnoty' => [\n" + '\n'.join(hodnoty) + '\n    ],\n'
            "    'textove' => [\n" + '\n'.join(textove) + '\n    ],\n];\n')
    with open(os.path.join(ROOT, 'includes/hero-vychozi.php'), 'w', encoding='utf-8') as f:
        f.write(vych)
    # defaultProperties do element.php
    def arr(d, ind):
        pad = ' ' * ind
        items = []
        for k, v in d.items():
            if isinstance(v, dict):
                items.append(f"{pad}{q(k)} => [\n{arr(v, ind + 4)}\n{pad}]")
            else:
                items.append(f"{pad}{q(k)} => {php_val(v)}")
        return ',\n'.join(items)
    dp = '[\n' + arr(vlastnosti, 12) + '\n        ]'
    with open(os.path.join(ROOT, 'elements/Super_Hero/element.php'), 'r+', encoding='utf-8') as f:
        el = f.read().replace("    static function defaultProperties()\n    {\n        return false;", "    static function defaultProperties()\n    {\n        return " + dp + ";")
        f.seek(0); f.write(el); f.truncate()
    n = sum(len(c) for _, _, c in CONTENT + DESIGN)
    print(f'element.php a hero-vychozi.php vygenerovány ({n} prvků).')

if __name__ == '__main__':
    main()
