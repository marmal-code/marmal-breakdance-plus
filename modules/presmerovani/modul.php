<?php
/**
 * Modul Přesměrování (od Breakdance Plus 0.20.0).
 *
 *  includes/data.php        nastavení, tabulky, úprava adres, ukládání a hledání pravidel, záznam 404 (bez HTML)
 *  includes/frontend.php    přesměrování na webu (template_redirect 0) a záznam chyb 404
 *  includes/auto.php        přesměrování při změně adresy publikované stránky (i podstránek)
 *  includes/stary-web.php   převod ze starého webu – adresy ze sitemapy / seznamu a návrhy nových stránek
 *  includes/csv.php         import / export CSV
 *  includes/admin.php       MarMal → Přesměrování
 *
 * Tabulky {prefix}marmal_presmerovani (pravidla) a {prefix}marmal_404 (chyby 404 – bez IP adres
 * a prohlížečů, odkud jen doména + cesta). Pravidla se hledají podle md5 cesty (index),
 * sekce (adresa končící *) jsou v autoload option jako krátký seznam.
 */

namespace MarmalElements\Presmerovani;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/frontend.php';
require_once __DIR__ . '/includes/auto.php';

if (is_admin()) {
    require_once __DIR__ . '/includes/stary-web.php';
    require_once __DIR__ . '/includes/csv.php';
    require_once __DIR__ . '/includes/admin.php';
}
