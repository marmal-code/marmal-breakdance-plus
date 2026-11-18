<?php
/**
 * Manifest modulu Fonty. Čte ho MarmalElements\Moduly.
 *
 * Výchozí stav VYPNUTO: po zapnutí modul začne fonty z Googlu stahovat na web – zapíná se vědomě.
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'id'         => 'fonty',
    'soubor'     => 'modul.php',
    'breakdance' => false,
    'zamceny'    => false,
    'poradi'     => 26,
    'vychozi'    => false,
    'konflikt'   => null,

    'texty'      => function () {
        return array(
            'nazev' => __('Fonty', 'marmal-breakdance-plus'),
            'popis' => __('Google Fonts z webu stáhne k sobě a návštěvníkům je posílá z vlastní domény – Google nedostane jejich IP adresu (GDPR) a stránka se načte rychleji. Fonty dál vybíráš v Breakdance jako dosud.', 'marmal-breakdance-plus'),
        );
    },
    'odkazy'     => function () {
        return array(
            __('Nastavení', 'marmal-breakdance-plus') => admin_url('admin.php?page=marmal-fonty'),
        );
    },
    'info'       => function () {
        if (!function_exists('MarmalElements\Fonty\sady')) {
            return '';
        }
        $chyby = array_filter(\MarmalElements\Fonty\sady(), function ($s) {
            return ($s['stav'] ?? '') !== 'ok';
        });
        return $chyby ? __('Některé fonty se nepodařilo stáhnout – načítají se dál z Googlu. Podrobnosti v MarMal → Fonty.', 'marmal-breakdance-plus') : '';
    },
);
