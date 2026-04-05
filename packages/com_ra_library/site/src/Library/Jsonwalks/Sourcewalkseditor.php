<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks;
/**
 * @version		0.0
 * @package		Process Walks Editor walks and convert to internal format
 * @author              Chris Vaughan Ramblers-webs.org.uk
 * @copyright           Copyright (c) 2021 Chris Vaughan. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */
// no direct access
defined('_JEXEC') or die('Restricted access');
use Ramblers\Component\Ra_library\Site\Library\Feedhelper\Feedhelper;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk\Admin;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk\Basics;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk\Contact;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk\Flags;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk\Timelocation;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk\Walk as actualWalk;
use Joomla\CMS\Factory;

class Sourcewalkseditor extends Sourcebase {

    private $rafeedurl;
    private $groupCode;
    private $groupName;
    private $site;
    private $srfr;
    private $feedPathJ5 = "index.php?option=com_ra_walks_editor&view=walks&format=json";
  
    // private $feedPath = 'index.php?option=com_ra_walkseditor&task=walks.controller&format=json';

    const TIMEFORMAT = "Y-m-d\TH:i:s";

    public function __construct($type = SourceOfWalk::WEditor) {
        parent::__construct(SourceOfWalk::WEditor);
    }

    public function _initialise($groupCode = null, $groupName = null, $site = null) {
        $this->groupCode = $groupCode;
        $this->groupName = $groupName;
        $this->site = $site;
    }

    public function getWalks($walks) {
     
        $this->rafeedurl = $this->site . $this->feedPathJ5;
        $CacheTime = 5; // minutes
        $cacheLocation = $this->CacheLocation();
        $this->srfr = new Feedhelper($cacheLocation, $CacheTime);
        $this->srfr->setReadTimeout(15);
        $response = $this->readFeed($this->rafeedurl);
        If ($response->version === '1.0') {
            $newwalks = $response->walks;
            If ($newwalks !== null) {
                foreach ($newwalks as $item) {
                    $id = $item->admin->id;
                    $walk = new Walk();
                    $this->convertToInternalFormat($walk, $item, $this->groupCode . "-" . strval($id));
                    $walks->addWalk($walk);
                }
            } else {
                $app = Factory::getApplication();
                $app->enqueueMessage('Walks Editor other than version 1.0 is not supported', 'error');
            }
        }
    }

    private function readFeed($rafeedurl) {
        $result = $this->srfr->getFeed($rafeedurl, "Group Walks Editor Programme");
        $json = $this->checkJsonFeed($rafeedurl, "Walks", $result);
        return $json->data;
    }

    private static function checkJsonFeed($feed, $feedTitle, $result) {
        $status = $result["status"];
        $contents = $result["contents"];

        switch ($contents) {
            case NULL:
                RErrors::notifyError($feedTitle . ' feed: Unable to read feed [Null response, Error 1]', $feed, 'error');
                break;
            case "":
                echo '<b>No ' . $feedTitle . ' found.</b> [Error 2]';
                break;
            case "[]":
                // echo '<b>Sorry no ' . $feedTitle . ' found</b>';
                break;
            default:
                $json = json_decode($contents);
                unset($contents);
                $error = json_last_error();
                if ($error == JSON_ERROR_NONE) {
                    return $json;
                } else {
                    $errormsg = json_last_error_msg();
                    RErrors::notifyError('Feed is not in Json format: ' . $errormsg . ' [Error 4]', $feed, 'error');
                }
                return null;
        }
    }

