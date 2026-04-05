<?php
namespace Ramblers\Component\Ra_library\Site\Library\Accounts;
/**
 * Description of logfile
 *
 * @author Chris
 */
use Joomla\CMS\Uri\Uri;
class Account {

    const DEV_DOMAINDETAILS = "index.php?option=com_content&view=article&id=202";
    const PROD_DOMAINDETAILS = "index.php?option=com_content&view=article&id=254";
    const FORMAT_SINGLE = 0;
    const FORMAT_NOLOGFILE = 1;
    const FORMAT_LOGFILE = 2;
    const FORMAT_CONTROL_FILES = 3;
    const FORMAT_FOLDERS = 4;
    const FORMAT_JOOMLA_CONFIG = 5;
    const FORMAT_AKEEBA = 6;
    const FORMAT_LARGESTFILES = 7;
    const FORMAT_NOJOOMLA = 8;
    const FORMAT_SPF = 9;

    private $status;
    private $webmaster;
    private $domain;
    private $log;
    private $timeperiod;

    public function __construct($domain, $status, $webmaster) {
        $this->domain = trim($domain);
        $this->status = $status;
        $this->webmaster = $webmaster;
        $this->log = new RAccountsLogfile($domain);
        $this->timeperiod = new DateInterval("P2D");
  
    }

    Public static function formatsArray() {
        $items = [1, 2, 3, 4, 5, 6, 7, 8, 9];
        return $items;
    }

    public function getColumns($format, $details) {

        $array = array();
        if (!$this->log->Exists()) {
            switch ($format) {
                case self::FORMAT_NOLOGFILE:
                    $array[] = $this->domainLink();
                    $array[] = $this->status;
                    $array[] = $this->whoisLink();
                    return $array;
            }
            return null;
        }
        switch ($format) {
            case self::FORMAT_LOGFILE:
                $array[] = $this->domainLink();
                $array[] = $this->status;
                $array[] = $this->webmaster;
                // $array[] = $this->log->getHCPVersion();
                $array[] = $this->log->getWebMonitorVersion();
                // $array[] = $this->log->getReportFormat();
                $array[] = $this->log->getFileSize();
                $array[] = $this->log->getFileDate();
                $array[] = $this->log->getTimeDiff();
                $array[] = $this->log->getNoFilesScanned();
                $array[] = $this->log->getTotalSizeScanned();
                $array[] = $this->log->getLatestFile();
                if (!$details == RAccountsLogfile::DISP_NONE) {
                    $array[] = $this->whoisLink();
                } else {
                    $array[] = $this->detailsLink() . "<br/><div>" . $this->whoisLink() . "</div>";
                }
                break;
            case self::FORMAT_CONTROL_FILES:
                $array[] = $this->domainLink() . "<br/>  " . $this->log->getFileDate();
                // $array[] = $this->log->getHCPVersion();
                $array[] = $this->log->getFile(RAccountsLogfile::FILE_HTACCESS, $details);
                $array[] = $this->log->getFile(RAccountsLogfile::FILE_PHPINI, $details);
                $array[] = $this->log->getFile(RAccountsLogfile::FILE_USERINI, $details);
                $array[] = $this->log->getFile(RAccountsLogfile::FILE_PUBLIC_HTACCESS, $details);
                $array[] = $this->log->getFile(RAccountsLogfile::FILE_PUBLIC_PHPINI, $details);
                $array[] = $this->log->getFile(RAccountsLogfile::FILE_PUBLIC_USERINI, $details);
                if ($details == RAccountsLogfile::DISP_NONE) {
                    $array[] = $this->detailsLink();
                }
                break;
            case self::FORMAT_FOLDERS:
                $array[] = $this->domainLink() . "<br/>  " . $this->log->getFileDate();
                $noJoomla = $this->log->getNoJoomlaInstalls();
                $folders = $this->log->getFolders();
                $array[] = $this->formatFolder($folders);
                $array[] = $this->log->getCMSFolders();
                $array[] = $this->log->getCMSVersions();
                break;
            case self::FORMAT_LARGESTFILES:
                $array[] = $this->domainLink() . "<br/>  " . $this->log->getFileDate();
                $array[] = $this->log->getLargestFilesName();
                $array[] = $this->log->getLargestFilesSize();
                break;
            case self::FORMAT_AKEEBA:
                if ($this->log->hasJoomla()) {
                    $array[] = $this->domainLink() . "<br/>  " . $this->log->getFileDate();
                    $array[] = $this->status;
                    $array[] = $this->log->getBackupFolder();
                    $array[] = $this->log->getBackupNo();
                    $array[] = $this->log->getBackupSize();
                    $array[] = $this->log->getBackupFile();
                }
                break;
            case self::FORMAT_JOOMLA_CONFIG:
                if ($this->log->hasJoomla()) {
                    $array[] = $this->domainLink() . "<br/>  " . $this->log->getFileDate();
                    $array[] = $this->status;
                    // $array[] = $this->log->getHCPVersion();
                    $array[] = $this->log->getConfigFolder();
                    $array[] = $this->log->getConfigSitename();
                    $array[] = $this->log->getJoomlaVersion();
                    $array[] = $this->log->getConfigCaching();
                    $array[] = $this->log->getConfigGZip();
                    $array[] = $this->log->getConfigSef();
                    $array[] = $this->log->getConfigSef_rewrite();
                    $array[] = $this->log->getConfigSef_suffix();
                    $array[] = $this->log->getJoomlaHtaccess($details);
                    $array[] = $this->log->getJoomlaPhpini($details);
                    $array[] = $this->log->getJoomlaUserini($details);
                    $array[] = $this->log->checkJoomlaFolders("\$log_path", ["logs", "administrator/logs"], $details);
                    $array[] = $this->log->checkJoomlaFolders("\$tmp_path", ["tmp"], $details);
                    if ($details == RAccountsLogfile::DISP_NONE) {
                        $array[] = $this->detailsLink();
                    }
                }
                break;
            case self::FORMAT_NOJOOMLA:
                if (!$this->log->hasJoomla()) {
                    $array[] = $this->domainLink() . "<br/>  " . $this->log->getFileDate();
                    $array[] = $this->status;
                    // $array[] = $this->log->getHCPVersion();
                }
                break;
            case self::FORMAT_SPF:
                $result = dns_get_record($this->domain, DNS_TXT);
                $spf = "Not found";
                if ($result !== false) {
                    if (isset($result[0])) {
                        $col0 = $result[0];
                        if (isset($col0['txt'])) {
                            $spf = $col0['txt'];
                        }
                    }
                }
                $array[] = $this->domainLink();
                $array[] = $this->status;
                $array[] = $spf;
                break;
            default:
                return Null;
                break;
        }
        return $array;
    }

