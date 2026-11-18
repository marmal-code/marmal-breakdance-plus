<?php
/**
 * Modul Patička – nastavení a výpočet hodnot (bez HTML).
 *
 * Vše je v jedné option `marmal_bdp_paticka`. Prázdné textové pole = výchozí text
 * z kódu pluginu (přeložitelný) – změna výchozího textu v pluginu se tak po
 * aktualizaci projeví na všech webech, které ho nepřepsaly.
 */

namespace MarmalElements\Paticka;

if (!defined('ABSPATH')) {
    exit;
}

const OPTION = 'marmal_bdp_paticka';

/** Režimy odkazu „Nastavení cookies“ (pevný seznam). */
function cookies_rezimy(): array
{
    return array(
        'auto'      => __('Automaticky podle cookie pluginu na webu', 'marmal-breakdance-plus'),
        'marmal'    => __('MarMal – Cookies (modul tohoto pluginu)', 'marmal-breakdance-plus'),
        'complianz' => 'Complianz',
        'cookieyes' => 'CookieYes',
        'cookiebot' => 'Cookiebot',
        'trida'     => __('Jiný plugin – vlastní CSS třída', 'marmal-breakdance-plus'),
        'ne'        => __('Nezobrazovat', 'marmal-breakdance-plus'),
    );
}

/** Prázdné nastavení (všechny klíče a jejich typ). */
function prazdne(): array
{
    return array(
        'rok'            => 0,
        'drzitel'        => '',
        'autor_zobrazit' => true,
        'autor_pred'     => '',
        'autor_odkaz'    => '',
        'autor_url'      => '',
        'utm'            => true,
        'firma'          => '',
        'sidlo'          => '',
        'ico'            => '',
        'dic'            => '',
        'rejstrik'       => '',
        'zasady_stranka' => 0,
        'zasady_text'    => '',
        'cookies'        => 'auto',
        'cookies_trida'  => '',
        'cookies_text'   => '',
    );
}

/** Výchozí texty (použijí se, když pole v nastavení zůstane prázdné). */
function vychozi_texty(): array
{
    return array(
        'autor_pred'   => __('Web vytvořil Martin Malý –', 'marmal-breakdance-plus'),
        'autor_odkaz'  => 'marmal.cz',
        'autor_url'    => 'https://marmal.cz/',
        'zasady_text'  => __('Ochrana osobních údajů', 'marmal-breakdance-plus'),
        'cookies_text' => __('Nastavení cookies', 'marmal-breakdance-plus'),
    );
}

/** Letošní rok v časovém pásmu webu. */
function letos(): int
{
    return (int) wp_date('Y');
}

/**
 * Návrh roku spuštění: rok nejstarší publikované stránky nebo příspěvku.
 * U převzatého webu (např. z Divi) ho Martin v nastavení přepíše na rok původního webu.
 */
function rok_navrh(): int
{
    $ids = get_posts(array(
        'post_type'        => array('page', 'post'),
        'post_status'      => 'publish',
        'orderby'          => 'date',
        'order'            => 'ASC',
        'numberposts'      => 1,
        'fields'           => 'ids',
        'suppress_filters' => true,
        'no_found_rows'    => true,
    ));
    $rok = $ids ? (int) get_the_date('Y', (int) $ids[0]) : 0;
    return ($rok >= 1990 && $rok <= letos()) ? $rok : letos();
}

/**
 * Uložené nastavení doplněné o chybějící klíče.
 * Když option ještě neexistuje, předvyplní rok spuštění a uloží se (rok se pak sám nemění).
 */
function nastaveni(): array
{
    // get_option má vlastní cache (autoload), proto tu žádná další není.
    $ulozene = get_option(OPTION, null);
    if (!is_array($ulozene)) {
        $ulozene        = prazdne();
        $ulozene['rok'] = rok_navrh();
        add_option(OPTION, $ulozene, '', true);
    }
    return array_merge(prazdne(), array_intersect_key($ulozene, prazdne()));
}

/**
 * Hodnota textového pole: uložená (přeložená přes Polylang / WPML), jinak výchozí.
 */
