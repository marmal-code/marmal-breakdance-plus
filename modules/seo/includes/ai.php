<?php
/**
 * Modul SEO – AI vyhledávání (od 0.22.0).
 *
 *  AI roboti v robots.txt   „povolit vše“ (výchozí, nic se nepřidává) nebo „zakázat trénování“ –
 *                           Disallow pro roboty, kteří obsah sbírají na trénování modelů; roboti
 *                           AI vyhledávání (OAI-SearchBot, Claude-SearchBot, PerplexityBot…) zůstanou povolení.
 *  IndexNow                 nová / změněná / zrušená stránka se ohlásí Seznamu a Bingu (IndexNow
 *                           sdílí adresy i s dalšími vyhledávači). Jen ostrý web (produkce, viditelný pro
 *                           vyhledávače). Klíč = soubor /{klíč}.txt, který vrací modul.
 *  llms.txt                 volitelný přehled webu pro jazykové modely (/llms.txt), výchozí vypnuto.
 *
 * Když běží jiný SEO plugin, nic z toho se nezapojí.
 */

namespace MarmalElements\Seo;

if (!defined('ABSPATH')) {
    exit;
}

const OPT_INDEXNOW = 'marmal_bdp_seo_indexnow';        // klíč + výsledek posledního odeslání
const OPT_FRONTA   = 'marmal_bdp_seo_indexnow_fronta'; // adresy k odeslání (bez autoload)
const MAX_FRONTA   = 500;
const LLMS_CACHE   = 'marmal_seo_llms';

// ================================================================= AI roboti v robots.txt

/** Roboti, kteří sbírají obsah na trénování AI modelů (vyhledávání AI je jinými roboty). */
function ai_treninkovi_roboti(): array
{
    return array('GPTBot', 'ClaudeBot', 'Google-Extended', 'Applebot-Extended', 'CCBot', 'Meta-ExternalAgent', 'Bytespider');
}

/** Blok robots.txt podle nastavení ('' = nic nepřidávat). */
function ai_robots_blok(): string
{
    if (nastaveni()['ai_roboti'] !== 'bez_treninku') {
        return '';
    }
    $r = "# Marmal SEO – AI: zákaz trénování modelů, AI vyhledávání povoleno\n";
    foreach (ai_treninkovi_roboti() as $bot) {
        $r .= 'User-agent: ' . $bot . "\n";
    }
    return $r . "Disallow: /\n";
}

add_filter('robots_txt', function ($obsah, $verejny) {
    if (!vypisuje() || !$verejny) {
        return $obsah;
    }
    $blok = ai_robots_blok();
    return $blok !== '' ? rtrim((string) $obsah) . "\n\n" . $blok : $obsah;
}, 21, 2);

// ================================================================= IndexNow

/** Klíč IndexNow (vytvoří se při prvním použití; není tajný – je veřejně v souboru /{klíč}.txt). */
function indexnow_klic(): string
{
    $o = get_option(OPT_INDEXNOW, array());
    $k = is_array($o) ? (string) ($o['klic'] ?? '') : '';
    if (!preg_match('/^[a-f0-9]{32}$/', $k)) {
        $k = bin2hex(random_bytes(16));
        $o = is_array($o) ? $o : array();
        $o['klic'] = $k;
        update_option(OPT_INDEXNOW, $o, false);
    }
    return $k;
}

/** Smí web adresy ohlašovat? Jen ostrý, veřejný web bez jiného SEO pluginu. */
function indexnow_povoleno(): bool
{
    return !empty(nastaveni()['indexnow']) && vypisuje() && (bool) get_option('blog_public')
        && (!function_exists('wp_get_environment_type') || wp_get_environment_type() === 'production');
}

/** Přidá adresy do fronty a naplánuje odeslání za minutu (víc změn najednou = jeden požadavek). */
function indexnow_pridat(array $adresy): void
{
    if (!indexnow_povoleno()) {
        return;
    }
    $host   = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $fronta = (array) get_option(OPT_FRONTA, array());
    foreach ($adresy as $u) {
        $u = esc_url_raw((string) $u, array('http', 'https'));
        if ($u !== '' && strtolower((string) wp_parse_url($u, PHP_URL_HOST)) === $host) {
            $fronta[$u] = true;
        }
    }
    if (!$fronta) {
        return;
    }
    update_option(OPT_FRONTA, array_slice($fronta, -MAX_FRONTA, null, true), false);
    if (!wp_next_scheduled('marmal_seo_indexnow')) {
        wp_schedule_single_event(time() + MINUTE_IN_SECONDS, 'marmal_seo_indexnow');
    }
}

