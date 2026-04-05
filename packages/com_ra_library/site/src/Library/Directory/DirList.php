<?php
namespace Ramblers\Component\Ra_library\Site\Library\Directory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

class DirList {

    protected $handle;
    private $names;
    private $fileTypes;

    const ASC = 1;
    const DESC = 2;

    public function __construct($fileTypes) {
        $this->fileTypes = $fileTypes;
    }

    public function listItems($folder, $sort = self::ASC) {
        $this->names = array();
        if (!file_exists($folder)) {
            $text = "Folder does not exist: " . $folder . ". Unable to list contents";
            $application = Factory::getApplication();
            $application->enqueueMessage(Text::_($text), 'error');
            echo "<b>Not able to list contents of folder<b>";
            return;
        }
        if ($handle = opendir($folder)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    if (is_dir($entry)) {
                        
                    } else {
                        $this->names[] = $entry;
                    }
                }
            }
            closedir($handle);
        }
        natcasesort($this->names);

        if ($sort == self::DESC) {
            $this->names = array_reverse($this->names);
        }
        echo "<ul>";
        foreach ($this->names as $value) {
            foreach ($this->fileTypes as $type) {
                $desc = "";
                $descfile = $folder . "/" . $value . ".text";
                if (file_exists($descfile)) {
                    $desc = " - " . file_get_contents($descfile);
                    $desc = strip_tags($desc, '<b><i><br><br/>');
                }
                $descfile = $folder . "/" . $value . ".txt";
                if (file_exists($descfile)) {
                    $desc = " - " . file_get_contents($descfile);
                    $desc = strip_tags($desc, '<b><i><br><br/>');
                }
                if ($this->endsWith($value, $type)) {
                    $text = $value;
                    $text = str_replace("-", " ", $text);
                    $text = str_replace("_", " ", $text);
                    echo "<li><a href='" . Uri::base() . $folder . "/" . $value . "' target='_blank'>" . $text . "</a>" . $desc . "</li>\n";
                }
            }
        }
        echo "</ul>";
    }

    private function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || strpos($haystack, $needle, strlen($haystack) - strlen($needle)) !== FALSE;
    }

}
