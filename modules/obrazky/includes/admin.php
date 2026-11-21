<?php
/**
 * Modul Obrázky – stránka MarMal → Obrázky, AJAX a sloupec v knihovně médií.
 * Nastavení přes options.php (register_setting), akce přes admin-ajax s nonce a manage_options.
 * Nic se nespustí bez kliknutí.
 */

namespace MarmalElements\Obrazky;

if (!defined('ABSPATH')) {
    exit;
}

const STRANKA = 'marmal-obrazky';

add_action('admin_init', function () {
    register_setting('marmal_obrazky', OPTION, array(
        'type'              => 'array',
        'sanitize_callback' => __NAMESPACE__ . '\sanitizovat',
        'show_in_rest'      => false,
    ));
});

add_action('admin_menu', function () {
    add_submenu_page('marmal', __('Obrázky', 'marmal-breakdance-plus'), __('Obrázky', 'marmal-breakdance-plus'), 'manage_options', STRANKA, __NAMESPACE__ . '\stranka');
}, 20);

add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos((string) $hook, STRANKA) === false) {
        return;
    }
    $url = MARMAL_BDP_URL . 'modules/obrazky/assets/';
    wp_enqueue_style('marmal-bdp-moduly', MARMAL_BDP_URL . 'assets/admin/moduly.css', array(), MARMAL_BDP_VERSION);
    wp_enqueue_style('marmal-obrazky', $url . 'admin.css', array('marmal-bdp-moduly'), MARMAL_BDP_VERSION);
    wp_enqueue_script('marmal-obrazky', $url . 'admin.js', array(), MARMAL_BDP_VERSION, true);
    wp_add_inline_script('marmal-obrazky', 'window.MARMAL_OBRAZKY=' . wp_json_encode(array(
        'ajax'  => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('marmal_obrazky'),
        'stav'  => stav_fronty(),
        'texty' => array(
            'potvrditStart'  => __("Spustit hromadný převod?\n\nModul zálohuje původní soubory i data stránek, převede používané obrázky do WebP a přepíše na ně odkazy v Breakdance. Staré soubory zůstanou na místě. Převod jde kdykoli pozastavit a celý vrátit.\n\nPřed spuštěním doporučuji mít i zálohu celého webu.", 'marmal-breakdance-plus'),
            'potvrditVratit' => __('Vrátit převod? Obnoví se původní soubory, metadata i data stránek a soubory WebP se smažou.', 'marmal-breakdance-plus'),
            'potvrditUklid'  => __('Smazat staré JPG a PNG převedených obrázků? Zůstanou jen v záloze modulu (dokud ji nesmažeš). Odkazy z revizí a z míst, která modul nezná, přestanou fungovat.', 'marmal-breakdance-plus'),
            'potvrditZaloha' => __('Smazat zálohu dávky? Když už jsou smazané i staré soubory, převod pak nepůjde vrátit.', 'marmal-breakdance-plus'),
            'chyba'          => __('Chyba:', 'marmal-breakdance-plus'),
            'spojeni'        => __('Požadavek se nepovedl (časový limit nebo spojení). Klikni na „Pokračovat“ – fronta pokračuje tam, kde skončila.', 'marmal-breakdance-plus'),
            'pozastaveno'    => __('Pozastaveno. Fronta zůstává uložená – pokračovat jde kdykoli, i po zavření prohlížeče.', 'marmal-breakdance-plus'),
            'hotovo'         => __('Hotovo – načítám výsledek…', 'marmal-breakdance-plus'),
            'pracuji'        => __('Pracuji…', 'marmal-breakdance-plus'),
        ),
    )) . ';', 'before');
});

// ================================================================= AJAX

