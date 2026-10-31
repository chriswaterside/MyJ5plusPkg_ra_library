<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Input\Files as FilesInput;

/**
 * Handles the image gallery subform shared by Past Walks and Routes: saving
 * uploaded photos (resized, no originals kept), reading the gallery back for
 * the edit form, and cleaning up files when images/records are removed.
 *
 * Deliberately uses the non-deprecated Joomla\Filesystem\* framework classes
 * (rather than the Joomla\CMS\Filesystem\* wrappers, which are marked for
 * removal in 6.0) and Joomla\Input\Files (rather than the deprecated
 * Joomla\CMS\Input\Files) so this keeps working across Joomla 5 and 6.
 *
 * @since  1.0.0
 */
class ImageGalleryHelper
{
    /**
     * Maximum width/height for the "large" saved copy - the original upload
     * is never kept, only this resized copy (never upscaled if the original
     * was already smaller than this).
     *
     * @since 1.0.0
     */
    private const MAX_LARGE_DIMENSION = 1600;

    /**
     * Maximum width/height for the thumbnail used in gallery grids.
     *
     * @since 1.0.0
     */
    private const MAX_THUMB_DIMENSION = 400;

    /**
     * Accepted upload mime types.
     *
     * @since 1.0.0
     */
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

    /**
     * Most photos one record's gallery may hold - matches max="50" on the
     * "images" subform in forms/pastwalk.xml and forms/route.xml, so a bulk
     * add can never create a gallery the form then refuses to save.
     *
     * @since 1.0.13
     */
    private const MAX_IMAGES_PER_RECORD = 50;

