#!/usr/bin/env python3
"""
Generátor elementu „Marmal – Patička“ (elements/Paticka_Webu/, od Breakdance Plus 0.10.0).

Element vykresluje modul Patička (modules/paticka – element_html() v includes/render.php).
Všechny soubory elementu vytváří tento skript – NEUPRAVOVAT ručně ani v Element Studiu,
změna = úprava skriptu a nové spuštění:   python3 tools/paticka_element.py

  element.php  – ovládací prvky (seznamy CONTENT a DESIGN níže)
  css.twig     – vzhled z Design tabu (CSS)
  default.css  – kopie modules/paticka/assets/paticka.css (načte se i v builderu)
  ssr.php      – výstup přes modul Patička
  html.twig    – jen %%SSR%%

Jen ověřené typy prvků Breakdance 2.8: text, dropdown, toggle, number, unit (i s breakpointy), color,
section a presety typography_with_effects_and_align, spacing_padding_all, borders, spacing_margin_y.
Vzor: martin-dev-vyber-bytu/tools/okoli_element.py + unit_elements.py.
"""
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DIR = ROOT / 'elements' / 'Paticka_Webu'
CSS_MODULU = ROOT / 'modules' / 'paticka' / 'assets' / 'paticka.css'


def q(s):
    return '"' + s.replace('\\', '\\\\').replace('"', '\\"') + '"'


def c(slug, label, children=None, opts="['type' => 'section']", responsive=False):
    kids = ', '.join(children) if children else ''
    return (f'c(\n        {q(slug)},\n        {q(label)},\n        [{kids}],\n        {opts},\n'
            f'        {"true" if responsive else "false"},\n        false,\n        [],\n      )')


def color(slug, label):
    return c(slug, label, None, "['type' => 'color', 'layout' => 'inline']")


def unit(slug, label, responsive=False):
    return c(slug, label, None, "['type' => 'unit', 'layout' => 'inline']", responsive)


def toggle(slug, label):
    return c(slug, label, None, "['type' => 'toggle', 'layout' => 'vertical']")


def dropdown(slug, label, items):
    its = ', '.join("['value' => '%s', 'text' => '%s']" % (v, t) for v, t in items)
    return c(slug, label, None, "['type' => 'dropdown', 'layout' => 'vertical', 'items' => [" + its + "]]")


def preset(name, label, slug):
    return (f'getPresetSection(\n      "EssentialElements\\\\{name}",\n      {q(label)},\n      {q(slug)},\n'
            f"       ['type' => 'popout']\n     )")


def section(slug, label, children):
    return c(slug, label, children, "['type' => 'section']")


TYPO = 'typography_with_effects_and_align'

CONTENT = [
    c('paticka', 'Patička', [
        dropdown('rozlozeni', 'Rozložení', [
            ('radek', 'Copyright vlevo, odkazy a podpis vpravo'),
            ('tri', 'Copyright vlevo, odkazy uprostřed, podpis vpravo'),
            ('sloupec', 'Vše pod sebou, zarovnané vlevo'),
            ('stred', 'Vše pod sebou, na střed'),
        ]),
        toggle('skryt_copyright', 'Skrýt copyright'),
        toggle('skryt_drzitele', 'Copyright jen s roky (bez názvu)'),
        toggle('skryt_zasady', 'Skrýt odkaz Ochrana osobních údajů'),
        toggle('skryt_zasady_cookies', 'Skrýt odkaz Zásady cookies'),
        toggle('skryt_cookies', 'Skrýt odkaz Nastavení cookies'),
        toggle('skryt_autora', 'Skrýt podpis autora'),
        toggle('skryt_udaje', 'Skrýt údaje o podnikateli'),
        toggle('udaje_pod_sebou', 'Údaje o podnikateli pod sebou'),
    ], "['type' => 'section', 'layout' => 'vertical']"),
    c('rozvrzeni', 'Rozvržení – podrobně', [
        dropdown('pozice_copyright', 'Copyright', [('auto', 'Podle rozložení'), ('vlevo', 'Vlevo'), ('stred', 'Uprostřed'), ('vpravo', 'Vpravo'), ('pod', 'Na řádku pod')]),
        dropdown('pozice_odkazy', 'Odkazy (zásady, cookies)', [('auto', 'Podle rozložení'), ('vlevo', 'Vlevo'), ('stred', 'Uprostřed'), ('vpravo', 'Vpravo'), ('pod', 'Na řádku pod')]),
        dropdown('pozice_autor', 'Podpis autora', [('auto', 'Podle rozložení'), ('vlevo', 'Vlevo'), ('stred', 'Uprostřed'), ('vpravo', 'Vpravo'), ('pod', 'Na řádku pod')]),
        dropdown('pozice_udaje', 'Údaje o podnikateli', [('auto', 'Podle rozložení'), ('vlevo', 'Vlevo'), ('stred', 'Uprostřed'), ('vpravo', 'Vpravo'), ('pod', 'Na řádku pod')]),
        dropdown('zarovnani_pod', 'Zarovnání řádku pod', [('auto', 'Podle rozložení'), ('vlevo', 'Vlevo'), ('stred', 'Na střed'), ('vpravo', 'Vpravo')]),
        dropdown('skladat', 'Pod sebe od', [('mobil', 'Mobil (do 767 px)'), ('tablet', 'Tablet (do 1119 px)'), ('maly', 'Malý mobil (do 479 px)'), ('nikdy', 'Nikdy')]),
        dropdown('mobil_zarovnani', 'Zarovnání pod sebou', [('vlevo', 'Vlevo'), ('stred', 'Na střed')]),
    ], "['type' => 'section', 'layout' => 'vertical']"),
]

