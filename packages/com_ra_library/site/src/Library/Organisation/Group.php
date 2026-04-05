<?php
namespace Ramblers\Component\Ra_library\Site\Library\Organisation;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of area
 *
 * @author Chris
 */
class Group {

    public $scope;
    public $code;
    public $name;
    public $url;
    public $description;
    public $latitude;
    public $longitude;
    public $website = "";
    public $status = "";

    public function __construct($item) {
        $this->scope = $item->scope;
        $this->code = $item->groupCode;
        $this->name = $item->name;
        $this->url = $item->url;
        $this->description = $item->description;
        $this->latitude = $item->latitude;
        $this->longitude = $item->longitude;
        if (property_exists($item, "website")) {
            $this->website = $item->website;
        }
        if (property_exists($item, "status")) {
            $this->status = $item->status;
        }
    }

    public function getLink($showLink) {
        if ($showLink) {
            return "<a href='" . $this->url . "' target='_blank' >" . $this->name . "</a>";
        } else {
            return $this->name;
        }
    }

    public function addMapMarker($areatext) {

        $title = str_replace("'", "&apos;", $this->name);
        $text = "Unknown group";
        $long = $this->longitude;
        $lat = $this->latitude;
        $url = $this->url;
        $desc = "<br/>" . htmlentities($this->description);
        $desc = str_replace("\n", '<br />', $desc);
        $desc = str_replace("\r", '', $desc);

        switch ($this->scope) {
            case "A":
                $iclass = "group-icon a";
                $text = "Ramblers Area [" . $this->code . "]";
                break;
            case "G":
                $iclass = "group-icon g";
                $text = $areatext . "Group [" . $this->code . "]";
                break;
            default:
                $iclass = "group-icon s";
                $text = $areatext . "Special Group [" . $this->code . "]";
                break;
        }
        $class = "group" . $this->scope;
        $popup = "<div class='" . $class . "'>" . $text . "<br/><a href='" . $url . "' target='_blank'>" . $title . "</a>" . $desc . "</div>";
        $icon = "myicon=L.divIcon({className: '" . $iclass . "', iconSize: null, html: '" . $title . "'});  ";
        $marker = "addMarker(\"" . $popup . "\", " . $lat . ", " . $long . ", myicon);";

        return $icon . $marker;
    }

}
