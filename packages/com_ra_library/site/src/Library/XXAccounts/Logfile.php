<?php
namespace Ramblers\Component\Ra_library\Site\Library\Accounts;

/**
 * Description of logfile
 *
 * @author Chris
 */
class Logfile {

    const CACHE = "../cache02/cache";
    const FILE_HTACCESS = 0;
    const FILE_PHPINI = 1;
    const FILE_USERINI = 2;
    const FILE_PUBLIC_HTACCESS = 3;
    const FILE_PUBLIC_PHPINI = 4;
    const FILE_PUBLIC_USERINI = 5;
    const JOOMLA_HTACCESS = 1;
    const JOOMLA_PHPINI = 2;
    const JOOMLA_USERINI = 3;
    const TICK = "&#10004;<br/>";
    const CROSS = "&#10008;<br/>";
    const DISP_NONE = 0;
    const DISP_VIEW = 1;
    const DISP_DETAILS = 2;

    private $exists = false;
    private $jsonobject = null;
    private $domain;
    private $today;
    private $filesize = 0;
    private $servertimediff = null;

    public function __construct($domain) {
        $this->domain = $domain;
        $file = self::CACHE . "/webstatus." . $domain . ".json.log";
        $this->exists = file_exists($file);
        $this->readFile($file);
        $this->today = new datetime();
        $class = new DateInterval("P3M");
        if ($this->jsonobject != Null) {
            if (isset($this->jsonobject["timediff"])) {
                $data = $this->jsonobject["timediff"];

                foreach ($data as $key => $value) {
                    $class->{$key} = $value;
                }
            }
        }
        $this->servertimediff = $class;
    }

    private function readFile($file) {
        if (!$this->exists) {
            return;
        }
        $this->filesize = filesize($file);
        $content = file_get_contents($file);
        if ($content === false) {
            $content = NULL;
        }
        switch ($content) {
            case NULL:
                break;
            case "[]":
                break;
            default:
                $json = json_decode($content, true);
                if (json_last_error() == JSON_ERROR_NONE) {

                    $this->jsonobject = $json;
                    unset($json);
                }
        }
    }

    public function getFileSize() {
        return number_format($this->filesize);
    }

    public function getReportFormat() {
        if (isset($this->jsonobject["reportversion"])) {
            return $this->jsonobject["reportversion"];
        }
        return "...";
    }

    public function getLargestFilesName() {
        if (isset($this->jsonobject["largestfiles"])) {
            $array = $this->jsonobject["largestfiles"];
            $out = "";
            foreach ($array as $key => $value) {
                $out.=$key . "<br/>";
            }

            return $out;
        }
        return "...";
    }

    public function getLargestFilesSize() {
        if (isset($this->jsonobject["largestfiles"])) {
            $array = $this->jsonobject["largestfiles"];
            $out = "";
            foreach ($array as $key => $value) {
                $out.=number_format($value) . "<br/>";
            }

            return $out;
        }
        return "...";
    }

    public function getWebMonitorVersion() {
        if (isset($this->jsonobject["webmonitorversion"])) {
            return $this->jsonobject["webmonitorversion"];
        }
        return "...";
    }

    public function getTimeDiff() {
        return $this->formatDateDiff($this->servertimediff);
    }

    public function getIP() {
        $ipfromdomain = gethostbyname($this->domain);
        return $ipfromdomain;
    }

    public function getHCPVersion() {
        $path = $this->jsonobject["path"];
        $domain = $this->jsonobject["domain"];
        $pos = strpos($path, $domain);
        if ($pos === false) {
            return "New";
        } else {
            return "Old";
        }
    }

    public function getCMSFolders() {
        $out = "";
        if ($this->jsonobject <> NULL) {
            $remove = strlen($this->jsonobject["path"]);
            if (isset($this->jsonobject["wordpressversions"])) {
                foreach ($this->jsonobject["wordpressversions"] as $key => $item) {
                    $out.=substr($key, $remove) . "<br/>";
                }
            }

            if (isset($this->jsonobject["joomlaversions"])) {
                $array = $this->jsonobject["joomlaversions"];
                foreach ($array as $key => $item) {
                    $out.=substr($key, $remove) . "<br/>";
                }
            }
        }
        return $out;
    }

