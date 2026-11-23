<?php
/**
 * Modul SEO – data o firmě pro vyhledávače a AI (od 0.22.0).
 *
 * JSON-LD na úvodní stránce: Organization nebo LocalBusiness (podtyp podle nastavení) + WebSite.
 * Název, sídlo, IČO a DIČ se berou z modulu Ochrana osobních údajů (jinak z Patičky) – nic se nezadává
 * dvakrát. V MarMal → SEO se doplní jen typ firmy, telefon, e-mail, logo, otevírací doba, profily
 * na sítích a souřadnice (a případně adresa provozovny, když se liší od sídla).
 */

namespace MarmalElements\Seo;

if (!defined('ABSPATH')) {
    exit;
}

const DNY = array('po' => 'Monday', 'ut' => 'Tuesday', 'st' => 'Wednesday', 'ct' => 'Thursday', 'pa' => 'Friday', 'so' => 'Saturday', 'ne' => 'Sunday');

/** Typy firmy: schema.org typ => [popisek, místní podnik?]. */
function firma_typy(): array
{
    return array(
        'Organization'                => array(__('Firma / organizace bez provozovny pro zákazníky', 'marmal-breakdance-plus'), false),
        'NGO'                         => array(__('Spolek, nezisková organizace', 'marmal-breakdance-plus'), false),
        'EducationalOrganization'     => array(__('Vzdělávací organizace (škola, kroužky)', 'marmal-breakdance-plus'), false),
        'SportsOrganization'          => array(__('Sportovní organizace', 'marmal-breakdance-plus'), false),
        'LocalBusiness'               => array(__('Místní podnik – obecně', 'marmal-breakdance-plus'), true),
        'ProfessionalService'         => array(__('Odborné služby (poradenství, projekce, akustika…)', 'marmal-breakdance-plus'), true),
        'HomeAndConstructionBusiness' => array(__('Služby pro dům a stavbu', 'marmal-breakdance-plus'), true),
        'GeneralContractor'           => array(__('Stavební firma', 'marmal-breakdance-plus'), true),
        'HVACBusiness'                => array(__('Klimatizace, topení, vzduchotechnika', 'marmal-breakdance-plus'), true),
        'RealEstateAgent'             => array(__('Realitní kancelář, prodej nemovitostí', 'marmal-breakdance-plus'), true),
        'LegalService'                => array(__('Advokátní kancelář, právní služby', 'marmal-breakdance-plus'), true),
        'AccountingService'           => array(__('Účetní a daňové služby', 'marmal-breakdance-plus'), true),
        'ChildCare'                   => array(__('Péče o děti (školka, lesní klub)', 'marmal-breakdance-plus'), true),
        'SportsClub'                  => array(__('Sportovní klub se sportovištěm', 'marmal-breakdance-plus'), true),
        'Store'                       => array(__('Obchod', 'marmal-breakdance-plus'), true),
        'Restaurant'                  => array(__('Restaurace, kavárna', 'marmal-breakdance-plus'), true),
        'HealthAndBeautyBusiness'     => array(__('Kosmetika, kadeřnictví, wellness', 'marmal-breakdance-plus'), true),
        'AutomotiveBusiness'          => array(__('Autoservis, prodej aut', 'marmal-breakdance-plus'), true),
        'TravelAgency'                => array(__('Cestovní kancelář', 'marmal-breakdance-plus'), true),
    );
}

