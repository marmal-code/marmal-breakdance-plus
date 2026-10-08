<?php
/**
 * Manifest modulu Galerie Plus.
 *
 * Modul je ZAMČENÝ (vždy zapnutý): element je registrovaný přes Element Studio
 * a vypnutím by zmizel ze všech stránek, kde je vložený. Jeho CSS a JS se
 * načítá jen na stránkách, kde je galerie použitá, takže web nijak nezatěžuje.
 * Kód: elements/Galerie_Plus/ + includes/gallery.php (načítá hlavní soubor pluginu).
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'id'         => 'galerie',
    'soubor'     => null,
    'breakdance' => true,
    'zamceny'    => true,
    'poradi'     => 10,
    'vychozi'    => true,
    'konflikt'   => null,
    'texty'      => function () {
        return array(
            'nazev' => __('Galerie Plus', 'marmal-breakdance-plus'),
            'popis' => __('Breakdance element: mozaika se zarovnanými okraji (20 vzorů) nebo slider s miniaturami, efekty po najetí, video dlaždice a vlastní lightbox. CSS a JS se načítá jen na stránkách s galerií.', 'marmal-breakdance-plus'),
        );
    },
    'odkazy'     => null,
    'info'       => null,
);
