<?php
/**
 * Modul Fonty – stránka MarMal → Fonty.
 * Nastavení přes options.php (register_setting), akce přes admin-post s nonce a manage_options.
 */

namespace MarmalElements\Fonty;

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', function () {
    register_setting('marmal_fonty', OPTION, array(
        'type'              => 'array',
        'sanitize_callback' => __NAMESPACE__ . '\sanitizovat',
        'show_in_rest'      => false,
    ));
});

add_action('admin_menu', function () {
    add_submenu_page('marmal', __('Fonty', 'marmal-breakdance-plus'), __('Fonty', 'marmal-breakdance-plus'), 'manage_options', 'marmal-fonty', __NAMESPACE__ . '\stranka');
}, 20);

/** Projde úvodní stránku (požadavek webu sám na sebe) – fonty se při něm stáhnou. */
function projit_web(): array
{
    // Záznam modulu Cookies „web načítá Google Fonts“ – průchod ho zapíše znovu, jen když fonty z Googlu zůstanou.
    delete_option('marmal_bdp_cookies_gfonty');
    // Parametr obejde page cache, jinak by se vrátila stará stránka a PHP by neběželo.
    $adresa = add_query_arg('marmal_fonty', (string) time(), home_url('/'));
    $r      = wp_remote_get($adresa, array(
        'timeout'   => 45,
        'sslverify' => apply_filters('https_local_ssl_verify', false), // požadavek na vlastní web (stejně jako Site Health)
    ));
    if (is_wp_error($r)) {
        return array(false, $r->get_error_message());
    }
    $kod = (int) wp_remote_retrieve_response_code($r);
    return array($kod === 200, (string) $kod);
}

add_action('admin_post_marmal_fonty_projit', function () {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Nemáte oprávnění.', 'marmal-breakdance-plus'), 403);
    }
    check_admin_referer('marmal_fonty_projit');
    list($ok, $info) = projit_web();
    if ($ok) {
        vycistit_cache();
    }
    wp_safe_redirect(add_query_arg(array('page' => 'marmal-fonty', 'zprava' => $ok ? 'projito' : 'chyba-pruchodu', 'info' => rawurlencode(substr($info, 0, 120))), admin_url('admin.php')));
    exit;
});

add_action('admin_post_marmal_fonty_smazat', function () {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Nemáte oprávnění.', 'marmal-breakdance-plus'), 403);
    }
    check_admin_referer('marmal_fonty_smazat');
    smazat_vse();
    list($ok) = projit_web();
    vycistit_cache();
    wp_safe_redirect(add_query_arg(array('page' => 'marmal-fonty', 'zprava' => $ok ? 'znovu' : 'smazano'), admin_url('admin.php')));
    exit;
});

function velikost(int $b): string
{
    return $b >= 1048576 ? number_format_i18n($b / 1048576, 1) . ' MB' : number_format_i18n(max(1, $b / 1024)) . ' kB';
}

