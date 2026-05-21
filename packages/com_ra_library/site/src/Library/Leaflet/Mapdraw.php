<?php
namespace Ramblers\Component\Ra_library\Site\Library\Leaflet;
/**
 * Description of mapdraw
 *
 * @author Chris Vaughan
 */
use Ramblers\Component\Ra_library\Site\Library\Load\Load;
use Joomla\CMS\Factory;
class Mapdraw extends Map {

    public $displayDescription = true;

    public function __construct() {
        parent::__construct();
        $this->options->setinitialviewView(52.89, -1.48, 10);
    }

    public function setCenter($lat, $long, $zoom) {
        $this->options->setinitialviewView($lat, $long, $zoom);
    }

    public function display() {
        parent::setCommand('ra.display.plotRoute');
        $this->help_page = "plot-walking-route.html";
        $this->options->fullscreen = true;

        $this->options->mouseposition = true;
        $this->options->rightclick = true;
        $this->options->fitbounds = true;
        $this->options->displayElevation = true;
        $this->options->cluster = null;
        $this->options->mylocation = true;
        $this->options->settings = true;
        $this->options->print = true;

        parent::display();

        Load::addScript("media/com_ra_library/js/leaflet/ra.display.plotRoute.js");
        Load::addStyleSheet("media/com_ra_library/js/leaflet/ra-gpx-tools.css");
        Load::addStyleSheet('media/com_ra_library/css/ramblerslibrary.css');
        Load::addScript("media/com_ra_library/js/leaflet/L.Control.GpxUpload.js");
        Load::addScript("media/com_ra_library/js/leaflet/L.Control.GpxDownload.js");
        Load::addScript("media/com_ra_library/js/leaflet/L.Control.SmartRoute.js");
        Load::addScript("media/com_ra_library/js/leaflet/L.Control.ReverseRoute.js");
        Load::addScript("media/com_ra_library/js/leaflet/L.Control.GpxSimplify.js");
        Load::addStyleSheet('media/com_ra_library/css/ra.tabs.css');
        Load::addScript("media/com_ra_library/js/ra.tabs.js");

        $document = Factory::getDocument();
        $path = "media/com_ra_library/js/vendors/Leaflet.draw-1.0.4/dist/";
        $document->addStyleSheet($path . "leaflet.draw.css");
        Load::addScript($path . "leaflet.draw-src.js");
        Load::addScript("media/com_ra_library/js/vendors/simplify-js-1.2.3/simplify.js");
        Load::addScript("media/com_ra_library/js/vendors/FileSaver-js-1.3.8/src/FileSaver.js");
    }
}
