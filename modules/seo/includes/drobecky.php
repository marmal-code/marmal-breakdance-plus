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

/** CSS drobečkové navigace (soubor assets/drobecky.css; element ho má v default.css). */
function drobecky_css(): string
{
    static $css = null;
    if (null === $css) {
        $soubor = dirname(__DIR__) . '/assets/drobecky.css';
        $css    = is_readable($soubor) ? (string) file_get_contents($soubor) : '';
    }
    return $css;
}

/** Vestavěná ikona domečku pro první položku. */
function drobecky_ikona_domu(): string
{
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/></svg>';
}

/** Povolené značky a atributy SVG z ikony Breakdance (žádné skripty, odkazy ani on* atributy). */
function drobecky_svg_povolene(): array
{
    $s = array('class' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'fill-rule' => true, 'clip-rule' => true, 'opacity' => true, 'fill-opacity' => true, 'stroke-opacity' => true, 'transform' => true);
    return array(
        'svg'      => $s + array('xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'aria-hidden' => true, 'focusable' => true, 'role' => true, 'preserveaspectratio' => true),
        'g'        => $s,
        'path'     => $s + array('d' => true),
        'circle'   => $s + array('cx' => true, 'cy' => true, 'r' => true),
        'ellipse'  => $s + array('cx' => true, 'cy' => true, 'rx' => true, 'ry' => true),
        'rect'     => $s + array('x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true),
        'line'     => $s + array('x1' => true, 'y1' => true, 'x2' => true, 'y2' => true),
        'polyline' => $s + array('points' => true),
        'polygon'  => $s + array('points' => true),
    );
}

/** SVG z hodnoty ikony Breakdance (klíč svgCode), pročištěné; '' = žádná ikona. */
function drobecky_svg($hodnota, int $hloubka = 0): string
{
    if ($hloubka > 4) {
        return '';
    }
    $svg = '';
    if (is_string($hodnota)) {
        $svg = stripos(ltrim($hodnota), '<svg') === 0 ? $hodnota : '';
    } elseif (is_array($hodnota)) {
        if (isset($hodnota['svgCode']) && is_string($hodnota['svgCode'])) {
            $svg = $hodnota['svgCode'];
        } else {
            foreach ($hodnota as $dite) {
                $svg = drobecky_svg($dite, $hloubka + 1);
                if ($svg !== '') {
                    return $svg;
                }
            }
        }
    }
    if ($svg === '') {
        return '';
    }
    $svg = wp_kses($svg, drobecky_svg_povolene());
    if (stripos($svg, '<svg') === false) {
        return '';
    }
    return (string) preg_replace('/<svg\b/i', '<svg aria-hidden="true" focusable="false"', $svg, 1);
}

/** Ukázková cesta pro builder (šablona nemá vlastní stránku, aby šel element stylovat). */
function drobecky_ukazka(): array
{
    return array(
        polozka(drobecky_uvod(), home_url('/')),
        polozka(__('Nadřazená stránka', 'marmal-breakdance-plus'), home_url('/')),
        polozka(__('Aktuální stránka', 'marmal-breakdance-plus'), ''),
    );
}

/**
 * HTML drobečkové navigace.
 *
 * Volby (všechny nepovinné, neplatná hodnota = výchozí):
 *   oddelovac      znak z drobecky_oddelovace() (prázdné = z nastavení SEO)
 *   oddelovac_svg  pročištěné SVG místo znaku
 *   uvod           text první položky (prázdné = z nastavení)
 *   uvod_zobrazit  text | ikona | ikona_text
 *   uvod_svg       pročištěné SVG ikony úvodu (prázdné = domeček)
 *   skryt_uvod     bez první položky (jen viditelně – schema pro Google zůstává)
 *   aktualni       ano | ne (ne = bez poslední položky)
 *   radky          zalamovat | jeden_radek
 *   css            ano | ne (ne = bez vloženého <style>, element má CSS v default.css)
 *   cesta          vlastní cesta (pole položek) – jen ukázka v builderu
 *
 * @return string prázdný řetězec = nic (úvodní stránka)
 */
function drobecky_vykreslit(array $o = array()): string
{
    $cesta = isset($o['cesta']) && is_array($o['cesta']) ? $o['cesta'] : drobecky();
    if (!$cesta) {
        return '';
    }
    $uvodText = cisty_text($o['uvod'] ?? '', 60);
    if ($uvodText !== '') {
        $cesta[0]['nazev'] = $uvodText;
    }
    $bezAktualni = ($o['aktualni'] ?? 'ano') === 'ne' && count($cesta) > 2;
    if ($bezAktualni) {
        array_pop($cesta);
    }
    $skrytUvod = !empty($o['skryt_uvod']) && count($cesta) > 1;
    $zobrazit  = (string) ($o['uvod_zobrazit'] ?? 'text');
    $zobrazit  = in_array($zobrazit, array('text', 'ikona', 'ikona_text'), true) ? $zobrazit : 'text';

    $odd = (string) ($o['oddelovac'] ?? '');
    if (!in_array($odd, drobecky_oddelovace(), true)) {
        $odd = (string) nastaveni()['drobecky_oddelovac'];
        $odd = in_array($odd, drobecky_oddelovace(), true) ? $odd : '›';
    }
    $oddSvg  = isset($o['oddelovac_svg']) ? drobecky_svg($o['oddelovac_svg']) : '';
    $oddHtml = '<span class="marmal-drobecky__odd" aria-hidden="true">' . ($oddSvg !== '' ? $oddSvg : esc_html($odd)) . '</span>';

    $tridy = 'marmal-drobecky';
    if (($o['radky'] ?? '') === 'jeden_radek') {
        $tridy .= ' marmal-drobecky--radek';
    }

    $out = '';
    static $css = false;
    if (($o['css'] ?? 'ano') !== 'ne' && !$css) {
        $out .= '<style id="marmal-drobecky-css">' . drobecky_css() . '</style>';
        $css  = true;
    }
    $out .= '<nav class="' . esc_attr($tridy) . '" aria-label="' . esc_attr__('Drobečková navigace', 'marmal-breakdance-plus') . '"><ol class="marmal-drobecky__seznam">';
    $n     = count($cesta);
    $prvni = true;
    foreach ($cesta as $i => $p) {
        if ($i === 0 && $skrytUvod) {
            continue;
        }
        $posledni = ($i === $n - 1) && !$bezAktualni;
        $obsah    = esc_html($p['nazev']);
        if ($i === 0 && $zobrazit !== 'text') {
            $ikona = isset($o['uvod_svg']) ? drobecky_svg($o['uvod_svg']) : '';
            $ikona = '<span class="marmal-drobecky__ikona">' . ($ikona !== '' ? $ikona : drobecky_ikona_domu()) . '</span>';
            $obsah = $ikona . ($zobrazit === 'ikona' ? '<span class="marmal-drobecky__sr">' . $obsah . '</span>' : '<span class="marmal-drobecky__text">' . $obsah . '</span>');
        }
        $out .= '<li class="marmal-drobecky__polozka' . ($i === 0 ? ' marmal-drobecky__polozka--uvod' : '') . ($posledni ? ' marmal-drobecky__polozka--aktualni' : '') . '">';
        if (!$prvni) {
            $out .= $oddHtml;
        }
        if ($posledni || $p['url'] === '') {
            $out .= '<span class="marmal-drobecky__aktualni"' . ($posledni ? ' aria-current="page"' : '') . '>' . $obsah . '</span>';
        } else {
            $out .= '<a class="marmal-drobecky__odkaz" href="' . esc_url($p['url']) . '">' . $obsah . '</a>';
        }
        $out .= '</li>';
        $prvni = false;
    }
    return $out . '</ol></nav>';
}

/** Shortcode [marmal_drobecky oddelovac="›" uvod="Úvod" aktualni="ne"]. */
function drobecky_html(array $atr = array()): string
{
    $atr = shortcode_atts(array('oddelovac' => '', 'uvod' => '', 'aktualni' => 'ano'), $atr, 'marmal_drobecky');
    return drobecky_vykreslit($atr);
}

/**
 * Element „Marmal – Drobečková navigace“ (elements/Drobecky_Navigace/ssr.php).
 * $c = Content → Drobečková navigace; v builderu bez vlastní cesty ukáže ukázku.
 */
function drobecky_element_html(array $c, bool $builder = false): string
{
    $o = array(
        'oddelovac'     => is_string($c['oddelovac'] ?? null) ? $c['oddelovac'] : '',
        'oddelovac_svg' => $c['oddelovac_ikona'] ?? null,
        'uvod'          => is_string($c['uvod_text'] ?? null) ? $c['uvod_text'] : '',
        'uvod_zobrazit' => is_string($c['uvod_zobrazit'] ?? null) ? $c['uvod_zobrazit'] : 'text',
        'uvod_svg'      => $c['uvod_ikona'] ?? null,
        'skryt_uvod'    => !empty($c['skryt_uvod']),
        'aktualni'      => !empty($c['skryt_aktualni']) ? 'ne' : 'ano',
        'radky'         => is_string($c['radky'] ?? null) ? $c['radky'] : 'zalamovat',
        'css'           => 'ne',
    );
    $html = drobecky_vykreslit($o);
    if ($html === '' && $builder) {
        $o['cesta'] = drobecky_ukazka();
        $html       = drobecky_vykreslit($o);
    }
    return $html;
}

// Viditelná navigace funguje i vedle jiného SEO pluginu (je čistě vizuální); schema jen bez něj (bezi()).
add_shortcode('marmal_drobecky', function ($atr) {
    return drobecky_html(is_array($atr) ? $atr : array());
});
