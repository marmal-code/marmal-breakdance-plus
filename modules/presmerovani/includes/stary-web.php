<?php
/**
 * Modul Přesměrování – převod ze starého webu (redesign).
 *
 * 1. Adresy starého webu: ze sitemapy (sitemap_index.xml, wp-sitemap.xml, sitemap.xml – i vnořené)
 *    nebo vložený seznam (jedna adresa na řádek). Sitemapa se stahuje přes wp_safe_remote_get
 *    (bez přesměrování na jinou doménu, limit velikosti a počtu).
 * 2. Pro každou adresu stav: existuje na novém webu / už má přesměrování / návrh nové stránky
 *    (shoda posledního úseku adresy s adresou stránky, jinak nejpodobnější název) / bez návrhu.
 * 3. Správce návrhy zkontroluje a potvrdí – vzniknou pravidla s původem „stary-web“.
 *
 * Seznam se drží v option marmal_bdp_presmerovani_stary (bez autoload) do zahození.
 */

namespace MarmalElements\Presmerovani;

if (!defined('ABSPATH')) {
    exit;
}

const OPT_STARY   = 'marmal_bdp_presmerovani_stary';
const MAX_ADRES   = 3000;
const MAX_SITEMAP = 30;
const MAX_BAJTU   = 5242880; // 5 MB na jednu sitemapu
const PRAH_NAVRHU = 60;      // % podobnosti pro návrh

/**
 * Adresy ze sitemapy (i indexu sitemap). Jen http(s) a jen stejná doména jako první sitemapa.
 * @return array|\WP_Error seznam absolutních adres
 */
function nacist_sitemapu(string $url)
{
    $url = esc_url_raw(trim($url), array('http', 'https'));
    if ($url === '') {
        return new \WP_Error('url', __('Zadej adresu sitemapy včetně https://.', 'marmal-breakdance-plus'));
    }
    $host   = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
    $fronta = array($url);
    $hotove = array();
    $adresy = array();
    while ($fronta && count($hotove) < MAX_SITEMAP && count($adresy) < MAX_ADRES) {
        $u = array_shift($fronta);
        if (isset($hotove[$u])) {
            continue;
        }
        $hotove[$u] = true;
        $r = wp_safe_remote_get($u, array('timeout' => 15, 'redirection' => 2, 'limit_response_size' => MAX_BAJTU, 'user-agent' => 'Marmal-Presmerovani/1.0'));
        if (is_wp_error($r)) {
            if (count($hotove) === 1) {
                return new \WP_Error('stazeni', sprintf(/* translators: %s: chyba */ __('Sitemapu se nepodařilo stáhnout: %s', 'marmal-breakdance-plus'), $r->get_error_message()));
            }
            continue;
        }
        if ((int) wp_remote_retrieve_response_code($r) !== 200) {
            if (count($hotove) === 1) {
                return new \WP_Error('kod', sprintf(/* translators: %d: kód */ __('Sitemapa vrátila chybu %d.', 'marmal-breakdance-plus'), (int) wp_remote_retrieve_response_code($r)));
            }
            continue;
        }
        foreach (rozebrat_sitemapu((string) wp_remote_retrieve_body($r)) as $typ => $locs) {
            foreach ($locs as $loc) {
                if (strtolower((string) wp_parse_url($loc, PHP_URL_HOST)) !== $host) {
                    continue; // jen stejný web
                }
                if ($typ === 'sitemap') {
                    $fronta[] = $loc;
                } elseif (count($adresy) < MAX_ADRES) {
                    $adresy[$loc] = true;
                }
            }
        }
    }
    if (!$adresy) {
        return new \WP_Error('prazdna', __('V sitemapě nejsou žádné adresy stránek.', 'marmal-breakdance-plus'));
    }
    return array_keys($adresy);
}

/**
 * <loc> ze sitemapy. Bez externích entit (LIBXML_NONET), chyby XML potichu.
 * @return array{sitemap: string[], url: string[]}
 */
