<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Ramblers\Component\Ra_library\Administrator\Helper\ImageGalleryHelper;

/**
 * Handles the "Import Selected" submission from the Walks Manager import
 * screen - showing the screen itself (fetching + listing candidate walks)
 * is handled by the Import view/model on a plain GET, since that step
 * doesn't write anything. This controller only exists for the write step.
 *
 * @since  1.0.0
 */
class ImportController extends BaseController {

    /**
     * Import the ticked walks (and any ticked photos for each) from the
     * cached Walks Manager fetch into the Past Walks table.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function import() {
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('pastwalks.create', 'com_ra_library')) {
            $app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_ra_library&view=pastwalks', false));

            return;
        }

        $wmIds = (array) $this->input->post->get('wm_ids', [], 'array');
        $wmIds = array_map('strval', array_filter($wmIds, static fn($id) => $id !== ''));

        $photoSelections = $this->input->post->get('photos', [], 'array');

        // 0 (or absent) means "Uncategorised" - PastwalkTable::bind() only
        // auto-resolves that special case from an empty STRING, not a
        // literal 0, so keep passing '' through for it rather than 0.
        $catidSelected = $this->input->post->getInt('catid', 0);
        $catid = $catidSelected > 0 ? $catidSelected : '';

        if (empty($wmIds)) {
            $app->enqueueMessage(Text::_('COM_RA_LIBRARY_IMPORT_NONE_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_ra_library&view=import', false));

            return;
        }

        /** @var \Ramblers\Component\Ra_library\Administrator\Model\ImportModel $importModel */
        $importModel = $this->getModel('Import', 'Administrator', ['ignore_request' => true]);
        $rows = $importModel->getCachedItems($wmIds);

        $imported = 0;
        $photosImported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $wmId => $row) {
            // Belt and braces - the checkbox for an already-imported walk is
            // disabled on the screen, but don't rely on that alone; the
            // walks_manager_id column is also unique, so this would fail
            // anyway, just with a less friendly error.
            if (!empty($row->already_imported)) {
                $skipped++;

                continue;
            }

            $data = [
                'record_type' => 'pastwalk',
                'walks_manager_id' => $row->wm_id,
                'walk_date' => $row->walk_date,
                'title' => $row->title,
                'walk_leader' => $row->leader,
                'description' => $row->description,
                'distance_km' => $row->distance_km,
                'national_grade' => $row->grade,
                'start_latitude' => $row->latitude,
                'start_longitude' => $row->longitude,
                'start_grid_reference' => $row->grid_reference,
                // The category chosen on the import screen - '' (not 0)
                // falls back to "Uncategorised", see $catid above.
                'catid' => $catid,
                'state' => 1,
                // Published straight away, but flagged so it's easy to find
                // and review/enrich afterwards - cleared automatically the
                // next time someone opens and saves the record.
                'needs_review' => 1,
            ];

            /** @var \Ramblers\Component\Ra_library\Administrator\Model\PastwalkModel $pastwalkModel */
            $pastwalkModel = $this->getModel('Pastwalk', 'Administrator', ['ignore_request' => true]);
            $saved = $pastwalkModel->save($data);

            if (!$saved) {
                $errors[] = $row->title . ': ' . $pastwalkModel->getError();

                continue;
            }

            $imported++;
            $newId = (int) $pastwalkModel->getState($pastwalkModel->getName() . '.id');

            $wantedPhotoIndexes = array_map('intval', (array) ($photoSelections[$wmId] ?? []));

            foreach ($wantedPhotoIndexes as $photoIndex) {
                if (!isset($row->photos[$photoIndex])) {
                    continue;
                }

                $photo = $row->photos[$photoIndex];
                $caption = $photo->alt !== '' ? $photo->alt : $row->title;
                $result = ImageGalleryHelper::importPhotoFromUrl($photo->url, $newId, 'pastwalk', $caption);

                if ($result === true) {
                    $photosImported++;
                } else {
                    $errors[] = $row->title . ' (photo): ' . $result;
                }
            }
        }

        if ($imported > 0) {
            $app->enqueueMessage(Text::sprintf('COM_RA_LIBRARY_IMPORT_SUCCESS', $imported, $photosImported), 'message');
        }

        if ($skipped > 0) {
            $app->enqueueMessage(Text::sprintf('COM_RA_LIBRARY_IMPORT_SKIPPED_ALREADY_IMPORTED', $skipped), 'warning');
        }

        if (!empty($errors)) {
            $app->enqueueMessage(Text::_('COM_RA_LIBRARY_IMPORT_ERRORS_HEADING') . '<br>' . implode('<br>', array_map('htmlspecialchars', $errors)), 'warning');
        }

        $this->setRedirect(Route::_('index.php?option=com_ra_library&view=pastwalks', false));
    }
}
