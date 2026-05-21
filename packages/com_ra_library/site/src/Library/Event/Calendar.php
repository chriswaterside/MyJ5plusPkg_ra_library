<?php
namespace Ramblers\Component\Ra_library\Site\Library\Event;
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

class Calendar extends Displaybase {

    private $size;
    private $mDisplayAll = false;
    private $monthFormat = "F 'y";

    function __construct($size) {
        parent::__construct();
        $this->size = $size;
    }

    function DisplayWalks($walks) {
        echo "REventCalendar: this way of using this class is not supported";
    }

    public function displayAll() {
        $this->mDisplayAll = true;
    }

    public function setMonthFormat($format) {
        if (is_string($format)) {
            $this->monthFormat = $format;
        }
    }

    function Display($events) {
        Load::addStyleSheet('media/com_ra_library/js/calendar/calendar.css');
        Load::addScript("media/com_ra_library/js/ra.js");
        Load::addStyleSheet('media/com_ra_library/css/ramblerslibrary.css');
        $cal = new displayCalendar($this->size, $this->mDisplayAll);
        $cal->setMonthFormat($this->monthFormat);
        $cal->show($this, $events);
    }

}
