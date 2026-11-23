#!/usr/bin/env python3
"""
Generátor elementu „Marmal – Drobečková navigace“ (elements/Drobecky_Navigace/, od Breakdance Plus 0.19.0).

Element vykresluje modul SEO (modules/seo – drobecky_element_html() v includes/drobecky.php).
Všechny soubory elementu vytváří tento skript – NEUPRAVOVAT ručně ani v Element Studiu,
změna = úprava skriptu a nové spuštění:   python3 tools/drobecky_element.py

  element.php  – ovládací prvky (seznamy CONTENT a DESIGN níže)
  css.twig     – vzhled z Design tabu (jen CSS proměnné a makra presetů)
  default.css  – kopie modules/seo/assets/drobecky.css (načte se i v builderu)
  ssr.php      – výstup přes modul SEO (v builderu ukázková cesta)
  html.twig    – jen %%SSR%%

Jen ověřené typy prvků Breakdance 2.8: text, dropdown, toggle, number, unit (i s breakpointy), color, icon,
section a presety typography_with_effects_and_align, spacing_padding_all, borders, spacing_margin_y.
Vzor: tools/paticka_element.py.
"""
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DIR = ROOT / 'elements' / 'Drobecky_Navigace'
CSS_MODULU = ROOT / 'modules' / 'seo' / 'assets' / 'drobecky.css'


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



def text_ctrl(slug, label):
    return c(slug, label, None, "['type' => 'text', 'layout' => 'vertical']")


def number(slug, label, mn, mx, step=1):
    return c(slug, label, None, "['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => %d, 'max' => %d, 'step' => %d]]" % (mn, mx, step))


def icon(slug, label):
    return c(slug, label, None, "['type' => 'icon', 'layout' => 'vertical']")


TYPO = 'typography_with_effects_and_align'
ODDELOVACE = [('auto', 'Podle nastavení SEO'), ('›', '›'), ('»', '»'), ('/', '/'), ('|', '|'), ('·', '·'), ('–', '–'), ('>', '>'), ('→', '→')]