add_action('wp_ajax_marmal_obrazky', function () {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('zprava' => __('Nemáte oprávnění.', 'marmal-breakdance-plus')), 403);
    }
    check_ajax_referer('marmal_obrazky', 'nonce');

    $ukon  = sanitize_key(wp_unslash($_POST['ukon'] ?? ''));
    $davka = preg_replace('/[^0-9-]/', '', (string) wp_unslash($_POST['davka'] ?? ''));
    $id    = absint($_POST['priloha'] ?? 0);

    try {
        switch ($ukon) {
            case 'inventura':
                $r = inventura();
                $r = array('kandidati' => count($r['kandidati']));
                break;
            case 'start':
                $r = zahajit_prevod();
                break;
            case 'krok':
                $r = krok();
                break;
            case 'pauza':
                pozastavit();
                $r = stav_fronty();
                break;
            case 'vratit':
                $r = zahajit_vraceni((string) $davka, $id);
                break;
            case 'uklid':
                $r = uklidit((string) $davka);
                $r = is_wp_error($r) ? $r : array('smazano' => $r);
                break;
            case 'zaloha':
                $r = smazat_zalohu((string) $davka);
                $r = is_wp_error($r) ? $r : array('ok' => true);
                break;
            default:
                $r = new \WP_Error('marmal_obrazky', 'Neznámý úkon.');
        }
    } catch (\Throwable $e) {
        zapsat_log('AJAX ' . $ukon . ': ' . $e->getMessage());
        $r = new \WP_Error('marmal_obrazky', $e->getMessage());
    }

    if (is_wp_error($r)) {
        wp_send_json_error(array('zprava' => $r->get_error_message(), 'stav' => stav_fronty()));
    }
    wp_send_json_success($r);
});

// ================================================================= knihovna médií: sloupec

add_filter('manage_media_columns', function ($sloupce) {
    $sloupce['marmal_obrazky'] = __('Převod', 'marmal-breakdance-plus');
    return $sloupce;
});

add_action('manage_media_custom_column', function ($sloupec, $id) {
    if ($sloupec !== 'marmal_obrazky') {
        return;
    }
    static $prilohy = null;
    if (null === $prilohy) {
        $prilohy = zaloha()['prilohy'];
    }
    $p = $prilohy[(int) $id] ?? null;
    if (!$p || ($p['stav'] ?? '') !== 'prevedeno') {
        echo '–';
        return;
    }
    echo esc_html(sprintf(
        /* translators: 1: původní formát, 2: původní velikost, 3: nová velikost */
        __('%1$s %2$s → WebP %3$s', 'marmal-breakdance-plus'),
        strtoupper((string) str_replace('image/', '', (string) $p['mime'])),
        velikost((int) $p['velikost_pred']),
        velikost((int) $p['velikost_po'])
    ));
}, 10, 2);

// ================================================================= stránka

function odkaz_na_prispevek(int $post_id): string
{
    $p = get_post($post_id);
    if (!$p) {
        return '';
    }
    $nazev = get_the_title($p);
    $nazev = $nazev !== '' ? $nazev : '#' . $post_id;
    $url   = (is_post_type_viewable($p->post_type) && $p->post_status === 'publish') ? get_permalink($p) : get_edit_post_link($p->ID, 'raw');
    return $url ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($nazev) . '</a>' : esc_html($nazev);
}

