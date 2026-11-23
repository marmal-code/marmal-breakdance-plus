<?php
/**
 * Modul Cookies – nastavení, kategorie, registr služeb a sanitizace (bez HTML).
 *
 * Nastavení: option marmal_bdp_cookies (register_setting, MarMal → Cookies).
 * Výchozí hodnoty = Martinův schválený prototyp (artefakt „Cookies – prototyp modulu“, 2026-11-18).
 * Prázdné textové pole = výchozí text z pluginu (přeložitelný) – změna textu v pluginu se po
 * aktualizaci projeví na všech webech, které ho nepřepsaly.
 */

namespace MarmalElements\Cookies;

if (!defined('ABSPATH')) {
    exit;
}

const OPTION       = 'marmal_bdp_cookies';
const OPTION_NALEZ = 'marmal_bdp_cookies_nalezeno'; // služby, které blokování našlo na webu
const COOKIE       = 'marmal_consent';
const OPTION_GFONTY = 'marmal_bdp_cookies_gfonty'; // kdy naposledy stránka načítala fonty z Googlu
const MAX_MTEXT    = 160;                         // krátký text kompaktní lišty na mobilu (znaků)
const OPTION_ODKAZ  = 'marmal_bdp_cookies_odkaz';  // kdy naposledy byl na stránce odkaz „Nastavení cookies“

/** Kategorie v pevném pořadí. id => [název, zamčená] */
function kategorie_zaklad(): array
{
    return array(
        'nezbytne'     => array(__('Nezbytné', 'marmal-breakdance-plus'), true),
        'preferencni'  => array(__('Preferenční', 'marmal-breakdance-plus'), false),
        'analyticke'   => array(__('Analytické', 'marmal-breakdance-plus'), false),
        'marketingove' => array(__('Marketingové', 'marmal-breakdance-plus'), false),
    );
}

function popis_vychozi(string $kat): string
{
    $p = array(
        'nezbytne'     => __('Zajišťují základní funkce webu, například zapamatování vaší volby cookies. Bez nich web nefunguje, proto je nelze vypnout.', 'marmal-breakdance-plus'),
        'preferencni'  => __('Pamatují si vaše volby, například jazyk webu.', 'marmal-breakdance-plus'),
        'analyticke'   => __('Pomáhají nám zjistit, kolik lidí web navštěvuje a co je zajímá (Google Analytics). Údaje vyhodnocujeme souhrnně.', 'marmal-breakdance-plus'),
        'marketingove' => __('Umožňují přehrát vložená videa a mapy třetích stran a měřit účinnost reklamy. Poskytovatelé (např. Google) mohou údaje použít i na jiných webech.', 'marmal-breakdance-plus'),
    );
    return $p[$kat] ?? '';
}

/**
 * Registr známých služeb.
 * cookies: [název (* = libovolné pokračování), poskytovatel / doména, účel, platnost]
 * skript / iframe: části adres, podle kterých je blokování pozná; inline: regulární výrazy pro vložené skripty.
 * auto: callback – služba je na webu jistě (není třeba ji zapínat ručně).
 */
