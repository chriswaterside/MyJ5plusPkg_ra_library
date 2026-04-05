<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk;
// no direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
class Walk implements \JsonSerializable {

    private $shape;
    private $nationalGrade;
    private $localGrade;
    private $distanceKm;
    private $distanceMiles;
    private $pace;
    private $ascent;
    private $validGrades = ["Event", "Easy Access", "Easy", "Leisurely", "Moderate", "Strenuous", "Technical"];

    public function __construct(string $shape, string $nationalGrade, string $localGrade,
            float $distanceKm, string $pace, string $ascent) {
        switch (strtolower($shape)) {
            case "linear":
                $this->shape = "Linear";
                break;
            case "circular":
                $this->shape = "Circular";
                break;
            case "figure":
            case "figure of eight":
                $this->shape = "Figure of Eight";
                break;
           case "event":
                $this->shape = "";
                break;
            default:
                $app = Factory::getApplication();
                $app->enqueueMessage("Processing walks - invalid shape: " . $shape, 'Error');
        }
        if (in_array($nationalGrade, $this->validGrades, true)) {
            $this->nationalGrade = $nationalGrade;
        } else {
            $app = Factory::getApplication();
            $app->enqueueMessage("Processing walks - invalid walk difficulty " . $nationalGrade, 'Error');
            $this->nationalGrade = "Event";
        }

        $this->localGrade = $localGrade;
        $this->distanceKm = $distanceKm;
        $this->distanceMiles = $distanceKm * 0.621371;
        $this->distanceKm = round($this->distanceKm, 1);
        $this->distanceMiles = round($this->distanceMiles, 1);
        $this->pace = $pace;
        $this->ascent = $ascent;
    }

    public function getValue($option) {
        $BR = "<br/>";
        $out = "";
        switch ($option) {

            case "{difficulty}":
                $out = $this->getValue("{distance}");
                $out .= $BR . "<span class='pointer " . str_replace("/ /g", "", $this->nationalGrade) . "' onclick='javascript:ra.walk.dGH()'>" . $this->nationalGrade . "</span>";
                if ($this->localGrade !== "") {
                    $out .= $BR . $this->localGrade;
                }
                break;
            case "{difficulty+}":
                $out = $this->getValue("{distance}");
                //  $out .= "<div>" . $this->getGradeSpan("middle") . "</div>";
                $out .= $BR . $this->getGradeSpan("middle");
                $out .= "<span class='pointer " . str_replace("/ /g", "", $this->nationalGrade) . "' onclick='javascript:ra.walk.dGH()'>" . $this->nationalGrade . "</span>";
                if ($this->localGrade !== "") {
                    $out .= $BR . $this->localGrade;
                }
                break;
            case "{distance}":
                if ($this->distanceMiles > 0) {
                    $out = $this->distanceMiles . "mi / " . $this->distanceKm . "km";
                }
                break;
            case "{distanceMi}":
                if ($this->distanceMiles > 0) {
                    $out = $this->distanceMiles . "mi";
                }
                break;
            case "{distanceKm}":
                if ($this->distanceMiles > 0) {
                    $out = $this->distanceKm . "km";
                }
                break;
            case "{gradeimg}":
                $out = $this->getGradeSpan('middle');
                break;
            case "{gradeimgRight}":
                $out = $this->getGradeSpan('right');
                break;
            case "{grade}":
                $out = "<span class='pointer " . str_replace("/ /g", "", $this->nationalGrade) . "' onclick='javascript:ra.walk.dGH()'>" . $this->nationalGrade . "</span>";
                if ($this->localGrade !== "") {
                    if (strcmp($this->nationalGrade, $this->localGrade) != 0) {
                        $out .= $BR . $this->localGrade;
                    }
                }
                break;
            case "{grade+}":
                $out = $this->getGradeSpan("middle");
                $out .= "<span class='pointer " . str_replace("/ /g", "", $this->nationalGrade) . "' onclick='javascript:ra.walk.dGH()'>" . $this->nationalGrade . "</span>";
                if ($this->localGrade !== "") {
                    if (strcmp($this->nationalGrade, $this->localGrade) != 0) {
                        $out .= $BR . $this->localGrade;
                    }
                }
                break;
            case "{nationalGrade}":
                $out = "<span class='pointer " . str_replace("/ /g", "", $this->nationalGrade) . "' onclick='javascript:ra.walk.dGH()'>" . $this->nationalGrade . "</span>";
                break;
            case "{nationalGradeAbbr}":
                $out = "<span class='pointer " . str_replace("/ /g", "", $this->nationalGrade) . "' onclick='javascript:ra.walk.dGH()'>" . $this->gradeAbbr() . "</span>";
                break;
            case "{localGrade}":
                $out = $this->localGrade;
                break;
            case "{shape}":
            case "{type}":
                $out = $this->shape;
                break;
        }
        return $out;
    }

