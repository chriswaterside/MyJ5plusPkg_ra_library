<?php
namespace Ramblers\Component\Ra_library\Site\Library\Walkseditor;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

use Ramblers\Component\Ra_library\Site\Library\Load\Load;

class Walkseditor {

    public static function addScriptsandCss() {

        HTMLHelper::_('jquery.framework');
        Load::addStyleSheet("media/com_ra_library/css/ramblerslibrary.css");

        $path = "media/com_ra_library/js/walkseditor/";
        //Load::addScript($path . "js/programmeitems.js");
        Load::addScript($path . "js/walk.js");
        Load::addScript($path . "js/inputfields.js");
        Load::addScript($path . "js/loader.js");
        Load::addScript($path . "js/maplocation.js");
        Load::addScript($path . "js/placeEditor.js");
        Load::addScript($path . "js/comp/viewAllWalks.js");
        Load::addScript($path . "js/comp/viewAllPlaces.js");
        Load::addScript($path . "js/walkeditor.js");
        Load::addScript($path . "js/walksEditorHelps.js");
        Load::addStyleSheet($path . "css/style.css");

        //  Load::addScript($path . "js/walksprogramme.js");
        Load::addScript($path . "js/viewWalks.js");
        $doc = Factory::getDocument();
        $doc->addScript("https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js");
        $doc->addStyleSheet("https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css", "text/css");
        Load::addScript("media/com_ra_library/js/ra.tabs.js");
        Load::addStyleSheet("media/com_ra_library/css/ra.tabs.css");
        Load::addStyleSheet('media/com_ra_library/js/vendors/cvList/cvList.css');
        Load::addScript("media/com_ra_library/js/vendors/cvList/cvList.js");
    }
}