function sluzby(): array
{
    $google = 'Google Ireland Ltd.';
    return array(
        'marmal'      => array(
            'nazev' => __('Volba cookies (tento web)', 'marmal-breakdance-plus'), 'kat' => 'nezbytne',
            'cookies' => array(array(COOKIE, __('tento web', 'marmal-breakdance-plus'), __('Uloží vaši volbu cookies a datum souhlasu', 'marmal-breakdance-plus'), 'platnost_souhlasu')),
            'auto' => '__return_true',
        ),
        'wordpress'   => array(
            'nazev' => 'WordPress', 'kat' => 'nezbytne',
            'cookies' => array(array('wordpress_logged_in_*', __('tento web', 'marmal-breakdance-plus'), __('Přihlášení do administrace (jen správci webu)', 'marmal-breakdance-plus'), __('relace', 'marmal-breakdance-plus'))),
            'auto' => '__return_true',
        ),
        'woocommerce' => array(
            'nazev' => 'WooCommerce', 'kat' => 'nezbytne',
            'cookies' => array(
                array('woocommerce_cart_hash', __('tento web', 'marmal-breakdance-plus'), __('Obsah košíku', 'marmal-breakdance-plus'), __('relace', 'marmal-breakdance-plus')),
                array('wp_woocommerce_session_*', __('tento web', 'marmal-breakdance-plus'), __('Nákupní relace', 'marmal-breakdance-plus'), __('2 dny', 'marmal-breakdance-plus')),
            ),
            'auto' => function () {
                return class_exists('WooCommerce');
            },
        ),
        'polylang'    => array(
            'nazev' => 'Polylang', 'kat' => 'preferencni',
            'cookies' => array(array('pll_language', __('tento web', 'marmal-breakdance-plus'), __('Zapamatuje zvolený jazyk webu', 'marmal-breakdance-plus'), __('1 rok', 'marmal-breakdance-plus'))),
            'auto' => function () {
                return function_exists('pll_current_language');
            },
        ),
        // Google Ads před GA4: v blokování vyhrává první shoda (gtag.js s ID AW- = marketingové).
        'gads'        => array(
            'posk' => 'google',
            'nazev' => 'Google Ads', 'kat' => 'marketingove',
            'cookies' => array(
                array('_gcl_au', 'Google Ads (' . $google . ')', __('Měření konverzí z reklam Google', 'marmal-breakdance-plus'), __('3 měsíce', 'marmal-breakdance-plus')),
                array('IDE', 'Google (' . $google . ', doubleclick.net)', __('Cílení a vyhodnocení reklamy', 'marmal-breakdance-plus'), __('13 měsíců', 'marmal-breakdance-plus')),
            ),
            'skript' => array('googleadservices.com/pagead/conversion', 'googletagmanager.com/gtag/js?id=AW-'),
            'inline' => array('/gtag\s*\(\s*[\'"]config[\'"]\s*,\s*[\'"]AW-/'),
            'auto'   => function () {
                return merici_id('gads') !== '';
            },
        ),
        'ga4'         => array(
            'posk' => 'google',
            'nazev' => 'Google Analytics 4', 'kat' => 'analyticke',
            'cookies' => array(
                array('_ga', 'Google Analytics (' . $google . ')', __('Rozlišuje jednotlivé návštěvníky pro statistiky', 'marmal-breakdance-plus'), 'platnost_ga'),
                array('_ga_*', 'Google Analytics (' . $google . ')', __('Uchovává stav návštěvy', 'marmal-breakdance-plus'), 'platnost_ga'),
            ),
            'skript' => array('googletagmanager.com/gtag/js', 'google-analytics.com/analytics.js', 'google-analytics.com/ga.js'),
            'inline' => array('/gtag\s*\(\s*[\'"]config[\'"]\s*,\s*[\'"](G|UA)-/', '/GoogleAnalyticsObject/'),
            'auto' => function () {
                return ga_id() !== '';
            },
        ),
        'hotjar'      => array(
            'posk' => 'hotjar',
            'nazev' => 'Hotjar', 'kat' => 'analyticke',
            'cookies' => array(
                array('_hjSessionUser_*', 'Hotjar Ltd.', __('Rozlišuje návštěvníky pro analýzu chování', 'marmal-breakdance-plus'), __('1 rok', 'marmal-breakdance-plus')),
                array('_hjSession_*', 'Hotjar Ltd.', __('Data aktuální návštěvy', 'marmal-breakdance-plus'), __('30 minut', 'marmal-breakdance-plus')),
            ),
            'skript' => array('static.hotjar.com', 'hj.contentsquare.net'),
            'inline' => array('/static\.hotjar\.com/', '/hj\.contentsquare\.net/', '/_hjSettings/'),
            'auto'   => function () {
                return merici_id('hotjar') !== '';
            },
        ),
        'clarity'     => array(
            'posk' => 'microsoft',
            'nazev' => 'Microsoft Clarity', 'kat' => 'analyticke',
            'cookies' => array(
                array('_clck', 'Microsoft Corporation', __('Rozlišuje návštěvníky pro analýzu chování', 'marmal-breakdance-plus'), __('1 rok', 'marmal-breakdance-plus')),
                array('_clsk', 'Microsoft Corporation', __('Spojuje zobrazení stránek jedné návštěvy', 'marmal-breakdance-plus'), __('1 den', 'marmal-breakdance-plus')),
            ),
            'skript' => array('clarity.ms/tag'),
            'inline' => array('/clarity\.ms\/tag/'),
            'auto'   => function () {
                return merici_id('clarity') !== '';
            },
        ),
        'meta'        => array(
            'posk' => 'meta',
            'nazev' => 'Meta Pixel', 'kat' => 'marketingove',
            'cookies' => array(
                array('_fbp', 'Meta Platforms Ireland Ltd.', __('Měření a cílení reklamy na Facebooku a Instagramu', 'marmal-breakdance-plus'), __('3 měsíce', 'marmal-breakdance-plus')),
                array('fr', 'Meta Platforms Ireland Ltd. (facebook.com)', __('Cílení a vyhodnocení reklamy', 'marmal-breakdance-plus'), __('3 měsíce', 'marmal-breakdance-plus')),
            ),
            'skript' => array('connect.facebook.net'),
            'inline' => array('/fbq\s*\(\s*[\'"]init[\'"]/', '/connect\.facebook\.net/'),
            'auto'   => function () {
                return merici_id('meta') !== '';
            },
        ),
        'sklik'       => array(
            'posk' => 'seznam',
            'nazev' => 'Sklik (Seznam.cz)', 'kat' => 'marketingove',
            'cookies' => array(
                array('sid', 'Seznam.cz, a.s.', __('Propojení návštěvy s reklamou Sklik a měření konverzí', 'marmal-breakdance-plus'), __('30 dní', 'marmal-breakdance-plus')),
                array('udid', 'Seznam.cz, a.s.', __('Identifikátor prohlížeče pro reklamu Sklik', 'marmal-breakdance-plus'), __('podle Seznam.cz', 'marmal-breakdance-plus')),
            ),
            'skript' => array('l.seznam.cz/sul.js', 'c.seznam.cz/js/rc.js', 'c.imedia.cz/js/retargeting.js'),
            'inline' => array('/retargetingHit/', '/conversionHit/', '/SEM\s*\(\s*[\'"]track/'),
            'auto'   => function () {
                return merici_id('sem') !== '';
            },
        ),
        'linkedin'    => array(
            'posk' => 'linkedin',
            'nazev' => 'LinkedIn Insight Tag', 'kat' => 'marketingove',
            'cookies' => array(
                array('li_fat_id', 'LinkedIn Ireland Unlimited Company', __('Měření konverzí z reklam na LinkedInu', 'marmal-breakdance-plus'), __('30 dní', 'marmal-breakdance-plus')),
                array('bcookie', 'LinkedIn (linkedin.com)', __('Identifikátor prohlížeče', 'marmal-breakdance-plus'), __('1 rok', 'marmal-breakdance-plus')),
                array('li_sugr', 'LinkedIn (linkedin.com)', __('Cílení reklamy', 'marmal-breakdance-plus'), __('3 měsíce', 'marmal-breakdance-plus')),
                array('UserMatchHistory', 'LinkedIn (linkedin.com)', __('Synchronizace reklamních identifikátorů', 'marmal-breakdance-plus'), __('30 dní', 'marmal-breakdance-plus')),
            ),
            'skript' => array('snap.licdn.com/li.lms-analytics'),
            'inline' => array('/_linkedin_partner_id/'),
            'auto'   => function () {
                return merici_id('linkedin') !== '';
            },
        ),
        'gtm'         => array(
            'posk' => 'google',
            'nazev' => 'Google Tag Manager', 'kat' => 'analyticke',
            'cookies' => array(),
            'skript' => array('googletagmanager.com/gtm.js'),
            'inline' => array('/gtm\.start/'),
            'auto'   => function () {
                return merici_id('gtm') !== '';
            },
        ),
        'youtube'     => array(
            'posk' => 'google',
            'nazev' => 'YouTube', 'kat' => 'marketingove', 'vlozeny' => __('Video z YouTube', 'marmal-breakdance-plus'),
            'cookies' => array(
                array('YSC', 'YouTube (' . $google . ')', __('Statistika přehrávání vloženého videa', 'marmal-breakdance-plus'), __('relace', 'marmal-breakdance-plus')),
                array('VISITOR_INFO1_LIVE', 'YouTube (' . $google . ')', __('Odhad rychlosti připojení pro přehrávání', 'marmal-breakdance-plus'), __('6 měsíců', 'marmal-breakdance-plus')),
            ),
            'skript' => array('youtube.com/iframe_api'),
            'iframe' => array('youtube.com/embed', 'youtube-nocookie.com/embed'),
        ),
        'vimeo'       => array(
            'posk' => 'vimeo',
            'nazev' => 'Vimeo', 'kat' => 'marketingove', 'vlozeny' => __('Video z Vimea', 'marmal-breakdance-plus'),
            'cookies' => array(array('vuid', 'Vimeo.com, Inc.', __('Statistika přehrávání vloženého videa', 'marmal-breakdance-plus'), __('2 roky', 'marmal-breakdance-plus'))),
            'skript' => array('player.vimeo.com/api'),
            'iframe' => array('player.vimeo.com'),
        ),
        'gmaps'       => array(
            'posk' => 'google',
            'nazev' => 'Google Mapy', 'kat' => 'marketingove', 'vlozeny' => __('Mapa Google', 'marmal-breakdance-plus'),
            'cookies' => array(array('NID', 'Google (' . $google . ', google.com)', __('Nastavení a měření vložené mapy', 'marmal-breakdance-plus'), __('6 měsíců', 'marmal-breakdance-plus'))),
            'skript' => array('maps.googleapis.com/maps/api/js'),
            'iframe' => array('google.com/maps', 'maps.google.'),
        ),
    );
}

