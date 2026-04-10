<?php

/**
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */

namespace Ramblers\Component\Ra_library\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\CheckboxesField;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Form\FormHelper;

class GroupSelectField extends CheckboxesField {

    protected $type = 'GroupSelect';

    public function setup(\SimpleXMLElement $element, $value, $group = null) {
        if (parent::setup($element, $value, $group) === false) {
            return false;
        }

        // Enable showon support
        if (isset($element['showon'])) {
            $this->showon = (string) $element['showon'];
        }

        return true;
    }

    protected function getOptions() {
        $url = 'https://groups.theramblers.org.uk/';
        $cacheLifetime = (int) $this->element['cache_lifetime'] ?: 3600;

        $cacheGroup = 'groups_' . md5($url);
        $cache = Factory::getCache($cacheGroup, 'callback');
        $cache->setLifeTime($cacheLifetime);

        $optionsData = $cache->get([$this, 'fetchJsonData'], [$url], md5($url));  // Expects public callback

        $options = parent::getOptions();
        if (is_array($optionsData)) {
            foreach ($optionsData as $item) {
                $class = match ($item['scope']) {
                    'A' => 'area',
                    'G' => 'group',
                    default => ''
                };

                $options[] = (object) [
                            'value' => $item['groupCode'],
                            'text' => $item['groupCode'] . ':  ' . $item['name'],
                            'class' => $class,
                            'inputClass' => 'group-input'
                ];
            }
        }

        return $options;
    }

    public function getInput() {
        $html = [];
        $options = $this->getOptions();
        $baseId = $this->fieldname;
        foreach ($options as $i => $option) {
            $value = htmlspecialchars($option->value, ENT_COMPAT, 'UTF-8');
            $text = htmlspecialchars($option->text, ENT_COMPAT, 'UTF-8');
            $optionClass = trim('form-check ' . ($option->class ?? ''));
            $name = $this->name;
            $id = $baseId . $i;
            if (!is_array($this->value)) {
                $this->value = [];
            }
            $checked = in_array($option->value, $this->value) ? ' checked="checked"' : '';

            $html[] = sprintf(
                    '<div class="%s">
                    <input type="checkbox" id="%s" name="%s" value="%s" class="form-check-input"%s>
                    <label for="%s" class="form-check-label">%s</label>
                </div>',
                    $optionClass, $id, $name, $value, $checked, $id, $text
            );
        }

        $checkboxesHtml = '<div id="' . $baseId . '" class="checkboxes">' . implode('', $html) . '</div>';

        $toggleHtml = '<div class="checkboxes-header mt-2">
            <button type="button" class="btn btn-outline-secondary btn-sm ramblers-toggle" data-target="' . $baseId . '">
                <i class="icon-eye"></i> Show Checked Only
            </button>
        </div>';

        $js = '
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const btn = document.querySelector("[data-target=\'' . addslashes($baseId) . '\']");
            if (!btn || !document.getElementById(\'' . addslashes($baseId) . '\')) return;
            
            let state = "checked";
            btn.addEventListener("click", function() {
                const targetId = this.dataset.target;
                const container = document.getElementById(targetId);
                const checks = container.querySelectorAll(".form-check");
                const isCheckedOnly = state === "checked";
                
                checks.forEach(div => {
                    const input = div.querySelector("input[type=checkbox]");
                    if (input) {
                        const shouldShow = !isCheckedOnly || input.checked;
                        div.style.display = shouldShow ? "block" : "none";
                        div.style.opacity = shouldShow ? "1" : "0.5";
                    }
                });
                
                state = isCheckedOnly ? "all" : "checked";
                this.innerHTML = state === "checked" 
                    ? \'<i class="icon-eye"></i> Show Checked Only\' 
                    : \'<i class="icon-list"></i> Show All\';
                this.title = state === "checked" ? "Hide unchecked" : "Show all";
            });
        });
        </script>';

        return $checkboxesHtml . $js;
    }

    // MUST be public for cache callback
    public function fetchJsonData($url) {
        try {
            $http = HttpFactory::getHttp();
            $response = $http->get($url);
            if ($response->code === 200) {
                $data = json_decode($response->body, true);
                return is_array($data) ? $data : [];
            }
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage('Group JSON fetch failed: ' . $e->getMessage(), 'warning');
        }
        return [];
    }

    public function renderField($canAccess = true) {
        if (!$canAccess) {
            return '';
        }

        $class = $this->element['class'] ? ' ' . (string) $this->element['class'] : '';
        $wrapperClass = trim('control-group ramblers-group-select' . $class);

        // CORRECT Joomla parser for JSON data-showon
        $dataShowon = '';
        if ($this->showon) {
            $showon = \Joomla\CMS\Form\FormHelper::parseShowOnConditions(
                    $this->showon,
                    $this->formControl, // 'jform'
                    $this->group         // 'options.ramblersgroups' or similar
            );
            $dataShowon = ' data-showon=\'' . json_encode($showon) . '\'';
        }

        $html = [];
        $html[] = '<div class="' . $wrapperClass . '"' . $dataShowon . '>';

        // control-label wrapper
        $html[] = '<div class="control-label">' . $this->getLabel() . '</div>';

        $html[] = '<div class="controls">';

        // Toggle button
        $baseId = $this->fieldname;
        $html[] = '<div class="checkboxes-header mt-2">';
        $html[] = '<button type="button" class="btn btn-outline-secondary btn-sm ramblers-toggle" data-target="' . $baseId . '">';
        $html[] = '<i class="icon-eye"></i> Show Checked Only';
        $html[] = '</button></div>';

        $html[] = $this->getInput();
        $html[] = '</div></div>';  // </controls> </control-group>

        return implode("\n", $html);
    }
}
