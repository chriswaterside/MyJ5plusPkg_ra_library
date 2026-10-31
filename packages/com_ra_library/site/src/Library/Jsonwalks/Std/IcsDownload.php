<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std;

/**
 * Description of EventDownload
 * This class provides a method of displaying a link so the use can download an iCalendar file
 * containing their events
 *
 * No longer writes a cached .ics file. It outputs the 'Add to calendar' button,
 * which opens the download / subscribe form; the .ics is created on request by the
 * calendarfeed controller (index.php?option=com_ra_library&task=calendarfeed.calendarfeed).
 *
 * @author Chris Vaughan
 */
// no direct access
defined("_JEXEC") or die("Restricted access");

use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Displaybase;
use Ramblers\Component\Ra_library\Site\Library\Ics\Calendarbutton;

class IcsDownload extends Displaybase {

    private $linktext = "Add to calendar";

    public function setLinkText($value) {
        $this->linktext = $value;
    }

    #[\Override]
    public function DisplayWalks($walks) {
        $options = Calendarbutton::getOptions($walks);  // groups and ids come from the walks shown
        echo Calendarbutton::render($options, $this->linktext);

    }
}
