<?php
/**
 * Modul Obrázky – nastavení, podpora serveru, konfliktní pluginy, log, evidence a cesty.
 *
 * Evidence (fronta, mapa, záloha) je v options bez autoloadu a nezávisí na verzi pluginu:
 * obyčejná pole s klíčem 'v' (verze formátu), aby šel převod vrátit i po aktualizaci.
 */

namespace MarmalElements\Obrazky;

if (!defined('ABSPATH')) {
    exit;
}

const OPTION         = 'marmal_bdp_obrazky';          // nastavení
const OPTION_FRONTA  = 'marmal_bdp_obrazky_fronta';   // běžící / poslední operace (převod, vrácení)
const OPTION_MAPA    = 'marmal_bdp_obrazky_mapa';     // stará cesta (relativně k uploads) => nová cesta
const OPTION_ZALOHA  = 'marmal_bdp_obrazky_zaloha';   // evidence převedených příloh a dávek
const OPTION_PREHLED = 'marmal_bdp_obrazky_prehled';  // výsledek poslední inventury
const OPTION_LOG     = 'marmal_bdp_obrazky_log';      // posledních 100 chyb a událostí
const OPTION_ZAMEK   = 'marmal_bdp_obrazky_zamek';    // zámek dávky (proti dvěma oknům najednou)
const SLOZKA_ZALOHY  = 'marmal-obrazky-zaloha';       // uploads/marmal-obrazky-zaloha/
const FORMAT         = 1;                             // verze formátu evidence
const UKLID_DNI      = 14;                            // „Smazat staré soubory“ nejdřív po 14 dnech
const MAX_LOG        = 100;

// ================================================================= nastavení

