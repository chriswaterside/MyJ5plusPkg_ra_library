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
            'mintcake'    => '#9BC8AB',
            'sunset'      => '#F08050',
            'granite'     => '#404141',
            'rosycheeks'  => '#F6B09D',
            'sunrise'     => '#F9B104',
            'white'       => '#FFFFFF',
            'transparent' => '',
            'custom'      => $customvalue ?? '',
        ];

        return $colors[$option] ?? ($customvalue ?? '');
    }

    /**
     * Turn the "which part of the page" setting into a CSS selector (Gantry 5 sections).
     * 'same' = the section that contains this module.
     */
    public static function resolveTarget(?string $target, ?string $custom): string
    {
        $sections = ['top', 'header', 'navigation', 'showcase', 'utility', 'feature', 'expanded', 'extension', 'bottom', 'footer', 'copyright'];
        $target = (string) $target;

        if ($target === 'same' || $target === '') {
            return 'section[id^="g-"]:has(.rafooter)';
        }
        if ($target === 'custom') {
            return self::cleanSelector($custom, '#g-footer, #g-copyright');
        }
        if (in_array($target, $sections, true)) {
            return '#g-' . $target;
        }

        return 'section[id^="g-"]:has(.rafooter)';
    }

    /**
     * Accept a CSS selector list typed in the module settings, or return the default.
     * Only ordinary selector characters are allowed (no braces, semicolons or tags).
     */
    public static function cleanSelector(?string $value, string $default): string
    {
        $value = trim((string) $value);

        if ($value === '' || !preg_match('/^[A-Za-z0-9_#.,\s>+~:*\-\[\]="\'()]+$/', $value)) {
            return $default;
        }

        return $value;
    }

    /**
     * Append a descendant suffix (e.g. " a") to every selector in a comma separated list.
     */
    public static function scope(string $selectors, string $suffix): string
    {
        $parts = array_filter(array_map('trim', explode(',', $selectors)), 'strlen');

        return implode(', ', array_map(static fn($sel) => $sel . $suffix, $parts));
    }
}
