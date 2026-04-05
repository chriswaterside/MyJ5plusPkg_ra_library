<?php

/**
 * Description of Calendar
 *
 * @author Chris Vaughan
 */
// no direct access
defined("_JEXEC") or die("Restricted access");

class RJsonwalksStdCalendarDEL extends RJsonwalksDisplaybase {

    public $tag = "p";
    public $startText = "Number of walks: ";
    public $endText = "";
    public function __construct() {
        parent::__construct();
    }

   public function DisplayWalks($walks) {
        define('CR', ' - ');
        $output = "BEGIN:VCALENDAR\nVERSION:2.0\nMETHOD:PUBLISH\n";
        $items = $walks->allWalks();
        foreach ($items as $walk) {
            $startTime = $walk->walkDate->format('d F Y') . " " . $walk->startLocation->time->format('H:i');
            $durationFullMins = round($walk->distanceMiles * 60 / 2);
            $durationMins = $durationFullMins % 60;
            $durationHours = ($durationFullMins - $durationMins) / 60;
            $intervalFormat = "PT" . $durationFullMins . "M";
            $interval = new DateInterval($intervalFormat);
            $finishTime = $walk->walkDate->format('d F Y') . " " . $walk->startLocation->time->add($interval)->format('H:i');
            $description = strip_tags($walk->description) . " " . CR .
                    $walk->contactName . " (" . $walk->telephone1 . " " . $walk->telephone2 . ")" . CR .
                    $walk->distanceMiles . " miles - " . $walk->nationalGrade;
            if ($walk->additionalNotes != '') {
                $description = $description . " - " . strip_tags($walk->additionalNotes);
            }
            if ($walk->startLocation->exact) {
                if ($walk->startLocation->description !== '') {
                    $startLocation = $walk->startLocation->description . ' (' . $walk->startLocation->gridref . ')';
                } else {
                    $startLocation = $walk->startLocation->gridref;
                }
            } else {
                $startLocation = ".";
            }

            // Ensure there are no special characters in the code.
            $description = preg_replace('/[^A-Za-z0-9\-.:]/', ' ', htmlspecialchars_decode($description));

            $event = new EVENT($walk->id, $startTime, $finishTime, $walk->title, $description, $startLocation, $walk->detailsPageUrl);
            $output = $output . $event->data;
            unset($walk);
        }


        $output = $output . "END:VCALENDAR\n";
        $length = strlen($output);
        header("Content-type:text/calendar");
        header('Content-Disposition: attachment; filename=walks.ics"');
        Header('Content-Length: ' . $length);
        Header('Connection: close');
        echo $output;
    }

}

class EVENT {

    var $data;
    var $name;

    function EVENT($id, $start, $end, $name, $description, $location, $url) {
        $this->name = $name;
        $this->data = "BEGIN:VEVENT\n";
        $this->data = $this->data . "DTSTART:" . date("Ymd\THis\Z", strtotime($start)) . "\n";
        $this->data = $this->data . "DTEND:" . date("Ymd\THis\Z", strtotime($end)) . "\n";
        $this->data = $this->data . "LOCATION:" . $location . "\n";
        $this->data = $this->data . "TRANSP: OPAQUE\n";
        $this->data = $this->data . "SEQUENCE:0\n";
        // $this->data = $this->data . "ID:" . $id . "\n";
        $this->data = $this->data . "DTSTAMP:" . date("Ymd\THis\Z") . "\n";
        $this->data = $this->data . "SUMMARY:" . $name . "\n";
        $this->data = $this->data . "DESCRIPTION:" . $description . "\n";
        $this->data = $this->data . "PRIORITY:1\n";
        $this->data = $this->data . "URL;VALUE=URI:" . $url . "\n";
        $this->data = $this->data . "CLASS:PUBLIC\n";
//        $this->data = $this->data . "BEGIN:VALARM\n";
//        $this->data = $this->data . "TRIGGER:-PT10080M\n";
//        $this->data = $this->data . "ACTION:DISPLAY\n";
//        $this->data = $this->data . "DESCRIPTION:Reminder\n";
//        $this->data = $this->data . "END:VALARM\n";
        $this->data = $this->data . "END:VEVENT\n";
    }

    function escapeString($string) {
        return preg_replace('/([\,;])/', '\\\$1', $string);
    }

    function save() {
        file_put_contents($this->name . ".ics", $this->data);
    }

    function show() {
        header("Content-type:text/calendar");
        header('Content-Disposition: attachment; filename="' . $this->name . '.ics"');
        Header('Content-Length: ' . strlen($this->data));
        Header('Connection: close');
        echo $this->data;
    }

}

//error handler function
function customError($errno, $errstr) {
    echo "<b>Error:</b> [$errno] $errstr";
}
