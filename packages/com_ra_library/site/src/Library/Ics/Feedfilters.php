<?php

namespace Ramblers\Component\Ra_library\Site\Library\Ics;

/**
 * The filters that can be used in a calendar feed url, and their values.
 *
 *   index.php?option=com_ra_library&task=calendarfeed.calendarfeed&groups=SR05,WR10
 *       &notype=events&nodow=sat,sun&nograde=ea,t&nodist=undefined,over15&limit=50
 *
 * Each filter names the values to LEAVE OUT, as a comma separated list of the codes below, mirroring the
 * form where the user unticks what they do not want. A filter that is not in the url leaves everything in,
 * so values added here later are included automatically in existing feeds.
 * The lists are also passed to the javascript (ra.calendarsubscribe.js) by Calendarbutton,
 * so the form and the controller can never disagree.
 */
defined('_JEXEC') or die('Restricted access');

class Feedfilters {

    // code => name used by the feed filters
    public const TYPES = ['walks' => 'Walks', 'events' => 'Events'];
    public const DAYS = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday',
        'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];
    public const GRADES = ['ea' => 'Easy Access', 'e' => 'Easy', 'l' => 'Leisurely',
        'm' => 'Moderate', 's' => 'Strenuous', 't' => 'Technical'];
    // code => [name shown to the user, [minimum miles, maximum miles]]
    public const DISTANCES = [
        'undefined' => ['Not defined: See description', [-1, 0]],
        'upto3' => ['Up to 3 miles (5 km)', [0, 3]],
        '3to5' => ['3+ to 5 miles (5-8 km)', [3, 5]],
        '5to8' => ['5+ to 8 miles (8-13 km)', [5, 8]],
        '8to10' => ['8+ to 10 miles (13-16 km)', [8, 10]],
        '10to13' => ['10+ to 13 miles (16-21 km)', [10, 13]],
        '13to15' => ['13+ to 15 miles (21-24 km)', [13, 15]],
        'over15' => ['15+ miles (24 km)', [15, 99999]],
    ];

    /**
     * Reads the list of codes to leave out and returns the codes that remain, in list order.
     * Nothing named (or the filter not in the url) leaves everything in. Codes that are not in the list
     * are ignored, so a value retired later does not break old feeds. Leaving out everything is an error.
     */
    public static function remaining(?string $excluded, array $options): array {
        $all = array_keys($options);
        if ($excluded === null || trim($excluded) === '') {
            return $all;
        }
        $left = array_diff($all, array_map('trim', explode(',', strtolower($excluded))));
        if (count($left) === 0) {
            throw new \RuntimeException('Incorrect feed options', 400);
        }
        return array_values($left);
    }

    public static function isAll(array $chosen, array $options): bool {
        return count($chosen) === count($options);
    }

    // [{code, name}, ...] for the javascript
    public static function toList(array $options, bool $nested = false): array {
        $out = [];
        foreach ($options as $code => $value) {
            $out[] = ['code' => (string) $code, 'name' => $nested ? $value[0] : $value];
        }
        return $out;
    }
}
