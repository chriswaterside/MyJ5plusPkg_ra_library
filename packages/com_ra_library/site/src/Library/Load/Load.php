<?php

namespace Ramblers\Component\Ra_library\Site\Library\Load;

use Joomla\CMS\Factory;

class Load {

    public static function addScript($path) {
        if (!str_starts_with($path, "http")) {
            $filemtime = filemtime($path);
        } else {
            $filemtime = 0;
        }
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseScript('ra-library.' . md5($path), $path, ['version' => $filemtime]);
    }

    public static function addStyleSheet($path) {
        if (!str_starts_with($path, "http")) {
            $filemtime = filemtime($path);
        } else {
            $filemtime = 0;
        }
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ra-library.' . md5($path), $path, ['version' => $filemtime]);
    }
}
