<?php
namespace Ramblers\Component\Ra_library\Site\Library\Organisation;

/**
 * Description of areas
 *
 * @author Chris
 */
use Ramblers\Component\Ra_library\Site\Library\Load\Load;

use Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

class Organisation {

    private $srfr;
    public $groups=[];
    public $areas=[];
    public $showLinks = true;
    public $showCodes = true;
    public $showGroups = true;
    public $colourMyGroup = '#ff0000';
    public $colourMyArea = '#00ff00';
    public $colourOtherGroups = '#0000ff';
    Public $centreGroup = "";
    public $mapZoom = -1;
//    private $totals = null;
//    private $statAreas = [];

    function __construct() {
        $this->load();
    }

    public function load() {
        $url = "https://groups.theramblers.org.uk/";
        $CacheTime = 60; // 60 minutes
        $cacheLocation = $this->CacheLocation();
        $this->srfr = new RFeedhelper($cacheLocation, $CacheTime);
// $this->srfr->setReadTimeout(60);
        $this->readFeed($url);
    }

    private function readFeed($rafeedurl) {

        $result = $this->srfr->getFeed($rafeedurl, "Ramblers Organisation");
        $status = $result["status"];
        $contents = $result["contents"];

        switch ($status) {
            case RFeedhelper::OK:
                break;
            case RFeedhelper::READFAILED:
                   $app = Factory::getApplication();
                $app->enqueueMessage(Text::_('Unable to fetch organisation data, data may be out of date: ' . $rafeedurl), 'warning');
                $contents = "[]";
                break;
            case RFeedhelper::FEEDERROR;
                   $app = Factory::getApplication();
                $app->enqueueMessage(Text::_('Feed must use HTTP protocol: ' . $rafeedurl), 'error');
                $contents = "[]";
                break;
            case RFeedhelper::FEEDFOPEN:
                   $app = Factory::getApplication();
                $app->enqueueMessage(Text::_('Not able to read feed using fopen: ' . $rafeedurl), 'error');
                $contents = "[]";
                break;
            default:
                break;
        }
        switch ($contents) {
            case NULL:
                $app = Factory::getApplication();
                $app->enqueueMessage(Text::_('Groups feed: Unable to read feed: ' . $rafeedurl), 'error');
                break;
            case "":
                echo '<b>Groups feed: No Groups found</b>';
                break;
            case "[]":
                echo '<b>Groups feed empty: No Groups found</b>';
                break;
            default:
                $json = json_decode($contents);
                unset($contents);
                $error = 0;
                if (json_last_error() == JSON_ERROR_NONE) {
                    foreach ($json as $value) {
                        $ok = $this->checkJsonProperties($value);
                        $error += $ok;
                    }
                    if ($error > 0) {
                         $app = Factory::getApplication();
                            $app->enqueueMessage(Text::_('Groups feed: Json file format not supported: ' . $rafeedurl), 'error');
                    } else {
                        $this->convert($json);
                    }
                    unset($json);
                    break;
                } else {
                      $app = Factory::getApplication();
                    $app->enqueueMessage(Text::_('Groups feed: feed is not in Json format: ' . $rafeedurl), 'error');
                }
        }
    }

    private function convert($json) {
        $this->areas = array();
        $this->groups = array();
        foreach ($json as $item) {
            if ($item->scope == "A") {
                $group = new ROrganisationArea($item);
                $this->areas[$item->groupCode] = $group;
                $this->groups[$item->groupCode] = $group;
            }

            if ($item->scope <> "A") {
                $group = new ROrganisationGroup($item);
                $this->groups[$item->groupCode] = $group;

                $areacode = substr($item->groupCode, 0, 2);
                if (isset($this->areas[$areacode])) {
                    $area = $this->areas[$areacode];
                    $area->addGroup($group);
                }
            }
        }
        ksort($this->groups);
        ksort($this->areas);
        foreach ($this->areas as $area) {
            $area->sort();
        }
    }