/**
 * Poskytovatelé služeb třetích stran pro stránku Zásady cookies: id => [název, odkaz na zásady, zpracování i mimo EU].
 * Odkazy vedou na obecné zásady ochrany soukromí poskytovatele (nemění se s každou verzí).
 */
function poskytovatele(): array
{
    return array(
        'google'    => array('Google Ireland Limited (Google LLC)', 'https://policies.google.com/privacy?hl=cs', true),
        'meta'      => array('Meta Platforms Ireland Limited', 'https://www.facebook.com/privacy/policy/', true),
        'microsoft' => array('Microsoft Ireland Operations Limited', 'https://privacy.microsoft.com/cs-cz/privacystatement', true),
        'linkedin'  => array('LinkedIn Ireland Unlimited Company', 'https://www.linkedin.com/legal/privacy-policy', true),
        'hotjar'    => array('Hotjar Ltd. (Contentsquare)', 'https://www.hotjar.com/legal/policies/privacy/', true),
        'vimeo'     => array('Vimeo.com, Inc.', 'https://vimeo.com/privacy', true),
        'seznam'    => array('Seznam.cz, a.s.', 'https://www.seznam.cz/ochranaudaju', false),
    );
}

/**
 * Poskytovatelé, jejichž služby web právě používá: id => [název, odkaz, mimo EU, [názvy služeb]].
 */
