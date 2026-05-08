<?php

namespace Ramblers\Component\Ra_library\Administrator\Field;

/**
 * @package     Joomla.Administrator
 * @subpackage  Form.Field
 *
 * Custom Nested Grouped List Field for Joomla 5
 * Supports unlimited nesting via recursive XML <group> tags.
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

class NestedgroupedlistField extends FormField {

    protected $type = 'Nestedgroupedlist';

    /**
     * Parse the XML element and return nested groups recursively.
     *
     * @return  array  Nested array: ['Group Label' => ['Subgroup Label' => [['value' => '...', 'text' => '...']], ...]]
     */
    protected function getGroups() {
        $groups = [];

        if (!($this->element instanceof \SimpleXMLElement) || !count($this->element->children())) {
            return [];
        }

        foreach ($this->element->children() as $option) {
            if ($option->getName() !== 'group') {
                continue;
            }

            $label = trim((string) $option['label']);
            if (!$label) {
                $label = $this->translateLabel ? Text::_((string) $option['default']) : (string) $option['default'];
            }

            $subGroups = $this->parseGroup($option);

            if (!empty($subGroups)) {
                $groups[$label] = $subGroups;
            }
        }

        return $groups;
    }

    /**
     * Recursively parse a group element.
     *
     * @param   \SimpleXMLElement  $groupElement  The group XML node.
     *
     * @return  array|array[]  Nested array OR flat options array
     */
    private function parseGroup($groupElement) {
        $subGroups = [];
        $directOptions = [];

        foreach ($groupElement->children() as $child) {
            $childName = $child->getName();

            if ($childName === 'group') {
                // Nested subgroup
                $subLabel = trim((string) $child['label']);
                if (!$subLabel) {
                    $subLabel = $this->translateLabel ? Text::_((string) $child['default']) : (string) $child['default'];
                }

                $subResult = $this->parseGroup($child);
                if (!empty($subResult)) {
                    $subGroups[$subLabel] = $subResult;
                }
            } elseif ($childName === 'option') {
                // **FIXED**: Always collect direct options (no hidemain check here)
                $directOptions[] = $this->parseOption($child);
            }
        }

        // **FIXED LOGIC**:
        if (!empty($subGroups)) {
            // Has subgroups: add direct options as leaf under separator if any
            if (!empty($directOptions)) {
                $subGroups[Text::_('JOPTION_USE_DEFAULT')] = $directOptions;  // Or '---'
            }
            return $subGroups;
        }

        // No subgroups: return direct options as flat array (leaf level)
        if (!empty($directOptions)) {
            return $directOptions;
        }

        return [];  // Empty
    }

    /**
     * Parse a single option.
     *
     * @param   \SimpleXMLElement  $option  The option XML.
     *
     * @return  array
     */
    private function parseOption($option) {
        $value = (string) $option['value'];
        $text = trim((string) $option);
        if ($text == '') {
            $text = $value;
        }

        $text = $this->translateLabel ? Text::_($text) : $text;

        return ['value' => $value, 'text' => $text];
    }

    /**
     * Get the HTML for the field input.
     */

    /**
     * Get the HTML for the field input.
     */
    public function getInput() {
        $groups = $this->getGroups();

        if (empty($groups)) {
            return '';
        }

        // Your existing attributes code (unchanged)...
        $arr = (array) $this->element->attributes();
        // ... (keep all your attr building code exactly as-is until $attr)
        $classes = 'form-control';
        $selectAttr = [
            'id' => $this->id,
            'name' => $this->name . ($this->multiple ? '[]' : ''),
            'class' => trim('inputbox' . (isset($arr['class']) ? ' ' . $arr['class'] : '') . (trim($classes ?? '') ? ' ' . $classes : '')),
                // $attr  // Append other attrs
        ];

        $html = '<select' . $this->arrayToHtmlAttributes($selectAttr) . '>';

        // FIXED: Single recursive call
        $html .= $this->renderGroupStructure($groups, $this->value);

        $html .= '</select>';

        return $html;
    }

    /**
     * Render full nested group structure recursively WITH level classes.
     */
    private function renderGroupStructure($structure, $selected = null, $level = 0) {
        $html = '';

        foreach ($structure as $label => $content) {
            $groupClass = 'level-' . $level . ' group-level-' . $level;
            $optClassBase = 'level-' . $level;

            if (is_array($content) && isset($content[0]['value'])) {
                // LEAF: array of options -> <optgroup class="level-X">
                $html .= '<optgroup label="' . htmlspecialchars($label, ENT_COMPAT) . '" class="' . $groupClass . '">';
                foreach ($content as $option) {
                    $sel = (is_array($selected) ? in_array($option['value'], $selected) : $option['value'] == $selected) ? ' selected="selected"' : '';
                    $optionClass = $optClassBase . ' option-level-' . $level;
                    $html .= '<option value="' . htmlspecialchars($option['value'], ENT_COMPAT) .
                            '" class="' . $optionClass . '"' . $sel . '>' .
                            htmlspecialchars($option['text']) . '</option>';
                }
                $html .= '</optgroup>';
            } else {
                // NODE: nested groups -> <optgroup class="level-X"> + recurse
                $html .= '<optgroup label="' . htmlspecialchars($label, ENT_COMPAT) . '" class="' . $groupClass . '">';
                $html .= $this->renderGroupStructure($content, $selected, $level + 1);
                $html .= '</optgroup>';
            }
        }

        return $html;
    }

    /**
     * Convert array to HTML attributes string.
     */
    private function arrayToHtmlAttributes($attributes) {
        $html = '';
        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                continue;  // Skip complex
            }
            $html .= ' ' . $key . '="' . htmlspecialchars((string) $value, ENT_COMPAT, 'UTF-8') . '"';
        }
        return $html;
    }

    /**
     * Method to get the field label.
     */
    public function getLabel() {
        return parent::getLabel();
    }
}
