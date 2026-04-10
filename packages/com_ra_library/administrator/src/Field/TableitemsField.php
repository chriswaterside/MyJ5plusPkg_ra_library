<?php

/*
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */

/**
 * @package     Ramblers.Component\Ra_library
 * @subpackage  Administrator\Field
 * @copyright   Copyright (C) 2026 Your Name. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\HTML\HTMLHelper;

class TableitemsField extends TextField
{
    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'tableitems';

    protected $fieldsPerItem;
    protected $fieldNames;

    protected function getInput()
    {
        $value = $this->value ? json_decode($this->value, true) : [];
        
        $this->fieldsPerItem = (int) ($this->element['fieldsperitem'] ?? 2);
        if ($this->fieldsPerItem < 1 || $this->fieldsPerItem > 3) {
            $this->fieldsPerItem = 2;
        }
        
        // Custom field names from XML, e.g. fieldnames='["Name","Price","Description"]'
        $fieldNamesJson = (string) ($this->element['fieldnames'] ?? '');
        $this->fieldNames = $fieldNamesJson ? json_decode($fieldNamesJson, true) : [];
        if (count($this->fieldNames) !== $this->fieldsPerItem) {
            $this->fieldNames = array_map(fn($i) => "Field " . ($i+1), range(0, $this->fieldsPerItem-1));
        }

        HTMLHelper::_('script', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js');
        HTMLHelper::_('script', 'com_ra_library/tableitems.js', ['version' => 'auto', 'relative' => true]);
        HTMLHelper::_('stylesheet', 'com_ra_library/tableitems.css', ['version' => 'auto', 'relative' => true]);

        $html = '<div id="' . $this->id . '" class="table-items-container" data-name="' . $this->name . '" data-fields="' . $this->fieldsPerItem . '" data-fieldnames="' . htmlspecialchars(json_encode($this->fieldNames)) . '">';
        $html .= '<div class="field-header">';
        $html .= '<button type="button" class="btn btn-sm btn-success add-item-btn"><i class="icon-plus"></i> Add Row</button>';
        $html .= '</div>';
        $html .= '<div class="items-list">';

        // Render initial/default rows
        $defaultItems = [];
        for ($i = 0; $i < 2; $i++) { // 2 default rows
            $row = [];
            foreach ($this->fieldNames as $name) {
                $row[$name] = "Sample: " . $name;
            }
            $defaultItems[] = $row;
        }
        
        foreach ($defaultItems as $i => $item) {
            $savedItem = $value[$i] ?? $item;
            $html .= $this->renderItemRow($i, $savedItem);
        }

        $html .= '</div>';
        $html .= '<input type="hidden" name="' . $this->name . '" class="items-json" value="' . htmlspecialchars(json_encode($value)) . '">';
        $html .= '</div>';

        return $html;
    }

    private function renderItemRow($index, $itemData = [])
    {
        $html = '<div class="item-row" data-index="' . $index . '">
                    <span class="handle">☰</span>
                    <div class="inputs">';
        
        foreach ($this->fieldNames as $f => $name) {
            $val = $itemData[$name] ?? '';
            $html .= '<input type="text" class="field-input field-' . ($f+1) . '" data-fieldname="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($val) . '" placeholder="' . htmlspecialchars($name) . '">';
        }
        
        $html .= '    </div>
                    <button type="button" class="btn btn-sm btn-danger remove-item">×</button>
                </div>';
        return $html;
    }
}