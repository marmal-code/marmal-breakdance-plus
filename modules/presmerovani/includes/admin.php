<?php
/**
 * Modul Přesměrování – stránka MarMal → Přesměrování.
 *
 * Záložky: Přesměrování (seznam + formulář) · Chyby 404 · Převod ze starého webu · Import / export · Nastavení.
 * Všechny změny jdou přes admin-post.php?action=marmal_presmerovani s „ukon“ z pevného seznamu,
 * nonce pro každý úkon zvlášť a oprávněním manage_options. Nastavení přes options.php (register_setting).
 */

namespace MarmalElements\Presmerovani;

if (!defined('ABSPATH')) {
    exit;
}

const STRANKA = 'marmal-presmerovani';
const UKONY   = array('ulozit', 'smazat', 'prepnout', 'hromadne', 'smazat404', 'vymazat404', 'stary_nacist', 'stary_vytvorit', 'stary_zahodit', 'export', 'import');

add_action('admin_init', function () {
    register_setting('marmal_presmerovani', OPTION, array(
        'type'              => 'array',
        'sanitize_callback' => __NAMESPACE__ . '\sanitizovat',
        'show_in_rest'      => false,
    ));
});

add_action('admin_menu', function () {
    $hook = add_submenu_page('marmal', __('Přesměrování', 'marmal-breakdance-plus'), __('Přesměrování', 'marmal-breakdance-plus'), 'manage_options', STRANKA, __NAMESPACE__ . '\stranka');
    add_action('admin_print_scripts-' . $hook, function () {
        wp_enqueue_style('marmal-presmerovani', MARMAL_BDP_URL . 'modules/presmerovani/assets/presmerovani-admin.css', array(), MARMAL_BDP_VERSION);
        wp_enqueue_script('marmal-presmerovani', MARMAL_BDP_URL . 'modules/presmerovani/assets/presmerovani-admin.js', array(), MARMAL_BDP_VERSION, true);
        wp_add_inline_script('marmal-presmerovani', 'window.marmalPresm = ' . wp_json_encode(array(
            'smazat'  => __('Opravdu smazat?', 'marmal-breakdance-plus'),
            'vybrano' => __('Vybráno: %d', 'marmal-breakdance-plus'),
            'nic'     => __('Nic není vybráno.', 'marmal-breakdance-plus'),
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ';', 'before');
    });
}, 21);

// ================================================================= zprávy

function zprava(string $typ, string $text, array $chyby = array()): void
{
    set_transient('marmal_presm_zprava_' . get_current_user_id(), array('typ' => $typ, 'text' => $text, 'chyby' => $chyby), 120);
}

function vypsat_zpravu(): void
{
    $k = 'marmal_presm_zprava_' . get_current_user_id();
    $z = get_transient($k);
    if (!is_array($z)) {
        return;
    }
    delete_transient($k);
    $typ = in_array($z['typ'] ?? '', array('success', 'error', 'warning', 'info'), true) ? $z['typ'] : 'info';
    echo '<div class="notice notice-' . esc_attr($typ) . ' is-dismissible"><p>' . esc_html((string) ($z['text'] ?? '')) . '</p>';
    if (!empty($z['chyby'])) {
        echo '<ul class="marmal-presm__chyby">';
        foreach ((array) $z['chyby'] as $ch) {
            echo '<li>' . esc_html((string) $ch) . '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
}

function url(array $args = array()): string
{
    return add_query_arg(array_merge(array('page' => STRANKA), $args), admin_url('admin.php'));
}

function akce_url(string $ukon, array $args = array()): string
{
    return wp_nonce_url(add_query_arg(array_merge(array('action' => 'marmal_presmerovani', 'ukon' => $ukon), $args), admin_url('admin-post.php')), 'marmal_presmerovani_' . $ukon);
}

function nonce_pole(string $ukon): void
{
    echo '<input type="hidden" name="action" value="marmal_presmerovani"><input type="hidden" name="ukon" value="' . esc_attr($ukon) . '">';
    wp_nonce_field('marmal_presmerovani_' . $ukon);
}

// ================================================================= úkony

add_action('admin_post_marmal_presmerovani', function () {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Nemáš oprávnění.', 'marmal-breakdance-plus'), '', array('response' => 403));
    }
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- nonce se ověří hned pod tím podle úkonu
    $ukon = isset($_REQUEST['ukon']) ? sanitize_key(wp_unslash($_REQUEST['ukon'])) : '';
    // phpcs:enable
    if (!in_array($ukon, UKONY, true)) {
        wp_die(esc_html__('Neznámý úkon.', 'marmal-breakdance-plus'), '', array('response' => 400));
    }
    check_admin_referer('marmal_presmerovani_' . $ukon);
    db_instalace();
    $zpet = url();

    switch ($ukon) {
        case 'ulozit':
            $id      = isset($_POST['id']) ? absint($_POST['id']) : 0;
            $cilPost = isset($_POST['cil_post']) ? absint($_POST['cil_post']) : 0;
            $res = ulozit(array(
                'zdroj'      => isset($_POST['zdroj']) ? sanitize_text_field(wp_unslash($_POST['zdroj'])) : '',
                'cil'        => $cilPost ? 'post:' . $cilPost : (isset($_POST['cil']) ? sanitize_text_field(wp_unslash($_POST['cil'])) : ''),
                'kod'        => isset($_POST['kod']) ? absint($_POST['kod']) : 301,
                'poznamka'   => isset($_POST['poznamka']) ? sanitize_text_field(wp_unslash($_POST['poznamka'])) : '',
                'aktivni'    => 1,
                'puvod'      => isset($_POST['z404']) ? '404' : 'rucne',
                'na_stranku' => true,
            ), $id);
            if (is_wp_error($res)) {
                zprava('error', $res->get_error_message());
                $zpet = url(array('upravit' => $id ?: null));
            } else {
                zprava('success', $id ? __('Přesměrování upraveno.', 'marmal-breakdance-plus') : __('Přesměrování přidáno.', 'marmal-breakdance-plus'));
                $zpet = url(array('tab' => isset($_POST['z404']) ? '404' : null));
            }
            break;

        case 'smazat':
            smazat(isset($_GET['id']) ? absint($_GET['id']) : 0);
            zprava('success', __('Přesměrování smazáno.', 'marmal-breakdance-plus'));
            break;

        case 'prepnout':
            prepnout(isset($_GET['id']) ? absint($_GET['id']) : 0);
            break;

        case 'hromadne':
            $ids = isset($_POST['ids']) ? array_map('absint', (array) wp_unslash($_POST['ids'])) : array();
            $co  = isset($_POST['co']) ? sanitize_key(wp_unslash($_POST['co'])) : '';
            hromadne(array_filter($ids), $co);
            zprava('success', sprintf(/* translators: %d: počet */ __('Hotovo (%d).', 'marmal-breakdance-plus'), count(array_filter($ids))));
            break;

        case 'smazat404':
            smazat_404(isset($_GET['id']) ? absint($_GET['id']) : 0);
            $zpet = url(array('tab' => '404'));
            break;

        case 'vymazat404':
            vymazat_404();
            zprava('success', __('Seznam chyb 404 vymazán.', 'marmal-breakdance-plus'));
            $zpet = url(array('tab' => '404'));
            break;

        case 'stary_nacist':
            $res = pripravit_stary_web(
                isset($_POST['sitemap']) ? esc_url_raw(wp_unslash($_POST['sitemap'])) : '',
                isset($_POST['seznam']) ? sanitize_textarea_field(wp_unslash($_POST['seznam'])) : '',
                nahrane_soubory(isset($_FILES['soubory']) && is_array($_FILES['soubory']) ? $_FILES['soubory'] : array()) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- kontroluje nahrane_soubory() a adresy_ze_souboru()
            );
            if (is_wp_error($res)) {
                zprava('error', $res->get_error_message());
            } else {
                zprava($res['chyby'] ? 'warning' : 'success', sprintf(/* translators: %d: počet */ __('Načteno %d adres starého webu. Zkontroluj návrhy a vytvoř přesměrování.', 'marmal-breakdance-plus'), $res['pocet']), $res['chyby']);
            }
            $zpet = url(array('tab' => 'stary'));
            break;

        case 'stary_vytvorit':
            $json  = isset($_POST['radky_json']) ? (string) wp_unslash($_POST['radky_json']) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- JSON, každá hodnota projde cesta() / ulozit()
            $volby = json_decode($json, true);
            $res   = vytvorit_ze_stareho(is_array($volby) ? $volby : array());
            zprava($res['chyby'] ? 'warning' : 'success', sprintf(/* translators: %d: počet */ __('Vytvořeno přesměrování: %d.', 'marmal-breakdance-plus'), $res['ok']), $res['chyby']);
            $zpet = url(array('tab' => 'stary'));
            break;

        case 'stary_zahodit':
            delete_option(OPT_STARY);
            $zpet = url(array('tab' => 'stary'));
            break;

        case 'export':
            export_csv(); // končí exit
            break;

        case 'import':
            $res = import_csv(isset($_FILES['csv']) && is_array($_FILES['csv']) ? $_FILES['csv'] : array()); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- kontroluje import_csv()
            if (is_wp_error($res)) {
                zprava('error', $res->get_error_message());
            } else {
                zprava($res['chyby'] ? 'warning' : 'success', sprintf(/* translators: %d: počet */ __('Importováno přesměrování: %d.', 'marmal-breakdance-plus'), $res['ok']), $res['chyby']);
            }
            $zpet = url(array('tab' => 'csv'));
            break;
    }
    wp_safe_redirect($zpet);
    exit;
});

function hromadne(array $ids, string $co): void
{
    global $wpdb;
    $ids = array_slice(array_values(array_unique(array_map('intval', $ids))), 0, 1000);
    if (!$ids || !in_array($co, array('smazat', 'zapnout', 'vypnout'), true)) {
        return;
    }
    $mista = implode(',', array_fill(0, count($ids), '%d'));
    if ($co === 'smazat') {
        $wpdb->query($wpdb->prepare('DELETE FROM ' . t_pravidla() . " WHERE id IN ($mista)", $ids)); // phpcs:ignore WordPress.DB.PreparedSQL
    } else {
        $wpdb->query($wpdb->prepare('UPDATE ' . t_pravidla() . ' SET aktivni = ' . ($co === 'zapnout' ? 1 : 0) . " WHERE id IN ($mista)", $ids)); // phpcs:ignore WordPress.DB.PreparedSQL
    }
    obnovit_sekce();
}

// ================================================================= stránka

function stranka(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    db_instalace();
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- jen zobrazení (záložka, hledání, stránkování)
    $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'pravidla';
    // phpcs:enable
    $taby = array(
        'pravidla'  => __('Přesměrování', 'marmal-breakdance-plus'),
        '404'       => __('Chyby 404', 'marmal-breakdance-plus'),
        'stary'     => __('Převod ze starého webu', 'marmal-breakdance-plus'),
        'csv'       => __('Import / export', 'marmal-breakdance-plus'),
        'nastaveni' => __('Nastavení', 'marmal-breakdance-plus'),
    );
    if (!isset($taby[$tab])) {
        $tab = 'pravidla';
    }
    echo '<div class="wrap marmal-presm"><h1>' . esc_html__('Přesměrování', 'marmal-breakdance-plus') . '</h1>';
    if ($k = konkurence()) {
        echo '<div class="notice notice-warning"><p>' . esc_html(sprintf(
            /* translators: %s: názvy pluginů */
            __('Na webu běží i %s. Přesměrování nastavuj jen na jednom místě – jinak se mohou přebíjet.', 'marmal-breakdance-plus'),
            implode(', ', $k)
        )) . '</p></div>';
    }
    vypsat_zpravu();
    echo '<nav class="nav-tab-wrapper">';
    foreach ($taby as $id => $nazev) {
        printf('<a href="%s" class="nav-tab%s">%s</a>', esc_url(url(array('tab' => $id === 'pravidla' ? null : $id))), $id === $tab ? ' nav-tab-active' : '', esc_html($nazev));
    }
    echo '</nav>';
    call_user_func(__NAMESPACE__ . '\tab_' . ($tab === '404' ? 'chyby' : $tab));
    echo '</div>';
}

function vyber_stranky(string $vybrano = ''): void
{
    $id = preg_match('/^post:(\d+)$/', $vybrano, $m) ? (int) $m[1] : 0;
    echo '<select name="cil_post" id="mp-cil-post" class="marmal-presm__stranky"><option value="0">' . esc_html__('— nebo vyber stránku —', 'marmal-breakdance-plus') . '</option>';
    foreach (hlidane_typy() as $typ) {
        $o = get_post_type_object($typ);
        $ids = get_posts(array('post_type' => $typ, 'post_status' => 'publish', 'posts_per_page' => 500, 'orderby' => 'title', 'order' => 'ASC', 'fields' => 'ids', 'no_found_rows' => true));
        if (!$ids) {
            continue;
        }
        echo '<optgroup label="' . esc_attr($o ? $o->labels->name : $typ) . '">';
        foreach ($ids as $pid) {
            printf('<option value="%d"%s>%s</option>', (int) $pid, selected($id, (int) $pid, false), esc_html(wp_strip_all_tags(get_the_title($pid)) ?: '#' . $pid));
        }
        echo '</optgroup>';
    }
    echo '</select>';
}

function tab_pravidla(): void
{
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- jen zobrazení a předvyplnění
    $upravit = isset($_GET['upravit']) ? absint($_GET['upravit']) : 0;
    $z404    = isset($_GET['zdroj']) ? cesta(sanitize_text_field(wp_unslash($_GET['zdroj']))) : '';
    $hledat  = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $strana  = isset($_GET['strana']) ? max(1, absint($_GET['strana'])) : 1;
    $razeni  = isset($_GET['razeni']) ? sanitize_key(wp_unslash($_GET['razeni'])) : 'vytvoreno';
    // phpcs:enable
    $p = $upravit ? pravidlo($upravit) : null;
    if (!$p) {
        $upravit = 0;
        $p       = array('zdroj' => $z404, 'cil' => '', 'kod' => 301, 'poznamka' => '');
        if ($z404 !== '' && function_exists(__NAMESPACE__ . '\navrh')) {
            $n = navrh($z404);
            if ($n['stav'] === 'navrh') {
                $p['cil'] = $n['cil'];
            }
        }
    }
    $cilText = preg_match('/^post:/', (string) $p['cil']) ? '' : (string) $p['cil'];
    ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="marmal-presm__box marmal-presm__form" id="mp-formular">
        <?php nonce_pole('ulozit'); ?>
        <input type="hidden" name="id" value="<?php echo (int) $upravit; ?>">
        <?php if ($z404 !== '') : ?><input type="hidden" name="z404" value="1"><?php endif; ?>
        <h2><?php echo $upravit ? esc_html__('Upravit přesměrování', 'marmal-breakdance-plus') : esc_html__('Nové přesměrování', 'marmal-breakdance-plus'); ?></h2>
        <div class="marmal-presm__radek">
            <label><span><?php esc_html_e('Stará adresa', 'marmal-breakdance-plus'); ?></span>
                <input type="text" name="zdroj" value="<?php echo esc_attr((string) $p['zdroj']); ?>" placeholder="/stara-stranka" required class="regular-text" maxlength="500"></label>
            <span class="marmal-presm__sipka" aria-hidden="true">→</span>
            <label><span><?php esc_html_e('Nová adresa', 'marmal-breakdance-plus'); ?></span>
                <input type="text" name="cil" id="mp-cil" value="<?php echo esc_attr($cilText); ?>" placeholder="/nova-stranka/ <?php esc_attr_e('nebo', 'marmal-breakdance-plus'); ?> https://…" class="regular-text" maxlength="1000"></label>
            <label><span><?php esc_html_e('Typ', 'marmal-breakdance-plus'); ?></span>
                <select name="kod" id="mp-kod">
                    <?php
                    $kody = array(
                        301 => __('301 – trvale (doporučeno)', 'marmal-breakdance-plus'),
                        302 => __('302 – dočasně', 'marmal-breakdance-plus'),
                        307 => __('307 – dočasně (zachová formulář)', 'marmal-breakdance-plus'),
                        410 => __('410 – stránka zrušena', 'marmal-breakdance-plus'),
                    );
                    foreach ($kody as $k => $t) {
                        printf('<option value="%d"%s>%s</option>', (int) $k, selected((int) $p['kod'], $k, false), esc_html($t));
                    }
                    ?>
                </select></label>
        </div>
        <div class="marmal-presm__radek">
            <label class="marmal-presm__siroky"><span><?php esc_html_e('Stránka na tomto webu', 'marmal-breakdance-plus'); ?></span><?php vyber_stranky((string) $p['cil']); ?></label>
            <label class="marmal-presm__siroky"><span><?php esc_html_e('Poznámka', 'marmal-breakdance-plus'); ?></span>
                <input type="text" name="poznamka" value="<?php echo esc_attr((string) $p['poznamka']); ?>" class="regular-text" maxlength="200"></label>
        </div>
        <p class="description"><?php esc_html_e('Stará adresa končící hvězdičkou (/blog/*) přesměruje celou sekci; zbytek adresy se doplní za novou adresu, nebo místo * v nové adrese (/clanky/*). Vybraná stránka vede vždy na její aktuální adresu, i když ji později přejmenuješ.', 'marmal-breakdance-plus'); ?></p>
        <p>
            <?php submit_button($upravit ? __('Uložit změny', 'marmal-breakdance-plus') : __('Přidat přesměrování', 'marmal-breakdance-plus'), 'primary', 'submit', false); ?>
            <?php if ($upravit || $z404 !== '') : ?> <a href="<?php echo esc_url(url()); ?>" class="button"><?php esc_html_e('Zrušit', 'marmal-breakdance-plus'); ?></a><?php endif; ?>
        </p>
    </form>
    <?php
    list($rows, $celkem) = seznam($hledat, $strana, 50, $razeni);
    ?>
    <form method="get" class="marmal-presm__hledat">
        <input type="hidden" name="page" value="<?php echo esc_attr(STRANKA); ?>">
        <input type="search" name="s" value="<?php echo esc_attr($hledat); ?>" placeholder="<?php esc_attr_e('Hledat adresu nebo poznámku', 'marmal-breakdance-plus'); ?>">
        <select name="razeni">
            <option value="vytvoreno"<?php selected($razeni, 'vytvoreno'); ?>><?php esc_html_e('Nejnovější', 'marmal-breakdance-plus'); ?></option>
            <option value="zasahy"<?php selected($razeni, 'zasahy'); ?>><?php esc_html_e('Nejpoužívanější', 'marmal-breakdance-plus'); ?></option>
            <option value="zdroj"<?php selected($razeni, 'zdroj'); ?>><?php esc_html_e('Podle adresy', 'marmal-breakdance-plus'); ?></option>
        </select>
        <button class="button"><?php esc_html_e('Hledat', 'marmal-breakdance-plus'); ?></button>
        <span class="marmal-presm__pocet"><?php echo esc_html(sprintf(/* translators: %d: počet */ __('Celkem: %d', 'marmal-breakdance-plus'), $celkem)); ?></span>
    </form>
    <?php if (!$rows) : ?>
        <p class="marmal-presm__prazdno"><?php echo $hledat !== '' ? esc_html__('Nic nenalezeno.', 'marmal-breakdance-plus') : esc_html__('Zatím žádné přesměrování.', 'marmal-breakdance-plus'); ?></p>
        <?php return; ?>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="mp-hromadne">
        <?php nonce_pole('hromadne'); ?>
        <table class="widefat striped marmal-presm__tabulka">
            <thead><tr>
                <td class="check-column"><input type="checkbox" class="mp-vse" aria-label="<?php esc_attr_e('Vybrat vše', 'marmal-breakdance-plus'); ?>"></td>
                <th><?php esc_html_e('Stará adresa', 'marmal-breakdance-plus'); ?></th>
                <th><?php esc_html_e('Nová adresa', 'marmal-breakdance-plus'); ?></th>
                <th><?php esc_html_e('Typ', 'marmal-breakdance-plus'); ?></th>
                <th><?php esc_html_e('Použito', 'marmal-breakdance-plus'); ?></th>
                <th><?php esc_html_e('Vzniklo', 'marmal-breakdance-plus'); ?></th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r) : ?>
                <?php
                $cilUrl = cil_url((string) $r['cil']);
                $puvody = array('rucne' => __('ručně', 'marmal-breakdance-plus'), 'auto' => __('změna adresy', 'marmal-breakdance-plus'), 'import' => __('import', 'marmal-breakdance-plus'), 'stary-web' => __('starý web', 'marmal-breakdance-plus'), '404' => __('z chyby 404', 'marmal-breakdance-plus'));
                ?>
                <tr class="<?php echo $r['aktivni'] ? '' : 'is-vypnute'; ?>">
                    <th scope="row" class="check-column"><input type="checkbox" name="ids[]" value="<?php echo (int) $r['id']; ?>"></th>
                    <td><code><?php echo esc_html((string) $r['zdroj']); ?></code><?php if ($r['poznamka'] !== '') : ?><br><small><?php echo esc_html((string) $r['poznamka']); ?></small><?php endif; ?></td>
                    <td>
                        <?php if ((int) $r['kod'] === 410) : ?>
                            <em><?php esc_html_e('zrušeno', 'marmal-breakdance-plus'); ?></em>
                        <?php elseif ($cilUrl === '') : ?>
                            <span class="marmal-presm__chyba"><?php esc_html_e('Cílová stránka už neexistuje', 'marmal-breakdance-plus'); ?></span>
                        <?php else : ?>
                            <a href="<?php echo esc_url($cilUrl); ?>" target="_blank" rel="noopener"><?php echo esc_html(cil_popis((string) $r['cil'])); ?></a>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int) $r['kod']; ?><?php echo $r['aktivni'] ? '' : ' <span class="marmal-presm__stitek">' . esc_html__('vypnuto', 'marmal-breakdance-plus') . '</span>'; ?></td>
                    <td><?php echo (int) $r['zasahy']; ?><?php if ($r['posledni']) : ?><br><small><?php echo esc_html(mysql2date(get_option('date_format'), get_date_from_gmt((string) $r['posledni']))); ?></small><?php endif; ?></td>
                    <td><?php echo esc_html($puvody[$r['puvod']] ?? (string) $r['puvod']); ?></td>
                    <td class="marmal-presm__akce">
                        <a href="<?php echo esc_url(url(array('upravit' => (int) $r['id']))); ?>"><?php esc_html_e('Upravit', 'marmal-breakdance-plus'); ?></a> ·
                        <a href="<?php echo esc_url(akce_url('prepnout', array('id' => (int) $r['id']))); ?>"><?php echo $r['aktivni'] ? esc_html__('Vypnout', 'marmal-breakdance-plus') : esc_html__('Zapnout', 'marmal-breakdance-plus'); ?></a> ·
                        <a href="<?php echo esc_url(akce_url('smazat', array('id' => (int) $r['id']))); ?>" class="marmal-presm__smazat mp-potvrdit"><?php esc_html_e('Smazat', 'marmal-breakdance-plus'); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="marmal-presm__hromadne">
            <select name="co">
                <option value="vypnout"><?php esc_html_e('Vypnout vybrané', 'marmal-breakdance-plus'); ?></option>
                <option value="zapnout"><?php esc_html_e('Zapnout vybrané', 'marmal-breakdance-plus'); ?></option>
                <option value="smazat"><?php esc_html_e('Smazat vybrané', 'marmal-breakdance-plus'); ?></option>
            </select>
            <button class="button mp-hromadne-btn"><?php esc_html_e('Provést', 'marmal-breakdance-plus'); ?></button>
        </p>
    </form>
    <?php
    strankovani($celkem, $strana, array('s' => $hledat ?: null, 'razeni' => $razeni !== 'vytvoreno' ? $razeni : null));
}

function strankovani(int $celkem, int $strana, array $args): void
{
    $stran = (int) ceil($celkem / 50);
    if ($stran < 2) {
        return;
    }
    echo '<p class="marmal-presm__strany">';
    for ($i = 1; $i <= $stran; $i++) {
        if ($i === $strana) {
            echo '<span class="button button-disabled">' . (int) $i . '</span> ';
        } elseif ($i <= 3 || $i > $stran - 2 || abs($i - $strana) <= 2) {
            echo '<a class="button" href="' . esc_url(url(array_merge($args, array('strana' => $i)))) . '">' . (int) $i . '</a> ';
        } elseif ($i === 4 || $i === $stran - 2) {
            echo '… ';
        }
    }
    echo '</p>';
}

function tab_chyby(): void
{
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- jen zobrazení
    $hledat = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $strana = isset($_GET['strana']) ? max(1, absint($_GET['strana'])) : 1;
    $roboti = !empty($_GET['roboti']);
    // phpcs:enable
    $nast = nastaveni();
    if (!$nast['log404']) {
        echo '<div class="notice notice-info inline"><p>' . esc_html__('Hlídání chyb 404 je vypnuté (záložka Nastavení).', 'marmal-breakdance-plus') . '</p></div>';
    }
    list($rows, $celkem) = seznam_404($hledat, $strana, 50, $roboti);
    ?>
    <p class="description"><?php echo esc_html(sprintf(/* translators: %d: dny */ __('Adresy, na které někdo přišel a stránka neexistovala. Ukládá se jen adresa, počet, čas a web, ze kterého návštěvník přišel – žádné IP adresy. Záznamy starší než %d dní se mažou.', 'marmal-breakdance-plus'), (int) $nast['dni404'])); ?></p>
    <form method="get" class="marmal-presm__hledat">
        <input type="hidden" name="page" value="<?php echo esc_attr(STRANKA); ?>"><input type="hidden" name="tab" value="404">
        <input type="search" name="s" value="<?php echo esc_attr($hledat); ?>" placeholder="<?php esc_attr_e('Hledat adresu', 'marmal-breakdance-plus'); ?>">
        <label><input type="checkbox" name="roboti" value="1"<?php checked($roboti); ?>> <?php esc_html_e('Včetně robotů', 'marmal-breakdance-plus'); ?></label>
        <button class="button"><?php esc_html_e('Zobrazit', 'marmal-breakdance-plus'); ?></button>
        <span class="marmal-presm__pocet"><?php echo esc_html(sprintf(/* translators: %d: počet */ __('Celkem: %d', 'marmal-breakdance-plus'), $celkem)); ?></span>
    </form>
    <?php if (!$rows) : ?>
        <p class="marmal-presm__prazdno"><?php esc_html_e('Žádné chyby 404. 🎉', 'marmal-breakdance-plus'); ?></p>
        <?php return; ?>
    <?php endif; ?>
    <table class="widefat striped marmal-presm__tabulka">
        <thead><tr>
            <th><?php esc_html_e('Adresa', 'marmal-breakdance-plus'); ?></th>
            <th><?php esc_html_e('Počet', 'marmal-breakdance-plus'); ?></th>
            <th><?php esc_html_e('Naposledy', 'marmal-breakdance-plus'); ?></th>
            <th><?php esc_html_e('Přišel z', 'marmal-breakdance-plus'); ?></th>
            <th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r) : ?>
            <tr>
                <td><a href="<?php echo esc_url(home_url((string) $r['cesta'])); ?>" target="_blank" rel="noopener"><code><?php echo esc_html((string) $r['cesta']); ?></code></a><?php echo $r['robot'] ? ' <span class="marmal-presm__stitek">' . esc_html__('robot', 'marmal-breakdance-plus') . '</span>' : ''; ?></td>
                <td><?php echo (int) $r['pocet']; ?></td>
                <td><?php echo esc_html(mysql2date(get_option('date_format') . ' H:i', get_date_from_gmt((string) $r['posledni']))); ?></td>
                <td><small><?php echo esc_html((string) $r['odkud']); ?></small></td>
                <td class="marmal-presm__akce">
                    <a href="<?php echo esc_url(url(array('zdroj' => (string) $r['cesta']))); ?>" class="button button-small button-primary"><?php esc_html_e('Přesměrovat', 'marmal-breakdance-plus'); ?></a>
                    <a href="<?php echo esc_url(akce_url('smazat404', array('id' => (int) $r['id']))); ?>" class="button button-small"><?php esc_html_e('Skrýt', 'marmal-breakdance-plus'); ?></a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php strankovani($celkem, $strana, array('tab' => '404', 's' => $hledat ?: null, 'roboti' => $roboti ? 1 : null)); ?>
    <p><a href="<?php echo esc_url(akce_url('vymazat404')); ?>" class="button mp-potvrdit"><?php esc_html_e('Vymazat celý seznam', 'marmal-breakdance-plus'); ?></a></p>
    <?php
}

