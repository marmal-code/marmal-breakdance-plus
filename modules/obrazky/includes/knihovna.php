<?php
/**
 * Modul Obrázky – B) hromadný převod existující knihovny.
 *
 * Průběh (fronta v OPTION_FRONTA, jeden krok = jeden AJAX požadavek s limitem ~20 s):
 *   prevod    – záloha souborů přílohy → WebP → nové metadata → záznam do mapy URL
 *   prepis    – _breakdance_data (mediální objekty znovu z metadat, ostatní řetězce mapou),
 *               post_content, Rank Math, globální volby Breakdance; před změnou záloha do souboru
 *   css       – regenerace CSS Breakdance (globální + změněné příspěvky), vymazání stránkové cache
 *   kontrola  – existence nových souborů, zbylé odkazy na staré soubory
 * Vrácení jde stejnou frontou (vraceni → prepis → css → mazani). Staré soubory zůstávají na místě
 * (revize, místa, která modul nezná) – maže je až ruční „Smazat staré soubory“ po 14 dnech.
 */

namespace MarmalElements\Obrazky;

if (!defined('ABSPATH')) {
    exit;
}

const CAS_KROKU   = 20;  // s na jeden AJAX požadavek (sdílený hosting, proxy)
const NOVA_POLOZKA = 13; // s – po této době už nezačínat další přílohu (jedna trvá 1–5 s)

// ================================================================= zdroje odkazů

/** Stavy příspěvků, jejichž odkazy se při inventuře počítají jako „používané“. */
function stavy_inventury(): array
{
    return array('publish', 'future', 'private', 'draft', 'pending');
}

/** SQL podmínka „žádné revize, koš ani automatické koncepty“ (bez uživatelských hodnot). */
function sql_platne_prispevky(string $alias = 'p'): string
{
    // Revize (i vlastní typy revizí) mají stav inherit – vynechá je podmínka na stav.
    return "{$alias}.post_type <> 'revision' AND {$alias}.post_status NOT IN ('trash','auto-draft','inherit')";
}

/**
 * ID příspěvků s daty Breakdance (stránky, šablony, hlavičky, globální bloky, popupy).
 * @param string[]|null $stavy null = všechny platné stavy
 * @return int[]
 */
function prispevky_breakdance(?array $stavy = null): array
{
    global $wpdb;
    $sql = "SELECT DISTINCT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_breakdance_data' WHERE " . sql_platne_prispevky();
    if ($stavy) {
        $sql .= ' AND p.post_status IN (' . implode(',', array_fill(0, count($stavy), '%s')) . ')';
        $sql  = $wpdb->prepare($sql, $stavy); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholdery doplněné výše
    }
    return array_map('intval', (array) $wpdb->get_col($sql)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
}

/**
 * ID příspěvků, jejichž post_content obsahuje cestu do uploads.
 * @return int[]
 */
function prispevky_s_obsahem(?array $stavy = null): array
{
    global $wpdb;
    list(, , $cesta) = uploads();
    $like  = '%' . $wpdb->esc_like($cesta) . '%';
    $like2 = '%' . $wpdb->esc_like(str_replace('/', '\/', $cesta)) . '%';
    $sql   = "SELECT p.ID FROM {$wpdb->posts} p WHERE " . sql_platne_prispevky() . ' AND (p.post_content LIKE %s OR p.post_content LIKE %s)';
    $arg   = array($like, $like2);
    if ($stavy) {
        $sql .= ' AND p.post_status IN (' . implode(',', array_fill(0, count($stavy), '%s')) . ')';
        $arg  = array_merge($arg, $stavy);
    }
    return array_map('intval', (array) $wpdb->get_col($wpdb->prepare($sql, $arg))); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
}

/** Meta klíče s adresou obrázku (Rank Math OG a Twitter). */
function meta_klice_url(): array
{
    return array('rank_math_facebook_image', 'rank_math_twitter_image');
}

/** Meta klíče s ID obrázku. */
function meta_klice_id(): array
{
    return array('_thumbnail_id', 'rank_math_facebook_image_id', 'rank_math_twitter_image_id');
}

/** @return int[] příspěvky s Rank Math obrázkem (adresou) */
function prispevky_s_meta_url(): array
{
    global $wpdb;
    $klice = meta_klice_url();
    $sql   = "SELECT DISTINCT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID WHERE " . sql_platne_prispevky()
        . ' AND m.meta_key IN (' . implode(',', array_fill(0, count($klice), '%s')) . ") AND m.meta_value <> ''";
    return array_map('intval', (array) $wpdb->get_col($wpdb->prepare($sql, $klice))); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
}

/** Všechny příspěvky, kde může být odkaz na obrázek (pro přepis a vrácení). @return int[] */
function seznam_k_prepisu(): array
{
    $ids = array_unique(array_merge(prispevky_breakdance(), prispevky_s_obsahem(), prispevky_s_meta_url()));
    sort($ids);
    return array_values($ids);
}

/**
 * Cesty k obrázkům v uploads, které text obsahuje (relativně k uploads, např. 2026/03/foto.png).
 * Lomítka \/ z JSON se nejdřív normalizují. Porovnávají se celé názvy souborů – „campanus1.jpg“
 * nikdy nenajde „campanus10.jpg“.
 * @return string[]
 */
function cesty_v_textu(string $text): array
{
    list(, , $cesta) = uploads();
    if ($text === '') {
        return array();
    }
    $text = str_replace('\/', '/', $text);
    if (strpos($text, $cesta) === false) {
        return array();
    }
    $vzor = '#' . preg_quote($cesta, '#') . '([^\s"\'<>()\\\\,;?\#]+?\.(?:jpe?g|png|gif|webp|avif))(?=[\s"\'<>()\\\\,;?\#]|$)#i';
    if (!preg_match_all($vzor, $text, $m)) {
        return array();
    }
    return array_values(array_unique(array_map('rawurldecode', $m[1])));
}

/**
 * Index použití: kde se který soubor a které ID přílohy objevuje.
 * @return array{cesty:array<string,int[]>,id:array<int,int[]>}
 */
function index_pouziti(?array $stavy): array
{
    global $wpdb;
    $cesty = array();
    $id    = array();
    $pridat_cesty = function (string $text, int $post_id) use (&$cesty) {
        foreach (cesty_v_textu($text) as $c) {
            if (!isset($cesty[$c])) {
                $cesty[$c] = array();
            }
            if (count($cesty[$c]) < 10 && !in_array($post_id, $cesty[$c], true)) {
                $cesty[$c][] = $post_id;
            }
        }
    };

    // 1) Data Breakdance – po dávkách, ať se do paměti nenačte celý web najednou.
    foreach (array_chunk(prispevky_breakdance($stavy), 50) as $davka) {
        $sql = "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_breakdance_data' AND post_id IN (" . implode(',', array_map('intval', $davka)) . ')';
        foreach ((array) $wpdb->get_results($sql) as $r) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery -- jen celá čísla
            $pridat_cesty((string) $r->meta_value, (int) $r->post_id);
            // Mediální objekty: {"id":123,"filename":… – ID jako pojistka k cestě (uzly stromu mají "id" bez "filename").
            if (preg_match_all('#\{\\\\?"id\\\\?":(\d+),\\\\?"filename\\\\?"#', (string) $r->meta_value, $m)) {
                foreach ($m[1] as $aid) {
                    $id[(int) $aid][] = (int) $r->post_id;
                }
            }
        }
    }
    // 2) Obsah příspěvků (Gutenberg, klasický editor).
    foreach (array_chunk(prispevky_s_obsahem($stavy), 100) as $davka) {
        $sql = "SELECT ID, post_content FROM {$wpdb->posts} WHERE ID IN (" . implode(',', array_map('intval', $davka)) . ')';
        foreach ((array) $wpdb->get_results($sql) as $r) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery -- jen celá čísla
            $pridat_cesty((string) $r->post_content, (int) $r->ID);
        }
    }
    // 3) Meta: Rank Math adresy a ID (náhledový obrázek, OG, Twitter).
    $stav_sql = '';
    $arg      = array_merge(meta_klice_url(), meta_klice_id());
    if ($stavy) {
        $stav_sql = ' AND p.post_status IN (' . implode(',', array_fill(0, count($stavy), '%s')) . ')';
        $arg      = array_merge($arg, $stavy);
    }
    $sql = "SELECT m.post_id, m.meta_key, m.meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE " . sql_platne_prispevky()
        . ' AND m.meta_key IN (' . implode(',', array_fill(0, count(meta_klice_url()) + count(meta_klice_id()), '%s')) . ')' . $stav_sql;
    foreach ((array) $wpdb->get_results($wpdb->prepare($sql, $arg)) as $r) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
        if (in_array($r->meta_key, meta_klice_id(), true)) {
            if ((int) $r->meta_value > 0) {
                $id[(int) $r->meta_value][] = (int) $r->post_id;
            }
        } else {
            $pridat_cesty((string) $r->meta_value, (int) $r->post_id);
        }
    }
    return array('cesty' => $cesty, 'id' => $id);
}

