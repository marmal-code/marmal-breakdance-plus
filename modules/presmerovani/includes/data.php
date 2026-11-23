<?php
/**
 * Modul Přesměrování – nastavení, tabulky a pravidla (bez HTML).
 *
 * Pravidlo: zdroj (cesta bez domény, malými písmeny, bez lomítka na konci; „/blog/*“ = celá sekce),
 * cíl („/nova-cesta/“, „https://jiny-web.cz/…“ nebo „post:123“ = vždy aktuální adresa stránky),
 * kód 301 / 302 / 307 / 410 (410 = stránka zrušena, cíl se nezadává).
 * Adresy na stejném webu se ukládají jako cesta – pravidla tak fungují i po přesunu ze stagingu.
 */

namespace MarmalElements\Presmerovani;

if (!defined('ABSPATH')) {
    exit;
}

const OPTION      = 'marmal_bdp_presmerovani';
const OPT_DB      = 'marmal_bdp_presmerovani_db';
const OPT_SEKCE   = 'marmal_bdp_presmerovani_sekce';
const DB_VERZE    = '1';
const KODY        = array(301, 302, 307, 410);
const MAX_CESTA   = 500;
const MAX_404     = 2000;   // strop tabulky chyb 404
const MAX_PRAVIDEL = 20000;

// ================================================================= nastavení

function prazdne(): array
{
    return array(
        'auto'      => true,  // přesměrování při změně adresy publikované stránky
        'log404'    => true,  // hlídání chyb 404
        'dni404'    => 90,    // jak dlouho držet záznamy 404
        'parametry' => true,  // předat parametry adresy (?utm_…) do cíle bez vlastních parametrů
    );
}

function nastaveni(): array
{
    $s = get_option(OPTION, array());
    return array_merge(prazdne(), is_array($s) ? $s : array());
}

/** Sanitizace nastavení (register_setting; bez wp_unslash – options.php už odslashoval). */
function sanitizovat($in): array
{
    $in = is_array($in) ? $in : array();
    return array(
        'auto'      => !empty($in['auto']),
        'log404'    => !empty($in['log404']),
        'dni404'    => max(7, min(365, absint($in['dni404'] ?? 90))),
        'parametry' => !empty($in['parametry']),
    );
}

/** Jiné pluginy na přesměrování (jen upozornění). */
function konkurence(): array
{
    $znama = array('redirection' => 'Redirection', 'safe-redirect-manager' => 'Safe Redirect Manager', '301-redirects' => '301 Redirects', 'simple-301-redirects' => 'Simple 301 Redirects', 'eps-301-redirects' => '301 Redirects (EPS)');
    $out   = array();
    foreach ((array) get_option('active_plugins', array()) as $plugin) {
        $slozka = strtok((string) $plugin, '/');
        if (isset($znama[$slozka])) {
            $out[$slozka] = $znama[$slozka];
        }
    }
    if (defined('RANK_MATH_VERSION')) {
        $moduly = get_option('rank_math_modules', array());
        if (is_array($moduly) && in_array('redirections', $moduly, true)) {
            $out['rank-math'] = 'Rank Math (Přesměrování)';
        }
    }
    return $out;
}

// ================================================================= tabulky

function t_pravidla(): string
{
    global $wpdb;
    return $wpdb->prefix . 'marmal_presmerovani';
}

function t_404(): string
{
    global $wpdb;
    return $wpdb->prefix . 'marmal_404';
}

