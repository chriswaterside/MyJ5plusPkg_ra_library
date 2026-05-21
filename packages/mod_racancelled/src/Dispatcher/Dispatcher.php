<?php

/**
 * @module      RA Cancelled Walks
 * @author      Chris Vaughan
 * @copyright   Copyright (C) 2024 Chris Vaughan webmaster@ramblers-webs.org.uk All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace RamblersWebs\Module\Racancelled\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;

/**
 * Dispatcher class for mod_racancelled.
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
        $data = parent::getLayoutData();
        $params = $data['params'];

        $class = $params->get('moduleclass_sfx', '');
        $data['moduleclass_sfx'] = htmlspecialchars((string) $class, ENT_QUOTES, 'UTF-8');

        return $data;
    }
}
