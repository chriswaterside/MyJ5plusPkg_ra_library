<?php
namespace Ramblers\Component\Ra_library\Site\Library\Accounts;

/**
 * Description of DnsRecord
 *
 * @author Chris Vaughan
 */
class Record {

    private $domain;

    public function __construct($domain) {
        $this->domain = $domain;
    }

    public function getSSLMailServer() {
        $server = "";
        $url = "mail." . $this->domain;
        $results = dns_get_record($url, DNS_ALL);
        if ($results===false){
            return $server;
        }
        $result = $results[0];
        if (isset($result["ip"])) {
            $ip = $result["ip"];
            $parts = explode(".", $ip);
            if ($parts[3] !== null) {
                $server = "mail" . $parts[3] . ".extendcp.co.uk";
            }
        }
        return $server;
    }

}