function db_instalace(): void
{
    if (get_option(OPT_DB) === DB_VERZE) {
        return;
    }
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $c = $wpdb->get_charset_collate();
    dbDelta('CREATE TABLE ' . t_pravidla() . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        zdroj VARCHAR(500) NOT NULL,
        zdroj_hash CHAR(32) NOT NULL,
        sekce TINYINT(1) NOT NULL DEFAULT 0,
        cil VARCHAR(1000) NOT NULL DEFAULT '',
        kod SMALLINT UNSIGNED NOT NULL DEFAULT 301,
        aktivni TINYINT(1) NOT NULL DEFAULT 1,
        zasahy BIGINT UNSIGNED NOT NULL DEFAULT 0,
        posledni DATETIME NULL,
        puvod VARCHAR(20) NOT NULL DEFAULT 'rucne',
        poznamka VARCHAR(200) NOT NULL DEFAULT '',
        vytvoreno DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY zdroj_hash (zdroj_hash)
    ) {$c};");
    dbDelta('CREATE TABLE ' . t_404() . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        cesta VARCHAR(500) NOT NULL,
        cesta_hash CHAR(32) NOT NULL,
        pocet BIGINT UNSIGNED NOT NULL DEFAULT 1,
        robot TINYINT(1) NOT NULL DEFAULT 0,
        odkud VARCHAR(300) NOT NULL DEFAULT '',
        prvni DATETIME NOT NULL,
        posledni DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY cesta_hash (cesta_hash),
        KEY posledni (posledni)
    ) {$c};");
    update_option(OPT_DB, DB_VERZE, true);
}

add_action('admin_init', __NAMESPACE__ . '\db_instalace');

function db_pripravena(): bool
{
    return get_option(OPT_DB) === DB_VERZE;
}

function ted(): string
{
    return current_time('mysql', true);
}

// ================================================================= adresy

/** Cesta webu v instalaci v podsložce („/web“), jinak ''. */
function zaklad(): string
{
    $p = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
    return rtrim($p, '/');
}

/**
 * Jednotný tvar cesty: bez domény, parametrů a #, dekódovaná, malými písmeny, bez lomítka na konci
 * (kromě „/“), bez podsložky instalace. Hvězdička na konci (sekce) zůstává. '' = neplatné.
 */
function cesta(string $adresa): string
{
    $adresa = trim($adresa);
    if ($adresa === '') {
        return '';
    }
    $sekce = substr($adresa, -1) === '*';
    if ($sekce) {
        $adresa = rtrim(substr($adresa, 0, -1));
    }
    $p = preg_match('#^[a-z][a-z0-9+.-]*://#i', $adresa) || strpos($adresa, '//') === 0
        ? (string) wp_parse_url($adresa, PHP_URL_PATH)
        : (string) strtok(strtok($adresa, '#'), '?');
    $p = rawurldecode($p);
    $p = wp_strip_all_tags($p);
    $p = preg_replace('/[\x00-\x1F\x7F\s]+/u', '', $p);
    $p = '/' . ltrim((string) $p, '/');
    $p = preg_replace('#/{2,}#', '/', $p);
    $z = zaklad();
    if ($z !== '' && stripos($p, $z . '/') === 0) {
        $p = substr($p, strlen($z));
    }
    $p = mb_strtolower($p, 'UTF-8');
    if ($p !== '/') {
        $p = rtrim($p, '/');
    }
    if ($p === '' || mb_strlen($p, 'UTF-8') > MAX_CESTA || strpos($p, '..') !== false) {
        return '';
    }
    return $sekce ? rtrim($p, '/') . '/*' : $p;
}

function je_sekce(string $zdroj): bool
{
    return substr($zdroj, -2) === '/*';
}

/** Zdroj, který se přesměrovat nesmí (administrace, přihlášení, REST, celý web). */
function zakazany_zdroj(string $zdroj): bool
{
    if ($zdroj === '' || $zdroj === '/' || $zdroj === '/*') {
        return true;
    }
    return (bool) preg_match('#^/(wp-admin|wp-login\.php|wp-json|wp-content|wp-includes|xmlrpc\.php|wp-cron\.php)(/|$|\*)#', $zdroj);
}

/**
 * Cíl do tvaru pro uložení: „post:ID“, cesta na tomto webu, nebo absolutní http(s) adresa jiného webu.
 * '' = neplatný.
 */
