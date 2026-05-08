<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std;

/**
 * Description of WalksDisplay
 *
 * @author Chris Vaughan
 */
// no direct access
defined("_JEXEC") or die("Restricted access");

use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Displaybase;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk;
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Script;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Addschema;

class Simplelist extends Displaybase {

    private $walksClass = "walks";
    private $walkClass = "walk";
    private $customFormat = null;
    private $inLineDisplay = false;
    private $listFormat = "{dowdd}{, ?meet}{, ?meetGR}" .
            "{, ?start}{, ?startGR}{, ?title}" .
            "{, ?distance}{, ?contactname}{, ?telephone}";

    public function customFormat($format) {
        $this->customFormat = $format;
    }

    public function DisplayWalks($walks) {
 
        if ($this->customFormat !== null) {
            $this->listFormat = $this->customFormat;
        }

        $walks->sort(Walk::SORT_DATE, Walk::SORT_TIME, Walk::SORT_DISTANCE);
        $items = $walks->allWalks();
        $groupByMonth = Walk::groupByMonth($this->listFormat);
        $lastValue = "";
        $odd = true;
        echo "<div class='" . $this->walksClass . "' >" . PHP_EOL;
        foreach ($items as $walk) {
            $thismonth = $walk->getMonthGroup();
            if ($thismonth <> $lastValue) {
                $lastValue = $thismonth;
                if ($groupByMonth) {
                    echo "<h2>" . $thismonth . "</h2>" . PHP_EOL;
                    $odd = false;
                }
            }
            if ($odd) {
                $this->displayWalk($walk, 'odd');
            } else {
                $this->displayWalk($walk, 'even');
            }
            $odd = !$odd;
        }
        echo "</div>" . PHP_EOL;

        Script::registerWalks(array_values($items));

        $schema = new Addschema();
        $schema->display($walks);
    }

    // display full walk either as popup or inline
    public function inLineDisplay() {
        $this->inLineDisplay = true;
    }

    public function setWalksClass($class) {
        $this->walksClass = $class;
    }

    public function setWalkClass($class) {
        $this->walkClass = $class;
    }

    private function displayWalk($walk, $oddeven) {
        $out = "";
        $status = $walk->getIntValue("admin", "status");
        $id = $walk->getIntValue("admin", "id");
        if ($this->inLineDisplay) {
            $DisplayWalkFunction = "ra.walk.toggleDisplay";
            $text = $walk->addTooltip($walk->getWalkText($this->listFormat, false));
            $out .= "<div class='" . $this->walkClass . $status . " " . $oddeven . " toggler pointer'"
                    . " onclick=\"javascript:" . $DisplayWalkFunction . "(event,'" . $id . "')\">" . PHP_EOL;
            $out .= "<span class='item'>" . $text . "</span></div>" . PHP_EOL;
        } else {
            $text = $walk->addTooltip($walk->getWalkText($this->listFormat));
            $out .= "<div class='" . $this->walkClass . $status . " " . $oddeven . "' >" . PHP_EOL;
            $out .= "<span class='item'>" . $text . "</span></div>" . PHP_EOL;
        }
        echo $out;
    }
}