function stranka(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $s       = nastaveni();
    $p       = podpora();
    $jine    = konkurence();
    $prehled = prehled();
    $fronta  = stav_fronty();
    $z       = zaloha();
    $davky   = $z['davky'];
    uasort($davky, function ($a, $b) {
        return ($b['datum'] ?? 0) <=> ($a['datum'] ?? 0);
    });
    $posledni = posledni_davka();
    $opt      = esc_attr(OPTION);
    $ano      = '<span class="marmal-ob__ok">✓</span>';
    $ne       = '<span class="marmal-ob__ne">✗</span>';
    ?>
    <div class="wrap marmal-ob">
        <h1><?php esc_html_e('MarMal – Obrázky', 'marmal-breakdance-plus'); ?></h1>
        <p class="marmal-moduly__uvod"><?php esc_html_e('Na web nepůjdou zbytečně těžké obrázky: nové JPG a PNG se při nahrání uloží jako WebP v rozumné velikosti, už nahrané se dají převést hromadně – i s přepisem odkazů v Breakdance, zálohou a možností vrácení.', 'marmal-breakdance-plus'); ?></p>

        <?php settings_errors(); ?>

        <!-- ============================================================ 1. Nastavení -->
        <h2><?php esc_html_e('1. Nastavení', 'marmal-breakdance-plus'); ?></h2>
        <div class="marmal-ob__server">
            <span><?php echo $p['webp'] ? $ano : $ne; // phpcs:ignore WordPress.Security.EscapeOutput -- pevné HTML výše ?> WebP</span>
            <span><?php echo $p['avif'] ? $ano : $ne; // phpcs:ignore WordPress.Security.EscapeOutput ?> AVIF</span>
            <span><?php esc_html_e('Knihovna:', 'marmal-breakdance-plus'); ?> <strong><?php echo esc_html($p['editor'] !== '' ? $p['editor'] : '–'); ?></strong></span>
            <span><?php echo $p['exif'] ? $ano : $ne; // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e('rozšíření exif', 'marmal-breakdance-plus'); ?></span>
        </div>
        <?php if (!$p['webp']) : ?>
            <div class="notice notice-error inline"><p><?php esc_html_e('Server neumí ukládat WebP (chybí Imagick i GD s podporou WebP). Modul nic nepřevádí – požádej hosting o zapnutí Imagick.', 'marmal-breakdance-plus'); ?></p></div>
        <?php elseif ($p['editor'] === 'GD') : ?>
            <p class="description"><?php esc_html_e('Převádí GD (Imagick na serveru chybí): funguje, jen se neponechá barevný profil ICC. Imagick je lepší – hosting ho obvykle zapne v nastavení PHP.', 'marmal-breakdance-plus'); ?></p>
        <?php endif; ?>
        <?php if ($jine) : ?>
            <div class="notice notice-warning inline"><p><?php
                /* translators: %s: názvy pluginů */
                echo esc_html(sprintf(__('Na webu běží i %s. Dva převaděče obrázků najednou se perou (dvojí převod, jiné odkazy) – jeden z nich vypni. U Smush to platí, jen když má zapnuté WebP.', 'marmal-breakdance-plus'), implode(', ', $jine)));
            ?></p></div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields('marmal_obrazky'); ?>
            <table class="form-table" role="presentation">
                <tr><th scope="row"><?php esc_html_e('Formát při nahrání', 'marmal-breakdance-plus'); ?></th><td>
                    <select name="<?php echo $opt; // phpcs:ignore WordPress.Security.EscapeOutput -- esc_attr výše ?>[cil]">
                        <option value="webp" <?php selected($s['cil'], 'webp'); ?>>WebP (<?php esc_html_e('doporučeno', 'marmal-breakdance-plus'); ?>)</option>
                        <option value="avif" <?php selected($s['cil'], 'avif'); ?> <?php disabled(!$p['avif']); ?>>AVIF<?php echo $p['avif'] ? '' : ' – ' . esc_html__('server neumí', 'marmal-breakdance-plus'); ?></option>
                        <option value="vypnuto" <?php selected($s['cil'], 'vypnuto'); ?>><?php esc_html_e('Nepřevádět při nahrání', 'marmal-breakdance-plus'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('AVIF je menší, ale převod je pomalejší a starší Safari ho neumí. Hromadný převod knihovny vždy ukládá WebP.', 'marmal-breakdance-plus'); ?></p></td></tr>
                <tr><th scope="row"><label for="marmal-ob-kvalita"><?php esc_html_e('Kvalita', 'marmal-breakdance-plus'); ?></label></th><td>
                    <input id="marmal-ob-kvalita" type="number" min="40" max="100" name="<?php echo $opt; // phpcs:ignore WordPress.Security.EscapeOutput ?>[kvalita]" value="<?php echo (int) $s['kvalita']; ?>" class="small-text">
                    <p class="description"><?php esc_html_e('80 je dobrý kompromis – rozdíl proti originálu na webu není vidět.', 'marmal-breakdance-plus'); ?></p></td></tr>
                <tr><th scope="row"><label for="marmal-ob-max"><?php esc_html_e('Maximální delší strana', 'marmal-breakdance-plus'); ?></label></th><td>
                    <input id="marmal-ob-max" type="number" min="0" max="8000" step="10" name="<?php echo $opt; // phpcs:ignore WordPress.Security.EscapeOutput ?>[max]" value="<?php echo (int) $s['max']; ?>" class="small-text"> px
                    <p class="description"><?php esc_html_e('Větší obrázky se zmenší (bez ořezu). 0 = nezmenšovat.', 'marmal-breakdance-plus'); ?></p></td></tr>
                <tr><th scope="row"><?php esc_html_e('PNG', 'marmal-breakdance-plus'); ?></th><td>
                    <label><input type="hidden" name="<?php echo $opt; // phpcs:ignore WordPress.Security.EscapeOutput ?>[png]" value="0"><input type="checkbox" name="<?php echo $opt; // phpcs:ignore WordPress.Security.EscapeOutput ?>[png]" value="1" <?php checked($s['png']); ?>> <?php esc_html_e('Převádět i PNG (průhlednost zůstane)', 'marmal-breakdance-plus'); ?></label></td></tr>
                <tr><th scope="row"><?php esc_html_e('Původní soubor', 'marmal-breakdance-plus'); ?></th><td>
                    <label><input type="hidden" name="<?php echo $opt; // phpcs:ignore WordPress.Security.EscapeOutput ?>[ponechat]" value="0"><input type="checkbox" name="<?php echo $opt; // phpcs:ignore WordPress.Security.EscapeOutput ?>[ponechat]" value="1" <?php checked($s['ponechat']); ?>> <?php esc_html_e('Při nahrání ponechat i původní JPG / PNG v uploads (zabírá místo, v knihovně médií není vidět)', 'marmal-breakdance-plus'); ?></label></td></tr>
                <tr><th scope="row"><label for="marmal-ob-min"><?php esc_html_e('Nepřevádět soubory menší než', 'marmal-breakdance-plus'); ?></label></th><td>
                    <input id="marmal-ob-min" type="number" min="0" name="<?php echo $opt; // phpcs:ignore WordPress.Security.EscapeOutput ?>[min_kb]" value="<?php echo (int) $s['min_kb']; ?>" class="small-text"> kB
                    <p class="description"><?php esc_html_e('0 = převádět vše. Když by WebP vyšel větší než originál (malé PNG s málo barvami), zůstane originál vždy.', 'marmal-breakdance-plus'); ?></p></td></tr>
                <tr><th scope="row"><?php esc_html_e('Hromadný převod – prahy', 'marmal-breakdance-plus'); ?></th><td>
                    <label>PNG <?php esc_html_e('nad', 'marmal-breakdance-plus'); ?> <input type="number" min="0" name="<?php echo $opt; // phpcs:ignore WordPress.Security.EscapeOutput ?>[prah_png]" value="<?php echo (int) $s['prah_png']; ?>" class="small-text"> kB</label>
                    &nbsp; <label>JPG <?php esc_html_e('nad', 'marmal-breakdance-plus'); ?> <input type="number" min="0" name="<?php echo $opt; // phpcs:ignore WordPress.Security.EscapeOutput ?>[prah_jpg]" value="<?php echo (int) $s['prah_jpg']; ?>" class="small-text"> kB</label>
                    <p class="description"><?php esc_html_e('Menší soubory se hromadně nepřevádějí – úspora by byla malá. Logo a ikona webu se nepřevádějí nikdy.', 'marmal-breakdance-plus'); ?></p></td></tr>
                <tr><th scope="row"><?php esc_html_e('Velikost podle místa na stránce', 'marmal-breakdance-plus'); ?></th><td>
                    <label><input type="hidden" name="<?php echo $opt; // phpcs:ignore WordPress.Security.EscapeOutput ?>[sizes_auto]" value="0"><input type="checkbox" name="<?php echo $opt; // phpcs:ignore WordPress.Security.EscapeOutput ?>[sizes_auto]" value="1" <?php checked($s['sizes_auto']); ?>> <?php esc_html_e('Doplňovat sizes="auto" u lazy obrázků', 'marmal-breakdance-plus'); ?></label>
                    <p class="description"><?php esc_html_e('Prohlížeč stáhne velikost obrázku podle skutečné šířky na stránce (karta 230 px → soubor 300 px), ne podle šířky originálu. Týká se jen obrázků s lazy loadingem – logo a úvodní obrázek zůstanou beze změny. Chybějící width a height se doplní z knihovny médií. Po změně vyčisti page cache.', 'marmal-breakdance-plus'); ?></p></td></tr>
            </table>
            <?php submit_button(__('Uložit nastavení', 'marmal-breakdance-plus')); ?>
        </form>

        <!-- ============================================================ 2. Přehled knihovny -->
        <h2><?php esc_html_e('2. Přehled knihovny', 'marmal-breakdance-plus'); ?></h2>
        <p>
            <button type="button" class="button" data-marmal-ob="inventura"><?php esc_html_e('Projít knihovnu', 'marmal-breakdance-plus'); ?></button>
            <?php if (!empty($prehled['cas'])) : ?>
                <span class="description"><?php
                    /* translators: %s: datum a čas */
                    echo esc_html(sprintf(__('Naposledy: %s. Jen čte – nic nemění.', 'marmal-breakdance-plus'), wp_date('j. n. Y H:i', (int) $prehled['cas'])));
                ?></span>
            <?php else : ?>
                <span class="description"><?php esc_html_e('Projde knihovnu médií a najde obrázky, které web skutečně používá (Breakdance, obsah, náhledové obrázky, Rank Math). Jen čte – nic nemění.', 'marmal-breakdance-plus'); ?></span>
            <?php endif; ?>
        </p>

        <?php if (!empty($prehled['cas'])) : ?>
            <div class="marmal-ob__sloupce">
                <table class="widefat striped marmal-ob__formaty">
                    <thead><tr><th><?php esc_html_e('Formát', 'marmal-breakdance-plus'); ?></th><th><?php esc_html_e('Počet', 'marmal-breakdance-plus'); ?></th><th><?php esc_html_e('Objem', 'marmal-breakdance-plus'); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ((array) $prehled['formaty'] as $mime => $f) : ?>
                        <tr><td><?php echo esc_html(strtoupper(str_replace('image/', '', (string) $mime))); ?></td><td><?php echo (int) $f['pocet']; ?></td><td><?php echo esc_html(velikost((int) $f['objem'])); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="marmal-ob__souhrn">
                    <p class="marmal-ob__cislo"><strong><?php echo (int) count((array) $prehled['kandidati']); ?></strong> <?php esc_html_e('obrázků k převodu', 'marmal-breakdance-plus'); ?></p>
                    <p><?php
                        /* translators: 1: objem teď, 2: odhad po převodu */
                        echo esc_html(sprintf(__('%1$s → odhadem %2$s (hlavní soubory)', 'marmal-breakdance-plus'), velikost((int) $prehled['objem']), velikost((int) $prehled['odhad_po'])));
                    ?></p>
                    <p class="description"><?php
                        $pr = (array) $prehled['preskoceno'];
                        /* translators: 1: používaných obrázků, 2: pod prahem, 3: logo/ikona, 4: nepoužívaných */
                        echo esc_html(sprintf(__('Web používá %1$d obrázků. Nepřevádí se: %2$d pod prahem, %3$d logo / ikona webu, %4$d JPG / PNG, které nic nepoužívá.', 'marmal-breakdance-plus'), (int) $prehled['pouzito'], (int) ($pr['prah'] ?? 0), (int) ($pr['logo'] ?? 0), (int) ($pr['nepouzito'] ?? 0)));
                    ?></p>
                </div>
            </div>

            <h3><?php esc_html_e('20 největších používaných obrázků', 'marmal-breakdance-plus'); ?></h3>
            <table class="widefat striped">
                <thead><tr><th></th><th><?php esc_html_e('Soubor', 'marmal-breakdance-plus'); ?></th><th><?php esc_html_e('Velikost', 'marmal-breakdance-plus'); ?></th><th><?php esc_html_e('Kde je', 'marmal-breakdance-plus'); ?></th></tr></thead>
                <tbody>
                <?php foreach ((array) $prehled['nejvetsi'] as $o) :
                    $nahled = wp_get_attachment_image_url((int) $o['id'], 'thumbnail');
                    ?>
                    <tr>
                        <td class="marmal-ob__nahled"><?php if ($nahled) : ?><img src="<?php echo esc_url($nahled); ?>" alt="" loading="lazy" width="48" height="48"><?php endif; ?></td>
                        <td><a href="<?php echo esc_url((string) get_edit_post_link((int) $o['id'], 'raw')); ?>"><?php echo esc_html(wp_basename((string) get_attached_file((int) $o['id']))); ?></a></td>
                        <td><?php echo esc_html(velikost((int) $o['velikost'])); ?></td>
                        <td><?php echo wp_kses_post(implode(', ', array_filter(array_map(__NAMESPACE__ . '\odkaz_na_prispevek', array_map('intval', (array) $o['kde']))))); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- ============================================================ 3. Hromadný převod -->
        <h2><?php esc_html_e('3. Hromadný převod', 'marmal-breakdance-plus'); ?></h2>
        <div class="marmal-ob__prubeh" id="marmal-ob-prubeh" <?php echo rozpracovano() ? '' : 'hidden'; ?>>
            <p class="marmal-ob__faze" id="marmal-ob-faze"></p>
            <div class="marmal-ob__lista"><span id="marmal-ob-lista"></span></div>
            <p id="marmal-ob-cisla"></p>
            <p class="marmal-ob__zprava" id="marmal-ob-zprava" role="status"></p>
            <ul class="marmal-ob__chyby" id="marmal-ob-chyby"></ul>
        </div>
        <p>
            <?php if (rozpracovano()) : ?>
                <button type="button" class="button button-primary" data-marmal-ob="pokracovat"><?php esc_html_e('Pokračovat', 'marmal-breakdance-plus'); ?></button>
                <button type="button" class="button" data-marmal-ob="pauza"><?php esc_html_e('Pozastavit', 'marmal-breakdance-plus'); ?></button>
            <?php else : ?>
                <button type="button" class="button button-primary" data-marmal-ob="start" <?php disabled(!$p['webp']); ?>><?php esc_html_e('Spustit převod', 'marmal-breakdance-plus'); ?></button>
                <span class="description"><?php esc_html_e('Nejdřív znovu projde knihovnu, pak převádí po dávkách (asi 20 s na požadavek). Okno nech otevřené; po zavření jde pokračovat.', 'marmal-breakdance-plus'); ?></span>
            <?php endif; ?>
        </p>

        <?php if ($fronta['faze'] === 'hotovo' && !empty($fronta['kontrola'])) :
            $k = (array) $fronta['kontrola'];
            ?>
            <div class="notice <?php echo ($k['chybi_pocet'] || $k['zbyle_pocet']) ? 'notice-warning' : 'notice-success'; ?> inline">
                <p><strong><?php esc_html_e('Kontrola posledního převodu:', 'marmal-breakdance-plus'); ?></strong>
                <?php
                /* translators: 1: převedeno, 2: objem před, 3: objem po, 4: stránek */
                echo esc_html(sprintf(__('obrázky: %1$d, %2$s → %3$s, přepsané stránky a šablony: %4$d.', 'marmal-breakdance-plus'), $fronta['hotove'], $fronta['pred_text'], $fronta['po_text'], count((array) (fronta()['zmeny'] ?? array()))));
                ?></p>
                <?php if ($k['chybi_pocet']) : ?>
                    <p><?php
                        /* translators: %d: počet */
                        echo esc_html(sprintf(__('Chybí nebo jsou prázdné nové soubory: %d.', 'marmal-breakdance-plus'), (int) $k['chybi_pocet']));
                    ?> <code><?php echo esc_html(implode(', ', array_slice((array) $k['chybi'], 0, 10))); ?></code></p>
                <?php endif; ?>
                <?php if ($k['zbyle_pocet']) : ?>
                    <p><?php esc_html_e('Na těchto stránkách zůstaly odkazy na původní soubory (fungují dál, soubory zůstávají):', 'marmal-breakdance-plus'); ?></p>
                    <ul>
                    <?php foreach (array_slice((array) $k['zbyle'], 0, 10, true) as $rel => $kde) : ?>
                        <li><code><?php echo esc_html((string) $rel); ?></code> – <?php echo wp_kses_post(implode(', ', array_filter(array_map(__NAMESPACE__ . '\odkaz_na_prispevek', array_map('intval', (array) $kde))))); ?></li>
                    <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p><?php esc_html_e('Na stránkách nezůstal žádný odkaz na převedené JPG / PNG.', 'marmal-breakdance-plus'); ?></p>
                <?php endif; ?>
                <?php if (!empty($fronta['css_rucne'])) : ?>
                    <p><strong><?php esc_html_e('CSS Breakdance se nepodařilo regenerovat automaticky – udělej to ručně: Breakdance → Settings → Tools → Regenerate Cache.', 'marmal-breakdance-plus'); ?></strong></p>
                <?php endif; ?>
                <p class="description"><?php esc_html_e('Pro jistotu projdi pár stránek v anonymním okně a vyčisti cache hostingu / CDN, pokud nějakou máš.', 'marmal-breakdance-plus'); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($davky) : ?>
            <h3><?php esc_html_e('Dávky', 'marmal-breakdance-plus'); ?></h3>
            <table class="widefat striped">
                <thead><tr>
                    <th><?php esc_html_e('Datum', 'marmal-breakdance-plus'); ?></th>
                    <th><?php esc_html_e('Obrázků', 'marmal-breakdance-plus'); ?></th>
                    <th><?php esc_html_e('Objem', 'marmal-breakdance-plus'); ?></th>
                    <th><?php esc_html_e('Stav', 'marmal-breakdance-plus'); ?></th>
                    <th><?php esc_html_e('Akce', 'marmal-breakdance-plus'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($davky as $did => $d) :
                    $prevedeno = 0;
                    foreach ((array) $d['prilohy'] as $aid) {
                        if (($z['prilohy'][$aid]['stav'] ?? '') === 'prevedeno' && ($z['prilohy'][$aid]['davka'] ?? '') === $did) {
                            $prevedeno++;
                        }
                    }
                    $uklid_od = (int) $d['datum'] + UKLID_DNI * DAY_IN_SECONDS;
                    $stavy = array(
                        'bezi'    => __('rozpracováno', 'marmal-breakdance-plus'),
                        'hotovo'  => __('hotovo', 'marmal-breakdance-plus'),
                        'vraceno' => __('vráceno', 'marmal-breakdance-plus'),
                    );
                    ?>
                    <tr>
                        <td><?php echo esc_html(wp_date('j. n. Y H:i', (int) $d['datum'])); ?></td>
                        <td><?php echo (int) $prevedeno; ?> / <?php echo (int) count((array) $d['prilohy']); ?></td>
                        <td><?php echo isset($d['pred']) ? esc_html(velikost((int) $d['pred']) . ' → ' . velikost((int) $d['po'])) : '–'; ?></td>
                        <td><?php echo esc_html($stavy[$d['stav']] ?? (string) $d['stav']); ?>
                            <?php if (!empty($d['uklizeno'])) : ?><br><small><?php esc_html_e('staré soubory smazány', 'marmal-breakdance-plus'); ?></small><?php endif; ?>
                            <?php if (empty($d['zaloha'])) : ?><br><small><?php esc_html_e('bez zálohy', 'marmal-breakdance-plus'); ?></small><?php endif; ?>
                        </td>
                        <td class="marmal-ob__akce">
                            <?php if (!rozpracovano() && $did === $posledni && $prevedeno > 0) : ?>
                                <button type="button" class="button" data-marmal-ob="vratit" data-davka="<?php echo esc_attr((string) $did); ?>"><?php esc_html_e('Vrátit převod', 'marmal-breakdance-plus'); ?></button>
                            <?php endif; ?>
                            <?php if (($d['stav'] ?? '') === 'hotovo' && empty($d['uklizeno']) && $prevedeno > 0) : ?>
                                <?php if (lze_uklidit($d)) : ?>
                                    <button type="button" class="button" data-marmal-ob="uklid" data-davka="<?php echo esc_attr((string) $did); ?>"><?php esc_html_e('Smazat staré soubory', 'marmal-breakdance-plus'); ?></button>
                                <?php else : ?>
                                    <button type="button" class="button" disabled title="<?php echo esc_attr(sprintf(/* translators: %s: datum */ __('Půjde od %s', 'marmal-breakdance-plus'), wp_date('j. n. Y', $uklid_od))); ?>"><?php esc_html_e('Smazat staré soubory', 'marmal-breakdance-plus'); ?></button>
                                    <small><?php echo esc_html(sprintf(/* translators: %s: datum */ __('od %s', 'marmal-breakdance-plus'), wp_date('j. n. Y', $uklid_od))); ?></small>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($d['zaloha']) && ($d['stav'] ?? '') !== 'bezi') : ?>
                                <button type="button" class="button-link marmal-ob__smazat" data-marmal-ob="zaloha" data-davka="<?php echo esc_attr((string) $did); ?>"><?php esc_html_e('Smazat zálohu', 'marmal-breakdance-plus'); ?></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($prevedeno > 0) : ?>
                    <tr class="marmal-ob__detail"><td colspan="5">
                        <details>
                            <summary><?php esc_html_e('Převedené obrázky (vrátit jednotlivě)', 'marmal-breakdance-plus'); ?></summary>
                            <table class="widefat">
                                <?php foreach ((array) $d['prilohy'] as $aid) :
                                    $pz = $z['prilohy'][$aid] ?? null;
                                    if (!$pz || ($pz['stav'] ?? '') !== 'prevedeno' || ($pz['davka'] ?? '') !== $did) {
                                        continue;
                                    }
                                    ?>
                                    <tr>
                                        <td><a href="<?php echo esc_url((string) get_edit_post_link((int) $aid, 'raw')); ?>"><?php echo esc_html(wp_basename((string) $pz['soubor'])); ?></a> → <?php echo esc_html(wp_basename((string) $pz['novy'])); ?></td>
                                        <td><?php echo esc_html(velikost((int) $pz['velikost_pred']) . ' → ' . velikost((int) $pz['velikost_po'])); ?></td>
                                        <td><?php if (!rozpracovano()) : ?><button type="button" class="button-link" data-marmal-ob="vratit" data-davka="<?php echo esc_attr((string) $did); ?>" data-priloha="<?php echo (int) $aid; ?>"><?php esc_html_e('Vrátit', 'marmal-breakdance-plus'); ?></button><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </details>
                    </td></tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="description"><?php esc_html_e('Staré soubory zůstávají na svém místě, aby fungovaly odkazy z revizí a z míst, která modul nezná. „Smazat staré soubory“ jde nejdřív 14 dní po převodu, záloha se maže zvlášť. Dávky se vracejí od nejnovější.', 'marmal-breakdance-plus'); ?></p>
        <?php endif; ?>

        <!-- ============================================================ 4. Obrázky na pozadí -->
        <?php render_pozadi(); ?>

        <?php $log = array_slice(log_zaznamy(), 0, 20); ?>
        <?php if ($log) : ?>
            <h3><?php esc_html_e('Log', 'marmal-breakdance-plus'); ?></h3>
            <table class="widefat striped marmal-ob__log">
                <tbody>
                <?php foreach ($log as $l) : ?>
                    <tr><td><?php echo esc_html(wp_date('j. n. H:i', (int) $l['cas'])); ?></td><td><?php echo esc_html((string) $l['zprava']); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
