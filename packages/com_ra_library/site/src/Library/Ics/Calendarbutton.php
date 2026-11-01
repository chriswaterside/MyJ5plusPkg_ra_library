<?php

namespace Ramblers\Component\Ra_library\Site\Library\Ics;

/**
 * Outputs the 'Add to calendar' button, which opens the download / subscribe form
 * (media/js/ra.calendarsubscribe.js). The form builds links to the calendarfeed controller,
 * so no .ics content is created here or in the browser.
 *
 *  - Download: the walks/events the display is showing, sent as a list of ids (at most MAX_IDS).
 *  - Subscribe: the groups of those walks minus anything the user unticked (type, day, grade, distance), kept up to date by the controller.
 */
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Uri\Uri;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;

class Calendarbutton {

    // must match CalendarfeedController::MAX_IDS
    const MAX_IDS = 100;

    public static function getBaseUrl(): string {
        return Uri::root();
    }

    /**
     * Options passed to the javascript, worked out from the walks the display shows ($walks is a Walks collection).
     */
    public static function getOptions($walks, string $calendarName = 'Ramblers Walks'): array {
        $today = (new \DateTime('today', new \DateTimeZone('Europe/London')))->format('Y-m-d');
        $groups = [];
        $ids = [];
        foreach ($walks->allWalks() as $walk) {
            $code = $walk->getIntValue('admin', 'groupCode');
            if ($code) {
                $groups[$code] = ['code' => $code, 'name' => $walk->getIntValue('admin', 'groupName')];
            }
            if ($walk->EventDateYYYYMMDD() >= $today) {
                $ids[] = (string) $walk->getIntValue('admin', 'id');
            }
        }
        return [
            'baseUrl' => self::getBaseUrl(),
            'groups' => array_values($groups),
            'ids' => array_slice($ids, 0, self::MAX_IDS),
            'idsTotal' => count($ids),
            'calendarName' => $calendarName,
            'lists' => self::getLists(),
        ];
    }

    public static function getLists(): array {
        return [
            'types' => Feedfilters::toList(Feedfilters::TYPES),
            'days' => Feedfilters::toList(Feedfilters::DAYS),
            'grades' => Feedfilters::toList(Feedfilters::GRADES),
            'distances' => Feedfilters::toList(Feedfilters::DISTANCES, true),
        ];
    }

    /**
     * Links for ONE walk/event, e.g. for the booking system. $id is the Walks Manager id.
     * download: a one-off file. subscribe: the calendar app keeps it up to date; once the walk is more than
     * a week old the feed holds a single event telling the user to remove the subscription.
     */
    public static function walkUrls($id): array {
        $path = 'index.php?option=com_ra_library&task=calendarfeed.calendarfeed&ids=' . rawurlencode((string) $id);
        $base = self::getBaseUrl();
        return [
            'downloadUrl' => $base . $path . '&download=1',
            'httpsUrl' => $base . $path,
            'webcalUrl' => 'webcal://' . preg_replace('#^https?://#i', '', $base) . $path,
        ];
    }

    public static function loadAssets(): void {
        Load::addScript("media/com_ra_library/js/ra.js");
        Load::addStyleSheet('media/com_ra_library/css/ramblerslibrary.css');
        Load::addScript("media/com_ra_library/js/ra.calendarsubscribe.js");
    }

    /**
     * Returns the html for the button and loads the script/css.
     */
    public static function render(array $options, string $text = 'Add to calendar', string $class = 'icsdownload'): string {
        self::loadAssets();
        $json = htmlspecialchars(json_encode($options), ENT_QUOTES, 'UTF-8');
        return '<div class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">'
                . '<button type="button" class="link-button tiny button mintcake ra-calsub-button" data-options="' . $json . '">'
                . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</button></div>';
    }
}
