<?php
/**
 * Modul Obrázky – přehled obrázků na pozadí sekcí, sloupců a divů v plné velikosti („full“) (od 0.17.0).
 *
 * Pozadí se přes srcset nezmenší – prohlížeč stáhne přesně zvolenou velikost. Stačí v Breakdance u elementu
 * nastavit menší velikost (Design → Background → Image Size). Na ticha-trida.cz to u hero zmenšilo obrázek
 * z 382 kB na 78 kB. Jen čtení dat Breakdance, nic se nemění. Načítá se jen v administraci.
 *
 * Data (ověřeno na Breakdance 3.0.2): design.background.image.{breakpoint}.id a design.background.image_size.{breakpoint};
 * chybějící velikost = plná velikost.
 */

namespace MarmalElements\Obrazky;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<int,array{post:int,nazev:string,element:string,id:string,priloha:int,soubor:string,velikost:int,breakpointy:string[]}>
 */
function pozadi_full(): array
{
    global $wpdb;
    // Nejdřív jen ID, pak data stránek po dávkách 20 (data můžou mít stovky kB) – šetří paměť.
    $ids = array_map('intval', (array) $wpdb->get_col($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        "SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_breakdance_data'
         WHERE " . sql_platne_prispevky() . ' AND m.meta_value LIKE %s LIMIT 1000', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- sql_platne_prispevky() je pevný řetězec
        '%' . $wpdb->esc_like('background') . '%'
    )));
    $out = array();
    foreach (array_chunk($ids, 20) as $davka) {
        $radky = (array) $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- jen celá čísla
            "SELECT p.ID, p.post_title, p.post_type, m.meta_value FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_breakdance_data'
             WHERE p.ID IN (" . implode(',', array_map('intval', $davka)) . ')'
        );
        foreach ($radky as $r) {
            $vnejsi = json_decode((string) $r->meta_value, true);
            $strom  = is_array($vnejsi) && isset($vnejsi['tree_json_string']) && is_string($vnejsi['tree_json_string']) ? json_decode($vnejsi['tree_json_string'], true) : null;
            if (!is_array($strom) || !isset($strom['root'])) {
                continue;
            }
            $typ   = get_post_type_object((string) $r->post_type);
            $nazev = ($r->post_title !== '' ? $r->post_title : '#' . $r->ID) . ' (' . ($typ ? $typ->labels->singular_name : $r->post_type) . ')';
            $projit = function ($uzel) use (&$projit, &$out, $r, $nazev) {
                if (!is_array($uzel)) {
                    return;
                }
                $bg = $uzel['data']['properties']['design']['background'] ?? null;
                if (is_array($bg) && is_array($bg['image'] ?? null) && ($bg['type'] ?? 'image') === 'image') {
                    $velikosti = is_array($bg['image_size'] ?? null) ? $bg['image_size'] : array();
                    $full      = array();
                    $priloha   = 0;
                    foreach ($bg['image'] as $bp => $obr) {
                        if (!is_array($obr) || empty($obr['id'])) {
                            continue;
                        }
                        $priloha = $priloha ?: (int) $obr['id'];
                        // Velikost platí i pro menší breakpointy, dokud ji něco nepřepíše – stačí základní a nastavené.
                        $v = $velikosti[$bp] ?? ($velikosti['breakpoint_base'] ?? 'full');
                        if ($v === 'full' || $v === '' || $v === null) {
                            $full[] = (string) $bp;
                        }
                    }
                    foreach ($velikosti as $bp => $v) {
                        if ($v === 'full' && !in_array((string) $bp, $full, true)) {
                            $full[] = (string) $bp;
                        }
                    }
                    if ($priloha > 0 && $full) {
                        $soubor = (string) get_attached_file($priloha);
                        $out[]  = array(
                            'post'        => (int) $r->ID,
                            'nazev'       => $nazev,
                            'element'     => (string) preg_replace('/^.*\\\\/', '', (string) ($uzel['data']['type'] ?? '')),
                            'id'          => (string) ($uzel['id'] ?? ''),
                            'priloha'     => $priloha,
                            'soubor'      => wp_basename($soubor),
                            'velikost'    => $soubor !== '' && is_file($soubor) ? (int) filesize($soubor) : 0,
                            'breakpointy' => array_map(function ($bp) {
                                return str_replace(array('breakpoint_', '_'), array('', ' '), $bp);
                            }, $full),
                        );
                    }
                }
                foreach ((array) ($uzel['children'] ?? array()) as $c) {
                    $projit($c);
                }
            };
            $projit($strom['root']);
        }
    }
    usort($out, function ($a, $b) {
        return $b['velikost'] <=> $a['velikost'];
    });
    return $out;
}

/** Sekce na stránce MarMal → Obrázky (na vyžádání). */
function render_pozadi(): void
{
    ?>
    <h2 id="pozadi"><?php esc_html_e('4. Obrázky na pozadí v plné velikosti', 'marmal-breakdance-plus'); ?></h2>
    <p><?php esc_html_e('Obrázek na pozadí sekce, sloupce nebo divu se nezmenšuje podle obrazovky – prohlížeč stáhne přesně zvolenou velikost. U velikosti „Full“ je to originál (často 1920 px a stovky kB i na mobilu). V Breakdance u elementu nastav Design → Background → Image Size (např. 1536x1536 na desktopu, Large na tabletu, Medium Large na mobilu).', 'marmal-breakdance-plus'); ?></p>
    <?php
    if (!isset($_GET['pozadi'])) { // phpcs:ignore WordPress.Security.NonceVerification -- jen zobrazení
        echo '<p><a class="button" href="' . esc_url(add_query_arg(array('page' => 'marmal-obrazky', 'pozadi' => 1), admin_url('admin.php')) . '#pozadi') . '">' . esc_html__('Najít obrázky na pozadí v plné velikosti', 'marmal-breakdance-plus') . '</a></p>';
        return;
    }
    $seznam = pozadi_full();
    if (!$seznam) {
        echo '<p>✓ ' . esc_html__('Žádný obrázek na pozadí nepoužívá plnou velikost.', 'marmal-breakdance-plus') . '</p>';
        return;
    }
    echo '<table class="widefat striped" style="max-width:1000px"><thead><tr><th>' . esc_html__('Stránka / šablona', 'marmal-breakdance-plus') . '</th><th>' . esc_html__('Element', 'marmal-breakdance-plus') . '</th><th>' . esc_html__('Obrázek', 'marmal-breakdance-plus') . '</th><th>' . esc_html__('Plná velikost na', 'marmal-breakdance-plus') . '</th></tr></thead><tbody>';
    foreach ($seznam as $p) {
        $builder = add_query_arg(array('breakdance' => 'builder', 'id' => $p['post']), home_url('/'));
        echo '<tr><td><a href="' . esc_url($builder) . '" target="_blank" rel="noopener">' . esc_html($p['nazev']) . '</a></td>'
            . '<td>' . esc_html($p['element'] . ' · ID ' . $p['id']) . '</td>'
            . '<td>' . esc_html($p['soubor']) . ($p['velikost'] ? ' (' . esc_html(velikost($p['velikost'])) . ')' : '') . '</td>'
            . '<td>' . esc_html(implode(', ', $p['breakpointy'])) . '</td></tr>';
    }
    echo '</tbody></table>';
}