/** ID příloh, které se nepřevádějí: ikona webu, logo (Customizer, Rank Math). @return int[] */
function vynechane_prilohy(): array
{
    $ids   = array((int) get_option('site_icon'), (int) get_theme_mod('custom_logo'));
    $rm    = get_option('rank-math-options-titles');
    if (is_array($rm)) {
        $ids[] = (int) ($rm['knowledgegraph_logo_id'] ?? 0);
        if (!empty($rm['knowledgegraph_logo']) && is_string($rm['knowledgegraph_logo'])) {
            $ids[] = (int) attachment_url_to_postid($rm['knowledgegraph_logo']);
        }
    }
    return array_values(array_filter(array_unique($ids)));
}

/**
 * Soubory přílohy relativně k uploads: hlavní, original_image, zmenšeniny.
 * @return array{hlavni:string,original:string,velikosti:array<string,string>,vse:string[]}
 */
function soubory_prilohy(int $id, ?array $meta = null, ?string $attached = null): array
{
    $meta     = $meta ?? wp_get_attachment_metadata($id);
    $meta     = is_array($meta) ? $meta : array();
    $attached = $attached ?? (string) get_post_meta($id, '_wp_attached_file', true);
    $hlavni   = ltrim(wp_normalize_path($attached), '/');
    if ($hlavni !== '' && path_is_absolute($attached)) {
        $hlavni = rel($attached);
    }
    $slozka   = dirname($hlavni);
    $slozka   = ($slozka === '.' || $slozka === '') ? '' : $slozka . '/';
    $original = !empty($meta['original_image']) ? $slozka . wp_basename((string) $meta['original_image']) : '';
    $vel      = array();
    foreach ((array) ($meta['sizes'] ?? array()) as $nazev => $v) {
        if (!empty($v['file'])) {
            $vel[(string) $nazev] = $slozka . wp_basename((string) $v['file']);
        }
    }
    $vse = array_values(array_unique(array_filter(array_merge(array($hlavni, $original), array_values($vel)))));
    return array('hlavni' => $hlavni, 'original' => $original, 'velikosti' => $vel, 'vse' => $vse);
}

/**
 * Inventura (jen čtení): přehled formátů, kandidáti na převod, největší používané soubory.
 * Výsledek se uloží do OPTION_PREHLED.
 */
function inventura(): array
{
    global $wpdb;
    if (function_exists('set_time_limit')) {
        @set_time_limit(120); // phpcs:ignore WordPress.PHP.NoSilencedErrors
    }
    $s        = nastaveni();
    $index    = index_pouziti(stavy_inventury());
    $vynechat = vynechane_prilohy();

    $prilohy = (array) $wpdb->get_results("SELECT ID, post_mime_type FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    foreach (array_chunk(wp_list_pluck($prilohy, 'ID'), 200) as $davka) {
        update_meta_cache('post', array_map('intval', $davka)); // metadata najednou, ne dotaz na každou přílohu
    }
    $formaty    = array();
    $kandidati  = array();
    $pouzite    = array();
    $objem_k    = 0;
    $objem_vse  = 0;
    $odhad      = 0;
    $preskoceno = array('logo' => 0, 'prah' => 0, 'nepouzito' => 0, 'chybi' => 0);

    foreach ($prilohy as $p) {
        $aid  = (int) $p->ID;
        $mime = (string) $p->post_mime_type;
        $meta = wp_get_attachment_metadata($aid);
        $f    = soubory_prilohy($aid, is_array($meta) ? $meta : array());
        $abs  = $f['hlavni'] !== '' ? abs_cesta($f['hlavni']) : '';
        $vel  = (is_array($meta) && !empty($meta['filesize'])) ? (int) $meta['filesize'] : (($abs && is_file($abs)) ? (int) filesize($abs) : 0);

        if (!isset($formaty[$mime])) {
            $formaty[$mime] = array('pocet' => 0, 'objem' => 0);
        }
        $formaty[$mime]['pocet']++;
        $formaty[$mime]['objem'] += $vel;

        $kde = isset($index['id'][$aid]) ? $index['id'][$aid] : array();
        foreach ($f['vse'] as $c) {
            if (isset($index['cesty'][$c])) {
                $kde = array_merge($kde, $index['cesty'][$c]);
            }
        }
        $kde = array_values(array_unique($kde));
        if (!$kde) {
            if (prevadet_mime($mime)) {
                $preskoceno['nepouzito']++;
            }
            continue;
        }
        $pouzite[] = array('id' => $aid, 'velikost' => $vel, 'mime' => $mime, 'kde' => array_slice($kde, 0, 5));

        if (!prevadet_mime($mime)) {
            continue;
        }
        if (in_array($aid, $vynechat, true)) {
            $preskoceno['logo']++;
            continue;
        }
        if (!$abs || !is_file($abs)) {
            $preskoceno['chybi']++;
            continue;
        }
        $prah = ($mime === 'image/png' ? $s['prah_png'] : $s['prah_jpg']) * 1024;
        if ($vel <= $prah) {
            $preskoceno['prah']++;
            continue;
        }
        $kandidati[] = $aid;
        $objem_k    += $vel;
        foreach ($f['vse'] as $c) {
            $a = abs_cesta($c);
            $objem_vse += is_file($a) ? (int) filesize($a) : 0;
        }
        // Odhad podle ručního převodu na ticha-trida.cz: PNG fotky −85 %, JPG −65 %.
        $odhad += (int) round($vel * ($mime === 'image/png' ? 0.15 : 0.35));
    }

    usort($pouzite, function ($a, $b) {
        return $b['velikost'] <=> $a['velikost'];
    });

    $vysledek = array(
        'v'          => FORMAT,
        'cas'        => time(),
        'formaty'    => $formaty,
        'kandidati'  => $kandidati,
        'objem'      => $objem_k,
        'objem_vse'  => $objem_vse,
        'odhad_po'   => $odhad,
        'nejvetsi'   => array_slice($pouzite, 0, 20),
        'pouzito'    => count($pouzite),
        'preskoceno' => $preskoceno,
    );
    update_option(OPTION_PREHLED, $vysledek, false);
    return $vysledek;
}

function prehled(): array
{
    $p = get_option(OPTION_PREHLED, array());
    return is_array($p) ? $p : array();
}

// ================================================================= zámek

/** Atomický zámek přes add_option (dvě okna nesmí zpracovávat frontu najednou). */
function zamknout(): bool
{
    if (add_option(OPTION_ZAMEK, time(), '', false)) {
        return true;
    }
    $cas = (int) get_option(OPTION_ZAMEK);
    if ($cas && $cas < time() - 3 * CAS_KROKU) { // zámek po spadlém požadavku
        delete_option(OPTION_ZAMEK);
        return add_option(OPTION_ZAMEK, time(), '', false);
    }
    return false;
}

function odemknout(): void
{
    delete_option(OPTION_ZAMEK);
}

// ================================================================= záloha