function aktivni_poskytovatele(): array
{
    $vse  = sluzby();
    $info = poskytovatele();
    $out  = array();
    foreach (aktivni_sluzby() as $sid) {
        $p = $vse[$sid]['posk'] ?? '';
        if ($p === '' || !isset($info[$p])) {
            continue;
        }
        if (!isset($out[$p])) {
            $out[$p] = array($info[$p][0], $info[$p][1], $info[$p][2], array());
        }
        $out[$p][3][] = $vse[$sid]['nazev'];
    }
    return $out;
}

/** Výchozí nastavení (schválený prototyp). Prázdné texty = výchozí texty z texty_vychozi(). */
function vychozi(): array
{
    return array(
        'layout' => 'box', 'corner' => 'br', 'prefs' => 'mid',
        'barvy' => 'web', 'brand' => '#1f3a5f', 'bg' => '#ffffff', 'ink' => '#1c2430',
        'btn' => 'fill', 'radius' => 14, 'width' => 400, 'shadow' => 'soft', 'icon' => 'l', 'close' => true,
        'mobil' => 'lista', 'mBod' => 767, // od 0.17.0: rozložení na mobilu (lista | okno | stejne) a bod zlomu v px
        'mText' => '',                     // krátký text kompaktní lišty (prázdné = výchozí)
        'title' => '', 'text' => '', 'lAccept' => '', 'lReject' => '', 'lPrefs' => '', 'lMore' => '', 'pIntro' => '',
        'popis_nezbytne' => '', 'popis_preferencni' => '', 'popis_analyticke' => '', 'popis_marketingove' => '',
        'odkaz_stranka' => 0,
        'sluzby' => array(),
        'vlastni' => '',
        'ga' => '', 'gcm' => 'basic', 'gaShort' => true,
        'gtm' => '', 'gtm_rezim' => 'souhlas', 'gads' => '', 'sem' => '', 'meta' => '', 'clarity' => '', 'hotjar' => '', 'linkedin' => '',
        'valid' => 6, 'rev' => 1, 'uchovat' => 3,
    );
}

