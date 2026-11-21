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

        <h2><?php esc_html_e('Přednačtení', 'marmal-breakdance-plus'); ?></h2>
        <?php render_prednacteni($s); ?>

        <h2><?php esc_html_e('Odkud Breakdance písma bere', 'marmal-breakdance-plus'); ?></h2>
        <?php render_pouziti(); ?>

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
                    <?php foreach (array(
                        'auto'    => __('Automaticky podle webu – písmo textu a nadpisů z Global Settings Breakdance (doporučeno)', 'marmal-breakdance-plus'),
                        'rucne'   => __('Ručně – soubory zaškrtnuté v tabulce níže (nejvýš 3)', 'marmal-breakdance-plus'),
                        'vypnuto' => __('Vypnuto', 'marmal-breakdance-plus'),
                    ) as $hodnota => $popis) : ?>
                        <p><label><input type="radio" name="<?php echo esc_attr(OPTION); ?>[preload_rezim]" value="<?php echo esc_attr($hodnota); ?>" <?php checked($s['preload_rezim'], $hodnota); ?>> <?php echo esc_html($popis); ?></label></p>
                    <?php endforeach; ?>
                    <p class="description"><?php esc_html_e('Přednačte se jen soubor, který stránka opravdu používá (latinka; čeština z latin-ext se stáhne hned po něm). Variabilní písmo = jeden soubor pro všechny váhy.', 'marmal-breakdance-plus'); ?></p>
                    <?php render_rucni_vyber($s); ?>
                </td></tr>
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

/** Co se teď přednačítá + upozornění (automatický režim). */
function render_prednacteni(array $s): void
{
    $katalog = katalog();
    foreach (upozorneni_preload() as $u) {
        echo '<div class="notice notice-warning inline"><p>' . esc_html($u) . '</p></div>';
    }
    if ($s['preload_rezim'] === 'vypnuto') {
        echo '<p>' . esc_html__('Přednačtení je vypnuté.', 'marmal-breakdance-plus') . '</p>';
        return;
    }
    if ($s['preload_rezim'] === 'auto') {
        $pisma = pisma_webu();
        if (!$pisma) {
            echo '<p>' . esc_html__('V Global Settings Breakdance není nastavené písmo textu ani nadpisů (nebo Breakdance neběží) – nepřednačítá se nic.', 'marmal-breakdance-plus') . '</p>';
        } else {
            echo '<ul style="list-style:disc;padding-left:20px">';
            foreach ($pisma as $p) {
                $f = soubor_pro($p['font'], $p['vaha']);
                echo '<li><strong>' . esc_html($p['popis']) . ':</strong> <code>' . esc_html($p['font']) . '</code>'
                    . ($p['vaha'] !== '' ? ' · ' . esc_html(sprintf(/* translators: %s: váha */ __('váha %s', 'marmal-breakdance-plus'), $p['vaha'])) : '')
                    . ' → ' . ($f !== '' ? '<code>' . esc_html($f) . '</code> (' . esc_html(popis_souboru($katalog[$f])) . ')' : esc_html__('nenalezeno', 'marmal-breakdance-plus')) . '</li>';
            }
            echo '</ul>';
        }
    }
    $vyber = soubory_k_prednacteni();
    echo '<p>' . esc_html__('Přednačítá se:', 'marmal-breakdance-plus') . ' ' . ($vyber ? '<code>' . implode('</code>, <code>', array_map('esc_html', $vyber)) . '</code>' : esc_html__('nic', 'marmal-breakdance-plus')) . '</p>';
}

/** Krátký popis souboru: „DM Sans, normal, 100–1000 (variabilní), latin, 38 kB“. */
function popis_souboru(array $i): string
{
    $vahy = !empty($i['var']) ? str_replace(' ', '–', implode(', ', (array) $i['vahy'])) . ' (' . __('variabilní', 'marmal-breakdance-plus') . ')' : implode(', ', (array) $i['vahy']);
    return $i['rodina'] . ', ' . $i['styl'] . ', ' . $vahy . ', ' . $i['sada'] . ', ' . velikost((int) $i['velikost']);
}

