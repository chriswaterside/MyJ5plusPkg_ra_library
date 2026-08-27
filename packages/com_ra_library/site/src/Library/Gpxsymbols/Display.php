<?php

namespace Ramblers\Component\Ra_library\Site\Library\Gpxsymbols;

/**
 * Description of display
 *
 * @author Chris Vaughan
 */
use Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Factory;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;

class Display {

    public function __construct() {
        Load::addStyleSheet("media/com_ra_library/gpxsymbols/display.css");
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->addInlineStyle('.gpximages {
  display: table;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  width: 100%;
    }');
    }

    public function Display() {
        echo "<h4>Alphabetic/Letter markers</h4>";

        echo "<p>Example: letter/k</p>";

        $this->listFolder("media/com_ra_library/gpxsymbols/letter");

        echo "<h4>Number symbols</h4>";

        echo "<p>Example: number/9</p>";

        $this->listFolder("media/com_ra_library/gpxsymbols/number");

        echo "<h4>Office symbols</h4>";

        echo "<p>Example: office/information</p>";

        $this->listFolder("media/com_ra_library/gpxsymbols/office");

        echo "<h4>Transport symbols</h4>";

        echo "<p>Example: transport/busstop</p>";

        $this->listFolder("media/com_ra_library/gpxsymbols/transport");
    }

    private function listFolder($folder) {
        $items = [];
        // Get the base URL 
        $baseUrl = Uri::base();
        if ($handle = opendir($folder)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $items[] = $entry;
                }
            }
            closedir($handle);
            asort($items, SORT_NATURAL);
            echo "<details class='ra' name='symbols'>";
            echo "<summary>Display possible markers</summary>";
            echo "<div class='gpximages' >";
            foreach ($items as $item) {
                $this->displayImage($baseUrl . $folder, $item);
            }
            echo "</div>";
            echo "</details>";
            echo "<div class='clear' ></div>";
        }
    }

    private function displayImage($folder, $entry) {
        $names = explode(".", $entry);
        $name = $names[0];
        echo "<div class='gpximage' >";
        echo "<span class='gpximagedisplay' ><img src='" . $folder . "/" . $entry . "' alt='image'></span>";
        echo "<span class='gpximagetitle' >" . $name . "</span>";
        echo "</div>";
    }
}
