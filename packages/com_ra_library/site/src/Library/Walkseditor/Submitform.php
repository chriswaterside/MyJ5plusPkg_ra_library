<?php
namespace Ramblers\Component\Ra_library\Site\Library\Walkseditor;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Map as LeafletMap;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;

class Submitform extends LeafletMap {

    private $groups = null;
    private $coords = null;
    private $localGrades = null;
    private $error = false;

    public function setWalksCoordinators($values) {
        if ($values == null) {
            $text = "No walks coordinators defined";
            $app = Factory::getApplication();
            $app->enqueueMessage($text, 'error');
            return;
        }
        if (is_array($values)) {
            $this->coords = $values;
            foreach ($this->coords as $value) {
                $userFactory = Factory::getContainer()->get(UserFactoryInterface::class);
                $user = $userFactory->loadUserById($value);
                if ($user->id == 0) {
                    $text = "Walk coordinator not found, id=" . $value;
                    $app = Factory::getApplication();
                    $app->enqueueMessage($text, 'error');
                    $this->error = true;
                }
                $user = null;
            }
        } else {
            $text = "Walk coordinators not defined as an array";
            $app = Factory::getApplication();
            $app->enqueueMessage($text, 'error');
        }
    }

    public function setGroups($values) {
        if (is_array($values)) {
            $this->groups = $values;
        } else {
            $text = "Groups not defined as an array";
            $app = Factory::getApplication();
            $app->enqueueMessage($text, 'error');
        }
    }

    public function setLocalGrades($values) {
        if (is_array($values)) {
            $this->localGrades = $values;
        } else {
            $text = "Local Grades not defined as an array";
            $app = Factory::getApplication();
            $app->enqueueMessage($text, 'error');
        }
    }

    public function display() {
        if ($this->groups == null) {
            $text = "No groups defined";
            $app = Factory::getApplication();
            $app->enqueueMessage($text, 'error');
            return;
        }
        if ($this->error) {
            return;
        }

        //  $this->help_page = "https://maphelp.ramblers-webs.org.uk/draw-walking-route.html";

        $this->options->settings = true;
        $this->options->mylocation = true;
        $this->options->rightclick = true;
        $this->options->fullscreen = true;
        $this->options->mouseposition = true;
        $this->options->postcodes = true;
        $this->options->fitbounds = true;
        $this->options->displayElevation = false;
        $this->options->cluster = false;
        $this->options->draw = false;
        $this->options->print = true;
        $this->options->ramblersPlaces = true;
        $this->options->controlcontainer = true;

        $this->data = new class {
            
        };
        $this->data->coords = $this->coords;
        $this->data->groups = $this->groups;
        $this->data->localGrades = $this->localGrades;

        $path = "media/com_ra_library/js/walkseditor/";
        Load::addScript($path . "js/form/submitwalk.js", "text/javascript");
        parent::setCommand('ra.walkseditor.form.submitwalk');
        parent::setDataObject($this->data);
        parent::display();
        Walkseditor::addScriptsandCss();
    }
}
