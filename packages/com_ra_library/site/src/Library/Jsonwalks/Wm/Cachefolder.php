<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Wm;
//
//     @version		2.0
//     @package		Cache folder for Walks Manager walks
//     @author                 Chris Vaughan
//     @copyright              Copyright (c) 2023 Chris Vaughan 
//     @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
//
//
// no direct access
defined('_JEXEC') or die('Restricted access');

class Cachefolder {

    private $cacheFolderPath;

    public function __construct($cacheLocation) {
        $this->cacheFolderPath = JPATH_SITE . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR . $cacheLocation;
        $this->createCacheFolder();
        $this->clearOldFiles();
    }

    public function clearOldFiles() {
        // Check if the cache folder exists
        if (file_exists($this->cacheFolderPath) && is_dir($this->cacheFolderPath)) {
            // clear files from folder
            $files = glob($this->cacheFolderPath . '/*'); // get all file names
            foreach ($files as $file) { // iterate files
                if (is_file($file)) {
                    if (time() - filemtime($file) > 24 * 3 * 3600) {
                        // file older than 3 days
                        unlink($file); // delete file}
                    }
                }
            }
        }
    }

    public function fileExists($filename) {
        return file_exists($this->cacheFolderPath . DIRECTORY_SEPARATOR . $filename);
    }

    public function lastModified($filename) {
        return filemtime($this->cacheFolderPath . DIRECTORY_SEPARATOR . $filename);
    }

    public function readFile($filename) {
        return Fileio::readFile($this->cacheFolderPath . DIRECTORY_SEPARATOR . $filename);
    }

    public function writeFile($filename, $data) {
        return Fileio::writeFile($this->cacheFolderPath . DIRECTORY_SEPARATOR . $filename, $data);
    }

    private function createCacheFolder() {
        // Check if the cache folder exists
        if (file_exists($this->cacheFolderPath) && is_dir($this->cacheFolderPath)) {
            // all OK
        } else {
            mkdir($this->cacheFolderPath);
        }
    }

}