function sanitizovat_cil(string $cil): string
{
    $cil = trim(wp_strip_all_tags($cil));
    if ($cil === '') {
        return '';
    }
    if (preg_match('/^post:(\d+)$/', $cil, $m)) {
        return 'post:' . absint($m[1]);
    }
    if (preg_match('#^https?://#i', $cil)) {
        $url  = esc_url_raw($cil, array('http', 'https'));
        $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        $muj  = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        if ($url === '' || $host === '') {
            return '';
        }
        if ($host === $muj || $host === 'www.' . $muj || 'www.' . $host === $muj) {
            $cesta = (string) wp_parse_url($url, PHP_URL_PATH);
            $dotaz = (string) wp_parse_url($url, PHP_URL_QUERY);
            $z     = zaklad();
            if ($z !== '' && stripos($cesta, $z . '/') === 0) {
                $cesta = substr($cesta, strlen($z));
            }
            return mb_substr(($cesta === '' ? '/' : $cesta) . ($dotaz !== '' ? '?' . $dotaz : ''), 0, 1000, 'UTF-8');
        }
        return mb_substr($url, 0, 1000, 'UTF-8');
    }
    if ($cil[0] !== '/') {
        $cil = '/' . $cil;
    }
    $cil = preg_replace('/[\x00-\x1F\x7F]+/', '', $cil);
    $cil = str_replace(array('"', "'", '<', '>', '\\', ' '), array('', '', '', '', '', '%20'), (string) $cil);
    $cil = preg_replace('#^/{2,}#', '/', $cil); // //host by byl odkaz na cizí web
    return mb_substr((string) $cil, 0, 1000, 'UTF-8');
}

/** Cíl na tomto webu (cesta nebo stránka), ne jiný web. */
function je_interni(string $cil): bool
{
    return !preg_match('#^https?://#i', $cil);
}

/**
 * Cesta na existující publikovanou stránku → „post:ID“ (přesměrování pak přežije její přejmenování).
 * Jen přesná shoda adresy a bez parametrů; jinak beze změny.
 */
function cil_na_stranku(string $cil): string
{
    if ($cil === '' || $cil[0] !== '/' || strpos($cil, '?') !== false || strpos($cil, '*') !== false) {
        return $cil;
    }
    $id = (int) url_to_postid(home_url($cil));
    if ($id && get_post_status($id) === 'publish' && cesta((string) get_permalink($id)) === cesta($cil)) {
        return 'post:' . $id;
    }
    return $cil;
}

/** Absolutní adresa cíle ('' = cíl neexistuje, např. smazaná stránka). */
function cil_url(string $cil): string
{
    if ($cil === '') {
        return '';
    }
    if (preg_match('/^post:(\d+)$/', $cil, $m)) {
        $id = (int) $m[1];
        return get_post_status($id) === 'publish' ? (string) get_permalink($id) : '';
    }
    if (preg_match('#^https?://#i', $cil)) {
        return $cil;
    }
    return home_url($cil);
}

/** Popis cíle pro administraci. */
function cil_popis(string $cil): string
{
    if (preg_match('/^post:(\d+)$/', $cil, $m)) {
        $id = (int) $m[1];
        $t  = get_the_title($id);
        return $t !== '' ? $t : '#' . $id;
    }
    return $cil;
}

// ================================================================= pravidla

/**
 * Uloží pravidlo. $p: zdroj, cil, kod, poznamka, puvod, aktivni.
 * $prepsat: když zdroj už existuje, přepíše ho (import, automatika), jinak chyba.
 *
 * @return int|\WP_Error ID pravidla
 */
