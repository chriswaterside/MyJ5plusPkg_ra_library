<?php

namespace Ramblers\Component\Ra_library\Administrator\Field;

/*
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */

/**
 * Description of SortablelistField
 *
 * @author chris
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;

class SortablelistField extends ListField {

    protected $type = 'Sortablelist';

    protected function getInput() {
        $options = $this->getOptions();
        $value = (array) $this->value; // Selected item IDs

        HTMLHelper::_('script', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js');
        HTMLHelper::_('script', 'com_ra_library/sortablelist.js', ['version' => 'auto', 'relative' => true]);
        HTMLHelper::_('stylesheet', 'com_ra_library/sortablelist.css', ['version' => 'auto', 'relative' => true]);

        $html = '<div class="sortable-container">';
        $html .= '<div id="' . $this->id . '-sortable" class="sortable-list">';

        foreach ($options as $option) {
            $selected = in_array($option->value, $value);
            $html .= '<div class="sortable-item" data-value="' . htmlspecialchars($option->value) . '">';
            $html .= '<label class="sortable-checkbox">';
            $html .= '<input type="checkbox" name="' . $this->name . '" value="' . htmlspecialchars($option->value) . '" ' . ($selected ? 'checked' : '') . '>';
            $html .= '<span class="sortable-handle">☰</span>';
            $html .= htmlspecialchars($option->text);
            $html .= '</label>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