DESIGN = [
    c('container', 'Container', [
        unit('width', 'Width', True),
        c('background', 'Background', None, "['type' => 'color', 'layout' => 'inline', 'colorOptions' => ['type' => 'solidAndGradient']]"),
        preset('spacing_padding_all', 'Padding', 'padding'),
        preset('borders', 'Borders', 'borders'),
    ]),
    preset(TYPO, 'Typography', 'typography'),
    preset('spacing_margin_y', 'Spacing', 'spacing'),
    section('mezery', 'Mezery', [
        unit('sloupce', 'Mezera mezi copyrightem a odkazy', True),
        unit('radky', 'Mezera mezi řádky', True),
        unit('odkazy', 'Mezera mezi odkazy a podpisem', True),
    ]),
    section('copyright', 'Copyright', [
        preset(TYPO, 'Písmo', 'pismo'),
        color('barva', 'Barva'),
    ]),
    section('odkazy', 'Odkazy (všechny)', [
        preset(TYPO, 'Písmo odkazů', 'pismo'),
        color('barva', 'Barva odkazu'),
        color('barva_hover', 'Barva po najetí'),
        dropdown('podtrzeni', 'Podtržení', [
            ('vzdy', 'Vždy'),
            ('hover', 'Jen po najetí'),
            ('bez_hover', 'Vždy, po najetí zmizí'),
            ('nikdy', 'Nikdy'),
        ]),
        color('podtrzeni_barva', 'Barva podtržení'),
        color('podtrzeni_hover', 'Barva podtržení po najetí'),
        unit('podtrzeni_tloustka', 'Tloušťka podtržení'),
        unit('podtrzeni_odsazeni', 'Odsazení podtržení od textu'),
    ]),
    section('autor', 'Podpis autora', [
        preset(TYPO, 'Písmo textu', 'pismo'),
        color('barva', 'Barva textu'),
        preset(TYPO, 'Písmo odkazu (prázdné = Odkazy)', 'odkaz_pismo'),
        color('odkaz', 'Barva odkazu (prázdné = Odkazy)'),
        color('odkaz_hover', 'Barva odkazu po najetí (prázdné = Odkazy)'),
    ]),
    section('udaje', 'Údaje o podnikateli', [
        preset(TYPO, 'Písmo', 'pismo'),
        color('barva', 'Barva (prázdné = text patičky, tlumeně)'),
    ]),
    section('oddelovac', 'Oddělovač (tečka)', [
        color('barva', 'Barva tečky'),
        toggle('skryt', 'Bez teček (jen mezera)'),
    ]),
]

S = '%%SELECTOR%% .marmal-paticka'
ODKAZ = f'{S} a.marmal-paticka__odkaz,\n{S} button.marmal-paticka__odkaz'
AUTOR = f'{S} .marmal-paticka__autor a.marmal-paticka__odkaz'

