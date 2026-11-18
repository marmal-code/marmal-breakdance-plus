<?php
/**
 * Modul Patička (od Breakdance Plus 0.10.0).
 *
 * Copyright s rokem spuštění webu, podpis autora (Martin Malý – marmal.cz) s UTM
 * a nofollow mimo úvodní stránku, údaje o podnikateli podle § 435 OZ, odkazy na
 * ochranu osobních údajů a nastavení cookies.
 *
 * Nastavení: MarMal → Patička (option marmal_bdp_paticka).
 * Výstup: shortcody (includes/render.php) a dynamická data Breakdance (kategorie MarMal → Patička).
 */

namespace MarmalElements\Paticka;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/render.php';

if (is_admin()) {
    require_once __DIR__ . '/includes/admin.php';
}

/**
 * Seznam dynamických polí: slug => [název, callback, typ].
 * Hodnoty počítají funkce z data.php (dají se volat i z PHP).
 */
function dynamicka_pole(): array
{
    return array(
        'marmal_paticka_copyright' => array(__('Copyright (© 2019–2026 Název)', 'marmal-breakdance-plus'), __NAMESPACE__ . '\copyright', 'string'),
        'marmal_paticka_roky'      => array(__('Copyright – jen roky (2019–2026)', 'marmal-breakdance-plus'), __NAMESPACE__ . '\roky', 'string'),
        'marmal_paticka_rok_od'    => array(__('Rok spuštění webu', 'marmal-breakdance-plus'), __NAMESPACE__ . '\rok_od', 'string'),
        'marmal_paticka_rok'       => array(__('Aktuální rok', 'marmal-breakdance-plus'), __NAMESPACE__ . '\letos', 'string'),
        'marmal_paticka_drzitel'   => array(__('Držitel práv', 'marmal-breakdance-plus'), __NAMESPACE__ . '\drzitel', 'string'),
        'marmal_paticka_autor'     => array(__('Podpis autora – text', 'marmal-breakdance-plus'), __NAMESPACE__ . '\autor_text', 'string'),
        'marmal_paticka_autor_url' => array(__('Podpis autora – odkaz (s UTM)', 'marmal-breakdance-plus'), __NAMESPACE__ . '\autor_url', 'url'),
        'marmal_paticka_udaje'     => array(__('Údaje o podnikateli – celý řádek', 'marmal-breakdance-plus'), __NAMESPACE__ . '\udaje_text', 'string'),
        'marmal_paticka_firma'     => array(__('Firma / jméno', 'marmal-breakdance-plus'), function () { return nastaveni()['firma']; }, 'string'),
        'marmal_paticka_sidlo'     => array(__('Sídlo', 'marmal-breakdance-plus'), function () { return nastaveni()['sidlo']; }, 'string'),
        'marmal_paticka_ico'       => array(__('IČO', 'marmal-breakdance-plus'), function () { return nastaveni()['ico']; }, 'string'),
        'marmal_paticka_dic'       => array(__('DIČ', 'marmal-breakdance-plus'), function () { return nastaveni()['dic']; }, 'string'),
        'marmal_paticka_rejstrik'  => array(__('Zápis v rejstříku', 'marmal-breakdance-plus'), function () { return text('rejstrik'); }, 'string'),
        'marmal_paticka_zasady'    => array(__('Ochrana osobních údajů – text', 'marmal-breakdance-plus'), function () { return text('zasady_text'); }, 'string'),
        'marmal_paticka_zasady_url' => array(__('Ochrana osobních údajů – odkaz', 'marmal-breakdance-plus'), __NAMESPACE__ . '\zasady_url', 'url'),
    );
}

// Dynamická data Breakdance – jen když Breakdance běží (třída se načte až po kontrole).
add_action('init', function () {
    if (!function_exists('\Breakdance\DynamicData\registerField')
        || !class_exists('\Breakdance\DynamicData\StringField')
        || !class_exists('\Breakdance\DynamicData\StringData')) {
        return;
    }
    require_once __DIR__ . '/includes/dynamic-data-pole.php';
    foreach (dynamicka_pole() as $slug => $pole) {
        \Breakdance\DynamicData\registerField(new Pole($slug, $pole[0], $pole[1], array($pole[2])));
    }
}, 20);

// Polylang: vlastní texty jdou přeložit v Jazyky → Překlady řetězců (skupina „Marmal – Patička“).
add_action('init', function () {
    if (!function_exists('pll_register_string')) {
        return;
    }
    $s = nastaveni();
    foreach (prekladane_klice() as $klic) {
        if (!empty($s[$klic])) {
            pll_register_string('marmal_paticka_' . $klic, $s[$klic], 'Marmal – Patička');
        }
    }
});