/** Kořen záloh – s index.php a .htaccess (na nginx chrání hlavně náhodný název složky dávky). */
function koren_zaloh(): string
{
    list($base) = uploads();
    $koren = $base . SLOZKA_ZALOHY . '/';
    if (!is_dir($koren)) {
        wp_mkdir_p($koren);
    }
    if (!is_file($koren . 'index.php')) {
        file_put_contents($koren . 'index.php', "<?php\n// Silence is golden.\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions
    }
    if (!is_file($koren . '.htaccess')) {
        file_put_contents($koren . '.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions
    }
    return $koren;
}

/** Zkopíruje soubor do zálohy dávky se zachováním cesty (rok/měsíc/soubor). */
function zalohovat_soubor(string $rel, string $slozka_davky): bool
{
    $zdroj = abs_cesta($rel);
    $cil   = abs_cesta($slozka_davky . '/' . $rel);
    if (!v_uploads($zdroj) || !is_file($zdroj)) {
        return false;
    }
    if (is_file($cil) && filesize($cil) === filesize($zdroj)) {
        return true; // už zálohováno (opakovaný pokus)
    }
    if (!wp_mkdir_p(dirname($cil))) {
        return false;
    }
    return @copy($zdroj, $cil) && filesize($cil) === filesize($zdroj); // phpcs:ignore WordPress.PHP.NoSilencedErrors
}

/** Zapíše textovou zálohu (data stránky, obsah, volba). Existující soubor nepřepisuje – drží originál. */
function zalohovat_text(string $slozka_davky, string $nazev, string $obsah): bool
{
    $cil = abs_cesta($slozka_davky . '/' . $nazev);
    if (is_file($cil)) {
        return true;
    }
    if (!wp_mkdir_p(dirname($cil))) {
        return false;
    }
    return file_put_contents($cil, $obsah, LOCK_EX) === strlen($obsah); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

function nacist_text(string $slozka_davky, string $nazev): ?string
{
    $cil = abs_cesta($slozka_davky . '/' . $nazev);
    if (!v_uploads($cil) || !is_file($cil)) {
        return null;
    }
    $t = file_get_contents($cil); // phpcs:ignore WordPress.WP.AlternativeFunctions
    return is_string($t) ? $t : null;
}

// ================================================================= převod jedné přílohy

/** Nastaví soubor, MIME a guid přílohy přímo (bez save_post hooků jiných pluginů). */
function nastavit_prilohu(int $id, string $attached_rel, string $mime, string $guid): void
{
    global $wpdb;
    update_post_meta($id, '_wp_attached_file', $attached_rel);
    $wpdb->update($wpdb->posts, array('post_mime_type' => $mime, 'guid' => $guid), array('ID' => $id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    clean_post_cache($id);
}

/**
 * Převede jednu přílohu do WebP.
 * @return array{stav:string,pred?:int,po?:int}|\WP_Error stav: ok | preskoceno
 */
function prevest_prilohu(int $id, string $davka_id)
{
    $post = get_post($id);
    if (!$post || $post->post_type !== 'attachment') {
        return array('stav' => 'preskoceno');
    }
    $z     = zaloha();
    $davka = $z['davky'][$davka_id] ?? null;
    if (!$davka) {
        return new \WP_Error('marmal_obrazky', 'Dávka neexistuje.');
    }
    $zaznam = $z['prilohy'][$id] ?? null;
    if ($zaznam && ($zaznam['stav'] ?? '') === 'prevedeno') {
        return array('stav' => 'preskoceno');
    }
    // Požadavek spadl uprostřed (po přepnutí souboru, před metadaty) → vrátit přílohu a začít znovu.
    if ($zaznam && ($zaznam['stav'] ?? '') === 'rozpracovano') {
        obnovit_rozpracovanou($id);
        $post   = get_post($id);
        $z      = zaloha();
        $zaznam = $z['prilohy'][$id] ?? null;
    }
    // Idempotence: už WebP / AVIF (nebo jiný formát) → nic.
    if (!prevadet_mime((string) $post->post_mime_type)) {
        return array('stav' => 'preskoceno');
    }

    $meta     = wp_get_attachment_metadata($id);
    $meta     = is_array($meta) ? $meta : array();
    $attached = (string) get_post_meta($id, '_wp_attached_file', true);
    $f        = soubory_prilohy($id, $meta, $attached);
    $hlavni   = abs_cesta($f['hlavni']);
    if ($f['hlavni'] === '' || !is_file($hlavni) || !v_uploads($hlavni)) {
        return new \WP_Error('marmal_obrazky', sprintf('Příloha %d: chybí soubor %s.', $id, $f['hlavni']));
    }

    // 1) Záloha všech souborů + evidence (jen poprvé – při opakovaném pokusu zůstává původní).
    if (!$zaznam || ($zaznam['davka'] ?? '') !== $davka_id) {
        foreach ($f['vse'] as $rel) {
            if (is_file(abs_cesta($rel)) && !zalohovat_soubor($rel, $davka['slozka'])) {
                return new \WP_Error('marmal_obrazky', sprintf('Příloha %d: nepodařilo se zálohovat %s (místo na disku, práva).', $id, $rel));
            }
        }
        $zaznam = array(
            'davka'         => $davka_id,
            'stav'          => 'rozpracovano',
            'soubor'        => $attached,
            'meta'          => $meta,
            'mime'          => (string) $post->post_mime_type,
            'guid'          => (string) $post->guid,
            'soubory_stare' => array_values(array_filter($f['vse'], function ($rel) {
                return is_file(abs_cesta($rel));
            })),
            'velikost_pred' => (int) filesize($hlavni),
            'novy'          => '',
            'cas'           => time(),
        );
    }

    // 2) Název: základ bez -scaled / -rotated + .webp; při kolizi -w, -w2…
    if (empty($zaznam['novy'])) {
        $slozka = dirname($f['hlavni']);
        $slozka = ($slozka === '.' || $slozka === '') ? '' : $slozka . '/';
        $zaklad = preg_replace('/-(scaled|rotated)$/', '', pathinfo($f['hlavni'], PATHINFO_FILENAME));
        $novy   = $slozka . $zaklad . '.webp';
        $i      = 1;
        while (is_file(abs_cesta($novy))) {
            $novy = $slozka . $zaklad . '-w' . ($i > 1 ? $i : '') . '.webp';
            $i++;
        }
        $zaznam['novy'] = $novy;
    }
    $z['prilohy'][$id] = $zaznam;
    ulozit_zalohu($z);

    // 3) Převod – zdrojem je original_image (nezmenšený originál), jinak hlavní soubor.
    $zdroj = $f['original'] !== '' && is_file(abs_cesta($f['original'])) ? abs_cesta($f['original']) : $hlavni;
    $cil   = abs_cesta($zaznam['novy']);
    $r     = prevest_soubor($zdroj, $cil, 'image/webp', nastaveni());
    if (is_wp_error($r)) {
        if (is_file($cil)) {
            wp_delete_file($cil);
        }
        return $r;
    }
    if ($r['velikost'] >= $zaznam['velikost_pred']) {
        wp_delete_file($r['cesta']);
        unset($z['prilohy'][$id]);
        ulozit_zalohu($z);
        return array('stav' => 'preskoceno', 'duvod' => 'vetsi');
    }

    // 4) Příloha → nový soubor, MIME, guid, metadata (zmenšeniny vzniknou z WebP).
    require_once ABSPATH . 'wp-admin/includes/image.php';
    list(, $baseurl) = uploads();
    nastavit_prilohu($id, $zaznam['novy'], 'image/webp', $baseurl . $zaznam['novy']);
    $nova_meta = wp_generate_attachment_metadata($id, $cil);
    if (empty($nova_meta) || is_wp_error($nova_meta) || empty($nova_meta['file'])) {
        // Vrátit přílohu do původního stavu, nový soubor pryč.
        nastavit_prilohu($id, $attached, $zaznam['mime'], $zaznam['guid']);
        wp_update_attachment_metadata($id, $meta);
        foreach (soubory_prilohy($id, is_array($nova_meta) ? $nova_meta : array(), $zaznam['novy'])['vse'] as $rel) {
            if (!in_array($rel, $zaznam['soubory_stare'], true) && is_file(abs_cesta($rel))) {
                wp_delete_file(abs_cesta($rel));
            }
        }
        return new \WP_Error('marmal_obrazky', sprintf('Příloha %d: nepodařilo se vytvořit zmenšeniny.', $id));
    }
    wp_update_attachment_metadata($id, $nova_meta);

    // 5) Mapa URL: starý hlavní soubor a original_image → nový hlavní, zmenšeniny podle názvu velikosti
    //    (velikost, která nevznikla – typicky 2048x2048 po zmenšení na 1920 px – → nový hlavní).
    //    Zpětná mapa pro vrácení: nový hlavní → starý hlavní, nové zmenšeniny → staré stejného názvu.
    $nove  = soubory_prilohy($id, $nova_meta, $zaznam['novy']);
    $stare = soubory_prilohy($id, $meta, $attached);
    $mapa  = mapa();
    $zpet  = array($nove['hlavni'] => $stare['hlavni']);
    $mapa[$stare['hlavni']] = $nove['hlavni'];
    if ($stare['original'] !== '') {
        $mapa[$stare['original']] = $nove['hlavni'];
    }
    foreach ($stare['velikosti'] as $nazev => $rel) {
        $mapa[$rel] = $nove['velikosti'][$nazev] ?? $nove['hlavni'];
    }
    foreach ($nove['velikosti'] as $nazev => $rel) {
        $zpet[$rel] = $stare['velikosti'][$nazev] ?? $stare['hlavni'];
    }
    foreach ($mapa as $k => $v) {
        if ($k === $v) {
            unset($mapa[$k]);
        }
    }
    ulozit_mapu($mapa);

    $z = zaloha();
    $z['prilohy'][$id] = array_merge($zaznam, array(
        'stav'          => 'prevedeno',
        'soubory_nove'  => $nove['vse'],
        'zpet'          => $zpet,
        'velikost_po'   => (int) $r['velikost'],
        'cas'           => time(),
    ));
    if (!in_array($id, $z['davky'][$davka_id]['prilohy'], true)) {
        $z['davky'][$davka_id]['prilohy'][] = $id;
    }
    ulozit_zalohu($z);

    return array('stav' => 'ok', 'pred' => $zaznam['velikost_pred'], 'po' => (int) $r['velikost']);
}

/**
 * Příloha zůstala rozpracovaná (spadlý požadavek, opakovaná chyba paměti): vrátit soubor, MIME
 * a metadata podle evidence a smazat nový WebP. Původní soubory převod nikdy nemaže.
 */
function obnovit_rozpracovanou(int $id): void
{
    $z = zaloha();
    $p = $z['prilohy'][$id] ?? null;
    if (!$p || ($p['stav'] ?? '') !== 'rozpracovano') {
        return;
    }
    if ((string) get_post_meta($id, '_wp_attached_file', true) !== (string) $p['soubor']) {
        nastavit_prilohu($id, (string) $p['soubor'], (string) $p['mime'], (string) $p['guid']);
        wp_update_attachment_metadata($id, (array) $p['meta']);
    }
    if (!empty($p['novy'])) {
        $zaklad = preg_replace('/\.webp$/i', '', (string) $p['novy']);
        // Bez GLOB_BRACE – na některých serverech (musl) konstanta neexistuje.
        $soubory = array_merge((array) glob(abs_cesta($zaklad) . '.webp'), (array) glob(abs_cesta($zaklad) . '-[0-9]*x[0-9]*.webp'));
        $vzor    = '/^' . preg_quote(wp_basename($zaklad), '/') . '(-\d+x\d+)?\.webp$/i';
        foreach ($soubory as $soubor) {
            if (!preg_match($vzor, wp_basename((string) $soubor))) {
                continue; // jen vlastní soubory, ne foto-2-300x200.webp jiné přílohy
            }
            $rel = rel((string) $soubor);
            if ($rel !== '' && !in_array($rel, (array) $p['soubory_stare'], true) && v_uploads((string) $soubor)) {
                wp_delete_file((string) $soubor);
            }
        }
    }
    unset($z['prilohy'][$id]);
    ulozit_zalohu($z);
}

// ================================================================= přepis odkazů

/**
 * Páry pro strtr: celé cesty od /wp-content/uploads/ (zachytí https, http, // i relativní odkazy)
 * a varianta s \/ (JSON v textu, volby Breakdance).
 * @param array<string,string> $mapa relativní cesty stará => nová
 */
function pary(array $mapa): array
{
    list(, , $cesta) = uploads();
    $p = array();
    foreach ($mapa as $stara => $nova) {
        $p[$cesta . $stara] = $cesta . $nova;
        $p[str_replace('/', '\/', $cesta . $stara)] = str_replace('/', '\/', $cesta . $nova);
    }
    return $p;
}

/** Je pole mediální objekt Breakdance (prepareMedia)? */
function je_media($a): bool
{
    return is_array($a) && isset($a['id']) && is_numeric($a['id']) && (int) $a['id'] > 0
        && isset($a['url']) && is_string($a['url'])
        && (isset($a['sizes']) || isset($a['mime']) || isset($a['filename']));
}

/**
 * Mediální objekt přílohy sestavený znovu z aktuálních metadat – url, filename, mime, sizes, srcset.
 * Přednostně funkcí Breakdance (stejný tvar, jaký ukládá builder), jinak vlastní sestavení.
 * alt a caption zůstávají z původního objektu (v builderu mohou být jiné než v knihovně).
 */
function media_objekt(int $id, array $puvodni): array
{
    $novy = null;
    if (function_exists('\Breakdance\Media\Metadata\prepareMedia')) {
        try {
            $novy = \Breakdance\Media\Metadata\prepareMedia($id);
        } catch (\Throwable $e) {
            $novy = null;
        }
    }
    if (!is_array($novy) || empty($novy['url'])) {
        $meta  = wp_get_attachment_metadata($id);
        $meta  = is_array($meta) ? $meta : array();
        $url   = (string) wp_get_attachment_url($id);
        $sizes = array();
        foreach ((array) ($meta['sizes'] ?? array()) as $nazev => $v) {
            $src = wp_get_attachment_image_src($id, $nazev);
            $w   = (int) ($v['width'] ?? 0);
            $h   = (int) ($v['height'] ?? 0);
            $sizes[$nazev] = array('height' => $h, 'width' => $w, 'url' => $src ? (string) $src[0] : '', 'orientation' => $w >= $h ? 'landscape' : 'portrait');
        }
        $w = (int) ($meta['width'] ?? 0);
        $h = (int) ($meta['height'] ?? 0);
        $sizes['full'] = array('url' => $url, 'height' => $h, 'width' => $w, 'orientation' => $w >= $h ? 'landscape' : 'portrait');
        $novy = array(
            'id'         => $id,
            'filename'   => wp_basename((string) get_attached_file($id)),
            'url'        => $url,
            'alt'        => (string) get_post_meta($id, '_wp_attachment_image_alt', true),
            'caption'    => (string) wp_get_attachment_caption($id),
            'mime'       => (string) get_post_mime_type($id),
            'type'       => 'image',
            'sizes'      => $sizes,
            'attributes' => array(
                'srcset' => (string) wp_get_attachment_image_srcset($id, 'full'),
                'sizes'  => (string) wp_get_attachment_image_sizes($id, 'full'),
            ),
        );
    }
    foreach (array('alt', 'caption') as $k) {
        if (isset($puvodni[$k]) && is_string($puvodni[$k])) {
            $novy[$k] = $puvodni[$k];
        }
    }
    // Neznámé klíče (např. z vlastních elementů) ponechat.
    return $novy + array_diff_key($puvodni, $novy);
}

/**
 * Rekurzivně projde data: mediální objekty převedených příloh sestaví znovu,
 * ostatní řetězce přepíše mapou (pozadí, odkazy v textu, vlastní CSS prvku).
 *
 * @param mixed $uzel
 * @param array<int,true> $ids přílohy, jejichž objekty se sestaví znovu
 * @return mixed
 */
function projit($uzel, array $pary, array $ids, int &$zmen)
{
    if (is_array($uzel)) {
        if (je_media($uzel) && isset($ids[(int) $uzel['id']])) {
            $novy = media_objekt((int) $uzel['id'], $uzel);
            if ($novy != $uzel) { // phpcs:ignore Universal.Operators.StrictComparisons -- porovnání obsahu polí
                $zmen++;
            }
            return $novy;
        }
        foreach ($uzel as $k => $v) {
            $uzel[$k] = projit($v, $pary, $ids, $zmen);
        }
        return $uzel;
    }
    if (is_string($uzel) && $pary && (strpos($uzel, '/') !== false)) {
        $novy = strtr($uzel, $pary);
        if ($novy !== $uzel) {
            $zmen++;
        }
        return $novy;
    }
    return $uzel;
}

/**
 * Přepíše odkazy v jednom příspěvku. Nikdy neuloží částečně upravená data: když nejde dekódovat
 * _breakdance_data, data Breakdance se přeskočí a chyba jde do logu.
 *
 * @param array $ctx ['pary' => …, 'ids' => [id => true], 'slozka' => záloha dávky|'' , 'davka' => id|'']
 * @return array{zmen:int,bd?:array,pc?:array}|\WP_Error
 */
function prepsat_prispevek(int $post_id, array $ctx)
{
    global $wpdb;
    $vysledek = array('zmen' => 0);
    $chyba    = null;

    // 1) Data Breakdance (dvojitě zakódovaný JSON: vnější objekt + tree_json_string).
    $raw = get_post_meta($post_id, '_breakdance_data', true);
    if (is_string($raw) && $raw !== '') {
        $vnejsi = json_decode($raw, true);
        $strom  = is_array($vnejsi) && isset($vnejsi['tree_json_string']) && is_string($vnejsi['tree_json_string'])
            ? json_decode($vnejsi['tree_json_string'], true) : null;
        if (!is_array($vnejsi) || (isset($vnejsi['tree_json_string']) && !is_array($strom))) {
            $chyba = new \WP_Error('marmal_obrazky_json', sprintf('Příspěvek %d: data Breakdance nejdou dekódovat – přeskočeno.', $post_id));
        } else {
            $zmen = 0;
            if (is_array($strom)) {
                $strom = projit($strom, $ctx['pary'], $ctx['ids'], $zmen);
            } elseif (isset($vnejsi['tree']) && is_array($vnejsi['tree'])) {
                $vnejsi['tree'] = projit($vnejsi['tree'], $ctx['pary'], $ctx['ids'], $zmen);
            }
            if ($zmen > 0) {
                if (is_array($strom)) {
                    $vnejsi['tree_json_string'] = wp_json_encode($strom);
                }
                $novy = wp_json_encode($vnejsi);
                if (!is_string($novy) || ($strom !== null && !is_string($vnejsi['tree_json_string']))) {
                    $chyba = new \WP_Error('marmal_obrazky_json', sprintf('Příspěvek %d: data Breakdance nejdou znovu zakódovat – přeskočeno.', $post_id));
                } elseif ($ctx['slozka'] !== '' && !zalohovat_text($ctx['slozka'], 'bd/' . $post_id . '.json', $raw)) {
                    $chyba = new \WP_Error('marmal_obrazky_zaloha', sprintf('Příspěvek %d: nepodařilo se zálohovat data Breakdance – přeskočeno.', $post_id));
                } else {
                    update_post_meta($post_id, '_breakdance_data', wp_slash($novy));
                    $vysledek['zmen'] += $zmen;
                    $vysledek['bd']    = array('soubor' => 'bd/' . $post_id . '.json', 'md5_po' => md5($novy));
                }
            }
        }
    }

    // 2) post_content (Gutenberg, klasický editor) – přímo v tabulce, bez nové revize a hooků.
    $post = get_post($post_id);
    if ($post && $ctx['pary'] && $post->post_content !== '') {
        $obsah = strtr($post->post_content, $ctx['pary']);
        if ($obsah !== $post->post_content) {
            if ($ctx['slozka'] === '' || zalohovat_text($ctx['slozka'], 'pc/' . $post_id . '.html', $post->post_content)) {
                $wpdb->update($wpdb->posts, array('post_content' => $obsah), array('ID' => $post_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                clean_post_cache($post_id);
                $vysledek['zmen']++;
                $vysledek['pc'] = array('soubor' => 'pc/' . $post_id . '.html', 'md5_po' => md5($obsah));
            }
        }
    }

    // 3) Rank Math OG / Twitter obrázek.
    foreach (meta_klice_url() as $klic) {
        $v = get_post_meta($post_id, $klic, true);
        if (is_string($v) && $v !== '' && $ctx['pary']) {
            $n = strtr($v, $ctx['pary']);
            if ($n !== $v) {
                update_post_meta($post_id, $klic, $n);
                $vysledek['zmen']++;
            }
        }
    }

    if ($chyba) {
        zapsat_log($chyba->get_error_message());
        $vysledek['chyba'] = $chyba->get_error_message();
    }
    return $vysledek;
}

/**
 * Globální volby Breakdance (breakdance%): když obsahují adresy z uploads, přepsat je stejnou mapou.
 * @return array<string,array> název volby => ['soubor' => záloha, 'md5_po' => …]
 */
function prepsat_volby(array $ctx): array
{
    global $wpdb;
    $zmenene = array();
    $nazvy   = (array) $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like('breakdance') . '%')); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    foreach ($nazvy as $nazev) {
        if ($nazev === 'breakdance_license_key' || stripos($nazev, 'license') !== false) {
            continue;
        }
        $hodnota = get_option($nazev);
        $zmen    = 0;
        $nova    = projit($hodnota, $ctx['pary'], $ctx['ids'], $zmen);
        if ($zmen < 1) {
            continue;
        }
        $soubor = 'opt/' . sanitize_file_name($nazev) . '.txt';
        if ($ctx['slozka'] !== '' && !zalohovat_text($ctx['slozka'], $soubor, maybe_serialize($hodnota))) {
            zapsat_log(sprintf('Volbu %s se nepodařilo zálohovat – nepřepsána.', $nazev));
            continue;
        }
        update_option($nazev, $nova);
        $zmenene[$nazev] = array('soubor' => $soubor, 'md5_po' => md5(maybe_serialize($nova)));
    }
    return $zmenene;
}

// ================================================================= CSS a cache

/**
 * Regenerace CSS Breakdance stejnými funkcemi jako Settings → Tools → Regenerate Cache.
 * @return bool false = Breakdance funkce nenabízí (regenerovat ručně)
 */
function regenerovat_css(array $ids, bool $globalni): bool
{
    $ok = true;
    if ($globalni) {
        if (function_exists('\Breakdance\Data\regenerate_global_settings_cache')) {
            try {
                \Breakdance\Data\regenerate_global_settings_cache();
            } catch (\Throwable $e) {
                zapsat_log('Regenerace globálního CSS Breakdance selhala: ' . $e->getMessage());
            }
        } else {
            $ok = false;
        }
    }
    if (!function_exists('\Breakdance\Render\generateCacheForPost')) {
        return $ids ? false : $ok;
    }
    foreach ($ids as $id) {
        $id = (int) $id;
        try {
            if (function_exists('\Breakdance\Util\Timing\withoutTiming')) {
                \Breakdance\Util\Timing\withoutTiming(function () use ($id) {
                    return \Breakdance\Render\generateCacheForPost($id);
                });
            } else {
                \Breakdance\Render\generateCacheForPost($id);
            }
        } catch (\Throwable $e) {
            zapsat_log(sprintf('Regenerace CSS příspěvku %d selhala: %s', $id, $e->getMessage()));
        }
    }
    return $ok;
}

/** Vymaže stránkovou cache známých pluginů (jen funkce / akce, které plugin dokumentuje). */
function vycistit_cache(): void
{
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();                       // WP Super Cache
    }
    do_action('cache_enabler_clear_complete_cache');  // Cache Enabler
    if (function_exists('rocket_clean_domain')) {
        rocket_clean_domain();                        // WP Rocket
    }
    do_action('litespeed_purge_all');                 // LiteSpeed Cache
    if (function_exists('w3tc_flush_all')) {
        w3tc_flush_all();                             // W3 Total Cache
    }
}

// ================================================================= fronta: start, krok, vrácení

/** Údaje pro přepis: páry z mapy a ID převedených příloh. */
function kontext_prevodu(string $slozka): array
{
    $ids = array();
    foreach (zaloha()['prilohy'] as $id => $p) {
        if (($p['stav'] ?? '') === 'prevedeno') {
            $ids[(int) $id] = true;
        }
    }
    return array('pary' => pary(mapa()), 'ids' => $ids, 'slozka' => $slozka);
}

/**
 * Spustí hromadný převod: čerstvá inventura → kandidáti → nová dávka se složkou zálohy.
 * @return array|\WP_Error stav fronty
 */
function zahajit_prevod()
{
    if (rozpracovano()) {
        return new \WP_Error('marmal_obrazky', __('Už běží nedokončená operace – pokračuj v ní.', 'marmal-breakdance-plus'));
    }
    if (cilovy_mime_knihovna() === '') {
        return new \WP_Error('marmal_obrazky', __('Server neumí ukládat WebP.', 'marmal-breakdance-plus'));
    }
    $inv = inventura();
    if (empty($inv['kandidati'])) {
        return new \WP_Error('marmal_obrazky', __('Není co převádět – žádný používaný JPG ani PNG nad nastavenými prahy.', 'marmal-breakdance-plus'));
    }
    // Místo na disku: záloha ≈ velikost převáděných souborů, k tomu nové WebP.
    list($base) = uploads();
    $volno = function_exists('disk_free_space') ? @disk_free_space($base) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
    $treba = (int) ($inv['objem_vse'] * 1.3) + 20 * 1048576;
    if ($volno !== false && $volno < $treba) {
        /* translators: 1: potřebné místo, 2: volné místo */
        return new \WP_Error('marmal_obrazky', sprintf(__('Na disku je málo místa: záloha a nové soubory potřebují asi %1$s, volných je %2$s.', 'marmal-breakdance-plus'), velikost($treba), velikost((int) $volno)));
    }

    koren_zaloh();
    $id     = wp_date('Y-m-d-His');
    $slozka = SLOZKA_ZALOHY . '/' . $id . '-' . strtolower(wp_generate_password(12, false, false));
    if (!wp_mkdir_p(abs_cesta($slozka))) {
        return new \WP_Error('marmal_obrazky', __('Nepodařilo se vytvořit složku zálohy v uploads.', 'marmal-breakdance-plus'));
    }
    file_put_contents(abs_cesta($slozka . '/index.php'), "<?php\n// Silence is golden.\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions

    $z = zaloha();
    $z['davky'][$id] = array(
        'datum'     => time(),
        'slozka'    => $slozka,
        'prilohy'   => array(),
        'prispevky' => array(),
        'volby'     => array(),
        'stav'      => 'bezi',
        'uklizeno'  => false,
        'zaloha'    => true,
    );
    ulozit_zalohu($z);

    ulozit_frontu(array(
        'operace'   => 'prevod',
        'davka'     => $id,
        'faze'      => 'prevod',
        'celkem'    => count($inv['kandidati']),
        'cekajici'  => array_values(array_map('intval', $inv['kandidati'])),
        'pokusy'    => array(),
        'hotove'    => 0,
        'preskocene' => 0,
        'chyby'     => array(),
        'pred'      => 0,
        'po'        => 0,
        'prispevky' => array(),
        'prispevky_celkem' => 0,
        'zmeny'     => array(),
        'css'       => array(),
        'css_globalni' => false,
        'css_rucne' => false,
        'zacatek'   => time(),
        'pozastaveno' => false,
    ));
    return stav_fronty();
}

/**
 * Spustí vrácení: celé dávky (nejnovější první) nebo jedné přílohy.
 * @return array|\WP_Error
 */
function zahajit_vraceni(string $davka_id, int $priloha = 0)
{
    if (rozpracovano()) {
        return new \WP_Error('marmal_obrazky', __('Už běží nedokončená operace – nejdřív ji dokonči.', 'marmal-breakdance-plus'));
    }
    $z = zaloha();
    if ($priloha > 0) {
        $p = $z['prilohy'][$priloha] ?? null;
        if (!$p || ($p['stav'] ?? '') !== 'prevedeno') {
            return new \WP_Error('marmal_obrazky', __('Tato příloha není převedená modulem.', 'marmal-breakdance-plus'));
        }
        if (!muze_vratit($priloha, $p)) {
            return new \WP_Error('marmal_obrazky', __('Původní soubory už nejsou ani na místě, ani v záloze – převod nejde vrátit.', 'marmal-breakdance-plus'));
        }
        $davka_id = (string) $p['davka'];
        $ids      = array($priloha);
        $obnovit  = false; // jedna příloha: jen zpětný přepis, data stránek ze zálohy by vrátila i ostatní
    } else {
        if ($davka_id !== posledni_davka()) {
            return new \WP_Error('marmal_obrazky', __('Vracet jde jen poslední dávku (dávky se vracejí od nejnovější).', 'marmal-breakdance-plus'));
        }
        $ids     = array();
        $vsechny = 0;
        foreach ((array) ($z['davky'][$davka_id]['prilohy'] ?? array()) as $id) {
            $p = $z['prilohy'][$id] ?? array();
            if (($p['stav'] ?? '') !== 'prevedeno' || ($p['davka'] ?? '') !== $davka_id) {
                continue;
            }
            $vsechny++;
            if (muze_vratit((int) $id, $p)) {
                $ids[] = (int) $id;
            }
        }
        if (!$ids) {
            return new \WP_Error('marmal_obrazky', __('V dávce není nic k vrácení.', 'marmal-breakdance-plus'));
        }
        // Data stránek ze zálohy jen když jde vrátit všechno – jinak by odkazovala na smazané soubory.
        $obnovit = !empty($z['davky'][$davka_id]['zaloha']) && $vsechny === count($ids);
    }

    ulozit_frontu(array(
        'operace'   => 'vraceni',
        'davka'     => $davka_id,
        'faze'      => 'vraceni',
        'celkem'    => count($ids),
        'cekajici'  => $ids,
        'vracene'   => array(),
        'pokusy'    => array(),
        'hotove'    => 0,
        'preskocene' => 0,
        'chyby'     => array(),
        'pred'      => 0,
        'po'        => 0,
        'obnovit'   => $obnovit,
        'prispevky' => array(),
        'prispevky_celkem' => 0,
        'zmeny'     => array(),
        'css'       => array(),
        'css_globalni' => false,
        'css_rucne' => false,
        'zacatek'   => time(),
        'pozastaveno' => false,
    ));
    return stav_fronty();
}

/** Nejnovější dávka, která ještě není celá vrácená. */
function posledni_davka(): string
{
    $davky = zaloha()['davky'];
    uasort($davky, function ($a, $b) {
        return ($b['datum'] ?? 0) <=> ($a['datum'] ?? 0);
    });
    foreach ($davky as $id => $d) {
        if (($d['stav'] ?? '') !== 'vraceno') {
            return (string) $id;
        }
    }
    return '';
}

/** Jde přílohu vrátit? (staré soubory na místě, nebo v záloze) */
function muze_vratit(int $id, array $p): bool
{
    $davka = zaloha()['davky'][$p['davka'] ?? ''] ?? array();
    foreach ((array) ($p['soubory_stare'] ?? array()) as $rel) {
        if (is_file(abs_cesta($rel))) {
            continue;
        }
        if (empty($davka['zaloha']) || !is_file(abs_cesta(($davka['slozka'] ?? '') . '/' . $rel))) {
            return false;
        }
    }
    return true;
}

/** Vrátí jednu přílohu (soubory, metadata, MIME). Stránky se opraví v další fázi. @return true|\WP_Error */
function vratit_prilohu(int $id)
{
    $z = zaloha();
    $p = $z['prilohy'][$id] ?? null;
    if (!$p || ($p['stav'] ?? '') !== 'prevedeno') {
        return true;
    }
    $davka = $z['davky'][$p['davka']] ?? array();
    foreach ((array) $p['soubory_stare'] as $rel) {
        $abs = abs_cesta($rel);
        if (is_file($abs)) {
            continue;
        }
        $zal = abs_cesta(($davka['slozka'] ?? '') . '/' . $rel);
        if (!v_uploads($abs) || !is_file($zal) || !@copy($zal, $abs)) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
            return new \WP_Error('marmal_obrazky', sprintf('Příloha %d: nepodařilo se obnovit %s ze zálohy.', $id, $rel));
        }
    }
    nastavit_prilohu($id, (string) $p['soubor'], (string) $p['mime'], (string) $p['guid']);
    wp_update_attachment_metadata($id, (array) $p['meta']);

    $mapa = mapa();
    foreach ((array) $p['soubory_stare'] as $rel) {
        unset($mapa[$rel]);
    }
    ulozit_mapu($mapa);

    $z['prilohy'][$id]['stav'] = 'vraceno-ceka'; // WebP se smažou až po opravě stránek
    ulozit_zalohu($z);
    return true;
}

/** Zpětná mapa vracených příloh (uložená při převodu): nové soubory → staré. */
function zpetna_mapa(array $ids): array
{
    $z    = zaloha();
    $mapa = array();
    foreach ($ids as $id) {
        foreach ((array) ($z['prilohy'][$id]['zpet'] ?? array()) as $nova => $stara) {
            $mapa[(string) $nova] = (string) $stara;
        }
    }
    return $mapa;
}

/** Stav fronty pro administraci. */
function stav_fronty(): array
{
    $f = fronta();
    if (!$f) {
        return array('faze' => '', 'text' => '');
    }
    $texty = array(
        'prevod'   => __('Převádím obrázky…', 'marmal-breakdance-plus'),
        'vraceni'  => __('Vracím původní soubory…', 'marmal-breakdance-plus'),
        'prepis'   => __('Přepisuji odkazy na stránkách…', 'marmal-breakdance-plus'),
        'css'      => __('Regeneruji CSS Breakdance…', 'marmal-breakdance-plus'),
        'mazani'   => __('Mažu soubory WebP vrácených obrázků…', 'marmal-breakdance-plus'),
        'kontrola' => __('Kontroluji výsledek…', 'marmal-breakdance-plus'),
        'hotovo'   => __('Hotovo.', 'marmal-breakdance-plus'),
    );
    return array(
        'operace'     => $f['operace'] ?? '',
        'davka'       => $f['davka'] ?? '',
        'faze'        => $f['faze'] ?? '',
        'text'        => $texty[$f['faze'] ?? ''] ?? '',
        'celkem'      => (int) ($f['celkem'] ?? 0),
        'hotove'      => (int) ($f['hotove'] ?? 0),
        'preskocene'  => (int) ($f['preskocene'] ?? 0),
        'zbyva'       => count((array) ($f['cekajici'] ?? array())),
        'chyby'       => array_values(array_slice((array) ($f['chyby'] ?? array()), -20)),
        'pred'        => (int) ($f['pred'] ?? 0),
        'po'          => (int) ($f['po'] ?? 0),
        'pred_text'   => velikost((int) ($f['pred'] ?? 0)),
        'po_text'     => velikost((int) ($f['po'] ?? 0)),
        'prispevky'   => (int) ($f['prispevky_celkem'] ?? 0) - count((array) ($f['prispevky'] ?? array())),
        'prispevky_celkem' => (int) ($f['prispevky_celkem'] ?? 0),
        'css_rucne'   => !empty($f['css_rucne']),
        'pozastaveno' => !empty($f['pozastaveno']),
        'kontrola'    => $f['kontrola'] ?? null,
    );
}

/**
 * Jeden krok fronty (jeden AJAX požadavek). Pracuje, dokud nevyprší časový limit.
 * @return array|\WP_Error
 */
function krok()
{
    if (!rozpracovano()) {
        return stav_fronty();
    }
    if (!zamknout()) {
        return new \WP_Error('marmal_obrazky_zamek', __('Fronta se právě zpracovává v jiném okně.', 'marmal-breakdance-plus'));
    }
    if (function_exists('set_time_limit')) {
        @set_time_limit(CAS_KROKU + 40); // phpcs:ignore WordPress.PHP.NoSilencedErrors
    }
    wp_raise_memory_limit('image');
    $start = microtime(true);
    $cas   = function () use ($start) {
        return microtime(true) - $start;
    };

    try {
        $f = fronta();
        $f['pozastaveno'] = false;
        $z = zaloha();
        $davka = $z['davky'][$f['davka']] ?? array();
        $slozka = !empty($davka['zaloha']) ? (string) ($davka['slozka'] ?? '') : '';

        while ($cas() < CAS_KROKU) {
            $faze = $f['faze'];

            if ($faze === 'prevod') {
                if (!$f['cekajici']) {
                    $f['faze']             = 'prepis';
                    $f['prispevky']        = seznam_k_prepisu();
                    $f['prispevky_celkem'] = count($f['prispevky']);
                    ulozit_frontu($f);
                    continue;
                }
                if ($cas() > NOVA_POLOZKA) {
                    break;
                }
                $id = (int) $f['cekajici'][0];
                // Ochrana proti zacyklení: příloha, na které požadavek dvakrát spadl (paměť), se přeskočí.
                $f['pokusy'][$id] = (int) ($f['pokusy'][$id] ?? 0) + 1;
                if ($f['pokusy'][$id] > 2) {
                    obnovit_rozpracovanou($id);
                    array_shift($f['cekajici']);
                    $f['chyby'][] = sprintf(__('Příloha %d: převod opakovaně spadl (nejspíš paměť) – přeskočena.', 'marmal-breakdance-plus'), $id);
                    zapsat_log(end($f['chyby']));
                    ulozit_frontu($f);
                    continue;
                }
                ulozit_frontu($f);

                $r = prevest_prilohu($id, (string) $f['davka']);
                array_shift($f['cekajici']);
                unset($f['pokusy'][$id]);
                if (is_wp_error($r)) {
                    $f['chyby'][] = $r->get_error_message();
                    zapsat_log($r->get_error_message());
                } elseif ($r['stav'] === 'ok') {
                    $f['hotove']++;
                    $f['pred'] += (int) $r['pred'];
                    $f['po']   += (int) $r['po'];
                } else {
                    $f['preskocene']++;
                }
                ulozit_frontu($f);
                continue;
            }

            if ($faze === 'vraceni') {
                if (!$f['cekajici']) {
                    $f['faze']             = 'prepis';
                    $f['prispevky']        = seznam_k_prepisu();
                    $f['prispevky_celkem'] = count($f['prispevky']);
                    ulozit_frontu($f);
                    continue;
                }
                $id = (int) array_shift($f['cekajici']);
                $r  = vratit_prilohu($id);
                if (is_wp_error($r)) {
                    $f['chyby'][] = $r->get_error_message();
                    zapsat_log($r->get_error_message());
                } else {
                    $f['vracene'][] = $id;
                    $f['hotove']++;
                }
                ulozit_frontu($f);
                continue;
            }

            if ($faze === 'prepis') {
                if ($f['operace'] === 'prevod') {
                    $ctx = kontext_prevodu($slozka);
                } else {
                    $ids = array();
                    foreach ((array) $f['vracene'] as $vid) {
                        $ids[(int) $vid] = true;
                    }
                    $ctx = array('pary' => pary(zpetna_mapa((array) $f['vracene'])), 'ids' => $ids, 'slozka' => '');
                }
                while ($f['prispevky'] && $cas() < CAS_KROKU - 2) {
                    $pid = (int) array_shift($f['prispevky']);
                    $r   = ($f['operace'] === 'prevod') ? prepsat_prispevek($pid, $ctx) : vratit_prispevek($pid, $ctx, $f);
                    if (!empty($r['chyba'])) {
                        $f['chyby'][] = $r['chyba'];
                    }
                    if (!empty($r['zmen'])) {
                        $f['zmeny'][$pid] = (int) $r['zmen'];
                        $f['css'][]       = $pid;
                        if ($f['operace'] === 'prevod') {
                            $z = zaloha();
                            $z['davky'][$f['davka']]['prispevky'][$pid] = array(
                                'zmen' => (int) $r['zmen'],
                                'bd'   => $r['bd'] ?? null,
                                'pc'   => $r['pc'] ?? null,
                            );
                            ulozit_zalohu($z);
                        }
                    }
                    ulozit_frontu($f);
                }
                if ($f['prispevky']) {
                    break;
                }
                // Globální volby Breakdance.
                if ($f['operace'] === 'prevod') {
                    $volby = prepsat_volby($ctx);
                    if ($volby) {
                        $z = zaloha();
                        $z['davky'][$f['davka']]['volby'] = array_merge((array) $z['davky'][$f['davka']]['volby'], $volby);
                        ulozit_zalohu($z);
                    }
                } else {
                    vratit_volby($ctx, $f);
                }
                $f['faze'] = 'css';
                $f['css']  = array_values(array_unique(array_map('intval', $f['css'])));
                ulozit_frontu($f);
                continue;
            }

            if ($faze === 'css') {
                if (empty($f['css_globalni'])) {
                    if (!regenerovat_css(array(), true)) {
                        $f['css_rucne'] = true;
                    }
                    $f['css_globalni'] = true;
                    ulozit_frontu($f);
                }
                while ($f['css'] && $cas() < CAS_KROKU - 2) {
                    $pid = (int) array_shift($f['css']);
                    if (!regenerovat_css(array($pid), false)) {
                        $f['css_rucne'] = true;
                    }
                    ulozit_frontu($f);
                }
                if ($f['css']) {
                    break;
                }
                vycistit_cache();
                $f['faze'] = $f['operace'] === 'prevod' ? 'kontrola' : 'mazani';
                ulozit_frontu($f);
                continue;
            }

            if ($faze === 'mazani') {
                smazat_webp_vracenych((array) $f['vracene']);
                $z = zaloha();
                $zbyva = 0;
                foreach ((array) ($z['davky'][$f['davka']]['prilohy'] ?? array()) as $id) {
                    if (($z['prilohy'][$id]['stav'] ?? '') === 'prevedeno' && ($z['prilohy'][$id]['davka'] ?? '') === $f['davka']) {
                        $zbyva++;
                    }
                }
                if ($zbyva === 0 && isset($z['davky'][$f['davka']])) {
                    $z['davky'][$f['davka']]['stav'] = 'vraceno';
                    ulozit_zalohu($z);
                }
                $f['faze'] = 'hotovo';
                $f['konec'] = time();
                ulozit_frontu($f);
                break;
            }

            if ($faze === 'kontrola') {
                $f['kontrola'] = kontrola((string) $f['davka']);
                $z = zaloha();
                if (isset($z['davky'][$f['davka']])) {
                    $z['davky'][$f['davka']]['stav']     = 'hotovo';
                    $z['davky'][$f['davka']]['kontrola'] = $f['kontrola'];
                    $z['davky'][$f['davka']]['pred']     = (int) $f['pred'];
                    $z['davky'][$f['davka']]['po']       = (int) $f['po'];
                    ulozit_zalohu($z);
                }
                $f['faze']  = 'hotovo';
                $f['konec'] = time();
                ulozit_frontu($f);
                break;
            }

            break; // neznámá / hotová fáze
        }
    } catch (\Throwable $e) {
        zapsat_log('Hromadný převod: ' . $e->getMessage());
        return new \WP_Error('marmal_obrazky', $e->getMessage());
    } finally {
        odemknout();
    }
    return stav_fronty();
}

/**
 * Vrácení v jednom příspěvku: když se data Breakdance od převodu nezměnila, obnoví zálohu
 * (přesně původní stav). Jinak (stránka mezitím upravená, nebo vrácení jedné přílohy) zpětný přepis –
 * úpravy na stránce zůstanou.
 */
function vratit_prispevek(int $post_id, array $ctx, array $f): array
{
    global $wpdb;
    $vysledek = array('zmen' => 0);
    if (!empty($f['obnovit'])) {
        $z      = zaloha();
        $davka  = $z['davky'][$f['davka']] ?? array();
        $zaznam = $davka['prispevky'][$post_id] ?? null;
        if ($zaznam) {
            $raw = get_post_meta($post_id, '_breakdance_data', true);
            if (!empty($zaznam['bd']) && is_string($raw) && md5($raw) === ($zaznam['bd']['md5_po'] ?? '')) {
                $puvodni = nacist_text((string) $davka['slozka'], (string) $zaznam['bd']['soubor']);
                if ($puvodni !== null && is_array(json_decode($puvodni, true))) {
                    update_post_meta($post_id, '_breakdance_data', wp_slash($puvodni));
                    $vysledek['zmen']++;
                }
            }
            $post = get_post($post_id);
            if (!empty($zaznam['pc']) && $post && md5($post->post_content) === ($zaznam['pc']['md5_po'] ?? '')) {
                $puvodni = nacist_text((string) $davka['slozka'], (string) $zaznam['pc']['soubor']);
                if ($puvodni !== null) {
                    $wpdb->update($wpdb->posts, array('post_content' => $puvodni), array('ID' => $post_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    clean_post_cache($post_id);
                    $vysledek['zmen']++;
                }
            }
        }
    }
    // Zbytek (upravené stránky, Rank Math, stránky mimo dávku) zpětným přepisem.
    $r = prepsat_prispevek($post_id, $ctx);
    $vysledek['zmen'] += (int) ($r['zmen'] ?? 0);
    if (!empty($r['chyba'])) {
        $vysledek['chyba'] = $r['chyba'];
    }
    return $vysledek;
}

function vratit_volby(array $ctx, array $f): void
{
    $z     = zaloha();
    $davka = $z['davky'][$f['davka']] ?? array();
    foreach ((array) ($davka['volby'] ?? array()) as $nazev => $zaznam) {
        if (empty($f['obnovit'])) {
            break;
        }
        if (md5(maybe_serialize(get_option($nazev))) !== ($zaznam['md5_po'] ?? '')) {
            continue;
        }
        $t = nacist_text((string) $davka['slozka'], (string) $zaznam['soubor']);
        if ($t !== null) {
            $hodnota = is_serialized($t) ? @unserialize($t, array('allowed_classes' => false)) : $t; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions,WordPress.PHP.NoSilencedErrors
            if ($hodnota !== false || $t === 'b:0;') {
                update_option($nazev, $hodnota);
            }
        }
    }
    prepsat_volby($ctx); // zpětný přepis toho, co ze zálohy obnovit nešlo
}

/** Smaže soubory WebP vrácených příloh (po opravě stránek). */
function smazat_webp_vracenych(array $ids): void
{
    $z = zaloha();
    foreach ($ids as $id) {
        $p = $z['prilohy'][$id] ?? null;
        if (!$p) {
            continue;
        }
        foreach ((array) ($p['soubory_nove'] ?? array()) as $rel) {
            $abs = abs_cesta($rel);
            if (!in_array($rel, (array) $p['soubory_stare'], true) && v_uploads($abs) && is_file($abs)
                && preg_match('/\.webp$/i', $rel) && $rel !== (string) get_post_meta((int) $id, '_wp_attached_file', true)) {
                wp_delete_file($abs);
            }
        }
        $z['prilohy'][$id]['stav'] = 'vraceno';
        $z['prilohy'][$id]['vraceno'] = time();
    }
    ulozit_zalohu($z);
}

/**
 * Kontrola po převodu: nové soubory existují a nejsou prázdné, na stránkách nezůstaly odkazy
 * na převedené JPG / PNG.
 */
function kontrola(string $davka_id): array
{
    $z      = zaloha();
    $davka  = $z['davky'][$davka_id] ?? array();
    $chybi  = array();
    $stare  = array();
    foreach ((array) ($davka['prilohy'] ?? array()) as $id) {
        $p = $z['prilohy'][$id] ?? null;
        if (!$p || ($p['stav'] ?? '') !== 'prevedeno') {
            continue;
        }
        $f = soubory_prilohy((int) $id);
        foreach ($f['vse'] as $rel) {
            $abs = abs_cesta($rel);
            if (!is_file($abs) || filesize($abs) < 1) {
                $chybi[] = $rel;
            }
        }
        foreach ((array) $p['soubory_stare'] as $rel) {
            $stare[$rel] = (int) $id;
        }
    }
    $zbyle = array();
    $index = index_pouziti(null);
    foreach ($index['cesty'] as $rel => $kde) {
        if (isset($stare[$rel])) {
            $zbyle[$rel] = $kde;
        }
    }
    return array(
        'cas'       => time(),
        'chybi'     => array_slice($chybi, 0, 50),
        'chybi_pocet' => count($chybi),
        'zbyle'     => array_slice($zbyle, 0, 50, true),
        'zbyle_pocet' => count($zbyle),
    );
}

/** Pozastaví frontu (prohlížeč přestane posílat kroky; pokračovat jde kdykoli). */
function pozastavit(): void
{
    $f = fronta();
    if ($f && rozpracovano()) {
        $f['pozastaveno'] = true;
        ulozit_frontu($f);
    }
}

// ================================================================= úklid

/** Lze dávku uklidit? (hotová, ne vrácená, aspoň 14 dní stará) */
function lze_uklidit(array $davka): bool
{
    return ($davka['stav'] ?? '') === 'hotovo' && empty($davka['uklizeno'])
        && (int) ($davka['datum'] ?? 0) <= time() - UKLID_DNI * DAY_IN_SECONDS;
}

/**
 * Smaže staré JPG / PNG převedených příloh dávky. Záloha zůstává (maže se zvlášť).
 * @return int|\WP_Error počet smazaných souborů
 */
function uklidit(string $davka_id)
{
    $z     = zaloha();
    $davka = $z['davky'][$davka_id] ?? null;
    if (!$davka || !lze_uklidit($davka)) {
        return new \WP_Error('marmal_obrazky', __('Staré soubory této dávky ještě nejde smazat (nejdřív 14 dní po převodu).', 'marmal-breakdance-plus'));
    }
    if (rozpracovano()) {
        return new \WP_Error('marmal_obrazky', __('Nejdřív dokonči běžící operaci.', 'marmal-breakdance-plus'));
    }
    $smazano = 0;
    foreach ((array) $davka['prilohy'] as $id) {
        $p = $z['prilohy'][$id] ?? null;
        if (!$p || ($p['stav'] ?? '') !== 'prevedeno') {
            continue;
        }
        $aktualni = soubory_prilohy((int) $id)['vse'];
        foreach ((array) $p['soubory_stare'] as $rel) {
            $abs = abs_cesta($rel);
            if (in_array($rel, $aktualni, true) || !v_uploads($abs) || !is_file($abs) || !preg_match('/\.(jpe?g|png)$/i', $rel)) {
                continue;
            }
            wp_delete_file($abs);
            $smazano++;
        }
        $z['prilohy'][$id]['uklizeno'] = time();
    }
    $z['davky'][$davka_id]['uklizeno'] = time();
    ulozit_zalohu($z);
    zapsat_log(sprintf('Dávka %s: smazáno %d starých souborů.', $davka_id, $smazano), 'info');
    return $smazano;
}

/**
 * Smaže zálohu dávky (soubory i data stránek). Po smazání jde dávku vrátit jen dokud nejsou
 * smazané staré soubory, a to zpětným přepisem.
 * @return true|\WP_Error
 */
function smazat_zalohu(string $davka_id)
{
    $z     = zaloha();
    $davka = $z['davky'][$davka_id] ?? null;
    if (!$davka || empty($davka['zaloha'])) {
        return new \WP_Error('marmal_obrazky', __('Záloha neexistuje.', 'marmal-breakdance-plus'));
    }
    if (rozpracovano()) {
        return new \WP_Error('marmal_obrazky', __('Nejdřív dokonči běžící operaci.', 'marmal-breakdance-plus'));
    }
    $slozka = abs_cesta((string) $davka['slozka']);
    list($base) = uploads();
    $koren = $base . SLOZKA_ZALOHY . '/';
    // Mazat jen podsložku kořene záloh, nic jiného.
    if (strpos(wp_normalize_path($slozka), $koren) !== 0 || strlen($slozka) <= strlen($koren) + 10 || !v_uploads($slozka . '/x')) {
        return new \WP_Error('marmal_obrazky', 'Neplatná složka zálohy.');
    }
    smazat_slozku($slozka);
    $z['davky'][$davka_id]['zaloha'] = false;
    ulozit_zalohu($z);
    zapsat_log(sprintf('Dávka %s: záloha smazána.', $davka_id), 'info');
    return true;
}

function smazat_slozku(string $slozka): void
{
    if (!is_dir($slozka) || is_link($slozka)) {
        return;
    }
    $polozky = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($slozka, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($polozky as $p) {
        if ($p->isDir() && !$p->isLink()) {
            @rmdir($p->getPathname()); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
        } else {
            wp_delete_file($p->getPathname());
        }
    }
    @rmdir($slozka); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
}