function text(string $klic): string
{
    $s       = nastaveni();
    $hodnota = isset($s[$klic]) ? trim((string) $s[$klic]) : '';
    if ($hodnota === '') {
        $vychozi = vychozi_texty();
        return $vychozi[$klic] ?? '';
    }
    return preklad($klic, $hodnota);
}

/** Vlastní texty, které jdou na vícejazyčném webu přeložit (Polylang / WPML). */
function prekladane_klice(): array
{
    return array('drzitel', 'autor_pred', 'autor_odkaz', 'rejstrik', 'zasady_text', 'cookies_text');
}

function preklad(string $klic, string $hodnota): string
{
    if (!in_array($klic, prekladane_klice(), true)) {
        return $hodnota;
    }
    if (function_exists('pll__')) {
        return (string) pll__($hodnota);
    }
    return (string) apply_filters('wpml_translate_single_string', $hodnota, 'Marmal – Patička', $klic);
}

// ============================================================= copyright

function rok_od(): int
{
    $rok = (int) nastaveni()['rok'];
    return $rok >= 1990 ? $rok : letos();
}

/** „2019–2026“, nebo jen „2026“, když web vznikl letos. Pomlčka bez mezer (česká typografie). */
function roky(): string
{
    $od    = rok_od();
    $letos = letos();
    return $od >= $letos ? (string) $letos : $od . '–' . $letos;
}

function drzitel(): string
{
    $d = text('drzitel');
    return $d !== '' ? $d : wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
}

/** „© 2019–2026 Název“ (neescapovaný text). */
function copyright(bool $sDrzitelem = true): string
{
    $txt = '© ' . roky();
    if ($sDrzitelem && drzitel() !== '') {
        $txt .= ' ' . drzitel();
    }
    return $txt;
}

// ============================================================= autor webu

function autor_zobrazit(): bool
{
    return !empty(nastaveni()['autor_zobrazit']);
}

/** Doména webu bez www (pro utm_source). */
function domena(): string
{
    $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    return (string) preg_replace('/^www\./i', '', strtolower($host));
}

/** Adresa odkazu autora včetně UTM parametrů (neescapovaná). */
function autor_url(): string
{
    $url = text('autor_url');
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return '';
    }
    if (!empty(nastaveni()['utm'])) {
        $url = add_query_arg(
            array(
                'utm_source'   => rawurlencode(domena()),
                'utm_medium'   => 'referral',
                'utm_campaign' => 'podpis-webu',
                'utm_content'  => 'paticka',
            ),
            $url
        );
    }
    return $url;
}

/** Celý podpis jako prostý text: „Web vytvořil Martin Malý – marmal.cz“. */
function autor_text(): string
{
    return trim(text('autor_pred') . ' ' . text('autor_odkaz'));
}

/**
 * rel odkazu autora: na úvodní stránce běžný odkaz, jinde nofollow
 * (Google nemá rád hromadné odkazy v patičkách cizích webů).
 */
function autor_rel(): string
{
    return is_front_page() ? 'noopener' : 'nofollow noopener';
}

// ============================================================= údaje o podnikateli (§ 435 OZ)

/** IČO – kontrolní číslice (modulo 11). */
function ico_platne(string $ico): bool
{
    if (!preg_match('/^\d{8}$/', $ico)) {
        return false;
    }
    $soucet = 0;
    for ($i = 0; $i < 7; $i++) {
        $soucet += (int) $ico[$i] * (8 - $i);
    }
    return (int) $ico[7] === (11 - ($soucet % 11)) % 10;
}

/**
 * Řádky údajů o podnikateli v pořadí firma, sídlo, IČO, DIČ, zápis (jen vyplněné).
 *
 * @return array<string,string> klíč => text (neescapovaný)
 */
