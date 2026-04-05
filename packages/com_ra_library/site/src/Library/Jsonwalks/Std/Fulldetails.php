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

    public $nationalGradeHelp = ""; // not used now!
    public $localGradeHelp = ""; // not used now!
    public $nationalGradeTarget = "_parent"; // not used now!
    public $localGradeTarget = "_parent"; // not used now!
    public $popupLink = true; // not used now!
    public $addContacttoHeader = false;
    public $displayGroup = null;  // not used now
    private $listFormat = ["{gradeimg}", "{dowddmm}",
        "{,title}",
        "{,distance}",
        "{,contactname}",
        "{,telephone}"];

    public function __construct() {
        parent::__construct();
        parent::inLineDisplay();
    }

    public function DisplayWalks($walks) {
        if (!$this->addContacttoHeader) {
            foreach (array_keys($this->listFormat, "{,contactname}", true) as $key) {
                unset($this->listFormat[$key]);
            }
            foreach (array_keys($this->listFormat, "{,telephone}", true) as $key) {
                unset($this->listFormat[$key]);
            }
        }
        parent::customFormat($this->listFormat);
        parent::DisplayWalks($walks);
    }

}
