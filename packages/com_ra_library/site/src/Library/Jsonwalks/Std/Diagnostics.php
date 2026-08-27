<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std;


defined("_JEXEC") or die("Restricted access");
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Displaybase;
//use Ramblers\Component\Ra_library\Site\Library\Jsonwalks;


class Diagnostics extends Displaybase {

    private $options = ["{lf}", "<b>ADMIN</b>", "{group}",
        "<b>BASICS</b>", "{dowShortdd}", "{dowShortddmm}",
        "{dowShortddmmyyyy}", "{dowdd}", "{dowddmm}", "{dowddmmyyyy}",
        "{title}", "{description}", "{additionalNotes}",
        "<b>MEETING</b>", "{meet}", "{meetTime}", "{meetPlace}", "{meetGR}", "{meetPC}", "{meetw3w}", "{meetOSMap}", "{meetDirections}",
        "<b>START</b>", "{start}", "{startTime}", "{startPlace}", "{startGR}", "{startPC}", "{startw3w}",
        "{startOSMap}", "{startDirections}",
        "<b>FINISH</b>", "{finishTime}",
        "<b>WALK</b>", "{difficulty}", "{difficulty+}", "{type}","{shape}", "{distance}", "{distanceMi}", "{distanceKm}", "{gradeimg}", "{gradeimgRight}",
        "{grade}", "{grade+}", "{nationalGrade}", "{nationalGradeAbbr}", "{localGrade}",
        "<b>CONTACTS</b>",  "{contact}", "{contactname}", "{contactperson}", "{telephone}",
        "{telephone1}", "{telephone2}", "{email}", "{emailat}", "{emaillink}",
        "<b>MEDIA</b>",  "{mediathumbr}","{mediathumbl}"];

    public function DisplayWalks($walks) {

        $items = $walks->allWalks();
        foreach ($items as $walk) {
            echo "<table><tr><th>Item</th><th>Name</th><th>Value</th><th>HTML</th></tr>";
            $no = 0;
            foreach ($this->options as $option) {

                $html = "<tr>";
                $value = $walk->getWalkValue($option);
                $text = str_replace(["<", ">"], ["&lt;", ">"], $value);
                $html .= "<td>" . $no . "</td><td>" . $option . "</td><td>" . $value . "</td><td>" . $text . "</td>";
                $html .= "</tr>";
                echo $html;
                $no += 1;
            }
            echo "</table>";
        }

        return;
    }

}
