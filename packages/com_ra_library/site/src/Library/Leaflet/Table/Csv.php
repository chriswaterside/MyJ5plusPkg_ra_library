<?php

namespace Ramblers\Component\Ra_library\Site\Library\Leaflet\Table;

/**
 * Description of list
 *
 * @author Chris Vaughan
 */
use Ramblers\Component\Ra_library\Site\Library\Load\Load;
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Map;
use Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

class Csv extends Map {

    private $filename = "";
    private $list;
    public $paginationDefault = 10;
    private $markerOptions = null;

    public function __construct($filename) {
        parent::__construct();
        $this->filename = $filename;
    }

    public function setMarkerOptions($markerOptions) {
        $this->markerOptions = $markerOptions;
    }

    public function display() {
        $ok = $this->readCSV();
        If (!$ok) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_("Unable to open/process the file: " . $this->filename), 'error');
            return;
        }

        $this->help_page = "listofitems.html";
        $this->options->cluster = true;
        $this->options->fullscreen = true;
        $this->options->filter = true;
        $this->options->locationsearch = true;
        $this->options->osgrid = true;
        $this->options->mouseposition = true;
        $this->options->settings = true;
        $this->options->mylocation = true;
        $this->options->rightclick = true;
        $this->options->fitbounds = true;
        $this->options->print = true;

        $data = new class {
            
        };
        $data->markerOptions = $this->markerOptions;
        $data->list = $this->list;
        $data->paginationDefault = $this->paginationDefault;

        parent::setCommand('ra.display.tableList.display');
        parent::setDataObject($data);
        parent::display();
        Load::addScript("media/com_ra_library/js/leaflet/table/ramblerstable.js");
        Load::addScript("media/com_ra_library/js/ra.tabs.js");
        Load::addStyleSheet("media/com_ra_library/css/ra.tabs.css");
        Load::addStyleSheet("media/com_ra_library/js/leaflet/table/style.css");
        Load::addStyleSheet('media/com_ra_library/css/ramblerslibrary.css');

        Load::addStyleSheet('media/com_ra_library/js/vendors/cvList/cvList.css');
        Load::addScript("media/com_ra_library/js/vendors/cvList/cvList.js");
    }

    private function readCSV() {
        $row = 1;
        $column = null;
        $this->list = new Columns();
        if (($handle = fopen($this->filename, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, null, ",")) !== FALSE) {
                $num = count($data);
                for ($col = 0; $col < $num; $col++) {
                    $value = $data[$col];
                    if ($row === 1) {
                        $column = new Column($value);
                        $this->list->addColumn($column);
                    } else {
                        $column = $this->list->getColumn($col);
                        if ($column == null) {
                            echo "<p>Column " . $col . " has no header title</p>";
                        } else {
                            if ($row == 2) {
                                $column->addOptions($value);
                            } else {
                                $column->addValue($value);
                            }
                        }
                    }
                }

                $row++;
            }
            fclose($handle);
            $this->list->removeIgnoredColumns();
        } else {
            return false;
        }
        return true;
    }
}