function texty_vychozi(): array
{
    return array(
        'title'   => __('Používáme cookies', 'marmal-breakdance-plus'),
        'text'    => __('Nezbytné cookies potřebujeme, aby web fungoval. Analytické a marketingové použijeme jen s vaším souhlasem – pomáhají nám web zlepšovat. Volbu můžete kdykoli změnit odkazem Nastavení cookies v patičce.', 'marmal-breakdance-plus'),
        'lAccept' => __('Přijmout vše', 'marmal-breakdance-plus'),
        'lReject' => __('Odmítnout vše', 'marmal-breakdance-plus'),
        'lPrefs'  => __('Nastavení', 'marmal-breakdance-plus'),
        'lMore'   => __('Více o cookies', 'marmal-breakdance-plus'),
        'mText'   => __('Používáme cookies. Analytické a marketingové jen s vaším souhlasem.', 'marmal-breakdance-plus'),
        'pIntro'  => __('Vyberte, které cookies smíme používat. Nezbytné jsou vždy zapnuté, ostatní jen s vaším souhlasem. Volbu můžete kdykoli změnit.', 'marmal-breakdance-plus'),
    );
}

function nastaveni(): array
{
    $s = get_option(OPTION, array());
    $s = is_array($s) ? $s : array();
    return array_merge(vychozi(), array_intersect_key($s, vychozi()));
}

/** Texty, které jde na vícejazyčném webu přeložit (Polylang / WPML). */
function prekladane_klice(): array
{
    return array('title', 'text', 'mText', 'lAccept', 'lReject', 'lPrefs', 'lMore', 'pIntro', 'popis_nezbytne', 'popis_preferencni', 'popis_analyticke', 'popis_marketingove');
}

function text(string $klic): string
{
    $s = nastaveni();
    $h = isset($s[$klic]) ? trim((string) $s[$klic]) : '';
    if ($h === '') {
        if (strpos($klic, 'popis_') === 0) {
            return popis_vychozi(substr($klic, 6));
        }
        return texty_vychozi()[$klic] ?? '';
    }
    if (function_exists('pll__')) {
        return (string) pll__($h);
    }
    return (string) apply_filters('wpml_translate_single_string', $h, 'Marmal – Cookies', $klic);
}

function ga_id(): string
{
    $id = strtoupper(trim((string) nastaveni()['ga']));
    return preg_match('/^G-[A-Z0-9]{4,20}$/', $id) ? $id : '';
}

/**
 * Měřicí kódy (od 0.12.0): klíč nastavení => [popis tvaru, regex, převod].
 * Regex hlídá, že ID je jen to, co má být – do HTML/JS se nikdy nedostane nic jiného.
 */
function merici_vzory(): array
{
    return array(
        'gtm'      => array('GTM-XXXXXXX', '/^GTM-[A-Z0-9]{4,12}$/', 'upper'),
        'gads'     => array('AW-123456789', '/^AW-[0-9]{6,15}$/', 'upper'),
        'sem'      => array(__('SEM ID ze Skliku', 'marmal-breakdance-plus'), '/^[A-Za-z0-9_-]{3,64}$/', ''),
        'meta'     => array('123456789012345', '/^[0-9]{8,20}$/', ''),
        'clarity'  => array('abcd1234ef', '/^[a-z0-9]{6,20}$/', 'lower'),
        'hotjar'   => array('1234567', '/^[0-9]{4,12}$/', ''),
        'linkedin' => array('1234567', '/^[0-9]{3,12}$/', ''),
    );
}

function merici_nazev(string $klic): string
{
    $n = array(
        'gtm' => 'Google Tag Manager', 'gads' => 'Google Ads', 'sem' => 'Sklik (Seznam Event Measurement)', 'meta' => 'Meta Pixel',
        'clarity' => 'Microsoft Clarity', 'hotjar' => 'Hotjar', 'linkedin' => 'LinkedIn Insight Tag',
    );
    return $n[$klic] ?? $klic;
}

function merici_id(string $klic): string
{
    $vzory = merici_vzory();
    if (!isset($vzory[$klic])) {
        return '';
    }
    $id = trim((string) (nastaveni()[$klic] ?? ''));
    $id = $vzory[$klic][2] === 'upper' ? strtoupper($id) : ($vzory[$klic][2] === 'lower' ? strtolower($id) : $id);
    return preg_match($vzory[$klic][1], $id) ? $id : '';
}

/** Platnost GA cookies v sekundách (13 měsíců ≈ 395 dní) nebo null = výchozí Google (2 roky). */
function ga_expirace(): ?int
{
    return !empty(nastaveni()['gaShort']) ? 395 * DAY_IN_SECONDS : null;
}

