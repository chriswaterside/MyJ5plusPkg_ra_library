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
    // Set via setOptions() when the admin has chosen to define the columns
    // and their options in the form (table_options) instead of reading them
    // from the second line of the CSV file. Null/empty means "use the
    // second line of the file", same as before this option existed.
    private $columnOptions = null;

    public function __construct($filename) {
        parent::__construct();
        $this->filename = $filename;
    }

    public function setMarkerOptions($markerOptions) {
        $this->markerOptions = $markerOptions;
    }

    /**
     * Optional. Pass an array of {heading, column, options} rows - the same
     * shape as Sql::setOptions()/Json::setOptions() (i.e. the table_options
     * field) - to define the table's columns from the form instead of the
     * CSV file's second line. "column" is matched against the CSV file's
     * header row (row 1), case-insensitively, to work out which value in
     * each data row belongs to it.
     *
     * Not calling this at all (or calling it with an empty array) leaves
     * the original behaviour unchanged: columns/options come from the CSV
     * file itself.
     */
    public function setOptions($options) {
        if (!is_array($options) || count($options) === 0) {
            $this->columnOptions = null;
            return;
        }

        foreach ($options as $option) {
            if (!array_key_exists("heading", $option) || !array_key_exists("column", $option) || !array_key_exists("options", $option)) {
                echo "ERROR: RLeafletCsvList options does not contain heading/column/options field";
                $this->columnOptions = null;
                return;
            }
        }

        $this->columnOptions = $options;
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
        $useFormOptions = is_array($this->columnOptions) && count($this->columnOptions) > 0;
        $csvPositions = []; // only used when $useFormOptions: list index => CSV column position

        if (($handle = fopen($this->filename, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, null, ",")) !== FALSE) {
                $num = count($data);

                if ($useFormOptions) {
                    // Columns/options come from the form, not the file - row 1
                    // is only used to find which CSV column each configured
                    // "column" name refers to; data starts on row 2.
                    if ($row === 1) {
                        $lookup = array_map(fn($h) => strtolower(trim((string) $h)), $data);
                        foreach ($this->columnOptions as $option) {
                            $column = new Column($option["heading"]);
                            $column->addOptions($option["options"]);
                            $column->columnName = $option["column"];
                            $this->list->addColumn($column);

                            $pos = array_search(strtolower(trim((string) $option["column"])), $lookup, true);
                            $csvPositions[] = $pos === false ? null : $pos;
                            if ($pos === false) {
                                echo '<p>Column "' . htmlspecialchars((string) $option["column"]) . '" was not found in the CSV file\'s header row</p>';
                            }
                        }
                    } else {
                        foreach ($this->list->getColumns() as $i => $column) {
                            $pos = $csvPositions[$i] ?? null;
                            if ($pos !== null && array_key_exists($pos, $data)) {
                                $column->addValue($data[$pos]);
                            }
                        }
                    }
                } else {
                    // Legacy behaviour, unchanged: row 1 = headers, row 2 =
                    // per-column options, row 3+ = data.
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
