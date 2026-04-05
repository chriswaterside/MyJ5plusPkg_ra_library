<?php
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks;

/**
 * Get groups information and find date of last update for walks
 *
 * @author ChrisV
 */
use Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
//define("GROUPSFEED", "https://uat-be.ramblers.nomensa.xyz/api/volunteers/groups?groups=");
define("APIKEY", "9a68085e6f159f6b2ecd0b7533805282");
define("GROUPSFEED", "https://walks-manager.ramblers.org.uk/api/volunteers/groups?groups=");

// https://walks-manager.ramblers.org.uk/api/volunteers/groups?groups=DE01,DE02,DE03&api-key=9a68085e6f159f6b2ecd0b7533805282

class Groups {

    private $date = '1970-01-01T00:00:00';

    public function __construct($groups) {

        $this->date = self::getLastUpdateDateTime($groups);
    }

    public function getUpdateDate() {
        return $this->date;
    }

    public static function getLastUpdateDateTime($groups) {
        $feeditems = self::getJsonFeed($groups);
        $date = "1970-01-01T00:00:00";
        foreach ($feeditems as $item) {
            if ($item->date_walks_events_updated > $date) {
                $date = $item->date_walks_events_updated;
            }
        }

        return $date;
    }

    public static function getJsonFeed($groups) {
        $feedurl = GROUPSFEED . strtoupper($groups) . "&api-key=" . APIKEY;
        $json = file_get_contents($feedurl);
        if ($json === false) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('Walks: Unable to determine if walks are up to date.'), 'warning');
            return null;
        }
        // echo "---- Feed read" ;
        $items = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('Walks: Groups feed returning incorrect format data.'), 'error');
            $app->enqueueMessage(Text::_('Walks: Unable to determine if walks are up to date.'), 'warning');
            return null;
        }
        return $items;
    }

}
