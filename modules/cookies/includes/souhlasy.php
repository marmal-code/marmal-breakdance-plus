<?php
/**
 * Modul Cookies – záznam souhlasů (důkaz podle čl. 7 odst. 1 GDPR).
 *
 * Ukládá se jen: čas, náhodné ID souhlasu (z cookie návštěvníka), volba, povolené kategorie, revize.
 * Žádná IP adresa ani User-Agent. Staré záznamy maže denní úklid podle nastavení (1–5 let).
 *
 * Zápis jde přes veřejný REST endpoint (návštěvník není přihlášený a stránka může být z page cache,
 * takže nonce použít nejde). Ochrana: povolení jen ze stejného webu (Origin / Referer), limit
 * zápisů na IP (otisk IP, ne IP), přísná kontrola hodnot, strop velikosti tabulky.
 */

namespace MarmalElements\Cookies;

if (!defined('ABSPATH')) {
    exit;
}

const DB_VERZE   = '1';
const LIMIT_IP   = 20;       // zápisů za 10 minut z jedné IP
const MAX_RADKU  = 500000;   // strop tabulky (pojistka proti zaplnění databáze)

function tabulka(): string
{
    global $wpdb;
    return $wpdb->prefix . 'marmal_souhlasy';
}

function db_instalace(): void
{
    if (get_option('marmal_bdp_cookies_db') === DB_VERZE) {
        return;
    }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $t = tabulka();
    dbDelta("CREATE TABLE {$t} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        cas DATETIME NOT NULL,
        souhlas_id VARCHAR(16) NOT NULL,
        volba VARCHAR(10) NOT NULL,
        kategorie VARCHAR(80) NOT NULL DEFAULT '',
        revize INT UNSIGNED NOT NULL DEFAULT 1,
        PRIMARY KEY  (id),
        KEY cas (cas),
        KEY souhlas_id (souhlas_id)
    ) " . $wpdb->get_charset_collate() . ';');
    update_option('marmal_bdp_cookies_db', DB_VERZE, true);
}

add_action('admin_init', __NAMESPACE__ . '\db_instalace');

// ============================================================= REST: zápis souhlasu

add_action('rest_api_init', function () {
    register_rest_route('marmal/v1', '/souhlas', array(
        'methods'             => 'POST',
        'callback'            => __NAMESPACE__ . '\rest_zapsat',
        'permission_callback' => __NAMESPACE__ . '\rest_smi_zapsat',
        'args'                => array(
            'id'        => array('type' => 'string', 'required' => true),
            'akce'      => array('type' => 'string', 'required' => true),
            'kategorie' => array('type' => 'array', 'required' => false, 'items' => array('type' => 'string')),
            'revize'    => array('type' => 'integer', 'required' => true),
        ),
    ));
});

/** Otisk IP pro limit (IP se nikam neukládá – jen jako klíč dočasného počítadla). */
function otisk_ip(): string
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    return substr(hash_hmac('sha256', $ip, wp_salt('nonce')), 0, 20);
}

/** Je hostitel adresy stejný jako web? */
function stejny_web(string $url): bool
{
    $a = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
    $b = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
    return $a !== '' && preg_replace('/^www\./', '', $a) === preg_replace('/^www\./', '', $b);
}

/**
 * Smí se souhlas zapsat? Veřejný endpoint (návštěvník není přihlášený) – ne __return_true:
 * jen ze stránek tohoto webu a s limitem zápisů.
 *
 * @return true|\WP_Error
 */
function rest_smi_zapsat(\WP_REST_Request $r)
{
    $puvod = (string) ($r->get_header('origin') ?: $r->get_header('referer'));
    if ($puvod === '' || !stejny_web($puvod)) {
        return new \WP_Error('marmal_puvod', 'Forbidden', array('status' => 403));
    }
    $klic  = 'mmck_rl_' . otisk_ip();
    $pocet = (int) get_transient($klic);
    if ($pocet >= LIMIT_IP) {
        return new \WP_Error('marmal_limit', 'Too Many Requests', array('status' => 429));
    }
    set_transient($klic, $pocet + 1, 10 * MINUTE_IN_SECONDS);
    return true;
}

/**
 * Očištění a kontrola hodnot (samostatně kvůli testům).
 *
 * @return array|null [souhlas_id, volba, kategorie, revize] nebo null = neplatné
 */
function zaznam_hodnoty($id, $akce, $kategorie, $revize): ?array
{
    $id = is_string($id) ? $id : '';
    if (!preg_match('/^[a-z0-9]{6,16}$/', $id)) {
        return null;
    }
    if (!in_array($akce, array('vse', 'nic', 'vyber'), true)) {
        return null;
    }
    $povolene = array('preferencni', 'analyticke', 'marketingove');
    $kat      = array_values(array_unique(array_intersect($povolene, array_map('strval', is_array($kategorie) ? $kategorie : array()))));
    $revize   = (int) $revize;
    if ($revize < 1 || $revize > 100000) {
        return null;
    }
    return array($id, $akce, implode(',', $kat), $revize);
}

