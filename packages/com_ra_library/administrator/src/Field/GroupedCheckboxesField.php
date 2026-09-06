<?php

namespace Ramblers\Component\Ra_library\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\GroupedlistField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

class GroupedCheckboxesField extends GroupedlistField {

    protected $type = 'GroupedCheckboxes';

    public function getInput() {
        $html = [];
        $groups = $this->getGroups();

        // NOTE: Joomla's FormField::getName() (called from setup(), before
        // getInput() ever runs) already appends "[]" to $this->name when the
        // field's "multiple" attribute is true - that's how core fields like
        // checkboxes/list-multiple get their array-style POST name. Appending
        // "[]" again here produced a name such as
        // "jform[filters][0][flags][][]". With that doubled bracket, PHP
        // parses every checked box into its own one-element sub-array
        // (e.g. [['refresh'], ['toilet']]) instead of a flat list
        // (['refresh', 'toilet']). That malformed shape is what got written
        // into options.filters[n].flags, and is why the boxes never showed
        // as ticked again on edit - the saved value could never match a
        // plain option value.
        $name = $this->name;
        $class = $this->element['class'] ? (string) $this->element['class'] : 'checkboxes';

        // Accepts the normal flat array of codes, but also tolerates a
        // JSON-encoded string and the malformed nested-array shape saved by
        // the bug above, so previously-saved rows display correctly too.
        $selected = $this->normaliseSelected($this->value);

        foreach ($groups as $groupLabel => $options) {
            if ($groupLabel !== '') {
                $html[] = '<fieldset class="checkboxes-group">';
                $html[] = '<div><b>' . Text::_($groupLabel) . '</b></div>';  // ✅ Translate group label
            }

            foreach ($options as $option) {
                $value = htmlspecialchars((string) $option->value, ENT_QUOTES, 'UTF-8');
                $text = Text::_($option->text);  // ✅ Translate + output (safe for text content)
                $checked = in_array((string) $option->value, $selected, true) ? ' checked="checked"' : '';

                $html[] = '<label class="checkbox" style="margin-right:20px;">';
                $html[] = '<input type="checkbox" name="' . $name . '" value="' . $value . '"' . $checked . ' class="' . $class . '" />';
                $html[] = ' ' . $text;
                $html[] = '</label>';
            }

            if ($groupLabel !== '') {
                $html[] = '</fieldset>';
            }
        }

        return implode("\n", $html);
    }

    /**
     * Flatten a bound field value into a plain array of string codes.
     *
     * Handles the normal case (an array of scalar codes), a JSON-encoded
     * string, and the "[['refresh'],['toilet']]" shape produced by rows
     * saved before the doubled "[]" name bug was fixed.
     *
     * @param   mixed  $value
     *
     * @return  string[]
     */
    private function normaliseSelected($value): array {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : array_filter(explode(',', $value));
        } elseif ($value instanceof \Traversable) {
            $value = iterator_to_array($value);
        } elseif (is_object($value)) {
            $value = (array) $value;
        }

        if (!is_array($value)) {
            return ($value === null || $value === '') ? [] : [(string) $value];
        }

        $flat = [];
        array_walk_recursive($value, function ($v) use (&$flat) {
            $flat[] = (string) $v;
        });

        return $flat;
    }
}
