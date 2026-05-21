<?php
namespace Ramblers\Component\Ra_library\Site\Library\Accounts;

/**
 * Description of accounts
 *
 * @author Chris
 */
define("ACCOUNTTABLE", "j8eh1_rw_accounts_domains");
use Joomla\CMS\Factory;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;

class Accounts {

    private $dbresults;
      public function __construct() {
        $this->readAccounts();
  
    }

    public function updateAccounts() {
        $this->getAccounts(false);
        $org = new ROrganisation();
        if ($org->areas != null) {
            $this->updateDatabase($org);
        } else {
            echo "Organisation not available: locations not updated";
        }
    }

    public function listLogDetails($format) {
        $sortbystatus = false;
        switch ($format) {
            case RAccountsAccount::FORMAT_NOLOGFILE:
                $sortbystatus = true;
                break;
            case RAccountsAccount::FORMAT_SPF:
                $sortbystatus = true;
                break;
            default:
                $sortbystatus = false;
                break;
        }
        if ($format != RAccountsAccount::FORMAT_SINGLE) { // all domains
            $this->getAccounts($sortbystatus);
            echo "<table style='font-size: 85%'>";
            echo RHtml::addTableHeader(RAccountsAccount::getHeader($format, RAccountsLogfile::DISP_NONE));
            foreach ($this->dbresults as $item) :
                $adomain = strtolower(trim($item->domain));
                $status = $item->status;
                if ($status !== 'DELETED') {
                    $webmaster = $item->web_master;
                    $account = new RAccountsAccount($adomain, $status, $webmaster);
                    $cols = $account->getColumns($format, RAccountsLogfile::DISP_NONE);
                    if ($cols <> null) {
                        echo RHtml::addTableRow($cols);
                    }
                }
            endforeach;
            echo "</table>";
        } else {
            $jinput = Factory::getApplication()->input;
            $domain = $jinput->getString('domain', '');
            echo "<h2 style='font-variant: small-caps;'>" . $domain . "</h2>";
            $this->getAccounts($sortbystatus);
            $formats = RAccountsAccount::formatsArray();
            foreach ($formats as $format) {
                foreach ($this->dbresults as $item) :
                    $adomain = strtolower(trim($item->domain));
                    if ($adomain == $domain) {
                        $status = $item->status;
                        $webmaster = $item->web_master;
                        $account = new RAccountsAccount($adomain, $status, $webmaster);
                        $cols = $account->getColumns($format, RAccountsLogfile::DISP_VIEW);
                        if ($cols <> null) {
                            RAccountsAccount::displayTitle($format);
                            echo "<table style='font-size: 85%'>";
                            echo RHtml::addTableHeader(RAccountsAccount::getHeader($format, RAccountsLogfile::DISP_VIEW));
                            echo RHtml::addTableRow($cols);
                            echo "</table>";
                            $account->displayDetails($format);
                        }
                    }
                endforeach;
            }
        }
    }

    public function addMapMarkers($map) {
        Load::addScript("media/com_ra_library/js/accounts/accounts.js");
        Load::addStyleSheet('media/com_ra_library/css/ramblerslibrary.css');
        $map->setCommand('ra.display.accountsMap');
        $map->options->fullscreen = true;
        $map->options->mouseposition = true;
        $map->options->displayElevation = false;
        $map->options->rightclick = true;
        $map->options->fitbounds = true;
        $map->options->cluster = true;
        $map->options->settings = true;
        $map->options->draw = false;
        $map->options->print = true;
      //  $map->options->ramblersPlaces = true;
        $this->readAccounts();

        $data = new class {
            
        };
        echo "<h4>Map of Hosted Ramblers Area/Group Websites</h4>";
        $data->hostedsites = $this->dbresults;
        $map->setDataObject($data);
        //    $map->display();
    }

    public function readAccounts() {

        if (RSqlUtils::tableExists(ACCOUNTTABLE)) {
            $db = Factory::getDbo();
            $query = $db->getQuery(true);

            $query->select("*");
            $query->from($db->quoteName(ACCOUNTTABLE));

            // Reset the query using our newly populated query object.
            $db->setQuery($query);

            // Load the results as a list of stdClass objects
            $this->dbresults = $db->loadObjectList();
        }
    }

    public function getResults() {
        return $this->dbresults;
    }

    private function getAccounts($sortbystatus) {

        if (RSqlUtils::tableExists(ACCOUNTTABLE)) {
            $db = Factory::getDbo();
            $query = $db->getQuery(true);

            $query->select($db->quoteName(array('id', 'code', 'domain', 'status', 'web_master')));
            $query->from($db->quoteName(ACCOUNTTABLE));
            if ($sortbystatus) {
                $query->order('status,domain ASC');
            } else {
                $query->order('domain ASC');
            }

            // Reset the query using our newly populated query object.
            $db->setQuery($query);

            // Load the results as a list of stdClass objects
            $this->dbresults = $db->loadObjectList();
        }
    }

    private function updateDatabase($org) {
        foreach ($this->dbresults as $item) :
            $this->updateAccount($item, $org);
        endforeach;
    }

    private function updateAccount($item, $org) {
        $groups = $org->groups;
        $uppCode = strtoupper($item->code);
        echo "<p>Updating group " . $uppCode . "<p/>";
        if (isset($groups[$uppCode])) {
            $group = $groups[$uppCode];
            $areacode = substr($uppCode, 0, 2);
            $area = $org->areas[$areacode];
            $this->updateDatabaseRecord($item->id, $group, $area, $uppCode);
        } else {
            $this->defaultDatabaseRecord($item->id, $uppCode, $item->domain);
        }
    }

    private function updateDatabaseRecord($id, $group, $area, $uppCode) {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $groupname = $group->name;
        if ($group->code == $area->code) {
            $groupname = "";
        }
        // Fields to update.
        $fields = array(
            $db->quoteName('latitude') . ' = ' . $group->latitude,
            $db->quoteName('longitude') . ' = ' . $group->longitude,
            $db->quoteName('areaname') . ' = "' . $area->name . '"',
            $db->quoteName('groupname') . ' = "' . $groupname . '"',
            $db->quoteName('code') . ' = "' . $uppCode . '"'
        );

        // Conditions for which records should be updated.
        $conditions = array(
            $db->quoteName('id') . "='" . $id . "'"
        );

        $query->update($db->quoteName(ACCOUNTTABLE))->set($fields)->where($conditions);

        $db->setQuery($query);

        $db->execute();
    }

    private function defaultDatabaseRecord($id, $uppCode, $domain) {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        // Fields to update.
        $fields = array(
            $db->quoteName('latitude') . ' = 51.488010',
            $db->quoteName('longitude') . ' = -0.123809',
            $db->quoteName('areaname') . ' = "Central"',
            $db->quoteName('groupname') . ' = "' . $domain . '"',
            $db->quoteName('code') . ' = "' . $uppCode . '"'
        );

        // Conditions for which records should be updated.
        $conditions = array(
            $db->quoteName('id') . "='" . $id . "'"
        );

        $query->update($db->quoteName(ACCOUNTTABLE))->set($fields)->where($conditions);

        $db->setQuery($query);

        $db->execute();
    }

}
