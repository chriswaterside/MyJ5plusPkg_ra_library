<?php
namespace Ramblers\Component\Ra_library\Site\Library\Load;
use Joomla\CMS\Factory;
class Load {

    public static function addScript($path, $type = "text/javascript") {
        if (!str_starts_with($path, "http")) {
            $filemtime = filemtime($path);
        } else {
            $filemtime = 0;
        }
        $document = Factory::getDocument();
        $document->addScript($path . "?rev=" . $filemtime, array('type' => $type));
    }

    public static function addStyleSheet($path, $type = "text/css") {
        if (!str_starts_with($path, "http")) {
            $filemtime = filemtime($path);
        } else {
            $filemtime = 0;
        }
        $document = Factory::getDocument();
        $document->addStyleSheet($path . "?rev=" . $filemtime, array('type' => $type));
    }
}
