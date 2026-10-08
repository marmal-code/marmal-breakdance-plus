<?php
/**
 * Modul Efekty (dříve samostatný plugin MarMal Effects 1.x).
 *
 * Knihovna CSS efektů – třídy mm-… se vkládají do pole Classes v Breakdance.
 * Načítá ho MarmalElements\Moduly, jen když je modul zapnutý a na webu neběží
 * starý plugin MarMal Effects (jinak by se funkce mm_effects_* deklarovaly dvakrát).
 *
 * Názvy mm-… / mm_effects_… / option mm_effects_settings jsou historické a NEMĚNÍ SE:
 * třídy jsou vložené v obsahu stránek a nastavení barev přechází ze starého pluginu samo.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MM_EFFECTS_DIR', __DIR__ . '/');
define('MM_EFFECTS_URL', plugin_dir_url(__FILE__));

/** Verze efektů = verze pluginu Breakdance Plus (kvůli cache souborů). */
function mm_effects_version()
{
    return MARMAL_BDP_VERSION;
}

/**
 * Kategorie efektů v pořadí, v jakém jsou v mm-effects.css (na pořadí záleží kvůli kaskádě).
 * Klíč = id kategorie v data/effects.json = název části v assets/css/casti/.
 */
function mm_effects_kategorie()
{
    return array(
        'scroll'   => 'Animace při scrollu (vč. mm-delay-…)',
        'ramecky'  => 'Rámečky',
        'tlacitka' => 'Tlačítka',
        'karty'    => 'Karty a sloupce',
        'obrazky'  => 'Obrázky',
        'hero'     => 'Hero a sekce',
        'text'     => 'Text a nadpisy',
        'pozadi'   => 'Pozadí',
        'animbg'   => 'Animovaná pozadí',
        'ikony'    => 'Ikony a detaily',
    );
}

/** Zapnuté kategorie (null = všechny). */
function mm_effects_zapnute_kategorie()
{
    $s   = mm_effects_settings();
    $vse = array_keys(mm_effects_kategorie());
    if (!is_array($s['cats'])) {
        return null;
    }
    $zapnute = array_values(array_intersect($vse, $s['cats']));
    return count($zapnute) === count($vse) ? null : $zapnute;
}

require_once __DIR__ . '/nastaveni.php';

/* --------------------------------------------------------------------------
 * CSS pro web: všechny kategorie = celý mm-effects.css, jinak jeden spojený
 * soubor jen se zapnutými kategoriemi v uploads/marmal-bdp/ (vytvoří se při
 * první návštěvě po změně nastavení nebo aktualizaci pluginu).
 * ----------------------------------------------------------------------- */
function mm_effects_frontend_css_urls()
{
    $zapnute = mm_effects_zapnute_kategorie();
    if (null === $zapnute) {
        return array(MM_EFFECTS_URL . 'assets/css/mm-effects.css');
    }

    $casti = array_merge(array('00-zaklad'), $zapnute, array('99-omezeny-pohyb'));
    $nazev = 'efekty-' . substr(md5(mm_effects_version() . '|' . implode(',', $casti)), 0, 12) . '.css';

    $uploads = wp_upload_dir(null, false);
    if (empty($uploads['error'])) {
        $dir = trailingslashit($uploads['basedir']) . 'marmal-bdp';
        $url = trailingslashit($uploads['baseurl']) . 'marmal-bdp/' . $nazev;
        if (is_file($dir . '/' . $nazev)) {
            return array($url);
        }
        $css = '';
        foreach ($casti as $cast) {
            $css .= (string) file_get_contents(MM_EFFECTS_DIR . 'assets/css/casti/' . $cast . '.css') . "\n"; // phpcs:ignore WordPress.WP.AlternativeFunctions
        }
        if (wp_mkdir_p($dir) && false !== file_put_contents($dir . '/' . $nazev, $css, LOCK_EX)) { // phpcs:ignore WordPress.WP.AlternativeFunctions
            foreach ((array) glob($dir . '/efekty-*.css') as $stary) {
                if (basename($stary) !== $nazev) {
                    @unlink($stary); // phpcs:ignore WordPress.PHP.NoSilencedErrors
                }
            }
            return array($url);
        }
    }

    // Do uploads nejde zapisovat → načíst části jednotlivě (funguje, jen víc souborů).
    return array_map(function ($cast) {
        return MM_EFFECTS_URL . 'assets/css/casti/' . $cast . '.css';
    }, $casti);
}

/* --------------------------------------------------------------------------
 * Načtení na webu (i v editoru Breakdance)
 * ----------------------------------------------------------------------- */
add_action('init', function () {
    $v = mm_effects_version();
    // Celá knihovna – používá ji galerie v administraci.
    wp_register_style('mm-effects', MM_EFFECTS_URL . 'assets/css/mm-effects.css', array(), $v);
    wp_register_script('mm-effects', MM_EFFECTS_URL . 'assets/js/mm-effects.js', array(), $v, true);
});

