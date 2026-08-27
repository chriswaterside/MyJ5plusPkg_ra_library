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
use Joomla\CMS\Uri\Uri;

/**
 * Read-only thumbnail preview for an image gallery row. Bound to the row's
 * own 'thumbnail_path' field (a hidden value carried through untouched by
 * saveImages() unless a new file is uploaded) - shows the current photo so
 * the admin can see what's already there without re-uploading, plus a
 * hidden input so the path value is still submitted with the row.
 *
 * @since  1.0.0
 */
class ImagepreviewField extends FormField
{
    /**
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'Imagepreview';

    /**
     * @return  string
     *
     * @since   1.0.0
     */
    protected function getInput()
    {
        $hidden = '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="' . htmlspecialchars((string) $this->value, \ENT_QUOTES, 'UTF-8') . '">';

        if (empty($this->value)) {
            return $hidden . '<span class="text-muted small">' . \Joomla\CMS\Language\Text::_('COM_RA_LIBRARY_IMAGE_NEW_ROW') . '</span>';
        }

        $src = Uri::root(true) . '/' . ltrim((string) $this->value, '/');

        return $hidden . '<img src="' . htmlspecialchars($src, \ENT_QUOTES, 'UTF-8') . '" alt="" style="max-width:80px;max-height:80px;border-radius:3px;">';
    }
}
