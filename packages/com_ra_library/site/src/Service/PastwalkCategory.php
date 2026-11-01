<?php

/**
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Site\Service;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Categories\Categories;

/**
 * Category tree for the "pastwalk" section (categories stored under the extension
 * com_ra_library.pastwalk).
 *
 * Joomla looks this class up by name (Site\Service\<Section>Category) when
 * something needs the categories of a component section. Without it the
 * Custom Fields screens hide the "Category" selector on a field, so a field
 * cannot be restricted to particular categories.
 *
 * Past Walks and Routes share one table, told apart by record_type, so this
 * is only used for the category tree itself, not for item counts.
 *
 * @since  1.0.14
 */
class PastwalkCategory extends Categories
{
    /**
     * @param   array  $options  Array of options
     */
    public function __construct($options = [])
    {
        $options['table']     = '#__ra_library_routes';
        $options['extension'] = 'com_ra_library.pastwalk';

        parent::__construct($options);
    }
}
