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
        $name = $this->name . ($this->multiple ? '[]' : '');
        $class = $this->element['class'] ? (string) $this->element['class'] : 'checkboxes';

        foreach ($groups as $groupLabel => $options) {
            if ($groupLabel !== '') {
                $html[] = '<fieldset class="checkboxes-group">';
                $html[] = '<legend>' . Text::_($groupLabel) . '</legend>';  // ✅ Translate group label
            }

            foreach ($options as $option) {
                $value = htmlspecialchars((string) $option->value, ENT_QUOTES, 'UTF-8');
                $text = Text::_($option->text);  // ✅ Translate + output (safe for text content)
                $checked = in_array((string) $option->value, (array) $this->value) ? ' checked="checked"' : '';

                $html[] = '<label class="checkbox">';
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
}
