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
        parent::customFormat($this->listFormat);
    }

    public function customFormat($format) {
        parent::customFormat($format);
    }

    public function DisplayWalks($walks) {

        parent::DisplayWalks($walks);
    }
}
