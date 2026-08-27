<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of basics
 *
 * @author chris
 */
class Flags implements \JsonSerializable {

    private $items = [];       // is an array of RJsonwalksWalkFlag describing the walk

    public function addWalksEditorFlags($section, $values) {
        foreach ($values as $value) {
            $flag = new Flag($section, "", $value->name);
            $this->items[] = $flag;
        }
    }

    public function addWalksManagerFlags($section, $values) {
        if ($values === null) {
            return;
        }
        foreach ($values as $value) {
            $flag = new Flag($section, $value->code, $value->description);
            $this->items[] = $flag;
        }
    }

    public function flagExists($flag) {
        foreach ($this->items as $item) {
            if ($item->isFlag(($flag))) {
                return true;
            }
        }
        return false;
    }

    public function jsonSerialize(): mixed {
        return $this->items;
    }

}