    public function getCMSVersions() {
        $out = "";
        if ($this->jsonobject <> NULL) {
            $remove = strlen($this->jsonobject["path"]);
            if (isset($this->jsonobject["wordpressversions"])) {
                foreach ($this->jsonobject["wordpressversions"] as $key => $item) {
                    $out.= "WordPress: " . $item . "<br/>";
                }
            }

            if (isset($this->jsonobject["joomlaversions"])) {
                $array = $this->jsonobject["joomlaversions"];
                foreach ($array as $key => $item) {
                    $out.="Joomla: " . $item . "<br/>";
                }
            }
        }
        return $out;
    }

    public function getNoJoomlaInstalls() {
        $no = 0;
        if ($this->jsonobject <> NULL) {
            $remove = strlen($this->jsonobject["path"]);


            if (isset($this->jsonobject["joomlaversions"])) {
                $array = $this->jsonobject["joomlaversions"];
                foreach ($array as $key => $item) {
                    $no+=1;
                }
            }
        }
        return $no;
    }

    public function getJoomlaVersion() {
        $out = "";
        if ($this->jsonobject <> NULL) {
            if (isset($this->jsonobject["joomlaversions"])) {
                foreach ($this->jsonobject["joomlaversions"] as $item) {
                    $out.=$item . "<br/>";
                }
                return $out;
            }
        }
        return "Not found";
    }

    public function getJoomlaHtaccess($displaydetails) {
        $out = $this->checkJoomlaControlFiles(".htaccess", $displaydetails);
        return $out;
    }

    public function getJoomlaPhpini($displaydetails) {
        $out = $this->checkJoomlaControlFiles("php.ini", $displaydetails);
        return $out;
    }

    public function getJoomlaUserini($displaydetails) {
        $out = $this->checkJoomlaControlFiles(".user.ini", $displaydetails);
        return $out;
    }

