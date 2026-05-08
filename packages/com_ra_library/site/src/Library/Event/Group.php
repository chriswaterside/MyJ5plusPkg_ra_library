<?php
namespace Ramblers\Component\Ra_library\Site\Library\Event;

/**
 * Description of group
 *
 * @author Chris Vaughan
 */
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Script;
class Group {

    private $arrayofevents;
    private $walkClass = "event";
    static $id = 0;

    function __construct() {
        $this->arrayofevents = array();
    }

    public function setWalkClass($class) {
        $this->walkClass = $class;
    }

    public function addWalks($feed) {
        $walks = $feed->getWalks();
        $arrayofwalks = $walks->allWalks();
        foreach ($arrayofwalks as $walk) {
            $this->arrayofevents[] = $walk;
        }

     //   Script::registerWalks(array_values($arrayofwalks));
    }

    // used by Download ICS
    public function addWalksArray($arrayofwalks) {
        foreach ($arrayofwalks as $walk) {
            $this->arrayofevents[] = $walk;
        }
    }

    public function getEvents() {
        return $this->arrayofevents;
    }

    public function getLastDate() {
        $lastdate = date("Y-m-d");
        foreach ($this->arrayofevents as $event) {
            $currentdate = $event->EventDateYYYYMMDD();
            if ($currentdate > $lastdate) {
                $lastdate = $currentdate;
            }
        }
        return $lastdate;
    }

    public function addEvent($display, $text, $currentDate) {
        $found = false;
        $out = "";
        $class = "even";
        foreach ($this->arrayofevents as $event) {
            if ($event->EventDateYYYYMMDD() === $currentDate) {
                // echo "found";
                if ($found == false) {
                    self::$id += 1;
                    $ident = "ev" . strval(self::$id);
                    $out .= "<div class='event-list-cal-event'>" . PHP_EOL;
                    $out .= "<div class='event-list-cal-day'><a onclick=\"javascript:ra.html.toggleVisibility('" . $ident . "')\">" . $text . "</a></div>" . PHP_EOL;
                    $out .= "<div class='event-list-cal-hover' id='" . $ident . "'>" . PHP_EOL;
                    $out .= $event->EventDate()->format('l, jS');
                }
                $found = true;
                $out .= $event->EventList($display, $class);
                if ($class === "odd") {
                    $class = "even";
                } else {
                    $class = "odd";
                }
            }
        }
        if ($found) {
            $out .= "</div>";
            $out .= "</div>";
            //$out.= "</a>";
        } else {
            $out .= "<div class='event-list-cal-day'>" . $text . "</div>";
            // $out.= $text;
        }
        return $out;
    }

    public function getIcalendarFile($icsfile) {
        foreach ($this->arrayofevents as $event) {
            $event->Event_ics($icsfile);
        }
    }

}