function rozebrat_sitemapu(string $xml): array
{
    $out = array('sitemap' => array(), 'url' => array());
    if ($xml === '' || stripos($xml, '<!DOCTYPE') !== false) {
        return $out; // DOCTYPE = možné entity, sitemapy ho nemají
    }
    $pred = libxml_use_internal_errors(true);
    $doc  = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    libxml_clear_errors();
    libxml_use_internal_errors($pred);
    if (!$doc) {
        return $out;
    }
    $typ = strtolower($doc->getName()) === 'sitemapindex' ? 'sitemap' : 'url';
    foreach ($doc->children() as $polozka) {
        foreach ($polozka->children() as $dite) {
            if (strtolower($dite->getName()) === 'loc') {
                $loc = esc_url_raw(trim((string) $dite), array('http', 'https'));
                if ($loc !== '') {
                    $out[$typ][] = $loc;
                }
            }
        }
    }
    return $out;
}

/** Adresy z vloženého textu (adresa nebo cesta na řádek; i „stará;nová“ – bere se první sloupec). */
function adresy_z_textu(string $text): array
{
    $out = array();
    foreach (preg_split('/\r\n|\r|\n/', $text) as $radek) {
        $radek = trim((string) preg_split('/[;\t,]/', (string) $radek)[0]);
        if ($radek !== '' && count($out) < MAX_ADRES) {
            $out[$radek] = true;
        }
    }
    return array_keys($out);
}

/** Publikované stránky nového webu pro návrhy: [id, slug, nazev_slug, nazev, typ]. */
function kandidati(): array
{
    static $c = null;
    if (null !== $c && !defined('MARMAL_SEO_TEST')) {
        return $c;
    }
    $c = array();
    $ids = get_posts(array('post_type' => hlidane_typy(), 'post_status' => 'publish', 'posts_per_page' => 3000, 'fields' => 'ids', 'no_found_rows' => true, 'orderby' => 'ID', 'order' => 'ASC'));
    foreach ((array) $ids as $id) {
        $id = (int) $id;
        $nazev = wp_strip_all_tags(get_the_title($id));
        $c[] = array(
            'id'    => $id,
            'slug'  => (string) get_post_field('post_name', $id),
            'tslug' => sanitize_title(remove_accents($nazev)),
            'nazev' => $nazev,
        );
    }
    return $c;
}

/** Poslední úsek cesty bez přípony (.html, .php) – podle něj se hledá. */
function slug_z_cesty(string $cesta): string
{
    $kusy = array_values(array_filter(explode('/', $cesta), 'strlen'));
    $s    = $kusy ? (string) end($kusy) : '';
    $s    = preg_replace('/\.(html?|php|aspx?)$/i', '', $s);
    return sanitize_title(remove_accents((string) $s));
}

function podobnost(string $a, string $b): float
{
    if ($a === '' || $b === '') {
        return 0.0;
    }
    similar_text($a, $b, $proc);
    return (float) $proc;
}

/**
 * Stav a návrh pro jednu starou cestu.
 * @return array{stara: string, stav: string, cil: string, nazev: string, skore: int}
 */
function navrh(string $cesta): array
{
    $r = array('stara' => $cesta, 'stav' => 'nic', 'cil' => '', 'nazev' => '', 'skore' => 0);
    if ($cesta === '' || $cesta === '/') {
        $r['stav'] = 'existuje';
        return $r;
    }
    if (preg_match('/\.(pdf|jpe?g|png|gif|webp|svg|zip|docx?|xlsx?|pptx?|mp4|mp3)$/i', $cesta)) {
        $r['stav'] = 'soubor';
    } elseif (najit($cesta)) {
        $r['stav'] = 'pravidlo';
        return $r;
    } else {
        $id = url_to_postid(home_url($cesta));
        if ($id && get_post_status($id) === 'publish' && cesta((string) get_permalink($id)) === $cesta) {
            $r['stav'] = 'existuje';
            return $r;
        }
    }
    if ($r['stav'] === 'soubor') {
        return $r;
    }
    $slug  = slug_z_cesty($cesta);
    $nejl  = null;
    $skore = 0.0;
    foreach (kandidati() as $k) {
        if ($slug !== '' && $k['slug'] === $slug) {
            $nejl  = $k;
            $skore = 100.0;
            break;
        }
        $s = max(podobnost($slug, $k['slug']), podobnost($slug, $k['tslug']));
        if ($s > $skore) {
            $skore = $s;
            $nejl  = $k;
        }
    }
    if ($nejl && $skore >= PRAH_NAVRHU) {
        $r['stav']  = 'navrh';
        $r['cil']   = 'post:' . $nejl['id'];
        $r['nazev'] = $nejl['nazev'];
        $r['skore'] = (int) round($skore);
    }
    return $r;
}

