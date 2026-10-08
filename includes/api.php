<?php
/**
 * Veřejné funkce pro jiné pluginy (hlavně MarMal Agent → sprava.marmal.cz).
 *
 * Volající má vždy nejdřív ověřit function_exists('marmal_bdp_moduly'),
 * protože Breakdance Plus na webu být nemusí.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Verze pluginu Marmal – Breakdance Plus. */
function marmal_bdp_verze(): string
{
    return MARMAL_BDP_VERSION;
}

/**
 * Seznam modulů a jejich stav.
 *
 * @return array<int,array{id:string,nazev:string,popis:string,aktivni:bool,zamceny:bool,stav:string,konflikt:?string}>
 *   stav: zapnuto | vypnuto | konflikt (běží starý plugin, čeká na převod) | chybi_breakdance
 */
function marmal_bdp_moduly(): array
{
    return \MarmalElements\Moduly::seznam();
}

/**
 * Zapne nebo vypne modul (projeví se od dalšího načtení stránky).
 *
 * @return true|WP_Error
 */
function marmal_bdp_nastavit_modul(string $id, bool $aktivni)
{
    return \MarmalElements\Moduly::nastavit($id, $aktivni);
}
