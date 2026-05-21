<?php
/*
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */

/**
 * @package     Ramblers\Component\Ra_library
 * @subpackage  Administrator\Field
 * @copyright   Copyright (C) 2026 Your Name. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\HTML\HTMLHelper;

class TableitemsField extends TextField {

    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'tableitems';
    protected $fieldsPerItem;
    protected $fieldNames;
    protected $hints;
    protected $classes;
    protected $widths;
    protected $inputTypes;
    protected $filters;  // NEW: Per-field filters

    protected function getInput() {
        if (!is_array($this->value)) {
            $value = [];
        } else {
            $value = $this->value;
        }

        $this->fieldsPerItem = (int) ($this->element['fieldsperitem'] ?? 2);
        if ($this->fieldsPerItem < 1 || $this->fieldsPerItem > 3) {
            $this->fieldsPerItem = 2;
        }

        $fieldNamesJson = (string) ($this->element['fieldnames'] ?? '');
        $this->fieldNames = $fieldNamesJson ? json_decode($fieldNamesJson, true) : [];
        if (count($this->fieldNames) !== $this->fieldsPerItem) {
            $this->fieldNames = array_map(fn($i) => "Field " . ($i + 1), range(0, $this->fieldsPerItem - 1));
        }

        $hintsJson = (string) ($this->element['hints'] ?? '');
        $this->hints = $hintsJson ? json_decode($hintsJson, true) : $this->fieldNames;
        if (count($this->hints) !== $this->fieldsPerItem) {
            $this->hints = $this->fieldNames;
        }

        // Existing attributes
        $classesJson = (string) ($this->element['classes'] ?? '');
        $this->classes = $classesJson ? json_decode($classesJson, true) : [];
        if (count($this->classes) !== $this->fieldsPerItem) {
            $this->classes = array_fill(0, $this->fieldsPerItem, '');
        }

        $widthsJson = (string) ($this->element['widths'] ?? '');
        $this->widths = $widthsJson ? json_decode($widthsJson, true) : [];
        if (count($this->widths) !== $this->fieldsPerItem) {
            $this->widths = array_fill(0, $this->fieldsPerItem, '');
        }

        $inputTypesJson = (string) ($this->element['inputtypes'] ?? '');
        $this->inputTypes = $inputTypesJson ? json_decode($inputTypesJson, true) : [];
        if (count($this->inputTypes) !== $this->fieldsPerItem) {
            $this->inputTypes = array_fill(0, $this->fieldsPerItem, 'text');
        }

        // NEW: filters attribute
        $filtersJson = (string) ($this->element['filters'] ?? '');
        $this->filters = $filtersJson ? json_decode($filtersJson, true) : [];
        if (count($this->filters) !== $this->fieldsPerItem) {
            $this->filters = array_fill(0, $this->fieldsPerItem, 'normal');
        }

        $sort = filter_var((string) ($this->element['sort'] ?? 'true'), FILTER_VALIDATE_BOOLEAN);

        HTMLHelper::_('script', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js');
        HTMLHelper::_('script', 'com_ra_library/fields/tableitems.js', ['version' => 'auto', 'relative' => true]);
        HTMLHelper::_('stylesheet', 'com_ra_library/fields/tableitems.css', ['version' => 'auto', 'relative' => true]);

        $html = '<div id="' . $this->id . '" class="table-items-container"';
        $html .= ' data-name="' . $this->name . '"';
        $html .= ' data-fields="' . $this->fieldsPerItem . '"';
        $html .= ' data-sort="' . ($sort ? 'true' : 'false') . '"';
        $html .= ' data-fieldnames="' . htmlspecialchars(json_encode($this->fieldNames), ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' data-hints="' . htmlspecialchars(json_encode($this->hints), ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' data-classes="' . htmlspecialchars(json_encode($this->classes), ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' data-widths="' . htmlspecialchars(json_encode($this->widths), ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' data-inputtypes="' . htmlspecialchars(json_encode($this->inputTypes), ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' data-filters="' . htmlspecialchars(json_encode($this->filters), ENT_QUOTES, 'UTF-8') . '">';  // NEW
        $html .= '<div class="field-header">';
        $html .= '<button type="button" class="btn btn-sm btn-success add-item-btn"><i class="icon-plus"></i> Add Row</button>';
        $html .= '</div>';
        $html .= '<div class="items-list">';

        foreach ($value as $i => $item) {
            $html .= $this->renderItemRow($i, $item);
        }

        $html .= '</div>';
        $html .= '<input type="hidden" name="' . $this->name . '" class="items-json" value="' . htmlspecialchars(json_encode($value), ENT_QUOTES, 'UTF-8') . '">';
        $html .= '</div>';

        return $html;
    }

    private function renderItemRow($index, $itemData = []) {
        $html = '<div class="item-row" data-index="' . $index . '">
                    <span class="handle">☰</span>
                    <div class="inputs">';

        foreach ($this->fieldNames as $f => $name) {
            $hint = $this->hints[$f] ?? $name;
            $val = (string) ($itemData[$name] ?? '');
            $inputType = $this->inputTypes[$f] ?? 'text';
            $fieldClass = trim($this->classes[$f] ?? '');
            $fieldClass = $fieldClass ? ' ' . htmlspecialchars($fieldClass) : '';
            $style = $this->widths[$f] ? ' style="width: ' . htmlspecialchars($this->widths[$f]) . ';"' : '';
            $filter = $this->filters[$f] ?? 'normal';
            $isRaw = ($filter === 'raw');

            if ($isRaw) {
                // NEW: Textarea for raw HTML
                $html .= '<textarea class="field-input field-' . ($f + 1) . $fieldClass . '" '
                    . 'data-fieldname="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" '
                    . 'placeholder="' . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') . '"' . $style . '>'
                    . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '</textarea>';
            } else {
                // Existing input
                $html .= '<input type="' . htmlspecialchars($inputType, ENT_QUOTES, 'UTF-8') . '" '
                    . 'class="field-input field-' . ($f + 1) . $fieldClass . '" '
                    . 'data-fieldname="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" '
                    . 'value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '" '
                    . 'placeholder="' . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') . '"' . $style . '>';
            }
        }

        $html .= '</div>
              <button type="button" class="btn btn-sm btn-danger remove-item">×</button>
              </div>';
        return $html;
    }
}
?>