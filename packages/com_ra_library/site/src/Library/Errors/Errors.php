<?php
namespace Ramblers\Component\Ra_library\Site\Library\Errors;
/**
 * Description of errors
 *
 * @author Chris Vaughan
 */
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

class Errors {

    private static $ERROR_STORE_URL = "https://errors.theramblers.org.uk/store_errors.php";

    // private static $ERROR_STORE_URL = "https://cache.ramblers-webs.org.uk/store_errors.php";
    // private static $ERROR_STORE_URL = "http://localhost/storeerrors/store_errors.php";

    public static function notifyError($errorText, $action, $level, $returncode = null) {

        $url = self::$ERROR_STORE_URL;

        switch (strtolower($level)) {
            case "message":
            case "notice":
            case "warning":
            case "error":
                break;
            default:
                $level = "Error";
                break;
        }

        $data = [];
        $uri = Uri::getInstance();
        $data['domain'] = $uri->toString();
        $data['action'] = $action;
        if ($returncode !== null) {
            $data['action'] = $action . " [" . $returncode . "]";
        }
        $data['error'] = $errorText;
        $data['trace'] = json_encode(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 5));

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);  // allow xx seconds for timeout
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);  // allow xx seconds for timeout

        curl_setopt($curl, \CURLOPT_HEADER, 1);
        curl_setopt($curl, \CURLINFO_HEADER_OUT, true);

        curl_exec($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }

        $status = curl_getinfo($curl, \CURLINFO_HTTP_CODE);
        curl_close($curl);

        $app = Factory::getApplication();
        $app->enqueueMessage(Text::_($errorText . ": " . $action), $level);
        if (isset($error_msg) || $status != 200) {
            if (!isset($error_msg)) {
                $error_msg = "Unknown cURL error";
            }
            $app->enqueueMessage(Text::_('SYSTEM was unable to report error.<br>cURL: ' . $error_msg), 'warning');
        }
    }

    private static function emailError($errorText, $action, $level) {
        $domain = Uri::base();
        $mailer = Factory::getMailer();
        $config = Factory::getConfig();
        $sender = array(
            $config->get('mailfrom'),
            $config->get('fromname')
        );

        $mailer->setSender($sender);
        $recipient = array('feeds@ramblers-webs.org.uk');
        $mailer->addRecipient($recipient);

        $mailer->setSubject('Walks feed error');
        $body = '<h2>' . $domain . '</h2>'
                . '<h3>' . $action . '</h3>'
                . '<p>ERROR: ' . $errorText . '</p>'
                . '<p>Severity: ' . $level . '</p>';
        $mailer->isHtml(true);
        $mailer->setBody($body);
        $send = $mailer->Send();
    }

    public static function checkJsonFeed($feed, $feedTitle, $result, $properties) {
        $status = $result["status"];
        $contents = $result["contents"];

        switch ($contents) {
            case NULL:
                Errors::notifyError($feedTitle . ' feed: Unable to read feed [Null response, Error 1]', $feed, 'error');
                break;
            case "":
                echo '<b>No ' . $feedTitle . ' found.</b> [Error 2]';
                break;
            case "[]":
                // echo '<b>Sorry no ' . $feedTitle . ' found</b>';
                break;
            default:
                $json = json_decode($contents);
                unset($contents);
                $errors = 0;
                $error = json_last_error();
                if ($error == \JSON_ERROR_NONE) {
                    foreach ($json as $value) {
                        $ok = Errors::checkJsonProperties($value, $properties);
                        $errors += $ok;
                    }
                    if ($errors > 0) {
                        Errors::notifyError('Feed: Json file contents not as expected [Error 3]', $feed, 'error');
                        Errors::emailError('Feed: Json file contents not as expected [Error 3]', $feed, 'error');
                    }
                    return $json;
                    break;
                } else {
                    $errormsg = json_last_error_msg();
                    Errors::notifyError('Feed is not in Json format: ' . $errormsg . ' [Error 4]', $feed, 'error');
                }
                return null;
        }
    }

    private static function checkJsonProperties($item, $properties) {
        if ($properties === null) {
            return 0;
        }
        foreach ($properties as $value) {
            if (!Errors::checkJsonProperty($item, $value)) {
                return 1;
            }
        }

        return 0;
    }

    private static function checkJsonProperty($item, $property) {
        if (property_exists($item, $property)) {
            return true;
        }
        return false;
    }
}
