<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Bu51;

/**
 * Description of WalksDisplay
 *
 * @author Chris Vaughan
 */
// no direct access
defined("_JEXEC") or die("Restricted access");

use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std\Simplelist;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;

class Fulldetails extends Simplelist {

    public $walksClass = "bu51Nextwalk";
    private $listFormat = "{gradeimg}{title}{lf}{dowddmmyyyy}{ meet at ?meetTime}{ start at ?startTime}{ estimated finish at ?finishTime}{, ?distance}{, ?nationalGrade}{, ?shape}{ ?walk}";
    private $nowalks = 20;

    public function __construct() {
        parent::__construct();
        parent::customFormat($this->listFormat);
        parent::setWalksClass($this->walksClass);
        Load::addStyleSheet("media/com_ra_library/js/jsonwalks/bu51/bu51style.css");
    }

    public function noWalks($no) {
        $this->nowalks = $no;
    }

    public function DisplayWalks($walks) {
        $walks->limitNumberWalks($this->nowalks);
        parent::DisplayWalks($walks);
    }
}
