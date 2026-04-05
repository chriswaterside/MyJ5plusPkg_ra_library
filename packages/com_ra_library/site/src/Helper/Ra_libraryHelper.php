<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Site\Helper;

defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Date\Date;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Class Ra_libraryFrontendHelper
 *
 * @since  1.0.0
 */
class Ra_libraryHelper {

    /**
     * Gets the files attached to an item
     *
     * @param   int     $pk     The item's id
     *
     * @param   string  $table  The table's name
     *
     * @param   string  $field  The field's name
     *
     * @return  array  The files
     */
    public static function getFiles($pk, $table, $field) {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);

        $query
                ->select($field)
                ->from($table)
                ->where('id = ' . (int) $pk);

        $db->setQuery($query);

        return explode(',', $db->loadResult());
    }

    /**
     * Gets the edit permission for an user
     *
     * @param   mixed  $item  The item
     *
     * @return  bool
     */
    public static function canUserEdit($item) {
        $permission = false;
        $user = Factory::getApplication()->getIdentity();

        if ($user->authorise('core.edit', 'com_ra_library') || (isset($item->created_by) && $user->authorise('core.edit.own', 'com_ra_library') && $item->created_by == $user->id) || $user->authorise('core.create', 'com_ra_library')) {
            $permission = true;
        }

        return $permission;
    }

    public static function getPostedData() {
        $input = Factory::getApplication()->getInput();
        // Retrieve individual parameters
        $jsonData = $input->POST->get('data', '', 'raw');
//        $md5 = $input->POST->get('md5', '', 'raw');
//        if ($md5 !== md5($jsonData)) {
//            throw new \RuntimeException('Invalid data received.');
//        }
        $data = \json_decode($jsonData);
        // Check if decoding was successful
        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON data received.');
        }
        return $data;
    }

    public static function sendSingleEmail($to, $copy, $replyTo, $subject, $content, $attach = null) {
        return sendEmailsToUsers([$to], $copy, $replyTo, $subject, $content, $attach);
    }

    public static function sendEmails($sendToArray, $copy, $replyTo, $subject, $content, $attach = null) {
//       example fields for $attach
//       data: JSON.stringify(this.walk.data, null, "    "),
//       type: 'string',
//       encoding: 'base64',
//       filename: 'walk.json',
//       mimeType: 'application/json'

        $config = Factory::getConfig();
        $sender = array(
            $config->get('mailfrom'),
            $config->get('fromname')
        );

        $container = Factory::getContainer();
        $mailer = $container->get(MailerFactoryInterface::class)->createMailer();
        $mailer->isHtml(true);
        $mailer->Encoding = '8bit';
        $mailer->setSender($sender);
        if ($replyTo !== null) {
            $mailer->addReplyTo($replyTo->email, $replyTo->name);
        }
        $mailer->setSubject($subject);
        if ($attach !== null) {
            if ($attach->type === 'string') {
                self::addStringAttachment($mailer, $attach);
            }
        }
        $okay = true;
        foreach ($sendToArray as $sendTo) {
            $mailer->clearAllRecipients();
            $mailer->addRecipient($sendTo->email, $sendTo->name);
            if ($copy !== null) {
                $mailer->addCC($copy->email, $copy->name);
                $copy = null;
            }
            $body = $content;
            $mailer->setBody($body);
            $errorMessage = '';
            try {
                $send = $mailer->Send();
            } catch (\Throwable $ex) {
                $errorMessage = $ex->getMessage();
                $okay = false;
            }
            if (!$send) {
                $okay = false;
            }
            self::addEmailLog($sendTo->email, $subject, $replyTo->email, $okay, $errorMessage);
        }
        return $okay;
    }

    private static function addStringAttachment($mailer, $attach) {
        // Your string content, e.g. ICS
        $filename = $attach->filename;
        $encoding = $attach->encoding;
        $mimeType = $attach->mimeType;
        $contents = $attach->data;

        // Get Joomla tmp path from configuration
        $config = Factory::getConfig();
        $tmpPath = rtrim($config->get('tmp_path'), '/');  // e.g. /path/to/site/tmp
        // Build a unique filename
        $file = $tmpPath . '/ics_' . uniqid() . '.ics';

        // Write the string into the file
        file_put_contents($file, $contents);

        // Now $file is a real file you can attach:
        $mailer->addAttachment($file, $filename, $encoding, $mimeType);

        // Optionally delete after sending:
        //  @unlink($file);
    }

    public static function addEmailLog($to, $title, $replyto, $okay, $message) {
        $data = new \stdClass();
        $data->datetime = (new Date())->toSql();
        $data->to = $to;
        $data->title = $title;
        $data->replyto = $replyto;
        //if ($okay) {
        $data->sent = $okay;
        // } else {
        //     $data->sendstatus = '0';
        // }

        $data->message = $message;

        $db = Factory::getContainer()->get('DatabaseDriver');
        $result = $db->insertObject('#__ra_email_log', $data, 'id');  // 'id' is primary key; gets auto-filled

        self::purgeEmailLog();
    }

    public static function purgeEmailLog() {
        $componentParams = ComponentHelper::getParams('com_ra_library');
        $days = $componentParams->get('logretentionperiod', 180);

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        // e.g. delete records older than $days days

        $cutoff = (new \DateTimeImmutable())
                ->modify("-{$days} days")
                ->format('Y-m-d H:i:s');

        $query
                ->delete($db->quoteName('#__ra_email_log'))
                ->where(
                        $db->quoteName('datetime') . ' < :cutoffDate'
                )
                ->bind(':cutoffDate', $cutoff, ParameterType::STRING);

        $db->setQuery($query);
        $db->execute();
    }
}
