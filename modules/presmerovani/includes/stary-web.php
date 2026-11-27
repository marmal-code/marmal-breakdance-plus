<?php
/**
 * Modul Přesměrování – převod ze starého webu (redesign).
 *
 * 1. Adresy starého webu: ze sitemapy (sitemap_index.xml, wp-sitemap.xml, sitemap.xml – i vnořené),
 *    z nahraných souborů (sitemapa .xml / .xml.gz, seznam .txt / .csv – i export ze Screaming Frogu)
 *    nebo vložený seznam (jedna adresa na řádek). Zdroje se dají kombinovat, adresy se sloučí.
 *    Sitemapa se stahuje přes wp_safe_remote_get (bez přesměrování na jinou doménu, limit velikosti
 *    a počtu). Nahraný soubor se jen přečte z dočasné složky, nikam se neukládá.
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
const MAX_SOUBORU = 20;      // nahraných souborů najednou
const PRIPONY     = array('xml', 'gz', 'txt', 'csv');

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
    $adresy = stahnout_sitemapy(array($url), strtolower((string) wp_parse_url($url, PHP_URL_HOST)), true);
    if (is_wp_error($adresy)) {
        return $adresy;
    }
    if (!$adresy) {
        return new \WP_Error('prazdna', __('V sitemapě nejsou žádné adresy stránek.', 'marmal-breakdance-plus'));
    }
    return $adresy;
}

/**
 * Stáhne sitemapy z fronty (i vnořené indexy) a vrátí adresy stránek stejného webu ($host).
 * $chyba_prvni = chyba u první sitemapy se vrátí jako WP_Error (u ostatních se sitemapa přeskočí).
 * @return array|\WP_Error
 */
