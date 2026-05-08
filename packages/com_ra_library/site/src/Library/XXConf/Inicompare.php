<?php
namespace Ramblers\Component\Ra_library\Site\Library\Conf;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Inicompare extends Compare{

    private $config1;
    private $config2;

    public function comparetext($text1, $text2) {
        $this->config1 = self::loadstring($text1);
        $this->config2 = self::loadstring($text2);
        foreach ($this->config1 as $var => $val) {
            if (isset($this->config2[$var])) {
                if ($val != $this->config2[$var]) {
                    return RConfCompare::DIFFERENT;
                }
            }
        }
        $count1 = count($this->config1);
        $count2 = count($this->config2);
        if ($count1 != $count2) {
            return RConfCompare::DIFFERENT;
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
            $line = rtrim($line, ";");
            $line = trim($line);
            if (trim($line) == "") {
                continue;
            }
            if ($line[0] == ';') {
                continue;
            }
            if ($line[0] == '[') {
                continue;
            }
            $var = explode('=', $line);
            $count = count($var);
            switch ($count) {
                case 1:
                    $config[trim($var[0])] = "";
                    break;
                case 2:
                    $config[trim($var[0])] = trim($var[1]);
                    break;

                default:
                    $config[trim($var[0])] = trim($var[1]) . trim($var[2]);
                    break;
            }
        }
        return $config;
    }

}