    private function convertToInternalFormat($walk, $item, $id) {

        // admin

        $source = SourceOfWalk::WEditor;
        $groupCode = $this->groupCode;
        $groupName = $this->groupName;
        $type = TypeOfWalk::GroupWalk;
        $status = $item->admin->status;
        if ($status === "Draft") {
            $status = "Published"; // fix
        }
        $cancellationReason = $item->admin->cancelledReason;
        $dateUpdated = \DateTime::createFromFormat(self::TIMEFORMAT, substr($item->admin->updated, 0, 19));
        $dateCreated = \DateTime::createFromFormat(self::TIMEFORMAT, substr($item->admin->created, 0, 19));
        $nationalUrl = "";

        $admin = new Admin($source, $type, $id, $groupCode, $groupName,
                $status, $cancellationReason, $nationalUrl, $dateUpdated, $dateCreated);
        $walk->addAdmin($admin);

        // Basics

        $walkDate = new \DateTime($item->basics->date);
        $finishDate = null;
        $title = $item->basics->title;
        $descriptionHtml = $item->basics->description;
        if ($descriptionHtml === null) {
            $descriptionHtml = '';
        }
        $additionalNotes = $item->basics->notes;
        $external_url = "";
        //    $walk->checkCancelledStatus();
        $basics = new Basics($admin, $walkDate, $finishDate, $title, $descriptionHtml,
                $additionalNotes, $external_url);
        $walk->addBasics($basics);

        // meeting

        $meetings = $item->meeting->locations;
        foreach ($meetings as $loc) {
            $str = $item->basics->date . "T" . $loc->time . ":00";
            $time = \DateTime::createFromFormat(self::TIMEFORMAT, $str);
            $name = $loc->name;
            $gridref = $loc->gridref8;
            $latitude = $loc->latitude;
            $longitude = $loc->longitude;
            $w3w = $loc->w3w;
            $postcode = "";
            if (property_exists($loc, 'postcode')) {
                $postcode = $loc->postcode->value;
            }
            $osmaps = null;
            $meet = new Timelocation("Meeting", "", $time, $name,
                    $latitude, $longitude, $gridref, $w3w,
                    $postcode, 0, 0, $osmaps);
            $walk->addMeeting($meet);
        }

        // start

        $loc = $item->start->location;

        switch ($item->start->type) {
            case 'start':
                $type = "Start";
                break;
            case 'area':
                $type = "Rough";
                break;
            default:
                $app = Factory::getApplication();
                $app->enqueueMessage('Walks Editor incorrect start type', 'error');
                $type = "Rough";
        }
        // $time = $loc->time . ":00";

        if (property_exists($loc, 'time')) {
            $str = $item->basics->date . "T" . $loc->time . ":00";
            $time = \DateTime::createFromFormat(self::TIMEFORMAT, $str);
        } else {
            $time = null;
        }
        $name = $loc->name;
        $gridref = $loc->gridref8;
        $latitude = $loc->latitude;
        $longitude = $loc->longitude;
        $w3w = "";
        if (property_exists($loc, 'w3w')) {
            $w3w = $loc->w3w;
        }
        $postcode = "";
        if (property_exists($loc, 'postcode')) {
            $postcode = $loc->postcode->value;
        }
        $osmaps = null;
        // do not use $osmaps as it does not have bounds    
        $tl = new Timelocation($type, "", $time, $name,
                $latitude, $longitude, $gridref, $w3w,
                $postcode, 0, 0, $osmaps);
        $walk->addStart($tl);

        // walk

        $walks = $item->walks;
        foreach ($walks as $w) {
            $shape = $w->type;
            $nationalGrade = $w->natgrade;
            $localGrade = $w->gradeLocal;
            if ($w->units === 'miles') {
                $distanceKm = $w->distance / 0.621371;
            } else {
                $distanceKm = $w->distance;
            }
            $pace = '';
            $ascent = '';
            if ($w->ascentMetres !== 0) {
                $ascent = strval($w->ascent_metres) . " m";
            }
            $singleWalk = new actualWalk($shape, $nationalGrade, $localGrade, $distanceKm, $pace, $ascent);
            $walk->addWalk($singleWalk);
        }

        // contact

        $c = $item->contact;
        $isLeader = $c->contactType === "isLeader";
        $contactName = $c->displayName;
        $email = $c->email;
        $contactForm = "";
        $telephone1 = $c->telephone1;
        $telephone2 = $c->telephone2;
        $contact = new Contact($id, $isLeader, $contactName, $email, $telephone1, $telephone2, $contactForm);
        $walk->addContact($contact);

        //  Flags
        $flags = new Flags();
        if (property_exists($item, 'facilities')) {
            $flags->addWalksEditorFlags("Facilities", $item->facilities);
        }
        if (property_exists($item, 'transport')) {
            $flags->addWalksEditorFlags("Transport", $item->transport);
        }
        if (property_exists($item, 'accessibility')) {
            $flags->addWalksEditorFlags("Accessibility", $item->accessibility);
        }
        $walk->setFlags($flags);

        //     $walk->media = $walk->getMedia($item);
    }
}
