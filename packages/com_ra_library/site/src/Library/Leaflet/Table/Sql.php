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

class Sql extends Map {

    private $sql = "";
    private $validOptions = false;
    private $list = null;
    private $fields = [];
    public $paginationDefault = 10;
    public $markerOptions;

    public function __construct($sql) {
        parent::__construct();
        $this->sql = $sql;
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
            $app->enqueueMessage(Text::_("RLeafletSqlList options are invalid"), 'error');
            return;
        }
        $result = $this->readSql();
        If (!$result) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_("Unable to process the sql table "), 'error');
            return;
        }
        foreach ($result as $row) {
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
        Load::addStyleSheet("media/com_ra_library/js/leaflet/table/style.css", "text/css");
        Load::addStyleSheet('media/com_ra_library/css/ramblerslibrary.css');
        Load::addScript("media/com_ra_library/js/ra.tabs.js");
        Load::addStyleSheet("media/com_ra_library/css/ra.tabs.css");
        Load::addStyleSheet("media/com_ra_library/js/leaflet/table/style.css");
        Load::addStyleSheet('media/com_ra_library/js/vendors/cvList/cvList.css');
        Load::addScript("media/com_ra_library/js/vendors/cvList/cvList.js");
        
    }

    private function readSqlOLD() {
        // Get a db connection.
        $db = Factory::getDbo();
        // Create a new query object.
        $query = $db->getQuery(true);
        // $sql can be a select statement or a table name
        if (str_starts_with(strtoupper($this->sql), "SELECT")) {
            $db->setQuery($this->sql);
            $db->execute();
        } elseif (substr($this->sql, 0, 1) == '#') {
            $query->select($db->quoteName($this->fields));
            $query->from($db->quoteName($this->sql));
            $db->setQuery($query);
        } else {
            echo "ERROR: RLeafletSqlList $this->sql is not a valid parameter, should be table name or SELECT statement";
            return false;
        }
        $db->replacePrefix($query);
        $results = $db->loadObjectList();
        return $results;
    }

    private function readSql() {
        // Get a db connection.
        $db = Factory::getDbo();
        // Create a new query object.
        // $sql can be a select statement or a table name
        if (str_starts_with(strtoupper($this->sql), "SELECT")) {
            try {
                $query = $db->getQuery(true);
                $db->setQuery($this->sql);
                $db->execute();
            } catch (Exception $ex) {
                echo "ERROR: RLeafletSqlList $this->sql cannot be executed";
                return false;
            }
        } elseif (substr($this->sql, 0, 1) == '#') {
            try {
                $query = $db->getQuery(true);
                $query->select($db->quoteName($this->fields));
                $query->from($db->quoteName($this->sql));
                $db->setQuery($query);
            } catch (Exception $ex) {
                echo "ERROR: RLeafletSqlList $this->sql is not a valid table";
                return false;
            }
        } else {
            echo "ERROR: RLeafletSqlList $this->sql is not a valid parameter, should be table name or SELECT statement";
            return false;
        }
        $db->replacePrefix($query);
        $results = $db->loadObjectList();
        return $results;
    }
}
