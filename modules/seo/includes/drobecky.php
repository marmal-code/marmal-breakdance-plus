<?php
/**
 * Modul SEO – drobečková navigace.
 *
 *  [marmal_drobecky]  viditelná navigace (Úvod › Přehled bytů › Byt 1.01) – vložit elementem
 *                     Shortcode do šablony / hlavičky stránky. Atributy: oddelovac="›",
 *                     uvod="Úvod", aktualni="ne" (bez poslední položky).
 *  JSON-LD BreadcrumbList v hlavičce – to čte Google (cesta místo adresy ve výsledcích).
 *
 * Cesta: stránky podle nadřazených stránek; příspěvky = stránka blogu → rubrika; ostatní typy
 * obsahu = nadřazená stránka z nastavení (např. Jednotky → „Přehled bytů“), jinak archiv typu.
 * Pluginy mohou cestu upravit filtrem `marmal_seo_drobecky` (pole [['nazev','url'], …]).
 */

namespace MarmalElements\Seo;

if (!defined('ABSPATH')) {
    exit;
}

/** Povolené oddělovače drobečkové navigace (jdou do CSS, proto pevný seznam). */
function drobecky_oddelovace(): array
{
    return array('›', '»', '/', '|', '·', '–', '>', '→');
}

function drobecky_uvod(): string
{
    $s = (string) nastaveni()['drobecky_uvod'];
    return $s !== '' ? $s : __('Úvod', 'marmal-breakdance-plus');
}

/** Položka cesty. */
function polozka(string $nazev, string $url): array
{
    return array('nazev' => cisty_text($nazev, 120), 'url' => $url);
}

/** Stránka s předky (od nejvyššího). */
function stranka_s_predky(int $id): array
{
    $out = array();
    if (!$id || get_post_status($id) !== 'publish') {
        return $out;
    }
    foreach (array_reverse(get_post_ancestors($id)) as $p) {
        if (get_post_status($p) === 'publish') {
            $out[] = polozka(get_the_title($p), (string) get_permalink($p));
        }
    }
    $out[] = polozka(get_the_title($id), (string) get_permalink($id));
    return $out;
}

/** Rubrika / termín s nadřazenými termíny. */
function termin_s_predky(\WP_Term $t): array
{
    $out = array();
    foreach (array_reverse(get_ancestors($t->term_id, $t->taxonomy, 'taxonomy')) as $pid) {
        $p = get_term($pid, $t->taxonomy);
        if ($p instanceof \WP_Term) {
            $odkaz = get_term_link($p);
            $out[] = polozka($p->name, is_string($odkaz) ? $odkaz : '');
        }
    }
    $odkaz = get_term_link($t);
    $out[] = polozka($t->name, is_string($odkaz) ? $odkaz : '');
    return $out;
}

/** Stránka blogu (Nastavení → Zobrazování → Stránka s příspěvky), když není úvodní. */
function stranka_blogu(): array
{
    $id = (int) get_option('page_for_posts');
    return ($id && get_option('show_on_front') === 'page') ? stranka_s_predky($id) : array();
}

/** Nadřazená stránka nebo archiv pro typ obsahu (ne stránky a příspěvky). */
function rodic_typu(string $typ): array
{
    $rodice = (array) nastaveni()['drobecky_rodice'];
    $id     = absint($rodice[$typ] ?? 0);
    if ($id) {
        return stranka_s_predky($id);
    }
    $pt = get_post_type_object($typ);
    if ($pt && $pt->has_archive) {
        $odkaz = get_post_type_archive_link($typ);
        return $odkaz ? array(polozka($pt->labels->name, $odkaz)) : array();
    }
    return array();
}

/**
 * Cesta pro aktuální stránku webu: [['nazev','url'], …], první = úvod.
 * Na úvodní stránce prázdné pole (navigace se nezobrazí).
 */
function drobecky(): array
{
    if (is_front_page()) {
        return array();
    }
    $cesta = array(polozka(drobecky_uvod(), home_url('/')));

    if (is_home()) {
        $cesta = array_merge($cesta, stranka_blogu());
    } elseif (is_page()) {
        $cesta = array_merge($cesta, stranka_s_predky((int) get_queried_object_id()));
    } elseif (is_singular('post')) {
        $id    = (int) get_queried_object_id();
        $cesta = array_merge($cesta, stranka_blogu());
        $rubr  = get_the_category($id);
        if ($rubr && $rubr[0] instanceof \WP_Term && $rubr[0]->slug !== 'uncategorized' && (int) $rubr[0]->term_id !== (int) get_option('default_category')) {
            $cesta = array_merge($cesta, termin_s_predky($rubr[0]));
        }
        $cesta[] = polozka(get_the_title($id), (string) get_permalink($id));
    } elseif (is_singular()) {
        $id    = (int) get_queried_object_id();
        $typ   = (string) get_post_type($id);
        $cesta = array_merge($cesta, rodic_typu($typ));
        if (is_post_type_hierarchical($typ)) {
            foreach (array_reverse(get_post_ancestors($id)) as $p) {
                $cesta[] = polozka(get_the_title($p), (string) get_permalink($p));
            }
        }
        $cesta[] = polozka(get_the_title($id), (string) get_permalink($id));
    } elseif (is_category() || is_tag() || is_tax()) {
        $t = get_queried_object();
        if (is_category() || is_tag()) {
            $cesta = array_merge($cesta, stranka_blogu());
        }
        if ($t instanceof \WP_Term) {
            $cesta = array_merge($cesta, termin_s_predky($t));
        }
    } elseif (is_post_type_archive()) {
        $typ   = get_query_var('post_type');
        $typ   = (string) (is_array($typ) ? reset($typ) : $typ);
        $odkaz = $typ ? get_post_type_archive_link($typ) : '';
        $cesta[] = polozka(post_type_archive_title('', false), $odkaz ?: '');
    } elseif (is_search()) {
        $cesta[] = polozka(__('Výsledky hledání', 'marmal-breakdance-plus'), '');
    } elseif (is_404()) {
        $cesta[] = polozka(__('Stránka nenalezena', 'marmal-breakdance-plus'), '');
    } elseif (is_author()) {
        $a = get_queried_object();
        if ($a && isset($a->display_name)) {
            $cesta[] = polozka($a->display_name, get_author_posts_url((int) $a->ID));
        }
    } elseif (is_date()) {
        $cesta[] = polozka(is_year() ? get_the_date('Y') : (is_month() ? get_the_date('F Y') : get_the_date()), '');
    }

    $cesta = apply_filters('marmal_seo_drobecky', $cesta);
    // Jen platné položky; stejná adresa dvakrát za sebou (např. nadřazená = aktuální) jen jednou.
    $out = array();
    foreach ((array) $cesta as $p) {
        if (!is_array($p) || empty($p['nazev'])) {
            continue;
        }
        $p = polozka((string) $p['nazev'], (string) ($p['url'] ?? ''));
        $posledni = end($out);
        if ($posledni && $p['url'] !== '' && $posledni['url'] === $p['url']) {
            continue;
        }
        $out[] = $p;
    }
    return count($out) > 1 ? $out : array();
}