/** Načte adresy (sitemap nebo text), spočítá návrhy a uloží seznam. @return int|\WP_Error počet */
function pripravit_stary_web(string $sitemap, string $text)
{
    $adresy = $sitemap !== '' ? nacist_sitemapu($sitemap) : adresy_z_textu($text);
    if (is_wp_error($adresy)) {
        return $adresy;
    }
    $radky = array();
    foreach ($adresy as $a) {
        $c = cesta((string) $a);
        if ($c !== '' && !isset($radky[$c])) {
            $radky[$c] = navrh($c);
        }
    }
    if (!$radky) {
        return new \WP_Error('prazdne', __('Žádná platná adresa.', 'marmal-breakdance-plus'));
    }
    $poradi = array('navrh' => 0, 'nic' => 1, 'soubor' => 2, 'pravidlo' => 3, 'existuje' => 4);
    uasort($radky, function ($a, $b) use ($poradi) {
        return ($poradi[$a['stav']] <=> $poradi[$b['stav']]) ?: strcmp($a['stara'], $b['stara']);
    });
    update_option(OPT_STARY, array('cas' => time(), 'zdroj' => $sitemap !== '' ? esc_url_raw($sitemap) : 'seznam', 'radky' => array_values($radky)), false);
    return count($radky);
}

function stary_web(): array
{
    $s = get_option(OPT_STARY, array());
    return is_array($s) && !empty($s['radky']) ? $s : array();
}

/**
 * Vytvoří potvrzená přesměrování. $volby = [[stara, cil, kod], …] z formuláře (JSON).
 * Stará adresa se bere jen ze seznamu (nejde podstrčit jinou), cíl a kód projdou ulozit().
 * @return array{ok: int, chyby: string[]}
 */
function vytvorit_ze_stareho(array $volby): array
{
    $seznam = stary_web();
    $platne = array();
    foreach ((array) ($seznam['radky'] ?? array()) as $r) {
        $platne[$r['stara']] = true;
    }
    $ok    = 0;
    $chyby = array();
    foreach (array_slice($volby, 0, MAX_ADRES) as $v) {
        if (!is_array($v)) {
            continue;
        }
        $stara = cesta((string) ($v['stara'] ?? ''));
        if (!isset($platne[$stara])) {
            continue;
        }
        $kod = (int) ($v['kod'] ?? 301) === 410 ? 410 : 301;
        $res = ulozit(array('zdroj' => $stara, 'cil' => (string) ($v['cil'] ?? ''), 'kod' => $kod, 'puvod' => 'stary-web', 'na_stranku' => true), 0, true);
        if (is_wp_error($res)) {
            $chyby[] = $stara . ' – ' . $res->get_error_message();
        } else {
            $ok++;
        }
    }
    // Přepočítat stavy (vytvořené řádky = „pravidlo“).
    if ($seznam) {
        foreach ($seznam['radky'] as &$r) {
            if (in_array($r['stav'], array('navrh', 'nic', 'soubor'), true) && najit($r['stara'])) {
                $r['stav'] = 'pravidlo';
            }
        }
        unset($r);
        update_option(OPT_STARY, $seznam, false);
    }
    return array('ok' => $ok, 'chyby' => array_slice($chyby, 0, 30));
}
