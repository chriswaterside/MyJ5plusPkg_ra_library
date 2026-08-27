<?php

Namespace Ramblers\Component\Ra_library\Site\Library\Gpx;

/**
 * Description of statistics
 *
 * @author Chris Vaughan
 */
use Ramblers\Component\Ra_library\Site\Library\Html\Html;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

class Statistics {

    private $folder;
    private $jsonfile;
    private $getMetaFromGPX = false;

    CONST JSONFILE = "0000gpx_statistics_file.json";

    public function __construct($folder, $getMetaFromGPX) {
        $this->folder = $folder;
        $this->getMetaFromGPX = $getMetaFromGPX;
        if (!file_exists($folder)) {
            $text = "Folder does not exist: " . $folder . ". Unable to list contents";
            $app = Factory::getApplication();
            $app->enqueueMessage($text, "error");
            echo "<b>Not able to list contents of folder: " . $folder . "<b>";
            return;
        }
        $changed = $this->folderFilesChanged($this->folder, self::JSONFILE);
        if ($changed) {
            // process files and create statistics file
            $this->jsonfile = new Jsonlog($this->folder . "/" . self::JSONFILE);
            $this->processFolder();
            $this->jsonfile->writeFile();
        }
    }

    private function folderFilesChanged($folder, $json) {
        if (!is_file($folder . "/" . $json)) {
            return true;
        }
        $jsonfile = new Jsonlog($folder . DIRECTORY_SEPARATOR . $json);
        $jsonfile->readFile();
        $items = $jsonfile->getItems();
        $filenames = array_column($items, 'filename');

        $dir = $this->folder . DIRECTORY_SEPARATOR;

        $master = filemtime($dir . $json);
        $count = 0;
        foreach (scandir($dir) as $entry) {
            if ($this->endsWith(strtolower($entry), ".gpx")) {
                $count += 1;
            }
        }
        if ($count !== count($filenames)) {
            return true;
        }
        foreach (scandir($dir) as $entry) {
            if (!$this->endsWith(strtolower($entry), ".gpx")) {
                continue;
            }
            if (filemtime($dir . $entry) > $master) {
                return true;
            }
            if (!in_array($entry, $filenames)) {
                return true;
            }
        }

        return false;
    }

    public function getJson() {
        $file = $this->folder . "/" . self::JSONFILE;
        if (file_exists($file)) {
            $contents = file_get_contents($file);
            return $contents;
        }
        return "[]";
    }

    private function processFolder() {
        $files = scandir($this->folder, SCANDIR_SORT_ASCENDING);
        if ($files === false) {
            echo "<p>Processing GPX files: Error access folder" . $this->folder . "</p>";
            $files = [];
        }
        // for each GPX file
        //       get stats and create new record
        Log::addLogger(
                ['text_file' => 'com_ra_library.php'], // filename in administrator/logs
                Log::ALL, // which priorities go to this logger
                ['com_ra_library']                        // which categories go to this logger
        );
        Log::add('Processing GPX files', Log::INFO, 'com_ra_library');
        Log::add('Diagnostics while generating file: ' . self::JSONFILE, Log::INFO, 'com_ra_library');
        Log::add('Filename, Title, Author, Date, Longitude, Latitude, Distance, Elevation Gain, min Alt, max Alt, Tracks,Segments, Routes ', Log::INFO, 'com_ra_library');

        foreach ($files as $file) {
            if ($this->endsWith(strtolower($file), ".gpx")) {
                $stat = $this->processGPXFile($file);
                $this->jsonfile->addItem("id" . $stat->id, $stat);
            }
        }
    }

    private function processGPXFile($file) {
        $stat = new Statistic();
        $gpx = new File($this->folder . "/" . $file);
        $stat->filename = $file;
        if ($this->getMetaFromGPX) {
            $stat->title = $gpx->name;
            if ($stat->title == "") {
                $stat->title = $this->getTitlefromName($file);
            }
            $stat->description = $gpx->description;
            if ($stat->description == "") {
                $stat->description = $this->getDescription($file);
            }
        } else {
            $stat->title = $this->getTitlefromName($file);
            $stat->description = $this->getDescription($file);
        }
        $stat->author = $gpx->author;
        $stat->date = $gpx->date;
        $stat->links = $gpx->links;
        $stat->longitude = $gpx->longitude;
        $stat->latitude = $gpx->latitude;
        $stat->endLongitude = $gpx->endLongitude;
        $stat->endLatitude = $gpx->endLatitude;
        $stat->distance = $gpx->distance;
        if ($gpx->cumulativeElevationGain !== null) {
            $stat->cumulativeElevationGain = $gpx->cumulativeElevationGain;
        }
        if ($gpx->minAltitude !== null) {
            $stat->minAltitude = $gpx->minAltitude;
        }
        if ($gpx->maxAltitude !== null) {
            $stat->maxAltitude = $gpx->maxAltitude;
        }
        $stat->tracks = $gpx->tracks;
        $stat->routes = $gpx->routes;
        $stat->duration = $gpx->duration;
        $cols = [];
        $cols[] = $stat->filename;
        $cols[] = $stat->title;
        $cols[] = $stat->author;
        $cols[] = $stat->date;
        $cols[] = round($stat->longitude, 4);
        $cols[] = round($stat->latitude, 4);
        $cols[] = round($stat->distance, 0) . " m";
        $cols[] = round($stat->cumulativeElevationGain, 1);
        $cols[] = round($stat->minAltitude, 0);
        $cols[] = round($stat->maxAltitude, 0);
        $cols[] = $stat->tracks . "(" . $gpx->segments . ")";
        $cols[] = $stat->routes;
        Log::add("'" . implode("','", $cols) . "'", Log::INFO, 'com_ra_library');
        return $stat;
    }

    private function getDescription($file) {
        $desc = "";
        $descfile = $this->folder . "/" . $file . ".txt";
        if (file_exists($descfile)) {
            $desc = file_get_contents($descfile);
        }
        return $desc;
    }

    private function getTitlefromName($file) {
        $title = substr($file, 0, -4);
        $title = str_replace("_", " ", $title);
        $title = str_replace("-", ", ", $title);
        return $title;
    }

    private function endsWith($haystack, $needle) {
        if (strlen($needle) > strlen($haystack)) {
            return false;
        }
        // search forward starting from end minus needle length characters
        return $needle === "" || strpos($haystack, $needle, strlen($haystack) - strlen($needle)) !== FALSE;
    }
}