function rest_zapsat(\WP_REST_Request $r)
{
    $h = zaznam_hodnoty($r->get_param('id'), $r->get_param('akce'), $r->get_param('kategorie'), $r->get_param('revize'));
    if (!$h) {
        return new \WP_Error('marmal_hodnoty', 'Bad Request', array('status' => 400));
    }
    db_instalace();
    global $wpdb;
    $t = tabulka();
    // Strop tabulky – při zahlcení se nový záznam neuloží (volba návštěvníka v jeho cookie platí dál).
    $pocet = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- název tabulky z $wpdb->prefix
    if ($pocet >= MAX_RADKU) {
        return new \WP_REST_Response(array('ok' => false), 507);
    }
    $wpdb->insert($t, array(
        'cas'        => current_time('mysql', true),
        'souhlas_id' => $h[0],
        'volba'      => $h[1],
        'kategorie'  => $h[2],
        'revize'     => $h[3],
    ), array('%s', '%s', '%s', '%s', '%d')); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    return new \WP_REST_Response(array('ok' => true), 201);
}

// ============================================================= úklid

add_action('init', function () {
    if (!wp_next_scheduled('marmal_cookies_uklid')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'marmal_cookies_uklid');
    }
});

add_action('marmal_cookies_uklid', function () {
    if (get_option('marmal_bdp_cookies_db') !== DB_VERZE) {
        return;
    }
    global $wpdb;
    $t     = tabulka();
    $hrana = gmdate('Y-m-d H:i:s', time() - (int) nastaveni()['uchovat'] * YEAR_IN_SECONDS);
    $wpdb->query($wpdb->prepare("DELETE FROM {$t} WHERE cas < %s", $hrana)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
});

// ============================================================= pro administraci

/** Posledních N záznamů. */
function posledni(int $n = 30): array
{
    if (get_option('marmal_bdp_cookies_db') !== DB_VERZE) {
        return array();
    }
    global $wpdb;
    $t = tabulka();
    return (array) $wpdb->get_results($wpdb->prepare("SELECT cas, souhlas_id, volba, kategorie, revize FROM {$t} ORDER BY id DESC LIMIT %d", $n), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
}

/** Počty voleb za posledních 30 dní: [vse, nic, vyber]. */
function statistika(): array
{
    $out = array('vse' => 0, 'nic' => 0, 'vyber' => 0);
    if (get_option('marmal_bdp_cookies_db') !== DB_VERZE) {
        return $out;
    }
    global $wpdb;
    $t     = tabulka();
    $hrana = gmdate('Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS);
    $rows  = (array) $wpdb->get_results($wpdb->prepare("SELECT volba, COUNT(*) AS n FROM {$t} WHERE cas >= %s GROUP BY volba", $hrana), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
    foreach ($rows as $r) {
        if (isset($out[$r['volba']])) {
            $out[$r['volba']] = (int) $r['n'];
        }
    }
    return $out;
}

/** Buňka CSV bez vzorců (ochrana proti CSV injection v Excelu). */
function csv_bunka(string $v): string
{
    if ($v !== '' && strpos('=+-@', $v[0]) !== false) {
        $v = "'" . $v;
    }
    return '"' . str_replace('"', '""', $v) . '"';
}

add_action('admin_post_marmal_cookies_export', function () {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Nemáte oprávnění.', 'marmal-breakdance-plus'), '', array('response' => 403));
    }
    check_admin_referer('marmal_cookies_export');
    db_instalace();
    global $wpdb;
    $t = tabulka();
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="souhlasy-cookies-' . gmdate('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF" . 'cas_utc;souhlas_id;volba;kategorie;revize' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
    $od = 0;
    do {
        $rows = (array) $wpdb->get_results($wpdb->prepare("SELECT id, cas, souhlas_id, volba, kategorie, revize FROM {$t} WHERE id > %d ORDER BY id ASC LIMIT 2000", $od), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
        foreach ($rows as $r) {
            $od = (int) $r['id'];
            echo implode(';', array(csv_bunka($r['cas']), csv_bunka($r['souhlas_id']), csv_bunka($r['volba']), csv_bunka($r['kategorie']), csv_bunka((string) $r['revize']))) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- CSV, buňky ošetřené csv_bunka()
        }
    } while (count($rows) === 2000);
    exit;
});

add_action('admin_post_marmal_cookies_revize', function () {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Nemáte oprávnění.', 'marmal-breakdance-plus'), '', array('response' => 403));
    }
    check_admin_referer('marmal_cookies_revize');
    $s        = get_option(OPTION, array());
    $s        = is_array($s) ? $s : array();
    $s['rev'] = max(1, (int) ($s['rev'] ?? 1)) + 1;
    // Přímo přes update_option bez sanitizace z formuláře (ta revizi záměrně nemění).
    remove_all_filters('sanitize_option_' . OPTION);
    update_option(OPTION, array_merge(nastaveni(), $s));
    wp_safe_redirect(admin_url('admin.php?page=marmal-cookies&tab=souhlasy&zprava=revize'));
    exit;
});

add_action('admin_post_marmal_cookies_zapomenout', function () {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Nemáte oprávnění.', 'marmal-breakdance-plus'), '', array('response' => 403));
    }
    check_admin_referer('marmal_cookies_zapomenout');
    delete_option(OPTION_NALEZ);
    wp_safe_redirect(admin_url('admin.php?page=marmal-cookies&tab=kategorie&zprava=zapomenuto'));
    exit;
});
