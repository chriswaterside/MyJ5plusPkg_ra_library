<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std;

/**
 * Description of WalksDisplay
 *
 * @author Chris Vaughan
 */
use Joomla\CMS\Component\ComponentHelper;
use \Ramblers\Component\Ra_eventbooking\Site\Helper\Ra_eventbookingHelper;
use \Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Displaybase;
use \Ramblers\Component\Ra_library\Site\Library\Leaflet\Map;
use \Ramblers\Component\Ra_library\Site\Library\Load\Load;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Addschema;

// no direct access
defined("_JEXEC") or die("Restricted access");

class Display extends Displaybase {

    public $displayClass = "";
    private $map = null;
    private $id;
    private $customListFormat = null;
    private $customTableFormat = null;
    private $customGradesFormat = null;
    private $customCalendarFormat = null;
    private $customTabOrder = null;

    public function DisplayWalks($walks) {

        $this->id = uniqid(rand());
        $this->map = new Map();
        $this->map->setCommand("ra.display.walksTabs");
        $this->map->help_page = "ledwalks.html";
        $this->map->leafletLoad = false;
        $options = $this->map->options;
        $options->cluster = true;
        $options->displayElevation = false;
        $options->mouseposition = true;
        $options->postcodes = true;
        $options->fitbounds = false;
        $options->settings = true;
        $options->rightclick = true;
        $options->calendar = true;
        $walks->sort(Walk::SORT_DATE, Walk::SORT_TIME, Walk::SORT_DISTANCE);

        $items = $walks->allWalks();
        $display = new Cancelledwalks();
        $number = $this->noCancelledWalks($items);
        if ($number > 3) {
            echo "<div class='cancelledWalks' style='margin-bottom:10px;'>";
            echo "Sorry: We have had to cancel " . $number . " walks.";
            echo "</div>";
        } else {
            $display->DisplayWalks($walks);  // display cancelled walks information
        }
        $data = new class {
            
        };
        $data->walks = array_values($items);

        $data->displayClass = $this->displayClass;
        $data->customGradesFormat = $this->customGradesFormat;
        $data->customCalendarFormat = $this->customCalendarFormat;
        $data->customListFormat = $this->customListFormat;
        $data->customTableFormat = $this->customTableFormat;
        $data->customTabOrder = $this->customTabOrder;
        $data->displayBookingsTable = false;
        if (ComponentHelper::isEnabled('com_ra_eventbooking')) {
            $data->displayBookingsTable = Ra_eventbookingHelper::canEdit();
        }

        $this->map->setDataObject($data);
        $this->map->display();
        Load::addScript("media/com_ra_library/js/jsonwalks/std/display.js");
        Load::addScript("media/com_ra_library/js/vendors/cvList/cvList.js");
        Load::addStyleSheet("media/com_ra_library/js/vendors/cvList/cvList.css");
        Load::addScript("media/com_ra_library/js/ra.tabs.js");
        Load::addStyleSheet("media/com_ra_library/css/ra.tabs.css");
        $schema = new Addschema();
        $schema->display($walks);
    }

    public function setWalksClass($value) {
        $this->displayClass = $value;
    }

    public function setTabOrder($value) {
        $this->customTabOrder = $value;
    }

    public function setCustomListFormat($value) {
        $this->customListFormat = $value;
    }

    public function setCustomTableFormat($value) {
        $this->customTableFormat = $value;
    }

    public function setCustomGradesFormat($value) {
        $this->customGradesFormat = $value;
    }

    public function setCustomCalendarFormat($value) {
        $this->customCalendarFormat = $value;
    }

    private function addGotoWalk() {
        // no longer used
    }

    private function noCancelledWalks($walks) {
        $number = 0;
        foreach ($walks as $walk) {
            if ($walk->isCancelled()) {
                $number += 1;
            }
        }
        return $number;
    }
}
