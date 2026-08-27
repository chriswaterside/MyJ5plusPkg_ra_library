<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of basics
 *
 * @author chris
 */
use Joomla\CMS\Factory;
class Timelocation implements \JsonSerializable {

    private $type;
    private $time;  // null if no time
    private $timeHHMM;
    private $timeHHMMshort;
    private $description;
    private $latitude;
    private $longitude;
    private $gridref;
    private $w3w;
    private $postcode = null;
    private $postcodeLatitude = 0;
    private $postcodeLongitude = 0;
    private $travel = "";
    private $osmaps = null;

    const LOCATION_MEETING = "Meeting";
    const LOCATION_START = "Start";
    const LOCATION_ROUGH = "Rough";
    const LOCATION_LOCATION = "Location";
    const LOCATION_FINISH = "Finish";

    public function __construct(string $type, string $travel, ?\DateTime $time, string $description,
            Float $latitude, Float $longitude, string $gridref, ?string $w3w,
            string $postcode, Float $postcodeLatitude, Float $postcodeLongitude, ?array $osmaps) {
        switch ($type) {
            case self::LOCATION_MEETING:
                $this->type = self::LOCATION_MEETING;
                break;
            case self::LOCATION_START:
                $this->type = self::LOCATION_START;
                break;
            case self::LOCATION_ROUGH:
                $this->type = self::LOCATION_ROUGH;
                break;
            case self::LOCATION_LOCATION:
                $this->type = self::LOCATION_LOCATION;
                break;
            case self::LOCATION_FINISH:
                $this->type = self::LOCATION_FINISH;
                break;
            default:
                $app = Factory::getApplication();
                $app->enqueueMessage("Error reading walks: invalid timelocation type");
        }

        $this->travel = $travel;

        if ($time !== null && $time !== "") {
            $this->time = $time;
            $this->timeHHMM = $this->time->format('g:ia');
            $this->timeHHMMshort = str_replace(":00", "", $this->timeHHMM);
            if ($this->timeHHMMshort == "12am") {
                $this->time = null;
                $this->timeHHMM = "No time";
                $this->timeHHMMshort = "No time";
            }
        } else {
            $this->time = null;
            $this->timeHHMM = "No time";
            $this->timeHHMMshort = "No time";
        }

        $this->description = $description;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->gridref = $gridref;
        $this->w3w = $w3w;
        $this->postcode = $postcode;
        $this->postcodeLatitude = $postcodeLatitude;
        $this->postcodeLongitude = $postcodeLongitude;
        $this->osmaps = $osmaps;
    }

    public function getValue($option) {
        $out = "";
        switch ($option) {
            case "{}":
                if ($this->type !== self::LOCATION_ROUGH) {
                    $out = $this->timeHHMMshort;
                    if ($this->description) {
                        $out .= " at " . $this->description;
                    }
                }
                break;
            case "{Time}":
                if ($this->type !== self::LOCATION_ROUGH) {
                    $out = $this->timeHHMMshort;
                }
                break;
             case "{Timefull}":
                if ($this->type !== self::LOCATION_ROUGH) {
                    $out = $this->timeHHMM;
                }
                break;
            case "{Place}":
                if ($this->type !== self::LOCATION_ROUGH) {
                    $out = $this->description;
                }
                break;
            case "{GR}":
                if ($this->type !== self::LOCATION_ROUGH) {
                    $out = $this->gridref;
                }
                break;
            case "{PC}":
                if ($this->postcode !== null) {
                    $out = $this->postcode;
                }
                break;
            case "{w3w}":
                if ($this->type !== self::LOCATION_ROUGH) {
                    $out = $this->w3w;
                }
                break;
            case "{Directions}":
                if ($this->type !== self::LOCATION_ROUGH) {
                    $lat = $this->latitude;
                    $long = $this->longitude;
                    $out = "<span><a href=&quot;javascript:ra.loc.directions(" . $lat . "," . $long . ")&quot; >[Directions]</a></span>";
                }
                break;
        }
        return $out;
    }

    public function getIntValue($option) {
        switch ($option) {
            case "type":
                return $this->type;
            case "time":
                return $this->time;
            case "lat/long":
                return  $this->latitude . ',' . $this->longitude ;
            case "postcode":
                return $this->postcode;
            case "textTime":
                if ($this->time != "") {
                    return $this->time->format('G:i:s');
                }
                return "";
            case "_icsDescription":
                return $this->getICSTextDescription();
        }
        $app = Factory::getApplication();
        $app->enqueueMessage("Internal error, invalid Timelocation value: " . $option);

        return "";
    }

    private function getICSTextDescription() {
        $textdescription = "";
        switch ($this->type) {
            case "Meeting":
                $textdescription = "Meet: ";
                break;
            case "Start":
                $textdescription = "Start: ";
                break;
            case "Rough":
                $textdescription = "Walking area: ";
                break;
            case "Location":
                $textdescription = "Location: ";
                break;
            case "Finish":
                $textdescription = "Finish: ";
                break;
            default:
                alert("Error 0003");
        }
        if ($this->type !== "Rough") {
            if ($this->timeHHMMshort !== "") {
                $textdescription .= $this->timeHHMMshort . " @ ";
            }
        }
        $place = $this->gridref;
        if ($this->postcode !== null) {
            $place .= ", " . $this->postcode;
        }
        if ($this->description !== '') {
            $textdescription .= $this->description . ' (' . $place . ')';
        } else {
            $textdescription .= $place;
        }
        return $textdescription;
    }

    public function distanceFrom($easting, $northing) {
        $dele = ($this->easting - $easting) / 1000;
        $deln = ($this->northing - $northing) / 1000;
        $dist = sqrt($dele * $dele + $deln * $deln);
        return $dist;
    }

    public function distanceFromLatLong($lat1, $lon1) {
        $lat2 = $this->latitude;
        $lon2 = $this->longitude;
        $dist = RGeometryGreatcircle::distance($lat1, $lon1, $lat2, $lon2, "KM");
        return $dist;
    }

    public function jsonSerialize(): mixed {
        return [
            'type' => $this->type,
            'travel' => $this->travel,
            'time' => $this->time,
            'timeHHMM' => $this->timeHHMM,
            'timeHHMMshort' => $this->timeHHMMshort,
            'description' => $this->description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'gridref' => $this->gridref,
            'w3w' => $this->w3w,
            'postcode' => $this->postcode,
            'postcodeLatitude' => $this->postcodeLatitude,
            'postcodeLongitude' => $this->postcodeLongitude,
            'osmaps' => $this->osmaps
        ];
    }

}