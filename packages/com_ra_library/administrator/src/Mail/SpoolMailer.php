<?php

namespace Ramblers\Component\Ra_library\Administrator\Mail;

use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Factory;

class SpoolMailer extends Mail {

    private string $component;
    private string $spoolFolder;
    private array $spooledComponents;

    public function __construct(string $component, string $spoolFolder, array $spooledComponents) {
        parent::__construct();
        $this->component = $component;
        $this->spoolFolder = $spoolFolder;
        $this->spooledComponents = $spooledComponents;
    }

    public function Send() {
        // If component is not in the spool list, send immediately
        if (!in_array($this->component, $this->spooledComponents)) {
            return parent::Send();
        }

        $db = Factory::getContainer()->get('DatabaseDriver');

        // Copy attachments to spool folder so they persist until the task runs
        $spooledAttachments = [];
        foreach ($this->getAttachments() as $att) {
            $originalPath = $att[0];
            $displayName = $att[2];
            if (is_file($originalPath)) {
                $spoolName = uniqid('att_') . '_' . basename($originalPath);
                $spoolPath = $this->spoolFolder . '/' . $spoolName;
                copy($originalPath, $spoolPath);
                $spooledAttachments[] = [
                    'path' => $spoolPath,
                    'name' => $displayName,
                ];
            }
        }

        $to = [];
        foreach ($this->getToAddresses() as $addr) {
            $to[] = ['email' => $addr[0], 'name' => $addr[1]];
        }
        $cc = [];
        foreach ($this->getCcAddresses() as $addr) {
            $cc[] = ['email' => $addr[0], 'name' => $addr[1]];
        }
        $bcc = [];
        foreach ($this->getBccAddresses() as $addr) {
            $bcc[] = ['email' => $addr[0], 'name' => $addr[1]];
        }
        $replyTo = [];
        foreach ($this->getReplyToAddresses() as $addr) {
            $replyTo[] = ['email' => $addr[0], 'name' => $addr[1]];
        }

        $row = (object) [
                    'created' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'status' => 0,
                    'attempts' => 0,
                    'sent' => null,
                    'component' => $this->component,
                    'to_email' => json_encode($to),
                    'cc' => json_encode($cc),
                    'bcc' => json_encode($bcc),
                    'reply_to' => json_encode($replyTo),
                    'subject' => $this->Subject,
                    'body' => $this->AltBody,
                    'htmlbody' => $this->Body,
                    'attachments' => json_encode($spooledAttachments),
                    'error' => null,
        ];

        $db->insertObject('#__ra_mail_spool', $row);

        return true;
    }
}
