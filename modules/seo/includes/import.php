<?php
/**
 * Modul SEO – převod z Yoast SEO a Rank Math.
 *
 * Převádí u stránek, příspěvků a dalších veřejných typů obsahu titulek, popisek, obrázek pro
 * sdílení a noindex; z nastavení webu oddělovač, výchozí obrázek a (u webu s příspěvky na úvodu)
 * titulek a popisek úvodu. Proměnné (%%title%%, %title%, %%sep%%…) se převedou na zástupné
 * symboly modulu nebo vypustí. Titulek, který by vyšel stejně jako výchozí vzor, se neukládá –
 * stránka pak dál bere výchozí hodnotu.
 *
 * Data původního pluginu zůstávají beze změny (jde se vrátit). Už vyplněné hodnoty modulu se
 * nepřepisují, pokud to správce výslovně nezaškrtne.
 *
 * Spouští se jen z MarMal → SEO přes admin-post s nonce a oprávněním manage_options.
 */

namespace MarmalElements\Seo;

if (!defined('ABSPATH')) {
    exit;
}

const IMPORT_MAX = 5000;

/** Klíče post meta podle zdroje. */
function import_klice(string $zdroj): array
{
    if ($zdroj === 'yoast') {
        return array(
            'title'    => '_yoast_wpseo_title',
            'desc'     => '_yoast_wpseo_metadesc',
            'image'    => '_yoast_wpseo_opengraph-image-id',
            'noindex'  => '_yoast_wpseo_meta-robots-noindex',
        );
    }
    if ($zdroj === 'rankmath') {
        return array(
            'title'    => 'rank_math_title',
            'desc'     => 'rank_math_description',
            'image'    => 'rank_math_facebook_image_id',
            'noindex'  => 'rank_math_robots',
        );
    }
    return array();
}

function import_zdroje(): array
{
    return array('yoast' => 'Yoast SEO', 'rankmath' => 'Rank Math');
}

