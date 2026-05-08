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
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Leaflet\Mapmarker;
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Mapdraw;
use Ramblers\Component\Ra_library\Site\Library\License\License;
use Ramblers\Component\Ra_library\Site\Library\Walkseditor\Submitform;
use Ramblers\Component\Ra_library\Site\Library\Walkseditor\Programme;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Av01\Processwalks as AV01Processwalks;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Bu51\Fulldetails as BU01Fulldetails;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Bu51\Groupstabs as BU01Groupstabs;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Bu51\Micronextwalks as BU01Micronextwalks;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Bu51\Tabs as BU01Tabs;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Ml\MLPrint as MLPrint;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Ns\Walksprinted as NSWalksprinted;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Sr02\Display as SR02Display;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Sr02\Nextwalks as SR02Nextwalks;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Sr02\Table2 as SR02Table2;
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Gpx\Map as GpxMap;
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Gpx\Maplist as GpxMaplist;
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Csv\CsvList;
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Json\JsonList;
use Ramblers\Component\Ra_library\Site\Library\Leaflet\Sql\SqlList as SqlList;

class DisplayHelper {

    private $displayoption;
    //private $options;
    private $data;

    public function __construct($displayoption, $options) {
        $this->displayoption = $displayoption;
        //  $this->options = $options;
        $this->data = json_decode($options);
    }

    Public function Display() {
        $data = $this->data;
        if ($data->addarticles === '1' && $data->before) {
            echo $data->before;
        }
        switch ($this->displayoption) {
            case "routes_plot":
                $this->displayPlot();
                break;
            case "routes_display_single":
                $this->displayRoutesSingle();
                break;
            case "routes_display_multe":
                $this->displayRoutesMulti();
                break;
            case "table_csv":
                $this->displayTableCSV();
                break;
            case "table_sql":
                $this->displayTableSQL();
                break;
            case "table_json":
                $this->displayTableJson();
                break;
            case "documents_folder":

                break;
            case "draft_programme":
                $this->WalkseditorProgramme();
                break;
            case "draft_submit":
                $this->WalkseditorSubmit();
                break;
            default: // assume it is one of the many led walks options
                $this->displayEvents();
                break;
        }
        if ($data->addarticles === '1' && $data->after) {
            echo $data->after;
        }
    }

    private function displayEvents() {
        $data = $this->data;
        $feedoptions = $this->getFutureEventFeedOptions($data->eventsources);
        $feed = new Feed($feedoptions);
        $this->filtersEvents($feed, $data->filters);
        $display = $this->getEventsDisplay($this->displayoption);
        $this->customiseWalksDisplay($display, $this->displayoption, $data);
        $feed->Display($display);
    }

    private function getFutureEventFeedoptions($sources) {
        $feedoptions = new Feedoptions();
        foreach ($sources as $source) {
            switch ($source->load_type) {
                case 'wm':
                    $codes = array_column($source->ramblersgroups, 'code');
                    $groups = implode(",", $codes);
                    $feedoptions->addWalksMangerGroupWalks($groups);
                    break;
                case 'wmarea':
                    $lat = $source->arealatitude;
                    $long = $source->arealongitude;
                    $dist = $source->arearadius;
                    $feedoptions->addWalksManagerGroupsInArea($lat, $long, $dist);
                    break;
                case 'we':
                    foreach ($source->walkseditor as $value) {
                        $code = trim($value->Code);
                        $group = trim($value->Name);
                        $site = trim($value->Website);
                        $feedoptions->addWalksEditorWalks($code, $group, $site);
                    }
                    break;
                default:
                    throw new \RuntimeException('Invalid source of events');
            }
        }
        return $feedoptions;
    }