    public function listAreas() {
        echo "<table>";
        echo RHtml::addTableHeader(["Area", "Group", "Hosted Web Site", "Status"]);
        foreach ($this->areas as $area) {
            $code = "";
            if ($this->showCodes) {
                $code = "Area " . $area->code . ": ";
            }

            echo RHtml::addTableRow([$code . $area->getLink($this->showLinks), " ", $area->website, $area->status]);

            if ($this->showGroups) {
                $code = "";
                foreach ($area->groups as $group) {
                    if ($this->showCodes) {
                        switch ($group->scope) {
                            case "G":
                                $code = "Group " . $group->code . ":  ";
                                break;
                            case "S":
                                $code = "Special Group " . $group->code . ":  ";
                                break;
                            default:
                                $code = "Other Group " . $group->code . ":  ";
                                break;
                        }
                    }
                    echo RHtml::addTableRow(["", $code . $group->getLink($this->showLinks), $group->website, $group->status]);
                }
            }
        }
        echo "</table>";
    }

    public function display($map) {
        if (isset($map)) {
            Load::addScript("media/com_ra_library/js/organisation/organisation.js");
            Load::addStyleSheet('media/com_ra_library/css/ramblerslibrary.css');
            $map->setCommand('ra.display.organisationMap');
            $map->help_page = "";
            $map->options->fullscreen = true;
            $map->options->mouseposition = true;
            $map->options->rightclick = true;
            $map->options->fitbounds = true;
            $map->options->displayElevation = false;
            $map->options->cluster = true;
            $map->options->mylocation = true;
            $map->options->settings = true;
            $map->options->draw = true;
            $map->options->print = true;
            //   $map->options->ramblersPlaces = true;

            $data = new class {
                
            };
            echo "<h4>Map of Ramblers Areas and Groups</h4>";
            $data->areas = $this->areas;
            $map->setDataObject($data);
            $map->display();
        }
    }

    public function myGroup($myGroup, $zoom) {
        $myGroup = strtoupper($myGroup);
        $map = new RLeafletMap();

        Load::addScript("media/com_ra_library/js/organisation/organisation.js");
        Load::addStyleSheet('media/com_ra_library/css/ramblerslibrary.css');
        $map->setCommand('ra.display.organisationMyGroup');
        $map->help_page = "";
        $map->options->fullscreen = true;
        $map->options->mouseposition = false;
        $map->options->rightclick = false;
        $map->options->fitbounds = true;
        $map->options->displayElevation = false;
        $map->options->cluster = false;
        $map->options->mylocation = false;
        $map->options->settings = false;
        $map->options->draw = true;
        $map->options->print = false;
        $map->options->ramblersPlaces = true;
        $data = new class {
            
        };
        $myArea = substr($myGroup, 0, 2);
        $data->myGroups = [];
        $data->nearByGroups = [];
        $data->myGroup = $myGroup;
        $data->myArea = $myArea;
        $data->areas = $this->areas;
        $data->colours = new class {
            
        };
        $data->colours->myGroup = $this->colourMyGroup;
        $data->colours->myArea = $this->colourMyArea;
        $data->colours->otherGroups = $this->colourOtherGroups;
        $data->zoom = $zoom;
        $map->setDataObject($data);
        $map->display();
    }

//    private function distance($lata, $lona, $latb, $lonb) {
//        $r = 6371.009; // convert to KM
//        $lat1 = deg2rad($lata);
//        $lon1 = deg2rad($lona);
//        $lat2 = deg2rad($latb);
//        $lon2 = deg2rad($lonb);
//        $lonDelta = $lon2 - $lon1;
//        $a = pow(cos($lat2) * sin($lonDelta), 2) + pow(cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($lonDelta), 2);
//        $b = sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($lonDelta);
//        $angle = atan2(sqrt($a), $b);
//        return $angle * $r;
//    }

//    private function getStats() {
//
//        $this->initialiseAreas();
//        foreach ($this->statAreas as $area) {
//            $this->processArea($area);
//        }
//        $this->processTotals($this->statAreas);
//        // $json = json_encode($this->areas, JSON_PRETTY_PRINT);
//        $jsontotal = json_encode($this->totals, JSON_PRETTY_PRINT);
//        echo "<h3>Results</h3>";
//        echo "<pre>";
//        //  echo $json;
//        echo $jsontotal;
//        echo "</pre>";
//        echo 'End of results';
//    }

//    private function initialiseAreas() {
//        $this->statAreas = [];
//        foreach ($this->areas as $area) {
//            $this->initialiseArea($area);
//        }
//        $this->totals = $this->createGroup("Totals", "Totals", false);
//    }

//    private function initialiseArea($area) {
//        $newArea = $this->createGroup($area->code, $area->name, true);
//        $this->statAreas[$newArea->code] = $newArea;
//        $this->initialiseGroups($newArea, $area->groups);
//    }

//    private function initialiseGroups($newArea, $groups) {
//        $newGroup = $this->createGroup($newArea->code, $newArea->name, false);
//        $newArea->groups[$newArea->code] = $newGroup;
//        foreach ($groups as $group) {
//            $newGroup = $this->createGroup($group->code, $group->name, false);
//            $newArea->groups[$group->code] = $newGroup;
//        }
//    }

