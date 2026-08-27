<?php

namespace Ramblers\Component\Ra_library\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Ramblers\Component\Ra_library\Administrator\Helper\DisplayoptionLabelHelper;

/**
 * Sorted-by-title picker for a Library Display, used as the "Select Item"
 * field when setting up a menu item (see site/tmpl/librarydisplay/
 * default.xml, which registers this via addfieldprefix instead of Joomla's
 * built-in type="sql" field, so it can show the friendly display-type
 * label alongside the title - not just id/title).
 *
 * @since  1.0.0
 */
class LibraryDisplayField extends ListField {

    protected $type = 'LibraryDisplay';

    protected function getOptions(): array {
        $options = parent::getOptions();

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
                ->select($db->quoteName(['id', 'title', 'displayoption']))
                ->from($db->quoteName('#__ra_library_displays'))
                // Menu items should only be able to point at a published
                // display - not unpublished (0), archived (2) or trashed (-2).
                ->where($db->quoteName('state') . ' = 1')
                ->order($db->quoteName('title') . ' ASC');

        $db->setQuery($query);

        try {
            $items = $db->loadObjectList();
        } catch (\RuntimeException $e) {
            return $options;
        }

        foreach ($items as $item) {
            $typeLabel = DisplayoptionLabelHelper::getLabel((string) $item->displayoption);
            $label = $item->title . ' (' . $item->id . ') - ' . $typeLabel;
            $options[] = HTMLHelper::_('select.option', $item->id, $label);
        }

        return $options;
    }
}