    /**
     * Fetch the gallery rows for a record, in display order.
     *
     * @param   int  $recordId  The past walk / route id.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public static function getImagesForRecord(int $recordId): array
    {
        if ($recordId <= 0) {
            return [];
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__ra_library_images'))
            ->where($db->quoteName('record_id') . ' = ' . $recordId)
            ->order($db->quoteName('ordering') . ' ASC, ' . $db->quoteName('id') . ' ASC');
        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Save the submitted image gallery subform for a record: updates
     * existing rows, inserts new ones (processing any uploaded file),
     * and removes rows/files that were dropped from the submission.
     *
     * Enforces "only one Featured image" by keeping the first row flagged
     * featured in submission order and forcing any others back to 0, rather
     * than needing JS to keep checkboxes in sync client-side.
     *
     * @param   int     $recordId    The past walk / route id (already saved).
     * @param   string  $recordType  'pastwalk' or 'route' - determines the storage subfolder.
     * @param   array   $imagesData  The submitted subform rows ($data['images']).
     *
     * @return  true|string  True on success, or a user-facing error message string on failure.
     *
     * @since   1.0.0
     */
    public static function saveImages(int $recordId, string $recordType, array $imagesData)
    {
        if ($recordId <= 0) {
            return true;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $existingById = [];

        foreach (self::getImagesForRecord($recordId) as $row) {
            $existingById[(int) $row->id] = $row;
        }

        $filesInput = new FilesInput();
        $allFiles = $filesInput->get('jform', [], 'raw');
        $imageFiles = (\is_array($allFiles) && isset($allFiles['images']) && \is_array($allFiles['images'])) ? $allFiles['images'] : [];

        $keptIds = [];
        $featuredAssigned = false;
        $ordering = 1;
        $displayIndex = 0;

        // NOTE: Joomla's repeatable-table subform names each row's controls
        // using the field's own name as a string prefix - e.g. this "images"
        // field's rows are posted as jform[images][images0], [images1], ...
        // (STRING keys "images0"/"images1", not integer 0/1). $imagesData
        // (already Form-filtered) and $imageFiles (the raw $_FILES pivot)
        // both preserve those same original keys, so rows must be matched
        // between the two arrays by that original key - re-indexing either
        // one with array_values()/foreach-index would silently break the
        // pairing and every "new" row would look like it has no upload.
        foreach ($imagesData as $rowKey => $row) {
            if (!\is_array($row)) {
                continue;
            }

            $displayIndex++;

            $id = (int) ($row['id'] ?? 0);
            $existingRow = ($id && isset($existingById[$id])) ? $existingById[$id] : null;

            $uploadedFile = $imageFiles[$rowKey]['image_upload'] ?? null;
            $hasNewUpload = \is_array($uploadedFile)
                && !empty($uploadedFile['tmp_name'])
                && (int) ($uploadedFile['error'] ?? \UPLOAD_ERR_NO_FILE) === \UPLOAD_ERR_OK;

            if (!$existingRow && !$hasNewUpload) {
                // A blank trailing row from the repeatable-table UI with
                // nothing filled in yet - nothing to save, skip silently.
                continue;
            }

            $caption = trim((string) ($row['caption'] ?? ''));
            $description = (string) ($row['description'] ?? '');
            $gridReference = trim((string) ($row['grid_reference'] ?? ''));
            [$latitude, $longitude] = self::latLongFromGridReference($gridReference);
            $wantsFeatured = !empty($row['featured']);

            $thumbnailPath = $existingRow->thumbnail_path ?? '';
            $largePath = $existingRow->large_path ?? '';
            $fileHash = (string) ($existingRow->file_hash ?? '');

            if ($hasNewUpload) {
                // Hash the ORIGINAL bytes before processUpload() - it rewrites
                // the temp file in place when correcting EXIF rotation. Stored
                // so "Add several photos at once" can skip re-adds of this photo.
                $fileHash = (string) @sha1_file($uploadedFile['tmp_name']);

                $processed = self::processUpload($uploadedFile, $recordId, $recordType);

                if ($processed === false) {
                    return Text::sprintf(
                        'COM_RA_LIBRARY_IMAGE_UPLOAD_ERROR',
                        $caption !== '' ? $caption : ('#' . $displayIndex)
                    );
                }

                // Replacing an existing photo - remove the old files it's superseding.
                self::deleteFileQuietly($thumbnailPath);
                self::deleteFileQuietly($largePath);

                $thumbnailPath = $processed['thumbnail_path'];
                $largePath = $processed['large_path'];
            }

            if ($largePath === '') {
                // No file at all, old or new - nothing sensible to store.
                continue;
            }

            $featured = ($wantsFeatured && !$featuredAssigned) ? 1 : 0;

            if ($featured) {
                $featuredAssigned = true;
            }

            $columns = [
                'record_id' => $recordId,
                'caption' => $caption,
                'description' => $description,
                'thumbnail_path' => $thumbnailPath,
                'large_path' => $largePath,
                'file_hash' => $fileHash,
                'grid_reference' => $gridReference,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'featured' => $featured,
                'ordering' => $ordering,
            ];

            if ($existingRow) {
                $query = $db->getQuery(true)->update($db->quoteName('#__ra_library_images'));

                foreach ($columns as $column => $value) {
                    $query->set($db->quoteName($column) . ' = ' . ($value === null ? 'NULL' : $db->quote($value)));
                }

                $query->where($db->quoteName('id') . ' = ' . (int) $existingRow->id);
                $db->setQuery($query)->execute();
                $keptIds[] = (int) $existingRow->id;
            } else {
                $query = $db->getQuery(true)->insert($db->quoteName('#__ra_library_images'));
                $query->columns(array_map(static fn ($col) => $db->quoteName($col), array_keys($columns)));
                $query->values(implode(',', array_map(
                    static fn ($value) => $value === null ? 'NULL' : $db->quote($value),
                    array_values($columns)
                )));
                $db->setQuery($query)->execute();
                $keptIds[] = (int) $db->insertid();
            }

            $ordering++;
        }

        // Anything that existed before but wasn't in this submission was
        // removed by the admin in the UI - delete its row and its files.
        foreach ($existingById as $id => $row) {
            if (!\in_array($id, $keptIds, true)) {
                self::deleteFileQuietly($row->thumbnail_path);
                self::deleteFileQuietly($row->large_path);

                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__ra_library_images'))
                    ->where($db->quoteName('id') . ' = ' . (int) $id);
                $db->setQuery($query)->execute();
            }
        }

        return true;
    }

    /**
     * Remove all gallery images (rows and files) for a record - used when
     * the parent past walk / route is permanently deleted.
     *
     * Deliberately only removes the image files here, not the record's
     * whole per-record folder - GPX/document files (AttachmentHelper) live
     * in that same folder, so the folder itself is only safe to remove once
     * both helpers have cleaned up their own files. See deleteRecordFolder()
     * below, called separately by the Model after both this and
     * AttachmentHelper::deleteAttachmentsForRecord() have run.
     *
     * @param   int  $recordId  The past walk / route id being deleted.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public static function deleteImagesForRecord(int $recordId): void
    {
        if ($recordId <= 0) {
            return;
        }

        foreach (self::getImagesForRecord($recordId) as $row) {
            self::deleteFileQuietly($row->thumbnail_path);
            self::deleteFileQuietly($row->large_path);
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__ra_library_images'))
            ->where($db->quoteName('record_id') . ' = ' . $recordId);
        $db->setQuery($query)->execute();
    }

    /**
     * Remove a record's whole per-record storage folder (images, GPX,
     * documents all live under the same folder) - only safe to call once
     * every file-owning helper has already deleted its own rows/files, i.e.
     * as the last step of a permanent delete, after
     * deleteImagesForRecord() and AttachmentHelper::deleteAttachmentsForRecord()
     * have both already run.
     *
     * @param   int     $recordId    The past walk / route id being permanently deleted.
     * @param   string  $recordType  'pastwalk' or 'route'.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public static function deleteRecordFolder(int $recordId, string $recordType): void
    {
        if ($recordId <= 0) {
            return;
        }

        $subfolder = ($recordType === 'route') ? 'routes' : 'pastwalks';
        $absoluteFolder = JPATH_ROOT . '/images/com_ra_library/' . $subfolder . '/' . $recordId;

        if (is_dir($absoluteFolder)) {
            try {
                Folder::delete($absoluteFolder);
            } catch (\Exception $e) {
                // Ignore - best-effort cleanup only.
            }
        }
    }

    /**
     * Process one uploaded photo: correct EXIF rotation, generate a
     * max-1600px "large" copy and a max-400px thumbnail (neither ever
     * upscaled beyond the original), save both under the record's storage
     * folder, and discard the original upload entirely.
     *
     * @param   array   $uploadedFile  One entry from Joomla\Input\Files::get() - name/type/tmp_name/error/size.
     * @param   int     $recordId      The past walk / route id.
     * @param   string  $recordType    'pastwalk' or 'route'.
     *
     * @return  array|false  ['large_path' => ..., 'thumbnail_path' => ...] (root-relative), or false on failure.
     *
     * @since   1.0.0
     */
    private static function processUpload(array $uploadedFile, int $recordId, string $recordType)
    {
        $tmpName = $uploadedFile['tmp_name'] ?? '';

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return false;
        }

        $mimeType = @mime_content_type($tmpName) ?: '';

        if (!\in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return false;
        }

        return self::resizeAndStore($tmpName, $mimeType, $recordId, $recordType);
    }

