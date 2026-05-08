<?php
namespace Ramblers\Component\Ra_library\Site\Library\Conf;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of htaccesscompare
 *
 * @author Chris Vaughan
 */
class Htaccesscompare extends Compare {

    private $config1;
    private $config2;

    public function comparetext($text1, $text2) {
        $this->config1 = self::loadstring($text1);
        $this->config2 = self::loadstring($text2);
        $count1 = count($this->config1);
        $count2 = count($this->config2);
        if ($count1 > $count2) {
            foreach ($this->config2 as $key => $value) {
                $value2 = $this->config1[$key];
                if (strtolower($value) != strtolower($value2)) {
                    return RConfCompare::DIFFERENT;
                }
            }
            return RConfCompare::ADDITIONAL;
        }
        if ($count1 != $count2) {
            return RConfCompare::DIFFERENT;
        }
        foreach ($this->config1 as $key => $value) {
            $value2 = $this->config2[$key];
            if (strtolower($value) != strtolower($value2)) {
                return RConfCompare::DIFFERENT;
            }
        }
        return RConfCompare::SAME;
    }

    public function getCount1() {
        return count($this->config1);
    }

    public function getCount2() {
        return count($this->config2);
    }

    private static function loadstring($text) {
        $lines = explode("\n", $text);
        $config = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line == "") {
                continue;
            }
            if ($line[0] == '#') {
                continue;
            }
            $line = str_replace("  ", " ", $line);
            $config[] = $line;
        }
        return $config;
    }

}
