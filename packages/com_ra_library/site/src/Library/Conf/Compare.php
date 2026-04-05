<?php
namespace Ramblers\Component\Ra_library\Site\Library\Conf;

/**
 * Description of compare
 *
 * @author Chris Vaughan
 */
use Joomla\CMS\Uri\Uri;
abstract class Compare {

    private $type;
    private $comparefilename;
    private $comparefiletext;
    private $result = "invalid";

    const DIFFERENT = "&#10008;";
    const ADDITIONAL = "ADDITIONAL Items";
    const SAME = "&#10004;";
    const IDENTICAL = "&#10004;";

    public function __construct() {
        
    }

    public function compare($text, $options) {
        $this->type = $options["compare"];
        $this->comparefilename = $options["path"];
        $this->readCompareFile();

        if ($this->comparefiletext == $text) {
            If ($text == "") {
                $this->result = "...";
            } else {
                $this->result = self::IDENTICAL;
            }
        } else {
            if (isset($options["replace"])) {
                if ($options["replace"] == true) {
                    $domain = $options["domain"];
                    $this->comparefiletext = str_replace("THIS_DOMAIN", $domain, $this->comparefiletext);
                }
            }

            $this->result = $this->comparetext($text, $this->comparefiletext);
        }
        $displaydetails = $options["displaydetails"];
        $folder = $options["folder"];
        $link = "";
        if ($displaydetails == RAccountsLogfile::DISP_DETAILS) {
            if ($this->result == self::DIFFERENT or $this->result == self::ADDITIONAL) {
                $rows = min([$this->getCount1(), $this->getCount2()]);
                If ($rows < 4) {
                    $rows = 4;
                } If ($rows > 10) {
                    $rows = 10;
                }
                $file = $options["file"];
                echo "<h4>" . $folder . "/" . $file . "</h4>";
                echo " <textarea rows='$rows' cols='80' style='width:auto;font-size: 85%;'>" . $text . "</textarea>";
                echo " <textarea rows='$rows' cols='80' style='width:auto;font-size: 85%;'>" . $this->comparefiletext . "</textarea>";
            }
        }
        if ($displaydetails == RAccountsLogfile::DISP_VIEW) {
            if ($this->result == self::SAME or $this->result == self::IDENTICAL) {
                if ($this->comparefilename != "") {
                    $link = " <a href='".Uri::root()  ."$this->comparefilename' target='_blank'>view</a>";
                }
            }
        }

        return $this->result . $link;
    }

    abstract function comparetext($text, $text2);

    abstract function getCount1();

    abstract function getCount2();

    private function readCompareFile() {
        if ($this->comparefilename == "") {
            $this->comparefiletext = "";
        } else {
            $this->comparefiletext = file_get_contents($this->comparefilename);
            if ($this->comparefiletext === false) {
                echo "Invalid comparison file: " . $this->comparefilename;
                $this->comparefiletext = "";
            }
        }
    }

}
