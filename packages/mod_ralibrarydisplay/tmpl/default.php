<?php

/**
 * @module      RA Library Display
 * @author      Chris Vaughan
 * @website     http://ramblers-webs.org.uk/
 * @copyright   Copyright (C) 2024 Chris Vaughan webmaster@ramblers-webs.org.uk All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Ramblers\Component\Ra_library\Site\Helper\DisplayHelper;

// -----------------------------------------------------------------------
// Module logic
// -----------------------------------------------------------------------
$doc = Factory::getApplication()->getDocument();
$doc->addStyleDeclaration('
    .mod-librarydisplay-content {margin-left:5px;
    margin-right:5px;}
');
echo "<div class='mod-librarydisplay-content'>";
$id = (int) $params->get('library_display_id');
if ($id) {
    $db = Factory::getDbo();
    $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__ra_library_displays'))
            ->where($db->quoteName('id') . ' = ' . $db->quote($id));
    $db->setQuery($query);
    try {
        $record = $db->loadObject(); // all columns available as properties, e.g. $display->title
    } catch (\RuntimeException $e) {
        $record = null;
    }
}
if ($record) {
    $displayoption = $record->displayoption;
    $options = $record->options;
    $display = new DisplayHelper($displayoption, $options);
    $display->Display($display);
}
echo "</div>";