function ulozit(array $p, int $id = 0, bool $prepsat = false)
{
    global $wpdb;
    if (!db_pripravena()) {
        db_instalace();
    }
    $zdroj = cesta((string) ($p['zdroj'] ?? ''));
    if ($zdroj === '' || zakazany_zdroj($zdroj)) {
        return new \WP_Error('zdroj', __('Neplatná stará adresa (nesmí být prázdná, úvodní stránka ani administrace).', 'marmal-breakdance-plus'));
    }
    $kod = (int) ($p['kod'] ?? 301);
    if (!in_array($kod, KODY, true)) {
        $kod = 301;
    }
    $cil = $kod === 410 ? '' : sanitizovat_cil((string) ($p['cil'] ?? ''));
    if (!empty($p['na_stranku'])) {
        $cil = cil_na_stranku($cil);
    }
    if ($kod !== 410 && $cil === '') {
        return new \WP_Error('cil', __('Chybí nebo je neplatná nová adresa.', 'marmal-breakdance-plus'));
    }
    if ($kod !== 410 && je_interni($cil)) {
        $url      = cil_url($cil);
        $cilCesta = cesta($url !== '' ? $url : $cil);
        $cilCesta = je_sekce($cilCesta) ? substr($cilCesta, 0, -2) : $cilCesta;
        $smycka   = $cilCesta === $zdroj
            || (je_sekce($zdroj) && strpos($cilCesta . '/', substr($zdroj, 0, -1)) === 0);
        if ($smycka) {
            return new \WP_Error('smycka', __('Nová adresa vede zpět na starou – vznikla by smyčka.', 'marmal-breakdance-plus'));
        }
    }
    $radek = array(
        'zdroj'      => $zdroj,
        'zdroj_hash' => md5($zdroj),
        'sekce'      => je_sekce($zdroj) ? 1 : 0,
        'cil'        => $cil,
        'kod'        => $kod,
        'aktivni'    => isset($p['aktivni']) ? (empty($p['aktivni']) ? 0 : 1) : 1,
        'puvod'      => in_array((string) ($p['puvod'] ?? ''), array('rucne', 'auto', 'import', 'stary-web', '404'), true) ? (string) $p['puvod'] : 'rucne',
        'poznamka'   => mb_substr(sanitize_text_field((string) ($p['poznamka'] ?? '')), 0, 200, 'UTF-8'),
    );
    $existujici = (int) $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . t_pravidla() . ' WHERE zdroj_hash = %s', $radek['zdroj_hash'])); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    if ($existujici && $existujici !== $id) {
        if (!$prepsat) {
            return new \WP_Error('duplicita', __('Pro tuto starou adresu už přesměrování existuje – uprav ho v seznamu.', 'marmal-breakdance-plus'));
        }
        $id = $existujici;
    }
    if ($id) {
        $wpdb->update(t_pravidla(), $radek, array('id' => $id));
    } else {
        $pocet = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . t_pravidla()); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        if ($pocet >= MAX_PRAVIDEL) {
            return new \WP_Error('limit', __('Dosažen nejvyšší počet přesměrování.', 'marmal-breakdance-plus'));
        }
        $radek['vytvoreno'] = ted();
        $wpdb->insert(t_pravidla(), $radek);
        $id = (int) $wpdb->insert_id;
    }
    // Stará adresa už není chyba 404.
    $wpdb->delete(t_404(), array('cesta_hash' => md5($zdroj)));
    obnovit_sekce();
    return $id;
}

function smazat(int $id): void
{
    global $wpdb;
    $wpdb->delete(t_pravidla(), array('id' => $id));
    obnovit_sekce();
}

function prepnout(int $id): void
{
    global $wpdb;
    $wpdb->query($wpdb->prepare('UPDATE ' . t_pravidla() . ' SET aktivni = 1 - aktivni WHERE id = %d', $id)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    obnovit_sekce();
}

function pravidlo(int $id): ?array
{
    global $wpdb;
    $r = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . t_pravidla() . ' WHERE id = %d', $id), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    return is_array($r) ? $r : null;
}

/** Krátký seznam aktivních sekcí (zdroj „/x/*“) do autoload option – na webu se nemusí číst tabulka. */
function obnovit_sekce(): void
{
    global $wpdb;
    $rows  = (array) $wpdb->get_results('SELECT id, zdroj FROM ' . t_pravidla() . ' WHERE sekce = 1 AND aktivni = 1', ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $sekce = array();
    foreach ($rows as $r) {
        $sekce[(string) $r['zdroj']] = (int) $r['id'];
    }
    // Nejdelší (nejkonkrétnější) sekce první.
    uksort($sekce, function ($a, $b) {
        return strlen($b) <=> strlen($a);
    });
    $pocet = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . t_pravidla() . ' WHERE aktivni = 1'); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    update_option(OPT_SEKCE, array('pocet' => $pocet, 'sekce' => $sekce), true);
}

