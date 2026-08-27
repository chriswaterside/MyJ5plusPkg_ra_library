<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\Registry\Registry;

/**
 * Distance field for distance_km: stored in the database as kilometres
 * (matching the Walks Manager feed's own distance_km field, so the import
 * screen and this field never disagree on what's actually stored), but
 * shown to and typed in by the admin as miles, since that's the unit UK
 * Ramblers groups actually use when describing a walk's length.
 *
 * The conversion happens in both directions:
 *  - getInput() converts the stored km value to miles for display.
 *  - filter() converts the submitted miles value back to km before it's
 *    bound to the table - this is the standard Joomla hook for a field to
 *    post-process its own submitted value (Form::filter() calls it), rather
 *    than needing any special-casing in the Model/Table.
 *
 * @since  1.0.0
 */
class DistancemilesField extends FormField
{
    /**
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'Distancemiles';

    /**
     * Exact km-per-mile conversion factor.
     *
     * @since 1.0.0
     */
    private const KM_PER_MILE = 1.609344;

    /**
     * @return  string
     *
     * @since   1.0.0
     */
    protected function getInput()
    {
        $miles = ($this->value !== null && $this->value !== '')
            ? round(((float) $this->value) / self::KM_PER_MILE, 1)
            : '';

        $attributes = [
            'type="number"',
            'name="' . $this->name . '"',
            'id="' . $this->id . '"',
            'value="' . htmlspecialchars((string) $miles, \ENT_QUOTES, 'UTF-8') . '"',
            'step="0.1"',
            'min="0"',
            'class="form-control"',
        ];

        return '<input ' . implode(' ', $attributes) . '>';
    }

    /**
     * Convert the submitted miles value back to km before it's stored.
     *
     * @param   mixed          $value  The raw (miles) value submitted for this field.
     * @param   string|null    $group  The field name group.
     * @param   Registry|null  $input  The Registry to check for field values, null uses $this->formControl.
     *
     * @return  mixed  The km value to actually store.
     *
     * @since   1.0.0
     */
    public function filter($value, $group = null, ?Registry $input = null)
    {
        if ($value === '' || $value === null) {
            return '';
        }

        if (!is_numeric($value)) {
            return '';
        }

        return round(((float) $value) * self::KM_PER_MILE, 3);
    }
}
