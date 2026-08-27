<?php
namespace Ramblers\Component\Ra_library\Site\Library\Feedhelper;
//
//     @version		2.0
//     @package		Simple Feed Reader 
//     @author                 Chris Vaughan
//     @copyright              Copyright (c) 2016 Chris Vaughan Derby & South Derbyshire Ramblers
//     @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
//
//
// no direct access
defined('_JEXEC') or die('Restricted access');
use Ramblers\Component\Ra_library\Site\Library\Errors\Errors;

class Feedhelper {

    private $cacheFolderPath;
    private $cacheTime;
    private $status = self::OK;
    private $timeout = 15; // seconds

    const OK = 0;
    const READFAILED = 1;
    const FEEDERROR = 2;
    const FEEDFOPEN = 3;

    public function __construct($cacheLocation, $cacheTime) {
        $this->cacheTime = $cacheTime * 60; // convert to seconds
        $this->cacheFolderPath = \JPATH_SITE . DIRECTORY_SEPARATOR . $cacheLocation;
        if (isset($cacheLocation)) {
            $this->createCacheFolder();
        } else {
            die("Invalid call to Jsonwalks Feedhelper");
        }
    }

    public function setReadTimeout($value) {
        $this->timeout = $value;
    }

    public function getFeed($feedurl, $title) {

        $url = trim($feedurl);
        if ($this->startsWith($url, "http://www.ramblers.org.uk")) {
            $url = str_replace("http://", "https://", $url);
        }
        $contents = '';
        $this->status = self::OK;
        if (ini_get('allow_url_fopen') == false) {
            Errors::notifyError('FETCH: Not able to read feed using fopen', $feedurl, 'error');
            $this->status = self::FEEDFOPEN;
        }
        if (substr($url, 0, 4) != "http") {
            Errors::notifyError('FETCH: Feed must use HTTP protocol', $feedurl, 'error');
            $this->status = self::FEEDERROR;
        }

        if ($this->status == self::OK) {
            $cachedFile = $this->createCachedFileFromUrl($url, $title);
            if ($cachedFile != '') {
                $contents = $this->file_get_contents($cachedFile);
                if ($contents === false) {
                    $contents = NULL;
                }
            }
        }
        $result = [];
        $result["status"] = $this->status;
        $result["contents"] = $contents;
        return $result;
    }

    // Get remote file
    private function createCachedFileFromUrl($url, $title) {
        $result = '';
        $tmpFile = $this->getCacheName($url);
        // Check if a cached copy exists 
        if (file_exists($tmpFile) && is_readable($tmpFile) && (filemtime($tmpFile) + $this->cacheTime) > time()) {
            $this->status = self::OK;
            return $tmpFile; // Use existing cached version
        }

        $fgcOutput = $this->file_get_contents($url);
        if ($fgcOutput === false) {
            $response = "Error from file_get_contents";
            Errors::notifyError('FETCH: Unable to fetch ' . $title . ', data may be out of date', $url, 'warning', $response);
            $fgcOutput = "[]";
        } else {
            file_put_contents($tmpFile, $fgcOutput);
        }

        // if cached file exists (new or old) then return it.
        if (file_exists($tmpFile) && is_readable($tmpFile)) {
            $result = $tmpFile;
        }
        return $result;
    }

    private function file_get_contents($file) {
        for ($i = 1; $i <= 2; $i++) {
            // try function twice in case first fails 
            $contents = file_get_contents($file);
            if ($contents !== false) {
                break;
            }
            sleep(1);
        }
        return $contents;
    }

    public function clearCache() {

        // Check if the cache folder exists
        if (file_exists($this->cacheFolderPath) && is_dir($this->cacheFolderPath)) {
            // clear files from folder
            $files = glob($this->cacheFolderPath . '/*'); // get all file names
            echo "<h2 class='feedrefresh'>Feed cache will be refreshed</h2>";
            foreach ($files as $file) { // iterate files
                if (is_file($file)) {
                    unlink($file); // delete file}
                }
            }
        }
    }

    private function getCacheName($feedname) {
        $url = strtolower(trim($feedname));
        if (substr($url, 0, 4) == "http") {
            $turl = explode("?", $url);
            $matchComponents = array("#(http|https)\:\/\/#s", "#www\.#s");
            $replaceComponents = array("", "");
            $turl = preg_replace($matchComponents, $replaceComponents, $turl[0]);
            $turl = str_replace(array("/", "-", "."), array("_", "_", "_"), $turl);
            $tmpFile = $this->cacheFolderPath . DS . urlencode($turl) . '.cache';
            /* $turl = explode("?", $url); CEV replacement code to handle walksfeed options */
            $turl = $url;
            $matchComponents = array("#(http|https)\:\/\/#s", "#www\.#s");
            $replaceComponents = array("", "");
            $turl = preg_replace($matchComponents, $replaceComponents, $turl);
            $turl = str_replace(array("/", "-", "."), array("_", "_", "_"), $turl);
            $turl = str_replace(array("?", "&", ",", "="), array("_", "", "", ""), $turl);
            $tmpFile = $this->cacheFolderPath . DS . urlencode($turl) . '.cache';
            /* end of change */
        } else {
            $tmpFile = $this->cacheFolderPath . DS . 'cached_' . md5($url);
        }
        return $tmpFile;
    }

    private function createCacheFolder() {
        // Check if the cache folder exists
        if (file_exists($this->cacheFolderPath) && is_dir($this->cacheFolderPath)) {
            // all OK
        } else {
            mkdir($this->cacheFolderPath);
        }
    }

    private function startsWith($string, $startString) {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }
}
