<?php
namespace Ramblers\Component\Ra_library\Site\Library\Ics;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of file
 *
 * @author Admin
 */
class Output {

    private $isc="";
    
    function __construct() {
        $this->addHeader();
    }

    public function text() {
        return $this->isc . "END:VCALENDAR";
    }

    public function addRecord($command, $content = "", $html = false) {
        $text = mb_convert_encoding($content, "UTF-8");
        $text = $this->escapeString($text);
        if ($html) {
            $before = "<!DOCTYPE html><html><head><title></title></head><body>";
            $after = "</body></html>";
            $text = str_replace("\\n", "<br/>", $text);
            $lines = $this->chunk_split_unicode($command . $before . $text . $after, 73);
        } else {
            $text = str_replace("<br/>", "\\n", $text);
            $text = str_replace("&nbsp;", " ", $text);
            $text = str_replace("<p>", "", $text);
            $text = str_replace("</p>", "\\n", $text);
            $text = str_replace("&ndash;", "-", $text);
            $text = strip_tags($text);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
            $lines = $this->chunk_split_unicode($command . $text, 73);
        }
        $this->isc .= $lines;
    }

    // Escapes a string of characters
    public static function escapeString($string) {
        return preg_replace('/([\,;])/', '\\\$1', $string);
    }

    public function addSequence($dateUpdated) {
        $date = new \DateTime('2010-01-01');
        $interval = $date->diff($dateUpdated);
        $days = $interval->days;
        // Fix added to include number of seconds since midnight, forcing an update on each download. 
        // Otherwise, the event would only update daily. 
        $sequence = strval($days) . strval(time() % 86400);
       
        $this->addRecord("SEQUENCE:" . $sequence);
    }

    private function addHeader() {
        $this->isc = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nMETHOD:PUBLISH\r\n";
        $this->isc .= "PRODID:ramblers-webs v1.2\r\n";
    }

    private function chunk_split_unicode($str, $l = 73, $e = "\r\n") {
        $tmp = array_chunk(
                preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY), $l);
        $str1 = "";
        $blank = "";
        foreach ($tmp as $t) {
            $str1 .= $blank . join("", $t) . $e;
            $blank = " ";
        }
        return $str1;
    }

    function __destruct() {
        
    }

}