function tab_stary(): void
{
    $s = stary_web();
    if (!$s) {
        ?>
        <div class="marmal-presm__box">
            <h2><?php esc_html_e('1. Adresy starého webu', 'marmal-breakdance-plus'); ?></h2>
            <p><?php esc_html_e('Ideálně před spuštěním nového webu: zadej sitemapu starého webu (ještě běží), nahraj ji jako soubor, nebo vlož seznam adres. Zdroje jde kombinovat – adresy se sloučí. Modul pro každou adresu zjistí, jestli na novém webu existuje, a když ne, navrhne novou stránku podle názvu. Ty návrhy jen zkontroluješ a potvrdíš.', 'marmal-breakdance-plus'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php nonce_pole('stary_nacist'); ?>
                <p><label><strong><?php esc_html_e('Sitemapa starého webu', 'marmal-breakdance-plus'); ?></strong><br>
                    <input type="url" name="sitemap" class="large-text" placeholder="https://stary-web.cz/sitemap_index.xml"></label></p>
                <p class="description"><?php esc_html_e('WordPress: /wp-sitemap.xml, Yoast a Rank Math: /sitemap_index.xml, Divi a jiné: /sitemap.xml. Pokud starý web běží na stejné doméně jako nový (staging), zadej sitemapu ještě před přepnutím, nebo vlož seznam adres.', 'marmal-breakdance-plus'); ?></p>
                <p><label><strong><?php esc_html_e('… nebo nahraj soubor se sitemapou', 'marmal-breakdance-plus'); ?></strong><br>
                    <input type="file" name="soubory[]" multiple accept=".xml,.gz,.txt,.csv,application/xml,text/xml,text/plain,text/csv,application/gzip"></label></p>
                <p class="description"><?php esc_html_e('Když starý web už neběží nebo sitemapu nejde stáhnout (ochrana proti robotům, heslo, staging): sitemapa .xml nebo .xml.gz, případně seznam adres .txt / .csv (adresa v prvním sloupci – třeba export ze Screaming Frogu nebo Search Console). Yoast a Rank Math mají sitemapu po částech (page-sitemap.xml, post-sitemap.xml…) – vyber všechny najednou. Max 5 MB na soubor.', 'marmal-breakdance-plus'); ?></p>
                <p><label><strong><?php esc_html_e('… nebo seznam adres (jedna na řádek)', 'marmal-breakdance-plus'); ?></strong><br>
                    <textarea name="seznam" rows="8" class="large-text" placeholder="https://stary-web.cz/o-nas/&#10;/sluzby/akustika.html&#10;/kontakt"></textarea></label></p>
                <?php submit_button(__('Načíst adresy', 'marmal-breakdance-plus'), 'primary', 'submit', false); ?>
            </form>
        </div>
        <?php
        return;
    }
    $pocty = array_count_values(array_column($s['radky'], 'stav'));
    $stavy = array(
        'navrh'    => __('Návrh', 'marmal-breakdance-plus'),
        'nic'      => __('Bez návrhu', 'marmal-breakdance-plus'),
        'soubor'   => __('Soubor', 'marmal-breakdance-plus'),
        'pravidlo' => __('Už přesměrováno', 'marmal-breakdance-plus'),
        'existuje' => __('Existuje i na novém webu', 'marmal-breakdance-plus'),
    );
    $staryHost = $s['zdroj'] !== 'seznam' ? (string) wp_parse_url((string) $s['zdroj'], PHP_URL_SCHEME) . '://' . (string) wp_parse_url((string) $s['zdroj'], PHP_URL_HOST) : '';
    ?>
    <div class="marmal-presm__box">
        <h2><?php esc_html_e('2. Zkontroluj návrhy a vytvoř přesměrování', 'marmal-breakdance-plus'); ?></h2>
        <p class="marmal-presm__filtry">
            <button type="button" class="button mp-filtr is-aktivni" data-stav=""><?php echo esc_html(sprintf(/* translators: %d: počet */ __('Vše (%d)', 'marmal-breakdance-plus'), count($s['radky']))); ?></button>
            <?php foreach ($stavy as $k => $t) : ?>
                <?php if (!empty($pocty[$k])) : ?>
                    <button type="button" class="button mp-filtr" data-stav="<?php echo esc_attr($k); ?>"><?php echo esc_html($t . ' (' . (int) $pocty[$k] . ')'); ?></button>
                <?php endif; ?>
            <?php endforeach; ?>
        </p>
        <p class="description"><?php esc_html_e('Zaškrtnuté řádky se vytvoří jako přesměrování 301. Novou adresu můžeš přepsat (začni psát – nabídnou se stránky webu). Pro zrušený obsah bez náhrady zvol 410. Adresy, které existují i na novém webu, přesměrovávat nemusíš.', 'marmal-breakdance-plus'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="mp-stary">
            <?php nonce_pole('stary_vytvorit'); ?>
            <input type="hidden" name="radky_json" id="mp-stary-json" value="[]">
            <datalist id="mp-stranky">
                <?php
                foreach (kandidati() as $k) {
                    $c = cesta_pro_export((string) get_permalink($k['id']));
                    printf('<option value="%s">%s</option>', esc_attr($c), esc_html($k['nazev']));
                }
                ?>
            </datalist>
            <table class="widefat marmal-presm__tabulka marmal-presm__stary">
                <thead><tr>
                    <td class="check-column"><input type="checkbox" class="mp-vse" aria-label="<?php esc_attr_e('Vybrat vše', 'marmal-breakdance-plus'); ?>"></td>
                    <th><?php esc_html_e('Stará adresa', 'marmal-breakdance-plus'); ?></th>
                    <th><?php esc_html_e('Stav', 'marmal-breakdance-plus'); ?></th>
                    <th><?php esc_html_e('Nová adresa', 'marmal-breakdance-plus'); ?></th>
                    <th><?php esc_html_e('Typ', 'marmal-breakdance-plus'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($s['radky'] as $r) : ?>
                    <?php
                    $hotove = in_array($r['stav'], array('pravidlo', 'existuje'), true);
                    $cil    = '';
                    if (preg_match('/^post:(\d+)$/', (string) $r['cil'], $m)) {
                        $cil = cesta_pro_export((string) get_permalink((int) $m[1]));
                    }
                    ?>
                    <tr data-stav="<?php echo esc_attr($r['stav']); ?>" class="mp-stary-radek">
                        <th scope="row" class="check-column"><input type="checkbox" class="mp-ano"<?php checked($r['stav'] === 'navrh'); ?><?php disabled($hotove); ?>></th>
                        <td><?php if ($staryHost !== '') : ?><a href="<?php echo esc_url($staryHost . $r['stara']); ?>" target="_blank" rel="noopener noreferrer"><code class="mp-stara"><?php echo esc_html($r['stara']); ?></code></a><?php else : ?><code class="mp-stara"><?php echo esc_html($r['stara']); ?></code><?php endif; ?></td>
                        <td><span class="marmal-presm__stav marmal-presm__stav--<?php echo esc_attr($r['stav']); ?>"><?php echo esc_html($stavy[$r['stav']] ?? $r['stav']); ?></span>
                            <?php if ($r['stav'] === 'navrh') : ?><br><small><?php echo esc_html($r['nazev'] . ' · ' . $r['skore'] . ' %'); ?></small><?php endif; ?></td>
                        <td><?php if (!$hotove) : ?><input type="text" class="mp-cil regular-text" list="mp-stranky" value="<?php echo esc_attr($cil); ?>" placeholder="/nova-stranka/"><?php endif; ?></td>
                        <td><?php if (!$hotove) : ?><select class="mp-kod"><option value="301">301</option><option value="410">410</option></select><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <?php submit_button(__('Vytvořit přesměrování z vybraných řádků', 'marmal-breakdance-plus'), 'primary', 'submit', false); ?>
                <span class="marmal-presm__pocet" id="mp-stary-pocet"></span>
            </p>
        </form>
        <p><a href="<?php echo esc_url(akce_url('stary_zahodit')); ?>" class="mp-potvrdit"><?php esc_html_e('Zahodit seznam a začít znovu', 'marmal-breakdance-plus'); ?></a></p>
    </div>
    <?php
}

function tab_csv(): void
{
    ?>
    <div class="marmal-presm__box">
        <h2><?php esc_html_e('Export', 'marmal-breakdance-plus'); ?></h2>
        <p><?php esc_html_e('Všechna přesměrování jako CSV (otevřeš v Excelu). Hodí se jako záloha nebo k přenosu na jiný web.', 'marmal-breakdance-plus'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php nonce_pole('export'); ?>
            <?php submit_button(__('Stáhnout CSV', 'marmal-breakdance-plus'), 'secondary', 'submit', false); ?>
        </form>
    </div>
    <div class="marmal-presm__box">
        <h2><?php esc_html_e('Import', 'marmal-breakdance-plus'); ?></h2>
        <p><?php esc_html_e('Sloupce: stará adresa ; nová adresa ; kód (301 / 302 / 307 / 410, prázdné = 301) ; poznámka. První řádek s názvy sloupců se přeskočí. Oddělovač středník i čárka, uložení z Excelu (CSV UTF-8 i obyčejné CSV) funguje. Existující stará adresa se přepíše.', 'marmal-breakdance-plus'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <?php nonce_pole('import'); ?>
            <p><input type="file" name="csv" accept=".csv,text/csv" required></p>
            <?php submit_button(__('Importovat', 'marmal-breakdance-plus'), 'primary', 'submit', false); ?>
        </form>
        <p class="description"><?php esc_html_e('Ukázka:', 'marmal-breakdance-plus'); ?></p>
        <pre class="marmal-presm__ukazka">stara_adresa;nova_adresa;kod;poznamka
/o-nas.html;/o-nas/;301;
/sluzby/*;/co-delame/*;301;celá sekce
/akce-2019;;410;zrušená akce</pre>
    </div>
    <?php
}

function tab_nastaveni(): void
{
    global $wpdb;
    $n = nastaveni();
    $pocet = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . t_pravidla()); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    ?>
    <form method="post" action="options.php" class="marmal-presm__box">
        <?php settings_fields('marmal_presmerovani'); ?>
        <table class="form-table" role="presentation">
            <tr><th scope="row"><?php esc_html_e('Změna adresy stránky', 'marmal-breakdance-plus'); ?></th>
                <td><label><input type="hidden" name="<?php echo esc_attr(OPTION); ?>[auto]" value="0"><input type="checkbox" name="<?php echo esc_attr(OPTION); ?>[auto]" value="1"<?php checked($n['auto']); ?>> <?php esc_html_e('Automaticky přesměrovat starou adresu, když u publikované stránky změníš adresu (i podstránky)', 'marmal-breakdance-plus'); ?></label></td></tr>
            <tr><th scope="row"><?php esc_html_e('Chyby 404', 'marmal-breakdance-plus'); ?></th>
                <td><label><input type="hidden" name="<?php echo esc_attr(OPTION); ?>[log404]" value="0"><input type="checkbox" name="<?php echo esc_attr(OPTION); ?>[log404]" value="1"<?php checked($n['log404']); ?>> <?php esc_html_e('Hlídat chyby 404', 'marmal-breakdance-plus'); ?></label><br>
                    <label><?php esc_html_e('Mazat záznamy starší než', 'marmal-breakdance-plus'); ?> <input type="number" name="<?php echo esc_attr(OPTION); ?>[dni404]" value="<?php echo (int) $n['dni404']; ?>" min="7" max="365" class="small-text"> <?php esc_html_e('dní', 'marmal-breakdance-plus'); ?></label></td></tr>
            <tr><th scope="row"><?php esc_html_e('Parametry adresy', 'marmal-breakdance-plus'); ?></th>
                <td><label><input type="hidden" name="<?php echo esc_attr(OPTION); ?>[parametry]" value="0"><input type="checkbox" name="<?php echo esc_attr(OPTION); ?>[parametry]" value="1"<?php checked($n['parametry']); ?>> <?php esc_html_e('Předat parametry (např. ?utm_source=…) na novou adresu', 'marmal-breakdance-plus'); ?></label></td></tr>
        </table>
        <?php submit_button(); ?>
        <p class="description"><?php echo esc_html(sprintf(/* translators: %d: počet */ __('Přesměrování v databázi: %d. Při vypnutí modulu zůstanou uložená a po zapnutí znovu fungují.', 'marmal-breakdance-plus'), $pocet)); ?></p>
    </form>
    <?php
}
