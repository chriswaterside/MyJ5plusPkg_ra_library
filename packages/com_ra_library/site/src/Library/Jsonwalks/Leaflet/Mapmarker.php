<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Leaflet;

/**
 * Description of mapmarker
 *
 * @author Chris Vaughan
 */
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Displaybase;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk;
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Map;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;

class Mapmarker extends Displaybase {

    private $map;
    private $walkClass = "walk";
    private $legendposition = "top";
    public $displayGradesSidebar = true;

    public function __construct() {
        $this->map = new Map;
        $this->map->setCommand("ra.display.walksMap");
        $this->map->help_page = "ledwalks.html";
        $options = $this->map->options;
        $options->cluster = true;
        // $options->displayElevation = true;
        $options->fullscreen = true;
        $options->mylocation = true;
        $options->settings = true;
        $options->mouseposition = true;
        $options->rightclick = true;
        $options->fitbounds = true;
        $options->print = true;
    }

    public function getMap() {
        return $this->map;
    }

    public function mapHeight($height) {
        $this->map->mapHeight = $height;
    }

    public function mapWidth($width) {
        $this->map->mapWidth = $width;
    }

    public function setLegend($position) {
        $this->legendposition = $position;
    }

    public function DisplayWalks($walks) {
        $items = $walks->allWalks();
        $data = new class {
            
        };
        $data->walks = array_values($items);
        //  $data->walks = [];

        $data->legendposition = $this->legendposition;
        $this->map->setDataObject($data);
        $this->map->display();
        Load::addScript("media/com_ra_library/js/jsonwalks/leaflet/mapmarker.js", "text/javascript");
    }
}
