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
use Joomla\Database\DatabaseInterface;

/**
 * Handles the "Route Points" subform on the Route edit form - a repeatable
 * list of located waypoints (grid reference and/or lat/long) each with an
 * HTML description, replacing the old single route_guide editor field so a
 * future map view can plot each point and show its description in a popup.
 *
 * Much simpler than ImageGalleryHelper/AttachmentHelper - there's no file
 * upload involved here at all, just plain form data, so there's no need for
 * the $_FILES row-matching-by-original-key trick those two need.
 *
 * @since  1.0.0
 */
class RoutePointHelper
{
    /**
     * Fetch the route point rows for a record, in display order.
     *
     * @param   int  $recordId  The route id.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public static function getPointsForRecord(int $recordId): array
    {
        if ($recordId <= 0) {
            return [];
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__ra_library_route_points'))
            ->where($db->quoteName('record_id') . ' = ' . $recordId)
            ->order($db->quoteName('ordering') . ' ASC, ' . $db->quoteName('id') . ' ASC');
        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Save the submitted route points subform: updates existing rows,
     * inserts new ones, and removes rows that were dropped from the
     * submission.
     *
     * @param   int    $recordId       The route id (already saved).
     * @param   array  $submittedData  The submitted subform rows ($data['route_points']).
     *
     * @return  true  Always true - there's no failure mode here (no file uploads to go wrong).
     *
     * @since   1.0.0
     */
    public static function savePoints(int $recordId, array $submittedData): bool
    {
        if ($recordId <= 0) {
            return true;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $existingById = [];

        foreach (self::getPointsForRecord($recordId) as $row) {
            $existingById[(int) $row->id] = $row;
        }

        $keptIds = [];
        $ordering = 1;

        foreach ($submittedData as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $description = (string) ($row['description'] ?? '');
            $gridReference = trim((string) ($row['grid_reference'] ?? ''));
            $latitude = (isset($row['latitude']) && $row['latitude'] !== '') ? (float) $row['latitude'] : null;
            $longitude = (isset($row['longitude']) && $row['longitude'] !== '') ? (float) $row['longitude'] : null;

            if ($title === '' && $description === '' && $gridReference === '' && $latitude === null && $longitude === null) {
                // A blank trailing row from the repeatable-table UI - skip.
                continue;
            }

            $id = (int) ($row['id'] ?? 0);
            $existingRow = ($id && isset($existingById[$id])) ? $existingById[$id] : null;

            $columns = [
                'record_id' => $recordId,
                'title' => $title,
                'description' => $description,
                'grid_reference' => $gridReference,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'ordering' => $ordering,
            ];

            if ($existingRow) {
                $query = $db->getQuery(true)->update($db->quoteName('#__ra_library_route_points'));

                foreach ($columns as $column => $value) {
                    $query->set($db->quoteName($column) . ' = ' . ($value === null ? 'NULL' : $db->quote($value)));
                }

                $query->where($db->quoteName('id') . ' = ' . (int) $existingRow->id);
                $db->setQuery($query)->execute();
                $keptIds[] = (int) $existingRow->id;
            } else {
                $query = $db->getQuery(true)->insert($db->quoteName('#__ra_library_route_points'));
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

        foreach ($existingById as $id => $row) {
            if (!\in_array($id, $keptIds, true)) {
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__ra_library_route_points'))
                    ->where($db->quoteName('id') . ' = ' . (int) $id);
                $db->setQuery($query)->execute();
            }
        }

        return true;
    }

    /**
     * Remove all route points for a record - used when the parent route is
     * permanently deleted.
     *
     * @param   int  $recordId  The route id being deleted.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public static function deletePointsForRecord(int $recordId): void
    {
        if ($recordId <= 0) {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__ra_library_route_points'))
            ->where($db->quoteName('record_id') . ' = ' . $recordId);
        $db->setQuery($query)->execute();
    }
}
