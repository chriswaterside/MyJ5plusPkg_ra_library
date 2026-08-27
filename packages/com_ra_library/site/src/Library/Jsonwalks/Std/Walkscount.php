<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std;

/**
 * Description of WalksDisplay
 *
 * @author Chris Vaughan
 */
// no direct access
defined("_JEXEC") or die("Restricted access");

use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Displaybase;

class Walkscount extends Displaybase {

    public $tag = "p";
    public $startText = "Number of walks: ";
    public $endText = "";

    function DisplayWalks($walks) {

        $no = $this->numberwalks($walks);
        echo "<" . $this->tag . ">" . $this->startText . $no . $this->endText . "</" . $this->tag . ">" . PHP_EOL;
    }

    private function numberwalks($walks) {
        return count($walks->allWalks());
    }
}
