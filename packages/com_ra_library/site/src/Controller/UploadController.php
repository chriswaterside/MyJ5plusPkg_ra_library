<?php

namespace Ramblers\Component\Ra_library\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\Authentication\AuthenticationHelper;
use Joomla\CMS\Mail\MailerFactoryInterface;

class UploadController extends BaseController {

    public function submit() {
        $app = Factory::getApplication();
        $app->getSession()->checkToken() or jexit('Invalid Token');

        $user = $app->getIdentity();

        if ($user->guest) {
            $app->enqueueMessage(Text::_('Login required.'), 'error');
            $this->setRedirect('index.php?option=com_ra_library&view=upload');
            return;
        }

        $params = $app->getParams('com_ra_library');
        $uploadFolder = trim($params->get('upload_folder', 'images/uploads'), '/');
        $allowed = array_filter(
                array_map(
                        'trim',
                        explode(',', $params->get('allowed_extensions', 'pdf,csv,docx'))
                )
        );

        $inputFile = $_FILES['jform'] ?? null;
        $tmp = $inputFile['tmp_name']['file'] ?? '';
        $name = $inputFile['name']['file'] ?? '';

        if (!$tmp || !$name) {
            $app->enqueueMessage(Text::_('No file uploaded.'), 'error');
            $this->setRedirect('index.php?option=com_ra_library&view=upload');
            return;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed, true)) {
            $app->enqueueMessage(Text::_('File type not allowed.'), 'error');
            $this->setRedirect('index.php?option=com_ra_library&view=upload');
            return;
        }

        $safeBase = File::makeSafe(pathinfo($name, PATHINFO_FILENAME));
        $destDir = JPATH_SITE . '/' . $uploadFolder;

        try {
            if (!Folder::exists($destDir)) {
                Folder::create($destDir);
            }

            $dest = $destDir . '/' . $safeBase . '.' . $ext;
            File::upload($tmp, $dest);

            // ✅ Upload success; now optionally send email to admins
            if ($params->get('notify_admins_on_upload', 0)) {
                $this->sendEmailToAdmins($user, $name, $dest);
            }

            $app->enqueueMessage(Text::_('File uploaded successfully.'), 'message');
        } catch (\Exception $e) {
            $app->enqueueMessage(Text::_('Upload failed.'), 'error');
        }
        $menu = $app->getMenu();
        $active = $menu->getActive();
        $itemId = $active ? $active->id : 0;

        $this->setRedirect('index.php?option=com_ra_library&view=upload&Itemid=' . $itemId);
    }

    private function sendEmailToAdmins($user, $fileName, $filePath) {
        $app = Factory::getApplication();

        // Get the Mailer instance from the DI Container
        $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

        // Initialize the MailTemplate with the mailer instance
        $mailTemplate = new MailTemplate(
                'com_ra_library.upload_notification',
                $app->get('language'),
                $mailer
        );

        // Add your template variables
        $mailTemplate->addTemplateData([
            'sitename' => $app->get('sitename'),
            'filename' => $fileName,
            'filepath' => $filePath,
            'username' => $user->name ?? $user->username
        ]);

        // Get the list of all Super Users
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $query = $db->getQuery(true)
                ->select($db->quoteName('u.email'))
                ->from($db->quoteName('#__users', 'u'))
                ->join('INNER', $db->quoteName('#__user_usergroup_map', 'map'), $db->quoteName('map.user_id') . ' = ' . $db->quoteName('u.id'))
                ->join('INNER', $db->quoteName('#__usergroups', 'g'), $db->quoteName('map.group_id') . ' = ' . $db->quoteName('g.id'))
                ->where($db->quoteName('g.title') . ' = ' . $db->quote('Super Users'));

        $db->setQuery($query);
        $adminEmails = $db->loadColumn();

        // Add them as recipients
        if (!empty($adminEmails)) {
            foreach ($adminEmails as $email) {
                $mailTemplate->addRecipient($email);
            }
        }

        // Send
        try {
            $mailTemplate->send();
        } catch (\Exception $e) {
            // Log the error
            $app->getLogger()->error('Failed to send upload notification: ' . $e->getMessage());
        }
    }
}
