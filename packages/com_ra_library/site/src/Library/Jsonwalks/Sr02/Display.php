<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Sr02;

/**
 * Description of RJsonwalksStdWalktable3col
 *
 * @author Chris Vaughan
 */
// no direct access
defined("_JEXEC") or die("Restricted access");
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std\Display as StdDisplay;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;


class Display extends StdDisplay {

    private $tableFormat = [
        ['title' => 'Date/Time', 'items' => ["{xdowddmm}", "{;startTime}"]],
        ['title' => 'Leader/Contact', 'items' => ["{xContact}", "{;telephone1}", "{;telephone2}"]],
        ['title' => 'Details', 'items' => ["{title}", "{;description}", "{lf}", "{Grid Ref: }", "{startGR}", "{ Postcode: }", "{startPC}", "{;additionalNotes}"]],
        ['title' => 'Distance', 'items' => ["{distance}", "{;xNationalGrade}", "{xSymbol}"]]
    ];
 

    public function __construct() {
        parent::__construct();
        parent::setCustomTableFormat($this->tableFormat);
        Load::addScript("media/com_ra_library/js/jsonwalks/sr02/display.js");
        Load::addStyleSheet("media/com_ra_library/js/jsonwalks/sr02/style.css");
    }
}