function vychozi(): array
{
    return array(
        'cil'        => 'webp',  // webp | avif | vypnuto (jen převod při nahrání)
        'kvalita'    => 80,
        'max'        => 1920,    // maximální delší strana v px (0 = nezmenšovat)
        'png'        => true,    // převádět i PNG (průhlednost zůstane)
        'ponechat'   => false,   // ponechat původní soubor v uploads
        'min_kb'     => 0,       // nepřevádět soubory menší než (kB) při nahrání
        'prah_png'   => 50,      // hromadný převod: PNG nad (kB)
        'prah_jpg'   => 200,     // hromadný převod: JPG nad (kB)
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
    $d = vychozi();
    $cil = sanitize_key((string) ($v['cil'] ?? $d['cil']));
    return array(
        'cil'      => in_array($cil, array('webp', 'avif', 'vypnuto'), true) ? $cil : 'webp',
        'kvalita'  => max(40, min(100, (int) ($v['kvalita'] ?? $d['kvalita']))),
        'max'      => max(0, min(8000, (int) ($v['max'] ?? $d['max']))),
        'png'      => !empty($v['png']),
        'ponechat' => !empty($v['ponechat']),
        'min_kb'   => max(0, min(100000, (int) ($v['min_kb'] ?? 0))),
        'prah_png' => max(0, min(100000, (int) ($v['prah_png'] ?? $d['prah_png']))),
        'prah_jpg' => max(0, min(100000, (int) ($v['prah_jpg'] ?? $d['prah_jpg']))),
    );
}

// ================================================================= server

/**
 * Co server umí: ['webp' => bool, 'avif' => bool, 'editor' => 'Imagick'|'GD'|'', 'exif' => bool].
 */
function podpora(): array
{
    static $p = null;
    if (null !== $p) {
        return $p;
    }
    $p = array(
        'webp'   => (bool) wp_image_editor_supports(array('mime_type' => 'image/webp')),
        'avif'   => (bool) wp_image_editor_supports(array('mime_type' => 'image/avif')),
        'editor' => '',
        'exif'   => function_exists('exif_read_data'),
    );
    if (function_exists('_wp_image_editor_choose')) {
        $trida = (string) _wp_image_editor_choose(array('mime_type' => 'image/webp'));
        $p['editor'] = stripos($trida, 'imagick') !== false ? 'Imagick' : (stripos($trida, 'gd') !== false ? 'GD' : '');
    }
    return $p;
}

/** Cílový MIME podle nastavení a serveru: image/webp | image/avif | '' (nepřevádět). */
function cilovy_mime(): string
{
    $s = nastaveni();
    $p = podpora();
    if ($s['cil'] === 'vypnuto') {
        return '';
    }
    if ($s['cil'] === 'avif' && $p['avif']) {
        return 'image/avif';
    }
    return $p['webp'] ? 'image/webp' : '';
}

/** Cílový MIME pro hromadný převod – vždy WebP (odkazy v datech stránek, nejširší podpora). */
function cilovy_mime_knihovna(): string
{
    return podpora()['webp'] ? 'image/webp' : '';
}

function pripona(string $mime): string
{
    return $mime === 'image/avif' ? 'avif' : 'webp';
}

/**
 * Aktivní pluginy se stejnou funkcí (podle složky pluginu, název hlavního souboru se mění).
 * @return string[] názvy
 */
function konkurence(): array
{
    $znama = array(
        'webp-converter-for-media'    => 'Converter for Media',
        'ewww-image-optimizer'        => 'EWWW Image Optimizer',
        'ewww-image-optimizer-cloud'  => 'EWWW Image Optimizer Cloud',
        'shortpixel-image-optimiser'  => 'ShortPixel',
        'imagify'                     => 'Imagify',
        'webp-express'                => 'WebP Express',
        'optimole-wp'                 => 'Optimole',
        'wp-smushit'                  => 'Smush',
        'wp-smush-pro'                => 'Smush Pro',
    );
    $aktivni = (array) get_option('active_plugins', array());
    if (is_multisite()) {
        $aktivni = array_merge($aktivni, array_keys((array) get_site_option('active_sitewide_plugins', array())));
    }
    $nalezeno = array();
    foreach ($aktivni as $plugin) {
        $slozka = strtok((string) $plugin, '/');
        if (isset($znama[$slozka])) {
            $nalezeno[] = $znama[$slozka];
        }
    }
    return array_values(array_unique($nalezeno));
}

// ================================================================= log

function zapsat_log(string $zprava, string $typ = 'chyba'): void
{
    $l = get_option(OPTION_LOG, array());
    $l = is_array($l) ? $l : array();
    $l[] = array('cas' => time(), 'typ' => sanitize_key($typ), 'zprava' => wp_strip_all_tags($zprava));
    if (count($l) > MAX_LOG) {
        $l = array_slice($l, -MAX_LOG);
    }
    update_option(OPTION_LOG, $l, false);
}

function log_zaznamy(): array
{
    $l = get_option(OPTION_LOG, array());
    return is_array($l) ? array_reverse($l) : array();
}

// ================================================================= cesty

/** [basedir/, baseurl/, cesta baseurl (např. /wp-content/uploads/)] */
function uploads(): array
{
    static $u = null;
    if (null === $u) {
        $d   = wp_upload_dir(null, false);
        $url = trailingslashit((string) $d['baseurl']);
        $u   = array(
            trailingslashit(wp_normalize_path((string) $d['basedir'])),
            $url,
            trailingslashit((string) wp_parse_url($url, PHP_URL_PATH)),
        );
    }
    return $u;
}

/** Absolutní cesta → relativní k uploads ('' když leží mimo). */
function rel(string $abs): string
{
    list($base) = uploads();
    $abs = wp_normalize_path($abs);
    return strpos($abs, $base) === 0 ? substr($abs, strlen($base)) : '';
}

function abs_cesta(string $rel): string
{
    list($base) = uploads();
    return $base . ltrim(wp_normalize_path($rel), '/');
}

/**
 * Leží cesta bezpečně uvnitř uploads? (i pro neexistující soubor – podle složky)
 * Chrání všechny operace se soubory (kopie, mazání) před cestami z poškozených metadat.
 */
function v_uploads(string $cesta): bool
{
    list($base) = uploads();
    $cesta = wp_normalize_path($cesta);
    if ($cesta === '' || strpos($cesta, '..') !== false || strpos($cesta, "\0") !== false) {
        return false;
    }
    $realBase = realpath($base);
    $slozka   = realpath(dirname($cesta));
    if (!$realBase || !$slozka) {
        return false;
    }
    $realBase = trailingslashit(wp_normalize_path($realBase));
    return strpos(trailingslashit(wp_normalize_path($slozka)), $realBase) === 0;
}

// ================================================================= evidence

function fronta(): array
{
    $f = get_option(OPTION_FRONTA, array());
    return is_array($f) ? $f : array();
}

function ulozit_frontu(array $f): void
{
    $f['v'] = FORMAT;
    update_option(OPTION_FRONTA, $f, false);
}

/** Běží (nebo je pozastavená) nedokončená operace? */
function rozpracovano(): bool
{
    $f = fronta();
    return !empty($f['faze']) && !in_array($f['faze'], array('hotovo', 'zruseno'), true);
}

/** @return array<string,string> stará relativní cesta => nová */
function mapa(): array
{
    $m = get_option(OPTION_MAPA, array());
    return is_array($m) && isset($m['cesty']) && is_array($m['cesty']) ? $m['cesty'] : array();
}

function ulozit_mapu(array $cesty): void
{
    update_option(OPTION_MAPA, array('v' => FORMAT, 'cesty' => $cesty), false);
}

/** Evidence: ['v' => 1, 'prilohy' => [id => záznam], 'davky' => [id => záznam]] */
function zaloha(): array
{
    $z = get_option(OPTION_ZALOHA, array());
    $z = is_array($z) ? $z : array();
    return array_merge(array('v' => FORMAT, 'prilohy' => array(), 'davky' => array()), $z);
}

function ulozit_zalohu(array $z): void
{
    $z['v'] = FORMAT;
    update_option(OPTION_ZALOHA, $z, false);
}

// ================================================================= pomocné

function velikost(int $b): string
{
    if ($b >= 1048576) {
        return number_format_i18n($b / 1048576, 1) . ' MB';
    }
    return number_format_i18n(max($b > 0 ? 1 : 0, $b / 1024)) . ' kB';
}

/** Je MIME typ, který modul převádí? (PNG jen podle nastavení) */
function prevadet_mime(string $mime, bool $png = true): bool
{
    return $mime === 'image/jpeg' || ($png && $mime === 'image/png');
}
