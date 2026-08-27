<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std;

/**
 * Description of WalksDisplay
 *
 * @author Chris Vaughan
 */
// no direct access
defined("_JEXEC") or die("Restricted access");

class Fulldetails extends Simplelist {

     private $listFormat = "{gradeimg}{dowddmm}{, ?title}{, ?distance}";

    public function __construct() {
        parent::__construct();
        parent::inLineDisplay();
    }

    public function DisplayWalks($walks) {
        parent::customFormat($this->listFormat);
        parent::DisplayWalks($walks);
    }
}
