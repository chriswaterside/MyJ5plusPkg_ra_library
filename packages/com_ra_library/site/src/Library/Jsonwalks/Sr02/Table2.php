<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Sr02;

/**
 * Description of RJsonwalksStdWalktable3col
 *
 * @author Chris Vaughan
 */
// no direct access
defined("_JEXEC") or die("Restricted access");

use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std\Walktable;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk;
use Joomla\CMS\Factory;

class Table2 extends Walktable {

    private $tableFormat = [
        ['title' => 'Date/Time', 'items' => '{dowddmm}{<br> ?startTime}'],
        ['title' => 'Leader/Contact', 'items' => '{xContact}{<br> ?telephone1}{<br> ?telephone2}'],
        ['title' => 'Details', 'items' => '{title}{<br> ?description}{lf}{Grid Ref: ?startGR}{ Postcode ?startPC}{<br> ?additionalNotes}'],
        ['title' => 'Distance', 'items' => '{distance}{<br> ?xNationalGrade}{xSymbol}']
    ];

    public function __construct() {
        parent::__construct();
        parent::customFormat($this->tableFormat);
        parent::setRowClass($this, 'rowClass');
        Walk::setCustomValues($this, "customValue");
        $document = Factory::getDocument();
        $document->addStyleSheet("media/com_ra_library/js/jsonwalks/sr02/style.css");
        parent::setWalksClass("sr02prog");
    }

    public function customValue($option, $walk) {
        $response = new \stdClass();
        $response->found = true;
        $response->out = "";
        switch ($option) {
            case "{xTitle}":
                $response->out = $walk->getIntValue("basics", "title");
                break;
            case "{xSymbol}":
                /* Picnic or Pub icon */
                if (stristr($walk->getIntValue("basics", "additionalNotes"), "picnic")) {
                    $response->out = '<img src="media/com_ra_library/js/jsonwalks/sr02/Sandwich-icon.png" title="Picnic Required" width="24" height="24" align="left"/>';
                    break;
                }
                if (stristr($walk->getIntValue("basics", "additionalNotes"), "pub")) {
                    $response->out = '<img src="media/com_ra_library/js/jsonwalks/sr02/beer.png" title="Pub Lunch" width="24" height="24" align="left"/>';
                }
                break;
            case "{xNationalGrade}":
                $response->out = strtoupper($walk->getIntValue("walks", "nationalGrade"));
                break;
            case "{xContact}":
                $response->out = "<b>" . $walk->getIntValue("contacts", "contactName") . "</b>";
                break;
            default:
                $response->found = false;
                break;
        }

        return $response;
    }

    public function rowClass($walk) {
        $class = "leisurely";
        $day = $walk->getIntValue("basics", "walkDate")->format('l');
        if (($walk->getIntValue("walks", "shape") === "Linear") && ($day == "Wednesday")) {
            $class = "sr02linear";
        } else {
            switch ($walk->getIntValue("walks", "nationalGrade")) {
                case "Easy" :
                    $class = "sr02easy";
                    break;
                case "Leisurely" :
                    $class = "sr02leisurely";
                    break;
                case "Moderate" :
                    $class = "sr02moderate";
                    break;
                case "Strenuous" :
                    $class = "sr02strenuous";
                    break;
            }
        }
        return $class;
    }
}
