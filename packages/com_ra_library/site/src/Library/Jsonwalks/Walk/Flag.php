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
class Flag implements \JsonSerializable {

    public $section = "";       // name of the section in which the flag is contained
    public $code = "";
    public $name = "";

    public function __construct($section, $code, $name) {
        $this->section = $section;
        $this->code = $code;
        $this->name = $name;
    }

    public function isFlag($flag) {
        return $flag === $this->name;
    }

    public function jsonSerialize(): mixed {
        return ['section' => $this->section,
            'code' => $this->code,
            'name' => $this->name
        ];
    }

}
