<?php

namespace MarmalElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;

/*
 * VYGENEROVÁNO skriptem tools/drobecky_element.py – neupravovat ručně ani v Element Studiu.
 * Výstup elementu je v ssr.php (modul SEO – drobecky_element_html()), vzhled v css.twig.
 */

\Breakdance\ElementStudio\registerElementForEditing(
    "MarmalElements\\DrobeckyNavigace",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class DrobeckyNavigace extends \Breakdance\Elements\Element
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
        "rozlozeni",
        "Rozložení",
        [c(
        "zarovnani",
        "Zarovnání",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'vlevo', 'text' => 'Vlevo'], ['value' => 'stred', 'text' => 'Na střed'], ['value' => 'vpravo', 'text' => 'Vpravo']]],
        false,
        false,
        [],
      ), c(
        "mezera",
        "Mezera mezi položkou a oddělovačem",
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
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "odkazy",
        "Odkazy",
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
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'hover', 'text' => 'Jen po najetí (výchozí)'], ['value' => 'vzdy', 'text' => 'Vždy'], ['value' => 'bez_hover', 'text' => 'Vždy, po najetí zmizí'], ['value' => 'nikdy', 'text' => 'Nikdy']]],
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
      ), c(
        "pozadi",
        "Pozadí odkazu",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "pozadi_hover",
        "Pozadí odkazu po najetí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\spacing_padding_all",
      "Vnitřní odsazení odkazu",
      "odsazeni",
       ['type' => 'popout']
     ), c(
        "zaobleni",
        "Zaoblení odkazu",
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
        "uvod",
        "První položka (Úvod)",
        [getPresetSection(
      "EssentialElements\\typography_with_effects_and_align",
      "Písmo (prázdné = Odkazy)",
      "pismo",
       ['type' => 'popout']
     ), c(
        "barva",
        "Barva (prázdné = Odkazy)",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "barva_hover",
        "Barva po najetí (prázdné = Odkazy)",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "ikona_velikost",
        "Velikost ikony",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "ikona_barva",
        "Barva ikony (prázdné = barva textu)",
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
        "aktualni",
        "Aktuální stránka",
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
      ), c(
        "pruhlednost",
        "Krytí v % (prázdné = 75)",
        [],
        ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => 1, 'max' => 100, 'step' => 1]],
        false,
        false,
        [],
      ), c(
        "pozadi",
        "Pozadí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), getPresetSection(
      "EssentialElements\\spacing_padding_all",
      "Vnitřní odsazení",
      "odsazeni",
       ['type' => 'popout']
     ), c(
        "zaobleni",
        "Zaoblení",
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
        "oddelovac",
        "Oddělovač",
        [c(
        "barva",
        "Barva",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "velikost",
        "Velikost",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "pruhlednost",
        "Krytí v % (prázdné = 60)",
        [],
        ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => 1, 'max' => 100, 'step' => 1]],
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
        "drobecky",
        "Drobečková navigace",
        [c(
        "uvod_text",
        "Text první položky (prázdné = z nastavení SEO, výchozí „Úvod“)",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "uvod_zobrazit",
        "První položka",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'text', 'text' => 'Text'], ['value' => 'ikona', 'text' => 'Jen ikona'], ['value' => 'ikona_text', 'text' => 'Ikona a text']]],
        false,
        false,
        [],
      ), c(
        "uvod_ikona",
        "Ikona první položky (prázdné = domeček)",
        [],
        ['type' => 'icon', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "skryt_uvod",
        "Skrýt první položku",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "skryt_aktualni",
        "Skrýt aktuální stránku",
        [],
        ['type' => 'toggle', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "oddelovac",
        "Oddělovač",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'auto', 'text' => 'Podle nastavení SEO'], ['value' => '›', 'text' => '›'], ['value' => '»', 'text' => '»'], ['value' => '/', 'text' => '/'], ['value' => '|', 'text' => '|'], ['value' => '·', 'text' => '·'], ['value' => '–', 'text' => '–'], ['value' => '>', 'text' => '>'], ['value' => '→', 'text' => '→']]],
        false,
        false,
        [],
      ), c(
        "oddelovac_ikona",
        "Ikona oddělovače (místo znaku)",
        [],
        ['type' => 'icon', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "radky",
        "Dlouhá cesta",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zalamovat', 'text' => 'Zalomit na další řádek'], ['value' => 'jeden_radek', 'text' => 'Jeden řádek, zkrátit aktuální stránku']]],
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
