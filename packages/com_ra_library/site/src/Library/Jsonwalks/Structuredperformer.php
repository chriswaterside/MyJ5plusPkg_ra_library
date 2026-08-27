<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of structuredperformer
 *
 * @author Chris
 */
class Structuredperformer {

    public $type;
    public $name;

    public function __construct($name) {
        $this->type = "Person";
        $this->name = $name;
    }

}
