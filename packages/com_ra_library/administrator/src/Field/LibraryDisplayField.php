<?php

namespace Ramblers\Component\Ra_library\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

class LibraryDisplayField extends ListField {

    protected $type = 'LibraryDisplay';

    protected function getOptions(): array {
        $options = parent::getOptions();

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
                ->select($db->quoteName(['id', 'title']))
                ->from($db->quoteName('#__ra_library_displays'))
                ->order($db->quoteName('title') . ' ASC');

        $db->setQuery($query);

        try {
            $items = $db->loadObjectList();
        } catch (\RuntimeException $e) {
            return $options;
        }

        foreach ($items as $item) {
            $options[] = HTMLHelper::_('select.option', $item->id, $item->title);
        }

        return $options;
    }
}
