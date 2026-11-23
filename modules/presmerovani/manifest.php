<?php
/**
 * Manifest modulu Přesměrování. Čte ho MarmalElements\Moduly.
 *
 * Výchozí stav VYPNUTO. Funguje nezávisle na modulu SEO i vedle Rank Mathu / Yoastu.
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'id'         => 'presmerovani',
    'soubor'     => 'modul.php',
    'breakdance' => false,
    'zamceny'    => false,
    'poradi'     => 28,
    'vychozi'    => false,
    'konflikt'   => null,

    'texty'      => function () {
        return array(
            'nazev' => __('Přesměrování', 'marmal-breakdance-plus'),
            'popis' => __('Přesměrování starých adres (301, 302, 307, 410) – jednotlivé adresy i celé sekce, automaticky při změně adresy stránky, hlídání chyb 404, převod ze starého webu s návrhy nových stránek a import / export CSV. Pro redesign webu.', 'marmal-breakdance-plus'),
        );
    },
    'odkazy'     => function () {
        return array(
            __('Nastavení', 'marmal-breakdance-plus') => admin_url('admin.php?page=marmal-presmerovani'),
        );
    },
    'info'       => function () {
        if (!function_exists('MarmalElements\Presmerovani\konkurence')) {
            return '';
        }
        $k = \MarmalElements\Presmerovani\konkurence();
        return $k ? sprintf(
            /* translators: %s: názvy pluginů */
            __('Na webu běží i %s – přesměrování nastavuj jen na jednom místě.', 'marmal-breakdance-plus'),
            implode(', ', $k)
        ) : '';
    },
);
