<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std;

/**
 * Description of WalksCalendar
 *
 * @author Chris Vaughan
 */
// no direct access
defined("_JEXEC") or die("Restricted access");

use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Displaybase;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;
use Ramblers\Component\Ra_library\Site\Library\Calendar\Calendar as displayCalendar;
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Script;

class Calendar extends Displaybase {

    private $size = 0;
    private $mDisplayAll = false;
    private $monthFormat = "F 'y";

    public function __construct() {
        parent::__construct();
    }

    public function DisplayWalks($walks) {
        Load::addStyleSheet('media/com_ra_library/js/calendar/calendar.css');
        Load::addScript("media/com_ra_library/js/ra.js");
        Load::addStyleSheet('media/com_ra_library/css/ramblerslibrary.css');
        $lastdate = $walks->getLastDate();
        Script::registerWalks($walks->allWalks());
        $cal = new displayCalendar($this->size, $this->mDisplayAll);
        $cal->setMonthFormat($this->monthFormat);
        $cal->show($this, $walks, $lastdate);
    }

    public function setSize($size) {
        $this->size = $size;
    }

    public function displayAll() {
        $this->mDisplayAll = true;
    }

    public function setMonthFormat($format) {
        if (is_string($format)) {
            $this->monthFormat = $format;
        }
    }
}
