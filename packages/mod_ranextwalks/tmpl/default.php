<?php

/**
 * @module      RA Next Walks
 * @author      Chris Vaughan
 * @website     http://ramblers-webs.org.uk/
 * @copyright   Copyright (C) 2024 Chris Vaughan webmaster@ramblers-webs.org.uk All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die;

use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Feed;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Feedoptions;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Std\Nextwalks;
use Joomla\CMS\Factory;

// -----------------------------------------------------------------------
// Module logic
// -----------------------------------------------------------------------
$groupsString = $params->get('ramblersgroups', '');
if ($groupString === "") {
    throw new \RuntimeException('Next walks module: no groups specified');
}
$doc = Factory::getApplication()->getDocument();
$doc->addStyleDeclaration('
    .mod-nextwalks-content {margin-left:5px;
    margin-right:5px;}
');
echo "<div class='mod-nextwalks-content'>";
$codes = array_column(json_decode($groupsString), 'code');
$groups = implode(",", $codes);

$options = new Feedoptions($groups);
$feed = new Feed($options);
$display = new Nextwalks();

$feed->Display($display);
echo "</div>";