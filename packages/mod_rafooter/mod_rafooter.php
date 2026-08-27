<?php
/**
 *              Part of the RA_library package
 * @module      RA Footer
 * @author      Chris Vaughan
 * @website     http://ramblers-webs.org.uk/
 * @copyright   Copyright (C) 2013 Chris Vaughan <webmaster@ramblers-webs.org.uk>. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
\JLoader::registerNamespace(
    'RamblersWebs\\Module\\Rafooter',
    __DIR__ . '/src',
    false,
    false,
    'psr4'
);

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx') ?? '');

require ModuleHelper::getLayoutPath('mod_rafooter', $params->get('layout', 'default'));