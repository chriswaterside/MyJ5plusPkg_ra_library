<?php
namespace Ramblers\Component\Ra_library\Site\Library\Leaflet\Table;

/**
 * Description of list
 *
 * @author Chris Vaughan
 * 15/04/24 Charlie Bigley Allow specification of a table name or a SELECT statement
 */
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Map;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;
use Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

class Json extends Map {

    private $filename = "";
    private $validOptions = false;
    private $list = null;
    private $fields = [];
    public $paginationDefault = 10;
    public $markerOptions;

    public function __construct($filename) {
        parent::__construct();
        $this->filename = $filename;
        $this->markerOptions = null;
    }

    public function setMarkerOptions($markerOptions) {
        $this->markerOptions = $markerOptions;
    }

    public function setOptions($options) {
        if (!is_array($options)) {
            echo "ERROR: RLeafletSqlList options  must be an array";
            return;
        }
        $this->list = new Columns();
        foreach ($options as $option) {
            if (!array_key_exists("heading", $option)) {
                echo "ERROR: RLeafletSqlList options  does not contain heading field";
                return;
            }
            if (!array_key_exists("column", $option)) {
                echo "ERROR: RLeafletSqlList options  does not contain column field";
                return;
            }
            if (!array_key_exists("options", $option)) {
                echo "ERROR: RLeafletSqlList options  does not contain options field";
                return;
            }
            $column = new Column($option["heading"]);
            $this->list->addColumn($column);
            $column->addOptions($option["options"]);
            $column->columnName = $option["column"];
            $this->fields[] = $option["column"];
        }
        $this->validOptions = true;
    }

    public function display() {
        If (!$this->validOptions) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_("RLeafletJsonList options are invalid"), 'error');
            return;
        }
        $result = $this->readJson();
        if ($result === false) {
            return;
        }
        foreach ($result as $key => $row) {

            $columns = $this->list->getColumns();
            foreach ($columns as $column) {
                if (property_exists($row, $column->columnName)) {
                    $col = $column->columnName;
                    $value = $row->$col;
                    $column->addValue($value);
                }
            }
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
        Load::addStyleSheet("media/com_ra_library/js/leaflet/table/style.css");
        Load::addStyleSheet('media/com_ra_library/css/ramblerslibrary.css');
        Load::addScript("media/com_ra_library/js/ra.tabs.js");
        Load::addStyleSheet("media/com_ra_library/css/ra.tabs.css");
        Load::addStyleSheet('media/com_ra_library/js/vendors/cvList/cvList.css');
        Load::addScript("media/com_ra_library/js/vendors/cvList/cvList.js");

      //  Load::addScript("https://cdnjs.cloudflare.com/ajax/libs/babel-polyfill/7.12.1/polyfill.min.js", "text/javascript");
    }

    private function readJson() {
        $data = file_get_contents($this->filename);
        $results = json_decode($data);
        if ($results === false) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_("RLeafletJsonList unable to read json file"), 'error');
        }
        return $results;
    }
}
