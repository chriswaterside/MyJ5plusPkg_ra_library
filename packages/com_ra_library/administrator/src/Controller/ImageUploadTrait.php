<?php

/**
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Ramblers\Component\Ra_library\Administrator\Helper\ImageGalleryHelper;

/**
 * Background (AJAX) photo upload for the Past Walk and Route edit forms.
 *
 * The edit form's uploader script posts ONE photo per request to
 * index.php?option=com_ra_library&task=<section>.uploadImage&format=json:
 *   id          the record id (must already be saved)
 *   photo       the file
 *   <token>=1   Joomla form token
 *
 * The using controller must provide allowEdit($data) (FormController does)
 * and imageRecordType() returning 'pastwalk' or 'route'.
 *
 * @since  1.0.14
 */
trait ImageUploadTrait
{
    /**
     * @return  string  'pastwalk' or 'route'
     */
    abstract protected function imageRecordType(): string;

    /**
     * Task: <section>.uploadImage
     *
     * @return  void
     *
     * @since   1.0.14
     */
    public function uploadImage()
    {
        $app = Factory::getApplication();

        if (!Session::checkToken('post')) {
            // Also what a file over post_max_size looks like: PHP discards the
            // whole body, so the token is missing.
            $this->imageJson(false, Text::_('COM_RA_LIBRARY_UPLOAD_ERR_TOKEN'), 403);
        }

        $recordType = $this->imageRecordType();
        $recordId = $this->input->post->getInt('id', 0);

        if ($recordId <= 0 || !$this->imageRecordExists($recordId, $recordType)) {
            $this->imageJson(false, Text::_('COM_RA_LIBRARY_UPLOAD_ERR_NO_RECORD'), 404);
        }

        if (!$this->allowEdit(['id' => $recordId], 'id')) {
            $this->imageJson(false, Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 403);
        }

        $file = $this->input->files->get('photo', [], 'raw');

        if (!\is_array($file) || !isset($file['tmp_name'])) {
            $this->imageJson(false, Text::_('COM_RA_LIBRARY_UPLOAD_ERR_NO_FILE'), 400);
        }

        $result = ImageGalleryHelper::addUploadedPhoto($recordId, $recordType, $file);

        $messages = [
            'added' => '',
            'duplicate' => Text::sprintf('COM_RA_LIBRARY_UPLOAD_DUPLICATE', $result['name']),
            'full' => Text::sprintf('COM_RA_LIBRARY_UPLOAD_FULL', $result['name'], ImageGalleryHelper::getUploadLimits()['maxImages']),
            'invalid' => Text::sprintf('COM_RA_LIBRARY_UPLOAD_INVALID', $result['name']),
            'failed' => Text::sprintf('COM_RA_LIBRARY_UPLOAD_FAILED', $result['name']),
        ];

        $this->imageJson(
            $result['status'] === 'added',
            $messages[$result['status']] ?? $messages['failed'],
            200,
            ['status' => $result['status'], 'row' => $result['row']]
        );
    }

    /**
     * @param   int  $recordId    Record id.
     * @param   string  $recordType  'pastwalk' or 'route'.
     *
     * @return  boolean
     */
    private function imageRecordExists(int $recordId, string $recordType): bool
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__ra_library_routes'))
            ->where($db->quoteName('id') . ' = ' . $recordId)
            ->where($db->quoteName('record_type') . ' = ' . $db->quote($recordType));

        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    /**
     * Send a JSON reply and end the request.
     *
     * @param   boolean  $success  Whether the upload worked.
     * @param   string   $message  Message for the user ('' when there is none).
     * @param   int      $httpCode HTTP status to send (the page script also reads "success").
     * @param   array    $data     Extra data.
     *
     * @return  void
     */
    private function imageJson(bool $success, string $message, int $httpCode, array $data = []): void
    {
        $app = Factory::getApplication();

        $app->setHeader('status', (string) $httpCode, true);
        $app->mimeType = 'application/json';
        $app->setHeader('Content-Type', $app->mimeType . '; charset=' . $app->charSet);
        $app->sendHeaders();

        echo new JsonResponse($data, $message !== '' ? $message : null, !$success);

        $app->close();
    }
}
