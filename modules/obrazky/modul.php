<?php
/**
 * Modul Obrázky (od Breakdance Plus 0.16.0) – aby na web nikdy nešly zbytečně těžké obrázky.
 *
 *  includes/data.php       nastavení, podpora serveru, konfliktní pluginy, log, evidence, cesty
 *  includes/editor.php     úprava jednoho souboru: orientace, zmenšení, metadata, uložení WebP/AVIF
 *  includes/prevod.php     A) převod JPG/PNG při nahrání (filtry wp_handle_upload…)
 *  includes/knihovna.php   B) hromadný převod: inventura, záloha, dávky, přepis odkazů, CSS, vrácení, úklid
 *  includes/admin.php      MarMal → Obrázky, AJAX, sloupec v knihovně médií
 *
 * Na frontendu modul nic nevypisuje ani nebufferuje – převod při nahrání musí běžet i v REST
 * požadavcích (builder, mobilní aplikace), proto se data.php a prevod.php načítají vždy.
 */

namespace MarmalElements\Obrazky;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/editor.php';
require_once __DIR__ . '/includes/prevod.php';

if (is_admin()) {
    require_once __DIR__ . '/includes/knihovna.php';
    require_once __DIR__ . '/includes/admin.php';
}
