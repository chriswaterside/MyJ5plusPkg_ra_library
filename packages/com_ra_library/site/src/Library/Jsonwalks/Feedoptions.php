<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks;
/**
 * Description of feedoptions
 *
 * @author chris
 */
use Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

class Feedoptions {

    private $sources = [];

    public function __construct($value = null) {
        // input can be a list of groups or a null string
        if ($value === null) {
            return;
        }
        $groups = strtoupper($value);
        $this->addWalksManagerGroupWalks($groups);
    }

    public function addWalksMangerGroupWalks($groups) {
        $this->addWalksManagerGroupWalks($groups);
    }

    public function addWalksManagerGroupWalks($groups, $period = null) {
        $this->checkGroups($groups);
        $readwalks = true;
        $readevents = true;
        $wellbeingWalks = false;
        $source = new Sourcewalksmanager();
        $source->_initialise($groups, $readwalks, $readevents, $wellbeingWalks, $period);
        $this->sources[] = $source;
    }

    public function addWalksManagerWellbeingWalks($groups) {
        $this->addWalksMangerWellbeingWalks($groups);
    }

    public function addWalksMangerWellbeingWalks($groups) {
        $this->checkGroups($groups);
        $readwalks = false;
        $readevents = false;
        $wellbeingWalks = true;
        $source = new Sourcewalksmanager();
        $source->_initialise($groups, $readwalks, $readevents, $wellbeingWalks);
        $this->sources[] = $source;
    }

    public function addWalksManagerGroupsInArea($lat, $long, $km) {
        $readwalks = true;
        $readevents = true;
        $wellbeingWalks = false;
        $source = new Sourcewalksmanagerarea();
        $source->_initialiseArea($lat, $long, $km, $readwalks, $readevents, $wellbeingWalks);
        $this->sources[] = $source;
    }

    public function addWalksManagerWellbeingInArea($lat, $long, $km) {
        $readwalks = false;
        $readevents = false;
        $wellbeingWalks = true;
        $source = new Sourcewalksmanagerarea();
        $source->_initialiseArea($lat, $long, $km, $readwalks, $readevents, $wellbeingWalks);
        $this->sources[] = $source;
    }

    public function addWalksEditorWalks($groupCode, $groupName, $site) {
        if (str_contains($site, "<")) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_("Site parameter must not contain html tags: " . $site), "error");
        } else {
            $source = new Sourcewalkseditor();
            $source->_initialise($groupCode, $groupName, $site);
            $this->sources[] = $source;
        }
    }

    private function checkGroups($input) {
        $groups = explode(",", $input);
        foreach ($groups as $group) {
            $len = strlen($group);
            if ($len !== 2 && $len !== 4) {
                throw new \RuntimeException('Invalid group input');
            }
        }
    }

    public function getWalks($walks) {
        foreach ($this->sources as $source) {
            $source->getWalks($walks);
        }
        $walks->sort(Walk::SORT_DATE, Walk::SORT_TIME, Walk::SORT_DISTANCE);
        return;
    }
}
