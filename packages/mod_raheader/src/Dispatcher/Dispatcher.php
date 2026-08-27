<?php

/**
 * @module      RA Header
 * @author      Chris Vaughan
 * @copyright   Copyright (C) 2023 Chris Vaughan webmaster@ramblers-webs.org.uk All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace RamblersWebs\Module\Raheader\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;

/**
 * Dispatcher class for mod_raheader.
 * Joomla 5/6 uses a Dispatcher to bootstrap module logic.
 */
class Dispatcher extends AbstractModuleDispatcher
{
    /**
     * Returns the layout data for the template.
     *
     * @return array
     */
    protected function getLayoutData(): array
    {
        $data   = parent::getLayoutData();
        $params = $data['params'];

        // Pre-resolve the module class suffix for the template
        $class = $params->get('moduleclass_sfx', '');
        $data['moduleclass_sfx'] = htmlspecialchars((string) $class, ENT_QUOTES, 'UTF-8');

        return $data;
    }
}