/** Typ obsahu, který se ohlašuje (veřejný, ne příloha). */
function indexnow_typ(string $typ): bool
{
    return $typ !== 'attachment' && in_array($typ, array_keys(typy_obsahu_vse()), true);
}

// Publikování a změna publikované stránky.
add_action('transition_post_status', function ($nove, $stare, $post) {
    if ($nove === 'publish' && $post instanceof \WP_Post && indexnow_typ((string) $post->post_type) && !wp_is_post_revision($post) && !wp_is_post_autosave($post)) {
        indexnow_pridat(array(get_permalink($post)));
    }
}, 20, 3);

// Změna adresy nebo zrušení publikace: ohlásit i starou adresu (vyhledávač zjistí přesměrování / 404).
add_action('post_updated', function ($id, $po, $pred) {
    if (!$po instanceof \WP_Post || !$pred instanceof \WP_Post || $pred->post_status !== 'publish' || !indexnow_typ((string) $po->post_type)) {
        return;
    }
    if ($po->post_status !== 'publish' || $pred->post_name !== $po->post_name || (int) $pred->post_parent !== (int) $po->post_parent) {
        indexnow_pridat(array(get_permalink($pred)));
    }
}, 20, 3);

// Trvalé smazání publikované stránky (bez koše).
add_action('before_delete_post', function ($id) {
    $post = get_post((int) $id);
    if ($post instanceof \WP_Post && $post->post_status === 'publish' && indexnow_typ((string) $post->post_type)) {
        indexnow_pridat(array(get_permalink($post)));
    }
});

/** Odešle frontu (cron). Vrací výsledek pro administraci. */
function indexnow_odeslat(): array
{
    $fronta = array_keys((array) get_option(OPT_FRONTA, array()));
    delete_option(OPT_FRONTA);
    if (!$fronta || !indexnow_povoleno()) {
        return array();
    }
    $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
    $klic = indexnow_klic();
    $telo = wp_json_encode(array(
        'host'        => $host,
        'key'         => $klic,
        'keyLocation' => home_url('/' . $klic . '.txt'),
        'urlList'     => array_values(array_slice($fronta, 0, 10000)),
    ));
    $vysledek = array('cas' => time(), 'pocet' => count($fronta), 'cile' => array());
    foreach (array('IndexNow' => 'https://api.indexnow.org/indexnow', 'Seznam' => 'https://search.seznam.cz/indexnow') as $nazev => $url) {
        $r = wp_safe_remote_post($url, array(
            'timeout'     => 10,
            'redirection' => 0,
            'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
            'body'        => $telo,
        ));
        $vysledek['cile'][$nazev] = is_wp_error($r) ? $r->get_error_message() : (int) wp_remote_retrieve_response_code($r);
    }
    $o = (array) get_option(OPT_INDEXNOW, array());
    $o['posledni'] = $vysledek;
    update_option(OPT_INDEXNOW, $o, false);
    return $vysledek;
}

add_action('marmal_seo_indexnow', __NAMESPACE__ . '\indexnow_odeslat');

/** Všechny indexované stránky webu do fronty (tlačítko po spuštění webu). @return int počet */
function indexnow_cely_web(): int
{
    $adresy = array();
    foreach (llms_stranky(2000) as $p) {
        $adresy[] = $p['url'];
    }
    indexnow_pridat($adresy);
    return count($adresy);
}

// ================================================================= soubory /{klíč}.txt a /llms.txt

add_action('parse_request', function ($wp) {
    $cesta = isset($wp->request) ? trim((string) $wp->request, '/') : '';
    if ($cesta === '' || !vypisuje()) {
        return;
    }
    if (!empty(nastaveni()['indexnow'])) {
        $o = get_option(OPT_INDEXNOW, array());
        $k = is_array($o) ? (string) ($o['klic'] ?? '') : '';
        if ($k !== '' && $cesta === $k . '.txt') {
            poslat_text($k);
        }
    }
    if ($cesta === 'llms.txt' && !empty(nastaveni()['llms'])) {
        poslat_text(llms_obsah());
    }
}, 0);

function poslat_text(string $text): void
{
    if (!headers_sent()) {
        status_header(200);
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Robots-Tag: noindex');
        header('X-Content-Type-Options: nosniff');
    }
    echo $text; // phpcs:ignore WordPress.Security.EscapeOutput -- prostý text (text/plain), ne HTML
    exit;
}

