<?php
/**
 * Modul Cookies (od Breakdance Plus 0.11.0) – cookie lišta podle pravidel ÚOOÚ.
 *
 *  includes/data.php      nastavení, kategorie, registr služeb, sanitizace
 *  includes/frontend.php  Consent Mode v2 + GA4 v hlavičce, blokování skriptů / iframů, lišta, shortcody
 *  includes/souhlasy.php  záznam souhlasů (REST + tabulka), úklid, export, revize
 *  includes/admin.php     MarMal → Cookies
 *  assets/cookies.js|css  lišta a okno nastavení (vzhled podle schváleného prototypu)
 *
 * Nastavení: option marmal_bdp_cookies. Souhlas návštěvníka: cookie marmal_consent
 * ({"r":revize,"k":[kategorie],"id":"…","t":čas}). Veřejné JS API: window.MarmalCookies.
 */

namespace MarmalElements\Cookies;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/frontend.php';
require_once __DIR__ . '/includes/souhlasy.php';

if (is_admin()) {
    require_once __DIR__ . '/includes/admin.php';
    require_once __DIR__ . '/includes/zasady.php';
}

/** Pro jiné moduly (Patička): běží modul Cookies? */
function je_aktivni(): bool
{
    return true;
}
