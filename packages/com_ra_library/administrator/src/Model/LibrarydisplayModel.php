<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\MVC\Model\AdminModel;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\CMS\Event\Model;
use Joomla\CMS\Event\AbstractEvent;
use \Joomla\Database\DatabaseInterface;
use Ramblers\Component\Ra_library\Administrator\Field\GroupSelectField;

/**
 * Librarydisplay model.
 *
 * @since  1.0.0
 */
class LibrarydisplayModel extends AdminModel {

    /**
     * @var    string  The prefix to use with controller messages.
     *
     * @since  1.0.0
     */
    protected $text_prefix = 'COM_RA_LIBRARY';

    /**
     * @var    string  Alias to manage history control
     *
     * @since  1.0.0
     */
    public $typeAlias = 'com_ra_library.librarydisplay';

    /**
     * @var    null  Item data
     *
     * @since  1.0.0
     */
    protected $item = null;

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   string  $type    The table type to instantiate
     * @param   string  $prefix  A prefix for the table class name. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  Table    A database object
     *
     * @since   1.0.0
     */
    public function getTable($type = 'Librarydisplay', $prefix = 'Administrator', $config = array()) {
        return parent::getTable($type, $prefix, $config);
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      An optional array of data for the form to interogate.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \JForm|boolean  A \JForm object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getForm($data = array(), $loadData = true) {
        // Initialise variables.
        $app = Factory::getApplication();

        // Get the form.
        $form = $this->loadForm(
                'com_ra_library.librarydisplay',
                'librarydisplay',
                array(
                    'control' => 'jform',
                    'load_data' => $loadData
                )
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.0.0
     */
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_ra_library.edit.librarydisplay.data', []);

        if (empty($data)) {
            $item = $this->getItem();
            $data = $this->preloadFormData($item);
        }

        return $data;
    }

    /**
     * Method to get a single record.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  mixed    Object on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItem($pk = null) {

        if ($item = parent::getItem($pk)) {
            if (isset($item->params)) {
                $item->params = json_encode($item->params);
            }

            // Do any procesing on fields here if needed
            if ($item && $item->options) {
                $newOptions = json_decode($item->options);
                if ($newOptions->ramblersgroups) {
                    // remove group names, convert to array of group codes
                    $newOptions->ramblersgroups = array_column($newOptions->ramblersgroups, 'code');
                    $item->options = json_encode($newOptions);
                }
            }
        }

        return $item;
    }

    /**
     * Method to duplicate an Librarydisplay
     *
     * @param   array  &$pks  An array of primary key IDs.
     *
     * @return  boolean  True if successful.
     *
     * @throws  Exception
     */
    public function duplicate(&$pks) {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $dispatcher = $this->getDispatcher();

        // Access checks.
        if (!$user->authorise('core.create', 'com_ra_library')) {
            throw new \Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
        }

        $context = $this->option . '.' . $this->name;

        // Include the plugins for the save events.
        PluginHelper::importPlugin($this->events_map['save']);

        $table = $this->getTable();

        foreach ($pks as $pk) {

            if ($table->load($pk, true)) {
                // Reset the id to create a new record.
                $table->id = 0;

                if (!$table->check()) {
                    throw new \Exception($table->getError());
                }


                // Create the before save event.
                $beforeSaveEvent = AbstractEvent::create(
                        $this->event_before_save,
                        [
                            'context' => $context,
                            'subject' => $table,
                            'isNew' => true,
                            'data' => $table,
                        ]
                );

                // Trigger the before save event.
                $dispatchResult = Factory::getApplication()->getDispatcher()->dispatch($this->event_before_save, $beforeSaveEvent);

                // Check if dispatch result is an array and handle accordingly
                $result = isset($dispatchResult['result']) ? $dispatchResult['result'] : [];

                // Proceed with your logic
                if (in_array(false, $result, true) || !$table->store()) {
                    throw new \Exception($table->getError());
                }

                // Trigger the after save event.
                Factory::getApplication()->getDispatcher()->dispatch(
                        $this->event_after_save,
                        AbstractEvent::create(
                                $this->event_after_save,
                                [
                                    'context' => $context,
                                    'subject' => $table,
                                    'isNew' => true,
                                    'data' => $table,
                                ]
                        )
                );
            } else {
                throw new \Exception($table->getError());
            }
        }

        // Clean cache
        $this->cleanCache();

        return true;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   Table  $table  Table Object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    #[\Override]
    protected function prepareTable($table) {
        jimport('joomla.filter.output');

        if (empty($table->id)) {
            if (@$table->ordering === '') {
                $db = Factory::getContainer()->get(DatabaseInterface::class);
                $db->setQuery('SELECT MAX(ordering) FROM #__ra_library_displays');
                $max = $db->loadResult();
                $table->ordering = $max + 1;
            }
        }

        // FIXED: Direct JSON fetch - no field instantiation
        $jinput = Factory::getApplication()->input;
        $data = $jinput->get('jform', [], 'array');

        // add group names to options->ramblersgroups
        if (isset($data['options']['ramblersgroups'])) {
            $codes = $data['options']['ramblersgroups'];

            // YOUR exact fetchJsonData logic (cached!)
            $url = 'https://groups.theramblers.org.uk/';
            $cacheGroup = 'groups_' . md5($url);
            $cache = Factory::getCache($cacheGroup, 'callback');
            $cache->setLifeTime(3600);
            $optionsData = $cache->get(function ($url) {
                $http = \Joomla\CMS\Http\HttpFactory::getHttp();
                $response = $http->get($url);
                if ($response->code === 200) {
                    return json_decode($response->body, true) ?: [];
                }
                return [];
            }, [$url], md5($url));

            $options = [];
            if (is_array($optionsData)) {
                foreach ($optionsData as $item) {
                    $options[] = (object) [
                                'value' => $item['groupCode'],
                                'text' => $item['name']
                    ];
                }
            }

            $selected = [];
            foreach ((array) $codes as $code) {
                foreach ($options as $option) {
                    if ($option->value === $code) {
                        $selected[] = [
                            'code' => $code,
                            'name' => $option->text
                        ];
                        break;
                    }
                }
            }
            $newOptions = json_decode($table->options);
            $newOptions->ramblersgroups = $selected;
            $table->options = json_encode($newOptions);
        }

        parent::prepareTable($table);
    }

    protected function preloadFormData($item) {
        $data = [];
        if ($item) {
            $data['id'] = $item->id;
            $data['title'] = $item->title;
            $data['options'] = json_decode($item->options, true) ?: [];
            $data['table_items'] = json_decode($item->table_items, true) ?: [];
        }
        return $data;
    }
}
