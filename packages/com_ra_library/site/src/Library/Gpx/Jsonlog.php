<?php
use Ramblers\Component\Ra_library\Site\Library\Gpx;

/**
 * Description of jsonlogfile
 *
 * @author Chris Vaughan
 */
class Jsonlog {

    private $file;
    private $items;
    private $items_processed = false;

    public function __construct($file) {
        $this->file = $file;
        $this->items = [];
    }

    public function readFile() {
        $this->items = [];
        if (file_exists($this->file)) {
            $string = file_get_contents($file);
        } else {
            $string = false;
        }

        if ($string !== false) {
            $this->items = json_decode($string, true);
            if ($this->items === null) {
                // error
                $this->items = [];
            }
        }
        $this->items_processed = false;
    }

    public function addItem($name, $item) {
        $this->items[] = $item;
        $this->items_processed = true;
    }

    Public function writeFile() {
        if ($this->items_processed) {
            //  $myJSON = json_encode($this->items, JSON_PRETTY_PRINT);
            $myJSON = json_encode($this->items);
            //echo $myJSON;
            file_put_contents($this->file, $myJSON);
        }
    }

}