// ================================================================= llms.txt

/**
 * Indexované stránky webu pro llms.txt a „Ohlásit celý web“: [id, typ, nazev, url, popis].
 * Jen typy obsahu s boxem SEO, publikované, bez noindex; na vícejazyčném webu výchozí jazyk.
 */
function llms_stranky(int $max = 500): array
{
    $out  = array();
    $args = array('post_status' => 'publish', 'posts_per_page' => $max, 'fields' => 'ids', 'no_found_rows' => true, 'has_password' => false);
    if (function_exists('pll_default_language') && jazyky()) {
        $args['lang'] = (string) pll_default_language();
    }
    $uvod = get_option('show_on_front') === 'page' ? (int) get_option('page_on_front') : 0;
    foreach (typy_obsahu() as $typ) {
        if ($typ === 'attachment' || count($out) >= $max) {
            continue;
        }
        $a = $args + array('post_type' => $typ);
        $a['orderby'] = is_post_type_hierarchical($typ) ? array('menu_order' => 'ASC', 'title' => 'ASC') : array('date' => 'DESC');
        foreach ((array) get_posts($a) as $id) {
            $id = (int) $id;
            if ($id === $uvod || count($out) >= $max || get_post_meta($id, '_marmal_seo_noindex', true)) {
                continue;
            }
            $d = data_prispevku($id);
            if ($d['noindex']) {
                continue;
            }
            $out[] = array('id' => $id, 'typ' => $typ, 'nazev' => nazev_prispevku($id), 'url' => (string) get_permalink($id), 'popis' => $d['description']);
        }
    }
    return $out;
}

/** Řádek Markdownu bez znaků, které by rozbily odkaz nebo strukturu. */
function md(string $t): string
{
    return trim(str_replace(array("\r", "\n", '[', ']', '<', '>'), array(' ', ' ', '(', ')', '', ''), $t));
}

/** Obsah /llms.txt (Markdown podle návrhu llmstxt.org), cache 12 h. */
function llms_obsah(): string
{
    $c = get_transient(LLMS_CACHE);
    if (is_string($c) && $c !== '' && !defined('MARMAL_SEO_TEST')) {
        return $c;
    }
    $s     = nastaveni();
    $popis = $s['popis'] !== '' ? (string) $s['popis'] : popis_webu();
    $t     = '# ' . md(nazev_webu()) . "\n\n";
    if ($popis !== '') {
        $t .= '> ' . md($popis) . "\n\n";
    }
    if ($s['llms_uvod'] !== '') {
        $t .= trim(str_replace(array('<', '>'), '', (string) $s['llms_uvod'])) . "\n\n";
    }
    $typy = typy_obsahu_vse();
    $po_typech = array();
    foreach (llms_stranky() as $p) {
        $po_typech[$p['typ']][] = $p;
    }
    $t .= '- [' . md(__('Úvod', 'marmal-breakdance-plus')) . '](' . esc_url_raw(uvod_url()) . ")\n";
    foreach ($po_typech as $typ => $stranky) {
        $o = get_post_type_object($typ);
        $t .= "\n## " . md($o ? (string) $o->labels->name : (string) ($typy[$typ] ?? $typ)) . "\n\n";
        foreach ($stranky as $p) {
            $t .= '- [' . md($p['nazev']) . '](' . esc_url_raw($p['url']) . ')' . ($p['popis'] !== '' ? ': ' . md($p['popis']) : '') . "\n";
        }
    }
    $f = firma();
    $kontakt = array_filter(array(
        $f['nazev'],
        trim($f['ulice'] . ($f['ulice'] !== '' && $f['mesto'] !== '' ? ', ' : '') . trim($f['psc'] . ' ' . $f['mesto'])),
        $f['ico'] !== '' ? 'IČO ' . $f['ico'] : '',
        $f['telefon'],
        $f['email'],
    ));
    if ($kontakt) {
        $t .= "\n## " . md(__('Kontakt', 'marmal-breakdance-plus')) . "\n\n" . implode("\n", array_map(function ($r) {
            return '- ' . md((string) $r);
        }, $kontakt)) . "\n";
    }
    set_transient(LLMS_CACHE, $t, 12 * HOUR_IN_SECONDS);
    return $t;
}

// Změna obsahu nebo nastavení = nový llms.txt.
foreach (array('save_post', 'deleted_post', 'update_option_' . OPTION) as $hook) {
    add_action($hook, function () {
        delete_transient(LLMS_CACHE);
    });
}