    /**
     * Shared by both processUpload() (a genuine $_FILES upload) and
     * importPhotoFromUrl() (a photo downloaded from the Walks Manager feed):
     * correct EXIF rotation, generate the max-1600px "large" copy and the
     * max-400px thumbnail (neither ever upscaled), save both under the
     * record's storage folder, and leave the source file untouched (the
     * caller is responsible for cleaning up its own temp file).
     *
     * @param   string  $tmpPath     Filesystem path to the source image (already known to be one of ALLOWED_MIME_TYPES).
     * @param   string  $mimeType    The detected mime type of the source image.
     * @param   int     $recordId    The past walk / route id.
     * @param   string  $recordType  'pastwalk' or 'route'.
     *
     * @return  array|false  ['large_path' => ..., 'thumbnail_path' => ...] (root-relative), or false on failure.
     *
     * @since   1.0.0
     */
    private static function resizeAndStore(string $tmpPath, string $mimeType, int $recordId, string $recordType)
    {
        self::correctOrientation($tmpPath, $mimeType);

        try {
            $properties = Image::getImageFileProperties($tmpPath);
            $image = new Image($tmpPath);
        } catch (\Exception $e) {
            return false;
        }

        $subfolder = ($recordType === 'route') ? 'routes' : 'pastwalks';
        $relativeFolder = 'images/com_ra_library/' . $subfolder . '/' . $recordId;
        $absoluteFolder = JPATH_ROOT . '/' . $relativeFolder;

        if (!is_dir($absoluteFolder)) {
            try {
                Folder::create($absoluteFolder);
            } catch (\Exception $e) {
                return false;
            }
        }

        $baseName = 'img' . substr(md5(uniqid((string) mt_rand(), true)), 0, 12);
        $relativeLarge = $relativeFolder . '/' . $baseName . '_large.jpg';
        $relativeThumb = $relativeFolder . '/' . $baseName . '_thumb.jpg';

        // Large copy: only shrink if it exceeds the cap, never upscale a smaller original.
        if ($properties->width > self::MAX_LARGE_DIMENSION || $properties->height > self::MAX_LARGE_DIMENSION) {
            $largeImage = $image->resize(self::MAX_LARGE_DIMENSION, self::MAX_LARGE_DIMENSION, true, Image::SCALE_INSIDE);
        } else {
            $largeImage = $image;
        }

        if (!$largeImage->toFile(JPATH_ROOT . '/' . $relativeLarge, \IMAGETYPE_JPEG, ['quality' => 85])) {
            return false;
        }

        // Thumbnail: derived from the large copy, same no-upscale guard.
        if ($largeImage->getWidth() > self::MAX_THUMB_DIMENSION || $largeImage->getHeight() > self::MAX_THUMB_DIMENSION) {
            $thumbImage = $largeImage->resize(self::MAX_THUMB_DIMENSION, self::MAX_THUMB_DIMENSION, true, Image::SCALE_INSIDE);
        } else {
            $thumbImage = $largeImage;
        }

        if (!$thumbImage->toFile(JPATH_ROOT . '/' . $relativeThumb, \IMAGETYPE_JPEG, ['quality' => 80])) {
            return false;
        }

        return [
            'large_path' => $relativeLarge,
            'thumbnail_path' => $relativeThumb,
        ];
    }

