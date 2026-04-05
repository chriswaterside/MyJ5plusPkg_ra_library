<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std;

/**
 * Description of areawalks
 *
 * @author Chris Vaughan
 */
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;
use Joomla\CMS\Factory;

class Areawalks extends Displaybase {

    public function DisplayWalks() {

        $app = Factory::getApplication();
        Load::addScript("media/com_ra_library/js/ra.js", "text/javascript");
        Load::addScript("media/com_ra_library/js/ramblerswalks.js", "text/javascript");

        // Set the cookie
        //   $time = time() + 604800; // 1 week
        // Get the cookie
        $area = $app->input->cookie->get('AreaCode', null);
        //    $ok = $app->input->cookie->set('AreaCode', '',time() - 1);
        //setcookie('AreaCode', '');
        if ($area == null) {
            $area = '';
        }
// If there's no cookie value, manually set it
        if ($area == '') {
            $this->SetAreaCode();
        } else {
            // echo "<p>Area code is $area</p>";
            $rafeedurl = "http://www.ramblers.org.uk/api/lbs/walks?groups=" . $area;
            $feed = new RJsonwalksFeed($rafeedurl); // standard software to read json feed and decode file
            $display = new RJsonwalksStdDisplay(); // code to display the walks in tabbed format
            $display->displayGroup = true; // include Group name in the display 
            $feed->Display($display); // display walks information
        }
    }

    private function SetAreaCode() {
        $groupsurl = "https://groups.theramblers.org.uk/";
        $CacheTime = 15; // minutes
        $time = getdate();
        if ($time["hours"] < 7) {
            $CacheTime = 7200; // 12 hours, rely on cache between midnight and 7am
        }
        $cacheLocation = $this->CacheLocation();
        $groupfeed = new RFeedhelper($cacheLocation, $CacheTime);
        $result = $groupfeed->getFeed($groupsurl, "Area/Group Walks");
        //$status = $result["status"];
        $contents = $result["contents"];
        $feedTitle = 'Walks';
        switch ($contents) {
            case NULL:
                RErrors::notifyError($feedTitle . ' feed: Unable to read feed [Null response, Error 1]', $feed, 'error');
                break;
            case "":
                echo '<b>No ' . $feedTitle . ' found.</b> [Error 2]';
                break;
            case "[]":
                // echo '<b>Sorry no ' . $feedTitle . ' found</b>';
                break;
            default:
                $json = json_decode($contents);
                unset($contents);
                $error = json_last_error();
                if ($error == JSON_ERROR_NONE) {
                    break;
                } else {
                    $errormsg = json_last_error_msg();
                    RErrors::notifyError('Feed is not in Json format: ' . $errormsg . ' [Error 4]', $feed, 'error');
                }
                return;
        }
        echo '<div id="js-areamsg"> ';
        echo '<label for="areas">Choose a Ramblers Area:</label>';
        echo '<select name="areas" id="js-areas" onchange="saveAreaCode()">';

        usort($json, function ($a, $b) {
            return strcmp($a->name, $b->name);
        });
        foreach ($json as $group) {
            if ($group->scope == "A") {
                $name = $group->name;
                $code = $group->groupCode;
                //    echo '<option value="' . $code . '">' . $name . '</option>';
            }
        }

        $this->map = new RLeafletMap();
        $this->map->setCommand("loadDisplayAreaWalks");
        $map->options->fullscreen = true;
        $map->options->cluster = false;
        $map->options->mouseposition = false;
        $map->options->postcodes = false;
        $map->options->fitbounds = false;
        $map->options->displayElevation = false;
        $map->options->print = false;
        $data = new class {
            
        };
        $data->areas = $json;

        $this->map->setDataObject($data);
        $map->display();
        echo '</div> ';
    }

    private function CacheLocation() {
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        return 'cache' . DS . 'ra_groups';
    }
}