    private function filtersEvents($feed, $filters) {
        foreach ($filters as $filter) {
            if (!$filter->enabled) {
                continue;
            }
            switch ($filter->filter_type) {
                case 'by_number':
                    $feed->setNewWalks($filter->by_number);
                    break;
                case 'by_days_of_week':
                    $feed->filterDayofweek($filter->days_of_week);
                    break;
                case 'by_grades':
                    $feed->filterNationalGrade($filter->grades);
                    break;
                case 'by_before_date':
                    $feed->filterDateBefore($filter->before_date);
                    break;
                case 'by_after_date':
                    $feed->filterDateAfter($filter->after_date);
                    break;
                case 'by_time_period':
                    $dateto = new DateTime(); // set date to today
                    $dateto->add(new DateInterval('P' . $filter->time_period . 'D')); // add one month, 'P3M' is 3 months, 'P30D' is 30 days
                    $feed->filterDateAfter($dateto);
                    break;
                case 'by_walks':
                    $feed->filterWalks();  // filter/remove walks
                    break;
                case 'by_events':
                    $feed->filterEvents();  // filter/remove events
                    break;
                case 'by_groups':
                    $groups = array_column($filter->keep_groups, 'code');
                    $feed->filterGroups($groups); // removes any walks for groups not in the list
                    break;
                case 'by_cancelled':
                    $feed->filterCancelled(); // removes walks that have been cancelled  
                    break;
                case 'by_outside_area':
                    break;
                case 'by_dist_shorter':
                    $feed->filterWalksDistanceBelow($filter->dist_shorter);
                    break;
                case 'by_dist_longer':
                    $feed->filterWalksDistanceAbove($filter->dist_longer);
                    break;
                case 'by_title':
                    break;
                case 'by_flags':
                    break;
            }
        }
    }

    private function getEventsDisplay($type) {
        switch ($type) {
            case 'future_nextwalks':
                $display = new Nextwalks();
                break;
            case 'future_display':
                $display = new Display();
                break;
            case 'future_calendar':
                $display = new Display();
                break;
            case 'future_table':
                $display = new Walktable();
                break;
            case 'future_list':
                $display = new Simplelist();
                break;
            case 'future_map':
                $display = new Mapmarker();
                break;
            case 'future_fulldetails':
                $display = new Fulldetails();
                break;
            case 'future_AV01a':
                $display = new AV01Processwalks();
                break;
            case 'future_BU51a':
                $display = new BU01Fulldetails();
                break;
            case 'future_BU51b':
                $display = new BU01Groupstabs();
                break;
            case 'future_BU51c':
                $display = new BU01Micronextwalks();
                break;
            case 'future_BU51d':
                $display = new BU01Tabs();
                break;
            case 'future_MLa':
                $display = new MLPrint();
                break;
            case 'future_NSa':
                $display = new NSWalksprinted();
                break;
            case 'future_SR02a':
                $display = new SR02Display();
                break;
            case 'future_SR02b':
                $display = new SR02Nextwalks();
                break;
            case 'future_SR02c':
                $display = new SR02Table2();
                break;
        }
        return $display;
    }

    private function customiseWalksDisplay($display, $option, $data) {
        switch ($option) {
            case 'nextwalks':
                if (strlen($data->custom_nextwalks) > 0) {
                    $display->customFormat($data->custom_nextwalks);
                }
                break;
            case 'future_display':
                if (count($data->custom_tabs) > 0) {
                    $display->setTabOrder($data->custom_tabs);
                }
                if (strlen($data->custom_grades) > 0) {
                    $display->setCustomGradesFormat($data->custom_grades);
                }
                if (strlen($data->custom_list) > 0) {
                    $display->setCustomListFormat($data->custom_list);
                }
                if (strlen($data->custom_calendar) > 0) {
                    $display->setCustomCalendarFormat($data->custom_calendar);
                }
                if (count($data->custom_table) > 0) {
                    // rename property
                    foreach ($data->custom_table as $item) {
                        $item->title = $item->heading;
                        unset($item->heading);
                        $item->items = $item->layout;
                        unset($item->layout);
                    }
                    $display->setCustomTableFormat($data->custom_table);
                }
                break;

            case 'table':
                if (count($data->custom_table) > 0) {
                    // rename property
                    foreach ($data->custom_table as $item) {
                        $item->items = $item->layout;
                        unset($item->layout);
                    }
                    $display->customFormat($data->custom_table);
                }
                break;
            case 'list':
                if (strlen($data->custom_simplelist) > 0) {
                    $display->customFormat($data->custom_simplelist);
                }
                break;
            case 'fulldetails':
                if (strlen($data->custom_fulldetails) > 0) {
                    $display->customFormat($data->custom_fulldetails);
                }
                break;
        }
    }

    private function displayPlot() {
        $data = $this->data;
        $latitude = $data->routes_latitude;
        $longitude = $data->routes_longitude;
        $zoomlevel = $data->routes_zoomlevel;
        $routingkey = $data->routes_smartroutingkey;
        if ($routingkey) {
            License::OpenRoutingServiceKey($routingkey);
        }
        $display = new Mapdraw();
        $display->setCenter($latitude, $longitude, $zoomlevel); // lat, long, zoom
        $display->display();
    }