    /**
     * Download a remote photo (Walks Manager media host).
     *
     * Uses Joomla's HTTP client (cURL or streams, with a User-Agent, redirects
     * and a timeout) rather than file_get_contents(), and says WHY a download
     * failed so the import screen can show something more useful than "could
     * not download". Transient failures (timeouts, 5xx, dropped connections)
     * are retried up to three times with a short pause; 404/410 are not.
     *
     * @param   string  $url  The photo URL.
     *
     * @return  array  [body|false, reason string for the failure ('' on success)]
     *
     * @since   1.0.14
     */
    private static function fetchRemotePhoto(string $url): array
    {
        // Spaces and non-ASCII characters in a path are not valid in a URL.
        $url = preg_replace_callback('/[^\x21-\x7e]/', static fn ($m) => rawurlencode($m[0]), $url);
        $reason = '';

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            if ($attempt > 1) {
                usleep(700000);
            }

            try {
                $http = \Joomla\CMS\Http\HttpFactory::getHttp(['follow_location' => true]);
                $response = $http->get($url, ['Accept' => 'image/*,*/*;q=0.8'], 30);

                $code = method_exists($response, 'getStatusCode') ? (int) $response->getStatusCode() : (int) ($response->code ?? 0);
                $body = method_exists($response, 'getBody') ? (string) $response->getBody() : (string) ($response->body ?? '');

                if ($code >= 200 && $code < 300 && $body !== '') {
                    return [$body, ''];
                }

                $reason = $code >= 200 && $code < 300 ? 'empty response' : 'HTTP ' . $code;

                // The photo is not there (or not for us): trying again will not help.
                if (\in_array($code, [400, 401, 403, 404, 410], true)) {
                    break;
                }
            } catch (\Throwable $e) {
                $reason = trim($e->getMessage()) !== '' ? trim($e->getMessage()) : 'connection failed';
            }
        }