/** Pravidlo pro cestu požadavku (přesná adresa má přednost před sekcí), null = žádné. */
function najit(string $cesta): ?array
{
    global $wpdb;
    $cache = get_option(OPT_SEKCE, array());
    if (is_array($cache) && isset($cache['pocet']) && (int) $cache['pocet'] === 0) {
        return null;
    }
    $r = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . t_pravidla() . ' WHERE zdroj_hash = %s AND aktivni = 1', md5($cesta)), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    if (is_array($r)) {
        return $r;
    }
    foreach ((array) ($cache['sekce'] ?? array()) as $zdroj => $id) {
        $prefix = substr((string) $zdroj, 0, -1); // „/blog/“ – platí pro „/blog“ i vše pod ní
        if (strpos($cesta . '/', $prefix) === 0) {
            return pravidlo((int) $id);
        }
    }
    return null;
}

/** Cílová adresa pro požadavek (sekce: zbytek cesty se doplní za cíl nebo místo *). */
function cil_pro(array $pravidlo, string $cesta, string $dotaz = ''): string
{
    $cil = (string) $pravidlo['cil'];
    $url = cil_url($cil);
    if ($url === '') {
        return '';
    }
    if (!empty($pravidlo['sekce'])) {
        $prefix = substr((string) $pravidlo['zdroj'], 0, -1);
        $zbytek = (string) substr($cesta . '/', strlen($prefix));
        $zbytek = rtrim($zbytek, '/');
        $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        if (strpos($url, '*') !== false) {
            $url = str_replace('*', $zbytek, $url);
        }
        $url = (string) preg_replace('#(?<!:)/{2,}#', '/', $url);
        // Zbytek adresy nesmí změnit doménu cíle (např. cíl „https://web.cz*“ + „@jiny.cz“).
        if (strtolower((string) wp_parse_url($url, PHP_URL_HOST)) !== $host || preg_match('#^https?://[^/]*@#i', $url)) {
            return '';
        }
    }
    if ($dotaz !== '' && nastaveni()['parametry'] && strpos($url, '?') === false) {
        $url .= '?' . $dotaz;
    }
    return $url;
}