    public static function displayTitle($format) {
        $title = "";
        switch ($format) {
            case self::FORMAT_NOLOGFILE:
                $title = "No Logfile";
                break;
            case self::FORMAT_LOGFILE:
                $title = "Basic Logfile details";
                break;
            case self::FORMAT_CONTROL_FILES:
                $title = "Control Files";
                break;
            case self::FORMAT_FOLDERS:
                $title = "Public_html folders";
                break;
            case self::FORMAT_LARGESTFILES:
                $title = "Largest files in account";
                break;
            case self::FORMAT_AKEEBA:
                $title = "Joomla/Akeeba backup files on server";
                break;
            case self::FORMAT_JOOMLA_CONFIG:
                $title = "Joomla Configuration settings";
                break;
            case self::FORMAT_NOJOOMLA:
                $title = "Joomla not found";
                break;
            case self::FORMAT_SPF:
                $title = "SPF/TXT record";
                break;
        }
        echo "<h3>" . $title . "</h3>";
    }

    public function displayDetails($format) {
        switch ($format) {
            case self::FORMAT_CONTROL_FILES:
                $this->log->getFile(RAccountsLogfile::FILE_HTACCESS, RAccountsLogfile::DISP_DETAILS);
                $this->log->getFile(RAccountsLogfile::FILE_PHPINI, RAccountsLogfile::DISP_DETAILS);
                $this->log->getFile(RAccountsLogfile::FILE_USERINI, RAccountsLogfile::DISP_DETAILS);
                $this->log->getFile(RAccountsLogfile::FILE_PUBLIC_HTACCESS, RAccountsLogfile::DISP_DETAILS);
                $this->log->getFile(RAccountsLogfile::FILE_PUBLIC_PHPINI, RAccountsLogfile::DISP_DETAILS);
                $this->log->getFile(RAccountsLogfile::FILE_PUBLIC_USERINI, RAccountsLogfile::DISP_DETAILS);
                break;
            case self::FORMAT_JOOMLA_CONFIG:
                $this->log->getJoomlaHtaccess(RAccountsLogfile::DISP_DETAILS);
                $this->log->getJoomlaPhpini(RAccountsLogfile::DISP_DETAILS);
                $this->log->getJoomlaUserini(RAccountsLogfile::DISP_DETAILS);
                $this->log->checkJoomlaFolders("\$log_path", ["logs", "administrator/logs"], true);
                $this->log->checkJoomlaFolders("\$tmp_path", ["tmp"], true);
            default:
                return Null;
                break;
        }
        return;
    }

