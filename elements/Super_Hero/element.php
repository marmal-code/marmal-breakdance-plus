<?php
/**
 * superHERO (MarmalElements\SuperHero) – úvodní sekce stránky.
 *
 * VYGENEROVÁNO nástrojem tools/hero_prvky.py – ovládací prvky neupravuj ručně,
 * uprav seznam v nástroji a spusť ho znovu. Logika vykreslení je v includes/hero.php.
 * Slug MarmalElements\SuperHero a cesty prvků se už nesmí měnit (rozbily by se vložené elementy).
 */

namespace MarmalElements;

use function Breakdance\Elements\c;
use function Breakdance\Elements\PresetSections\getPresetSection;


\Breakdance\ElementStudio\registerElementForEditing(
    "MarmalElements\\SuperHero",
    \Breakdance\Util\getdirectoryPathRelativeToPluginFolder(__DIR__)
);

class SuperHero extends \Breakdance\Elements\Element
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
        return [
            'content' => [
                'zdroj' => [
                    'rezim' => 'rucne',
                    'nadpis_pole' => 'post_title',
                    'nadpis_acf' => 'hero_nadpis',
                    'text_pole' => 'post_excerpt',
                    'text_acf' => 'hero_text',
                    'obrazek_pole' => 'featured_image',
                    'obrazek_acf' => 'hero_obrazek'
                ],
                'obsah' => [
                    'odznak_styl' => 'tecka',
                    'eyebrow' => 'Nadnadpis',
                    'nadpis' => 'Hlavní sdělení, které *zaujme*',
                    'nadpis_tag' => 'h1',
                    'text' => 'Jedna až dvě věty o tom, co nabízíte a proč právě vy.'
                ],
                'tlacitka' => [
                    'btn1_text' => 'Nezávazná poptávka',
                    'btn1_url' => '#kontakt',
                    'btn1_nove_okno' => false,
                    'btn2_text' => 'Naše reference',
                    'btn2_url' => '#reference',
                    'btn2_nove_okno' => false
                ],
                'video' => [
                    'spoustec' => 'zadny'
                ],
                'duvera' => [
                    'typ' => 'zadna',
                    'hodnoceni_cislo' => '4,9',
                    'hodnoceni_text' => 'z 87 recenzí na Googlu',
                    'avatary_text' => '+120 spokojených zákazníků',
                    'avatary_pocet' => 4,
                    'loga_nadpis' => 'Pracovali jsme pro',
                    'loga_pozice' => 'dole',
                    'loga_pohyb' => false
                ],
                'oteviraci' => [
                    'zapnout' => false,
                    'dny' => 'po-pa',
                    'od' => '7:00',
                    'do' => '17:00',
                    'pozice' => 'pod'
                ],
                'vyhody' => [
                    'polozka1' => 'Výhoda číslo jedna',
                    'polozka2' => 'Výhoda číslo dva',
                    'polozka3' => 'Výhoda číslo tři'
                ],
                'medium' => [
                    'typ' => 'obrazek'
                ],
                'pozadi' => [
                    'typ' => 'barva'
                ],
                'slider' => [
                    'rezim' => 'vypnuto'
                ],
                'doplnky' => [
                    'sipka' => 'zadna',
                    'header' => false,
                    'pauza' => true,
                    'sticky' => false,
                    'sticky_text' => 'Zavolat',
                    'sticky_url' => 'tel:+420'
                ]
            ],
            'design' => [
                'rozlozeni' => [
                    'varianta' => 'split',
                    'pomer' => '1.2fr 1fr',
                    'zarovnani' => 'center',
                    'min_vyska' => '85',
                    'sirka_elementu' => 'full',
                    'z_webu' => true
                ],
                'pozadi' => [
                    'prekryv_sila' => 60,
                    'prekryv_styl' => 'prechod',
                    'barva_textu' => 'auto'
                ],
                'typografie' => [
                    'velikost' => 'L',
                    'nadpis_max' => 16,
                    'eyebrow_styl' => 'kapitalky'
                ],
                'tlacitka' => [
                    'btn1_styl' => 'primary',
                    'btn2_styl' => 'outline',
                    'z_presetu' => true,
                    'tvar' => 'zaobleny',
                    'velikost' => 'M'
                ],
                'vyhody' => [
                    'styl' => 'seznam',
                    'ikona_styl' => 'kruh'
                ],
                'medium' => [
                    'maska' => 'zadna',
                    'pomer' => '4/3',
                    'stin' => true,
                    'dekor' => 'zadny'
                ],
                'slider' => [
                    'interval' => 6,
                    'prechod' => 'prolnuti',
                    'navigace' => 'tecky',
                    'pauza' => true
                ],
                'vykon' => [
                    'lcp' => true,
                    'setrit_data' => true
                ],
                'styl' => [
                    'preset' => 'vlastni'
                ],
                'animace' => [
                    'vstup' => 'nahoru',
                    'postupne' => true,
                    'rychlost' => 'normal',
                    'medium_vstup' => 'stejne'
                ],
                'nadpis_efekt' => [
                    'zvyrazneni' => 'barva',
                    'rotace' => 'vyjeti'
                ],
                'tlacitka_efekt' => [
                    'hover' => 'sipka',
                    'upoutat' => 'zadne'
                ],
                'pozadi_efekt' => [
                    'dekor' => 'zadny',
                    'dekor_pohyb' => true,
                    'kenburns' => true
                ],
                'medium_efekt' => [
                    'animace' => 'zadna',
                    'stitek_anim' => true
                ],
                'odjezd' => [
                    'typ' => 'zadny'
                ],
                'divider' => [
                    'tvar' => 'zadny',
                    'otocit' => false,
                    'pohyb' => true
                ],
                'mobil' => [
                    'stack_od' => 'tablet',
                    'medium_mobil' => 'pod',
                    'zarovnani_mobil' => 'vlevo',
                    'tlacitka_full' => true,
                    'video_mobil' => 'obrazek',
                    'animace_mobil' => 'vypnout',
                    'min_vyska_mobil' => 'auto'
                ]
            ]
        ];
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
        return [
            c(
                'rozlozeni',
                'Rozložení',
                [
                c(
                    'varianta',
                    'Varianta',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'split', 'text' => 'Text vlevo, médium vpravo'], ['value' => 'split-rev', 'text' => 'Médium vlevo, text vpravo'], ['value' => 'center', 'text' => 'Na střed, médium pod textem'], ['value' => 'stack', 'text' => 'Vlevo, médium pod textem'], ['value' => 'text', 'text' => 'Jen text']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'pomer',
                    'Poměr sloupců (text : médium)',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => '1fr 1fr', 'text' => '1:1'], ['value' => '1.2fr 1fr', 'text' => '6:5'], ['value' => '1.5fr 1fr', 'text' => '3:2'], ['value' => '1fr 1.2fr', 'text' => '5:6']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'zarovnani',
                    'Svislé zarovnání',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'start', 'text' => 'Nahoru'], ['value' => 'center', 'text' => 'Na střed'], ['value' => 'end', 'text' => 'Dolů']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'min_vyska',
                    'Minimální výška',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'auto', 'text' => 'Auto'], ['value' => '60', 'text' => '60 % výšky okna'], ['value' => '75', 'text' => '75 %'], ['value' => '85', 'text' => '85 %'], ['value' => '100', 'text' => '100 %']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'sirka_elementu',
                    'Šířka elementu',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'full', 'text' => 'Na celou šířku okna'], ['value' => 'content', 'text' => 'Šířka obsahu webu'], ['value' => 'custom', 'text' => 'Vlastní'], ['value' => 'auto', 'text' => 'Podle rodiče (bez úprav)']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'sirka_elementu_vlastni',
                    'Vlastní šířka elementu (max.)',
                    [],
                    ['type' => 'unit', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'z_webu',
                    'Šířka obsahu, mezery a zaoblení z design systému webu',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'sirka',
                    'Šířka obsahu (vlastní)',
                    [],
                    ['type' => 'unit', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'odsazeni',
                    'Svislé odsazení sekce (vlastní)',
                    [],
                    ['type' => 'unit', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'mezera',
                    'Mezera mezi sloupci (vlastní)',
                    [],
                    ['type' => 'unit', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'barvy',
                'Barvy (prázdné = z Global Settings)',
                [
                c(
                    'brand',
                    'Hlavní barva (brand)',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'accent',
                    'Zvýrazňující barva (accent)',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'tmava',
                    'Tmavá barva',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'svetla',
                    'Světlá barva',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'pozadi',
                'Pozadí a překryv',
                [
                c(
                    'barva_pozadi',
                    'Barva pozadí (prázdné = tmavá barva)',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'barva_pozadi2',
                    'Druhá barva přechodu (prázdné = hlavní barva)',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'barva_prekryvu',
                    'Barva překryvu (prázdné = tmavá barva)',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'prekryv_sila',
                    'Síla překryvu (%)',
                    [],
                    ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => 0, 'max' => 90, 'step' => 5]],
                    false,
                    false,
                    [],
                ),
                c(
                    'prekryv_styl',
                    'Tvar překryvu',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'prechod', 'text' => 'Přechod od textu'], ['value' => 'plny', 'text' => 'Plný']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'barva_textu',
                    'Text na pozadí',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'auto', 'text' => 'Auto'], ['value' => 'svetly', 'text' => 'Světlý'], ['value' => 'tmavy', 'text' => 'Tmavý']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'barva_textu_vlastni',
                    'Barva textu (prázdné = podle volby výše)',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'typografie',
                'Nadpis a eyebrow',
                [
                c(
                    'velikost',
                    'Velikost nadpisu',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'S', 'text' => 'S'], ['value' => 'M', 'text' => 'M'], ['value' => 'L', 'text' => 'L'], ['value' => 'XL', 'text' => 'XL']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'nadpis_max',
                    'Max. šířka nadpisu (znaků)',
                    [],
                    ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => 10, 'max' => 40, 'step' => 1]],
                    false,
                    false,
                    [],
                ),
                c(
                    'eyebrow_styl',
                    'Styl eyebrow',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'kapitalky', 'text' => 'Kapitálky'], ['value' => 'pilulka', 'text' => 'Pilulka']]],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'tlacitka',
                'Tlačítka',
                [
                c(
                    'btn1_styl',
                    'Tlačítko 1 – styl',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'primary', 'text' => 'Primary'], ['value' => 'secondary', 'text' => 'Secondary'], ['value' => 'outline', 'text' => 'Outline'], ['value' => 'sklo', 'text' => 'Sklo'], ['value' => 'prechod', 'text' => 'Přechod'], ['value' => 'link', 'text' => 'Odkaz'], ['value' => 'vlastni', 'text' => 'Vlastní (Design → Tlačítko 1/2 – vlastní)']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'btn2_styl',
                    'Tlačítko 2 – styl',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'primary', 'text' => 'Primary'], ['value' => 'secondary', 'text' => 'Secondary'], ['value' => 'outline', 'text' => 'Outline'], ['value' => 'sklo', 'text' => 'Sklo'], ['value' => 'prechod', 'text' => 'Přechod'], ['value' => 'link', 'text' => 'Odkaz'], ['value' => 'vlastni', 'text' => 'Vlastní (Design → Tlačítko 1/2 – vlastní)']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'z_presetu',
                    'Tvar a velikost z Button Presets webu',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'tvar',
                    'Tvar (vlastní)',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'hranaty', 'text' => 'Hranatý'], ['value' => 'zaobleny', 'text' => 'Zaoblený'], ['value' => 'pilulka', 'text' => 'Pilulka']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'velikost',
                    'Velikost (vlastní)',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'S', 'text' => 'S'], ['value' => 'M', 'text' => 'M'], ['value' => 'L', 'text' => 'L']]],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'tlacitko1',
                'Tlačítko 1 – vlastní styl',
                [
                c(
                    'pozadi',
                    'Pozadí',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'pozadi_hover',
                    'Pozadí po najetí',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'text',
                    'Text a ikona',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'text_hover',
                    'Text a ikona po najetí',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'okraj_hover',
                    'Barva okraje po najetí',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                getPresetSection(
                    "EssentialElements\\borders_without_shadows",
                    'Okraj a zaoblení',
                    'okraj',
                    ['type' => 'popout']
                ),
                getPresetSection(
                    "EssentialElements\\spacing_padding_all",
                    'Vnitřní odsazení',
                    'odsazeni',
                    ['type' => 'popout']
                ),
                getPresetSection(
                    "EssentialElements\\typography_with_effects_and_align",
                    'Písmo',
                    'pismo',
                    ['type' => 'popout']
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'tlacitko2',
                'Tlačítko 2 – vlastní styl',
                [
                c(
                    'pozadi',
                    'Pozadí',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'pozadi_hover',
                    'Pozadí po najetí',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'text',
                    'Text a ikona',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'text_hover',
                    'Text a ikona po najetí',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'okraj_hover',
                    'Barva okraje po najetí',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                getPresetSection(
                    "EssentialElements\\borders_without_shadows",
                    'Okraj a zaoblení',
                    'okraj',
                    ['type' => 'popout']
                ),
                getPresetSection(
                    "EssentialElements\\spacing_padding_all",
                    'Vnitřní odsazení',
                    'odsazeni',
                    ['type' => 'popout']
                ),
                getPresetSection(
                    "EssentialElements\\typography_with_effects_and_align",
                    'Písmo',
                    'pismo',
                    ['type' => 'popout']
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'vyhody',
                'Výhody / čísla',
                [
                c(
                    'styl',
                    'Styl',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'seznam', 'text' => 'Seznam'], ['value' => 'karty', 'text' => 'Karty'], ['value' => 'cisla', 'text' => 'Čísla'], ['value' => 'predel', 'text' => 'Předěl – přes celou šířku se svislými čarami']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'predel_barva',
                    'Barva čar předělu (prázdné = text se 25 % průhledností)',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'ikona_styl',
                    'Ikona',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'kruh', 'text' => 'Ikona v kruhu'], ['value' => 'ikona', 'text' => 'Ikona'], ['value' => 'tecka', 'text' => 'Tečka'], ['value' => 'blikajici', 'text' => 'Blikající tečka']]],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'medium',
                'Médium',
                [
                c(
                    'maska',
                    'Maska (tvar obrázku)',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zadna', 'text' => 'Obdélník'], ['value' => 'oblouk', 'text' => 'Oblouk'], ['value' => 'blob', 'text' => 'Organický tvar'], ['value' => 'kruh', 'text' => 'Kruh'], ['value' => 'kapsle', 'text' => 'Kapsle'], ['value' => 'list', 'text' => 'List'], ['value' => 'sestiuhelnik', 'text' => 'Šestiúhelník'], ['value' => 'sikmo', 'text' => 'Šikmý řez'], ['value' => 'roh', 'text' => 'Zkosené rohy'], ['value' => 'vlna', 'text' => 'Vlnitá hrana']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'pomer',
                    'Poměr stran',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => '4/3', 'text' => '4:3'], ['value' => 'auto', 'text' => 'Podle obrázku'], ['value' => '1/1', 'text' => '1:1'], ['value' => '3/4', 'text' => '3:4'], ['value' => '16/9', 'text' => '16:9']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'zaobleni',
                    'Zaoblení (vlastní)',
                    [],
                    ['type' => 'unit', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'stin',
                    'Stín',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'dekor',
                    'Dekor za obrázkem',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zadny', 'text' => 'Žádný'], ['value' => 'plocha', 'text' => 'Plocha'], ['value' => 'ramecek', 'text' => 'Rámeček'], ['value' => 'tecky', 'text' => 'Tečky'], ['value' => 'kruh', 'text' => 'Kruh']]],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'slider',
                'Slider',
                [
                c(
                    'interval',
                    'Interval (s)',
                    [],
                    ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => 3, 'max' => 12, 'step' => 1]],
                    false,
                    false,
                    [],
                ),
                c(
                    'prechod',
                    'Přechod',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'prolnuti', 'text' => 'Prolnutí'], ['value' => 'posun', 'text' => 'Posun'], ['value' => 'zoom', 'text' => 'Zoom']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'navigace',
                    'Navigace',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'tecky', 'text' => 'Tečky'], ['value' => 'sipky', 'text' => 'Šipky'], ['value' => 'cisla', 'text' => 'Čísla + šipky'], ['value' => 'progres', 'text' => 'Průběh'], ['value' => 'zadna', 'text' => 'Žádná']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'pauza',
                    'Zastavit při najetí myší',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'vykon',
                'Výkon',
                [
                c(
                    'lcp',
                    'Hlavní obrázek načíst přednostně (LCP)',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'setrit_data',
                    'Bez videa při šetření dat a pomalém připojení',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'pismo',
                'Písmo jednotlivých prvků',
                [
                getPresetSection(
                    "EssentialElements\\typography_with_effects_and_align",
                    'Odznak',
                    'odznak',
                    ['type' => 'popout']
                ),
                getPresetSection(
                    "EssentialElements\\typography_with_effects_and_align",
                    'Eyebrow',
                    'eyebrow',
                    ['type' => 'popout']
                ),
                getPresetSection(
                    "EssentialElements\\typography_with_effects_and_align",
                    'Nadpis',
                    'nadpis',
                    ['type' => 'popout']
                ),
                getPresetSection(
                    "EssentialElements\\typography_with_effects_and_align",
                    'Podpůrný text',
                    'text',
                    ['type' => 'popout']
                ),
                getPresetSection(
                    "EssentialElements\\typography_with_effects_and_align",
                    'Tlačítka',
                    'tlacitka',
                    ['type' => 'popout']
                ),
                getPresetSection(
                    "EssentialElements\\typography_with_effects_and_align",
                    'Výhody (text položek)',
                    'vyhody',
                    ['type' => 'popout']
                ),
                getPresetSection(
                    "EssentialElements\\typography_with_effects_and_align",
                    'Čísla',
                    'cisla',
                    ['type' => 'popout']
                ),
                getPresetSection(
                    "EssentialElements\\typography_with_effects_and_align",
                    'Důvěra',
                    'duvera',
                    ['type' => 'popout']
                ),
                getPresetSection(
                    "EssentialElements\\typography_with_effects_and_align",
                    'Otevírací doba',
                    'oteviraci',
                    ['type' => 'popout']
                ),
                getPresetSection(
                    "EssentialElements\\typography_with_effects_and_align",
                    'Plovoucí štítek',
                    'stitek',
                    ['type' => 'popout']
                ),
                c(
                    'barva_hl',
                    'Barva zvýraznění a rotujících slov',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'styl',
                'Styl efektů',
                [
                c(
                    'preset',
                    'Styl efektů (Klidný / Moderní / Výrazný přebije volby níže)',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'vlastni', 'text' => 'Vlastní'], ['value' => 'klidny', 'text' => 'Klidný'], ['value' => 'moderni', 'text' => 'Moderní'], ['value' => 'vyrazny', 'text' => 'Výrazný']]],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'animace',
                'Vstupní animace',
                [
                c(
                    'vstup',
                    'Typ',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zadna', 'text' => 'Žádná'], ['value' => 'fade', 'text' => 'Prolnutí'], ['value' => 'nahoru', 'text' => 'Vyjetí zdola'], ['value' => 'zleva', 'text' => 'Příjezd zleva'], ['value' => 'zprava', 'text' => 'Příjezd zprava'], ['value' => 'zoom', 'text' => 'Přiblížení'], ['value' => 'rozostreni', 'text' => 'Z rozostření'], ['value' => 'odkryti', 'text' => 'Odkrytí maskou'], ['value' => 'preklopeni', 'text' => '3D překlopení'], ['value' => 'slova', 'text' => 'Nadpis po slovech']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'postupne',
                    'Prvky postupně za sebou',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'rychlost',
                    'Rychlost',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'normal', 'text' => 'Normálně'], ['value' => 'rychle', 'text' => 'Rychle'], ['value' => 'pomalu', 'text' => 'Pomalu']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'medium_vstup',
                    'Animace média',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'stejne', 'text' => 'Stejná'], ['value' => 'zoom', 'text' => 'Přiblížení'], ['value' => 'zprava', 'text' => 'Zprava'], ['value' => 'odkryti', 'text' => 'Odkrytí'], ['value' => 'rotace', 'text' => 'Pootočení']]],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'nadpis_efekt',
                'Efekty nadpisu',
                [
                c(
                    'zvyrazneni',
                    'Zvýraznění *slova*',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'barva', 'text' => 'Barva'], ['value' => 'podtrzeni', 'text' => 'Podtržení'], ['value' => 'zvyraznovac', 'text' => 'Zvýrazňovač'], ['value' => 'prechod', 'text' => 'Přelévání']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'rotace',
                    'Rotující [slova]',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'vyjeti', 'text' => 'Vyjetí'], ['value' => 'prolnuti', 'text' => 'Prolnutí'], ['value' => 'psaci', 'text' => 'Psací stroj']]],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'tlacitka_efekt',
                'Efekty tlačítek',
                [
                c(
                    'hover',
                    'Efekt po najetí',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zadny', 'text' => 'Žádný'], ['value' => 'zvednuti', 'text' => 'Zvednutí se stínem'], ['value' => 'sipka', 'text' => 'Posun ikony'], ['value' => 'vypln', 'text' => 'Výplň zleva'], ['value' => 'lesk', 'text' => 'Přejezd lesku'], ['value' => 'zare', 'text' => 'Záře'], ['value' => 'magnet', 'text' => 'Magnet']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'upoutat',
                    'Upoutat pozornost (tlačítko 1)',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zadne', 'text' => 'Ne'], ['value' => 'pulz', 'text' => 'Pulz'], ['value' => 'lesk', 'text' => 'Lesk'], ['value' => 'houpnuti', 'text' => 'Zahoupání']]],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'pozadi_efekt',
                'Efekty pozadí',
                [
                c(
                    'dekor',
                    'Dekorace pozadí',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zadny', 'text' => 'Žádná'], ['value' => 'tecky', 'text' => 'Tečkový rastr'], ['value' => 'mrizka', 'text' => 'Mřížka'], ['value' => 'sum', 'text' => 'Filmové zrno'], ['value' => 'bloby', 'text' => 'Plovoucí světla'], ['value' => 'castice', 'text' => 'Stoupající částice'], ['value' => 'paprsky', 'text' => 'Otáčející se paprsky'], ['value' => 'reflektor', 'text' => 'Světlo za kurzorem']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'dekor_pohyb',
                    'Dekorace v pohybu',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'kenburns',
                    'Pomalý zoom fotky (Ken Burns)',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'medium_efekt',
                'Efekty média',
                [
                c(
                    'animace',
                    'Animace obrázku',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zadna', 'text' => 'Žádná'], ['value' => 'vznaseni', 'text' => 'Vznášení'], ['value' => 'morph', 'text' => 'Měnící se tvar'], ['value' => 'zoom', 'text' => 'Pomalé přiblížení uvnitř'], ['value' => 'naklon', 'text' => '3D náklon za kurzorem']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'stitek_anim',
                    'Plovoucí štítek se pohupuje',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'odjezd',
                'Při odscrollování',
                [
                c(
                    'typ',
                    'Efekt',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zadny', 'text' => 'Žádný'], ['value' => 'zeslabnuti', 'text' => 'Zeslábnutí'], ['value' => 'paralaxa', 'text' => 'Paralaxa']]],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'divider',
                'Přechod do další sekce (divider)',
                [
                c(
                    'tvar',
                    'Tvar',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zadny', 'text' => 'Žádný'], ['value' => 'vlna', 'text' => 'Vlna'], ['value' => 'vlny', 'text' => 'Vrstvené vlny'], ['value' => 'oblouk', 'text' => 'Oblouk'], ['value' => 'kopec', 'text' => 'Kopec'], ['value' => 'sikmo', 'text' => 'Šikmo'], ['value' => 'krivka', 'text' => 'Asymetrická křivka'], ['value' => 'hory', 'text' => 'Hory'], ['value' => 'zuby', 'text' => 'Zuby'], ['value' => 'papir', 'text' => 'Natržený papír']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'vyska',
                    'Výška',
                    [],
                    ['type' => 'unit', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'barva',
                    'Barva (= pozadí další sekce, prázdné = bílá)',
                    [],
                    ['type' => 'color', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'otocit',
                    'Zrcadlit vodorovně',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'pohyb',
                    'Vlny v pohybu',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'mobil',
                'Tablet a mobil',
                [
                c(
                    'stack_od',
                    'Skládat pod sebe od',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'tablet', 'text' => 'Tabletu'], ['value' => 'mobil', 'text' => 'Mobilu']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'medium_mobil',
                    'Médium po složení',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'pod', 'text' => 'Pod textem'], ['value' => 'nad', 'text' => 'Nad textem'], ['value' => 'skryt', 'text' => 'Skrýt']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'zarovnani_mobil',
                    'Zarovnání textu po složení',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'vlevo', 'text' => 'Vlevo'], ['value' => 'stred', 'text' => 'Na střed']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'tlacitka_full',
                    'Tlačítka na celou šířku (mobil)',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'video_mobil',
                    'Video pozadí na mobilu',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'obrazek', 'text' => 'Jen poster'], ['value' => 'video', 'text' => 'Přehrávat']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'animace_mobil',
                    'Animace pozadí a obrázku na mobilu',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'vypnout', 'text' => 'Vypnout'], ['value' => 'ponechat', 'text' => 'Ponechat']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'min_vyska_mobil',
                    'Minimální výška na mobilu',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'auto', 'text' => 'Auto'], ['value' => '60', 'text' => '60 %'], ['value' => '75', 'text' => '75 %'], ['value' => '100', 'text' => '100 %']]],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            )
        ];
    }

    static function contentControls()
    {
        return [
            c(
                'zdroj',
                'Zdroj obsahu',
                [
                c(
                    'rezim',
                    'Obsah',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'rucne', 'text' => 'Ručně'], ['value' => 'dynamicky', 'text' => 'Dynamická data (šablona)']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'nadpis_pole',
                    'Nadpis z',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'post_title', 'text' => 'Název příspěvku'], ['value' => 'acf', 'text' => 'ACF pole'], ['value' => 'none', 'text' => 'Ručně zadaný nadpis']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'nadpis_acf',
                    'ACF pole nadpisu (název)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'text_pole',
                    'Text z',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'post_excerpt', 'text' => 'Výňatek příspěvku'], ['value' => 'acf', 'text' => 'ACF pole'], ['value' => 'none', 'text' => 'Ručně zadaný text']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'text_acf',
                    'ACF pole textu (název)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'obrazek_pole',
                    'Obrázek z',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'featured_image', 'text' => 'Náhledový obrázek'], ['value' => 'acf', 'text' => 'ACF pole'], ['value' => 'none', 'text' => 'Ručně vybraný obrázek']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'obrazek_acf',
                    'ACF pole obrázku (název)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'obsah',
                'Text',
                [
                c(
                    'odznak',
                    'Odznak nad nadpisem (prázdné = skryto)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'odznak_styl',
                    'Styl odznaku',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'obrys', 'text' => 'Obrys'], ['value' => 'tecka', 'text' => 'Blikající tečka'], ['value' => 'sklo', 'text' => 'Sklo']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'odznak_ikona',
                    'Ikona odznaku (u stylu Obrys / Sklo)',
                    [],
                    ['type' => 'icon', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'eyebrow',
                    'Nadnadpis (eyebrow)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'nadpis',
                    'Nadpis – *slovo* = zvýraznění, [a|b|c] = rotující slova',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'nadpis_tag',
                    'HTML tag nadpisu',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'h1', 'text' => 'H1'], ['value' => 'h2', 'text' => 'H2'], ['value' => 'p', 'text' => 'p']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'text',
                    'Podpůrný text',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'tlacitka',
                'Tlačítka',
                [
                c(
                    'btn1_text',
                    'Tlačítko 1 – text (prázdné = skryto)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'btn1_url',
                    'Tlačítko 1 – odkaz',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'btn1_nove_okno',
                    'Tlačítko 1 – otevřít v novém okně',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'btn1_ikona',
                    'Tlačítko 1 – ikona',
                    [],
                    ['type' => 'icon', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'btn2_text',
                    'Tlačítko 2 – text (prázdné = skryto)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'btn2_url',
                    'Tlačítko 2 – odkaz',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'btn2_nove_okno',
                    'Tlačítko 2 – otevřít v novém okně',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'btn2_ikona',
                    'Tlačítko 2 – ikona',
                    [],
                    ['type' => 'icon', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'video',
                'Video v lightboxu',
                [
                c(
                    'spoustec',
                    'Otevřít video',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zadny', 'text' => 'Ne'], ['value' => 'tlacitko2', 'text' => 'Tlačítkem 2'], ['value' => 'medium', 'text' => 'Play na obrázku'], ['value' => 'oboje', 'text' => 'Obojí']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'url',
                    'Odkaz na video (YouTube / Vimeo / MP4)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'soubor',
                    'Nebo adresa MP4 z knihovny médií (Média → Kopírovat URL)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'duvera',
                'Důvěra',
                [
                c(
                    'typ',
                    'Typ',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zadna', 'text' => 'Žádná'], ['value' => 'hodnoceni', 'text' => 'Hodnocení'], ['value' => 'avatary', 'text' => 'Zákazníci (fotky)'], ['value' => 'loga', 'text' => 'Loga']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'hodnoceni_cislo',
                    'Hodnocení (např. 4,9)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'hodnoceni_text',
                    'Text u hodnocení',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'avatary_text',
                    'Text u fotek',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'avatary_pocet',
                    'Počet kroužků bez fotek',
                    [],
                    ['type' => 'number', 'layout' => 'inline', 'rangeOptions' => ['min' => 3, 'max' => 6, 'step' => 1]],
                    false,
                    false,
                    [],
                ),
                c(
                    'avatary_foto',
                    'Fotky zákazníků (jen se souhlasem)',
                    [],
                    ['type' => 'wpmedia', 'layout' => 'vertical', 'mediaOptions' => ['acceptedFileTypes' => ['image'], 'multiple' => true]],
                    false,
                    false,
                    [],
                ),
                c(
                    'loga_nadpis',
                    'Nadpis u log',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'loga_soubory',
                    'Loga',
                    [],
                    ['type' => 'wpmedia', 'layout' => 'vertical', 'mediaOptions' => ['acceptedFileTypes' => ['image'], 'multiple' => true]],
                    false,
                    false,
                    [],
                ),
                c(
                    'loga',
                    'Nebo názvy oddělené čárkou',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'loga_pozice',
                    'Umístění log',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'dole', 'text' => 'Pruh dole'], ['value' => 'pod', 'text' => 'Pod tlačítky']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'loga_pohyb',
                    'Loga pomalu jedou (jen pruh dole)',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'oteviraci',
                'Otevírací doba',
                [
                c(
                    'zapnout',
                    'Zobrazit „Teď máme otevřeno“',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'dny',
                    'Dny',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'po-pa', 'text' => 'Po–Pá'], ['value' => 'po-so', 'text' => 'Po–So'], ['value' => 'denne', 'text' => 'Denně']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'od',
                    'Od (např. 7:00)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'do',
                    'Do (např. 17:00)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'telefon',
                    'Telefon',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'pozice',
                    'Umístění',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'pod', 'text' => 'Pod tlačítky'], ['value' => 'nahore', 'text' => 'Nad nadpisem']]],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'vyhody',
                'Výhody / čísla',
                [
                c(
                    'polozka1',
                    'Položka 1 (pro čísla „120+ | dokončených zakázek“; prázdné = skryto)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'polozka2',
                    'Položka 2',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'polozka3',
                    'Položka 3',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'polozka4',
                    'Položka 4',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'polozka5',
                    'Položka 5',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'polozka6',
                    'Položka 6',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'ikona',
                    'Ikona položek (prázdné = fajfka)',
                    [],
                    ['type' => 'icon', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'medium',
                'Médium vedle textu',
                [
                c(
                    'typ',
                    'Typ',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'obrazek', 'text' => 'Obrázek'], ['value' => 'video', 'text' => 'Video'], ['value' => 'formular', 'text' => 'Formulář'], ['value' => 'zadne', 'text' => 'Nic']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'obrazek',
                    'Obrázek',
                    [],
                    ['type' => 'wpmedia', 'layout' => 'vertical', 'mediaOptions' => ['acceptedFileTypes' => ['image'], 'multiple' => false]],
                    false,
                    false,
                    [],
                ),
                c(
                    'alt',
                    'Alt text (prázdné = z knihovny médií)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'video',
                    'Video – adresa MP4 bez zvuku (Média → Kopírovat URL)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'form_nadpis',
                    'Nadpis nad formulářem',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'form_shortcode',
                    'Formulář – shortcode (např. Global Block s formulářem)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'stitek',
                    'Plovoucí štítek u obrázku (prázdné = skryto)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'stitek_ikona',
                    'Ikona štítku (prázdné = hvězda)',
                    [],
                    ['type' => 'icon', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'pozadi',
                'Pozadí sekce',
                [
                c(
                    'typ',
                    'Typ pozadí',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zadne', 'text' => 'Žádné'], ['value' => 'barva', 'text' => 'Barva'], ['value' => 'gradient', 'text' => 'Přechod'], ['value' => 'zivy', 'text' => 'Živý přechod (animovaný)'], ['value' => 'obrazek', 'text' => 'Obrázek'], ['value' => 'video', 'text' => 'Video']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'obrazek',
                    'Obrázek / poster videa',
                    [],
                    ['type' => 'wpmedia', 'layout' => 'vertical', 'mediaOptions' => ['acceptedFileTypes' => ['image'], 'multiple' => false]],
                    false,
                    false,
                    [],
                ),
                c(
                    'video',
                    'Video pozadí – adresa MP4 bez zvuku, 2–4 MB (Média → Kopírovat URL)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'slider',
                'Slider',
                [
                c(
                    'rezim',
                    'Slider',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'vypnuto', 'text' => 'Vypnuto'], ['value' => 'pozadi', 'text' => 'Jen pozadí'], ['value' => 'cely', 'text' => 'Celá sekce']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'obrazky',
                    'Obrázky slidů',
                    [],
                    ['type' => 'wpmedia', 'layout' => 'vertical', 'mediaOptions' => ['acceptedFileTypes' => ['image'], 'multiple' => true]],
                    false,
                    false,
                    [],
                ),
                c(
                    'slide2_nadpis',
                    'Slide 2 – nadpis (prázdné = slide se nezobrazí)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'slide2_text',
                    'Slide 2 – text',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'slide2_termin',
                    'Slide 2 – zobrazit jen v termínu (např. 1.12.–31.12.)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'slide3_nadpis',
                    'Slide 3 – nadpis (prázdné = slide se nezobrazí)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'slide3_text',
                    'Slide 3 – text',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'slide3_termin',
                    'Slide 3 – zobrazit jen v termínu (např. 1.12.–31.12.)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'slide4_nadpis',
                    'Slide 4 – nadpis (prázdné = slide se nezobrazí)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'slide4_text',
                    'Slide 4 – text',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'slide4_termin',
                    'Slide 4 – zobrazit jen v termínu (např. 1.12.–31.12.)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'slide5_nadpis',
                    'Slide 5 – nadpis (prázdné = slide se nezobrazí)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'slide5_text',
                    'Slide 5 – text',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'slide5_termin',
                    'Slide 5 – zobrazit jen v termínu (např. 1.12.–31.12.)',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            ),
            c(
                'doplnky',
                'Doplňky',
                [
                c(
                    'sipka',
                    'Ukazatel „posunout dolů“',
                    [],
                    ['type' => 'dropdown', 'layout' => 'vertical', 'items' => [['value' => 'zadna', 'text' => 'Žádný'], ['value' => 'kruh', 'text' => 'Šipka'], ['value' => 'mys', 'text' => 'Myš'], ['value' => 'cara', 'text' => 'Čára']]],
                    false,
                    false,
                    [],
                ),
                c(
                    'header',
                    'Nad sekcí je průhledný header (přidá odsazení)',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'pauza',
                    'Tlačítko „Pozastavit pohyb“ (WCAG)',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'sticky',
                    'Přichycené CTA na mobilu',
                    [],
                    ['type' => 'toggle', 'layout' => 'inline'],
                    false,
                    false,
                    [],
                ),
                c(
                    'sticky_text',
                    'Text přichyceného tlačítka',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'sticky_url',
                    'Odkaz přichyceného tlačítka',
                    [],
                    ['type' => 'text', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                ),
                c(
                    'sticky_ikona',
                    'Ikona přichyceného tlačítka (prázdné = telefon)',
                    [],
                    ['type' => 'icon', 'layout' => 'vertical'],
                    false,
                    false,
                    [],
                )
                ],
                ['type' => 'section', 'layout' => 'vertical'],
                false,
                false,
                [],
            )
        ];
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
        return ['content.zdroj', 'content.obsah', 'content.tlacitka', 'content.video', 'content.duvera', 'content.oteviraci', 'content.vyhody', 'content.medium', 'content.pozadi', 'content.slider', 'content.doplnky', 'design.rozlozeni', 'design.barvy', 'design.pozadi', 'design.typografie', 'design.tlacitka', 'design.tlacitko1', 'design.tlacitko2', 'design.vyhody', 'design.medium', 'design.slider', 'design.vykon', 'design.styl', 'design.animace', 'design.nadpis_efekt', 'design.tlacitka_efekt', 'design.pozadi_efekt', 'design.medium_efekt', 'design.odjezd', 'design.divider', 'design.mobil'];
    }
}
