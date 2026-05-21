<?php

/**
 *              Part of the RA_library package
 * @module      RA Header
 * @author      Chris Vaughan
 * @website     http://ramblers-webs.org.uk/
 * @copyright   Copyright (C) 2023 Chris Vaughan webmaster@ramblers-webs.org.uk All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

// Joomla 5/6 entry point - logic has moved to the Dispatcher class.
// This file is kept minimal as required by the new extension architecture.
require ModuleHelper::getLayoutPath('mod_raheader', $params->get('layout', 'default'));
