<?php
/**
 * Modul SEO a sdílení (od Breakdance Plus 0.18.0).
 *
 *  includes/data.php       nastavení, sanitizace, jiné SEO pluginy, výpočet hodnot stránky (bez HTML)
 *  includes/obrazek.php    obrázek pro sdílení 1200 × 630 (JPEG, vytváří se jen pro použité obrázky)
 *  includes/frontend.php   <title>, meta description, canonical, robots, Open Graph, Twitter, sitemap
 *  includes/drobecky.php   drobečková navigace [marmal_drobecky] + JSON-LD BreadcrumbList
 *  includes/metabox.php    box „SEO a sdílení“ u stránky / příspěvku / jednotky (klasický i blokový editor)
 *  includes/admin.php      MarMal → SEO (nastavení webu, kontrola, převod)
 *  includes/import.php     převod z Yoast SEO a Rank Math
 *
 * Pořadí hodnot: vyplněno u stránky → filtr `marmal_seo_data` (např. Developerské projekty) →
 * výchozí (vzor titulku, výchozí popisek, náhledový obrázek / výchozí obrázek webu).
 *
 * Ukládání: post meta _marmal_seo_title, _marmal_seo_desc, _marmal_seo_image, _marmal_seo_noindex,
 * nastavení webu v option marmal_bdp_seo. Bez ACF.
 */

namespace MarmalElements\Seo;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/obrazek.php';
require_once __DIR__ . '/includes/frontend.php';
require_once __DIR__ . '/includes/drobecky.php';

if (is_admin()) {
    require_once __DIR__ . '/includes/metabox.php';
    require_once __DIR__ . '/includes/import.php';
    require_once __DIR__ . '/includes/admin.php';
}
