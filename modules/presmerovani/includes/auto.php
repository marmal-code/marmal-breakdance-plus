<?php
/**
 * Modul Přesměrování – automaticky při změně adresy publikované stránky.
 *
 * Když se u publikované stránky / příspěvku / jednotky změní adresa (slug nebo nadřazená stránka),
 * vznikne přesměrování 301 ze staré adresy na stránku („post:ID“ – vede vždy na její aktuální adresu,
 * takže další přejmenování řetěz nevytvoří). U hierarchických typů i pro podstránky (max. 200).
 * Pravidlo, jehož zdroj je teď nová adresa stránky, se smaže (stránka přejmenovaná zpět).
 */

namespace MarmalElements\Presmerovani;

if (!defined('ABSPATH')) {
    exit;
}

/** Typy obsahu, u kterých se adresa hlídá. */
function hlidane_typy(): array
{
    $typy = get_post_types(array('public' => true));
    unset($typy['attachment']);
    return array_keys($typy);
}

/** Smaže pravidlo se zdrojem = cesta (nová adresa stránky nesmí být přesměrovaná). */
function uvolnit(string $cesta): void
{
    global $wpdb;
    if ($cesta !== '') {
        $wpdb->delete(t_pravidla(), array('zdroj_hash' => md5($cesta)));
    }
}

function po_zmene(int $id, \WP_Post $po, \WP_Post $pred): void
{
    if (!nastaveni()['auto'] || !db_pripravena()) {
        return;
    }
    if ($pred->post_status !== 'publish' || $po->post_status !== 'publish' || !in_array($po->post_type, hlidane_typy(), true)) {
        return;
    }
    if (wp_is_post_revision($id) || wp_is_post_autosave($id)) {
        return;
    }
    $stara = cesta((string) get_permalink($pred));
    $nova  = cesta((string) get_permalink($id));
    if ($stara === '' || $nova === '' || $stara === $nova || $stara === '/') {
        return;
    }
    uvolnit($nova);
    ulozit(array('zdroj' => $stara, 'cil' => 'post:' . $id, 'kod' => 301, 'puvod' => 'auto'), 0, true);

    if (is_post_type_hierarchical($po->post_type)) {
        $potomci = get_page_children($id, get_pages(array('post_type' => $po->post_type, 'post_status' => 'publish', 'number' => 1000)));
        foreach (array_slice((array) $potomci, 0, 200) as $dite) {
            $novaDite = cesta((string) get_permalink((int) $dite->ID));
            if ($novaDite === '' || strpos($novaDite . '/', $nova . '/') !== 0) {
                continue;
            }
            $staraDite = $stara . substr($novaDite, strlen($nova));
            if ($staraDite !== $novaDite) {
                uvolnit($novaDite);
                ulozit(array('zdroj' => $staraDite, 'cil' => 'post:' . (int) $dite->ID, 'kod' => 301, 'puvod' => 'auto'), 0, true);
            }
        }
    }
}

add_action('post_updated', function ($id, $po, $pred) {
    if ($po instanceof \WP_Post && $pred instanceof \WP_Post) {
        po_zmene((int) $id, $po, $pred);
    }
}, 10, 3);
