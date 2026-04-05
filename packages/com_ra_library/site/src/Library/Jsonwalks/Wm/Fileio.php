<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Wm;

/**
 * Description of fileio
 * All methods are static
 *
 * @author Chris Vaughan
 */
use Ramblers\Component\Ra_library\Site\Library\Errors\Errors;
use Joomla\Filesystem\File;

class Fileio {

    private static $lastError = "";
    private static $lastTimeElapsedSecs = 0;
    private static $secretStrings = [];

    public static function setSecretStrings($values) {
        self::$secretStrings = $values;
    }

    public static function getLastTimeElapsedSecs() {
        return self::$lastTimeElapsedSecs;
    }

    public static function getLastError() {
        return self::$lastError;
    }

    public static function readFile($url) {
        self::$lastError = "";
        $start = microtime(true);
        $result = self::file_get_contents($url);
        self::$lastTimeElapsedSecs = microtime(true) - $start;
        if ($result === false) {
            $e = error_get_last();
            self::$lastError = self::removeSecretStrings($e['message']);
        }

        return $result;
    }

    public static function writeFile($filename, $data) {
        File::write($filename, $data);
    }

    private static function file_get_contents($file) {
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

    private static function removeSecretStrings($str) {
        $out = $str;
        foreach (self::$secretStrings as $value) {
            $out = str_replace($value, 'xxx', $out);
        }
        return $out;
    }

    public static function errorMsg($msg) {
         Errors::notifyError($msg . self::$lastError, "Walks Manager", 'error');
    }
}