function udaje(): array
{
    $s = nastaveni();
    $r = array();
    if ($s['firma'] !== '') {
        $r['firma'] = $s['firma'];
    }
    if ($s['sidlo'] !== '') {
        /* translators: %s: adresa sídla */
        $r['sidlo'] = sprintf(__('Sídlo: %s', 'marmal-breakdance-plus'), $s['sidlo']);
    }
    if ($s['ico'] !== '') {
        /* translators: %s: IČO */
        $r['ico'] = sprintf(__('IČO: %s', 'marmal-breakdance-plus'), $s['ico']);
    }
    if ($s['dic'] !== '') {
        /* translators: %s: DIČ */
        $r['dic'] = sprintf(__('DIČ: %s', 'marmal-breakdance-plus'), $s['dic']);
    }
    $zapis = text('rejstrik');
    if ($zapis !== '') {
        $r['rejstrik'] = $zapis;
    }
    return $r;
}

function udaje_text(string $oddelovac = ' · '): string
{
    return implode($oddelovac, udaje());
}

// ============================================================= odkazy

/** Adresa stránky se zásadami: vybraná stránka (přeložená), jinak stránka z Nastavení → Soukromí. */
function zasady_url(): string
{
    $id = (int) nastaveni()['zasady_stranka'];
    if ($id > 0) {
        if (function_exists('pll_get_post')) {
            $id = (int) (pll_get_post($id) ?: $id);
        }
        $id = (int) apply_filters('wpml_object_id', $id, 'page', true);
        if ($id > 0 && get_post_status($id) === 'publish') {
            return (string) get_permalink($id);
        }
    }
    return (string) get_privacy_policy_url();
}

/** Je na webu aktivní plugin z dané složky? (bez načítání wp-admin/includes/plugin.php) */
function plugin_aktivni(array $slozky): bool
{
    $aktivni = array_merge(
        (array) get_option('active_plugins', array()),
        array_keys((array) get_site_option('active_sitewide_plugins', array()))
    );
    foreach ($aktivni as $plugin) {
        $slozka = strtok((string) $plugin, '/');
        if (in_array($slozka, $slozky, true)) {
            return true;
        }
    }
    return false;
}

/** Skutečný režim odkazu cookies ('auto' se převede podle pluginu na webu). */
function cookies_rezim(): string
{
    $r = (string) nastaveni()['cookies'];
    if ($r !== 'auto') {
        return array_key_exists($r, cookies_rezimy()) ? $r : 'ne';
    }
    // Vlastní modul Cookies má přednost (běží-li, ostatní cookie pluginy mají být vypnuté).
    if (function_exists('MarmalElements\Cookies\je_aktivni')) {
        return 'marmal';
    }
    if (plugin_aktivni(array('complianz-gdpr', 'complianz-gdpr-premium'))) {
        return 'complianz';
    }
    if (plugin_aktivni(array('cookie-law-info'))) {
        return 'cookieyes';
    }
    if (plugin_aktivni(array('cookiebot'))) {
        return 'cookiebot';
    }
    return 'ne';
}

/** CSS třída, na kterou reaguje cookie plugin (otevře lištu / předvolby). */
function cookies_trida(string $rezim): string
{
    switch ($rezim) {
        case 'marmal':
            return 'marmal-cookies-nastaveni';
        case 'complianz':
            return 'cmplz-show-banner';
        case 'cookieyes':
            return 'cky-banner-element';
        case 'trida':
            return sanitize_html_class((string) nastaveni()['cookies_trida']);
        default:
            return '';
    }
}

// ============================================================= sanitizace (register_setting)

function delka(string $s, int $max): string
{
    return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
}

/**
 * Sanitizace nastavení z formuláře. Volá ji WordPress (options.php → register_setting,
 * oprávnění manage_options a nonce kontroluje WordPress; options.php hodnoty už odslashoval).
 *
 * @param mixed $vstup
 */
