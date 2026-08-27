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
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Displaybase;
use Ramblers\Component\Ra_library\Site\Library\Ics\Output;

class Download extends Displaybase {

    private $linktext = "Download Calendar";
    private $pretext = "";
    private $posttext = "";

    public function __construct() {
 
    }

    public function setLinkText($value) {
        $this->linktext = $value;
    }

    public function setPreText($value) {
        $this->pretext = $value;
    }

    public function setPostText($value) {
        $this->posttext = $value;
    }

    public function DisplayWalks($walks) {
        echo "REventDownload: this way of using this class is not supported";
    }

    public function Display($filename, $events) {
        $icsfile = new RIcsFile($filename);
        if (!$icsfile->exists()) {
            $events->getIcalendarFile($icsfile);
            $icsfile->write();
        }
        echo "<div class='icsdownload'>";
        echo $this->pretext;
        echo $icsfile->getFileLink($this->linktext);
        echo $this->posttext;
        echo "</div>";
    }

    public function getText($events) {
        $icsfile = new Output();
        $events->getIcalendarFile($icsfile);
        return $icsfile->text();
    }

}
