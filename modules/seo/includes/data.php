<?php
/**
 * Modul SEO – nastavení a výpočet hodnot (bez HTML).
 *
 * Titulky a popisky se ukládají jako čistý text (bez HTML) a escapují se až při výpisu.
 * Zástupné symboly v titulku i popisku: %nazev% (název stránky), %web% (název webu),
 * %oddelovac%, %popis_webu% (krátký popis z Nastavení → Obecné).
 */

namespace MarmalElements\Seo;

if (!defined('ABSPATH')) {
    exit;
}

const OPTION        = 'marmal_bdp_seo';
const META_TITLE    = '_marmal_seo_title';
const META_DESC     = '_marmal_seo_desc';
const META_IMAGE    = '_marmal_seo_image';
const META_NOINDEX  = '_marmal_seo_noindex';
const MAX_TITLE     = 120;
const MAX_DESC      = 300;
const DOP_TITLE     = 60;   // doporučená délka titulku (znaky)
const DOP_DESC_MIN  = 120;  // doporučená délka popisku
const DOP_DESC_MAX  = 160;
const OG_SIZE       = 'marmal-og';
const OG_W          = 1200;
const OG_H          = 630;

// ================================================================= nastavení

/** Povolené oddělovače v titulku. */
function oddelovace(): array
{
    return array('|', '–', '-', '·', '•', '/', '»');
}

function prazdne(): array
{
    return array(
        'web'             => '',      // prázdné = název webu z Nastavení → Obecné
        'oddelovac'       => '|',
        'vzor'            => '',      // prázdné = %nazev% %oddelovac% %web%
        'titulek_uvod'    => '',      // prázdné = vlastní titulek úvodní stránky, jinak „web | popis webu“
        'popis'           => '',      // výchozí popisek
        'obrazek'         => 0,       // výchozí obrázek pro sdílení (ID přílohy)
        'locale'          => '',      // prázdné = podle jazyka webu / stránky (Polylang, WPML)
        'typy'            => array(), // prázdné = všechny veřejné typy obsahu
        'noindex_autor'   => true,
        'noindex_datum'   => true,
        'noindex_stitky'  => false,
        'noindex_prilohy' => true,
        'drobecky_schema'    => true,   // JSON-LD BreadcrumbList v hlavičce
        'drobecky_uvod'      => '',     // prázdné = „Úvod“
        'drobecky_oddelovac' => '›',
        'drobecky_rodice'    => array(), // typ obsahu => ID nadřazené stránky
        'robots'             => '',     // vlastní řádky robots.txt
        'jazyky'             => array(), // jazyk => přepsání JAZYK_POLE pro další jazyky (Polylang, WPML)
        // Data o firmě (firma.php) – název, sídlo, IČO a DIČ z modulu Ochrana os. údajů / Patičky
        'firma_schema'  => true,
        'firma_typ'     => 'Organization',
        'firma_nazev'   => '',      // prázdné = z GDPR / Patičky
        'firma_telefon' => '',      // prázdné = z GDPR
        'firma_email'   => '',      // prázdné = z GDPR
        'firma_ulice'   => '',      // adresa provozovny; prázdné = ze sídla
        'firma_psc'     => '',
        'firma_mesto'   => '',
        'firma_zeme'    => 'CZ',
        'firma_logo'    => 0,       // prázdné = logo webu / ikona webu
        'firma_hodiny'  => array(), // po…ne => „8:00–16:00“
        'firma_site'    => '',      // profily na sítích, jeden odkaz na řádek
        'firma_gps'     => '',      // „50.0123, 14.4567“
        // AI vyhledávání (ai.php)
        'ai_roboti'     => 'vse',   // vse | bez_treninku
        'indexnow'      => true,
        'llms'          => false,
        'llms_uvod'     => '',
    );
}

function vychozi_vzor(): string
{
    return '%nazev% %oddelovac% %web%';
}

/** Hodnoty, které jdou u vícejazyčného webu nastavit pro každý další jazyk zvlášť. */
const JAZYK_POLE = array('web', 'vzor', 'titulek_uvod', 'popis', 'obrazek', 'drobecky_uvod');

/**
 * Nastavení pro aktuální jazyk: výchozí jazyk = základní hodnoty, další jazyk = základní hodnoty
 * přepsané vyplněnými poli z $s['jazyky'][jazyk]. Jednojazyčný web a administrace = základní hodnoty.
 */
function nastaveni(): array
{
    static $cache = array();
    $jazyk = aktualni_jazyk();
    if (isset($cache[$jazyk]) && !defined('MARMAL_SEO_TEST')) {
        return $cache[$jazyk];
    }
    $ulozene = get_option(OPTION, array());
    $s       = array_merge(prazdne(), is_array($ulozene) ? $ulozene : array());
    if ($jazyk !== '' && $jazyk !== vychozi_jazyk() && !empty($s['jazyky'][$jazyk]) && is_array($s['jazyky'][$jazyk])) {
        foreach (JAZYK_POLE as $klic) {
            $v = $s['jazyky'][$jazyk][$klic] ?? '';
            if ($v !== '' && $v !== 0 && $v !== '0' && $v !== null) {
                $s[$klic] = $v;
            }
        }
    }
    $cache[$jazyk] = $s;
    return $s;
}

