<?php

/*
 * Send an email on behalf of a JS script
 *      parameters
 *         POST data
 * 
 *      url
 *         index.php?option=com_ra_library&view=sendemail&format=json
 * 
 * 
 */

namespace Ramblers\Component\Ra_library\Site\View\Sendemail;

use \Ramblers\Component\Ra_library\Site\Helper\Ra_libraryHelper as helper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\View\JsonView as BaseJsonView;
use Joomla\CMS\Factory;

// use Joomla\CMS\Component\ComponentHelper;
// No direct access
defined('_JEXEC') or die;

class JsonView extends BaseJsonView {

    public function display($tpl = null) {
        try {
            $feedback = [];
            $data = helper::getPostedData();
            $to = [];
            foreach ($data->toid as $id) {
                $user = Factory::getUser($id);
                $item = (object) ['name' => $user->name,
                            'email' => $user->email];
                array_push($to, $item);
            }

            $replyTo = $data->replyTo;
            $copy = $data->copy;
            $title = $data->title;
            $content = $data->content;
            $attach = $data->attach;
            if ($to === null OR $replyTo === null) {
                throw new \RuntimeException('Invalid user input 1');
            }
            if ($title === null OR $content === null) {
                throw new \RuntimeException('Invalid user input 2');
            }
            $okay = helper::sendEmails($to, $copy, $replyTo, $title, $content, $attach);
            if (!$okay) {
                throw new \RuntimeException('Invalid user input 3');
            }
            $feedback[] = '<h3>Email has been sent</h3>';
            $record = (object) [
                        'feedback' => $feedback
            ];
            echo new JsonResponse($record);
        } catch (Exception $e) {
            echo new JsonResponse($e);
        }
    }
}
