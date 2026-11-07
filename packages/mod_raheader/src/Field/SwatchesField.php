<?php

/**
 * @module      RA Header
 * Shows the colours that can be chosen for the title, sub title and background.
 * Display only: it stores no value.
 */

namespace RamblersWebs\Module\Raheader\Site\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

class SwatchesField extends FormField
{
    protected $type = 'Swatches';

    private const BRAND = [
        'COLOUR_MINTCAKE' => '#9BC8AB',
        'COLOUR_SUNSET' => '#F08050',
        'COLOUR_GRANITE' => '#404141',
        'COLOUR_ROSYCHEEKS' => '#F6B09D',
        'COLOUR_SUNRISE' => '#F9B104',
        'COLOUR_WHITE_CLOUDY' => '#FFFFFF',
    ];

    private const PANTONE = [
        'PANTONE0110' => '#D7A900',
        'PANTONE0159' => '#C75B12',
        'PANTONE0186' => '#C60C30',
        'PANTONE0555' => '#206C49',
        'PANTONE0583' => '#A8B400',
        'PANTONE1815' => '#782327',
        'PANTONE4485' => '#5B491F',
        'PANTONE5565' => '#8BA69C',
        'PANTONE7474' => '#007A87',
    ];

    protected function getLabel()
    {
        return '';
    }

    protected function getInput()
    {
        return '<div class="w-100">'
            . $this->group(Text::_('GROUP_RAMBLERS_BRAND'), self::BRAND, true)
            . $this->group(Text::_('GROUP_PANTONE_OLD'), self::PANTONE, false)
            . '<p class="small text-muted mt-2">' . Text::_('SWATCHES_NOTE') . '</p>'
            . '</div>';
    }

    private function group(string $title, array $colours, bool $translate): string
    {
        $html = '<h4 class="h6 mt-3">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h4>'
            . '<div style="display:flex;flex-wrap:wrap;gap:8px">';

        foreach ($colours as $name => $hex) {
            $label = $translate ? Text::_($name) : $name;
            $r = hexdec(substr($hex, 1, 2));
            $g = hexdec(substr($hex, 3, 2));
            $b = hexdec(substr($hex, 5, 2));
            $text = (($r * 299 + $g * 587 + $b * 114) / 1000) > 150 ? '#000000' : '#FFFFFF';

            $html .= '<div style="width:130px;padding:12px 8px;border:1px solid #888;border-radius:4px;'
                . 'background:' . $hex . ';color:' . $text . ';text-align:center;font-size:.85rem;line-height:1.3">'
                . '<strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong><br>' . $hex . '</div>';
        }

        return $html . '</div>';
    }
}
