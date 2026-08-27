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

use Joomla\CMS\Form\Field\TextareaField;

/**
 * A plain textarea field, identical in every way to Joomla's core one,
 * except it appends the shared "Syntax" / "Fields" help buttons (see
 * LayoutHelpButtonsTrait) after its label when the field's XML declares a
 * helptype="walks|pastwalks|routes|categorytree" attribute. Used in place of
 * type="textarea" on any field whose value is a SimpleTemplate layout
 * string, on both the Display Options screen (librarydisplay.xml) and the
 * Global Options screen (config.xml) - one field type, same look and
 * behaviour wherever it's used.
 *
 * @since  1.0.0
 */
class LayoutHelpTextareaField extends TextareaField
{
    use LayoutHelpButtonsTrait;

    /**
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'LayoutHelpTextarea';

    /**
     * @since   1.0.0
     */
    protected function getLabel()
    {
        return parent::getLabel() . $this->renderLayoutHelpButtons();
    }
}
