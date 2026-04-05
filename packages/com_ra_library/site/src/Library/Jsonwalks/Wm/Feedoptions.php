<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Wm;
/**
 * Description of feedOptions
 *
 * @author Chris Vaughan
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

class Feedoptions {

    public $date_start = null;
    public $date_end = null;
    public $groupCode = null;
    public $limit = null;
    public $include_walks = true;
    public $include_events = false;
    public $include_wellbeing_walks = false;
    public $latitude = null;
    public $longitude = null;
    public $distance = null;

    public function __construct() {
        
    }

    public function getFeedURL() {
        $url = WALKMANAGER;
        $url = $url . "api-key=" . APIKEY;
        // Now add the type of information to return
        $types = [];
        if ($this->include_walks) {
            $types[] = "group-walk";
        }
        if ($this->include_events) {
            $types[] = "group-event";
        }
        if ($this->include_wellbeing_walks) {
            $types[] = "wellbeing-walk";
        }
        $url = $url . "&types=" . implode(",", $types);

        if ($this->groupCode !== null) {
            $url = $url . '&groups=' . strtoupper($this->groupCode);
        }
        if ($this->latitude !== null) {
            $url = $url . '&latitude=' . $this->latitude;
        }
        if ($this->longitude !== null) {
            $url = $url . '&longitude=' . $this->longitude;
        }
        if ($this->distance !== null) {
            $url = $url . '&distance=' . $this->distance;
        }
        if ($this->date_start !== null) {
            $url = $url . '&date=' . $this->date_start;
        }
        if ($this->date_end !== null) {
            $url = $url . '&date_end=' . $this->date_end;
        }
        if ($this->limit !== null) {
            $url = $url . '&limit=' . $this->limit;
        }
        return $url;
    }

    public function getCacheFileName($extension) {
        $url = "";
        if ($this->include_walks) {
            $url = $url . "walks";
        }
        if ($this->include_events) {
            $url = $url . "events";
        }
        if ($this->include_wellbeing_walks) {
            $url = $url . "wellbeing-walks";
        }
        if ($this->groupCode !== null) {
            $url = 'cache_' . strtoupper($this->groupCode);
            $url = str_replace(',', '', $url);
        } else {
            if ($this->latitude !== null) {
                $url = $url . 'latitude' . $this->latitude;
            }
            if ($this->longitude !== null) {
                $url = $url . 'longitude' . $this->longitude;
            }
            if ($this->distance !== null) {
                $url = $url . 'distance' . $this->distance;
            }
        }

        if ($this->date_start !== null) {
            $url = $url . "_" . str_replace('-', '', $this->date_start);
        }
        if ($this->date_end !== null) {
            $url = $url . "_" . str_replace('-', '', $this->date_end);
        }

        if ($this->limit !== null) {
            $url = $url . '_limit' . $this->limit;
        }
        $url = str_replace('-', '_', $url);
        $url = str_replace(',', '_', $url);
        $url = str_replace('.', '_', $url);
        return $url . "." . $extension;
    }
}
