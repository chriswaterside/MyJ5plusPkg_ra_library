<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of feedbase
 *
 * @author chris
 */
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks;

abstract class Sourcebase {

    protected $type;

    public function __construct($type = "") {
        $this->type = $type;
    }

    abstract protected function getWalks($walk);

  //  abstract protected function _initialise($arg1 = null, $arg2 = null, $arg3 = null);

    protected function CacheLocation() {
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        return 'cache' . DS . 'ra_feed';
    }

}

abstract class SourceOfWalk {

    const Unknown = '?';
    const GWEM = 'gwem';
    const WManager = 'wm';
    const WEditor = 'we';

}

abstract class TypeOfWalk {

    const Unknown = '?';
    const GroupWalk = 'Walk';
    const GroupEvent = 'Event';
    const WellbeingWalk = 'Well being walk';

}
