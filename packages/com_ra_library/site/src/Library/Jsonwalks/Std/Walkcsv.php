<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std;

/**
 * Description of WalksDisplay
 *    Create a csv file containing the walks and display a button to allow user to download csv file
 * 
 * @author Chris Vaughan
 * 
 */
// no direct access
defined("_JEXEC") or die("Restricted access");
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Displaybase;


class Walkcsv extends RJsonwalksDisplaybase {

    private $filename;
    private $buttonClass = "button-p1815";
    public $removeHTML = false;
    public $convertToASCII = false;

    public function __construct($filename = "tmp/walks-download") {
        parent::__construct();
        $this->filename = $filename . (new DateTime())->format('YmdHis') . ".csv";
    }

    public function DisplayWalks($walks) {
        $handle = fopen($this->filename, 'w'); //open file for writing 
        If ($handle === false) {
            die('Cannot open file:  ' . $this->filename);
        } else {
            $walks->sort(RJsonwalksWalk::SORT_DATE, RJsonwalksWalk::SORT_TIME, RJsonwalksWalk::SORT_DISTANCE);
            $items = $walks->allWalks();
            $fields = $this->csvHeader();

            fputcsv($handle, $fields);
            foreach ($items as $walk) {
                $fields = $this->displayWalkCSV($walk);
                fputcsv($handle, $fields);
            }
            fclose($handle);
            $this->createButton();
        }
    }

    private function displayWalkCSV($walk) {
        $array = [];
        $array[] = $walk->getIntValue("basics", "walkDate")->format('d/m/Y');
        $array[] = $walk->getIntValue("basics", "title");
        $description = $walk->getIntValue("basics", "descriptionHtml");
        if ($this->removeHTML) {
            $array[] = html_entity_decode(strip_tags($description));
        } else {
            $array[] = $description;
        }
        $array[] = $walk->getIntValue("basics", "additionalNotes");
        $array[] = ""; // link
        $array[] = $walk->getIntValue("contacts", "contactName");
        $array[] = $walk->getIntValue("walks", "shape");
        $array[] = $walk->getIntValue("start", "textTime");
        $array[] = $walk->getIntValue("start", "lat/long");
        $array[] = ""; // latitude/longitude
        $array[] = "";
        $array[] = $walk->getWalkValue("{startPlace}");

        $array[] = $walk->getIntValue("meeting", "textTime");
        $array[] = $walk->getIntValue("meeting", "lat/long");
        $array[] = ""; // latitude/longitude
        $array[] = "";
        $array[] = $walk->getWalkValue("{meetPlace}");

        $array[] = $walk->getintValue("finish", "textTime");
        $array[] = $walk->getIntValue("finish", "lat/long");
        $array[] = ""; // latitude/longitude
        $array[] = "";
        $array[] = $walk->getWalkValue("{finishPlace}");

        $array[] = $walk->getintValue("walks", "nationalGrade");
        $array[] = $walk->getintValue("walks", "distanceMiles");
        $array[] = "";

        $array[] = $walk->getIntValue("walks", "ascent");
        $array[] = "";
        $flags = $this->flags();
        $set = $walk->flagsExists($flags);
        foreach ($set as $item) {
            if ($item) {
                $array[] = "True";
            } else {
                $array[] = "False";
            }
        }



        return $array;
    }

    private function csvHeader() {
        return ["Date", "Title", "Description", "Additional details", "Website Link", "Walk leaders", "Linear or Circular", "Start time", "Starting location", "Starting postcode", "Starting gridref", "Starting location details", "Meeting time", "Meeting location", "Meeting postcode", "Meeting gridref", "Meeting location details", "Est finish time", "Finishing location", "Finishing postcode", "Finishing gridref", "Finishing location details", "Difficulty", "Distance km", "Distance miles", "Ascent metres", "Ascent feet", "Dog friendly", "Introductory walk", "No stiles", "Family-friendly", "Wheelchair accessible", "Accessible by public transport", "Car parking available", "Car sharing available", "Coach trip", "Refreshments available (Pub/cafe)", "Toilets available"];
    }

    private function flags() {
        return ["Dog friendly", "Introductory walk", "No stiles", "Family-friendly", "Wheelchair accessible", "Accessible by public transport", "Car parking available", "Car sharing available", "Coach trip", "Refreshments available (Pub/cafe)", "Toilets available"];
    }

    private function createButton() {
        echo '<a href="' . $this->filename . '" class="link-button ' . $this->buttonClass . '">Download walks CSV</a>';
    }

}
