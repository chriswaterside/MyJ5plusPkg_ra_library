<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of structuredlocation
 *
 * @author Chris
 */
class Structuredlocation {

    public $type;
    public $name;
    public $address;

    public function __construct($name, $address) {
        $this->type = "Place";
        $this->name = $name;
        $this->address = $address;
    }

}
