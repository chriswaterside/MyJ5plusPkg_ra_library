<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks;

/*
 * schema.org definition of walk
 */

/**
 * Description of structuredevent
 *
 * @author Chris
 */
class Structuredevent {

    //put your code here
    public $context;
    public $type;
    public $name;
    public $startdate;
    public $enddate;
    public $image;
    public $description;
    public $url;
    public $location;
    public $performer;

    public function __construct($performer, $location) {
        $this->context = "http://schema.org/";
        $this->type = "Event";
        $this->performer = $performer;
        $this->location = $location;
    }

}
