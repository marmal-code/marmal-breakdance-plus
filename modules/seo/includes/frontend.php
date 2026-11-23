<?php
/**
 * Modul SEO – výstup na webu.
 *
 *  <title>                 přes pre_get_document_title (WordPress / Breakdance ho vypisuje přes title-tag)
 *  meta description, canonical, Open Graph, Twitter   wp_head priorita 1
 *  robots noindex          přes filtr wp_robots (jedna značka robots spolu s WordPressem)
 *  sitemap                 stránky s noindex a vypnuté archivy pryč z /wp-sitemap.xml
 *
 * Když běží jiný SEO plugin, nic z toho se nezapojí.
 */

namespace MarmalElements\Seo;

if (!defined('ABSPATH')) {
    exit;
}

/** Běží výstup na webu (ne administrace, builder, REST, AJAX, cron…). */
function bezi(): bool
{
    static $r = null;
    if (null !== $r && !defined('MARMAL_SEO_TEST')) {
        return $r;
    }
    // phpcs:disable WordPress.Security.NonceVerification -- jen čtení příznaků builderu, nic se neukládá
    $builder = isset($_GET['breakdance']) || isset($_GET['breakdance_iframe'])
        || (function_exists('\Breakdance\isRequestFromBuilderIframe') && \Breakdance\isRequestFromBuilderIframe());
    // phpcs:enable
    $r = !is_admin() && !wp_doing_ajax() && !wp_doing_cron() && !$builder
        && !(defined('REST_REQUEST') && REST_REQUEST) && !(defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)
        && !is_feed() && !is_robots() && !is_embed() && vypisuje();
    return $r;
}

// ================================================================= titulek

add_filter('pre_get_document_title', function ($titulek) {
    if (!bezi()) {
        return $titulek;
    }
    $k = kontext();
    return $k['title'] !== '' ? $k['title'] : $titulek;
}, 20);

// ================================================================= robots (noindex)

add_filter('wp_robots', function ($robots) {
    if (!bezi()) {
        return $robots;
    }
    if (kontext()['noindex']) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
        unset($robots['index'], $robots['max-image-preview']);
    }
    return $robots;
}, 20);

// ================================================================= canonical: jen náš (výchozí WordPressu pryč)

add_action('wp', function () {
    if (bezi()) {
        remove_action('wp_head', 'rel_canonical');
    }
});

// ================================================================= hlavička

/** Řádky hlavičky jako pole [typ, atribut, hodnota] – testovatelné bez výpisu. */
function znacky(array $k): array
{
    $z = array();
    if ($k['description'] !== '') {
        $z[] = array('meta', 'name', 'description', $k['description']);
    }
    if ($k['canonical'] !== '' && !$k['noindex']) {
        $z[] = array('link', 'canonical', absolutni($k['canonical']));
    }
    if (!$k['og']) {
        return $z;
    }
    $url = $k['canonical'] !== '' ? absolutni($k['canonical']) : '';
    $z[] = array('meta', 'property', 'og:locale', locale_og());
    foreach (locale_alternativy((int) $k['post_id']) as $loc) {
        $z[] = array('meta', 'property', 'og:locale:alternate', $loc);
    }
    $z[] = array('meta', 'property', 'og:type', $k['og_type']);
    $z[] = array('meta', 'property', 'og:title', $k['title']);
    if ($k['description'] !== '') {
        $z[] = array('meta', 'property', 'og:description', $k['description']);
    }
    if ($url !== '') {
        $z[] = array('meta', 'property', 'og:url', $url);
    }
    $z[] = array('meta', 'property', 'og:site_name', nazev_webu());
    if ($k['og_type'] === 'article' && $k['post_id']) {
        $z[] = array('meta', 'property', 'article:published_time', (string) get_post_time('c', true, $k['post_id']));
        $z[] = array('meta', 'property', 'article:modified_time', (string) get_post_modified_time('c', true, $k['post_id']));
    }
    $img = $k['image_id'] ? og_obrazek((int) $k['image_id']) : null;
    if ($img) {
        $z[] = array('meta', 'property', 'og:image', $img['url']);
        if (strpos($img['url'], 'https://') === 0) {
            $z[] = array('meta', 'property', 'og:image:secure_url', $img['url']);
        }
        if ($img['width'] && $img['height']) {
            $z[] = array('meta', 'property', 'og:image:width', (string) $img['width']);
            $z[] = array('meta', 'property', 'og:image:height', (string) $img['height']);
        }
        if ($img['type'] !== '') {
            $z[] = array('meta', 'property', 'og:image:type', $img['type']);
        }
        $z[] = array('meta', 'property', 'og:image:alt', $img['alt'] !== '' ? $img['alt'] : $k['title']);
    }
    $z[] = array('meta', 'name', 'twitter:card', $img ? 'summary_large_image' : 'summary');
    $z[] = array('meta', 'name', 'twitter:title', $k['title']);
    if ($k['description'] !== '') {
        $z[] = array('meta', 'name', 'twitter:description', $k['description']);
    }
    if ($img) {
        $z[] = array('meta', 'name', 'twitter:image', $img['url']);
    }
    return $z;
}

