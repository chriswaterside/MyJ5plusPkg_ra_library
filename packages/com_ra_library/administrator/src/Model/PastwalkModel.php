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

/**
 * Pastwalk model.
 *
 * @since  1.0.0
 */
class PastwalkModel extends AdminModel {

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
    public $typeAlias = 'com_ra_library.pastwalk';

    /**
     * Returns a reference to a Table object, always creating it.
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
        return parent::getTable($type, $prefix, $config);
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
                'com_ra_library.pastwalk',
                'pastwalk',
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
        $data = Factory::getApplication()->getUserState('com_ra_library.edit.pastwalk.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Save a Pastwalk record.
     *
     * The underlying table is now shared with Routes (distinguished by the
     * record_type column) - force record_type to 'pastwalk' here regardless
     * of what's in the submitted data, so the Past Walk form can never be
     * used (accidentally or otherwise) to create/overwrite a route row.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success.
     *
     * @since   1.0.0
     */
    public function save($data) {
        $data['record_type'] = 'pastwalk';

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

        $result = parent::save($data);

        if (!$result) {
            return false;
        }

        $recordId = (int) $this->getState($this->getName() . '.id');
        $imagesResult = ImageGalleryHelper::saveImages($recordId, 'pastwalk', $imagesData);

        if ($imagesResult !== true) {
            $this->setError($imagesResult);

            return false;
        }

        $gpxResult = AttachmentHelper::saveAttachments($recordId, 'pastwalk', 'gpx', $gpxData);

        if ($gpxResult !== true) {
            $this->setError($gpxResult);

            return false;
        }

        $documentsResult = AttachmentHelper::saveAttachments($recordId, 'pastwalk', 'document', $documentsData);

        if ($documentsResult !== true) {
            $this->setError($documentsResult);

            return false;
        }

        return true;
    }

    /**
     * Delete one or more past walks - also removes each one's image gallery
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
            ImageGalleryHelper::deleteRecordFolder((int) $pk, 'pastwalk');
        }

        return parent::delete($pks);
    }

    /**
     * Restrict deleting a past walk to users with the pastwalks.delete
     * action, rather than the core.delete action used by the rest of the
     * component - so a "past walks only" user group can be granted delete
     * rights here without touching anything else in com_ra_library.
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

        return Factory::getApplication()->getIdentity()->authorise('pastwalks.delete', 'com_ra_library');
    }

    /**
     * Restrict publish/unpublish/archive/trash to users with the
     * pastwalks.edit.state action (see canDelete() above for why).
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    protected function canEditState($record) {
        return Factory::getApplication()->getIdentity()->authorise('pastwalks.edit.state', 'com_ra_library');
    }
}
