<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Ml;

/**
 * Description of RJsonwalksStdWalktable3col
 *
 * @author Chris Vaughan
 */
// no direct access
defined("_JEXEC") or die("Restricted access");
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std\Walktable;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;


class MLPrint extends Walktable {

    private $firstWalkDate = null;
    private $tableFormat = [
        ['title' => 'Date/Time', 'items' => ["{dowShortddmm}", "{;startTimefull}"]],
        ['title' => 'Programme', 'items' => ["{xTitle}", "{;description}", "{;startPlace}", "{xContact}", "{xStart}"]]
    ];

    public function __construct() {
        parent::__construct();
        parent::customFormat($this->tableFormat);
        Walk::setCustomValues($this, "customValue");
        parent::setRowClass($this, 'rowClass');
        Load::addStyleSheet("media/com_ra_library/js/jsonwalks/ml/style.css");
        Load::addSCript("media/com_ra_library/js/jsonwalks/ml/script.js");
        parent::setWalksClass("ml-print-contents");
        echo "<div id='ml-buttons'></div>";
    }

    public function customValue($option, $walk) {
        $response = new \stdClass();
        $response->found = true;
        $response->out = "";
        switch ($option) {
            case "{xTitle}":
                $title = $walk->getWalkValue("{title}");
                $distance = $walk->getWalkValue("{distanceMi}");
                $grade = $walk->getWalkValue("{nationalGradeAbbr}");
                $groupname = $walk->getWalkValue("{group}");
                $response->out = "<span class='mlTitle'>" . $title . "</span> " . $distance . " (" . $grade . ") " . $groupname;
                break;
            case "{xContact}":
                $tel = $walk->getIntValue("contacts", "telephone1");
                $response->out = " Contact: " . $walk->getIntValue("contacts", "contactName") . " <b>" . $tel . "</b>";
                break;
            case "{xStart}":
                $gr = $walk->getWalkValue("{startGR}");
                $pc = $walk->getWalkValue("{startPC}");
                $w3w = $walk->getWalkValue("{startw3w}");
                $response->out = " {" . $gr . ", " . $pc . "}<br/>w3w " . $w3w;
                break;
            default:
                $response->found = false;
                break;
        }
        return $response;
    }

    public function rowClass($walk) {
        $today = new \DateTime();
        $class = "ml-weeks";
        $walkDate = $walk->getIntValue("basics", "walkDate");

        $lastdate = $today->add(new \DateInterval('P37D'));
        if ($walkDate > $lastdate) {
            $class = "ml-allwalks";
        }
        return $class;
    }

    public function DisplayWalks($walks) {
        $allWalks = $walks->allWalks();
        echo "<div id='ml-printwalks'>";
        $total = count($allWalks);
        if ($total > 0) {
            $this->firstWalkDate = $allWalks[0]->getIntValue("basics", "walkDate");
            $this->lastWalkDate = $allWalks[$total - 1]->getIntValue("basics", "walkDate");
            $this->lastdate = clone $this->firstWalkDate;
            $this->lastdate = $this->lastdate->add(new \DateInterval('P37D'));
            echo "<div class='ml-allwalks'>";
            echo "There are " . $total . " walks";
            echo " from " . $this->firstWalkDate->format('D, jS F Y');
            echo " to " . $this->lastWalkDate->format('D, jS F Y');
            echo "</div>";
            $no = 0;
            foreach ($allWalks as $walk) {
                $class = $this->rowClass($walk);
                if ($class !== "ml-allwalks") {
                    $no += 1;
                    $fiveweeksDate = $walk->getIntValue("basics", "walkDate");
                }
            }
            echo "<div class='ml-fivewalks'>";
            echo "There are " . $no . " walks";
            echo " from " . $this->firstWalkDate->format('D, jS F Y');
            echo " to " . $fiveweeksDate->format('D, jS F Y');
            echo "<br/>(Covering the 5 weeks & 2 days from the first walk)";
            echo "</div>";
        } else {
            echo "No walks found in current walks programme";
        }
        echo "<br/>";
        parent::DisplayWalks($walks);
        echo "</div>";
    }
}
