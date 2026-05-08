<?php
namespace Ramblers\Component\Ra_library\Site\Library\Organisation;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of area
 *
 * @author Chris
 */
class Area extends Group{
    public $groups;
    
    function __construct($item) {
        parent::__construct($item);
        $this->groups=array();
        }

    public function addGroup($group) {
        $this->groups[$group->code] = $group;
    }

    public function sort(){
        ksort($this->groups);
    }
}