function sanitizovat($vstup): array
{
    $vstup = is_array($vstup) ? $vstup : array();
    $c     = prazdne();
    $txt   = function (string $klic, int $max) use ($vstup): string {
        return isset($vstup[$klic]) ? delka(sanitize_text_field((string) $vstup[$klic]), $max) : '';
    };

    // Rok: 1990 až letos; prázdný = nový návrh podle nejstarší stránky.
    $rok = isset($vstup['rok']) ? absint($vstup['rok']) : 0;
    if ($rok < 1990 || $rok > letos()) {
        if ($rok !== 0) {
            add_settings_error(OPTION, 'rok', __('Rok spuštění musí být mezi 1990 a letoškem – použil se návrh podle nejstarší stránky webu.', 'marmal-breakdance-plus'), 'warning');
        }
        $rok = rok_navrh();
    }
    $c['rok'] = $rok;

    $c['drzitel']        = $txt('drzitel', 120);
    $c['autor_zobrazit'] = !empty($vstup['autor_zobrazit']);
    $c['autor_pred']     = $txt('autor_pred', 120);
    $c['autor_odkaz']    = $txt('autor_odkaz', 80);
    $c['utm']            = !empty($vstup['utm']);

    $url = isset($vstup['autor_url']) ? esc_url_raw(trim((string) $vstup['autor_url']), array('http', 'https')) : '';
    if ($url === '' && !empty($vstup['autor_url'])) {
        add_settings_error(OPTION, 'autor_url', __('Adresa odkazu autora musí začínat https:// – použije se výchozí.', 'marmal-breakdance-plus'), 'warning');
    }
    $c['autor_url'] = delka($url, 300);

    $c['firma'] = $txt('firma', 160);
    $c['sidlo'] = $txt('sidlo', 200);

    // IČO: jen číslice, kratší doplnit nulami zleva (stará IČO), ověřit kontrolní číslici.
    $ico = isset($vstup['ico']) ? preg_replace('/\D/', '', (string) $vstup['ico']) : '';
    if ($ico !== '' && strlen($ico) <= 8) {
        $ico = str_pad($ico, 8, '0', STR_PAD_LEFT);
        if (!ico_platne($ico)) {
            add_settings_error(OPTION, 'ico', __('IČO nemá platnou kontrolní číslici – zkontroluj ho prosím (uloženo i tak).', 'marmal-breakdance-plus'), 'warning');
        }
    } elseif ($ico !== '') {
        add_settings_error(OPTION, 'ico', __('IČO má 8 číslic – neuloženo.', 'marmal-breakdance-plus'), 'error');
        $ico = '';
    }
    $c['ico'] = $ico;

    // DIČ: velká písmena bez mezer, kód země + 2–12 znaků (CZ12345678).
    $dic = isset($vstup['dic']) ? strtoupper(preg_replace('/[\s.\-]/', '', (string) $vstup['dic'])) : '';
    if ($dic !== '' && !preg_match('/^[A-Z]{2}[0-9A-Z]{2,12}$/', $dic)) {
        add_settings_error(OPTION, 'dic', __('DIČ musí mít tvar např. CZ12345678 – neuloženo.', 'marmal-breakdance-plus'), 'error');
        $dic = '';
    }
    $c['dic'] = $dic;

    $c['rejstrik'] = $txt('rejstrik', 300);

    $stranka = isset($vstup['zasady_stranka']) ? absint($vstup['zasady_stranka']) : 0;
    $c['zasady_stranka'] = ($stranka > 0 && get_post_type($stranka) === 'page') ? $stranka : 0;
    $c['zasady_text']    = $txt('zasady_text', 80);

    $rezim            = isset($vstup['cookies']) ? sanitize_key((string) $vstup['cookies']) : 'auto';
    $c['cookies']     = array_key_exists($rezim, cookies_rezimy()) ? $rezim : 'auto';
    $c['cookies_trida'] = isset($vstup['cookies_trida']) ? delka(sanitize_html_class((string) $vstup['cookies_trida']), 60) : '';
    $c['cookies_text']  = $txt('cookies_text', 80);

    registrovat_preklady($c);
    return $c;
}

/** Zaregistruje vlastní texty pro překlad ve WPML (Polylang je registruje na init). */
function registrovat_preklady(array $s): void
{
    foreach (prekladane_klice() as $klic) {
        if (!empty($s[$klic])) {
            do_action('wpml_register_single_string', 'Marmal – Patička', $klic, $s[$klic]);
        }
    }
}
