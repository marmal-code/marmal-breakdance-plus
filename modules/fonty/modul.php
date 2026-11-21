<?php
/**
 * Modul Fonty (od Breakdance Plus 0.13.0) – Google Fonts z vlastní domény.
 *
 *  includes/data.php        nastavení, adresář, evidence stažených sad, ověření adres
 *  includes/stahovani.php   stažení CSS z Googlu, rozbor @font-face, stažení woff2, přepis CSS
 *  includes/preload.php     přednačtení podle Global Settings / ručně, přehled „odkud se písmo bere“
 *  includes/frontend.php    výstup stránky: odkazy na fonts.googleapis.com → lokální CSS, preload
 *  includes/admin.php       MarMal → Fonty
 *
 * Princip: ve výsledném HTML se najde každý odkaz na fonts.googleapis.com/css(2) (link i @import).
 * Při prvním výskytu se sada jednou stáhne do uploads/marmal-fonty/, pak se odkaz nahradí lokálním
 * CSS. Nezáleží na tom, kdo odkaz přidal (Breakdance, jiný plugin, Custom Code). Builder se nemění.
 */

namespace MarmalElements\Fonty;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/stahovani.php';
require_once __DIR__ . '/includes/preload.php';
require_once __DIR__ . '/includes/frontend.php';

if (is_admin()) {
    require_once __DIR__ . '/includes/admin.php';
}
