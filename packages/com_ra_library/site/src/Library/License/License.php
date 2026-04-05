<?php
namespace Ramblers\Component\Ra_library\Site\Library\License;
/**
 * Enable web master to specify license keys
 *
 * @author Chris Vaughan
 */
use Joomla\CMS\Uri\Uri;
class License {

    private static $openRoutingServicelicensekey = null;

    public static function OpenRoutingServiceKey($value) {
        self::$openRoutingServicelicensekey = $value;
    }

    public static function getOpenRoutingServiceKey() {
        return self::$openRoutingServicelicensekey;
    }

    public static function isOpenRoutingServiceKeySet() {
        return self::$openRoutingServicelicensekey != "undefined";
    }

    // Common licenses for all domains

    public static function getOrdnanceSurveyLicenseTestKey() {
        if (strpos(Uri::base(), 'localhost') !== false) {
            return 'OL9IpgZ7gHe35WaXPKrpTIQRkiMS9UAb';
        }
        if (strpos(Uri::base(), 'locahaberandlorn-ramblers') !== false) {
            return 'OL9IpgZ7gHe35WaXPKrpTIQRkiMS9UAb';
        }
        return null;
    }

    public static function getOrdnanceSurveyLicenseKey() {
        return '0af3JPmbRyCAkGAjns8RA5YGsv4qIATl';
    }

    // key to display OS test style map
    public static function getOrdnanceSurveyLicenseKeyTestStyle() {
        if (strpos(Uri::base(), 'localhost') !== false) {
            return '0af3JPmbRyCAkGAjns8RA5YGsv4qIATl';
        }
        if (strpos(Uri::base(), 'locahaberandlorn-ramblers') !== false) {
            return '0af3JPmbRyCAkGAjns8RA5YGsv4qIATl';
        }
        return null;
    }

    public static function getMapBoxLicenseKey() {


        return null;
    }

    public static function getThunderForestLicenseKey() {

        return null;
    }

    public static function getESRILicenseKey() {
        return "";
    }

    public static function getOSMVectoricenseKey() {
        if (strpos(Uri::base(), 'localhost') !== false) {
            return "";
        }
        if (strpos(Uri::base(), 'locahaberandlorn-ramblers') !== false) {
            return "";
        } else {
            return null;
        }
    }

    public static function getW3WLicenseKey() {
        return 'SRJ2YZLZ';
    }

}