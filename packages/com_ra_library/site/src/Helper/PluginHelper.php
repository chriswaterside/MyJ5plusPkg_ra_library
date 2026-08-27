<?php

namespace Ramblers\Component\Ra_library\Site\Helper;

\defined('_JEXEC') or die;

class PluginHelper {

    public static function renderView($viewName, $display) {
        switch ($viewName) {
            case 'display':
                ob_start();
                $display = new DisplayHelper($display->displayoption, $display->options);
                $display->Display();
                $output = ob_get_clean();
                return $output;

            default:
                return '<div class="ra-library-error">Unknown view: '
                        . htmlspecialchars($viewName, ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }
}