CSS_TWIG = f'''{{# VYGENEROVÁNO skriptem tools/paticka_element.py – neupravovat ručně. #}}
%%SELECTOR%% {{
  width: {{{{ design.container.width.style }}}};
  {{{{ macros.backgroundColor(design.container.background) }}}}
  {{{{ macros.borders(design.container.borders) }}}}
  {{{{ macros.spacing_padding_all(design.container.padding) }}}}
  {{{{ macros.typography(design.typography, globalSettings)  }}}}
  {{{{ macros.spacing_margin_y(design.spacing) }}}}
}}

{{# Mezery – CSS proměnné modulu (výchozí hodnoty v default.css; prázdný prvek nic nemění) #}}
{S} {{
  {{% if design.mezery.sloupce.style %}}--marmal-paticka-gap-col: {{{{ design.mezery.sloupce.style }}}};{{% endif %}}
  {{% if design.mezery.radky.style %}}--marmal-paticka-gap-row: {{{{ design.mezery.radky.style }}}};{{% endif %}}
  {{% if design.mezery.odkazy.style %}}--marmal-paticka-gap-cast: 4px {{{{ design.mezery.odkazy.style }}}};{{% endif %}}
}}

{S} .marmal-paticka__copyright {{
  {{{{ macros.typography(design.copyright.pismo, globalSettings) }}}}
  {{% if design.copyright.barva %}}color: {{{{ design.copyright.barva }}}};{{% endif %}}
}}

{{# Odkazy – přes CSS proměnné (pravidla v default.css), takže nezáleží na pořadí stylů.
   Proměnné nastavené v .marmal-paticka__autor platí jen pro odkaz autora; prázdné = sekce Odkazy. #}}
{S} {{
  {{% if design.odkazy.barva %}}--marmal-paticka-odkaz: {{{{ design.odkazy.barva }}}};{{% endif %}}
  {{% if design.odkazy.barva_hover %}}--marmal-paticka-odkaz-hover: {{{{ design.odkazy.barva_hover }}}};{{% endif %}}
  {{% if design.odkazy.podtrzeni == 'hover' %}}--marmal-paticka-podtrzeni: none;--marmal-paticka-podtrzeni-hover: underline;{{% endif %}}
  {{% if design.odkazy.podtrzeni == 'bez_hover' %}}--marmal-paticka-podtrzeni: underline;--marmal-paticka-podtrzeni-hover: none;{{% endif %}}
  {{% if design.odkazy.podtrzeni == 'nikdy' %}}--marmal-paticka-podtrzeni: none;--marmal-paticka-podtrzeni-hover: none;{{% endif %}}
  {{% if design.odkazy.podtrzeni_barva %}}--marmal-paticka-podtrzeni-barva: {{{{ design.odkazy.podtrzeni_barva }}}};{{% endif %}}
  {{% if design.odkazy.podtrzeni_hover %}}--marmal-paticka-podtrzeni-barva-hover: {{{{ design.odkazy.podtrzeni_hover }}}};{{% endif %}}
  {{% if design.odkazy.podtrzeni_tloustka.style %}}--marmal-paticka-podtrzeni-tloustka: {{{{ design.odkazy.podtrzeni_tloustka.style }}}};--marmal-paticka-podtrzeni-tloustka-hover: {{{{ design.odkazy.podtrzeni_tloustka.style }}}};{{% endif %}}
  {{% if design.odkazy.podtrzeni_odsazeni.style %}}--marmal-paticka-podtrzeni-odsazeni: {{{{ design.odkazy.podtrzeni_odsazeni.style }}}};{{% endif %}}
}}
{ODKAZ} {{
  {{{{ macros.typography(design.odkazy.pismo, globalSettings) }}}}
}}

{S} .marmal-paticka__autor {{
  {{{{ macros.typography(design.autor.pismo, globalSettings) }}}}
  {{% if design.autor.barva %}}color: {{{{ design.autor.barva }}}};{{% endif %}}
  {{% if design.autor.odkaz %}}--marmal-paticka-odkaz: {{{{ design.autor.odkaz }}}};{{% endif %}}
  {{% if design.autor.odkaz_hover %}}--marmal-paticka-odkaz-hover: {{{{ design.autor.odkaz_hover }}}};{{% endif %}}
}}
{AUTOR} {{
  {{{{ macros.typography(design.autor.odkaz_pismo, globalSettings) }}}}
}}

{S} .marmal-paticka .marmal-paticka__firma {{
  {{{{ macros.typography(design.udaje.pismo, globalSettings) }}}}
  {{% if design.udaje.barva %}}color: {{{{ design.udaje.barva }}}};--marmal-paticka-udaje-opacity: 1;{{% endif %}}
}}

{S} .marmal-paticka__tecka {{
  {{% if design.oddelovac.barva %}}color: {{{{ design.oddelovac.barva }}}};--marmal-paticka-tecka: 1;{{% endif %}}
  {{% if design.oddelovac.skryt %}}display: none;{{% endif %}}
}}
{{% if design.oddelovac.skryt %}}
{S} .marmal-paticka__firma:not(.marmal-paticka__firma--pod),
{S} .marmal-paticka__odkazy {{
  display: inline-flex;
  flex-wrap: wrap;
  column-gap: 1em;
}}
{{% endif %}}
'''