    private function createGroup($code, $name, $groups) {
        $group = new class {
            
        };
        $group->code = $code;
        $group->name = $name;
        $group->noWalks = 0;
        $group->additionalNotes = 0;
        $group->distanceZero = 0;
        $group->hasMeetPlace = 0;
        $group->hasOnlyMeetPlace = 0;
        $group->hasExactMeetPlace = 0;
        $group->hasExactStartPlace = 0;
        $group->hasFinishPlace = 0;
        $group->hasNeither = 0;
        $group->isLinear = 0;
        $group->pace = 0;
        $group->ascentFeet = 0;
        $group->localGrade = 0;
        $group->suitability = 0;
        $group->strands = 0;
        $group->festivals = 0;
        $group->surroundings = 0;
        $group->theme = 0;
        $group->specialStatus = 0;
        $group->facilities = 0;
        $group->cancelInTitle = 0;
        if ($groups) {
            $group->groups = [];
        }
        return $group;
    }

//    private function processArea($area) {
//        $url = "https://www.ramblers.org.uk/api/lbs/walks?groups=" . $area->code;
//        $CacheTime = 180; // 60 minutes
//        $cacheLocation = $this->CacheLocation();
//        $this->srfr = new RFeedhelper($cacheLocation, $CacheTime);
//        $result = $this->srfr->getFeed($url, "Area Walks");
//        $json = RErrors::checkJsonFeed($url, "Walks", $result, []);
//        If ($json !== null) {
//            $walks = new RJsonwalksWalks($json);
//            unset($json);
//            $this->processAreaWalks($area, $walks);
//        }
//    }

//    private function processAreaWalks($area, $walks) {
//
//        $totals = $this->totals;
//        $items = $walks->allWalks();
//        foreach ($items as $walk) {
//            if (!array_key_exists($walk->groupCode, $area->groups)) {
//                echo "<p>Group code not found " . $walk->groupCode . " - " . $walk->groupName;
//                $group = $this->createGroup($walk->groupCode, $walk->groupName, false);
//                $area->groups[$walk->groupCode] = $group;
//            }
//            $area->noWalks += 1;
//            $area->groups[$walk->groupCode]->noWalks += 1;
//            $totals->noWalks += 1;
//
//            $this->setValues($totals, $area, $walk, 'additionalNotes', 'additionalNotes', '!==', "");
//            $this->setValues($totals, $area, $walk, 'pace', 'pace', '!==', "");
//            $this->setValues($totals, $area, $walk, 'localGrade', 'localGrade', '!==', "");
//            $this->setValues($totals, $area, $walk, 'ascentFeet', 'ascentFeet', '!==', null);
//            $this->setValues($totals, $area, $walk, 'distanceZero', 'distanceMiles', '<=', 0);
//            $this->setValues($totals, $area, $walk, 'hasMeetPlace', 'hasMeetPlace', '===', true);
//            $this->setValues($totals, $area, $walk, 'isLinear', 'isLinear', '===', true);
//            if ($walk->hasExactMeetPlace) {
//                $this->setValues($totals, $area, $walk, 'hasOnlyMeetPlace', 'hasExactStartPlace', '===', false);
//                if (!$walk->hasExactStartPlace) {
//                    $a = 1;
//                }
//            }
//            $this->setValues($totals, $area, $walk, 'hasExactMeetPlace', 'hasExactMeetPlace', '===', true);
//            $this->setValues($totals, $area, $walk, 'hasExactStartPlace', 'hasExactStartPlace', '===', true);
//            $this->setValues($totals, $area, $walk, 'hasNeither', 'hasNeither', '===', true);
//            $this->setValues($totals, $area, $walk, 'hasFinishPlace', 'hasFinishPlace', '===', true);
//            $this->setValues($totals, $area, $walk, 'suitability', 'suitability', '!==', null);
//            $this->setValues($totals, $area, $walk, 'strands', 'strands', '!==', null);
//            $this->setValues($totals, $area, $walk, 'festivals', 'festivals', '!==', null);
//            $this->setValues($totals, $area, $walk, 'surroundings', 'surroundings', '!==', null);
//            $this->setValues($totals, $area, $walk, 'theme', 'theme', '!==', null);
//            $this->setValues($totals, $area, $walk, 'specialStatus', 'specialStatus', '!==', null);
//            $this->setValues($totals, $area, $walk, 'facilities', 'facilities', '!==', null);
//            //      $this->setValues($totals, $area, $walk, 'cancelInTitle', 'title', 'contains', 'cancel');
//        }
//    }

//    private function setValues($totals, $area, $walk, $statsproperty, $property, $test, $value) {
//        $set = false;
//        $testvalue = $walk->$property;
//        switch ($test) {
//            case '!==':
//                if ($testvalue !== $value) {
//                    $set = true;
//                }
//                break;
//            case '>':
//                if ($testvalue > $value) {
//                    $set = true;
//                }
//                break;
//            case '<=':
//                if ($testvalue <= $value) {
//                    $set = true;
//                }
//                break;
//            case '===':
//                if ($testvalue === $value) {
//                    $set = true;
//                }
//                break;
//            case 'contains':
//                if (strpos($testvalue, $value) !== false) {
//                    $set = true;
//                }
//                break;
//        }
//        if ($set) {
//            $area->$statsproperty += 1;
//            $area->groups[$walk->groupCode]->$statsproperty += 1;
//            $totals->$statsproperty += 1;
//        }
//    }

//    private function processTotals($areas) {
//        $totalNoGroups = 0;
//        $totalNoAreas = 0;
//        $totalGWEMGroups = 0;
//        foreach ($areas as $area) {
//            $areaNoGroups = 0;
//            $areaGWEMGroups = 0;
//            $totalNoAreas += 1;
//            foreach ($area->groups as $group) {
//                $areaNoGroups += 1;
//                $totalNoGroups += 1;
//                if ($group->noWalks > 0) {
//                    $areaGWEMGroups += 1;
//                    $totalGWEMGroups += 1;
//                }
//            }
//            $area->NoGroupsUsingGWEM = $areaGWEMGroups;
//            $area->NoGroups = $areaNoGroups;
//        }
//        $this->totals->NoGroupsUsingGWEM = $totalGWEMGroups;
//        $this->totals->NoGroups = $totalNoGroups;
//        $this->totals->NoAreas = $totalNoAreas;
//    }

//    public function displayStats() {
//
//        echo "<h4>Ramblers Areas and Groups - Walks statistics</h4>";
//        $this->getStats();
//    }

    private function checkJsonProperties($item) {
        $properties = array("scope", "groupCode", "name", "url", "description", "latitude",
            "longitude");

        foreach ($properties as $value) {
            if (!$this->checkJsonProperty($item, $value)) {
                return 1;
            }
        }

        return 0;
    }

    private function checkJsonProperty($item, $property) {
        if (property_exists($item, $property)) {
            return true;
        }
        return false;
    }

    private function CacheLocation() {
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        return 'cache' . DS . 'ra_groups';
    }

}
