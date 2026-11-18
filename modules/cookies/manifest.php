<?php
/**
 * Manifest modulu Cookies. Čte ho MarmalElements\Moduly.
 *
 * Výchozí stav VYPNUTO: po zapnutí se na webu hned zobrazí cookie lišta a začne
 * blokování skriptů – zapíná se vědomě, až je nastavené (a vypnutý starý cookie plugin).
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'id'         => 'cookies',
    'soubor'     => 'modul.php',
    'breakdance' => false,
    'zamceny'    => false,
    'poradi'     => 25,
    'vychozi'    => false,
    // Dva cookie pluginy najednou = dvě lišty a zmatek v souhlasech.
    'konflikt'   => null,

    'texty'      => function () {
        return array(
            'nazev' => __('Cookies', 'marmal-breakdance-plus'),
            'popis' => __('Cookie lišta podle pravidel ÚOOÚ: souhlas předem, Odmítnout vše v první vrstvě, okno nastavení po kategoriích, Google Consent Mode v2, GA4, blokování skriptů a náhledy videí a map do souhlasu, seznam cookies a záznam souhlasů. Vzhled z Global Settings Breakdance.', 'marmal-breakdance-plus'),
        );
    },
    'odkazy'     => function () {
        return array(
            __('Nastavení', 'marmal-breakdance-plus') => admin_url('admin.php?page=marmal-cookies'),
            __('Náhled na webu', 'marmal-breakdance-plus') => add_query_arg('marmal_cookies_nahled', '1', home_url('/')),
        );
    },
    'info'       => function () {
        if (!function_exists('MarmalElements\Cookies\konkurence')) {
            return '';
        }
        $jine = \MarmalElements\Cookies\konkurence();
        /* translators: %s: názvy pluginů */
        return $jine ? sprintf(__('Pozor: běží i %s – vypni ho, jinak budou na webu dvě lišty.', 'marmal-breakdance-plus'), implode(', ', $jine)) : '';
    },
);
