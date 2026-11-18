<?php
/**
 * Server-side render elementu „Marmal – Patička“ (MarmalElements\PatickaWebu).
 * VYGENEROVÁNO skriptem tools/paticka_element.py – neupravovat ručně.
 *
 * @var array $propertiesData
 */

if (!function_exists('\MarmalElements\Paticka\element_html')) {
    // Modul Patička je vypnutý (MarMal → Moduly) – návštěvník nic nevidí, správce upozornění.
    if (current_user_can('manage_options')) {
        echo '<p class="marmal-paticka__hlaska">' . esc_html__('Modul Patička je vypnutý – zapni ho v MarMal → Moduly.', 'marmal-breakdance-plus') . '</p>';
    }
    return;
}

$marmal_pat_c = $propertiesData['content']['paticka'] ?? [];
$marmal_pat_r = $propertiesData['content']['rozvrzeni'] ?? [];
// Content → Patička + Content → Rozvržení – podrobně (od 0.11.0); hodnoty čistí element_html().
$marmal_pat_c = array_merge(is_array($marmal_pat_r) ? $marmal_pat_r : [], is_array($marmal_pat_c) ? $marmal_pat_c : []);
// element_html() vrací hotové HTML, každá hodnota je escapovaná (esc_html/esc_attr/esc_url).
echo \MarmalElements\Paticka\element_html(is_array($marmal_pat_c) ? $marmal_pat_c : []); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