SSR = r'''<?php
/**
 * Server-side render elementu „Marmal – Patička“ (MarmalElements\PatickaWebu).
 * VYGENEROVÁNO skriptem tools/paticka_element.py – neupravovat ručně.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MarmalElements\Paticka\element_html')) {
    // Modul Patička je vypnutý (MarMal → Moduly) – návštěvník nic nevidí, správce upozornění.
    if (current_user_can('manage_options')) {
        echo '<p class="marmal-paticka__hlaska">' . esc_html__('Modul Patička je vypnutý – zapni ho v MarMal → Moduly.', 'marmal-breakdance-plus') . '</p>';
    }
    return;
}

$marmal_pat_c = $propertiesData['content']['paticka'] ?? [];
$marmal_pat_r = $propertiesData['content']['rozvrzeni'] ?? [];
// Content → Patička + Content → Rozvržení – podrobně (od 0.11.0); hodnoty čistí element_html().
$marmal_pat_c = array_merge(is_array($marmal_pat_r) ? $marmal_pat_r : [], is_array($marmal_pat_c) ? $marmal_pat_c : []);
// element_html() vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr/esc_url).
echo \MarmalElements\Paticka\element_html(is_array($marmal_pat_c) ? $marmal_pat_c : []); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
'''

TEMPLATE = '''<?php

namespace MarmalElements;

use function Breakdance\\Elements\\c;
use function Breakdance\\Elements\\PresetSections\\getPresetSection;

/*
 * VYGENEROVÁNO skriptem tools/paticka_element.py – neupravovat ručně ani v Element Studiu.
 * Výstup elementu je v ssr.php (modul Patička – element_html()), vzhled v css.twig.
 */

\\Breakdance\\ElementStudio\\registerElementForEditing(
    "MarmalElements\\\\PatickaWebu",
    \\Breakdance\\Util\\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class PatickaWebu extends \\Breakdance\\Elements\\Element
{
    static function uiIcon()
    {
        return 'BracketsIcon';
    }

    static function tag()
    {
        return 'div';
    }

    static function tagOptions()
    {
        return ['div', 'section', 'aside'];
    }

    static function tagControlPath()
    {
        return false;
    }

    static function name()
    {
        return 'Marmal – Patička';
    }

    static function className()
    {
        return 'marmal-paticka-element';
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
        return [%DESIGN%];
    }

    static function contentControls()
    {
        return [%CONTENT%];
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
        return [['location' => 'outside-top', 'cssProperty' => 'margin-top', 'affectedPropertyPath' => 'design.spacing.margin_top.%%BREAKPOINT%%'], ['location' => 'outside-bottom', 'cssProperty' => 'margin-bottom', 'affectedPropertyPath' => 'design.spacing.margin_bottom.%%BREAKPOINT%%']];
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
        return ['breakdance', 'oxygen'];
    }

    static function order()
    {
        return 102;
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
        return ['optionsWork' => 'yes', 'dynamicBehaviorWorks' => 'yes', 'optionsGood' => 'yes'];
    }

    static function propertyPathsToWhitelistInFlatProps()
    {
        return false;
    }

    static function propertyPathsToSsrElementWhenValueChanges()
    {
        return ['content.paticka', 'content.rozvrzeni'];
    }
}
'''

if __name__ == '__main__':
    DIR.mkdir(parents=True, exist_ok=True)
    php = TEMPLATE.replace('%DESIGN%', ', '.join(DESIGN)).replace('%CONTENT%', ', '.join(CONTENT))
    default_css = ('/* VYGENEROVÁNO skriptem tools/paticka_element.py z modules/paticka/assets/paticka.css – neupravovat ručně. */\n'
                   '.marmal-paticka-element {\n  max-width: 100%;\n  width: 100%;\n}\n\n'
                   + CSS_MODULU.read_text(encoding='utf-8'))
    soubory = {
        'element.php': php,
        'css.twig': CSS_TWIG,
        'default.css': default_css,
        'ssr.php': SSR,
        'html.twig': '%%SSR%%',
    }
    for nazev, obsah in soubory.items():
        (DIR / nazev).write_text(obsah, encoding='utf-8')
    print('Zapsáno', DIR.relative_to(ROOT), '–', ', '.join(soubory))
