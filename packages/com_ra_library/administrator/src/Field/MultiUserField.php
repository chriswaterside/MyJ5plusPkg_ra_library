<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan  <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Ruby Tuesday
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\Field;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\ParameterType;

/**
 * MultiUser Field
 *
 * Supports multiple selection and user group filtering
 *
 * @since  1.0.0
 */
class MultiUserField extends FormField {

    /**
     * Form field type
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'MultiUser';

    public function renderField($options = []) {
        $canAccess = ($options['canAccess'] ?? true);

        if (!$canAccess) {
            return '';
        }

        $class = $this->element['class'] ? ' ' . (string) $this->element['class'] : '';
        $wrapperClass = trim('control-group ramblers-group-select' . $class);

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

        $html[] = $this->getInput();
        $html[] = '</div></div>';  // </controls> </control-group>

        return implode("\n", $html);
    }

    /**
     * Get the form field input markup
     *
     * @return  string  The field input markup
     *
     * @since   1.0.0
     */
    protected function getInput(): string {

        $multiple = ((string) $this->element['multiple'] === 'true');
        $userGroup = (string) $this->element['userGroup'];
        $showGroupSelector = ((string) $this->element['groupselector'] !== 'false');
        HTMLHelper::_('script', 'com_ra_library/fields/multiuser-group-filter.js', ['version' => 'auto', 'relative' => true]);
        HTMLHelper::_('stylesheet', 'com_ra_library/fields/multiuser-group-filter.css', ['version' => 'auto', 'relative' => true]);

        $options = $this->getOptions(''); // All users
        $groupOptions = $this->getUserGroups();
        $selectedValues = $multiple ? (array) $this->value : [(string) $this->value];

        // Group selector for BOTH single and multiple
        if ($showGroupSelector) {
            return $this->getWithGroupSelector($options, $groupOptions, $selectedValues, $multiple);
        }

        if ($multiple) {
            return $this->getMultipleCheckboxes($options, $selectedValues);
        }

        return $this->getSingleSelect($options, $selectedValues, 0);
    }

