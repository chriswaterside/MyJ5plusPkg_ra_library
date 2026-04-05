<?php
namespace Ramblers\Component\Ra_library\Site\Library\Leaflet;
/**
 *  This function displays all meeting and starting location that are valid
 *    i.e. 1 start or greater
 * 
 *
 * @author Chris Vaughan
 */
use Ramblers\Component\Ra_library\Site\Library\Load\Load;

class Mapplaces extends RLeafletMap {

    public function __construct() {
        parent::__construct();
    }

    public function display() {
        parent::setCommand('ra.display.places');

        $this->help_page = "places-help-home.html";
        $this->options->fullscreen = true;
        $this->options->mouseposition = true;
        $this->options->rightclick = true;
        $this->options->fitbounds = true;
        $this->options->displayElevation = false;
        $this->options->cluster = false;
        $this->options->mylocation = true;
        $this->options->settings = true;
        $this->options->draw = false;
        $this->options->print = true;
       // $this->options->ramblersPlaces = true;
    
        parent::display();

        Load::addScript("media/lib_com_ra_library/jsramblers/leaflet/ra-display-places.js", "text/javascript");
        Load::addStyleSheet('media/com_ra_library/js/css/ramblerslibrary.css');

    }
}