// ================================================================= jazyky (Polylang, WPML)

/**
 * Jazyky webu: slug => [nazev, locale, vychozi]. Prázdné pole = jednojazyčný web
 * (i když je Polylang / WPML zapnutý jen s jedním jazykem).
 */
function jazyky(): array
{
    static $c = null;
    if (null !== $c && !defined('MARMAL_SEO_TEST')) {
        return $c;
    }
    $c = array();
    if (function_exists('pll_languages_list')) {
        $vychozi = function_exists('pll_default_language') ? (string) pll_default_language() : '';
        foreach ((array) pll_languages_list(array('fields' => '')) as $l) {
            if (is_object($l) && !empty($l->slug)) {
                $c[(string) $l->slug] = array('nazev' => (string) ($l->name ?? $l->slug), 'locale' => (string) ($l->locale ?? ''), 'vychozi' => (string) $l->slug === $vychozi);
            }
        }
    } elseif (has_filter('wpml_active_languages')) {
        $vychozi = (string) apply_filters('wpml_default_language', null);
        foreach ((array) apply_filters('wpml_active_languages', null, array('skip_missing' => 0)) as $kod => $l) {
            if (is_array($l)) {
                $c[(string) $kod] = array('nazev' => (string) ($l['native_name'] ?? $kod), 'locale' => (string) ($l['default_locale'] ?? ''), 'vychozi' => (string) $kod === $vychozi);
            }
        }
    }
    return $c = (count($c) > 1 ? $c : array());
}

function vychozi_jazyk(): string
{
    foreach (jazyky() as $slug => $l) {
        if ($l['vychozi']) {
            return (string) $slug;
        }
    }
    return '';
}

/** Jazyk vynucený pro výpočet (náhled v boxu u stránky v administraci). null = žádný. */
function vynuceny_jazyk(): ?string
{
    return $GLOBALS['marmal_seo_jazyk'] ?? null;
}

function vynutit_jazyk(?string $jazyk): void
{
    $GLOBALS['marmal_seo_jazyk'] = $jazyk;
}

/** Jazyk, pro který se počítá výstup: vynucený, jinak jazyk stránky na webu. V administraci ''. */
function aktualni_jazyk(): string
{
    $v = vynuceny_jazyk();
    if (null !== $v) {
        return $v;
    }
    if (is_admin() || !jazyky()) {
        return '';
    }
    if (function_exists('pll_current_language')) {
        return (string) pll_current_language();
    }
    return (string) apply_filters('wpml_current_language', null);
}

function jazyk_prispevku(int $post_id): string
{
    if (function_exists('pll_get_post_language')) {
        return (string) pll_get_post_language($post_id);
    }
    if (has_filter('wpml_post_language_details')) {
        $d = apply_filters('wpml_post_language_details', null, $post_id);
        return is_array($d) ? (string) ($d['language_code'] ?? '') : '';
    }
    return '';
}

/** Překlady příspěvku: jazyk => ID (včetně něj samotného). Jednojazyčný web = []. */
function preklady(int $post_id): array
{
    if (!$post_id || !jazyky()) {
        return array();
    }
    if (function_exists('pll_get_post_translations')) {
        return array_map('intval', (array) pll_get_post_translations($post_id));
    }
    $out = array();
    $typ = (string) (get_post_type($post_id) ?: 'page');
    foreach (array_keys(jazyky()) as $j) {
        $t = (int) apply_filters('wpml_object_id', $post_id, $typ, false, $j);
        if ($t) {
            $out[$j] = $t;
        }
    }
    return $out;
}

/** ID příspěvku v daném jazyce (když překlad není, původní ID). */
function preklad(int $post_id, string $jazyk): int
{
    if (!$post_id || $jazyk === '' || !jazyky()) {
        return $post_id;
    }
    if (function_exists('pll_get_post')) {
        $t = (int) pll_get_post($post_id, $jazyk);
        return $t ?: $post_id;
    }
    $t = (int) apply_filters('wpml_object_id', $post_id, (string) (get_post_type($post_id) ?: 'page'), true, $jazyk);
    return $t ?: $post_id;
}

/** Adresa úvodní stránky v aktuálním jazyce. */
function uvod_url(): string
{
    if (jazyky() && function_exists('pll_home_url')) {
        $u = (string) pll_home_url(aktualni_jazyk());
        if ($u !== '') {
            return $u;
        }
    }
    return home_url('/');
}

