<?php
/**
 * Plugin Name:       Marmal – Breakdance Plus
 * Plugin URI:        https://github.com/marmal-code/marmal-breakdance-plus
 * Description:       Vlastní a vylepšené elementy pro Breakdance (Galerie Plus a další). Elementy se tvoří v Element Studiu.
 * Version:           0.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Martin Malý – marmal.cz
 * Author URI:        https://marmal.cz
 * License:           GPLv2 or later
 * Text Domain:       marmal-breakdance-plus
 * Update URI:        https://github.com/marmal-code/marmal-breakdance-plus
 */

namespace MarmalElements;

use function Breakdance\Util\getDirectoryPathRelativeToPluginFolder;

if (!defined('ABSPATH')) {
    exit;
}

define('MARMAL_BDP_VERSION', '0.2.0');
define('MARMAL_BDP_FILE', __FILE__);
define('MARMAL_BDP_DIR', plugin_dir_path(__FILE__));
define('MARMAL_BDP_URL', plugin_dir_url(__FILE__));

/*
 * 1) Automatické aktualizace z GitHubu (Plugin Update Checker).
 *    Nová verze = commit se zvýšenou Version (GitHub Action sama vytvoří Release se ZIPem).
 *    Repo je veřejné, proto není potřeba žádný token.
 */
require_once MARMAL_BDP_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';

$marmal_bdp_updater = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/marmal-code/marmal-breakdance-plus/',
    __FILE__,
    'marmal-breakdance-plus'
);
// Stahovat ZIP přiložený k Release (čistý balíček bez .github apod.).
$marmal_bdp_updater->getVcsApi()->enableReleaseAssets();

/*
 * 2) Složky pro Element Studio (elementy, makra, presety).
 *    MUSÍ být priorita < 10 – Breakdance načítá elementy na prioritě 10.
 *    Namespace "MarmalElements" se už nesmí měnit (rozbily by se vložené elementy).
 */
add_action('breakdance_loaded', function () {
    if (!function_exists('\Breakdance\ElementStudio\registerSaveLocation')
        || !function_exists('\Breakdance\Util\getDirectoryPathRelativeToPluginFolder')) {
        return;
    }

    $base = getDirectoryPathRelativeToPluginFolder(__DIR__);

    \Breakdance\ElementStudio\registerSaveLocation($base . '/elements', 'MarmalElements', 'element', 'Marmal – elementy', false);
    \Breakdance\ElementStudio\registerSaveLocation($base . '/macros',   'MarmalElements', 'macro',   'Marmal – makra',    false);
    \Breakdance\ElementStudio\registerSaveLocation($base . '/presets',  'MarmalElements', 'preset',  'Marmal – presety',  false);
}, 9);

/*
 * 3) Elementy – sdílená PHP logika (Element Studio ji nepřepisuje).
 */
require_once MARMAL_BDP_DIR . 'includes/gallery.php';

/*
 * 4) Upozornění v administraci, když Breakdance není aktivní.
 */
add_action('admin_notices', function () {
    if (did_action('breakdance_loaded') || !current_user_can('activate_plugins')) {
        return;
    }
    echo '<div class="notice notice-warning"><p><strong>Marmal – Breakdance Plus:</strong> '
        . esc_html__('Plugin potřebuje aktivní Breakdance. Elementy se nenačtou.', 'marmal-breakdance-plus')
        . '</p></div>';
});