function stahnout_sitemapy(array $fronta, string $host, bool $chyba_prvni = false)
{
    $hotove = array();
    $adresy = array();
    while ($fronta && count($hotove) < MAX_SITEMAP && count($adresy) < MAX_ADRES) {
        $u = array_shift($fronta);
        if (isset($hotove[$u])) {
            continue;
        }
        $hotove[$u] = true;
        $prvni = $chyba_prvni && count($hotove) === 1;
        $r = wp_safe_remote_get($u, array('timeout' => 15, 'redirection' => 2, 'limit_response_size' => MAX_BAJTU, 'user-agent' => 'Marmal-Presmerovani/1.0'));
        if (is_wp_error($r)) {
            if ($prvni) {
                return new \WP_Error('stazeni', sprintf(/* translators: %s: chyba */ __('Sitemapu se nepodařilo stáhnout: %s', 'marmal-breakdance-plus'), $r->get_error_message()));
            }
            continue;
        }
        if ((int) wp_remote_retrieve_response_code($r) !== 200) {
            if ($prvni) {
                return new \WP_Error('kod', sprintf(/* translators: %d: kód */ __('Sitemapa vrátila chybu %d.', 'marmal-breakdance-plus'), (int) wp_remote_retrieve_response_code($r)));
            }
            continue;
        }
        foreach (rozebrat_sitemapu(rozbalit((string) wp_remote_retrieve_body($r))) as $typ => $locs) {
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
    return array_keys($adresy);
}

/** Obsah .gz rozbalí (max MAX_BAJTU), jiný vrátí beze změny. */
function rozbalit(string $data): string
{
    if (strncmp($data, "\x1f\x8b", 2) !== 0) {
        return $data;
    }
    if (!function_exists('gzdecode')) {
        return '';
    }
    $out = @gzdecode($data, MAX_BAJTU); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- poškozený / příliš velký archiv = prázdný výsledek
    return is_string($out) ? $out : '';
}

/** Text do UTF-8 bez BOM (uložení z Excelu nebo Poznámkového bloku bývá Windows-1250). */
function na_utf8(string $obsah): string
{
    $obsah = (string) preg_replace('/^\xEF\xBB\xBF/', '', $obsah);
    if (!mb_check_encoding($obsah, 'UTF-8')) {
        $prevod = function_exists('iconv') ? @iconv('Windows-1250', 'UTF-8//IGNORE', $obsah) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
        $obsah  = $prevod !== false ? $prevod : mb_convert_encoding($obsah, 'UTF-8', 'ISO-8859-2');
    }
    return (string) $obsah;
}

/**
 * Seznam nahraných souborů z $_FILES položky (i multiple – pole tmp_name) do [[name, tmp_name, size, error], …].
 * Jen soubory, které opravdu přišly formulářem (is_uploaded_file).
 */
function nahrane_soubory(array $pole): array
{
    $out = array();
    if (!isset($pole['tmp_name'])) {
        return $out;
    }
    $tmp = (array) $pole['tmp_name'];
    foreach ($tmp as $i => $t) {
        $chyba = (int) (((array) ($pole['error'] ?? array()))[$i] ?? UPLOAD_ERR_NO_FILE);
        if ($chyba === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[] = array(
            'name'     => sanitize_file_name((string) (((array) ($pole['name'] ?? array()))[$i] ?? '')),
            'tmp_name' => (string) $t,
            'size'     => (int) (((array) ($pole['size'] ?? array()))[$i] ?? 0),
            'error'    => $chyba,
        );
    }
    return $out;
}

/**
 * Adresy z nahraných souborů: sitemapa (.xml, .xml.gz – i Yoast / Rank Math po částech) nebo seznam
 * (.txt, .csv – adresa v prvním sloupci, řádky bez adresy jako hlavička „Address“ se přeskočí).
 * Index sitemap se nahraje jen se seznamem dílčích sitemap – ty se zkusí stáhnout, pokud starý web ještě běží.
 * @return array{adresy: string[], chyby: string[]}
 */
function adresy_ze_souboru(array $soubory): array
{
    $adresy = array();
    $chyby  = array();
    $indexy = array();
    if (count($soubory) > MAX_SOUBORU) {
        $chyby[]  = sprintf(/* translators: %d: počet */ __('Najednou jde nahrát nejvýš %d souborů, další se přeskočily.', 'marmal-breakdance-plus'), MAX_SOUBORU);
        $soubory = array_slice($soubory, 0, MAX_SOUBORU);
    }
    foreach ($soubory as $f) {
        $xml   = array('url' => array(), 'sitemap' => array());
        $jmeno = $f['name'] !== '' ? $f['name'] : '?';
        if ($f['error'] !== UPLOAD_ERR_OK || $f['tmp_name'] === '' || !is_uploaded_file($f['tmp_name'])) {
            $chyby[] = $jmeno . ' – ' . __('soubor se nepodařilo nahrát (možná je větší, než hosting dovolí).', 'marmal-breakdance-plus');
            continue;
        }
        if ($f['size'] > MAX_BAJTU) {
            $chyby[] = $jmeno . ' – ' . __('soubor je větší než 5 MB.', 'marmal-breakdance-plus');
            continue;
        }
        if (!in_array(strtolower(pathinfo($jmeno, PATHINFO_EXTENSION)), PRIPONY, true)) {
            $chyby[] = $jmeno . ' – ' . __('nahraj sitemapu .xml (.xml.gz) nebo seznam adres .txt / .csv.', 'marmal-breakdance-plus');
            continue;
        }
        $obsah = rozbalit((string) file_get_contents($f['tmp_name'])); // phpcs:ignore WordPress.WP.AlternativeFunctions -- dočasný soubor z uploadu
        $obsah = na_utf8($obsah);
        if (trim($obsah) === '') {
            $chyby[] = $jmeno . ' – ' . __('soubor je prázdný nebo poškozený.', 'marmal-breakdance-plus');
            continue;
        }
        $pred = count($adresy);
        if (strpos(ltrim($obsah), '<') === 0) {
            $xml = rozebrat_sitemapu($obsah);
            foreach ($xml['url'] as $loc) {
                $adresy[$loc] = true;
            }
            foreach ($xml['sitemap'] as $loc) {
                $indexy[$loc] = true;
            }
            if (!$xml['url'] && !$xml['sitemap']) {
                $chyby[] = $jmeno . ' – ' . __('nejde o platnou sitemapu (XML s <loc>).', 'marmal-breakdance-plus');
                continue;
            }
        } else {
            foreach (adresy_z_textu($obsah, true) as $a) {
                $adresy[$a] = true;
            }
        }
        if (count($adresy) === $pred && !$xml['sitemap']) {
            $chyby[] = $jmeno . ' – ' . __('nenašla se v něm žádná adresa.', 'marmal-breakdance-plus');
        }
        if (count($adresy) >= MAX_ADRES) {
            break;
        }
    }
    // Index sitemap: dílčí sitemapy, které nebyly nahrané (podle názvu souboru), zkusit stáhnout ze starého webu.
    $nahrane = array_map('strtolower', array_column($soubory, 'name'));
    foreach (array_keys($indexy) as $loc) {
        $nazev = strtolower(basename((string) wp_parse_url($loc, PHP_URL_PATH)));
        if (in_array($nazev, $nahrane, true) || in_array($nazev . '.gz', $nahrane, true) || in_array(preg_replace('/\.gz$/', '', $nazev), $nahrane, true)) {
            unset($indexy[$loc]);
        }
    }
    if ($indexy && count($adresy) < MAX_ADRES) {
        $prvni = (string) array_key_first($indexy);
        $host  = strtolower((string) wp_parse_url($prvni, PHP_URL_HOST));
        $nove  = stahnout_sitemapy(array_keys($indexy), $host);
        $nove  = is_wp_error($nove) ? array() : $nove;
        $pred  = count($adresy);
        foreach ($nove as $a) {
            $adresy[$a] = true;
        }
        if (count($adresy) === $pred) {
            $chyby[] = __('Nahraný index sitemap jen odkazuje na dílčí sitemapy a ty se nepodařilo stáhnout. Nahraj přímo dílčí sitemapy (např. page-sitemap.xml, post-sitemap.xml) – jde jich vybrat víc najednou.', 'marmal-breakdance-plus');
        }
    }
    return array('adresy' => array_slice(array_keys($adresy), 0, MAX_ADRES), 'chyby' => $chyby);
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

/**
 * Adresy z vloženého textu (adresa nebo cesta na řádek; i „stará;nová“ – bere se první sloupec).
 * $jen_adresy = jen řádky začínající http(s):// nebo / (soubor: hlavička a jiné řádky se přeskočí).
 */
function adresy_z_textu(string $text, bool $jen_adresy = false): array
{
    $out = array();
    foreach (preg_split('/\r\n|\r|\n/', $text) as $radek) {
        $radek = trim((string) preg_split('/[;\t,]/', (string) $radek)[0], " \t\"'");
        if ($jen_adresy && !preg_match('#^(https?://|/)#i', $radek)) {
            continue;
        }
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

/**
 * Načte adresy ze všech zadaných zdrojů (sitemapa, nahrané soubory, vložený text – sloučí je),
 * spočítá návrhy a uloží seznam.
 * @return array{pocet: int, chyby: string[]}|\WP_Error
 */
function pripravit_stary_web(string $sitemap, string $text, array $soubory = array())
{
    $adresy = array();
    $chyby  = array();
    $zdroj  = '';
    if ($sitemap !== '') {
        $z = nacist_sitemapu($sitemap);
        if (is_wp_error($z)) {
            $chyby[] = $z->get_error_message();
        } else {
            $adresy = array_merge($adresy, $z);
            $zdroj  = esc_url_raw($sitemap);
        }
    }
    if ($soubory) {
        $z      = adresy_ze_souboru($soubory);
        $adresy = array_merge($adresy, $z['adresy']);
        $chyby  = array_merge($chyby, $z['chyby']);
    }
    if (trim($text) !== '') {
        $adresy = array_merge($adresy, adresy_z_textu($text));
    }
    if ($sitemap === '' && !$soubory && trim($text) === '') {
        return new \WP_Error('nic', __('Zadej sitemapu, nahraj soubor nebo vlož seznam adres.', 'marmal-breakdance-plus'));
    }
    // Odkazy na starý web v tabulce: doména sitemapy, jinak první absolutní adresy ze souboru / seznamu.
    if ($zdroj === '') {
        foreach ($adresy as $a) {
            if (preg_match('#^https?://#i', (string) $a)) {
                $zdroj = esc_url_raw((string) $a);
                break;
            }
        }
    }
    $radky = array();
    foreach (array_slice($adresy, 0, MAX_ADRES) as $a) {
        $c = cesta((string) $a);
        if ($c !== '' && !isset($radky[$c])) {
            $radky[$c] = navrh($c);
        }
    }
    if (!$radky) {
        return $chyby ? new \WP_Error('prazdne', implode(' ', array_slice($chyby, 0, 5))) : new \WP_Error('prazdne', __('Žádná platná adresa.', 'marmal-breakdance-plus'));
    }
    $poradi = array('navrh' => 0, 'nic' => 1, 'soubor' => 2, 'pravidlo' => 3, 'existuje' => 4);
    uasort($radky, function ($a, $b) use ($poradi) {
        return ($poradi[$a['stav']] <=> $poradi[$b['stav']]) ?: strcmp($a['stara'], $b['stara']);
    });
    update_option(OPT_STARY, array('cas' => time(), 'zdroj' => $zdroj !== '' ? $zdroj : 'seznam', 'radky' => array_values($radky)), false);
    return array('pocet' => count($radky), 'chyby' => array_slice($chyby, 0, 30));
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
