<?php
/**
 * Modul Ochrana osobních údajů (od Breakdance Plus 0.15.0).
 *
 *  includes/data.php       nastavení, údaje o správci (z Patičky + doplňky), zvýraznění „DOPLŇTE“, cílová stránka
 *  includes/vzor.php       text stránky podle čl. 13 GDPR (účely podle nastavení)
 *  includes/shortcody.php  [marmal_gdpr_spravce] [marmal_gdpr_kontakt] [marmal_gdpr_cookies] [marmal_gdpr_ucinnost] [marmal_gdpr_formular]
 *  includes/admin.php      MarMal → Ochrana osobních údajů: návod, nastavení, vytvoření návrhu, zveřejnění
 *
 * Bezpečný postup: modul nikdy nepřepíše zveřejněnou stránku. Vytvoří KONCEPT, správce ho zkontroluje
 * a doplní zvýrazněná místa, tlačítkem „Zveřejnit“ koncept převezme adresu dosavadní stránky zásad
 * (ta zůstane jako koncept – jde se k ní vrátit).
 */

namespace MarmalElements\Gdpr;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/vzor.php';
require_once __DIR__ . '/includes/shortcody.php';

if (is_admin()) {
    require_once __DIR__ . '/includes/admin.php';
}
