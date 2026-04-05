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


class Groupstabs extends Display {

    public $walkClass = "bu51Nextwalk";
    public $feedClass = "walksfeed"; // not used?
    public $tabOrder = ['List', 'Map'];
    public $listFormat = ['{gradeimg}', '{group}', '{,title}', '{lf}',
        '{dowdd}', '{[ meet at ]meetTime}', '{[ start at ]startTime}', '{[ estimated finish at ]finishTime}',
        '{,distance}', '{,nationalGrade}','{,type}','{ walk}'];

    public function __construct() {
        parent::__construct();
        
        parent::setTabOrder($this->tabOrder);
        parent::setCustomListFormat($this->listFormat);
   //     parent::setWalksClass($this->walkClass);
        Load::addStyleSheet("media/com_ra_library/js/jsonwalks/bu51/bu51style.css", "text/css");
    }

    public function DisplayWalks($walks) {
        parent::DisplayWalks($walks);
    }

}
