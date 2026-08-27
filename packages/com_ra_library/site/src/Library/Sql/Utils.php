<?php
namespace Ramblers\Component\Ra_library\Site\Library\Sql;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use Joomla\CMS\Factory;
class Utils {

    static function tableExists($table) {
        $db = Factory::getDbo();
        $findTable = $db->replacePrefix($table, $prefix = '#__');
        $tables = $db->getTableList();
        foreach ($tables as $value) {
            if ($value == $findTable) {
                return true;
            }
        }
        return false;
    }

}