/** JSON-LD BreadcrumbList (null = nic). */
function drobecky_schema(array $cesta): ?array
{
    if (count($cesta) < 2) {
        return null;
    }
    $prvky = array();
    $i     = 0;
    foreach ($cesta as $p) {
        $prvek = array('@type' => 'ListItem', 'position' => ++$i, 'name' => $p['nazev']);
        if ($p['url'] !== '') {
            $prvek['item'] = absolutni($p['url']);
        }
        $prvky[] = $prvek;
    }
    return array('@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $prvky);
}

add_action('wp_head', function () {
    if (!bezi() || !nastaveni()['drobecky_schema'] || is_404() || is_search()) {
        return;
    }
    $schema = drobecky_schema(drobecky());
    if ($schema) {
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON s JSON_HEX_TAG
    }
}, 2);

function drobecky_css(): string
{
    return '.marmal-drobecky__seznam{display:flex;flex-wrap:wrap;align-items:center;gap:var(--marmal-drobecky-gap,.5em);list-style:none;margin:0;padding:0}'
        . '.marmal-drobecky__polozka{display:inline-flex;align-items:center;gap:var(--marmal-drobecky-gap,.5em);margin:0}'
        . '.marmal-drobecky__polozka+.marmal-drobecky__polozka::before{content:var(--marmal-drobecky-odd,"›");opacity:var(--marmal-drobecky-odd-opacity,.6)}'
        . '.marmal-drobecky a.marmal-drobecky__odkaz{color:var(--marmal-drobecky-odkaz,inherit);text-decoration:none}'
        . '.marmal-drobecky a.marmal-drobecky__odkaz:hover,.marmal-drobecky a.marmal-drobecky__odkaz:focus-visible{color:var(--marmal-drobecky-odkaz-hover,inherit);text-decoration:underline}'
        . '.marmal-drobecky__aktualni{color:var(--marmal-drobecky-aktualni,inherit);opacity:var(--marmal-drobecky-aktualni-opacity,.75)}';
}

/** HTML drobečkové navigace (prázdný řetězec = nic). */
function drobecky_html(array $atr = array()): string
{
    $atr  = shortcode_atts(array('oddelovac' => '', 'uvod' => '', 'aktualni' => 'ano'), $atr, 'marmal_drobecky');
    $cesta = drobecky();
    if (!$cesta) {
        return '';
    }
    if ($atr['uvod'] !== '') {
        $cesta[0]['nazev'] = cisty_text($atr['uvod'], 60);
    }
    if ($atr['aktualni'] === 'ne' && count($cesta) > 2) {
        array_pop($cesta);
        $bezAktualni = true;
    }
    $odd   = in_array($atr['oddelovac'], drobecky_oddelovace(), true) ? $atr['oddelovac'] : (string) nastaveni()['drobecky_oddelovac'];
    $odd   = in_array($odd, drobecky_oddelovace(), true) ? $odd : '›';
    $styl  = $odd !== '›' ? ' style="--marmal-drobecky-odd:&quot;' . esc_attr($odd) . '&quot;"' : '';

    static $css = false;
    $out = '';
    if (!$css) {
        $out .= '<style id="marmal-drobecky-css">' . drobecky_css() . '</style>';
        $css  = true;
    }
    $out .= '<nav class="marmal-drobecky" aria-label="' . esc_attr__('Drobečková navigace', 'marmal-breakdance-plus') . '"' . $styl . '><ol class="marmal-drobecky__seznam">';
    $n    = count($cesta);
    foreach ($cesta as $i => $p) {
        $posledni = ($i === $n - 1) && empty($bezAktualni);
        $out .= '<li class="marmal-drobecky__polozka">';
        if ($posledni || $p['url'] === '') {
            $out .= '<span class="marmal-drobecky__aktualni"' . ($posledni ? ' aria-current="page"' : '') . '>' . esc_html($p['nazev']) . '</span>';
        } else {
            $out .= '<a class="marmal-drobecky__odkaz" href="' . esc_url($p['url']) . '">' . esc_html($p['nazev']) . '</a>';
        }
        $out .= '</li>';
    }
    return $out . '</ol></nav>';
}

add_shortcode('marmal_drobecky', function ($atr) {
    if (!vypisuje()) {
        return '';
    }
    return drobecky_html(is_array($atr) ? $atr : array());
});
