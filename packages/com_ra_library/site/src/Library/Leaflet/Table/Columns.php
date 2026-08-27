<?php
namespace Ramblers\Component\Ra_library\Site\Library\Leaflet\Table;

/**
 * Description of Columns
 *
 * @author Chris Vaughan
 */
class Columns implements \JsonSerializable {

    private $columns = [];
    private $displayMap = false;
    private $paginateList = true;

    public function __construct() {
        
    }

    public function addColumn($column) {
        $this->columns[] = $column;
    }

    public function getColumn($no) {
        if (array_key_exists($no, $this->columns)) {
            return $this->columns[$no];
        } else {
            return null;
        }
    }

    public function getColumns() {
        return $this->columns;
    }

    public function removeIgnoredColumns() {
        foreach ($this->columns as $key => $col) {
            if ($col->getIgnore()) {
                unset($this->columns[$key]);
            }
        }
        $this->columns = array_values($this->columns);
    }

    public function jsonSerialize(): mixed {
        return [
            'items' => $this->columns,
            'displayMap' => $this->displayMap,
            'paginateList' => $this->paginateList
        ];
    }
}