function hlavicka_html(array $k): string
{
    $out = "\n<!-- Marmal SEO -->\n";
    foreach (znacky($k) as $z) {
        if ($z[0] === 'link') {
            $out .= '<link rel="' . esc_attr($z[1]) . '" href="' . esc_url($z[2]) . '">' . "\n";
            continue;
        }
        $hodnota = in_array($z[2], array('og:url', 'og:image', 'og:image:secure_url', 'twitter:image'), true) ? esc_url($z[3]) : esc_attr($z[3]);
        $out    .= '<meta ' . esc_attr($z[1]) . '="' . esc_attr($z[2]) . '" content="' . $hodnota . '">' . "\n";
    }
    return $out . "<!-- / Marmal SEO -->\n";
}

add_action('wp_head', function () {
    if (!bezi()) {
        return;
    }
    echo hlavicka_html(kontext()); // phpcs:ignore WordPress.Security.EscapeOutput -- escapované v hlavicka_html()
}, 1);

// ================================================================= sitemap (/wp-sitemap.xml)

/** Stránky s noindex pryč ze sitemap. */
add_filter('wp_sitemaps_posts_query_args', function ($args) {
    if (!vypisuje()) {
        return $args;
    }
    $args['meta_query']   = isset($args['meta_query']) && is_array($args['meta_query']) ? $args['meta_query'] : array();
    $args['meta_query'][] = array(
        'relation' => 'OR',
        array('key' => META_NOINDEX, 'compare' => 'NOT EXISTS'),
        array('key' => META_NOINDEX, 'value' => '1', 'compare' => '!='),
    );
    // Pluginy (Developerské projekty) mohou vyřadit další stránky.
    $vyradit = array_filter(array_map('absint', (array) apply_filters('marmal_seo_sitemap_vyradit', array(), $args['post_type'] ?? '')));
    if ($vyradit) {
        $args['post__not_in'] = array_merge(isset($args['post__not_in']) ? (array) $args['post__not_in'] : array(), $vyradit);
    }
    return $args;
});

/** Archivy autorů a štítků s noindex pryč ze sitemap. */
add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
    if ($name === 'users' && vypisuje() && nastaveni()['noindex_autor']) {
        return false;
    }
    return $provider;
}, 10, 2);

add_filter('wp_sitemaps_taxonomies', function ($taxonomie) {
    if (vypisuje() && nastaveni()['noindex_stitky']) {
        unset($taxonomie['post_tag']);
    }
    return $taxonomie;
});

// ================================================================= robots.txt (virtuální – generuje WordPress)

/**
 * Vlastní řádky z nastavení na konec robots.txt. Řádek „Sitemap:“ přidává WordPress sám.
 * Fyzický soubor robots.txt v kořeni webu má přednost – pak se nic z toho nepoužije.
 */
add_filter('robots_txt', function ($obsah, $verejny) {
    if (!vypisuje() || !$verejny) {
        return $obsah;
    }
    $vlastni = robots_sanitizovat(nastaveni()['robots']);
    if ($vlastni !== '') {
        $obsah = rtrim((string) $obsah) . "\n\n# Marmal SEO\n" . $vlastni . "\n";
    }
    return $obsah;
}, 20, 2);
