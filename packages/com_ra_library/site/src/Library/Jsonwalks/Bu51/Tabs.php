<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Bu51;

/**
 * Description of WalksDisplay
 *
 * @author Paul Rhodes
 */
// no direct access
defined("_JEXEC") or die("Restricted access");

use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std\Display;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;

class Tabs extends Display {

    public $feedClass = "walksfeed"; // not used?
    public $tabOrder = ['List', 'Calendar', 'Map'];
    public $listFormat = "{gradeimg}{title}{lf}{dowdd}{ meet at ?meetTime}{ start at ?startTime}{ estimated finish at ?finishTime}{, ?distance}{, ?nationalGrade}{, ?shape}{walk} ";

    public function __construct() {
        parent::__construct();
        parent::setTabOrder($this->tabOrder);
        parent::setCustomListFormat($this->listFormat);
        Load::addStyleSheet("media/com_ra_library/js/jsonwalks/bu51/bu51style.css");
    }

    public function DisplayWalks($walks) {
        parent::DisplayWalks($walks);
    }
}
