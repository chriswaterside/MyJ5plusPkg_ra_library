<?php
namespace Ramblers\Component\Ra_library\Site\Library\Event;
/**
 * Description of EventDownload
 * This class provides a method of displaying a link so the use can download an iCalendar file
 * containing their events
 *
 * @author Chris Vaughan
 */
// no direct access
defined("_JEXEC") or die("Restricted access");
use Ramblers\Component\Ra_library\Site\Library\Ics\Output;

class Feed extends Output {

    public function __construct() {
        
    }

    public function output($events) {
        $events->getIcalendarFile($this);
        return parent::text();
    }

    public function getText($events) {
        $icsfile = new Output();
        $events->getIcalendarFile($icsfile);
        return $icsfile->text();
    }

}
