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

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
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

        $thumbRelative = ltrim((string) $this->value, '/');
        $src = Uri::root(true) . '/' . $thumbRelative;

        // The 1600px copy sits next to the thumbnail and shares its base name
        // (ImageGalleryHelper::resizeAndStore(): <base>_thumb.jpg / <base>_large.jpg).
        // Use it for the hover preview and the click viewer when it is there.
        $largeRelative = preg_replace('/_thumb\.jpg$/', '_large.jpg', $thumbRelative);
        $largeSrc = ($largeRelative !== $thumbRelative && is_file(JPATH_ROOT . '/' . $largeRelative))
            ? Uri::root(true) . '/' . $largeRelative
            : $src;

        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('fields.imagepreview', 'media/com_ra_library/css/fields/imagepreview.css');
        $wa->registerAndUseScript('fields.imagepreview', 'media/com_ra_library/js/fields/imagepreview.js', [], ['defer' => true]);
        Text::script('COM_RA_LIBRARY_IMAGE_VIEWER_CLOSE');
        Text::script('COM_RA_LIBRARY_IMAGE_VIEWER_PREV');
        Text::script('COM_RA_LIBRARY_IMAGE_VIEWER_NEXT');
        Text::script('COM_RA_LIBRARY_IMAGE_VIEWER_COUNT');

        return $hidden
            . '<a class="ra-photo-thumb" href="' . htmlspecialchars($largeSrc, \ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener"'
            . ' title="' . htmlspecialchars(Text::_('COM_RA_LIBRARY_IMAGE_VIEW_LARGER'), \ENT_QUOTES, 'UTF-8') . '">'
            . '<img src="' . htmlspecialchars($src, \ENT_QUOTES, 'UTF-8') . '" alt="">'
            . '</a>';
    }
}
