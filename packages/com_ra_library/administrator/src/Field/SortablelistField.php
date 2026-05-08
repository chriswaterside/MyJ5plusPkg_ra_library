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
use \Joomla\CMS\Language\Text;

class SortablelistField extends ListField {

    protected $type = 'Sortablelist';

    /**
     * Method to get the field label, including description.
     */
    protected function getInput() {
        $options = $this->getOptions();
        $rawValue = $this->value;
        if (is_string($rawValue)) {
            $value = array_filter(array_map('trim', explode(',', $rawValue)));
        } else {
            $value = (array) $rawValue; // Already array from DB/save
        }
        HTMLHelper::_('script', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js');
        HTMLHelper::_('script', 'com_ra_library/fields/sortablelist.js', ['version' => 'auto', 'relative' => true]);
        HTMLHelper::_('stylesheet', 'com_ra_library/fields/sortablelist.css', ['version' => 'auto', 'relative' => true]);

        // Build lookup for options by value
        $optionMap = [];
        foreach ($options as $option) {
            $optionMap[$option->value] = $option;
        }

        $html = '<div class="sortable-container">';
        $html .= '<div id="' . $this->id . '-sortable" class="sortable-list">';

// First, render selected items in the saved order
        foreach ($value as $val) {
            $val = (string) $val;
            if (isset($optionMap[$val])) {
                $option = $optionMap[$val];
                $selected = true;
                $html .= '<div class="sortable-item" data-value="' . htmlspecialchars($option->value) . '">';
                $html .= '<label class="sortable-checkbox">';
                $html .= '<input type="checkbox" name="' . $this->name . '" value="' . htmlspecialchars($option->value) . '" ' . ($selected ? 'checked' : '') . '>';
                $html .= '<span class="sortable-handle">☰</span>';
                $html .= htmlspecialchars($option->text);
                $html .= '</label>';
                $html .= '</div>';
            }
        }

// Then, append unselected options (skip those already rendered)
        foreach ($options as $option) {
            $val = (string) $option->value;
            if (!in_array($val, $value)) {
                $selected = false;
                $html .= '<div class="sortable-item" data-value="' . htmlspecialchars($option->value) . '">';
                $html .= '<label class="sortable-checkbox">';
                $html .= '<input type="checkbox" name="' . $this->name . '" value="' . htmlspecialchars($option->value) . '" ' . ($selected ? 'checked' : '') . '>';
                $html .= '<span class="sortable-handle">☰</span>';
                $html .= htmlspecialchars($option->text);
                $html .= '</label>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}