/** ID příspěvků (veřejné typy obsahu), které mají u zdroje nějaká data. */
function import_prispevky(string $zdroj): array
{
    global $wpdb;
    $klice = array_values(import_klice($zdroj));
    if (!$klice) {
        return array();
    }
    $typy = array_keys(typy_obsahu_vse());
    if (!$typy) {
        return array();
    }
    $ph_k = implode(',', array_fill(0, count($klice), '%s'));
    $ph_t = implode(',', array_fill(0, count($typy), '%s'));
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- jen zástupné %s, hodnoty jdou přes prepare()
    $sql = $wpdb->prepare(
        "SELECT DISTINCT pm.post_id FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key IN ($ph_k) AND pm.meta_value <> '' AND p.post_type IN ($ph_t) AND p.post_status NOT IN ('trash', 'auto-draft', 'inherit')
         ORDER BY pm.post_id LIMIT %d",
        array_merge($klice, $typy, array(IMPORT_MAX))
    );
    return array_map('intval', (array) $wpdb->get_col($sql)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- připraveno výše
}

/**
 * Proměnné Yoastu (%%x%%) a Rank Mathu (%x%, %x(arg)%) → zástupné symboly modulu nebo text.
 * Neznámé proměnné (klíčové slovo, výňatek, čísla stran…) se vypustí.
 */
function import_promenne(string $text, int $post_id = 0): string
{
    if ($text === '' || strpos($text, '%') === false) {
        return cisty_text($text, MAX_TITLE * 3);
    }
    $mapa = array(
        'title'            => '%nazev%',
        'sitename'         => '%web%',
        'sep'              => '%oddelovac%',
        'sitedesc'         => '%popis_webu%',
        'currentyear'      => (string) wp_date('Y'),
        'term_title'       => '%nazev%',
        'pt_single'        => $post_id ? (string) (get_post_type_object((string) get_post_type($post_id))->labels->singular_name ?? '') : '',
    );
    $rubrika = '';
    if ($post_id) {
        $r       = get_the_category($post_id);
        $rubrika = ($r && $r[0] instanceof \WP_Term) ? $r[0]->name : '';
    }
    $mapa['category']         = $rubrika;
    $mapa['primary_category'] = $rubrika;

    $nahrad = function ($m) use ($mapa) {
        $k = strtolower($m[1]);
        return $mapa[$k] ?? '';
    };
    $text = preg_replace_callback('/%%([a-z0-9_]+)%%/i', $nahrad, $text);              // Yoast
    $text = preg_replace_callback('/%([a-z0-9_]+)(?:\([^)%]*\))?%/i', function ($m) use ($nahrad) {
        // vlastní zástupné symboly modulu nechat
        if (in_array($m[0], array('%nazev%', '%web%', '%oddelovac%', '%popis_webu%'), true)) {
            return $m[0];
        }
        return $nahrad($m);
    }, $text);                                                                          // Rank Math
    return cisty_text($text, MAX_TITLE * 3);
}

/** Hodnoty jednoho příspěvku ze zdroje (už převedené). */
function import_hodnoty(string $zdroj, int $post_id): array
{
    $k     = import_klice($zdroj);
    $title = import_promenne((string) get_post_meta($post_id, $k['title'], true), $post_id);
    $desc  = import_promenne((string) get_post_meta($post_id, $k['desc'], true), $post_id);
    $img   = absint(get_post_meta($post_id, $k['image'], true));
    $robot = get_post_meta($post_id, $k['noindex'], true);
    $noindex = $zdroj === 'yoast' ? ((string) $robot === '1') : (is_array($robot) && in_array('noindex', $robot, true));

    // Titulek stejný jako výchozí vzor → neukládat (stránka bude brát výchozí).
    $nazev = nazev_prispevku($post_id);
    if ($title !== '' && nahradit($title, $nazev) === nahradit(vzor(), $nazev)) {
        $title = '';
    }
    if ($title !== '' && trim(str_replace(array('%nazev%', '%web%', '%oddelovac%', '%popis_webu%'), '', $title)) === '' && strpos($title, '%nazev%') === false) {
        $title = '';
    }
    return array(
        'title'   => mb_substr($title, 0, MAX_TITLE, 'UTF-8'),
        'desc'    => mb_substr($desc, 0, MAX_DESC, 'UTF-8'),
        'image'   => ($img && wp_attachment_is_image($img)) ? $img : 0,
        'noindex' => $noindex,
    );
}

/** Přehled před převodem: kolik stránek má u zdroje titulek / popisek / obrázek / noindex. */
function import_prehled(string $zdroj): array
{
    $p = array('stranek' => 0, 'title' => 0, 'desc' => 0, 'image' => 0, 'noindex' => 0, 'globalni' => import_globalni_hodnoty($zdroj));
    foreach (import_prispevky($zdroj) as $id) {
        $h = import_hodnoty($zdroj, $id);
        $p['stranek']++;
        $p['title']   += $h['title'] !== '' ? 1 : 0;
        $p['desc']    += $h['desc'] !== '' ? 1 : 0;
        $p['image']   += $h['image'] ? 1 : 0;
        $p['noindex'] += $h['noindex'] ? 1 : 0;
    }
    return $p;
}

/** Nastavení webu ze zdroje (jen hodnoty, které modul umí). */
function import_globalni_hodnoty(string $zdroj): array
{
    $out = array();
    if ($zdroj === 'yoast') {
        $t    = get_option('wpseo_titles', array());
        $soc  = get_option('wpseo_social', array());
        $t    = is_array($t) ? $t : array();
        $soc  = is_array($soc) ? $soc : array();
        $odd  = array('sc-dash' => '-', 'sc-ndash' => '–', 'sc-mdash' => '–', 'sc-middot' => '·', 'sc-bull' => '•', 'sc-pipe' => '|', 'sc-raquo' => '»', 'sc-gt' => '»', 'sc-tilde' => '–', 'sc-star' => '•', 'sc-lt' => '|');
        if (!empty($t['separator']) && isset($odd[$t['separator']])) {
            $out['oddelovac'] = $odd[$t['separator']];
        }
        $out['titulek_uvod'] = import_promenne((string) ($t['title-home-wpseo'] ?? ''));
        $out['popis_uvod']   = import_promenne((string) ($t['metadesc-home-wpseo'] ?? ''));
        $out['obrazek']      = absint($soc['og_default_image_id'] ?? 0);
    } elseif ($zdroj === 'rankmath') {
        $t = get_option('rank-math-options-titles', array());
        $t = is_array($t) ? $t : array();
        $sep = trim((string) ($t['title_separator'] ?? ''));
        $sep = html_entity_decode($sep, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $mapa = array('&ndash;' => '–', '—' => '–', '>' => '»', '~' => '–', '*' => '•');
        $sep  = $mapa[$sep] ?? $sep;
        if (in_array($sep, oddelovace(), true)) {
            $out['oddelovac'] = $sep;
        }
        $out['titulek_uvod'] = import_promenne((string) ($t['homepage_title'] ?? ''));
        $out['popis_uvod']   = import_promenne((string) ($t['homepage_description'] ?? ''));
        $out['obrazek']      = absint($t['open_graph_image_id'] ?? 0);
    }
    if (!empty($out['obrazek']) && !wp_attachment_is_image($out['obrazek'])) {
        $out['obrazek'] = 0;
    }
    // Titulek a popisek úvodu platí jen pro web s příspěvky na úvodu – jinak má úvod vlastní stránku.
    if (get_option('show_on_front') === 'page') {
        unset($out['titulek_uvod'], $out['popis_uvod']);
    }
    return array_filter($out);
}

/**
 * Převod. $prepsat = přepsat i hodnoty, které už modul má.
 * @return array počty: stranek, title, desc, image, noindex, preskoceno, globalni (seznam klíčů)
 */
function import_spustit(string $zdroj, bool $prepsat = false): array
{
    $vysledek = array('stranek' => 0, 'title' => 0, 'desc' => 0, 'image' => 0, 'noindex' => 0, 'preskoceno' => 0, 'globalni' => array());
    if (!import_klice($zdroj)) {
        return $vysledek;
    }
    $ulozit = array('title' => META_TITLE, 'desc' => META_DESC, 'image' => META_IMAGE, 'noindex' => META_NOINDEX);
    foreach (import_prispevky($zdroj) as $id) {
        $h = import_hodnoty($zdroj, $id);
        $v = vlastni($id);
        $stavajici = array('title' => $v['title'], 'desc' => $v['description'], 'image' => $v['image_id'], 'noindex' => $v['noindex']);
        $vysledek['stranek']++;
        foreach ($ulozit as $klic => $meta) {
            if (empty($h[$klic])) {
                continue;
            }
            if (!empty($stavajici[$klic]) && !$prepsat) {
                $vysledek['preskoceno']++;
                continue;
            }
            $hodnota = $klic === 'noindex' ? '1' : $h[$klic];
            update_post_meta($id, $meta, is_string($hodnota) ? wp_slash($hodnota) : $hodnota);
            $vysledek[$klic]++;
        }
    }

    // Nastavení webu – jen prázdné hodnoty modulu (nebo všechny při $prepsat).
    $s = get_option(OPTION, array());
    $s = array_merge(prazdne(), is_array($s) ? $s : array());
    $zmena = false;
    foreach (import_globalni_hodnoty($zdroj) as $k => $hodnota) {
        $cil = $k === 'popis_uvod' ? 'popis' : $k;
        $prazdna = $cil === 'oddelovac' ? ($s['oddelovac'] === '|' || $s['oddelovac'] === '') : empty($s[$cil]);
        if ($prazdna || $prepsat) {
            $s[$cil] = $hodnota;
            $vysledek['globalni'][] = $cil;
            $zmena = true;
        }
    }
    if ($zmena) {
        update_option(OPTION, sanitizovat($s));
    }
    update_option(OPTION . '_import', array('zdroj' => $zdroj, 'cas' => time(), 'vysledek' => $vysledek), false);
    return $vysledek;
}

add_action('admin_post_marmal_seo_import', function () {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Nemáš oprávnění.', 'marmal-breakdance-plus'), 403);
    }
    check_admin_referer('marmal_seo_import');
    $zdroj   = sanitize_key(wp_unslash($_POST['zdroj'] ?? ''));
    $prepsat = !empty($_POST['prepsat']);
    if (!isset(import_zdroje()[$zdroj])) {
        wp_die(esc_html__('Neznámý zdroj.', 'marmal-breakdance-plus'), 400);
    }
    import_spustit($zdroj, $prepsat);
    wp_safe_redirect(add_query_arg(array('page' => 'marmal-seo', 'prevedeno' => $zdroj), admin_url('admin.php')) . '#prevod');
    exit;
});