/** Tabulka stažených souborů pro ruční výběr (vazba přes identitu, ne název souboru). */
function render_rucni_vyber(array $s): void
{
    $katalog = katalog();
    $zvolene = (array) $s['preload_soubory'];
    ?>
    <details id="marmal-fonty-rucne" style="margin-top:8px" <?php echo $s['preload_rezim'] === 'rucne' ? 'open' : ''; ?>>
        <summary><?php esc_html_e('Stažené soubory (ruční výběr)', 'marmal-breakdance-plus'); ?></summary>
        <?php if (!$katalog) : ?>
            <p><?php esc_html_e('Zatím žádné stažené soubory – nejdřív klikni na „Projít web a stáhnout“.', 'marmal-breakdance-plus'); ?></p>
        <?php else : ?>
            <table class="widefat striped" style="max-width:820px;margin-top:8px">
                <thead><tr>
                    <th style="width:30px"></th>
                    <th><?php esc_html_e('Písmo', 'marmal-breakdance-plus'); ?></th>
                    <th><?php esc_html_e('Styl', 'marmal-breakdance-plus'); ?></th>
                    <th><?php esc_html_e('Váha / rozsah vah', 'marmal-breakdance-plus'); ?></th>
                    <th><?php esc_html_e('Znaky', 'marmal-breakdance-plus'); ?></th>
                    <th><?php esc_html_e('Velikost', 'marmal-breakdance-plus'); ?></th>
                </tr></thead>
                <tbody>
                <?php
                uasort($katalog, function ($a, $b) {
                    return array($a['rodina'], $a['styl'], $a['sada']) <=> array($b['rodina'], $b['styl'], $b['sada']);
                });
                $videno = array();
                foreach ($katalog as $nazev => $i) :
                    $id = identita_souboru($i);
                    if (isset($videno[$id])) {
                        continue; // stejný soubor ve dvou sadách
                    }
                    $videno[$id] = true;
                    ?>
                    <tr>
                        <td><input type="checkbox" class="marmal-fonty-rucne" name="<?php echo esc_attr(OPTION); ?>[preload_soubory][]" value="<?php echo esc_attr($id); ?>" <?php checked(in_array($id, $zvolene, true)); ?> title="<?php echo esc_attr((string) $nazev); ?>"></td>
                        <td><?php echo esc_html((string) $i['rodina']); ?></td>
                        <td><?php echo esc_html((string) $i['styl']); ?></td>
                        <td><?php echo esc_html(!empty($i['var']) ? str_replace(' ', '–', implode(', ', (array) $i['vahy'])) . ' (' . __('variabilní', 'marmal-breakdance-plus') . ')' : implode(', ', (array) $i['vahy'])); ?></td>
                        <td><?php echo esc_html((string) $i['sada']); ?></td>
                        <td><?php echo esc_html(velikost((int) $i['velikost'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="description"><?php esc_html_e('Nejvýš 3 soubory. Výběr si pamatuje písmo, styl, váhu a znakovou sadu, takže přežije „Smazat a stáhnout znovu“.', 'marmal-breakdance-plus'); ?></p>
            <script>
            (function () {
              var boxy = document.querySelectorAll('.marmal-fonty-rucne');
              function hlidat() {
                var n = Array.prototype.filter.call(boxy, function (b) { return b.checked; }).length;
                Array.prototype.forEach.call(boxy, function (b) { b.disabled = !b.checked && n >= <?php echo (int) MAX_RUCNE; ?>; });
              }
              Array.prototype.forEach.call(boxy, function (b) { b.addEventListener('change', hlidat); });
              hlidat();
            })();
            </script>
        <?php endif; ?>
    </details>
    <?php
}

/** Přehled, kde se jednotlivá písma používají (na vyžádání – prochází data všech stránek). */
function render_pouziti(): void
{
    $zobrazit = isset($_GET['pouziti']); // phpcs:ignore WordPress.Security.NonceVerification -- jen zobrazení
    if (!$zobrazit) {
        echo '<p>' . esc_html__('Najde každé písmo z Google Fonts, které Breakdance používá, a ukáže kde: Global Settings, typografický preset, nebo konkrétní stránka / šablona s ID elementu. Pomůže najít zapomenutá písma (např. ve skryté sekci menu v hlavičce).', 'marmal-breakdance-plus') . '</p>';
        echo '<p><a class="button" href="' . esc_url(add_query_arg(array('page' => 'marmal-fonty', 'pouziti' => 1), admin_url('admin.php')) . '#pouziti') . '">' . esc_html__('Zjistit, kde se písma používají', 'marmal-breakdance-plus') . '</a></p>';
        return;
    }
    $pouziti = pouziti_pisem();
    $stazene = array();
    foreach (katalog() as $i) {
        $stazene[klic_rodiny((string) $i['rodina'])] = true;
    }
    echo '<table class="widefat striped" id="pouziti" style="max-width:1000px"><thead><tr><th>' . esc_html__('Písmo', 'marmal-breakdance-plus') . '</th><th>' . esc_html__('Kde se používá', 'marmal-breakdance-plus') . '</th></tr></thead><tbody>';
    if (!$pouziti) {
        echo '<tr><td colspan="2">' . esc_html__('Breakdance nepoužívá žádné písmo z Google Fonts.', 'marmal-breakdance-plus') . '</td></tr>';
    }
    foreach ($pouziti as $k => $p) {
        echo '<tr><td><code>' . esc_html($p['font']) . '</code>' . (isset($stazene[$k]) ? '' : '<br><small>' . esc_html__('nestaženo', 'marmal-breakdance-plus') . '</small>') . '</td><td><ul style="margin:0">';
        foreach ($p['mista'] as $m) {
            echo '<li>' . ($m[1] !== '' ? '<a href="' . esc_url($m[1]) . '" target="_blank" rel="noopener">' . esc_html($m[0]) . '</a>' : esc_html($m[0])) . '</li>';
        }
        echo '</ul></td></tr>';
    }
    echo '</tbody></table>';
    echo '<p class="description">' . esc_html__('Písmo, které je jen v jedné stránce nebo elementu a web ho jinak nepoužívá, je nejspíš zbytek staré typografie – odstraň ho v builderu (u elementu nastav písmo „výchozí“) a pak klikni na „Smazat a stáhnout znovu“.', 'marmal-breakdance-plus') . '</p>';
}