    private function checkJoomlaControlFiles($filename, $displaydetails) {
        if ($this->jsonobject <> NULL) {
            if (isset($this->jsonobject["config"])) {
                $out = "";
                $directory = $this->jsonobject["directory"];
                foreach ($this->jsonobject["config"] as $item) {
                    $parts = explode(",", $item);
                    if ($parts[1] == "\$sitename") {
                        $folder = $parts[0];
                        $file = $directory . $folder . "/" . $filename;
                        if (isset($this->jsonobject["joomlaversions"][$directory . $folder])) {
                            $version = $this->jsonobject["joomlaversions"][$directory . $folder];
                            $first = substr($version, 0, 1);
                            switch ($first) {
                                case "1":
                                    $out.= "Obsolete<br/>";
                                    break;
                                case "2":
                                    $out.= "Obsolete<br/>";
                                    break;
                                case "3":
                                    $result = $this->checkJoomlaControlFile($directory, $folder, $filename, $displaydetails);
                                    $out.=$result . "<br/>";
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                }
                return $out;
            }
            return "";
        }
        return "";
    }

    private function checkJoomlaControlFile($directory, $folder, $filename, $displaydetails) {
        $options = [];
        $comp = Null;
        $options = $this->getRecommendedJoomla($filename);
        switch ($options["compare"]) {
            case "htaccess":
                $comp = new RConfHtaccesscompare();
                break;
            case "ini":
                $comp = new RConfInicompare();
                break;
        }
        $options["displaydetails"] = $displaydetails;
        $options["folder"] = $folder;
        $text = "";
        if ($this->jsonobject <> NULL) {
            $key = $directory . $folder . "/" . $filename;
            if (isset($this->jsonobject["files"]["$key"])) {
                $text = $this->jsonobject["files"]["$key"];
            }
        }
        //  return $key;     
        if ($comp != NULL) {
            $out = $comp->compare($text, $options);
            return $out;
        }
        return "ERROR checkJoomlaControlFile(2)";
    }

    private function getRecommendedJoomla($filename) {
        $options = [];
        $hcp = $this->getHCPVersion();
        switch ($filename) {
            case ".htaccess":
                $options = ["compare" => "htaccess", "file" => ".htaccess", "path" => "libraries/ramblers/conf/htaccess/joomla3"];
                break;
            case "php.ini":
                $options = ["compare" => "ini", "file" => "php.ini", "path" => ""];
                break;
            case ".user.ini":
                switch ($hcp) {
                    case "Old":
                        $options = ["compare" => "ini", "file" => ".user.ini", "path" => ""];
                        break;
                    default:
                        $options = ["compare" => "ini", "file" => ".user.ini", "path" => "libraries/ramblers/conf/userini/joomla3.ini"];
                        break;
                }
                break;
        }
        return $options;
    }

    public function getNoFilesScanned() {
        if (isset($this->jsonobject["nofilesscanned"])) {
            return number_format($this->jsonobject["nofilesscanned"]);
        }
        return "...";
    }

    public function getTotalSizeScanned() {
        if (isset($this->jsonobject["totalsizescanned"])) {
            return number_format($this->jsonobject["totalsizescanned"]);
        }
        return "...";
    }

    public function getLatestFile() {
        if (isset($this->jsonobject["latestfile"])) {
            return $this->jsonobject["latestfile"];
        }
        return "...";
    }

    public function getFileDate() {
        if ($this->jsonobject <> NULL) {
            if (isset($this->jsonobject["creationdate"])) {
                $value = DateTime::createFromFormat('Y-m-d H:i:s', $this->jsonobject["creationdate"]);
                $date = new DateTime();
                $interval = new DateInterval('P2D');
                $date->sub($interval);
                $item = $this->jsonobject["creationdate"];
                if ($value < $date) {
                    $item = "<div style='color:red'>" . $item . "</div>";
                }
                return $item;
            }
            return "Unknown";
        }
        return "...";
    }

    public function getFolders() {
        if ($this->jsonobject <> NULL) {
            if (isset($this->jsonobject["directories"])) {
                $out = array();
                foreach ($this->jsonobject["directories"] as $item) {
                    $parts = explode("/", $item);
                    $out[] = end($parts);
                }
                return $out;
            }
            return ["None"];
        }
        return ["..."];
    }

    public function hasJoomla() {
        if ($this->jsonobject == NULL) {
            return false;
        }
        if (!isset($this->jsonobject["config"])) {
            return false;
        }
        $count = 0;
        foreach ($this->jsonobject["config"] as $item) {
            $count+=1;
        }
        return $count <> 0;
        ;
    }

    public function getConfigFolder() {
        if ($this->jsonobject <> NULL) {
            if (isset($this->jsonobject["config"])) {
                $out = "";
                foreach ($this->jsonobject["config"] as $item) {
                    $parts = explode(",", $item);
                    if ($parts[1] == "\$sitename") {
                        $out.=$parts[0] . "<br/>";
                    }
                }
                return $out;
            }
            return "";
        }
        return "";
    }

    public function getConfigSitename() {
        return $this->getConfigName("\$sitename");
    }

    public function getConfigCaching() {
        return $this->getConfigName("\$caching");
    }

    public function getConfigGZip() {
        return $this->getConfigName("\$gzip");
    }

    public function getConfigSef() {
        return $this->getConfigName("\$sef");
    }

    public function getConfigSef_rewrite() {
        return $this->getConfigName("\$sef_rewrite");
    }

    public function getConfigSef_suffix() {
        return $this->getConfigName("\$sef_suffix");
    }

    public function checkJoomlaFolders($which, $subfolders, $details) {

        $path = $this->jsonobject["path"] . "public_html/";

        if ($this->jsonobject <> NULL) {
            if (isset($this->jsonobject["config"])) {
                $out = "";
                foreach ($this->jsonobject["config"] as $item) {
                    $parts = explode(",", $item);
                    if (trim($parts[1]) == $which) {
                        $folder = $parts[0];
                        $item = $parts[2];
                        $correctvalues = [];
                        foreach ($subfolders as $subfolder) {
                            $correctvalues[] = $path . $folder . "/" . $subfolder;
                        }
                        $ok = false;
                        foreach ($correctvalues as $correctvalue) {
                            if ($correctvalue == $item) {
                                $ok = true;
                            }
                        }
                        if ($ok) {
                            $out.= Self::TICK;
                        } else {
                            $out.= self::CROSS;
                            if ($details) {
                                echo "<h4>" . $folder . "/" . $which . "</h4>";
                                echo "<ul>";
                                foreach ($correctvalues as $correctvalue) {
                                    echo "<li>Correct value " . $correctvalue . "</li>";
                                }
                                echo "<li>Actual value " . $item . "</li>";
                                echo "</ul>";
                            }
                        }
                    }
                }
                return $out;
            }
            return "not set";
        }
        return "...";
    }

    public function getConfigName($which) {
        if ($this->jsonobject <> NULL) {
            if (isset($this->jsonobject["config"])) {
                $out = "";
                foreach ($this->jsonobject["config"] as $item) {
                    $parts = explode(",", $item);
                    if (trim($parts[1]) == $which) {
                        $out.=$parts[2] . "<br/>";
                    }
                }
                return $out;
            }
            return "not set";
        }
        return "...";
    }

    public function getBackupFolder() {
        if ($this->jsonobject <> NULL) {
            if (isset($this->jsonobject["joomlabackups"])) {
                $out = "";
                foreach ($this->jsonobject["joomlabackups"] as $item) {
                    //  if (strpos($item->file, 'site') !== false) {
                    //       $out.=$item->folder . "<br/>";
                    //   }

                    $out.=$item["folder"] . "<br/>";
                }
                return $out;
            }
            return "";
        }
        return "";
    }

    public function getBackupNo() {
        if ($this->jsonobject <> NULL) {
            if (isset($this->jsonobject["joomlabackups"])) {
                $out = "";
                foreach ($this->jsonobject["joomlabackups"] as $item) {
                    //   if (strpos($item->file, 'site') !== false) {
                    //      $out.=$item->nofiles . "<br/>";
                    //  }
                    //  if (strpos($item->file, 'site') !== false) {
                    $out.=$item["nofiles"] . "<br/>";
                    //  }
                }
                return $out;
            }
            return "";
        }
        return "";
    }

    public function getBackupSize() {
        if ($this->jsonobject <> NULL) {
            if (isset($this->jsonobject["joomlabackups"])) {
                $out = "";
                foreach ($this->jsonobject["joomlabackups"] as $item) {
                    // if (strpos($item->file, 'site') !== false) {
                    $out.=number_format($item["totalsize"]) . "<br/>";
                    // }
                }
                return $out;
            }
            return "";
        }
        return "";
    }

    public function getBackupFile() {
        if ($this->jsonobject <> NULL) {
            if (isset($this->jsonobject["joomlabackups"])) {
                $out = "";
                foreach ($this->jsonobject["joomlabackups"] as $item) {
                    //  if (strpos($item->file, 'site') !== false) {
                    $out.=self::getBackupDiv($item["file"]);
                    //  }
                }
                return $out;
            }
            return "";
        }
        return "";
    }

    public function isOlder($interval) {
        if ($this->jsonobject <> NULL) {
            if (isset($this->jsonobject["creationdate"])) {
                $datetext = $this->jsonobject["creationdate"];
                $created = DateTime::createFromFormat("Y-m-d H:i:s", $datetext);
                if ($created->add($interval) < $this->today) {
                    return true;
                }
            } else {
                return true;
            }
        }
        return false;
    }

    public function getFile($which, $display) {

        $options = [];
        $comp = Null;
        $options = $this->getRecommendedOptions($which);
        switch ($options["compare"]) {
            case "ini":
                $comp = new RConfInicompare();
                break;
            default:
                $comp = new RConfHtaccesscompare();
                break;
        }
        $options["domain"] = $this->jsonobject["domain"];
        $options["displaydetails"] = $display;
        $options["folder"] = "";
        $text = "";
        $file = $options["file"];
        if ($this->jsonobject <> NULL) {
            $key = $this->jsonobject["path"] . $file;
            if (isset($this->jsonobject["files"]["$key"])) {
                $text = $this->jsonobject["files"]["$key"];
            }
        }
        if ($comp != NULL) {
            $out = $comp->compare($text, $options);
            return $out;
        }
        Return "ERROR - getFile";
    }

    public function getRecommendedOptions($which) {
        $hcp = $this->getHCPVersion();
        $options = [];
        switch ($hcp) {
            case "New":
                switch ($which) {
                    case self::FILE_HTACCESS:
                        $options = ["compare" => "htaccess", "file" => ".htaccess", "path" => "libraries/ramblers/conf/htaccess/new-root"];
                        break;
                    case self::FILE_PHPINI:
                        $options = ["compare" => "ini", "file" => "php.ini", "path" => ""];
                        break;
                    case self::FILE_USERINI:
                        $options = ["compare" => "ini", "file" => ".user.ini", "path" => ""];
                        break;
                    case self::FILE_PUBLIC_HTACCESS:
                        $options = ["compare" => "htaccess", "file" => "public_html/.htaccess", "path" => ""];
                        break;
                    case self::FILE_PUBLIC_PHPINI:
                        $options = ["compare" => "ini", "file" => "public_html/php.ini", "path" => ""];
                        break;
                    case self::FILE_PUBLIC_USERINI:
                        $options = ["compare" => "ini", "file" => "public_html/.user.ini", "path" => ""];
                        break;
                }
                break;
            Case "Old":
                switch ($which) {
                    case self::FILE_HTACCESS:
                        $options = ["compare" => "htaccess", "file" => ".htaccess", "path" => "libraries/ramblers/conf/htaccess/old-root"];
                        break;
                    case self::FILE_PHPINI:
                        $options = ["compare" => "ini", "file" => "php.ini", "path" => ""];
                        break;
                    case self::FILE_USERINI:
                        $options = ["compare" => "ini", "file" => ".user.ini", "path" => ""];
                        break;
                    case self::FILE_PUBLIC_HTACCESS:
                        $options = ["compare" => "htaccess", "file" => "public_html/.htaccess", "path" => "libraries/ramblers/conf/htaccess/old-public_html", "replace" => true];
                        break;
                    case self::FILE_PUBLIC_PHPINI:
                        $options = ["compare" => "ini", "file" => "public_html/php.ini", "path" => "libraries/ramblers/conf/phpini/old-public_html"];
                        break;
                    case self::FILE_PUBLIC_USERINI:
                        $options = ["compare" => "ini", "file" => "public_html/.user.ini", "path" => ""];
                        break;
                }
                break;
        }
        Return $options;
    }

    static function isSame($input, $expected) {
        $value = str_replace("\n\n", "\n", $input); // do not worry about extra blank lines
        foreach ($expected as $item) {
            if ($value === $item) {
                return "Default";
            }
            $value = str_replace(" ;", ";", $value);
            if (trim($value) === trim($item)) {
                return "Default";
            }
            if (strtolower(trim($value)) == strtolower(trim($item))) {
                return "Default - case difference";
            }
        }

        return self::process($value, 400);
    }

    public function Exists() {
        return $this->exists;
    }

    private static function process($text, $length) {
        $out = substr($text, 0, $length);
        $out = str_replace("\n", "<br/>", $out);
        return $out;
    }

    private static function getBackupDiv($file) {
        $dt = substr($file, -19, 15);
        $datecreated = DateTime::createFromFormat('Ymd-His', $dt);
        $date = new DateTime();
        $interval = new DateInterval('P7D');
        $date->sub($interval);
        $item = $file;
        if ($datecreated < $date) {
            $item = "<div style='color:#ababab'>" . $file . "</div>";
        } else {
            $item = "<div>" . $file . "</div>";
        }
        $interval = new DateInterval('P113D');
        $date->sub($interval);

        if ($datecreated < $date) {
            $item = "<div style='color:red'>" . $file . "</div>";
        }
        return $item;
    }

    /**
     * A sweet interval formatting, will use the two biggest interval parts.
     * On small intervals, you get minutes and seconds.
     * On big intervals, you get months and days.
     * Only the two biggest parts are used.
     *
     * @return string
     */
    public function formatDateDiff($interval) {

        $doPlural = function($nb, $str) {
            return $nb > 1 ? $str . 's' : $str;
        }; // adds plurals

        $format = array();
        if ($interval->y !== 0) {
            $format[] = "%y " . $doPlural($interval->y, "year");
        }
        if ($interval->m !== 0) {
            $format[] = "%m " . $doPlural($interval->m, "month");
        }
        if ($interval->d !== 0) {
            $format[] = "%d " . $doPlural($interval->d, "day");
        }
        if ($interval->h !== 0) {
            $format[] = "%h " . $doPlural($interval->h, "hour");
        }
        if ($interval->i !== 0) {
            $format[] = "%i " . $doPlural($interval->i, "minute");
        }
        if ($interval->s !== 0) {
            if (!count($format)) {
                return "less than a minute";
            } else {
                $format[] = "%s " . $doPlural($interval->s, "second");
            }
        }

// We use the two biggest parts
        if (count($format) > 1) {
            $format = array_shift($format) . " and " . array_shift($format);
        } else {
            $format = array_pop($format);
        }

// Prepend 'since ' or whatever you like
        return $interval->format($format);
    }

}