function platnost_souhlasu_dny(): int
{
    $m = (int) nastaveni()['valid'];
    return in_array($m, array(3, 6, 12), true) ? (int) round($m * 30.5) : 183;
}

/** Popis platnosti pro tabulku cookies (zástupné hodnoty v registru). */
function platnost_text(string $p): string
{
    if ($p === 'platnost_souhlasu') {
        /* translators: %d: počet měsíců */
        return sprintf(_n('%d měsíc', '%d měsíců', (int) nastaveni()['valid'], 'marmal-breakdance-plus'), (int) nastaveni()['valid']);
    }
    if ($p === 'platnost_ga') {
        return ga_expirace() ? __('13 měsíců', 'marmal-breakdance-plus') : __('2 roky', 'marmal-breakdance-plus');
    }
    return $p;
}

/**
 * Služby aktivní na webu: automaticky poznané + zapnuté ručně + nalezené blokováním.
 *
 * @return string[] id služeb
 */
function aktivni_sluzby(): array
{
    $s     = nastaveni();
    $vse   = sluzby();
    $out   = array();
    $rucne = array_map('strval', (array) $s['sluzby']);
    $nalez = (array) get_option(OPTION_NALEZ, array());
    foreach ($vse as $id => $sl) {
        $auto = isset($sl['auto']) && is_callable($sl['auto']) && call_user_func($sl['auto']);
        if ($auto || in_array($id, $rucne, true) || isset($nalez[$id])) {
            $out[] = $id;
        }
    }
    return $out;
}

/**
 * Vlastní cookies z textového pole: řádek „název | poskytovatel | účel | platnost | kategorie“.
 *
 * @return array<int,array{0:string,1:string,2:string,3:string,4:string}>
 */
function vlastni_cookies(): array
{
    $out = array();
    foreach (preg_split('/\r\n|\r|\n/', (string) nastaveni()['vlastni']) as $radek) {
        $c = array_map('trim', explode('|', $radek));
        if (count($c) < 5 || $c[0] === '' || !array_key_exists($c[4], kategorie_zaklad())) {
            continue;
        }
        $out[] = array_slice($c, 0, 5);
    }
    return $out;
}

/**
 * Kategorie použité na webu včetně cookies (pro lištu i tabulku).
 * Kategorie bez jediné cookie se nezobrazí (kromě nezbytných).
 *
 * @return array<int,array{id:string,nazev:string,popis:string,zamceno:bool,cookies:array}>
 */
function kategorie(): array
{
    $vse     = sluzby();
    $cookies = array_fill_keys(array_keys(kategorie_zaklad()), array());
    foreach (aktivni_sluzby() as $id) {
        foreach ($vse[$id]['cookies'] as $c) {
            $cookies[$vse[$id]['kat']][] = array($c[0], $c[1], $c[2], platnost_text($c[3]));
        }
    }
    foreach (vlastni_cookies() as $c) {
        $cookies[$c[4]][] = array($c[0], $c[1], $c[2], $c[3]);
    }
    $out = array();
    foreach (kategorie_zaklad() as $id => $k) {
        if (!$k[1] && !$cookies[$id]) {
            continue;
        }
        $out[] = array('id' => $id, 'nazev' => $k[0], 'popis' => text('popis_' . $id), 'zamceno' => $k[1], 'cookies' => $cookies[$id]);
    }
    return $out;
}

/** Odkaz „Více o cookies“: vybraná stránka → stránka zásad z Patičky → Nastavení → Soukromí. */
/** Adresy, podle kterých se stránka Zásady cookies najde sama (když není vybraná v nastavení). */
function slugy_zasad(): array
{
    return array('zasady-cookies', 'zasady-pouzivani-cookies', 'cookies');
}

/** ID stránky Zásady cookies (i koncept): vybraná v nastavení, jinak podle adresy. 0 = žádná. */
function stranka_zasad(): int
{
    $id = (int) nastaveni()['odkaz_stranka'];
    if ($id > 0 && get_post_type($id) === 'page' && in_array(get_post_status($id), array('publish', 'draft', 'pending', 'private'), true)) {
        return $id;
    }
    foreach (slugy_zasad() as $slug) {
        $p = get_page_by_path($slug);
        if ($p && in_array($p->post_status, array('publish', 'draft', 'pending'), true)) {
            return (int) $p->ID;
        }
    }
    return 0;
}

