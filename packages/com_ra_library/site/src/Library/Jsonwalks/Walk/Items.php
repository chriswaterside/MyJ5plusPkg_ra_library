<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk;
/**
 * Description of items
 *
 * @author Chris Vaughan
 */
class Items implements \JsonSerializable {

    private $items = [];

    public function addItem($newItem) {
        if ($newItem !== null) {
            array_push($this->items, $newItem);
        }
    }

    public function getValue($option) {
        if (count($this->items) > 0) {
            return $this->items[0]->getValue($option);
        }
        return "";
    }

    public function getItems() {
        return $this->items;
    }

    public function isEmpty() {
        return count($this->items) === 0;
    }

    public function getIntValue($option) {
        if (count($this->items) > 0) {
            return $this->items[0]->getIntValue($option);
        }
        return "";
    }

//    public function forEach($fcn) {
//        foreach ($this->items as $item) {
//            $fcn($item);
//        }
//    }

    public function jsonSerialize(): mixed {
        return $this->items;
    }

}