    private function displayRoutesSingle() {
        $data = $this->data;
        $gpx = $data->routes_file;
        $linecolour = $data->routes_linecolour;
        $download = $data->routes_download;

        $map = new GpxMap();
        $map->linecolour = $linecolour;
        $map->addDownloadLink = $download; //  "None" no download link, "Users" link if registered user; "Public" link for public
        $map->displayPath($gpx);
    }

    private function displayRoutesMulti() {
        $data = $this->data;
        $gpx = $data->routes_folder;
        $linecolour = $data->routes_linecolour;
        $download = $data->routes_download;
        $displaytitle = $data->routes_displaytitle;
        $map = new GpxMaplist(); // standard software to list GPX files from folder.
        $map->addDownloadLink = $download; //  "None" no download link, "Users" link if registered user; "Public" link for public
        $map->linecolour = $linecolour; // optional - specify colour to be used for the route line
        $map->displayTitle = $displaytitle;
        $map->folder = $gpx;
        $map->display();
    }

    private function displayTableCSV() {
        $data = $this->data;
        $folder = $data->table_file;
        $list = new CsvList($folder);
        $list->setDisplayOptions($table_options);
        $list->display();
    }

    private function displayTableSQL() {
        $data = $this->data;
        $select = $data->table_sqlselect;
        $options = $data->table_options;
        // convert from object to arrays
        $options = json_encode($options);
        $options = json_decode($options, true) ?: [];
        $list = new SqlList($select);
        $this->setTableDisplayOptions($list);
        $list->setOptions($options);
        $list->display();
    }

    private function displayTableJson() {

        $data = $this->data;
        $json = $data->table_json;
        $options = $data->table_options;
        // convert from object to arrays
        $options = json_encode($options);
        $options = json_decode($options, true) ?: [];
        $list = new JsonList($json);
        $list->setOptions($options);
        $list->setDisplayOptions($list);
        $list->display();
    }

    private function setTableDisplayOptions($list) {
        $data = $this->data;
        switch ($data->table_markertype) {
            case "text":
                $displayOptions = (object) [
                            'icons' => (object) [
                                'type' => 'text',
                                'column' => $data->table_markercolumn,
                                'class' => $data->table_textmarkerclass
                            ]
                ];
                break;
            case 'icons':
                $displayOptions = (object) [
                            'icons' => (object) [
                                'type' => 'icon',
                                'column' => $data->table_markercolumn,
                                'values' => new \stdClass()
                            ]
                ];
                foreach ($data->table_iconmarkers as $value) {
                    $prop = $value->value;
                    $val = $value->icon;
                    $displayOptions->icons->values->$prop = $val;
                }
                break;
            default:
                return;
        }
        $list->setDisplayOptions($displayOptions);
    }

    private function dsiplayDocumentsFolder() {
        $dir = new RDirectoryList(array(".pdf", ".doc", ".docx", ".odt", ".zip")); // specifies the option and which file types will be displayed
        $dir->listItems("images/xxxxxxxx"); // specifies the folder that should be listed
        $dir->listItems("images/xxxxxxxx", RDirectoryList::ASC); // this also specifies that the files should be listed in alphabetical order.(the default)
        $dir->listItems("images/xxxxxxxx", RDirectoryList::DESC); // this also specifies that the files should be listed in reverse alphabetical order.
    }

    private function WalkseditorProgramme() {
        $data = $this->data;
        $form = new Programme();
        $form->setGroups($this->getRamblersGroups());
        $this->setlocalGrades($form);
        $form->display();
    }

    private function WalkseditorSubmit() {
        $data = $this->data;
        $form = new Submitform();
        $form->setGroups($this->getRamblersGroups());
        $coords = $data->draft_coords;
        $form->setWalksCoordinators($coords);
        $this->setlocalGrades($form);
        $form->display();
    }

    private function setlocalGrades($form) {
        $data = $this->data;
        if (!isset($data->draft_localgrades)) {
            return;
        }
        if (!is_array($data->draft_localgrades)) {
            return;
        }
        $grades = $data->draft_localgrades;
        if (count($grades) === 0) {
            return;
        }
        $gs = [];
        foreach ($grades as $value) {
            $prefix = $value->code . ": ";
            $name = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $value->description);
            $gs[$value->code] = $name;
        }
        $form->setLocalGrades($gs);
    }

    private function getRamblersGroups() {
        $data = $this->data;
        $groups = $data->draft_ragroups;
        $gs = [];
        foreach ($groups as $value) {
            $prefix = $value->code . ": ";
            $name = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $value->name);
            $gs[$value->code] = $name;
        }
        return $gs;
    }
}
