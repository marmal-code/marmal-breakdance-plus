<?php
/**
 * Manifest modulu SEO. Čte ho MarmalElements\Moduly.
 *
 * Výchozí stav VYPNUTO: po zapnutí modul mění titulky a hlavičku všech stránek – zapíná se vědomě.
 * Když na webu běží Yoast, Rank Math, SEOPress, AIOSEO nebo The SEO Framework, nic nevypisuje.
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'id'         => 'seo',
    'soubor'     => 'modul.php',
    'breakdance' => false,
    'zamceny'    => false,
    'poradi'     => 24,
    'vychozi'    => false,
    'konflikt'   => null,

    'texty'      => function () {
        return array(
            'nazev' => __('SEO a sdílení', 'marmal-breakdance-plus'),
            'popis' => __('Titulek, popisek, canonical a náhled pro sdílení (Open Graph, Twitter) u každé stránky. Výchozí hodnoty pro celý web, přepsání u stránky, automatické texty z jiných pluginů (Developerské projekty), noindex i v sitemap a převod z Yoastu / Rank Mathu. Když běží jiný SEO plugin, modul nic nevypisuje.', 'marmal-breakdance-plus'),
        );
    },
    'odkazy'     => function () {
        return array(
            __('Nastavení', 'marmal-breakdance-plus') => admin_url('admin.php?page=marmal-seo'),
        );
    },
    'info'       => function () {
        if (!function_exists('MarmalElements\Seo\upozorneni')) {
            return '';
        }
        return implode(' ', array_column(\MarmalElements\Seo\upozorneni(), 'text'));
    },
);
