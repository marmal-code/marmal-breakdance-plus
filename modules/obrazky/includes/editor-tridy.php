<?php
/**
 * Modul Obrázky – editory s vlastními metodami. Načítá se až z nacist_editor(),
 * kdy už existují rodičovské třídy WordPressu (WP_Image_Editor_Imagick / _GD).
 *
 * Proč vlastní odstranění metadat: strip_meta() WordPressu záměrně ponechává EXIF, IPTC a XMP
 * (kvůli orientaci a autorským právům). Na web ale nemají jít – GPS z fotek z mobilu je osobní údaj.
 * Po srovnání orientace už EXIF není potřeba; zůstane jen barevný profil ICC.
 */

namespace MarmalElements\Obrazky;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists(__NAMESPACE__ . '\Editor_Imagick') && class_exists('WP_Image_Editor_Imagick')) {
    class Editor_Imagick extends \WP_Image_Editor_Imagick
    {
        /** Odstraní všechna metadata kromě barevného profilu ICC. */
        public function marmal_odstranit_metadata(): void
        {
            if (!($this->image instanceof \Imagick)) {
                return;
            }
            try {
                $icc = '';
                $profily = $this->image->getImageProfiles('icc', true);
                if (!empty($profily['icc'])) {
                    $icc = $profily['icc'];
                }
                $this->image->stripImage();
                if ($icc !== '') {
                    $this->image->profileImage('icc', $icc);
                }
            } catch (\Exception $e) {
                // Metadata se nepodařilo odstranit – obrázek se uloží i tak.
                unset($e);
            }
        }

        /** Po ručním srovnání orientace (bez rozšíření exif) nastaví příznak „správně“. */
        public function marmal_orientace_ok(): void
        {
            if ($this->image instanceof \Imagick && defined('Imagick::ORIENTATION_TOPLEFT')) {
                try {
                    $this->image->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
                } catch (\Exception $e) {
                    unset($e);
                }
            }
        }

        public function marmal_uvolnit(): void
        {
            if ($this->image instanceof \Imagick) {
                $this->image->clear();
            }
        }
    }
}

if (!class_exists(__NAMESPACE__ . '\Editor_GD') && class_exists('WP_Image_Editor_GD')) {
    class Editor_GD extends \WP_Image_Editor_GD
    {
        /** GD při ukládání žádná metadata (ani EXIF, ani ICC) nezapisuje – není co dělat. */
        public function marmal_odstranit_metadata(): void
        {
        }

        public function marmal_uvolnit(): void
        {
        }
    }
}