function stranka(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $s      = nastaveni();
    $sady   = sady();
    $zprava = isset($_GET['zprava']) ? sanitize_key(wp_unslash($_GET['zprava'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification
    $info   = isset($_GET['info']) ? sanitize_text_field(rawurldecode(wp_unslash($_GET['info']))) : ''; // phpcs:ignore WordPress.Security.NonceVerification
    uasort($sady, function ($a, $b) {
        return ($b['pouzito'] ?? 0) <=> ($a['pouzito'] ?? 0);
    });
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('MarMal – Fonty', 'marmal-breakdance-plus'); ?></h1>
        <p><?php esc_html_e('Google Fonts se na web načítají z vlastní domény: Google nedostane IP adresy návštěvníků (GDPR) a stránka se načte rychleji. Fonty dál vybíráš v Breakdance jako dosud – modul je při první návštěvě stránky stáhne sám. V builderu se fonty načítají z Googlu dál (vidíš to jen ty).', 'marmal-breakdance-plus'); ?></p>

        <?php settings_errors(); ?>
        <?php if ($zprava === 'projito') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Úvodní stránka prošla, nalezené fonty jsou stažené a cache je vyčištěná.', 'marmal-breakdance-plus'); ?></p></div>
        <?php elseif ($zprava === 'znovu') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Fonty smazány a znovu stažené z úvodní stránky. Fonty z ostatních stránek se stáhnou při jejich první návštěvě.', 'marmal-breakdance-plus'); ?></p></div>
        <?php elseif ($zprava === 'smazano') : ?>
            <div class="notice notice-warning is-dismissible"><p><?php esc_html_e('Fonty smazány. Stáhnou se znovu při první návštěvě webu.', 'marmal-breakdance-plus'); ?></p></div>
        <?php elseif ($zprava === 'chyba-pruchodu') : ?>
            <div class="notice notice-warning is-dismissible"><p><?php
                /* translators: %s: chyba */
                echo esc_html(sprintf(__('Web se nepodařilo projít z administrace (%s). Nevadí – otevři web v anonymním okně, fonty se stáhnou při první návštěvě.', 'marmal-breakdance-plus'), $info));
            ?></p></div>
        <?php endif; ?>

        <h2><?php esc_html_e('Fonty na webu', 'marmal-breakdance-plus'); ?></h2>
        <table class="widefat striped" style="max-width:1000px">
            <thead><tr>
                <th><?php esc_html_e('Fonty', 'marmal-breakdance-plus'); ?></th>
                <th><?php esc_html_e('Stav', 'marmal-breakdance-plus'); ?></th>
                <th><?php esc_html_e('Soubory', 'marmal-breakdance-plus'); ?></th>
                <th><?php esc_html_e('Staženo', 'marmal-breakdance-plus'); ?></th>
                <th><?php esc_html_e('Naposledy na webu', 'marmal-breakdance-plus'); ?></th>
            </tr></thead>
            <tbody>
            <?php if (!$sady) : ?>
                <tr><td colspan="5"><?php esc_html_e('Zatím nic. Klikni na „Projít web a stáhnout“, nebo otevři web – fonty se stáhnou při první návštěvě. Když se nic neobjeví, web Google Fonts nepoužívá.', 'marmal-breakdance-plus'); ?></td></tr>
            <?php endif; ?>
            <?php foreach ($sady as $sada) : ?>
                <tr>
                    <td><strong><?php echo esc_html(implode(', ', (array) $sada['rodiny'])); ?></strong><br><code style="font-size:11px;word-break:break-all"><?php echo esc_html((string) $sada['url']); ?></code></td>
                    <td><?php if (($sada['stav'] ?? '') === 'ok') : ?>
                            <span style="color:#008a20">✓ <?php esc_html_e('lokálně', 'marmal-breakdance-plus'); ?></span>
                        <?php else : ?>
                            <span style="color:#b32d2e">✗ <?php esc_html_e('z Googlu', 'marmal-breakdance-plus'); ?></span><br><small><?php echo esc_html((string) $sada['chyba']); ?></small>
                        <?php endif; ?></td>
                    <td><?php echo (int) $sada['soubory']; ?> × woff2, <?php echo esc_html(velikost((int) $sada['velikost'])); ?></td>
                    <td><?php echo esc_html(wp_date('j. n. Y H:i', (int) $sada['cas'])); ?></td>
                    <td><?php echo esc_html(wp_date('j. n. Y', (int) $sada['pouzito'])); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p>
            <a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=marmal_fonty_projit'), 'marmal_fonty_projit')); ?>"><?php esc_html_e('Projít web a stáhnout', 'marmal-breakdance-plus'); ?></a>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=marmal_fonty_smazat'), 'marmal_fonty_smazat')); ?>"><?php esc_html_e('Smazat a stáhnout znovu', 'marmal-breakdance-plus'); ?></a>
        </p>
        <p class="description"><?php esc_html_e('„Projít web“ načte úvodní stránku a vyčistí page cache (WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache). U jiné cache nebo cache hostingu ji vyčisti ručně, aby se na web dostaly nové odkazy. „Smazat a stáhnout znovu“ použij po aktualizaci fontu u Googlu nebo po změně znakových sad.', 'marmal-breakdance-plus'); ?></p>

        <h2><?php esc_html_e('Nastavení', 'marmal-breakdance-plus'); ?></h2>
        <form method="post" action="options.php">
            <?php settings_fields('marmal_fonty'); ?>
            <table class="form-table" role="presentation">
                <tr><th scope="row"><?php esc_html_e('Znakové sady', 'marmal-breakdance-plus'); ?></th><td>
                    <p><label><input type="checkbox" checked disabled> <?php esc_html_e('Latinka včetně češtiny (vždy)', 'marmal-breakdance-plus'); ?></label></p>
                    <?php foreach (znakove_sady() as $k => $n) : ?>
                        <p><label><input type="checkbox" name="<?php echo esc_attr(OPTION); ?>[sady][]" value="<?php echo esc_attr($k); ?>" <?php checked(in_array($k, (array) $s['sady'], true)); ?>> <?php echo esc_html($n); ?></label></p>
                    <?php endforeach; ?>
                    <p class="description"><?php esc_html_e('Po změně klikni na „Smazat a stáhnout znovu“.', 'marmal-breakdance-plus'); ?></p></td></tr>
                <tr><th scope="row"><?php esc_html_e('Zobrazení textu', 'marmal-breakdance-plus'); ?></th><td>
                    <label><input type="hidden" name="<?php echo esc_attr(OPTION); ?>[swap]" value="0"><input type="checkbox" name="<?php echo esc_attr(OPTION); ?>[swap]" value="1" <?php checked($s['swap']); ?>> <?php esc_html_e('font-display: swap – text je vidět hned systémovým písmem, font se doplní (doporučeno, pomáhá Core Web Vitals)', 'marmal-breakdance-plus'); ?></label></td></tr>
                <tr><th scope="row"><?php esc_html_e('Přednačtení', 'marmal-breakdance-plus'); ?></th><td>
                    <label><input type="hidden" name="<?php echo esc_attr(OPTION); ?>[preload]" value="0"><input type="checkbox" name="<?php echo esc_attr(OPTION); ?>[preload]" value="1" <?php checked($s['preload']); ?>> <?php esc_html_e('Přednačíst běžný řez (400) hlavních fontů – nejvýš 2 soubory na stránku', 'marmal-breakdance-plus'); ?></label></td></tr>
            </table>
            <?php submit_button(__('Uložit nastavení', 'marmal-breakdance-plus')); ?>
        </form>

        <h2><?php esc_html_e('Kontrola', 'marmal-breakdance-plus'); ?></h2>
        <ol>
            <li><?php esc_html_e('Otevři web v anonymním okně → F12 → Network → do filtru napiš „fonts.g“.', 'marmal-breakdance-plus'); ?></li>
            <li><?php esc_html_e('Nesmí se objevit nic z fonts.googleapis.com ani fonts.gstatic.com. Fonty se načítají z /wp-content/uploads/marmal-fonty/.', 'marmal-breakdance-plus'); ?></li>
            <li><?php esc_html_e('Když se Google ještě objeví: vyčisti cache webu a hostingu. Font vložený přímo v CSS (@font-face s adresou fonts.gstatic.com) modul nepřepisuje – takový font nahraj v Breakdance → Preferences → Custom Fonts.', 'marmal-breakdance-plus'); ?></li>
        </ol>
    </div>
    <?php
}