add_action('wp_enqueue_scripts', function () {
    $v      = mm_effects_version();
    $urls   = mm_effects_frontend_css_urls();
    $handle = 'mm-effects';

    wp_deregister_style('mm-effects');
    foreach ($urls as $i => $url) {
        $h = 0 === $i ? $handle : $handle . '-' . $i;
        wp_enqueue_style($h, $url, 0 === $i ? array() : array($handle), $v);
    }
    $vars = mm_effects_inline_vars();
    if ($vars) {
        wp_add_inline_style($handle, $vars); // první soubor vždy obsahuje výchozí proměnné
    }

    if (mm_effects_settings()['load_js']) {
        wp_enqueue_script('mm-effects');
    }
}, 20);

/**
 * Třídu html.mm-js přidáme hned v <head>, aby animované prvky neproblikly.
 * Pojistka: když se hlavní skript do 3 s nenačte, všechno se zase ukáže.
 * V editoru Breakdance se nic neschovává.
 */
add_action('wp_head', function () {
    if (!mm_effects_settings()['load_js']) {
        return;
    }
    ?>
<script id="mm-effects-early">(function(d,l){if(/[?&](breakdance|breakdance_iframe)=/.test(l.search))return;d.classList.add('mm-js');setTimeout(function(){if(!window.MMEffects)d.classList.remove('mm-js');},3000);})(document.documentElement,location);</script>
    <?php
}, 1);

/* --------------------------------------------------------------------------
 * Administrace: galerie s náhledy + nastavení (pod menu MarMal)
 * ----------------------------------------------------------------------- */
add_action('admin_menu', function () {
    $GLOBALS['mm_effects_gallery_hook'] = add_submenu_page(
        \MarmalElements\Moduly::STRANKA,
        'Galerie efektů',
        'Efekty – galerie',
        'edit_posts',
        'mm-effects',
        'mm_effects_render_gallery'
    );
    add_submenu_page(
        \MarmalElements\Moduly::STRANKA,
        'Nastavení efektů',
        'Efekty – nastavení',
        'manage_options',
        'mm-effects-settings',
        'mm_effects_render_settings'
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (empty($GLOBALS['mm_effects_gallery_hook']) || $GLOBALS['mm_effects_gallery_hook'] !== $hook) {
        return;
    }
    $v = mm_effects_version();
    wp_enqueue_style('mm-effects');
    $vars = mm_effects_inline_vars();
    if ($vars) {
        wp_add_inline_style('mm-effects', $vars);
    }
    wp_enqueue_style('mm-effects-gallery', MM_EFFECTS_URL . 'assets/admin/gallery.css', array('mm-effects'), $v);
    wp_add_inline_style('mm-effects-gallery', ':root{--mmg-bg:#f0f0f1}' . mm_effects_admin_bde_css());

    wp_enqueue_script('mm-effects');
    wp_enqueue_script('mm-effects-gallery', MM_EFFECTS_URL . 'assets/admin/gallery.js', array('mm-effects'), $v, true);

    $json = file_get_contents(MM_EFFECTS_DIR . 'data/effects.json'); // phpcs:ignore WordPress.WP.AlternativeFunctions
    $data = json_decode((string) $json, true);
    wp_add_inline_script('mm-effects-gallery', 'window.MM_EFFECTS_DATA=' . wp_json_encode($data) . ';', 'before');
});

// Skript efektů musí vědět, že v galerii má animace opravdu přehrávat.
add_filter('script_loader_tag', function ($tag, $handle) {
    if ('mm-effects' === $handle && is_admin()) {
        $tag = '<script>window.MM_EFFECTS_FORCE=true;</script>' . $tag;
    }
    return $tag;
}, 10, 2);

function mm_effects_render_gallery()
{
    echo '<div class="wrap">';
    $zapnute = mm_effects_zapnute_kategorie();
    if (null !== $zapnute) {
        $vypnute = array_diff_key(mm_effects_kategorie(), array_flip($zapnute));
        echo '<div class="notice notice-info inline"><p>Na tomto webu se nenačítají kategorie: <strong>'
            . esc_html(implode(', ', $vypnute))
            . '</strong>. Jejich třídy na webu nebudou fungovat – zapneš je v '
            . '<a href="' . esc_url(admin_url('admin.php?page=mm-effects-settings')) . '">nastavení efektů</a>.</p></div>';
    }
    echo '<div id="mm-gallery" class="mm-g"></div></div>';
}

// Rychlý odkaz na galerii v horní liště (na webu i v administraci).
add_action('admin_bar_menu', function ($bar) {
    if (!current_user_can('edit_posts')) {
        return;
    }
    $bar->add_node(array(
        'id'    => 'mm-effects',
        'title' => 'MarMal Efekty',
        'href'  => admin_url('admin.php?page=mm-effects'),
        'meta'  => array('target' => '_blank'),
    ));
}, 90);
