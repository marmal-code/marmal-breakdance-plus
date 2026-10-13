<?php
/**
 * Manifest modulu superHERO.
 *
 * Modul je ZAMČENÝ (vždy zapnutý): element je registrovaný přes Element Studio
 * a vypnutím by zmizel ze všech stránek, kde je vložený. CSS a JS se načítá jen
 * na stránkách, kde je superHERO použitý.
 * Kód: elements/Super_Hero/ + includes/hero.php + assets/hero/ (načítá hlavní soubor pluginu).
 * Ovládací prvky se generují nástrojem tools/hero_prvky.py.
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'id'         => 'hero',
    'soubor'     => null,
    'breakdance' => true,
    'zamceny'    => true,
    'poradi'     => 15,
    'vychozi'    => true,
    'konflikt'   => null,
    'texty'      => function () {
        return array(
            'nazev' => __('superHERO', 'marmal-breakdance-plus'),
            'popis' => __('Breakdance element pro úvodní sekci: 5 rozložení, pozadí z fotky, videa, slideru nebo živého přechodu, tlačítka s efekty, důvěra, otevírací doba, masky obrázků, dividery a typografie každého prvku. JS se načítá jen na stránkách s elementem.', 'marmal-breakdance-plus'),
        );
    },
    'odkazy'     => null,
    'info'       => null,
);