        return [false, $reason];
    }

    /**
     * Download one photo from a Walks Manager feed URL and save it into a
     * record's gallery, going through the exact same resize pipeline as a
     * manual upload (no originals kept). Used by the "Import from Walks
     * Manager" screen when the admin ticks a feed photo to bring in.
     *
     * @param   string  $url          The feed photo URL (Walks Manager media "medium" style).
     * @param   int     $recordId     The past walk id this photo belongs to (already saved).
     * @param   string  $recordType   'pastwalk' or 'route'.
     * @param   string  $caption      Caption to store (usually the feed's media alt text).
     * @param   string  $description  Optional description.
     *
     * @return  true|string  True on success, or a user-facing error message string on failure.
     *
     * @since   1.0.0
     */
    public static function importPhotoFromUrl(string $url, int $recordId, string $recordType, string $caption, string $description = '')
    {
        if ($recordId <= 0 || $url === '') {
            return Text::_('COM_RA_LIBRARY_IMAGE_IMPORT_INVALID');
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'ra_import_');

        if ($tmpPath === false) {
            return Text::_('COM_RA_LIBRARY_IMAGE_IMPORT_TMP_ERROR');
        }

        [$contents, $reason] = self::fetchRemotePhoto($url);

        if ($contents === false) {
            @unlink($tmpPath);

            return Text::sprintf('COM_RA_LIBRARY_IMAGE_IMPORT_FETCH_ERROR', $caption !== '' ? $caption : $url, $reason, $url);
        }

        file_put_contents($tmpPath, $contents);

        // Taken before resizeAndStore() rewrites the temp file (EXIF rotation).
        $fileHash = (string) @sha1_file($tmpPath);

        $mimeType = @mime_content_type($tmpPath) ?: '';

        if (!\in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            @unlink($tmpPath);

            return Text::sprintf('COM_RA_LIBRARY_IMAGE_IMPORT_TYPE_ERROR', $caption !== '' ? $caption : $url);
        }

        $processed = self::resizeAndStore($tmpPath, $mimeType, $recordId, $recordType);
        @unlink($tmpPath);

        if ($processed === false) {
            return Text::sprintf('COM_RA_LIBRARY_IMAGE_IMPORT_PROCESS_ERROR', $caption !== '' ? $caption : $url);
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $columns = [
            'record_id' => $recordId,
            'caption' => $caption,
            'description' => $description,
            'thumbnail_path' => $processed['thumbnail_path'],
            'large_path' => $processed['large_path'],
            'file_hash' => $fileHash,
            'grid_reference' => '',
            'latitude' => null,
            'longitude' => null,
            'featured' => 0,
            'ordering' => self::getNextOrdering($recordId),
        ];

        $query = $db->getQuery(true)->insert($db->quoteName('#__ra_library_images'));
        $query->columns(array_map(static fn ($col) => $db->quoteName($col), array_keys($columns)));
        $query->values(implode(',', array_map(
            static fn ($value) => $value === null ? 'NULL' : $db->quote($value),
            array_values($columns)
        )));
        $db->setQuery($query)->execute();

        return true;
    }

    /**
     * Limits shown on the edit forms and used by the uploader script.
     * (Photos are uploaded one request at a time, so only the per-file PHP
     * limit applies - not post_max_size or max_file_uploads.)
     *
     * @return  array{maxImages: int, maxFileBytes: int, maxPixels: int}
     *
     * @since   1.0.14
     */
    public static function getUploadLimits(): array
    {
        return [
            'maxImages' => self::MAX_IMAGES_PER_RECORD,
            // 0 = unlimited
            'maxFileBytes' => self::iniToBytes((string) \ini_get('upload_max_filesize')),
            'maxPixels' => self::MAX_LARGE_DIMENSION,
        ];
    }

    /**
     * Add ONE uploaded photo to a saved record's gallery (the background
     * uploader on the edit forms posts photos one at a time).
     *
     * Duplicates are detected by the SHA-1 of the original file bytes against
     * photos already in this record's gallery. Photos stored before the
     * file_hash column existed have no hash and cannot be matched.
     *
     * @param   int     $recordId    The past walk / route id (must already exist).
     * @param   string  $recordType  'pastwalk' or 'route'.
     * @param   array   $file        The entry from the request's files input (name/tmp_name/error/size).
     *
     * @return  array  ['status' => added|duplicate|full|invalid|failed, 'row' => array|null, 'name' => string]
     *
     * @since   1.0.14
     */
    public static function addUploadedPhoto(int $recordId, string $recordType, array $file): array
    {
        $name = trim((string) ($file['name'] ?? ''));
        $name = $name !== '' ? basename($name) : '?';
        $result = ['status' => 'failed', 'row' => null, 'name' => $name];

        $error = (int) ($file['error'] ?? \UPLOAD_ERR_NO_FILE);
        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($recordId <= 0 || $error !== \UPLOAD_ERR_OK || $tmpName === '' || !is_uploaded_file($tmpName)) {
            return $result;
        }

        if (\count(self::getImagesForRecord($recordId)) >= self::MAX_IMAGES_PER_RECORD) {
            $result['status'] = 'full';

            return $result;
        }

        // Hash first: cheap, and must be of the untouched original (see processUpload()).
        $hash = (string) @sha1_file($tmpName);

        if ($hash !== '') {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__ra_library_images'))
                ->where($db->quoteName('record_id') . ' = ' . $recordId)
                ->where($db->quoteName('file_hash') . ' = ' . $db->quote($hash));

            if ((int) $db->setQuery($query)->loadResult() > 0) {
                $result['status'] = 'duplicate';

                return $result;
            }
        }

        $mimeType = @mime_content_type($tmpName) ?: '';

        if (!\in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $result['status'] = 'invalid';

            return $result;
        }

        $processed = self::processUpload($file, $recordId, $recordType);

        if ($processed === false) {
            return $result;
        }

        $columns = [
            'record_id' => $recordId,
            'caption' => '',
            'description' => '',
            'thumbnail_path' => $processed['thumbnail_path'],
            'large_path' => $processed['large_path'],
            'file_hash' => $hash,
            'grid_reference' => '',
            'latitude' => null,
            'longitude' => null,
            'featured' => 0,
            'ordering' => self::getNextOrdering($recordId),
        ];

        self::insertImageRow($columns);

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $id = (int) $db->insertid();
        $root = \Joomla\CMS\Uri\Uri::root(true);

        $result['status'] = 'added';
        $result['row'] = [
            'id' => $id,
            'thumbnail_path' => $processed['thumbnail_path'],
            'thumbnail_url' => $root . '/' . ltrim($processed['thumbnail_path'], '/'),
            'large_url' => $root . '/' . ltrim($processed['large_path'], '/'),
        ];

        return $result;
    }

    /**
     * Insert one gallery row. (saveImages() and importPhotoFromUrl() still
     * carry their own copies of this block - left untouched to keep this
     * change small; they could call this later.)
     *
     * @param   array  $columns  Column => value; null values are written as NULL.
     *
     * @return  void
     *
     * @since   1.0.13
     */
    private static function insertImageRow(array $columns): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)->insert($db->quoteName('#__ra_library_images'));
        $query->columns(array_map(static fn ($col) => $db->quoteName($col), array_keys($columns)));
        $query->values(implode(',', array_map(
            static fn ($value) => $value === null ? 'NULL' : $db->quote($value),
            array_values($columns)
        )));
        $db->setQuery($query)->execute();
    }

    /**
     * Convert a php.ini size value ("8M", "2G", "512K", "1048576") to bytes.
     *
     * @param   string  $value  The ini value.
     *
     * @return  int  Bytes; 0 for "0"/empty/-1 (meaning unlimited).
     *
     * @since   1.0.13
     */
    private static function iniToBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $number = (float) $value;

        switch (strtolower(substr($value, -1))) {
            case 'g':
                $number *= 1024;
                // no break
            case 'm':
                $number *= 1024;
                // no break
            case 'k':
                $number *= 1024;
        }

        return (int) $number;
    }

    /**
     * Next display-order value for a new row being appended to a record's
     * gallery (used by importPhotoFromUrl() - saveImages() tracks its own
     * ordering counter across a whole subform submission instead).
     *
     * @param   int  $recordId  The past walk / route id.
     *
     * @return  int
     *
     * @since   1.0.0
     */
    private static function getNextOrdering(int $recordId): int
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('MAX(' . $db->quoteName('ordering') . ')')
            ->from($db->quoteName('#__ra_library_images'))
            ->where($db->quoteName('record_id') . ' = ' . $recordId);
        $db->setQuery($query);

        return ((int) $db->loadResult()) + 1;
    }

    /**
     * Calculate a photo's latitude/longitude from its grid reference
     * (e.g. "SK364514") - the admin only ever enters a grid reference now
     * (see administrator/forms/imagesrow.xml), lat/long are derived from
     * it here rather than being separately-entered fields, using the same
     * OSTN15-based transformer already relied on elsewhere in the
     * component for GPX/route coordinate work.
     *
     * The OsCoordinateTransformer instance is cached across calls within
     * one request (it parses a ~100KB OSTN15 grid data file on
     * construction) - a gallery save can process many photos in one go,
     * and there's no need to re-parse that file once per photo.
     *
     * @param   string  $gridReference  e.g. "SK364514" - blank/unparseable both just return [null, null].
     *
     * @return  array{0: float|null, 1: float|null}  [latitude, longitude].
     *
     * @since   1.0.0
     */
    /**
     * Delegates to Ra_libraryHelper::latLongFromGridReference() - the same
     * grid-ref parsing is also needed by PastwalkTable::bind() for Past
     * Walk / Route start locations, so it lives in one shared place rather
     * than being duplicated here.
     *
     * @param   string  $gridReference  e.g. "SK364514".
     *
     * @return  array  [latitude, longitude] floats, or [null, null].
     *
     * @since   1.0.0
     */
    private static function latLongFromGridReference(string $gridReference): array
    {
        return Ra_libraryHelper::latLongFromGridReference($gridReference);
    }

    /**
     * Rewrites a JPEG in place with its EXIF rotation baked into the pixel
     * data, so photos taken on phones (which store the image sensor-side-up
     * and rely on an EXIF tag for display rotation) don't end up sideways
     * once that tag is dropped by the resize/re-save below. No-ops quietly
     * if the exif extension isn't available or the file isn't a JPEG/has no
     * orientation tag - this is a nice-to-have correction, not essential.
     *
     * @param   string  $path      Filesystem path to the (temporary) uploaded file.
     * @param   string  $mimeType  The detected mime type of the upload.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private static function correctOrientation(string $path, string $mimeType): void
    {
        if ($mimeType !== 'image/jpeg' || !\function_exists('exif_read_data')) {
            return;
        }

        $exif = @exif_read_data($path);

        if (!$exif || empty($exif['Orientation'])) {
            return;
        }

        $orientation = (int) $exif['Orientation'];

        if ($orientation <= 1) {
            return;
        }

        $handle = @imagecreatefromjpeg($path);

        if (!$handle) {
            return;
        }

        switch ($orientation) {
            case 3:
                $handle = imagerotate($handle, 180, 0);
                break;

            case 6:
                $handle = imagerotate($handle, -90, 0);
                break;

            case 8:
                $handle = imagerotate($handle, 90, 0);
                break;
        }

        imagejpeg($handle, $path, 95);
        imagedestroy($handle);
    }

    /**
     * Best-effort delete of a stored image file - failures are swallowed
     * (e.g. the file was already missing), since this is always cleanup
     * alongside a DB change that should proceed regardless.
     *
     * @param   string  $relativePath  Root-relative path as stored in the DB.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private static function deleteFileQuietly(string $relativePath): void
    {
        if ($relativePath === '') {
            return;
        }

        $fullPath = JPATH_ROOT . '/' . ltrim($relativePath, '/');

        if (is_file($fullPath)) {
            try {
                File::delete($fullPath);
            } catch (\Exception $e) {
                // Ignore - best-effort cleanup only.
            }
        }
    }
}