/** Adresa stránky Zásady cookies v aktuálním jazyce, jen když je publikovaná. '' = žádná. */
function zasady_url(): string
{
    $id = stranka_zasad();
    if ($id <= 0) {
        return '';
    }
    if (function_exists('pll_get_post')) {
        $id = (int) (pll_get_post($id) ?: $id);
    }
    $id = (int) apply_filters('wpml_object_id', $id, 'page', true);
    return ($id > 0 && get_post_status($id) === 'publish') ? (string) get_permalink($id) : '';
}

/** Odkaz „Více o cookies“: Zásady cookies, jinak zásady ochrany osobních údajů (Patička / Nastavení → Soukromí). */
function odkaz_vice(): string
{
    $u = zasady_url();
    if ($u !== '') {
        return $u;
    }
    if (function_exists('MarmalElements\Paticka\zasady_url')) {
        return (string) \MarmalElements\Paticka\zasady_url();
    }
    return (string) get_privacy_policy_url();
}

/** Jiné cookie pluginy, které na webu běží (dvě lišty = problém). */
function konkurence(): array
{
    $znam = array(
        'cookie-law-info' => 'CookieYes', 'complianz-gdpr' => 'Complianz', 'complianz-gdpr-premium' => 'Complianz',
        'cookiebot' => 'Cookiebot', 'real-cookie-banner' => 'Real Cookie Banner', 'borlabs-cookie' => 'Borlabs Cookie',
        'gdpr-cookie-compliance' => 'GDPR Cookie Compliance', 'cookie-notice' => 'Cookie Notice',
    );
    $aktivni = array_merge(
        (array) get_option('active_plugins', array()),
        array_keys((array) get_site_option('active_sitewide_plugins', array()))
    );
    $out = array();
    foreach ($aktivni as $p) {
        $slozka = strtok((string) $p, '/');
        if (isset($znam[$slozka])) {
            $out[$znam[$slozka]] = true;
        }
    }
    return array_keys($out);
}

// ============================================================= sanitizace

function hex(string $v, string $vychozi): string
{
    $v = sanitize_hex_color($v);
    return $v ? strtolower($v) : $vychozi;
}

function z_vyctu($v, array $povolene, string $vychozi): string
{
    $v = is_string($v) ? $v : '';
    return in_array($v, $povolene, true) ? $v : $vychozi;
}

function delka(string $s, int $max): string
{
    return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
}

/**
 * Sanitizace nastavení (register_setting; nonce a manage_options hlídá WordPress, hodnoty
 * přicházejí z options.php už odslashované). Revize se mění jen tlačítkem (admin-post),
 * tady se zachová uložená.
 *
 * @param mixed $vstup
 */