/** Údaje z modulu Ochrana osobních údajů, jinak z Patičky: firma, sidlo, ico, dic, telefon, email. */
function firma_zdroj(): array
{
    $out = array('firma' => '', 'sidlo' => '', 'ico' => '', 'dic' => '', 'telefon' => '', 'email' => '', 'odkud' => '');
    if (function_exists('MarmalElements\Gdpr\nastaveni')) {
        $g = (array) \MarmalElements\Gdpr\nastaveni();
        foreach (array('firma', 'sidlo', 'ico', 'dic', 'telefon', 'email') as $k) {
            $out[$k] = cisty_text($g[$k] ?? '', 200);
        }
        $out['odkud'] = 'gdpr';
    } elseif (function_exists('MarmalElements\Paticka\nastaveni')) {
        $p = (array) \MarmalElements\Paticka\nastaveni();
        foreach (array('firma', 'sidlo', 'ico', 'dic') as $k) {
            $out[$k] = cisty_text($p[$k] ?? '', 200);
        }
        $out['odkud'] = 'paticka';
    }
    return $out;
}

/** Sídlo „Ulice 12, 252 28 Černošice“ → [ulice, psc, mesto]. Jiný tvar = celé jako ulice. */
function rozdelit_adresu(string $sidlo): array
{
    $sidlo = trim($sidlo);
    if ($sidlo === '') {
        return array('', '', '');
    }
    if (preg_match('/^(.+?),?\s+(\d{3})\s?(\d{2})\s+(.+)$/u', $sidlo, $m)) {
        return array(trim($m[1], " ,"), $m[2] . ' ' . $m[3], trim($m[4]));
    }
    if (preg_match('/^(.+?),\s*(.+?),?\s+(\d{3})\s?(\d{2})$/u', $sidlo, $m)) { // „Ulice 12, Černošice, 25228“
        return array(trim($m[1]), $m[3] . ' ' . $m[4], trim($m[2]));
    }
    return array($sidlo, '', '');
}

/**
 * Otevírací doba dne: „8:00–16:00“, „8-12, 13-16“, prázdné / „zavřeno“ = zavřeno.
 * @return array<int, array{0:string,1:string}> intervaly HH:MM
 */
function hodiny_dne(string $text): array
{
    $out = array();
    if (!preg_match_all('/(\d{1,2})(?:[:.](\d{2}))?\s*[-–—]\s*(\d{1,2})(?:[:.](\d{2}))?/u', $text, $m, PREG_SET_ORDER)) {
        return $out;
    }
    foreach (array_slice($m, 0, 3) as $x) {
        $h1 = (int) $x[1];
        $m1 = isset($x[2]) && $x[2] !== '' ? (int) $x[2] : 0;
        $h2 = (int) $x[3];
        $m2 = isset($x[4]) && $x[4] !== '' ? (int) $x[4] : 0;
        if ($h1 > 24 || $h2 > 24 || $m1 > 59 || $m2 > 59) {
            continue;
        }
        $od = sprintf('%02d:%02d', $h1, $m1);
        $do = sprintf('%02d:%02d', $h2, $m2);
        if ($do === '24:00') {
            $do = '23:59';
        }
        if ($od < $do) {
            $out[] = array($od, $do);
        }
    }
    return $out;
}

