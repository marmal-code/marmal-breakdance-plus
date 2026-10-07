<?php

namespace MarmalElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "MarmalElements\\GaleriePlus",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class GaleriePlus extends \Breakdance\Elements\Element
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
        return ['div', 'section', 'figure'];
    }

    static function tagControlPath()
    {
        return false;
    }

    static function name()
    {
        return 'Galerie Plus';
    }

    static function className()
    {
        return 'marmal-gallery-element';
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
        "mozaika",
        "Mozaika",
        [c(
        "vzor",
        "Vzor rozložení",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [
            ['value' => 'vysoky-4-vysoky', 'text' => 'Vysoký – 4 – vysoký (6)'],
            ['value' => 'velky-vlevo', 'text' => 'Velký vlevo + 4 (5)'],
            ['value' => 'velky-vpravo', 'text' => 'Velký vpravo + 4 (5)'],
            ['value' => 'vysoky-vlevo', 'text' => 'Vysoký vlevo + 4 (5)'],
            ['value' => 'velky-2', 'text' => 'Velký + 2 (3)'],
            ['value' => 'mrizka', 'text' => 'Rovnoměrná mřížka'],
        ]],
        true,
        false,
        [],
      ), c(
        "mobil",
        "Na mobilu",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [
            ['value' => 'auto', 'text' => 'Automaticky: mřížka 2 sloupce'],
            ['value' => 'rucne', 'text' => 'Podle nastavení breakpointů'],
        ]],
        false,
        false,
        [],
      ), c(
        "sloupce",
        "Počet sloupců (jen mřížka)",
        [],
        ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => 1, 'max' => 8, 'step' => 1]],
        true,
        false,
        [],
      ), c(
        "opakovani",
        "Počet opakování vzoru (0 = vše)",
        [],
        ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => 0, 'max' => 20, 'step' => 1]],
        true,
        false,
        [],
      ), c(
        "vyska_radku",
        "Výška řádku",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "mezera",
        "Mezera",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "zaobleni",
        "Zaoblení rohů",
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
        "dlazdice",
        "Dlaždice",
        [c(
        "hover",
        "Efekt po najetí",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => [
            ['value' => 'zoom', 'text' => 'Přiblížení'],
            ['value' => 'none', 'text' => 'Žádný'],
        ]],
        false,
        false,
        [],
      ), c(
        "prekryv",
        "„+N fotek“ – překryv",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "prekryv_text",
        "„+N fotek“ – text",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "video_pozadi",
        "Video dlaždice – pozadí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "video_barva",
        "Video dlaždice – text a ikona",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Video dlaždice – písmo",
        "video_pismo",
        ['type' => 'popout']
      ), c(
        "lightbox_pozadi",
        "Lightbox – pozadí",
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
      ), getPresetSection(
      "EssentialElements\\spacing_margin_y",
      "Spacing",
      "spacing",
       ['type' => 'popout']
     )];
    }

    static function contentControls()
    {
        return [c(
        "obrazky",
        "Obrázky",
        [c(
        "zdroj",
        "Zdroj obrázků",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [
            ['value' => 'rucne', 'text' => 'Vybrat z knihovny médií'],
            ['value' => 'acf', 'text' => 'ACF pole (galerie)'],
        ]],
        false,
        false,
        [],
      ), c(
        "vyber",
        "Obrázky z knihovny",
        [],
        ['type' => 'wpmedia', 'layout' => 'vertical', 'mediaOptions' => ['acceptedFileTypes' => ['image'], 'multiple' => true]],
        false,
        false,
        [],
      ), c(
        "acf_pole",
        "Název ACF pole (galerie)",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "velikost",
        "Velikost náhledů",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => [
            ['value' => 'medium_large', 'text' => 'Střední (768 px)'],
            ['value' => 'large', 'text' => 'Velká (1024 px)'],
            ['value' => 'full', 'text' => 'Originál'],
        ]],
        false,
        false,
        [],
      ), c(
        "bez_lightboxu",
        "Vypnout lightbox",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "ladeni",
        "Ladění (jen pro správce)",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      )],
        ['type' => 'section', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "video",
        "Video dlaždice",
        [c(
        "url",
        "Odkaz na video (YouTube, Vimeo, MP4)",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "text",
        "Text dlaždice",
        [],
        ['type' => 'text', 'layout' => 'vertical'],
        false,
        false,
        [],
      ), c(
        "pozice",
        "Pozice v galerii (prázdné = 6.)",
        [],
        ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => 1, 'max' => 50, 'step' => 1]],
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
        return 100;
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
        return ['content.obrazky', 'content.video'];
    }
}