function zasah(int $id): void
{
    global $wpdb;
    $wpdb->query($wpdb->prepare('UPDATE ' . t_pravidla() . ' SET zasahy = zasahy + 1, posledni = %s WHERE id = %d', ted(), $id)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * Seznam pravidel pro administraci.
 * @return array{0: array, 1: int}
 */
function seznam(string $hledat = '', int $strana = 1, int $naStranu = 50, string $razeni = 'vytvoreno'): array
{
    global $wpdb;
    $where = '';
    $args  = array();
    if ($hledat !== '') {
        $like  = '%' . $wpdb->esc_like($hledat) . '%';
        $where = 'WHERE zdroj LIKE %s OR cil LIKE %s OR poznamka LIKE %s';
        $args  = array($like, $like, $like);
    }
    $order = array('vytvoreno' => 'id DESC', 'zasahy' => 'zasahy DESC, id DESC', 'zdroj' => 'zdroj ASC')[$razeni] ?? 'id DESC';
    $sqlPocet = 'SELECT COUNT(*) FROM ' . t_pravidla() . ' ' . $where;
    $celkem   = (int) ($args ? $wpdb->get_var($wpdb->prepare($sqlPocet, $args)) : $wpdb->get_var($sqlPocet)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $sql      = 'SELECT * FROM ' . t_pravidla() . ' ' . $where . ' ORDER BY ' . $order . ' LIMIT %d OFFSET %d';
    $rows     = $wpdb->get_results($wpdb->prepare($sql, array_merge($args, array($naStranu, max(0, ($strana - 1) * $naStranu)))), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    return array(is_array($rows) ? $rows : array(), $celkem);
}

// ================================================================= chyby 404

/** Robot podle hlavičky prohlížeče – ukládá se jen ano/ne, ne hlavička. */
function je_robot(string $ua): bool
{
    return (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|bingpreview|semrush|ahrefs|mj12|yandex|baidu|python|curl|wget|httpclient/i', $ua);
}

/** Odkud (referer) zkrácené na doménu + cestu – bez parametrů (mohou obsahovat osobní údaje). */
function odkud(string $referer): string
{
    if ($referer === '') {
        return '';
    }
    $host = (string) wp_parse_url($referer, PHP_URL_HOST);
    $path = (string) wp_parse_url($referer, PHP_URL_PATH);
    if ($host === '') {
        return '';
    }
    return mb_substr(sanitize_text_field($host . $path), 0, 300, 'UTF-8');
}

/** Adresy, které se do 404 nezapisují (skenery zranitelností, soubory WordPressu). */
function ignorovat_404(string $cesta): bool
{
    return (bool) preg_match('#(\.php|\.asp|\.env|\.git|/wp-(admin|includes|content/plugins)|/cgi-bin|/\.well-known|xmlrpc|/feed$)#i', $cesta);
}

function zapsat_404(string $cesta, string $referer = '', bool $robot = false): void
{
    global $wpdb;
    if (!db_pripravena() || $cesta === '' || ignorovat_404($cesta)) {
        return;
    }
    $hash = md5($cesta);
    $upd  = $wpdb->query($wpdb->prepare('UPDATE ' . t_404() . ' SET pocet = pocet + 1, posledni = %s WHERE cesta_hash = %s', ted(), $hash)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    if ($upd) {
        return;
    }
    $pocet = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . t_404()); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    if ($pocet >= MAX_404) {
        // Místo pro nový záznam: smaže se nejstarší s nejmenším počtem.
        $wpdb->query('DELETE FROM ' . t_404() . ' ORDER BY pocet ASC, posledni ASC LIMIT 50'); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }
    $wpdb->insert(t_404(), array(
        'cesta'      => $cesta,
        'cesta_hash' => $hash,
        'pocet'      => 1,
        'robot'      => $robot ? 1 : 0,
        'odkud'      => odkud($referer),
        'prvni'      => ted(),
        'posledni'   => ted(),
    ));
}

/** @return array{0: array, 1: int} */
function seznam_404(string $hledat = '', int $strana = 1, int $naStranu = 50, bool $roboti = true): array
{
    global $wpdb;
    $podminky = array();
    $args     = array();
    if ($hledat !== '') {
        $podminky[] = 'cesta LIKE %s';
        $args[]     = '%' . $wpdb->esc_like($hledat) . '%';
    }
    if (!$roboti) {
        $podminky[] = 'robot = 0';
    }
    $where  = $podminky ? 'WHERE ' . implode(' AND ', $podminky) : '';
    $sqlP   = 'SELECT COUNT(*) FROM ' . t_404() . ' ' . $where;
    $celkem = (int) ($args ? $wpdb->get_var($wpdb->prepare($sqlP, $args)) : $wpdb->get_var($sqlP)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $sql    = 'SELECT * FROM ' . t_404() . ' ' . $where . ' ORDER BY pocet DESC, posledni DESC LIMIT %d OFFSET %d';
    $rows   = $wpdb->get_results($wpdb->prepare($sql, array_merge($args, array($naStranu, max(0, ($strana - 1) * $naStranu)))), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    return array(is_array($rows) ? $rows : array(), $celkem);
}

function smazat_404(int $id): void
{
    global $wpdb;
    if ($id > 0) {
        $wpdb->delete(t_404(), array('id' => $id));
    }
}

function vymazat_404(): void
{
    global $wpdb;
    $wpdb->query('DELETE FROM ' . t_404()); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

/** Denní úklid starých záznamů 404. */
function uklid_404(): void
{
    global $wpdb;
    if (!db_pripravena()) {
        return;
    }
    $hranice = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS * (int) nastaveni()['dni404']);
    $wpdb->query($wpdb->prepare('DELETE FROM ' . t_404() . ' WHERE posledni < %s', $hranice)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

add_action('marmal_presmerovani_uklid', __NAMESPACE__ . '\uklid_404');
add_action('init', function () {
    if (!wp_next_scheduled('marmal_presmerovani_uklid')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'marmal_presmerovani_uklid');
    }
});
