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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Read-only "current file" indicator for a GPX/document subform row - bound
 * to the row's own 'file_path' field (a hidden value carried through
 * untouched by AttachmentHelper::saveAttachments() unless a new file is
 * uploaded). Shows the current filename with a download link if one's
 * already stored, plus a hidden input so the path value is still submitted
 * with the row - same pattern as ImagepreviewField, just a filename/link
 * instead of a thumbnail since these aren't images.
 *
 * @since  1.0.0
 */
class FilepreviewField extends FormField
{
    /**
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'Filepreview';

    /**
     * @return  string
     *
     * @since   1.0.0
     */
    protected function getInput()
    {
        $hidden = '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="' . htmlspecialchars((string) $this->value, \ENT_QUOTES, 'UTF-8') . '">';

        if (empty($this->value)) {
            return $hidden . '<span class="text-muted small">' . Text::_('COM_RA_LIBRARY_ATTACHMENT_NEW_ROW') . '</span>';
        }

        $href = Uri::root(true) . '/' . ltrim((string) $this->value, '/');
        $filename = basename((string) $this->value);

        return $hidden . '<a href="' . htmlspecialchars($href, \ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">'
            . '<span class="icon-file-alt" aria-hidden="true"></span> ' . htmlspecialchars($filename, \ENT_QUOTES, 'UTF-8') . '</a>';
    }
}
