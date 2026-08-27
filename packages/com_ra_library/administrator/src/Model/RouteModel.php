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

use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Ramblers\Component\Ra_library\Administrator\Helper\ImageGalleryHelper;
use Ramblers\Component\Ra_library\Administrator\Helper\AttachmentHelper;
use Ramblers\Component\Ra_library\Administrator\Helper\RoutePointHelper;

/**
 * Route model.
 *
 * Routes share the ra_library_routes table and PastwalkTable class with
 * Past Walks (record_type column tells the two apart) - this model just
 * points getTable() at that same Table class and always forces
 * record_type = 'route' on save, so the Route form can never touch a
 * past-walk row or vice versa.
 *
 * @since  1.0.0
 */
class RouteModel extends AdminModel {

    /**
     * @var    string  The prefix to use with controller messages.
     *
     * @since  1.0.0
     */
    protected $text_prefix = 'COM_RA_LIBRARY';

    /**
     * @var    string  Alias to manage history/content_types mapping
     *
     * @since  1.0.0
     */
    public $typeAlias = 'com_ra_library.route';

    /**
     * Returns a reference to a Table object, always creating it.
     *
     * Deliberately instantiates 'Pastwalk' (not 'Route') - there is no
     * separate RouteTable class, since the physical table and columns are
     * identical to Past Walks. The record_type column is what keeps the two
     * apart, not a different Table class.
     *
     * @param   string  $type    The table type to instantiate
     * @param   string  $prefix  A prefix for the table class name. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  Table    A database object
     *
     * @since   1.0.0
     */
    public function getTable($type = 'Pastwalk', $prefix = 'Administrator', $config = array()) {
        return parent::getTable('Pastwalk', $prefix, $config);
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      An optional array of data for the form to interrogate.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \Joomla\CMS\Form\Form|boolean  A Form object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getForm($data = array(), $loadData = true) {
        $form = $this->loadForm(
                'com_ra_library.route',
                'route',
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
     * Method to get a single record - also attaches the image gallery rows
     * for this record, since AdminModel::getItem() only knows about the
     * parent table's own columns.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  \stdClass|false  Object on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItem($pk = null) {
        $item = parent::getItem($pk);

        if ($item && !empty($item->id)) {
            $item->images = ImageGalleryHelper::getImagesForRecord((int) $item->id);
            $item->gpx = AttachmentHelper::getAttachmentsForRecord((int) $item->id, 'gpx');
            $item->documents = AttachmentHelper::getAttachmentsForRecord((int) $item->id, 'document');
            $item->route_points = RoutePointHelper::getPointsForRecord((int) $item->id);
        }

        return $item;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.0.0
     */
    protected function loadFormData() {
        $data = Factory::getApplication()->getUserState('com_ra_library.edit.route.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Save a Route record.
     *
     * Forces record_type to 'route' regardless of what's in the submitted
     * data, so the Route form can never create/overwrite a past-walk row -
     * see PastwalkModel::save() for the mirror-image guard on that side.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success.
     *
     * @since   1.0.0
     */
    public function save($data) {
        $data['record_type'] = 'route';

        // Editing an existing record from the admin form is itself the
        // "review" the Import screen's needs_review flag exists to prompt -
        // clear it automatically so there's no separate "mark reviewed"
        // step. Leave it alone on a fresh insert, since ImportController
        // sets it explicitly there when a walk comes from Walks Manager.
        if (!empty($data['id'])) {
            $data['needs_review'] = 0;
        }

        $imagesData = $data['images'] ?? [];
        unset($data['images']);

        $gpxData = $data['gpx'] ?? [];
        unset($data['gpx']);

        $documentsData = $data['documents'] ?? [];
        unset($data['documents']);

        $routePointsData = $data['route_points'] ?? [];
        unset($data['route_points']);

        $result = parent::save($data);

        if (!$result) {
            return false;
        }

        $recordId = (int) $this->getState($this->getName() . '.id');
        $imagesResult = ImageGalleryHelper::saveImages($recordId, 'route', $imagesData);

        if ($imagesResult !== true) {
            $this->setError($imagesResult);

            return false;
        }

        $gpxResult = AttachmentHelper::saveAttachments($recordId, 'route', 'gpx', $gpxData);

        if ($gpxResult !== true) {
            $this->setError($gpxResult);

            return false;
        }

        $documentsResult = AttachmentHelper::saveAttachments($recordId, 'route', 'document', $documentsData);

        if ($documentsResult !== true) {
            $this->setError($documentsResult);

            return false;
        }

        RoutePointHelper::savePoints($recordId, $routePointsData);

        return true;
    }

    /**
     * Delete one or more routes - also removes each one's image gallery
     * (DB rows and the actual stored files) first, so a permanent delete
     * doesn't leave orphaned photos behind on disk.
     *
     * @param   array  &$pks  An array of record primary keys.
     *
     * @return  boolean  True if successful, false if an error occurs.
     *
     * @since   1.0.0
     */
    public function delete(&$pks) {
        foreach ((array) $pks as $pk) {
            ImageGalleryHelper::deleteImagesForRecord((int) $pk);
            AttachmentHelper::deleteAttachmentsForRecord((int) $pk);

            // Last - only safe to remove the shared per-record folder once
            // both of the above have removed their own files from it.
            ImageGalleryHelper::deleteRecordFolder((int) $pk, 'route');
            RoutePointHelper::deletePointsForRecord((int) $pk);
        }

        return parent::delete($pks);
    }

    /**
     * Restrict deleting a route to users with the routes.delete action.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    protected function canDelete($record) {
        if (empty($record->id)) {
            return false;
        }

        return Factory::getApplication()->getIdentity()->authorise('routes.delete', 'com_ra_library');
    }

    /**
     * Restrict publish/unpublish/archive/trash to users with the
     * routes.edit.state action.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    protected function canEditState($record) {
        return Factory::getApplication()->getIdentity()->authorise('routes.edit.state', 'com_ra_library');
    }
}
