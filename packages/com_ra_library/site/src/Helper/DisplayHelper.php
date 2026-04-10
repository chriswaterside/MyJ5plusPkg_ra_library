<?php

namespace Ramblers\Component\Ra_library\Site\Helper;

/*
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */

/**
 * Description of DisplayHelper
 *
 * @author chris
 */
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Feedoptions;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Feed;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std\Areawalks;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std\Display;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std\Fulldetails;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std\Nextwalks;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std\Simplelist;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std\Walktable;
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Mapdraw;
use Ramblers\Component\Ra_library\Site\Library\License\License;
use \Ramblers\Component\Ra_library\Site\Library\Walkseditor\Submitform;
use \Ramblers\Component\Ra_library\Site\Library\Walkseditor\Programme;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Av01\Processwalks as AV01Processwalks;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Bu51\Fulldetails as BU01Fulldetails;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Bu51\Groupstabs as  BU01Groupstabs;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Bu51\Micronextwalks as  BU01Micronextwalks;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Bu51\Tabs as  BU01Tabs;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Ml\MLPrint as  MLPrint;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Ns\Walksprinted as  NSWalksprinted;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Sr02\Walksprinted as  SR02Display;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Sr02\Walksprinted as  SR02Nextwalks;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Sr02\Walksprinted as  SR02Table2;


class DisplayHelper {

    private $options;
    private $data;

    public function __construct($options) {
        $this->options = $options;
        $this->data = json_decode($options);
    }

    Public function Display() {
        $data = $this->data;
       if ($data->addarticles==='1' && $data->before) {
            echo $data->before;
        }
        switch ($data->displaytype) {
            case "events":
                $this->displayEvents();
                break;
            case "plot":
                $this->displayPlot();
                break;
            case "weprogramme":
                $this->WalkseditorProgramme();
                break;
            case "wesubmit":
                $this->WalkseditorSubmit();
                break;
        }
        if ($data->addarticles==='1' && $data->after) {
            echo $data->after;
        }
    }

    private function displayEvents() {
        $data = $this->data;
        $codes = array_column($data->ramblersgroups, 'code');
        $groups = implode(",", $codes);
        $options = new Feedoptions();
        $options->addWalksMangerGroupWalks($groups);

        $feed = new Feed($options);
        switch ($data->walksdisplayoption) {
            case 'nextwalks':
                $display = new Nextwalks();
                break;
            case 'display':
                $display = new Display();
                break;
            case 'calendar':
                $display = new Display();
                break;
            case 'table':
                $display = new Walktable();
                break;
            case 'list':
                $display = new Simplelist();
                break;
            case 'map':
                $display = new Display();
                break;
            case 'fulldetails':
                $display = new Fulldetails();
                break;
            case 'av01a':
                $display = new AV01Processwalks();
                break;
            case 'BU51a':
                $display = new BU01Fulldetails();
                break;
            case 'BU51b':
                $display = new BU01Groupstabs();
                break;
            case 'BU51c':
                $display = new BU01Micronextwalks();
                break;
            case 'BU51d':
                $display = new BU01Tabs();
                break;
            case 'MLa':
                $display = new MLPrint();
                break;
            case 'NSa':
                $display = new NSWalksprinted();
                break;
            case 'SR02a':
                $display = new SR02Display();
                break;
            case 'SR02b':
                $display = new SR02Nextwalks();
                break;
            case 'SR02c':
                $display = new SR02Table2();
                break;
        }

        $feed->Display($display);
    }

    private function displayPlot() {
        $data = $this->data;
        $latitude = $data->latitude;
        $longitude = $data->longitude;
        $zoomlevel = $data->zoomlevel;
        $routingkey = $data->routingkey;
        if ($routingkey) {
            License::OpenRoutingServiceKey($routingkey);
        }
        $display = new Mapdraw();
        $display->setCenter($latitude, $longitude, $zoomlevel); // lat, long, zoom
        $display->display();
    }

    private function WalkseditorProgramme() {
        $data = $this->data;
        $form = new Programme();
        $groups = $data->ramblersgroups;
        $gs = [];
        foreach ($groups as $value) {
            $gs[$value->code] = $value->name;
        }

//        $groups = [
//            'DE01' => 'Amber Valley',
//            'DE02' => 'Derby and S Derbyshire'
//        ];
        $form->setGroups($this->getRamblersGroups());
        $localGrades = ['Grade A' => 'Grade A - short walks',
            'Grade B' => 'Grade B - medium length walks',
            'Grade S' => 'Grade S - Long difficult walks'];

        $form->setLocalGrades($localGrades);
        $form->display();
    }

    private function WalkseditorSubmit() {
        $data = $this->data;
        $form = new Submitform();
        $form->setGroups($this->getRamblersGroups());
        $localGrades = ['Grade A' => 'Grade A - short walks', 'Grade B' => 'Grade B - medium length walks', 'Grade S' => 'Grade S - Long difficult walks'];
      
        //$coords = [972, 976];
      
            $form->setLocalGrades($localGrades);
        $coords = explode(",", $data->programmecoords);
        $form->setWalksCoordinators($coords);
        $form->display();
    }

    private function getRamblersGroups() {
        $data = $this->data;
        $groups = $data->ramblersgroups;
        $gs = [];
        foreach ($groups as $value) {
            $gs[$value->code] = $value->name;
        }
        return $gs;
    }
}
