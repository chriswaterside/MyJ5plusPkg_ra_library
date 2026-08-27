<?php
namespace Ramblers\Component\Ra_library\Site\Library\Leaflet;
/**
 * Description of RLeafletMap
 *   Not called by users
 *
 * @author Chris Vaughan
 */
class Map {

    private $script = null;
    public $debugoptions = false;
    public $defaultMap = "";
    //  public $mapStyle = " #leafletmap { height: 500px; width:100%;}";
    public $mapHeight = "100%";
    public $mapWidth = "500px";
    public $options;
    public $help_page = "";
    public $leafletLoad = true;

    function __construct() {
        $this->options = new Mapoptions();
        $this->script = new Script($this->options);
    }

    public function setCommand($command) {
        $this->script->setCommand($command);
    }

    public function setDataObject($value) {
        $this->script->setDataObject($value);
    }

    public function display() {
        $this->options->helpPage = $this->help_page;
        $options = $this->options;
        // set mapping options
        if ($this->defaultMap == "Topo") {
            $options->topoMapDefault = true;
        }
        $this->script->add($options);
    }
}
