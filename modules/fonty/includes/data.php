<?php
/**
 * Modul Fonty – nastavení, adresář a evidence stažených sad.
 */

namespace MarmalElements\Fonty;

if (!defined('ABSPATH')) {
    exit;
}

const OPTION      = 'marmal_bdp_fonty';        // nastavení
const OPTION_SADY = 'marmal_bdp_fonty_sady';   // evidence stažených sad (klíč => údaje)
const SLOZKA      = 'marmal-fonty';            // uploads/marmal-fonty/
const MAX_SAD     = 30;                        // víc různých odkazů na Google Fonts web mít nebude

/** Znakové sady z Google Fonts. Latin a latin-ext se stahují vždy (čeština). */
function znakove_sady(): array
{
    return array(
        'cyrillic'   => __('Cyrilice (ruština, ukrajinština…)', 'marmal-breakdance-plus'),
        'greek'      => __('Řečtina', 'marmal-breakdance-plus'),
        'vietnamese' => __('Vietnamština', 'marmal-breakdance-plus'),
    );
}

function vychozi(): array
{
    return array(
        'sady'    => array(),   // navíc k latin + latin-ext
        'swap'    => true,      // font-display: swap – text je vidět hned, font se doplní
        'preload' => true,      // přednačíst běžný řez (400) hlavních fontů
    );
}

function nastaveni(): array
{
    $s = get_option(OPTION, array());
    $s = is_array($s) ? $s : array();
    return array_merge(vychozi(), array_intersect_key($s, vychozi()));
}

/** @param mixed $vstup */
function sanitizovat($vstup): array
{
    $v = is_array($vstup) ? $vstup : array();
    return array(
        'sady'    => array_values(array_intersect(array_keys(znakove_sady()), array_map('strval', (array) ($v['sady'] ?? array())))),
        'swap'    => !empty($v['swap']),
        'preload' => !empty($v['preload']),
    );
}

/** Stahované znakové sady (vždy latin a latin-ext). */
function stahovane_sady(): array
{
    return array_merge(array('latin', 'latin-ext'), (array) nastaveni()['sady']);
}

/** [cesta, adresa] složky s fonty v uploads. */
function adresar(): array
{
    $u = wp_upload_dir(null, false);
    return array(
        trailingslashit($u['basedir']) . SLOZKA . '/',
        set_url_scheme(trailingslashit($u['baseurl']) . SLOZKA . '/'),
    );
}

/** Evidence stažených sad: klíč => [url, css, rodiny, soubory, velikost, cas, stav, chyba, pouzito, preload]. */
function sady(): array
{
    $s = get_option(OPTION_SADY, array());
    return is_array($s) ? $s : array();
}

function ulozit_sady(array $sady): void
{
    // Nejstarší (podle posledního použití) pryč, když je jich moc.
    if (count($sady) > MAX_SAD) {
        uasort($sady, function ($a, $b) {
            return ($b['pouzito'] ?? 0) <=> ($a['pouzito'] ?? 0);
        });
        $sady = array_slice($sady, 0, MAX_SAD, true);
    }
    update_option(OPTION_SADY, $sady, false);
}

/**
 * Adresa CSS z Google Fonts v jednotném tvaru (https, bez HTML entit), nebo '' když to Google Fonts CSS není.
 * Povolené jsou jen fonts.googleapis.com/css a /css2 – nic jiného se nikdy nestahuje.
 */
function google_url(string $url): string
{
    $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (strpos($url, '//') === 0) {
        $url = 'https:' . $url;
    }
    $url = preg_replace('#^http://#i', 'https://', $url);
    $p   = wp_parse_url($url);
    if (!is_array($p) || strtolower($p['host'] ?? '') !== 'fonts.googleapis.com' || ($p['scheme'] ?? '') !== 'https'
        || !in_array($p['path'] ?? '', array('/css', '/css2'), true) || empty($p['query']) || strlen($url) > 2000) {
        return '';
    }
    return 'https://fonts.googleapis.com' . $p['path'] . '?' . $p['query'];
}

/** Klíč sady – stejný odkaz se stejnými znakovými sadami = stejné soubory. */
function klic(string $google_url): string
{
    return substr(md5($google_url . '|' . implode(',', stahovane_sady())), 0, 12);
}

/** Názvy rodin z odkazu (pro přehled v administraci). */
function rodiny_z_url(string $google_url): array
{
    $q = (string) wp_parse_url($google_url, PHP_URL_QUERY);
    $r = array();
    foreach (explode('&', $q) as $cast) {
        if (stripos($cast, 'family=') !== 0) {
            continue;
        }
        foreach (explode('|', urldecode(substr($cast, 7))) as $f) {
            $jmeno = trim(explode(':', $f)[0]);
            if ($jmeno !== '') {
                $r[] = sanitize_text_field($jmeno);
            }
        }
    }
    return array_values(array_unique($r));
}

/**
 * Vyčistí page cache známých pluginů (po stažení fontů, aby se na web dostal nový odkaz).
 * Volá jen funkce / akce, které daný plugin dokumentuje; když plugin není, nic se nestane.
 */
function vycistit_cache(): void
{
    if (function_exists('rocket_clean_domain')) {
        rocket_clean_domain();                 // WP Rocket
    }
    do_action('litespeed_purge_all');           // LiteSpeed Cache
    if (function_exists('w3tc_flush_all')) {
        w3tc_flush_all();                      // W3 Total Cache
    }
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();                // WP Super Cache
    }
}