CONTENT = [
    c('drobecky', 'Drobečková navigace', [
        text_ctrl('uvod_text', 'Text první položky (prázdné = z nastavení SEO, výchozí „Úvod“)'),
        dropdown('uvod_zobrazit', 'První položka', [('text', 'Text'), ('ikona', 'Jen ikona'), ('ikona_text', 'Ikona a text')]),
        icon('uvod_ikona', 'Ikona první položky (prázdné = domeček)'),
        toggle('skryt_uvod', 'Skrýt první položku'),
        toggle('skryt_aktualni', 'Skrýt aktuální stránku'),
        dropdown('oddelovac', 'Oddělovač', ODDELOVACE),
        icon('oddelovac_ikona', 'Ikona oddělovače (místo znaku)'),
        dropdown('radky', 'Dlouhá cesta', [('zalamovat', 'Zalomit na další řádek'), ('jeden_radek', 'Jeden řádek, zkrátit aktuální stránku')]),
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
    section('rozlozeni', 'Rozložení', [
        dropdown('zarovnani', 'Zarovnání', [('vlevo', 'Vlevo'), ('stred', 'Na střed'), ('vpravo', 'Vpravo')]),
        unit('mezera', 'Mezera mezi položkou a oddělovačem', True),
        unit('radky', 'Mezera mezi řádky', True),
    ]),
    section('odkazy', 'Odkazy', [
        preset(TYPO, 'Písmo odkazů', 'pismo'),
        color('barva', 'Barva odkazu'),
        color('barva_hover', 'Barva po najetí'),
        dropdown('podtrzeni', 'Podtržení', [('hover', 'Jen po najetí (výchozí)'), ('vzdy', 'Vždy'), ('bez_hover', 'Vždy, po najetí zmizí'), ('nikdy', 'Nikdy')]),
        color('podtrzeni_barva', 'Barva podtržení'),
        color('podtrzeni_hover', 'Barva podtržení po najetí'),
        unit('podtrzeni_tloustka', 'Tloušťka podtržení'),
        unit('podtrzeni_odsazeni', 'Odsazení podtržení od textu'),
        color('pozadi', 'Pozadí odkazu'),
        color('pozadi_hover', 'Pozadí odkazu po najetí'),
        preset('spacing_padding_all', 'Vnitřní odsazení odkazu', 'odsazeni'),
        unit('zaobleni', 'Zaoblení odkazu'),
    ]),
    section('uvod', 'První položka (Úvod)', [
        preset(TYPO, 'Písmo (prázdné = Odkazy)', 'pismo'),
        color('barva', 'Barva (prázdné = Odkazy)'),
        color('barva_hover', 'Barva po najetí (prázdné = Odkazy)'),
        unit('ikona_velikost', 'Velikost ikony'),
        color('ikona_barva', 'Barva ikony (prázdné = barva textu)'),
    ]),
    section('aktualni', 'Aktuální stránka', [
        preset(TYPO, 'Písmo', 'pismo'),
        color('barva', 'Barva'),
        number('pruhlednost', 'Krytí v % (prázdné = 75)', 1, 100),
        color('pozadi', 'Pozadí'),
        preset('spacing_padding_all', 'Vnitřní odsazení', 'odsazeni'),
        unit('zaobleni', 'Zaoblení'),
    ]),
    section('oddelovac', 'Oddělovač', [
        color('barva', 'Barva'),
        unit('velikost', 'Velikost'),
        number('pruhlednost', 'Krytí v % (prázdné = 60)', 1, 100),
    ]),
]

S = '%%SELECTOR%% .marmal-drobecky'
ODKAZ = f'{S} a.marmal-drobecky__odkaz.marmal-drobecky__odkaz'
UVOD = f'{S} .marmal-drobecky__polozka--uvod'

CSS_TWIG = f'''{{# VYGENEROVÁNO skriptem tools/drobecky_element.py – neupravovat ručně. #}}
%%SELECTOR%% {{
  width: {{{{ design.container.width.style }}}};
  {{{{ macros.backgroundColor(design.container.background) }}}}
  {{{{ macros.borders(design.container.borders) }}}}
  {{{{ macros.spacing_padding_all(design.container.padding) }}}}
  {{{{ macros.typography(design.typography, globalSettings)  }}}}
  {{{{ macros.spacing_margin_y(design.spacing) }}}}
}}

{{# Rozložení, odkazy, aktuální stránka a oddělovač – CSS proměnné (výchozí hodnoty v default.css; prázdný prvek nic nemění) #}}
{S} {{
  {{% if design.rozlozeni.zarovnani == 'stred' %}}--marmal-drobecky-zarovnani: center;{{% endif %}}
  {{% if design.rozlozeni.zarovnani == 'vpravo' %}}--marmal-drobecky-zarovnani: flex-end;{{% endif %}}
  {{% if design.rozlozeni.mezera.style %}}--marmal-drobecky-gap: {{{{ design.rozlozeni.mezera.style }}}};{{% endif %}}
  {{% if design.rozlozeni.radky.style %}}--marmal-drobecky-gap-row: {{{{ design.rozlozeni.radky.style }}}};{{% endif %}}
  {{% if design.odkazy.barva %}}--marmal-drobecky-odkaz: {{{{ design.odkazy.barva }}}};{{% endif %}}
  {{% if design.odkazy.barva_hover %}}--marmal-drobecky-odkaz-hover: {{{{ design.odkazy.barva_hover }}}};{{% endif %}}
  {{% if design.odkazy.podtrzeni == 'vzdy' %}}--marmal-drobecky-podtrzeni: underline;--marmal-drobecky-podtrzeni-hover: underline;{{% endif %}}
  {{% if design.odkazy.podtrzeni == 'bez_hover' %}}--marmal-drobecky-podtrzeni: underline;--marmal-drobecky-podtrzeni-hover: none;{{% endif %}}
  {{% if design.odkazy.podtrzeni == 'nikdy' %}}--marmal-drobecky-podtrzeni: none;--marmal-drobecky-podtrzeni-hover: none;{{% endif %}}
  {{% if design.odkazy.podtrzeni_barva %}}--marmal-drobecky-podtrzeni-barva: {{{{ design.odkazy.podtrzeni_barva }}}};{{% endif %}}
  {{% if design.odkazy.podtrzeni_hover %}}--marmal-drobecky-podtrzeni-barva-hover: {{{{ design.odkazy.podtrzeni_hover }}}};{{% endif %}}
  {{% if design.odkazy.podtrzeni_tloustka.style %}}--marmal-drobecky-podtrzeni-tloustka: {{{{ design.odkazy.podtrzeni_tloustka.style }}}};{{% endif %}}
  {{% if design.odkazy.podtrzeni_odsazeni.style %}}--marmal-drobecky-podtrzeni-odsazeni: {{{{ design.odkazy.podtrzeni_odsazeni.style }}}};{{% endif %}}
  {{% if design.odkazy.pozadi %}}--marmal-drobecky-odkaz-bg: {{{{ design.odkazy.pozadi }}}};{{% endif %}}
  {{% if design.odkazy.pozadi_hover %}}--marmal-drobecky-odkaz-bg-hover: {{{{ design.odkazy.pozadi_hover }}}};{{% endif %}}
  {{% if design.odkazy.zaobleni.style %}}--marmal-drobecky-odkaz-radius: {{{{ design.odkazy.zaobleni.style }}}};{{% endif %}}
  {{% if design.aktualni.barva %}}--marmal-drobecky-aktualni: {{{{ design.aktualni.barva }}}};{{% endif %}}
  {{% if design.aktualni.pruhlednost %}}--marmal-drobecky-aktualni-opacity: {{{{ design.aktualni.pruhlednost / 100 }}}};{{% endif %}}
  {{% if design.aktualni.pozadi %}}--marmal-drobecky-aktualni-bg: {{{{ design.aktualni.pozadi }}}};{{% endif %}}
  {{% if design.aktualni.zaobleni.style %}}--marmal-drobecky-aktualni-radius: {{{{ design.aktualni.zaobleni.style }}}};{{% endif %}}
  {{% if design.oddelovac.barva %}}--marmal-drobecky-odd-barva: {{{{ design.oddelovac.barva }}}};{{% endif %}}
  {{% if design.oddelovac.velikost.style %}}--marmal-drobecky-odd-velikost: {{{{ design.oddelovac.velikost.style }}}};{{% endif %}}
  {{% if design.oddelovac.pruhlednost %}}--marmal-drobecky-odd-opacity: {{{{ design.oddelovac.pruhlednost / 100 }}}};{{% endif %}}
}}
{ODKAZ} {{
  {{{{ macros.typography(design.odkazy.pismo, globalSettings) }}}}
  {{{{ macros.spacing_padding_all(design.odkazy.odsazeni) }}}}
}}

{{# První položka – proměnné nastavené na její <li> platí jen pro ni; prázdné = sekce Odkazy #}}
{UVOD} {{
  {{% if design.uvod.barva %}}--marmal-drobecky-odkaz: {{{{ design.uvod.barva }}}};--marmal-drobecky-aktualni: {{{{ design.uvod.barva }}}};{{% endif %}}
  {{% if design.uvod.barva_hover %}}--marmal-drobecky-odkaz-hover: {{{{ design.uvod.barva_hover }}}};{{% endif %}}
  {{% if design.uvod.ikona_velikost.style %}}--marmal-drobecky-ikona: {{{{ design.uvod.ikona_velikost.style }}}};{{% endif %}}
  {{% if design.uvod.ikona_barva %}}--marmal-drobecky-ikona-barva: {{{{ design.uvod.ikona_barva }}}};{{% endif %}}
}}
{UVOD} a.marmal-drobecky__odkaz.marmal-drobecky__odkaz,
{UVOD} .marmal-drobecky__aktualni {{
  {{{{ macros.typography(design.uvod.pismo, globalSettings) }}}}
}}

{S} .marmal-drobecky__aktualni {{
  {{{{ macros.typography(design.aktualni.pismo, globalSettings) }}}}
  {{{{ macros.spacing_padding_all(design.aktualni.odsazeni) }}}}
}}
'''

SSR = r'''<?php
/**
 * Server-side render elementu „Marmal – Drobečková navigace“ (MarmalElements\DrobeckyNavigace).
 * VYGENEROVÁNO skriptem tools/drobecky_element.py – neupravovat ručně.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MarmalElements\Seo\drobecky_element_html')) {
    // Modul SEO je vypnutý (MarMal → Moduly) – návštěvník nic nevidí, správce upozornění.
    if (current_user_can('manage_options')) {
        echo '<p class="marmal-drobecky__hlaska">' . esc_html__('Modul SEO a sdílení je vypnutý – zapni ho v MarMal → Moduly.', 'marmal-breakdance-plus') . '</p>';
    }
    return;
}

// V builderu (náhled přes AJAX / REST) nemá šablona vlastní stránku – element ukáže ukázkovou cestu.
// phpcs:ignore WordPress.Security.NonceVerification -- jen příznak builderu, nic se neukládá
$marmal_dr_builder = wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || is_admin() || isset($_GET['breakdance']) || isset($_GET['breakdance_iframe']);
$marmal_dr_c       = $propertiesData['content']['drobecky'] ?? [];
// drobecky_element_html() vrací hotové HTML – texty esc_html, odkazy esc_url, ikony přes wp_kses.
echo \MarmalElements\Seo\drobecky_element_html(is_array($marmal_dr_c) ? $marmal_dr_c : [], $marmal_dr_builder); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
'''

TEMPLATE = '''<?php

namespace MarmalElements;

use function Breakdance\\Elements\\c;
use function Breakdance\\Elements\\PresetSections\\getPresetSection;

/*
 * VYGENEROVÁNO skriptem tools/drobecky_element.py – neupravovat ručně ani v Element Studiu.
 * Výstup elementu je v ssr.php (modul SEO – drobecky_element_html()), vzhled v css.twig.
 */

\\Breakdance\\ElementStudio\\registerElementForEditing(
    "MarmalElements\\\\DrobeckyNavigace",
    \\Breakdance\\Util\\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class DrobeckyNavigace extends \\Breakdance\\Elements\\Element
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
        return ['div', 'section'];
    }

    static function tagControlPath()
    {
        return false;
    }

    static function name()
    {
        return 'Marmal – Drobečková navigace';
    }

    static function className()
    {
        return 'marmal-drobecky-element';
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
        return 103;
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
        return ['content.drobecky'];
    }
}
'''

if __name__ == '__main__':
    DIR.mkdir(parents=True, exist_ok=True)
    php = TEMPLATE.replace('%DESIGN%', ', '.join(DESIGN)).replace('%CONTENT%', ', '.join(CONTENT))
    default_css = ('/* VYGENEROVÁNO skriptem tools/drobecky_element.py z modules/seo/assets/drobecky.css – neupravovat ručně. */\n'
                   '.marmal-drobecky-element {\n  max-width: 100%;\n  min-width: 0;\n}\n\n'
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
