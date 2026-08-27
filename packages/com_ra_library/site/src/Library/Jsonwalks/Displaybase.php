<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks;

/**
 * Description of Displaybase
 *
 * @author Chris Vaughan
 */
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Script;
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Mapoptions;

abstract class Displaybase {

    //   protected $displayStartTime = false;// not supported?
    //   protected $displayStartDescription = false;// not supported?
    //   protected $printOn = false;
    //   public $displayGradesIcon = true;// not supported?
    //   public $displayGradesSidebar = true;// not supported?
    //   public $emailDisplayFormat = 1;
    // 1 display mailto link to contact, Obfuscated to prevent harvesting by bots and spammers, without visible changes to the address for human visitors.
    // 2 link to ramblers.org.uk form to email contact 
    // 3 do not display
    // 4 display as name (at) domain
    //    protected $dispMenu = 0;
    //    protected $dispArticle = 0;
    private $script = null;
    private $options = null;
    protected $walksClass = "walks";
    protected $walkClass = "walk";

    // 0 display walk via ramblers.org.uk
    // >0 display via local site article

    abstract protected function DisplayWalks($walks);

    public function __construct() {
        $this->script = new Script();
        $this->options = new Mapoptions();
        // default map options for display of walk
        $this->options->mapHeight = "250px";
        $this->options->rightclick = true;
        $this->options->fullscreen = true;
        $this->options->copyright = false;
        $this->script->add($this->options);
    }

//    public function alwaysDisplayStartTime($value) {
//        $this->displayStartTime = $value;
//    }
//
//    public function alwaysDisplayStartDescription($value) {
//        $this->displayStartDescription = $value;
//    }

    public function setWalksClass($class) {
        $this->walksClass = $class;
    }

    public function setWalkClass($class) {
        $this->walksClass = $class;
    }

    public function setTableClass($class) {
        $this->walksClass = $class;
    }
}