    public function getIntValue($option) {
        switch ($option) {
            case "distanceKm":
                return $this->distanceKm;
            case "distanceMiles":
                return $this->distanceMiles;
            case "localGrade":
                return $this->localGrade;
            case "nationalGrade":
                return $this->nationalGrade;
            case "shape":
                return $this->shape;
            case "pace":
                return $this->pace;
            case "ascent":
                return $this->ascent;
            case "schemaDistance":
                if ($this->nationalGrade !== "None") {
                    return "A " . $this->nationalGrade . " " . $this->distanceMiles . "mile / " . $this->distanceKm . "km walk";
                }
            case "icsWalkDistance":
                if ($this->distanceMiles > 0) {
                    return ", " . $this->distanceMiles . "mi/" . $this->distanceKm . "km";
                }
            case "_icsWalkGrade":
                if ($this->localGrade !== "") {
                    return "Grade: " . $this->localGrade . "/" . $this->nationalGrade . "; <br/> ";
                } else {
                    return "Grade: " . $this->nationalGrade . "; <br/> ";
                }
                return "";
        }
        $app = Factory::getApplication();
        $app->enqueueMessage("RJsonwalksWalkWalk error, invalid Walk value: " . $option);

        return "";
    }

    public function notInGradeList($grades) {
        foreach ($grades as $grade) {
            if (strtolower($grade) == strtolower($this->nationalGrade)) {
                return false;
            }
        }
        return true;
    }

    public function filterDistance($distanceMin, $distanceMax) {
        // if outside of the range then remove the walk
        return $this->distanceMiles < $distanceMin || $this->distanceMiles > $distanceMax;
    }

    private function getGradeSpan($class) {
        $tag = "";
        $img = $this->getGradeImg();
        switch ($this->nationalGrade) {
            case "Event":
                $tag = "<span data-descr='Event' class='grade " . $class . "' onclick='javascript:ra.walk.dGH()'>" . $img . "</span>";
                break;
            case "Easy Access":
                $tag = "<span data-descr='Easy Access' class='grade " . $class . "' onclick='javascript:ra.walk.dGH()'>" . $img . "</span>";
                break;
            case "Easy":
                $tag = "<span data-descr='Easy' class='grade " . $class . "' onclick='javascript:ra.walk.dGH()'>" . $img . "</span>";
                break;
            case "Leisurely":
                $tag = "<span data-descr='Leisurely' class='grade " . $class . "' onclick='javascript:ra.walk.dGH()'>" . $img . "</span>";
                break;
            case "Moderate":
                $tag = "<span data-descr='Moderate' class='grade " . $class . "' onclick='javascript:ra.walk.dGH()'>" . $img . "</span>";
                break;
            case "Strenuous":
                $tag = "<span data-descr='Strenuous' class='grade " . $class . "' onclick='javascript:ra.walk.dGH()'>" . $img . "</span>";
                break;
            case "Technical":
                $tag = "<span data-descr='Technical' class='grade " . $class . "' onclick='javascript:ra.walk.dGH()'>" . $img . "</span>";
                break;
            default:
                $app = Factory::getApplication();
                $app->enqueueMessage("RJsonwalksWalkWalk error: invalid garde", 'information');
        }
        return $tag;
    }

    private function getGradeImg() {
        $folder = Uri::base(true);
        $url = $folder . "/media/com_ra_library/images/grades/";

        switch ($this->nationalGrade) {
            case "Event":
                $url = "<img src='" . $url . "event.png' alt='Event' height='30' width='30'>";
                break;
            case "Easy Access":
                $url = "<img src='" . $url . "ea.png' alt='Easy Access' height='30' width='30'>";
                break;
            case "Easy":
                $url = "<img src='" . $url . "e.png' alt='Easy' height='30' width='30'>";
                break;
            case "Leisurely":
                $url = "<img src='" . $url . "l.png' alt='Leisurely' height='30' width='30'>";
                break;
            case "Moderate":
                $url = "<img src='" . $url . "m.png' alt='Moderate' height='30' width='30'>";
                break;
            case "Strenuous":
                $url = "<img src='" . $url . "s.png' alt='Strenuous' height='30' width='30'>";
                break;
            case "Technical":
                $url = "<img src='" . $url . "t.png' alt='Technical' height='30' width='30'>";
                break;
            default:
                $app = Factory::getApplication();
                $app->enqueueMessage("RJsonwalksWalkWalk error: invalid garde", 'information');
        }
        return $url;
    }

    private function gradeAbbr() {
        switch ($this->nationalGrade) {
            case "Event":
                return "Event";
            case "Easy Access":
                return "EA";
            case "Easy":
                return "E";
            case "Leisurely":
                return "L";
            case "Moderate":
                return "M";
            case "Strenuous":
                return "S";
            case "Technical":
                return "T";
            default:
                $app = Factory::getApplication();
                $app->enqueueMessage("RJsonwalksWalkWalk error: invalid garde", 'information');
                return "";
        }
    }

    #[\Override]
    public function jsonSerialize(): mixed {
        return [
            'shape' => $this->shape,
            'nationalGrade' => $this->nationalGrade,
            'localGrade' => $this->localGrade,
            'distanceKm' => $this->distanceKm,
            'distanceMiles' => $this->distanceMiles,
            'pace' => $this->pace,
            'ascent' => $this->ascent
        ];
    }

}