function sanitizovat($vstup): array
{
    $v   = is_array($vstup) ? $vstup : array();
    $d   = vychozi();
    $old = nastaveni();
    $c   = array();

    $c['layout'] = z_vyctu($v['layout'] ?? '', array('box', 'bar-b', 'bar-t', 'mid'), $d['layout']);
    $c['corner'] = z_vyctu($v['corner'] ?? '', array('br', 'bl'), $d['corner']);
    $c['prefs']  = z_vyctu($v['prefs'] ?? '', array('mid', 'side'), $d['prefs']);
    $c['barvy']  = z_vyctu($v['barvy'] ?? '', array('web', 'vlastni'), $d['barvy']);
    $c['brand']  = hex((string) ($v['brand'] ?? ''), $d['brand']);
    $c['bg']     = hex((string) ($v['bg'] ?? ''), $d['bg']);
    $c['ink']    = hex((string) ($v['ink'] ?? ''), $d['ink']);
    $c['btn']    = z_vyctu($v['btn'] ?? '', array('fill', 'line'), $d['btn']);
    $c['radius'] = max(0, min(24, (int) ($v['radius'] ?? $d['radius'])));
    $c['width']  = max(320, min(480, (int) ($v['width'] ?? $d['width'])));
    $c['shadow'] = z_vyctu($v['shadow'] ?? '', array('none', 'soft', 'strong'), $d['shadow']);
    $c['icon']   = z_vyctu($v['icon'] ?? '', array('l', 'r', 'off'), $d['icon']);
    $c['close']  = !empty($v['close']);
    $c['mobil']  = z_vyctu($v['mobil'] ?? '', array('lista', 'okno', 'stejne'), $d['mobil']);
    $c['mBod']   = max(320, min(1200, (int) ($v['mBod'] ?? $d['mBod'])));
    $c['mText']  = delka(sanitize_text_field((string) ($v['mText'] ?? '')), MAX_MTEXT);

    foreach (array('title' => 120, 'lAccept' => 40, 'lReject' => 40, 'lPrefs' => 40, 'lMore' => 60) as $k => $max) {
        $c[$k] = delka(sanitize_text_field((string) ($v[$k] ?? '')), $max);
    }
    foreach (array('text', 'pIntro', 'popis_nezbytne', 'popis_preferencni', 'popis_analyticke', 'popis_marketingove') as $k) {
        $c[$k] = delka(sanitize_textarea_field((string) ($v[$k] ?? '')), 600);
    }
    $stranka              = absint($v['odkaz_stranka'] ?? 0);
    $c['odkaz_stranka']   = ($stranka > 0 && get_post_type($stranka) === 'page') ? $stranka : 0;

    $c['sluzby'] = array_values(array_intersect(array_keys(sluzby()), array_map('strval', (array) ($v['sluzby'] ?? array()))));

    // Vlastní cookies: max 40 řádků, každý řádek očištěný, kategorie z pevného seznamu.
    $radky = array();
    foreach (array_slice(preg_split('/\r\n|\r|\n/', (string) ($v['vlastni'] ?? '')), 0, 40) as $r) {
        $cast = array_map(function ($x) {
            return delka(sanitize_text_field($x), 160);
        }, explode('|', $r));
        if (count($cast) >= 5 && $cast[0] !== '' && array_key_exists($cast[4], kategorie_zaklad())) {
            $radky[] = implode(' | ', array_slice($cast, 0, 5));
        }
    }
    $c['vlastni'] = implode("\n", $radky);

    $ga = strtoupper(trim(sanitize_text_field((string) ($v['ga'] ?? ''))));
    if ($ga !== '' && !preg_match('/^G-[A-Z0-9]{4,20}$/', $ga)) {
        add_settings_error(OPTION, 'ga', __('ID měření GA4 má tvar G-XXXXXXXXXX – neuloženo.', 'marmal-breakdance-plus'), 'error');
        $ga = '';
    }
    $c['ga']      = $ga;
    $c['gcm']     = z_vyctu($v['gcm'] ?? '', array('basic', 'advanced'), $d['gcm']);
    $c['gaShort'] = !empty($v['gaShort']);
    foreach (merici_vzory() as $k => $vzor) {
        $id = trim(sanitize_text_field((string) ($v[$k] ?? '')));
        $id = $vzor[2] === 'upper' ? strtoupper($id) : ($vzor[2] === 'lower' ? strtolower($id) : $id);
        if ($id !== '' && !preg_match($vzor[1], $id)) {
            /* translators: 1: název pole, 2: ukázka tvaru */
            add_settings_error(OPTION, $k, sprintf(__('%1$s: neplatné ID (tvar %2$s) – neuloženo.', 'marmal-breakdance-plus'), merici_nazev($k), $vzor[0]), 'error');
            $id = '';
        }
        $c[$k] = $id;
    }
    $c['gtm_rezim'] = z_vyctu($v['gtm_rezim'] ?? '', array('souhlas', 'vzdy'), $d['gtm_rezim']);
    $valid        = (int) ($v['valid'] ?? 6);
    $c['valid']   = in_array($valid, array(3, 6, 12), true) ? $valid : 6;
    $c['uchovat'] = max(1, min(5, (int) ($v['uchovat'] ?? 3)));
    $c['rev']     = max(1, (int) $old['rev']);

    foreach (prekladane_klice() as $k) {
        if (!empty($c[$k])) {
            do_action('wpml_register_single_string', 'Marmal – Cookies', $k, $c[$k]);
        }
    }
    return $c;
}

// ============================================================= barvy

function luminance(string $hex): float
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) {
        return 0.0;
    }
    $l = 0.0;
    foreach (array(0 => 0.2126, 2 => 0.7152, 4 => 0.0722) as $i => $w) {
        $c  = hexdec(substr($hex, $i, 2)) / 255;
        $c  = $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $l += $c * $w;
    }
    return $l;
}

function kontrast(string $a, string $b): float
{
    $x = luminance($a);
    $y = luminance($b);
    return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
}

/** Barva textu na hlavní barvě (bílá / téměř černá podle kontrastu). */
function na_brand(string $brand): string
{
    return kontrast('#ffffff', $brand) >= kontrast('#111111', $brand) ? '#ffffff' : '#111111';
}
