<?php

namespace Ramblers\Component\Ra_library\Administrator\Field;

/**
 * Nested Panels custom form field for Joomla 5/6.
 *
 * Renders a "drill down" selector: top-level groups are shown as
 * image + label panels; clicking one reveals its children (more
 * groups, or the final options) until a leaf option is chosen.
 * The chosen leaf value is what gets saved, same as a normal list field.
 *
 * Install:
 *  - Copy this file to your component's fields/ folder
 *    (e.g. administrator/components/com_ra_library/models/fields/nestedpanels.php)
 *  - Make sure that path is registered, e.g. in your form XML:
 *      <fields addfieldpath="/administrator/components/com_ra_library/models/fields">
 *  - Copy media/js/nestedpanels.js and media/css/nestedpanels.css into
 *    your component's media folder and adjust the asset paths/names below
 *    (or register them properly via a media/joomla.asset.json file).
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

class NestedPanelsField extends FormField {

    protected $type = 'NestedPanels';

    protected function getInput() {
        // Adjust these two asset paths/names to match where you put the JS/CSS
        // in your own extension (ideally register them in a joomla.asset.json
        // file and reference by asset name instead of raw path).
        $doc = Factory::getApplication()->getDocument();
        $wa = $doc->getWebAssetManager();

        $wa->registerAndUseStyle(
                'fields.nestedpanels',
                'media/com_ra_library/css/fields/nestedpanels.css'
        );
        $wa->registerAndUseScript(
                'fields.nestedpanels',
                'media/com_ra_library/js/fields/nestedpanels.js',
                [],
                ['defer' => true]
        );

        $tree = $this->buildTree($this->element);
        $fieldId = $this->id;
        $fieldName = $this->name;
        $value = (string) $this->value;

        $html = [];
        $html[] = '<div class="nestedpanels-field" data-field-id="' . htmlspecialchars($fieldId, ENT_QUOTES) . '">';
        $html[] = '<input type="hidden" id="' . htmlspecialchars($fieldId, ENT_QUOTES) . '" name="' . htmlspecialchars($fieldName, ENT_QUOTES) . '" value="' . htmlspecialchars($value, ENT_QUOTES) . '">';
        $html[] = '<div class="nestedpanels-breadcrumb"></div>';
        $html[] = '<div class="nestedpanels-levels"></div>';
        $html[] = '<script type="application/json" class="nestedpanels-data">' . json_encode($tree) . '</script>';
        $html[] = '</div>';

        return implode('', $html);
    }

    /**
     * Recursively converts <group> / <option> XML nodes under $element
     * into a nested array: groups have children, options are leaves.
     */
    protected function buildTree(\SimpleXMLElement $element): array {
        $nodes = [];

        foreach ($element->children() as $child) {
            $tag = $child->getName();

            if ($tag === 'group') {
                // Groups are just navigation levels, not selectable values,
                // so no 'value' is read or stored here.
                $nodes[] = [
                    'type' => 'group',
                    'label' => Text::_((string) $child['label']),
                    'image' => (string) ($child['image'] ?? ''),
                    'children' => $this->buildTree($child),
                ];
            } elseif ($tag === 'option') {
                $nodes[] = [
                    'type' => 'option',
                    'value' => (string) $child['value'],
                    'label' => Text::_(trim((string) $child)),
                    'image' => (string) ($child['image'] ?? ''),
                ];
            }
        }

        return $nodes;
    }
}