/** og:locale ostatních jazyků (u stránky jen těch, do kterých je přeložená). */
function locale_alternativy(int $post_id): array
{
    $jazyky = jazyky();
    if (!$jazyky) {
        return array();
    }
    $kde = $post_id ? array_keys(preklady($post_id)) : array_keys($jazyky);
    $out = array();
    $ted = locale_og();
    foreach ($kde as $j) {
        $loc = (string) ($jazyky[$j]['locale'] ?? '');
        if (preg_match('/^([a-z]{2,3})[_-]([A-Za-z]{2})/', $loc, $m)) {
            $loc = $m[1] . '_' . strtoupper($m[2]);
            if ($loc !== $ted) {
                $out[$loc] = true;
            }
        }
    }
    return array_keys($out);
}

/** Text bez HTML a zalomení, s omezenou délkou. */
function cisty_text($v, int $max): string
{
    $v = wp_strip_all_tags(html_entity_decode((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $v = trim(preg_replace('/\s+/u', ' ', $v));
    return mb_substr($v, 0, $max, 'UTF-8');
}

/**
 * Sanitizace nastavení (register_setting). Volá se i při update_option z kódu – musí přijmout
 * i už čisté pole. options.php hodnoty už odslashuje, wp_unslash se tu nevolá.
 */
function sanitizovat($in): array
{
    $in  = is_array($in) ? $in : array();
    $out = prazdne();

    $out['web']          = cisty_text($in['web'] ?? '', 80);
    $odd                 = (string) ($in['oddelovac'] ?? '|');
    $out['oddelovac']    = in_array($odd, oddelovace(), true) ? $odd : '|';
    $out['vzor']         = cisty_text($in['vzor'] ?? '', MAX_TITLE);
    if ($out['vzor'] !== '' && strpos($out['vzor'], '%nazev%') === false) {
        add_settings_error(OPTION, 'vzor', __('Vzor titulku musí obsahovat %nazev% – jinak by všechny stránky měly stejný titulek. Použije se výchozí vzor.', 'marmal-breakdance-plus'));
        $out['vzor'] = '';
    }
    $out['titulek_uvod'] = cisty_text($in['titulek_uvod'] ?? '', MAX_TITLE);
    $out['popis']        = cisty_text($in['popis'] ?? '', MAX_DESC);

    $img            = absint($in['obrazek'] ?? 0);
    $out['obrazek'] = ($img && wp_attachment_is_image($img)) ? $img : 0;

    $loc           = (string) ($in['locale'] ?? '');
    $out['locale'] = preg_match('/^[a-z]{2,3}_[A-Z]{2}$/', $loc) ? $loc : '';

    $verejne     = array_keys(typy_obsahu_vse());
    $typy        = array_map('sanitize_key', (array) ($in['typy'] ?? array()));
    $out['typy'] = array_values(array_intersect($typy, $verejne));

    foreach (array('noindex_autor', 'noindex_datum', 'noindex_stitky', 'noindex_prilohy') as $k) {
        $out[$k] = !empty($in[$k]);
    }

    $out['drobecky_schema']    = !empty($in['drobecky_schema']);
    $out['drobecky_uvod']      = cisty_text($in['drobecky_uvod'] ?? '', 60);
    $odd                       = (string) ($in['drobecky_oddelovac'] ?? '›');
    $out['drobecky_oddelovac'] = function_exists(__NAMESPACE__ . '\drobecky_oddelovace') && in_array($odd, drobecky_oddelovace(), true) ? $odd : '›';
    $out['drobecky_rodice']    = array();
    foreach ((array) ($in['drobecky_rodice'] ?? array()) as $typ => $id) {
        $typ = sanitize_key((string) $typ);
        $id  = absint($id);
        if ($id && in_array($typ, $verejne, true) && !in_array($typ, array('page', 'post'), true) && get_post_type($id) === 'page') {
            $out['drobecky_rodice'][$typ] = $id;
        }
    }
    $out['robots'] = robots_sanitizovat($in['robots'] ?? '');
    $out['jazyky'] = jazyky_sanitizovat($in['jazyky'] ?? array());
    $out = array_merge($out, geo_sanitizovat($in));

    if (function_exists(__NAMESPACE__ . '\og_vytvorit')) {
        $obrazky = array_merge(array($out['obrazek']), array_column($out['jazyky'], 'obrazek'));
        foreach (array_unique(array_filter(array_map('intval', $obrazky))) as $obr) {
            og_vytvorit($obr);
        }
    }
    return $out;
}

/** Data o firmě a AI vyhledávání (firma.php, ai.php). */
function geo_sanitizovat(array $in): array
{
    $o = array();
    $o['firma_schema'] = !empty($in['firma_schema']);
    $typ = (string) ($in['firma_typ'] ?? 'Organization');
    $o['firma_typ'] = function_exists(__NAMESPACE__ . '\\firma_typy') && array_key_exists($typ, firma_typy()) ? $typ : 'Organization';
    $o['firma_nazev']   = cisty_text($in['firma_nazev'] ?? '', 120);
    $o['firma_telefon'] = mb_substr(preg_replace('/[^0-9+()\/ \-]/', '', (string) ($in['firma_telefon'] ?? '')), 0, 30, 'UTF-8');
    $o['firma_telefon'] = trim($o['firma_telefon']);
    $email = sanitize_email((string) ($in['firma_email'] ?? ''));
    $o['firma_email']   = is_email($email) ? $email : '';
    $o['firma_ulice']   = cisty_text($in['firma_ulice'] ?? '', 120);
    $o['firma_psc']     = mb_substr(preg_replace('/[^0-9A-Za-z \-]/', '', (string) ($in['firma_psc'] ?? '')), 0, 12, 'UTF-8');
    $o['firma_psc']     = trim($o['firma_psc']);
    $o['firma_mesto']   = cisty_text($in['firma_mesto'] ?? '', 80);
    $zeme = strtoupper((string) ($in['firma_zeme'] ?? 'CZ'));
    $o['firma_zeme']    = preg_match('/^[A-Z]{2}$/', $zeme) ? $zeme : 'CZ';
    $logo = absint($in['firma_logo'] ?? 0);
    $o['firma_logo']    = ($logo && wp_attachment_is_image($logo)) ? $logo : 0;
    $o['firma_hodiny']  = array();
    $hodiny = is_array($in['firma_hodiny'] ?? null) ? $in['firma_hodiny'] : array();
    foreach (array('po', 'ut', 'st', 'ct', 'pa', 'so', 'ne') as $den) {
        $h = is_scalar($hodiny[$den] ?? '') ? cisty_text((string) ($hodiny[$den] ?? ''), 60) : '';
        if ($h !== '') {
            $o['firma_hodiny'][$den] = $h;
        }
    }
    $site = array();
    foreach (preg_split('/[\r\n]+/', (string) ($in['firma_site'] ?? '')) as $r) {
        $u = esc_url_raw(trim((string) $r), array('https'));
        if ($u !== '') {
            $site[] = $u;
        }
    }
    $o['firma_site'] = implode("\n", array_slice(array_unique($site), 0, 15));
    $gps = cisty_text($in['firma_gps'] ?? '', 40);
    $o['firma_gps'] = ($gps === '' || (function_exists(__NAMESPACE__ . '\\gps') && gps($gps))) ? $gps : '';
    if ($gps !== '' && $o['firma_gps'] === '') {
        add_settings_error(OPTION, 'gps', __('Souřadnice nemají tvar „50.0123, 14.4567“ – neuloženy.', 'marmal-breakdance-plus'));
    }
    $ai = (string) ($in['ai_roboti'] ?? 'vse');
    $o['ai_roboti'] = in_array($ai, array('vse', 'bez_treninku'), true) ? $ai : 'vse';
    $o['indexnow']  = !empty($in['indexnow']);
    $o['llms']      = !empty($in['llms']);
    $uvod = wp_strip_all_tags((string) ($in['llms_uvod'] ?? ''));
    $uvod = preg_replace("/\r\n?/", "\n", $uvod);
    $o['llms_uvod'] = trim(mb_substr((string) $uvod, 0, 1000, 'UTF-8'));
    return $o;
}

/**
 * Přepsání pro další jazyky. Bez Polylangu / WPML (dočasně vypnutý) zůstanou uložená beze změny,
 * aby se při uložení nastavení neztratila.
 */
function jazyky_sanitizovat($in): array
{
    $zname = jazyky();
    if (!$zname) {
        $stare = get_option(OPTION, array());
        $in    = is_array($stare) && is_array($stare['jazyky'] ?? null) ? $stare['jazyky'] : array();
    }
    $out = array();
    foreach ((array) $in as $j => $h) {
        $j = sanitize_key((string) $j);
        if (!is_array($h) || $j === '' || ($zname && (!isset($zname[$j]) || $zname[$j]['vychozi']))) {
            continue;
        }
        $r = array(
            'web'           => cisty_text($h['web'] ?? '', 80),
            'vzor'          => cisty_text($h['vzor'] ?? '', MAX_TITLE),
            'titulek_uvod'  => cisty_text($h['titulek_uvod'] ?? '', MAX_TITLE),
            'popis'         => cisty_text($h['popis'] ?? '', MAX_DESC),
            'obrazek'       => absint($h['obrazek'] ?? 0),
            'drobecky_uvod' => cisty_text($h['drobecky_uvod'] ?? '', 60),
        );
        if ($r['vzor'] !== '' && strpos($r['vzor'], '%nazev%') === false) {
            add_settings_error(OPTION, 'vzor-' . $j, __('Vzor titulku musí obsahovat %nazev% – jinak by všechny stránky měly stejný titulek. Použije se výchozí vzor.', 'marmal-breakdance-plus'));
            $r['vzor'] = '';
        }
        if ($r['obrazek'] && !wp_attachment_is_image($r['obrazek'])) {
            $r['obrazek'] = 0;
        }
        if (array_filter($r)) {
            $out[$j] = $r;
        }
    }
    return array_slice($out, 0, 20, true);
}

/** Veřejné typy obsahu (kromě příloh): slug => název. */
function typy_obsahu_vse(): array
{
    $out = array();
    foreach (get_post_types(array('public' => true), 'objects') as $slug => $pt) {
        if ($slug === 'attachment') {
            continue;
        }
        $out[$slug] = $pt->labels->singular_name ?? $pt->label ?? $slug;
    }
    return $out;
}

/** Typy obsahu, u kterých je box „SEO a sdílení“. */
function typy_obsahu(): array
{
    $vse  = array_keys(typy_obsahu_vse());
    $vyb  = nastaveni()['typy'];
    return $vyb ? array_values(array_intersect($vyb, $vse)) : $vse;
}

function nazev_webu(): string
{
    $s = nastaveni()['web'];
    return $s !== '' ? $s : cisty_text(prelozeny_text(get_bloginfo('name'), 'Blog Title'), 80);
}

function popis_webu(): string
{
    return cisty_text(prelozeny_text(get_bloginfo('description'), 'Tagline'), 160);
}

/**
 * Název / popis webu v jazyce vynuceném pro náhled v administraci (na webu ho Polylang / WPML
 * přeloží sám). $wpml = název řetězce v doméně „WP“ ve WPML.
 */
function prelozeny_text(string $text, string $wpml): string
{
    $j = vynuceny_jazyk();
    if (null === $j || $j === '' || $text === '' || $j === vychozi_jazyk()) {
        return $text;
    }
    if (function_exists('pll_translate_string')) {
        return (string) pll_translate_string($text, $j);
    }
    return (string) apply_filters('wpml_translate_single_string', $text, 'WP', $wpml, $j);
}

function oddelovac(): string
{
    return nastaveni()['oddelovac'];
}

function vzor(): string
{
    $v = nastaveni()['vzor'];
    return $v !== '' ? $v : vychozi_vzor();
}

/** Nahradí zástupné symboly a uklidí mezery (prázdný %popis_webu% nenechá viset oddělovač). */
function nahradit(string $text, string $nazev = ''): string
{
    if ($text === '' || strpos($text, '%') === false) {
        return $text;
    }
    $odd  = oddelovac();
    $text = strtr($text, array(
        '%nazev%'      => $nazev,
        '%web%'        => nazev_webu(),
        '%popis_webu%' => popis_webu(),
        '%oddelovac%'  => $odd,
    ));
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    // Oddělovač na začátku / konci nebo zdvojený (prázdná část) pryč.
    $q    = preg_quote($odd, '/');
    $text = preg_replace('/^(' . $q . '\s*)+|(\s*' . $q . ')+$/u', '', $text);
    $text = preg_replace('/(' . $q . ')(\s*' . $q . ')+/u', '$1', $text);
    return trim($text);
}

/**
 * Vlastní řádky robots.txt: jen známé direktivy a komentáře, max. 50 řádků po 200 znacích.
 * Ostatní řádky se zahodí (překlep by mohl zablokovat celý web jen u části robotů).
 */
function robots_sanitizovat($text): string
{
    $radky = preg_split('/\r\n|\r|\n/', (string) $text);
    $out   = array();
    foreach ((array) $radky as $r) {
        if (trim((string) $r) === '') {
            if ($out && end($out) !== '') {
                $out[] = '';
            }
            continue;
        }
        $r = trim(wp_strip_all_tags((string) $r));
        if ($r === '') {
            continue;
        }
        if ($r[0] === '#' || preg_match('/^(user-agent|disallow|allow|sitemap|crawl-delay|host|clean-param)\s*:/i', $r)) {
            $out[] = mb_substr($r, 0, 200, 'UTF-8');
        }
        if (count($out) >= 50) {
            break;
        }
    }
    return trim(implode("\n", $out));
}

// ================================================================= jiné SEO pluginy

/** Aktivní SEO pluginy, se kterými modul nic nevypisuje: složka => název. */
function konkurence(): array
{
    $znama = array(
        'wordpress-seo'           => 'Yoast SEO',
        'wordpress-seo-premium'   => 'Yoast SEO Premium',
        'seo-by-rank-math'        => 'Rank Math',
        'seo-by-rank-math-pro'    => 'Rank Math Pro',
        'wp-seopress'             => 'SEOPress',
        'wp-seopress-pro'         => 'SEOPress Pro',
        'all-in-one-seo-pack'     => 'All in One SEO',
        'all-in-one-seo-pack-pro' => 'All in One SEO Pro',
        'autodescription'         => 'The SEO Framework',
        'slim-seo'                => 'Slim SEO',
        'squirrly-seo'            => 'Squirrly SEO',
    );
    $aktivni = (array) get_option('active_plugins', array());
    if (is_multisite()) {
        $aktivni = array_merge($aktivni, array_keys((array) get_site_option('active_sitewide_plugins', array())));
    }
    $nalezeno = array();
    foreach ($aktivni as $plugin) {
        $slozka = strtok((string) $plugin, '/');
        if (isset($znama[$slozka])) {
            $nalezeno[$slozka] = $znama[$slozka];
        }
    }
    // Pojistka pro pluginy v jiné složce (zip s jiným názvem).
    $konstanty = array('WPSEO_VERSION' => 'Yoast SEO', 'RANK_MATH_VERSION' => 'Rank Math', 'SEOPRESS_VERSION' => 'SEOPress', 'AIOSEO_VERSION' => 'All in One SEO', 'THE_SEO_FRAMEWORK_VERSION' => 'The SEO Framework');
    foreach ($konstanty as $k => $nazev) {
        if (defined($k) && !in_array($nazev, $nalezeno, true)) {
            $nalezeno[$k] = $nazev;
        }
    }
    return $nalezeno;
}

/** Modul na webu vypisuje hlavičku (žádný jiný SEO plugin). */
function vypisuje(): bool
{
    return !konkurence();
}

/** Upozornění pro administraci: [typ (error|warning|info), text]. */
function upozorneni(): array
{
    $u = array();
    if ($k = konkurence()) {
        $u[] = array('typ' => 'warning', 'text' => sprintf(
            /* translators: %s: názvy SEO pluginů */
            __('Na webu běží %s – modul SEO proto nic nevypisuje. Nejdřív převeď data (MarMal → SEO → Převod), pak ten plugin deaktivuj.', 'marmal-breakdance-plus'),
            implode(', ', $k)
        ));
    }
    if (!get_option('blog_public')) {
        $u[] = array('typ' => 'error', 'text' => __('Web je skrytý před vyhledávači (Nastavení → Zobrazování → „Požádat vyhledávače, aby neindexovaly tento web“). Po spuštění webu to vypni.', 'marmal-breakdance-plus'));
    }
    if (function_exists('has_site_icon') && !has_site_icon()) {
        $u[] = array('typ' => 'info', 'text' => __('Chybí ikona webu (favicon) – nastav ji ve Vzhled → Přizpůsobit → Identita webu → Ikona webu (čtverec aspoň 512 × 512 px).', 'marmal-breakdance-plus'));
    }
    if (!nastaveni()['obrazek']) {
        $u[] = array('typ' => 'info', 'text' => __('Není nastavený výchozí obrázek pro sdílení – stránky bez náhledového obrázku se na Facebooku ukážou bez obrázku.', 'marmal-breakdance-plus'));
    }
    return $u;
}

// ================================================================= hodnoty stránky

/** Uložené hodnoty u příspěvku (nevyplněné = prázdné). */
function vlastni(int $post_id): array
{
    return array(
        'title'       => (string) get_post_meta($post_id, META_TITLE, true),
        'description' => (string) get_post_meta($post_id, META_DESC, true),
        'image_id'    => (int) get_post_meta($post_id, META_IMAGE, true),
        'noindex'     => (bool) get_post_meta($post_id, META_NOINDEX, true),
    );
}

/**
 * Hodnoty z jiných pluginů přes filtr `marmal_seo_data`.
 *   add_filter('marmal_seo_data', function (array $data, int $post_id) { $data['title'] = '…'; return $data; }, 10, 2);
 * Klíče: title (může obsahovat %web%, %oddelovac%), description, image_id, noindex.
 */
function z_filtru(int $post_id): array
{
    $vychozi = array('title' => '', 'description' => '', 'image_id' => 0, 'noindex' => false);
    $d       = apply_filters('marmal_seo_data', $vychozi, $post_id);
    $d       = is_array($d) ? $d : $vychozi;
    $img     = absint($d['image_id'] ?? 0);
    return array(
        'title'       => cisty_text($d['title'] ?? '', MAX_TITLE),
        'description' => cisty_text($d['description'] ?? '', MAX_DESC),
        'image_id'    => ($img && wp_attachment_is_image($img)) ? $img : 0,
        'noindex'     => !empty($d['noindex']),
    );
}

function nazev_prispevku(int $post_id): string
{
    return cisty_text(get_the_title($post_id), 200);
}

/** Titulek úvodní stránky bez vlastního textu: nastavení → „web | popis webu“ → web. */
function titulek_uvodu_auto(): string
{
    $s = nastaveni()['titulek_uvod'];
    if ($s !== '') {
        return $s;
    }
    return popis_webu() !== '' ? '%web% %oddelovac% %popis_webu%' : '%web%';
}

function je_uvod(int $post_id): bool
{
    if (get_option('show_on_front') !== 'page') {
        return false;
    }
    $uvod = (int) get_option('page_on_front');
    return $uvod === $post_id || ($uvod && in_array($post_id, preklady($uvod), true));
}

/**
 * Hodnoty příspěvku podle pořadí záloh. Vrací i „zálohy“ (co by se použilo bez vlastní hodnoty)
 * pro šedé texty v boxu a náhled.
 *
 * @return array{title:string,description:string,image_id:int,noindex:bool,zaloha:array,zdroj:array}
 */
function data_prispevku(int $post_id): array
{
    $jazyk = jazyky() ? jazyk_prispevku($post_id) : '';
    if ($jazyk === '') {
        return data_prispevku_v_jazyce($post_id);
    }
    $pred = vynuceny_jazyk();
    vynutit_jazyk($jazyk);
    try {
        return data_prispevku_v_jazyce($post_id);
    } finally {
        vynutit_jazyk($pred);
    }
}

function data_prispevku_v_jazyce(int $post_id): array
{
    $v = vlastni($post_id);
    $f = z_filtru($post_id);

    $nazev = nazev_prispevku($post_id);

    // Titulek
    $auto_title = je_uvod($post_id) ? titulek_uvodu_auto() : vzor();
    $zal_title  = $f['title'] !== '' ? $f['title'] : $auto_title;

    // Popisek: ruční výňatek (jen když je opravdu vyplněný) → výchozí popisek webu.
    $vynatek = has_excerpt($post_id) ? cisty_text(get_post_field('post_excerpt', $post_id), MAX_DESC) : '';
    $auto_desc = $vynatek !== '' ? $vynatek : nastaveni()['popis'];
    $zal_desc  = $f['description'] !== '' ? $f['description'] : $auto_desc;

    // Obrázek: náhledový obrázek → výchozí obrázek webu.
    $thumb    = (int) get_post_thumbnail_id($post_id);
    $auto_img = ($thumb && wp_attachment_is_image($thumb)) ? $thumb : (int) nastaveni()['obrazek'];
    $zal_img  = $f['image_id'] ?: $auto_img;

    $zaloha = array(
        'title'       => nahradit($zal_title, $nazev),
        'description' => nahradit($zal_desc, $nazev),
        'image_id'    => (int) $zal_img,
    );

    return array(
        'title'       => $v['title'] !== '' ? nahradit($v['title'], $nazev) : $zaloha['title'],
        'description' => $v['description'] !== '' ? nahradit($v['description'], $nazev) : $zaloha['description'],
        'image_id'    => ($v['image_id'] && wp_attachment_is_image($v['image_id'])) ? $v['image_id'] : $zaloha['image_id'],
        'noindex'     => $v['noindex'] || $f['noindex'],
        'zaloha'      => $zaloha,
        'zdroj'       => array(
            'title'       => $v['title'] !== '' ? 'vlastni' : ($f['title'] !== '' ? 'filtr' : 'auto'),
            'description' => $v['description'] !== '' ? 'vlastni' : ($f['description'] !== '' ? 'filtr' : 'auto'),
            'noindex_filtr' => $f['noindex'],
        ),
    );
}

/** Odkaz na stránku N archivu (bez parametrů z adresy). */
function strankovani(string $zaklad, int $n): string
{
    if ($n < 2 || $zaklad === '') {
        return $zaklad;
    }
    global $wp_rewrite;
    if ($wp_rewrite && $wp_rewrite->using_permalinks()) {
        return user_trailingslashit(trailingslashit($zaklad) . $wp_rewrite->pagination_base . '/' . $n, 'paged');
    }
    return add_query_arg('paged', $n, $zaklad);
}

/** Absolutní adresa se schématem webu (na HTTPS webu vždy https://). */
function absolutni(string $url): string
{
    if ($url === '') {
        return '';
    }
    if (strpos($url, '//') === 0) {
        $url = 'https:' . $url;
    }
    $schema = wp_parse_url(home_url('/'), PHP_URL_SCHEME) === 'https' ? 'https' : null;
    return $schema ? set_url_scheme($url, 'https') : $url;
}

/** og:locale (cs_CZ) z nastavení nebo jazyka stránky (Polylang / WPML mění locale podle jazyka). */
function locale_og(): string
{
    $s = nastaveni()['locale'];
    if ($s !== '') {
        return $s;
    }
    $l = (string) get_locale();
    if (preg_match('/^([a-z]{2,3})_([A-Z]{2})/', $l, $m)) {
        return $m[1] . '_' . $m[2];
    }
    $mapa = array('cs' => 'cs_CZ', 'en' => 'en_US', 'de' => 'de_DE', 'sk' => 'sk_SK', 'pl' => 'pl_PL', 'fr' => 'fr_FR', 'it' => 'it_IT', 'es' => 'es_ES', 'uk' => 'uk_UA', 'ru' => 'ru_RU');
    return $mapa[substr($l, 0, 2)] ?? 'cs_CZ';
}

/**
 * Všechny hodnoty pro aktuální stránku webu (volá se ve wp_head a pre_get_document_title).
 * null = stránka bez SEO výstupu (např. 404 má jen titulek).
 */
function kontext(): array
{
    static $cache = null;
    if (null !== $cache && !defined('MARMAL_SEO_TEST')) {
        return $cache;
    }
    $s      = nastaveni();
    $paged  = max((int) get_query_var('paged'), 1);
    $k      = array(
        'title'       => '',
        'description' => '',
        'image_id'    => 0,
        'canonical'   => '',
        'noindex'     => false,
        'og'          => true,
        'og_type'     => 'website',
        'post_id'     => 0,
    );
    $nazev  = '';
    $titulek = vzor();

    if (is_front_page() && !is_singular()) {
        // Úvod s posledními příspěvky.
        $titulek          = titulek_uvodu_auto();
        $k['description'] = $s['popis'];
        $k['image_id']    = (int) $s['obrazek'];
        $k['canonical']   = strankovani(uvod_url(), $paged);
    } elseif (is_singular() || is_home()) {
        $post_id = is_home() ? (int) get_option('page_for_posts') : (int) get_queried_object_id();
        if ($post_id) {
            $d                = data_prispevku($post_id);
            $k['post_id']     = $post_id;
            $titulek          = $d['title'];
            $k['description'] = $d['description'];
            $k['image_id']    = $d['image_id'];
            $k['noindex']     = $d['noindex'];
            $k['og_type']     = is_singular('post') ? 'article' : 'website';
            if (is_home()) {
                $k['canonical'] = strankovani((string) get_permalink($post_id), $paged);
            } else {
                $c              = wp_get_canonical_url($post_id);
                $k['canonical'] = $c ? (string) $c : '';
            }
            if (is_attachment() && $s['noindex_prilohy']) {
                $k['noindex'] = true;
            }
        } else {
            $titulek = $s['titulek_uvod'] !== '' ? $s['titulek_uvod'] : '%web%';
        }
    } elseif (is_search()) {
        /* translators: %s: hledaný výraz */
        $nazev        = sprintf(__('Výsledky hledání „%s“', 'marmal-breakdance-plus'), cisty_text(get_search_query(false), 60));
        $k['noindex'] = true;
        $k['og']      = false;
    } elseif (is_404()) {
        $nazev   = __('Stránka nenalezena', 'marmal-breakdance-plus');
        $k['og'] = false;
    } elseif (is_category() || is_tag() || is_tax()) {
        $term             = get_queried_object();
        $nazev            = $term ? cisty_text($term->name, 200) : '';
        $popis            = $term ? cisty_text(term_description($term), MAX_DESC) : '';
        $k['description'] = $popis !== '' ? $popis : $s['popis'];
        $k['image_id']    = (int) $s['obrazek'];
        $odkaz            = $term ? get_term_link($term) : '';
        $k['canonical']   = is_string($odkaz) ? strankovani($odkaz, $paged) : '';
        $k['noindex']     = is_tag() && $s['noindex_stitky'];
    } elseif (is_post_type_archive()) {
        $pt               = get_query_var('post_type');
        $pt               = is_array($pt) ? reset($pt) : $pt;
        $nazev            = cisty_text(post_type_archive_title('', false), 200);
        $k['description'] = $s['popis'];
        $k['image_id']    = (int) $s['obrazek'];
        $odkaz            = $pt ? get_post_type_archive_link($pt) : '';
        $k['canonical']   = $odkaz ? strankovani($odkaz, $paged) : '';
    } elseif (is_author()) {
        $autor            = get_queried_object();
        $nazev            = $autor && isset($autor->display_name) ? cisty_text($autor->display_name, 100) : '';
        $k['description'] = $s['popis'];
        $k['image_id']    = (int) $s['obrazek'];
        $k['canonical']   = $autor ? strankovani(get_author_posts_url((int) $autor->ID), $paged) : '';
        $k['noindex']     = (bool) $s['noindex_autor'];
    } elseif (is_date()) {
        if (is_day()) {
            $nazev = get_the_date();
        } elseif (is_month()) {
            $nazev = get_the_date('F Y');
        } else {
            $nazev = get_the_date('Y');
        }
        $nazev            = cisty_text($nazev, 60);
        $k['description'] = $s['popis'];
        $k['noindex']     = (bool) $s['noindex_datum'];
        $k['og']          = false;
    } else {
        $titulek = '%web%';
    }

    $k['title'] = nahradit($titulek, $nazev);
    // Další strany archivu: „… | Strana 2“ (jinak by měly stejný titulek jako první).
    if ($paged > 1 && !is_singular()) {
        /* translators: %d: číslo strany */
        $k['title'] .= ' ' . oddelovac() . ' ' . sprintf(__('Strana %d', 'marmal-breakdance-plus'), $paged);
    }
    if ($k['title'] === '') {
        $k['title'] = nazev_webu();
    }
    $cache = $k;
    return $k;
}