    /**
     * Render field WITH group selector (works for both single and multiple)
     */
    protected function getWithGroupSelector(array $options, array $groupOptions, array $selectedValues, bool $multiple): string {
        $html = '<div class="multiuser-wrapper mb-3">';

        // Group selector dropdown
        $selectedGroupId = $this->getXmlGroupId();
        $html .= '<div class="row mb-3">';
        $html .= '<div class="col-12 col-md-4">';
        $html .= '<label class="form-label">Filter by Group:</label>';
        $html .= '<select class="form-select group-filter" data-target="multiuser">';
        foreach ($groupOptions as $group) {
            $selected = ($group->id == $selectedGroupId) ? ' selected' : '';
            $html .= sprintf(
                    '<option value="%d"%s>%s</option>',
                    (int) $group->id,
                    $selected,
                    htmlspecialchars($group->text, ENT_QUOTES, 'UTF-8')
            );
        }
        $html .= '</select>';
        $html .= '</div></div>';

        // Users list
        $html .= '<div class="row g-2 multiuser-multiple" data-group-filter="multiuser">';
        foreach ($options as $option) {
            $groupIds = $this->getUserGroupsForUser($option->value);
            $dataGroups = ' data-user-groups="' . htmlspecialchars(json_encode($groupIds), ENT_QUOTES, 'UTF-8') . '"';
            $checked = in_array((string) $option->value, $selectedValues, true) ? ' checked' : '';

            $html .= '<div class="col-sm-6 col-md-4 user-checkbox" data-group-filter="multiuser"' . $dataGroups . '>';

            if ($multiple) {
                // Checkbox
                $html .= '<div class="form-check">';
                $html .= sprintf(
                        '<input class="form-check-input" type="checkbox" id="%s" name="%s[]" value="%d"%s>',
                        $this->getId($option->value),
                        $this->name,
                        (int) $option->value,
                        $checked
                );
                $html .= sprintf(
                        '<label class="form-check-label" for="%s">%s</label>',
                        $this->getId($option->value),
                        htmlspecialchars($option->text, ENT_QUOTES, 'UTF-8')
                );
                $html .= '</div>';
            } else {
                // Radio button for single select
                $html .= '<div class="form-check">';
                $html .= sprintf(
                        '<input class="form-check-input" type="radio" id="%s" name="%s" value="%d"%s>',
                        $this->getId($option->value),
                        $this->name,
                        (int) $option->value,
                        $checked
                );
                $html .= sprintf(
                        '<label class="form-check-label" for="%s">%s</label>',
                        $this->getId($option->value),
                        htmlspecialchars($option->text, ENT_QUOTES, 'UTF-8')
                );
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Get XML group ID (helper)
     */
    protected function getXmlGroupId(): int {
        $userGroup = (string) $this->element['userGroup'];
        return $userGroup ? $this->getGroupIdFromTitle($userGroup) : 0;
    }

    /**
     * Get user groups for dropdown
     *
     * @return  array
     */

    /**
     * Get user groups for dropdown (excluding Public and Guest)
     *
     * @return  array
     */
    protected function getUserGroups(): array {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('id'),
                    $db->quoteName('title') . ' AS ' . $db->quoteName('text')
                ])
                ->from($db->quoteName('#__usergroups'))
                ->where($db->quoteName('title') . ' NOT IN (' . implode(',', $db->quote(['Public', 'Guest'])) . ')')
                ->order($db->quoteName('lft') . ' ASC');

        $db->setQuery($query);
        $groups = $db->loadObjectList();

        array_unshift($groups, (object) ['id' => 0, 'text' => 'All Groups']);
        return $groups;
    }

    /**
     * Get group ID from title
     */
    protected function getGroupIdFromTitle(string $title): int {
        if (empty($title))
            return 0;

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__usergroups'))
                ->where($db->quoteName('title') . ' = :title')
                ->bind(':title', $title, ParameterType::STRING);

        $db->setQuery($query);
        return (int) $db->loadResult();
    }

    /**
     * Get groups for specific user - DB query (not Factory::getUser)
     */
    protected function getUserGroupsForUser(int $userId): array {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
                ->select($db->quoteName('group_id'))
                ->from($db->quoteName('#__user_usergroup_map'))
                ->where($db->quoteName('user_id') . ' = :userId')
                ->bind(':userId', $userId, ParameterType::INTEGER);

        $db->setQuery($query);
        return $db->loadColumn() ?: [];
    }

    /**
     * Render multiple checkboxes
     *
     * @param   array    $options        Options list
     * @param   array    $selectedValues Selected values
     *
     * @return  string
     *
     * @since   1.0.0
     */
    protected function getMultipleCheckboxes(array $options, array $selectedValues): string {
        $html = '<div class="row g-2 MultiUserField-multiple">';
        foreach ($options as $option) {
            $checked = in_array((string) $option->value, $selectedValues, true) ? ' checked' : '';
            $html .= '<div class="col-sm-6 col-md-4">';
            $html .= '<div class="form-check">';
            $html .= sprintf(
                    '<input class="form-check-input" type="checkbox" id="%s" name="%s[]" value="%d"%s>',
                    $this->getId($option->value),
                    $this->name,
                    (int) $option->value,
                    $checked
            );
            $html .= sprintf(
                    '<label class="form-check-label" for="%s">%s</label>',
                    $this->getId($option->value),
                    htmlspecialchars($option->text, ENT_QUOTES, 'UTF-8')
            );
            $html .= '</div></div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Render single select dropdown
     *
     * @param   array  $options      Options list
     * @param   array  $selected     Selected values
     * @param   int    $defaultId    Default contact ID
     *
     * @return  string
     *
     * @since   1.0.0
     */
    protected function getSingleSelect(array $options, array $selected): string {
        $html = [];
        $html[] = '<div class="mb-3">';
        $html[] = '<select class="form-select" name="' . $this->name . '" id="' . $this->id . '">';

        foreach ($options as $option) {
            $selectedAttr = in_array((string) $option->value, $selected, true) ? ' selected="selected"' : '';
            $html[] = sprintf(
                    '<option value="%d"%s>%s</option>',
                    (int) $option->value,
                    $selectedAttr,
                    htmlspecialchars($option->text, ENT_QUOTES, 'UTF-8')
            );
        }

        $html[] = '</select>';
        $html[] = '</div>';

        return implode("\n", $html);
    }

    /**
     * Get filtered user options
     *
     * @param   string  $userGroup  User group title to filter by
     *
     * @return  array
     *
     * @since   1.0.0
     */
    protected function getOptions(string $userGroup = ''): array {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
                ->select($db->quoteName(['id', 'name', 'username']))
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('block') . ' = 0')
                ->order($db->quoteName('name') . ' ASC');

        $db->setQuery($query);
        $users = $db->loadObjectList();

        $options = [];
        foreach ($users as $user) {
            $options[] = (object) [
                        'value' => $user->id,
                        'text' => trim($user->name . ' (' . $user->username . ')'),
            ];
        }
        return $options;
    }

    /**
     * Check if user belongs to specified group
     *
     * @param   int     $userId     User ID
     * @param   string  $groupTitle Group title
     *
     * @return  bool
     *
     * @since   1.0.0
     */
    protected function userInGroup(int $userId, string $groupTitle): bool {
        if (empty($groupTitle)) {
            return true;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__usergroups'))
                ->where($db->quoteName('title') . ' = :groupTitle')
                ->bind(':groupTitle', $groupTitle, ParameterType::STRING);

        $db->setQuery($query);
        $groupId = (int) $db->loadResult();

        if ($groupId < 1) {
            return false;
        }

        $user = Factory::getUser($userId);

        return in_array($groupId, $user->get('groups'), true);
    }

    /**
     * Get field ID for checkbox
     *
     * @param   int|string  $value  Value
     *
     * @return  string
     *
     * @since   1.0.0
     */
    protected function getId($fieldId = '', $fieldName = ''): string {
        // Use parent first, then append checkbox value
        $id = parent::getId($fieldId, $fieldName);

        // For checkboxes, append the user ID (when called with single numeric arg)
        if (func_num_args() === 1 && is_numeric($fieldId)) {
            $id .= '_' . (int) $fieldId;
        }

        return $id;
    }
}