    public static function getHeader($format, $details) {
        switch ($format) {
            case self::FORMAT_NOLOGFILE:
                return ["Domain", "Status", "Whois"];
            case self::FORMAT_LOGFILE:
                if (!$details == RAccountsLogfile::DISP_NONE) {
                    return ["Domain", "Status", "Webmaster", "Web Monitor", "File size", "Date", "Server Time Diff", "Files scanned", "Total size scanned", "Latest File", "Whois"];
                } else {
                    return ["Domain", "Status", "Webmaster", "Web Monitor", "File size", "Date", "Server Time Diff", "Files scanned", "Total size scanned", "Latest File", "Domain Details<br/>Whois"];
                }
            case self::FORMAT_CONTROL_FILES:
                if (!$details == RAccountsLogfile::DISP_NONE) {
                    return ["Domain<br/>  Date", ".htaccess", "php.ini", ".user.ini", "public_html/.htaccess", "public_html/php.ini", "public_html/.user.ini"];
                }
                return ["Domain<br/>  Date", ".htaccess", "php.ini", ".user.ini", "public_html/.htaccess", "public_html/php.ini", "public_html/.user.ini", "Details"];
            case self::FORMAT_FOLDERS:
                return ["Domain<br/>  Date", "Public_html/Folders", "CMS Folder", "CMS Version"];
            case self::FORMAT_LARGESTFILES:
                return ["Domain<br/>  Date", "Largest Files", "Size"];
            case self::FORMAT_AKEEBA:
                return ["Domain<br/>  Date", "Status", "Folder", "No", "Size", "File"];
            case self::FORMAT_JOOMLA_CONFIG:
                if (!$details == RAccountsLogfile::DISP_NONE) {
                    return ["Domain<br/>  Date", "Status", "Folder", "Site name", "Version", "caching", "gzip", "sef", "sef_rewrite", "sef_suffix", ".htaccess", "php.ini", ".user.ini", "Log", "Tmp"];
                } else {
                    return ["Domain<br/>  Date", "Status", "Folder", "Site name", "Version", "caching", "gzip", "sef", "sef_rewrite", "sef_suffix", ".htaccess", "php.ini", ".user.ini", "Log", "Tmp", "Details"];
                }
            case self::FORMAT_NOJOOMLA:
                return ["Domain<br/>  Date", "Status"];
            case self::FORMAT_SPF:
                return ["Domain", "Status", "Txt/Spf Record"];
            default:
                return Null;
        }
        return Null;
    }

    private function whoisLink() {
        if ($this->endsWith($this->domain, ".uk")) {
            return "<a target='_blank' href='https://www.nominet.uk/whois/?query=" . $this->domain . "#whois-results'>Whois</a>";
        } else {
            return " ";
        }
    }

    private function beginsWith($str, $sub) {
        return ( substr($str, 0, strlen($sub)) === $sub );
    }

    private function endsWith($str, $sub) {
        return ( substr($str, strlen($str) - strlen($sub)) === $sub );
    }

    private function domainLink() {
        return "<a target='_blank' href='http://" . $this->domain . "'>" . $this->domain . "</a>";
    }

    private function detailsLink() {
        $host = Uri::base();
        if (strpos($host, 'localhost') !== false) {
            return "<a  href='" . self::DEV_DOMAINDETAILS . "&domain=" . $this->domain . "'>Details</a>";
        }
        return "<a  href='" . self::PROD_DOMAINDETAILS . "&domain=" . $this->domain . "'>Details</a>";
    }

    private function formatFolder($folders) {
        $lines = "";
        $line = "";
        foreach ($folders as $folder) {
            $len = strlen($line);
            if ($len == 0) {
                $line = $folder;
            } else {
                if ($len < 60) {
                    $line.=", " . $folder;
                } else {
                    $lines.= $line . "<br/>";
                    $line = $folder;
                }
            }
        }
        $lines.=$line;
        return $lines;
    }

}
