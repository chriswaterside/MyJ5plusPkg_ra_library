<?php

/**
 * Description of WalksDisplay
 *
 * @author Chris Vaughan
 */
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std;
// no direct access
defined("_JEXEC") or die("Restricted access");

class Nextwalks extends Simplelist {

    public $walkClass = "nextwalk";
    public $feedClass = "walksfeed"; // not used?
    private $listFormat = "{gradeimg}{dowddmm}{, ?title}{, ?distance}";

    private $nowalks = 5;

    public function __construct() {
        parent::__construct();
        parent::customFormat($this->listFormat);
        parent::setWalksClass($this->walkClass);
    }

    public function noWalks($no) {
        $this->nowalks = $no;
    }
    
     public function DisplayWalks($walks) {
        $walks->noWalks($this->nowalks);
        parent::DisplayWalks($walks);
    }

}
