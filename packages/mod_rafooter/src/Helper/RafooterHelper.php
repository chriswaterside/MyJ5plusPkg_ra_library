<?php
/**
 * @module      RA Footer
 * @license     http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace RamblersWebs\Module\Rafooter\Helper;


defined('_JEXEC') or die;

class RafooterHelper
{
    public static function getFooterColor(string $option, ?string $customvalue): string
    {
        $colors = [
            'darkgrey'    => '#333333',
            'pantone0110' => '#D7A900',
            'pantone0159' => '#C75B12',
            'pantone0186' => '#C60C30',
            'pantone0555' => '#206C49',
            'pantone0583' => '#A8B400',
            'pantone1815' => '#782327',
            'pantone4485' => '#5B491F',
            'pantone5565' => '#8BA69C',
            'pantone7474' => '#007A87',
            'white'       => '#FFFFFF',
            'transparent' => '',
            'custom'      => $customvalue ?? '',
        ];

        return $colors[$option] ?? ($customvalue ?? '');
    }
}