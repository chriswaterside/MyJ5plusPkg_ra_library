<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk;
/**
 * Description of contacts
 *
 * @author chris
 */
use Joomla\CMS\Factory;
class Contact implements \JsonSerializable {

    private $id = 0;
    private $isLeader = false;       // is the contact info for the leader of the walk
    private $contactName = "";       // contact name
    private $email = "";             // email address for contact
    private $key = null;
    private $contactForm = "";       // The contact form on the Ramblers Site
    private $telephone1 = "";        // first telephone number of contact
    private $telephone2 = "";        // second telephone number of contact

    public function __construct(string $id, bool $isLeader, string $contactName, string $email,
            string $telephone1, string $telephone2, string $contactForm) {
        $this->id = $id;
        $this->isLeader = $isLeader;
        $this->contactName = $contactName;
        $this->email = $email;
        $this->telephone1 = $telephone1;
        $this->telephone2 = $telephone2;
        $this->contactForm = $contactForm;
        if ($this->email !== "") {
            $this->key = $this->encrypt($this->email);
            $this->email = 'yes'; // overwrite email
        }
        if ($this->contactForm != "") {
            $this->email = 'yes';
        }
    }

    public function getValue($option) {
        $BR = "<br/>";
        $out = "";
        switch ($option) {
            case "{contact}":
                $titlePrefix = 'Contact ';
                if ($this->isLeader) {
                    $titlePrefix = "Leader ";
                }
                if ($this->contactName !== "") {
                    $out .= "<b>" . $this->contactName . "</b>";
                }
                if ($this->email !== "") {
                    $out .= $BR . $this->getEmailLink($this);
                }
                if ($this->telephone1 !== "") {
                    $out .= $BR . $this->telephone1;
                }
                if ($this->telephone2 !== "") {
                    $out .= $BR . $this->telephone2;
                }
                if ($out !== '') {
                    $out = $titlePrefix . $out;
                }
                break;
            case "{contactname}":
                if ($this->contactName !== '') {
                    if ($this->isLeader) {
                        $out .= "Leader: ";
                    } else {
                        $out .= "Contact: ";
                    }
                }
                $out = $this->contactName;
                break;
            case "{contactperson}":
                $out = $this->contactName;
                break;
            case "{telephone}":
            case "{telephone1}":
                if ($this->telephone1 !== "") {
                    $out .= $this->telephone1;
                }
                break;
            case "{telephone2}":
                if ($this->telephone2 !== "") {
                    $out .= $this->telephone2;
                }
                break;
            case "{email}":
            case "{emailat}":
                if ($this->email !== "") {
                    $out = $this->getEmailLink($this);
                }
                break;
            case "{emaillink}":
                if ($this->email !== "") {
                    $out = $this->getEmailLink($this);
                }
                break;
        }
        return $out;
    }

    public function getIntValue($option) {
        switch ($option) {
            case "contactName":
                return $this->contactName;
            case "telephone1":
                return $this->telephone1;
            case "telephone2":
                return $this->telephone2;
            case "_icsrecord":
                $out = "";
                if ($this->contactName !== "") {
                    $out = "Contact: " . $this->contactName;
                }
                if ($this->telephone1 !== "") {
                    $out .= ", " . $this->telephone1;
                }
                if ($this->telephone2 !== "") {
                    $out .= ", " . $this->telephone2;
                }
                return $out . "<br/>";
        }
        $app = Factory::getApplication();
        $app->enqueueMessage("Internal error, invalid Contact value: " . $option);

        return "";
    }

    private function getEmailLink() {
        //   $link = "javascript:ra.walk.emailContact(\"" . $this->id . "\")";
        //  $out= "<span><a href='" . $link . "' >Email contact</a></span>";
        if ($this->email !== "") {
            if ($this->contactForm !== "") {
                $out = "<span><b>Contact link: </b><a target='_blank' href='" . $this->contactForm . "' title='Click to send an email to leader/contact or group'>Email walk contact</a></span>";
            } else {
                $gwemlink = "javascript:ra.walk.emailContact(\"" . $this->id . "\")";
                $out = "<span><a href='" . $gwemlink . "' title='Click to send an email to leader/contact'>Email contact</a></span>";
            }
        }
        return $out;
    }

    private function encrypt($simple_string) {
        // Store the cipher method
        $ciphering = "AES-128-CTR";

        // Use OpenSSl Encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;

        // Non-NULL Initialization Vector for encryption
        $encryption_iv = '1234567891011121';

        // Store the encryption key
        $encryption_key = "GeeksforGeeks";

        // Use openssl_encrypt() function to encrypt the data
        $encryption = openssl_encrypt($simple_string, $ciphering,
                $encryption_key, $options, $encryption_iv);
        return $encryption;
    }

    public function jsonSerialize(): mixed {
        return [
            'isLeader' => $this->isLeader,
            'contactName' => $this->contactName,
            'email' => $this->email,
            'key' => $this->key,
            'contactForm' => $this->contactForm,
            'telephone1' => $this->telephone1,
            'telephone2' => $this->telephone2
        ];
    }
}
