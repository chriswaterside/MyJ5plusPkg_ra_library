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

class Walktable extends Displaybase {

    private $walksClass = "walks";
    private $walkClass = "walk";
    private $tableClass = '';
    private $customFormat = null;
    private $monthlyReminderClass = null;
    private $monthlyReminderMethod = null;
    private $rowClassClass = null;
    private $rowClassMethod = null;
    public $link = true;
    public $addDescription = true;
    public $addGroupName = false;
    public $addLocation = true;
    private $tableFormat = [
        ['title' => 'Date', 'items' => ["{dowddmm}"]],
        ['title' => 'Meet', 'items' => ["{meet}", "{,meetGR}", "{,meetPC}"]],
        ['title' => 'Start', 'items' => ["{start}", "{,startGR}", "{,startPC}"]],
        ['title' => 'Title', 'items' => ["{title}", "{mediathumbr}"]],
        ['title' => 'Difficulty', 'items' => ["{difficulty+}"]],
        ['title' => 'Contact', 'items' => ["{contact}"]]];

    public function customFormat($format) {
        $this->customFormat = $format;
    }

    public function DisplayWalks($walks) {

        $walks->sort(Walk::SORT_DATE,Walk::SORT_TIME, Walk::SORT_DISTANCE);
        $items = $walks->allWalks();
        if ($this->customFormat !== null) {
            $this->tableFormat = $this->customFormat;
        }
        $groupByMonth = Walk::groupByMonth($this->tableFormat);
        $odd = false;
        $lastValue = "";
        echo "<table class='" . $this->tableClass . "' >" . PHP_EOL;

        if (!$groupByMonth) {
            echo $this->displayTableHeader();
        }

        foreach ($items as $walk) {
            if ($groupByMonth) {
                $thismonth = $walk->getMonthGroup($walk);
                $displayMonth = $thismonth !== $lastValue;
                $lastValue = $thismonth;
                if ($displayMonth) {
                    if ($displayMonth) {
                        $out = "<tr><td>";
                        $out .= "<h3>" . $thismonth . "</h3>";
                        $out .= "</td></tr>";
                        echo $out;
                    }
                    if ($this->monthlyReminderClass !== null) {
                        call_user_func(array($this->monthlyReminderClass, $this->monthlyReminderMethod), $thismonth);
                    }
                }
            }
            echo $this->displayWalk_Table($walk, $this->walkClass, $odd);
            $odd = !$odd;
        }
        echo "</table>" . PHP_EOL;
        Script::registerWalks(array_values($items));
        //       $schema = new RJsonwalksAddschema();
        //      $schema->display($walks);
    }

    public function setMonthlyReminder($clss, $method) {
        $this->monthlyReminderClass = $clss;
        $this->monthlyReminderMethod = $method;
    }

    public function setRowClass($clss, $method) {
        $this->rowClassClass = $clss;
        $this->rowClassMethod = $method;
    }

    public function setWalksClass($class) {
        $this->tableClass = $class;
    }

    public function setWalkClass($class) {
        $this->walkClass = $class;
    }

    public function setTableClass($class) {
        $this->tableClass = $class;
    }

    private function displayTableHeader() {
        $cols = $this->tableFormat;
        $out = "<tr>";
        foreach ($cols as $col) {
            $out .= "<th>" . $col['title'] . "</th>";
        }
        return $out . "</tr>";
    }

    private function displayWalk_Table($walk, $class, $odd) {
        $cols = $this->tableFormat;
        $nClass = $class;
        if ($this->rowClassClass !== null) {
            $nClass = call_user_func(array($this->rowClassClass, $this->rowClassMethod), $walk);
        }
        if ($odd) {
            $out = "<tr class='odd " . $nClass . " walk" . $walk->getIntValue("admin", "status") . "' >";
        } else {
            $out = "<tr class='even " . $nClass . " walk" . $walk->getIntValue("admin", "status") . "' >";
        }

        foreach ($cols as $col) {
            if (array_key_exists('class', $col)) {
                $class = $col['class'];
                $out .= "<td " . $class . ">";
            } else {
                $out .= "<td>";
            }
            $items = $col['items'];
            $out .= $walk->addTooltip($walk->getWalkValues($items));
            $out .= "</td>";
        }
        $out .= "</tr>";
        return $out;
    }

}
