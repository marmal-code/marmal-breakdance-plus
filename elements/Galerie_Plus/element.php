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
        "rozlozeni",
        "Rozložení",
        [c(
        "typ",
        "Typ galerie",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [
            ['value' => 'mozaika', 'text' => 'Mozaika'],
            ['value' => 'slider', 'text' => 'Slider s miniaturami'],
        ]],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "mozaika",
        "Mozaika",
        [c(
        "vzor",
        "Vzor rozložení",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [
            // VZORY-START
            ['value' => '2-velka-3', 'text' => '2 sloupce – velká vlevo + 3 pod sebou (4)'],
            ['value' => '2-3-velka', 'text' => '2 sloupce – 3 pod sebou + velká vpravo (4)'],
            ['value' => '2-velka-2', 'text' => '2 sloupce – velká vlevo + 2 pod sebou (3)'],
            ['value' => '2-2-velka', 'text' => '2 sloupce – 2 pod sebou + velká vpravo (3)'],
            ['value' => '2-siroka-2', 'text' => '2 sloupce – široká nahoře + 2 dole (3)'],
            ['value' => '2-2-siroka', 'text' => '2 sloupce – 2 nahoře + široká dole (3)'],
            ['value' => 'velky-2', 'text' => '3 sloupce – velká vlevo + 2 pod sebou (3)'],
            ['value' => '3-2-velka', 'text' => '3 sloupce – 2 pod sebou + velká vpravo (3)'],
            ['value' => 'vysoky-vlevo', 'text' => '3 sloupce – vysoká vlevo + 4 (5)'],
            ['value' => '3-vysoka-2-vysoka', 'text' => '3 sloupce – vysoká + 2 + vysoká (4)'],
            ['value' => '3-velka-3', 'text' => '3 sloupce – velká vlevo + 3 pod sebou (4)'],
            ['value' => '3-siroka-3', 'text' => '3 sloupce – široká nahoře + 3 dole (4)'],
            ['value' => '3-mozaika', 'text' => '3 sloupce – mozaika 3 × 3 (7 fotek) (7)'],
            ['value' => 'vysoky-4-vysoky', 'text' => '4 sloupce – vysoká + 4 + vysoká (6)'],
            ['value' => 'velky-vlevo', 'text' => '4 sloupce – velká vlevo + 4 (5)'],
            ['value' => 'velky-vpravo', 'text' => '4 sloupce – 4 + velká vpravo (5)'],
            ['value' => '4-velka-stred', 'text' => '4 sloupce – velká uprostřed (5)'],
            ['value' => '4-velka-2-vysoke', 'text' => '4 sloupce – velká + 2 vysoké (3)'],
            ['value' => '4-vysoka-6', 'text' => '4 sloupce – vysoká vlevo + 6 (7)'],
            ['value' => '4-siroke-6', 'text' => '4 sloupce – široké v rozích + 4 (6)'],
            ['value' => 'mrizka', 'text' => 'Rovnoměrná mřížka'],
            // VZORY-END
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
        "vyska",
        "Výška galerie (jeden vzor)",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "vyska_radku",
        "Výška řádku (místo výšky galerie)",
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
      ), c(
        "pomer",
        "Poměr stran dlaždic (jen mřížka)",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => [
            ['value' => '1 / 1', 'text' => '1 : 1'],
            ['value' => '4 / 3', 'text' => '4 : 3'],
            ['value' => '3 / 2', 'text' => '3 : 2'],
            ['value' => '16 / 9', 'text' => '16 : 9'],
            ['value' => '3 / 4', 'text' => '3 : 4 (na výšku)'],
        ]],
        true,
        false,
        [],
      ), c(
        "vyrez",
        "Výřez fotky v dlaždici",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => [
            ['value' => 'center', 'text' => 'Střed'],
            ['value' => 'top', 'text' => 'Nahoře'],
            ['value' => 'bottom', 'text' => 'Dole'],
            ['value' => 'left', 'text' => 'Vlevo'],
            ['value' => 'right', 'text' => 'Vpravo'],
        ]],
        false,
        false,
        [],
      )],
        ['type' => 'section'],
        false,
        false,
        [],
      ), c(
        "slider",
        "Slider s miniaturami",
        [c(
        "pomer",
        "Poměr stran hlavní fotky",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => [
            ['value' => '21 / 9', 'text' => '21 : 9'],
            ['value' => '16 / 9', 'text' => '16 : 9'],
            ['value' => '3 / 2', 'text' => '3 : 2'],
            ['value' => '4 / 3', 'text' => '4 : 3'],
            ['value' => '1 / 1', 'text' => '1 : 1'],
        ]],
        true,
        false,
        [],
      ), c(
        "vyska",
        "Pevná výška hlavní fotky (místo poměru)",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "prizpusobeni",
        "Fotka v hlavním okně",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [
            ['value' => 'cover', 'text' => 'Vyplnit (oříznout)'],
            ['value' => 'contain', 'text' => 'Celá fotka (s okraji)'],
        ]],
        false,
        false,
        [],
      ), c(
        "pozadi",
        "Pozadí za fotkou",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "nahledy",
        "Počet viditelných miniatur",
        [],
        ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => 2, 'max' => 10, 'step' => 1]],
        true,
        false,
        [],
      ), c(
        "mezera",
        "Mezera miniatur",
        [],
        ['type' => 'unit', 'layout' => 'inline'],
        true,
        false,
        [],
      ), c(
        "pomer_nahledu",
        "Poměr stran miniatur",
        [],
        ['type' => 'dropdown', 'layout' => 'inline', 'items' => [
            ['value' => '16 / 9', 'text' => '16 : 9'],
            ['value' => '3 / 2', 'text' => '3 : 2'],
            ['value' => '4 / 3', 'text' => '4 : 3'],
            ['value' => '1 / 1', 'text' => '1 : 1'],
        ]],
        false,
        false,
        [],
      ), c(
        "aktivni",
        "Rámeček aktivní miniatury",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "bez_nahledu",
        "Skrýt miniatury",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "bez_sipek",
        "Skrýt šipky",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "sipky_pozadi",
        "Šipky – pozadí",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "sipky_barva",
        "Šipky – ikona",
        [],
        ['type' => 'color', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "pocitadlo",
        "Zobrazit počítadlo (3 / 12)",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "autoplay",
        "Automatické přehrávání (sekundy, 0 = vypnuto)",
        [],
        ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => 0, 'max' => 30, 'step' => 1]],
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
        "Po najetí: pohyb fotky",
        [],
        ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [
            ['value' => 'zoom', 'text' => 'Přiblížení'],
            ['value' => 'drift', 'text' => 'Pomalý posun (jako kamera)'],
            ['value' => 'none', 'text' => 'Žádný'],
        ]],
        false,
        false,
        [],
      ), c(
        "prejezd",
        "Po najetí: světelný přejezd",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "barvy",
        "Po najetí: barvy na dotek",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "zaostreni",
        "Po najetí: zaostření (ztlumit ostatní)",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), c(
        "popisek",
        "Po najetí: popisek zespodu",
        [],
        ['type' => 'toggle', 'layout' => 'inline'],
        false,
        false,
        [],
      ), getPresetSection(
        "EssentialElements\\typography_with_effects_and_align",
        "Popisek – písmo",
        "popisek_pismo",
        ['type' => 'popout']
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
        return ['content.obrazky', 'content.video', 'design.mozaika.mobil', 'design.rozlozeni', 'design.slider.prizpusobeni', 'design.slider.bez_nahledu', 'design.slider.bez_sipek', 'design.slider.pocitadlo', 'design.slider.autoplay'];
    }
}
