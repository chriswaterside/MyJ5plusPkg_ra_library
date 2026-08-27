<?php

namespace Ramblers\Component\Ra_library\Site\Library\Gpx;

/**
 * Description of statistic
 *
 * @author Chris Vaughan
 */
class Statistic {

    private static $lastId = 0;
    Public $id = 0;
    public $filename = '';
    public $title = '';
    public $description = '';
    public $date = '';
    public $author = '';
    //   public $copyright = '';
    public $longitude = 0;
    public $latitude = 0;
    public $endLongitude = 0;
    public $endLatitude = 0;
    public $distance = 0;
    public $cumulativeElevationGain = 0;
    public $minAltitude = 0;
    public $maxAltitude = 0;
    public $tracks = 0;
    public $routes = 0;
    public $links = [];

    public function __construct() {
        $this->id = self::$lastId;
        self::$lastId += 1;
    }
}
