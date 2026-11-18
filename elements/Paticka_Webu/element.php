<?php

namespace MarmalElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;

/*
 * VYGENEROVÁNO skriptem tools/paticka_element.py – neupravovat ručně ani v Element Studiu.
 * Výstup elementu je v ssr.php (modul Patička – element_html()), vzhled v css.twig.
 */

\Breakdance\ElementStudio\registerElementForEditing(
    "MarmalElements\\PatickaWebu",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class PatickaWebu extends \Breakdance\Elements\Element
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
        return [c(
        "container",
        "Container",
        [c(
        "width",
        "Width",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "background",
        "Background",
        [],
        ['type' => 'color', 'layout' => 'inline', 'colorOptions' => ['type' => 'solidAndGradient']],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\spacing_padding_all",
      "Padding",
      "padding",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\borders",
      "Borders",
      "borders",
       ['type' => 'popout']
     )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\typography_with_effects_and_align",
      "Typography",
      "typography",
       ['type' => 'popout']
     ), getPresetSection(
      "EssentialElements\\spacing_margin_y",
      "Spacing",
      "spacing",
       ['type' => 'popout']
     ), c(
        "mezery",
        "Mezery",
        [c(
        "sloupce",
        "Mezera mezi copyrightem a odkazy",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "radky",
        "Mezera mezi řádky",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "odkazy",
        "Mezera mezi odkazy a podpisem",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "copyright",
        "Copyright",
        [getPresetSection(
      "EssentialElements\\typography_with_effects_and_align",
      "Písmo",
      "pismo",
       ['type' => 'popout']
     ), c(
        "barva",
        "Barva",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "odkazy",
        "Odkazy (všechny)",
        [getPresetSection(
      "EssentialElements\\typography_with_effects_and_align",
      "Písmo odkazů",
      "pismo",
       ['type' => 'popout']
     ), c(
        "barva",
        "Barva odkazu",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "barva_hover",
        "Barva po najetí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "podtrzeni",
        "Podtržení",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'vzdy', 'text' => 'Vždy'], ['value' => 'hover', 'text' => 'Jen po najetí'], ['value' => 'bez_hover', 'text' => 'Vždy, po najetí zmizí'], ['value' => 'nikdy', 'text' => 'Nikdy']]],
        false,
        false,
        [],
      ), c(
        "podtrzeni_barva",
        "Barva podtržení",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "podtrzeni_hover",
        "Barva podtržení po najetí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "podtrzeni_tloustka",
        "Tloušťka podtržení",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "podtrzeni_odsazeni",
        "Odsazení podtržení od textu",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "autor",
        "Podpis autora",
        [getPresetSection(
      "EssentialElements\\typography_with_effects_and_align",
      "Písmo textu",
      "pismo",
       ['type' => 'popout']
     ), c(
        "barva",
        "Barva textu",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\typography_with_effects_and_align",
      "Písmo odkazu (prázdné = Odkazy)",
      "odkaz_pismo",
       ['type' => 'popout']
     ), c(
        "odkaz",
        "Barva odkazu (prázdné = Odkazy)",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "odkaz_hover",
        "Barva odkazu po najetí (prázdné = Odkazy)",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "udaje",
        "Údaje o podnikateli",
        [getPresetSection(
      "EssentialElements\\typography_with_effects_and_align",
      "Písmo",
      "pismo",
       ['type' => 'popout']
     ), c(
        "barva",
        "Barva (prázdné = text patičky, tlumeně)",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "oddelovac",
        "Oddělovač (tečka)",
        [c(
        "barva",
        "Barva tečky",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "skryt",
        "Bez teček (jen mezera)",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      )];
    }

    static function contentControls()
    {
        return [c(
        "paticka",
        "Patička",
        [c(
        "rozlozeni",
        "Rozložení",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'radek', 'text' => 'Copyright vlevo, odkazy a podpis vpravo'], ['value' => 'sloupec', 'text' => 'Vše pod sebou, zarovnané vlevo'], ['value' => 'stred', 'text' => 'Vše pod sebou, na střed']]],
        false,
        false,
        [],
      ), c(
        "skryt_copyright",
        "Skrýt copyright",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "skryt_drzitele",
        "Copyright jen s roky (bez názvu)",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "skryt_zasady",
        "Skrýt odkaz Ochrana osobních údajů",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "skryt_cookies",
        "Skrýt odkaz Nastavení cookies",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "skryt_autora",
        "Skrýt podpis autora",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "skryt_udaje",
        "Skrýt údaje o podnikateli",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "udaje_pod_sebou",
        "Údaje o podnikateli pod sebou",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      )],
        ['type' => 'section', 'layout' => 'vertical'],
        false,
        false,
        [],
      )];
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
        return ['content.paticka'];
    }
}
