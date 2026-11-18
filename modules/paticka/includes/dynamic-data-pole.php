<?php
/**
 * Třída dynamického pole Breakdance pro modul Patička.
 * Načítá se AŽ po ověření, že Breakdance běží (dědí z Breakdance\DynamicData\StringField).
 * Jedna třída obslouží všechna pole – hodnotu počítá callback z includes/data.php.
 */

namespace MarmalElements\Paticka;

use Breakdance\DynamicData\StringField;
use Breakdance\DynamicData\StringData;

if (!defined('ABSPATH')) {
    exit;
}

class Pole extends StringField
{
    private $slug;
    private $label;
    private $cb;
    private $types;

    /**
     * @param string   $slug  marmal_paticka_…
     * @param string   $label název v nabídce dynamických dat
     * @param callable $cb    vrací neescapovaný text nebo URL
     * @param array    $types ['string'] nebo ['url']
     */
    public function __construct(string $slug, string $label, callable $cb, array $types = array('string'))
    {
        $this->slug  = $slug;
        $this->label = $label;
        $this->cb    = $cb;
        $this->types = $types;
    }

    public function label()
    {
        return $this->label;
    }

    public function category()
    {
        return 'MarMal';
    }

    public function subcategory()
    {
        return __('Patička', 'marmal-breakdance-plus');
    }

    public function slug()
    {
        return $this->slug;
    }

    public function returnTypes()
    {
        return $this->types;
    }

    public function handler($attributes): StringData
    {
        $hodnota = (string) call_user_func($this->cb);
        if ($hodnota === '') {
            return StringData::emptyString();
        }
        // URL jen http(s), text escapovaný (hodnoty zadává správce webu).
        return StringData::fromString(in_array('url', $this->types, true) ? esc_url($hodnota, array('http', 'https')) : esc_html($hodnota));
    }
}