/** Otevírací doba pro schema – stejné dny se sloučí do jedné položky. */
function hodiny_schema(array $hodiny): array
{
    $skupiny = array();
    foreach (DNY as $den => $anglicky) {
        foreach (hodiny_dne((string) ($hodiny[$den] ?? '')) as $i) {
            $k = $i[0] . '-' . $i[1];
            $skupiny[$k]['dny'][] = $anglicky;
            $skupiny[$k]['od']    = $i[0];
            $skupiny[$k]['do']    = $i[1];
        }
    }
    $out = array();
    foreach ($skupiny as $s) {
        $out[] = array('@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $s['dny'], 'opens' => $s['od'], 'closes' => $s['do']);
    }
    return $out;
}

/** Odkazy na profily (sameAs) – jen https, jeden na řádek. */
function firma_site_seznam(string $text): array
{
    $out = array();
    foreach (preg_split('/[\r\n]+/', $text) as $r) {
        $u = esc_url_raw(trim((string) $r), array('https'));
        if ($u !== '' && wp_parse_url($u, PHP_URL_HOST)) {
            $out[$u] = true;
        }
    }
    return array_slice(array_keys($out), 0, 15);
}

/** „50.0123, 14.4567“ → [lat, lng] nebo null. */
function gps(string $text): ?array
{
    if (!preg_match('/^\s*(-?\d{1,2}(?:[.,]\d+)?)\s*[,;\s]\s*(-?\d{1,3}(?:[.,]\d+)?)\s*$/', $text, $m)) {
        return null;
    }
    $lat = (float) str_replace(',', '.', $m[1]);
    $lng = (float) str_replace(',', '.', $m[2]);
    return (abs($lat) <= 90 && abs($lng) <= 180) ? array($lat, $lng) : null;
}

/** Logo: nastavené → logo webu (Vzhled) → ikona webu. [url, w, h] nebo null. */
function firma_logo(): ?array
{
    $id = (int) nastaveni()['firma_logo'];
    if (!$id) {
        $id = (int) get_theme_mod('custom_logo');
    }
    if ($id && wp_attachment_is_image($id)) {
        $src = wp_get_attachment_image_src($id, 'full');
        if ($src) {
            return array(absolutni((string) $src[0]), (int) $src[1], (int) $src[2]);
        }
    }
    $ikona = function_exists('get_site_icon_url') ? (string) get_site_icon_url(512) : '';
    return $ikona !== '' ? array(absolutni($ikona), 512, 512) : null;
}

/**
 * Hotové údaje o firmě (zdroj + nastavení). Prázdné hodnoty = nevyplněno.
 * @return array{typ:string,mistni:bool,nazev:string,telefon:string,email:string,ulice:string,psc:string,mesto:string,zeme:string,ico:string,dic:string,hodiny:array,site:array,gps:?array}
 */
function firma(): array
{
    $s = nastaveni();
    $z = firma_zdroj();
    list($ulice, $psc, $mesto) = rozdelit_adresu($z['sidlo']);
    $typy = firma_typy();
    $typ  = isset($typy[$s['firma_typ']]) ? (string) $s['firma_typ'] : 'Organization';
    $f    = array(
        'typ'     => $typ,
        'mistni'  => (bool) $typy[$typ][1],
        'nazev'   => $s['firma_nazev'] !== '' ? (string) $s['firma_nazev'] : ($z['firma'] !== '' ? $z['firma'] : nazev_webu()),
        'telefon' => $s['firma_telefon'] !== '' ? (string) $s['firma_telefon'] : $z['telefon'],
        'email'   => $s['firma_email'] !== '' ? (string) $s['firma_email'] : $z['email'],
        'ulice'   => $s['firma_ulice'] !== '' ? (string) $s['firma_ulice'] : $ulice,
        'psc'     => $s['firma_psc'] !== '' ? (string) $s['firma_psc'] : $psc,
        'mesto'   => $s['firma_mesto'] !== '' ? (string) $s['firma_mesto'] : $mesto,
        'zeme'    => (string) $s['firma_zeme'],
        'ico'     => preg_replace('/\D/', '', $z['ico']),
        'dic'     => strtoupper(preg_replace('/\s/', '', $z['dic'])),
        'hodiny'  => (array) $s['firma_hodiny'],
        'site'    => firma_site_seznam((string) $s['firma_site']),
        'gps'     => gps((string) $s['firma_gps']),
    );
    // Místní podnik bez adresy by Google odmítl – pak obecná organizace.
    if ($f['mistni'] && ($f['ulice'] === '' || $f['mesto'] === '')) {
        $f['mistni'] = false;
    }
    return $f;
}

/** Co v datech o firmě chybí (pro administraci). */
function firma_chybi(): array
{
    $s = nastaveni();
    $f = firma();
    $c = array();
    $typy = firma_typy();
    if ($typy[$f['typ']][1] && !$f['mistni']) {
        $c[] = __('adresa (ulice a město) – bez ní se místo místního podniku vypíše jen organizace', 'marmal-breakdance-plus');
    }
    if (firma_zdroj()['firma'] === '' && $s['firma_nazev'] === '') {
        $c[] = __('název firmy (MarMal → Ochrana os. údajů nebo Patička)', 'marmal-breakdance-plus');
    }
    if ($f['telefon'] === '' && $f['email'] === '') {
        $c[] = __('telefon nebo e-mail', 'marmal-breakdance-plus');
    }
    if (!firma_logo()) {
        $c[] = __('logo', 'marmal-breakdance-plus');
    }
    return $c;
}

/** JSON-LD graf pro úvodní stránku (Organization / LocalBusiness + WebSite). */
function firma_schema(): array
{
    $f    = firma();
    $s    = nastaveni();
    $home = uvod_url();
    $org  = array(
        '@type' => $f['mistni'] ? $f['typ'] : (firma_typy()[$f['typ']][1] ? 'Organization' : $f['typ']),
        '@id'   => home_url('/#organization'),
        'name'  => $f['nazev'],
        'url'   => $home,
    );
    if ($s['popis'] !== '') {
        $org['description'] = (string) $s['popis'];
    }
    if ($logo = firma_logo()) {
        $org['logo'] = array('@type' => 'ImageObject', 'url' => $logo[0], 'width' => $logo[1], 'height' => $logo[2]);
    }
    $obr = (int) $s['obrazek'] ? og_obrazek((int) $s['obrazek'], false) : null;
    if ($obr) {
        $org['image'] = $obr['url'];
    } elseif (isset($org['logo'])) {
        $org['image'] = $org['logo']['url'];
    }
    if ($f['telefon'] !== '') {
        $org['telephone'] = $f['telefon'];
    }
    if ($f['email'] !== '') {
        $org['email'] = $f['email'];
    }
    if ($f['ulice'] !== '' || $f['mesto'] !== '') {
        $adr = array('@type' => 'PostalAddress');
        foreach (array('streetAddress' => 'ulice', 'addressLocality' => 'mesto', 'postalCode' => 'psc', 'addressCountry' => 'zeme') as $k => $v) {
            if ($f[$v] !== '') {
                $adr[$k] = $f[$v];
            }
        }
        $org['address'] = $adr;
    }
    if ($f['ico'] !== '') {
        $org['identifier'] = array('@type' => 'PropertyValue', 'propertyID' => 'IČO', 'value' => $f['ico']);
    }
    if ($f['dic'] !== '') {
        $org['vatID'] = $f['dic'];
    }
    if ($f['site']) {
        $org['sameAs'] = $f['site'];
    }
    if ($f['mistni']) {
        $h = hodiny_schema($f['hodiny']);
        if ($h) {
            $org['openingHoursSpecification'] = $h;
        }
        if ($f['gps']) {
            $org['geo'] = array('@type' => 'GeoCoordinates', 'latitude' => $f['gps'][0], 'longitude' => $f['gps'][1]);
        }
    }
    $web = array(
        '@type'      => 'WebSite',
        '@id'        => home_url('/#website'),
        'url'        => $home,
        'name'       => nazev_webu(),
        'publisher'  => array('@id' => home_url('/#organization')),
        'inLanguage' => str_replace('_', '-', locale_og()),
    );
    if (popis_webu() !== '') {
        $web['description'] = popis_webu();
    }
    /** Filtr pro doplnění / úpravu dat o firmě (např. vlastní pole). */
    return (array) apply_filters('marmal_seo_firma_schema', array('@context' => 'https://schema.org', '@graph' => array($org, $web)));
}

add_action('wp_head', function () {
    if (!bezi() || !nastaveni()['firma_schema'] || !is_front_page() || is_paged()) {
        return;
    }
    echo '<script type="application/ld+json">' . wp_json_encode(firma_schema(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON s JSON_HEX_TAG
}, 2);
