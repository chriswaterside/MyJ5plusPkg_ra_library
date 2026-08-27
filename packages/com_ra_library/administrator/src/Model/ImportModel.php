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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walksdaterange;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Wm\Feed;

/**
 * Fetches confirmed group-walks from the Ramblers Walks Manager feed (reusing
 * the site's own Wm\Feed library) and works out which ones aren't already in
 * the Past Walks table, so they can be offered on the import screen.
 *
 * Deliberately its own small model rather than an AdminModel - this isn't a
 * CRUD form over one DB record, it's a "fetch from an external feed, show a
 * pick-list, then hand selected rows off to PastwalkModel::save()" workflow.
 *
 * @since  1.0.0
 */
class ImportModel extends BaseDatabaseModel {

    /**
     * The application user-state key the fetched, mapped walk rows are
     * cached under between the "show the list" (GET) and "import the ticked
     * ones" (POST) requests, keyed by their Walks Manager id.
     *
     * @since 1.0.0
     */
    private const STATE_KEY = 'com_ra_library.import.items';

    /**
     * Timestamp format used throughout the Walks Manager feed.
     *
     * @since 1.0.0
     */
    private const TIMEFORMAT = 'Y-m-d\TH:i:s';

    /**
     * The component's saved default group code(s), from Options.
     *
     * @return  string
     *
     * @since   1.0.0
     */
    public function getDefaultGroupCodes(): string {
        return self::extractCodesCsv($this->getDefaultGroupCodesRaw());
    }

    /**
     * The raw stored component default for wm_group_codes - JSON text from
     * the feedcheckboxes field (an array of {code, name} rows), used to
     * pre-bind the checkboxes on the Import screen so they show correctly
     * ticked, not just to compute a CSV string from.
     *
     * @return  string
     *
     * @since   1.0.0
     */
    public function getDefaultGroupCodesRaw(): string {
        $params = \Joomla\CMS\Component\ComponentHelper::getParams('com_ra_library');

        return (string) $params->get('wm_group_codes', '');
    }

    /**
     * The feedcheckboxes field (used on both the component Options screen
     * and this Import screen) stores its value as JSON text - an array of
     * {code, name} rows, one per ticked group - not a plain CSV string.
     * Feed::getGroupEventItems() needs a plain CSV string of codes, so this
     * converts between the two. Also tolerates a plain CSV string being
     * passed straight through, in case it's ever set some other way.
     *
     * @param   string  $raw  The raw stored value - JSON array of {code, name} rows, or a plain CSV string.
     *
     * @return  string  Comma-separated group codes.
     *
     * @since   1.0.0
     */
    public static function extractCodesCsv(string $raw): string {
        $raw = trim($raw);

        if ($raw === '') {
            return '';
        }

        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            $codes = array_filter(array_map(
                            static fn($item) => is_array($item) ? (string) ($item['code'] ?? '') : '',
                            $decoded
                    ));

            return implode(',', $codes);
        }

        // Not JSON - already a plain CSV string.
        return $raw;
    }

    /**
     * The configured default group(s), as {code, name} pairs for display -
     * e.g. the read-only "importing from" summary on the Import screen. Same
     * tolerant JSON-or-plain-CSV handling as extractCodesCsv(); for a plain
     * CSV string there's no separate name, so code is reused as the name.
     *
     * @return  array  List of ['code' => string, 'name' => string].
     *
     * @since   1.0.0
     */
    public function getDefaultGroupsForDisplay(): array {
        $raw = trim($this->getDefaultGroupCodesRaw());

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            $groups = [];

            foreach ($decoded as $item) {
                $code = is_array($item) ? (string) ($item['code'] ?? '') : '';

                if ($code === '') {
                    continue;
                }

                $groups[] = [
                    'code' => $code,
                    'name' => is_array($item) && (string) ($item['name'] ?? '') !== '' ? (string) $item['name'] : $code,
                ];
            }

            return $groups;
        }

        // Not JSON - a plain CSV string of codes, no separate names.
        $codes = array_filter(array_map('trim', explode(',', $raw)));

        return array_map(static fn($code) => ['code' => $code, 'name' => $code], $codes);
    }

    /**
     * Fetch the feed, drop anything that's cancelled or isn't a group-walk,
     * map what's left into simple display rows - flagging any that have
     * already been imported (matched on walks_manager_id) with the matching
     * Past Walk's id and current state rather than dropping them - and cache
     * everything in the user state ready for import().
     *
     * $groupCodes is clamped to the component's configured default group(s)
     * (Options > wm_group_codes) regardless of what's passed in - importing
     * is only ever allowed from those groups, never an arbitrary code a
     * caller might supply.
     *
     * @param   string  $groupCodes  CSV group codes, e.g. "ER05" - intersected against the configured default(s).
     * @param   string  $period      A relative date string for Walksdaterange::setPast(), e.g. "3 months".
     *
     * @return  array  ['items' => array of row objects, 'error' => string|null]
     *
     * @since   1.0.0
     */
    public function fetchAvailableWalks(string $groupCodes, string $period): array {
        $groupCodes = $this->clampToAllowedGroupCodes($groupCodes);

        if ($groupCodes === '') {
            return ['items' => [], 'error' => \Joomla\CMS\Language\Text::_('COM_RA_LIBRARY_IMPORT_NO_GROUP_CODE')];
        }

        try {
            $range = new Walksdaterange();
            $range->setPast($period !== '' ? $period : '3 months');

            $feed = new Feed();
            $rawItems = $feed->getGroupEventItems($groupCodes, true, false, false, $range);
        } catch (\Throwable $e) {
            return ['items' => [], 'error' => $e->getMessage()];
        }

        if (empty($rawItems)) {
            return ['items' => [], 'error' => null];
        }

        $alreadyImported = $this->getAlreadyImportedByWmId();

        $items = [];

        foreach ($rawItems as $item) {
            if (!\is_object($item) || !isset($item->id, $item->status, $item->item_type)) {
                continue;
            }

            if ($item->status !== 'confirmed') {
                continue;
            }

            if ($item->item_type !== 'group-walk') {
                continue;
            }

            $wmId = (string) $item->id;
            $row = $this->mapItem($item);

            if ($row === null) {
                continue;
            }

            $existing = $alreadyImported[$wmId] ?? null;
            $row->already_imported = $existing !== null;
            $row->pastwalk_id = $existing !== null ? (int) $existing->id : null;
            $row->pastwalk_state = $existing !== null ? (int) $existing->state : null;

            $items[$wmId] = $row;
        }

        Factory::getApplication()->setUserState(self::STATE_KEY, $items);

        return ['items' => array_values($items), 'error' => null];
    }

    /**
     * Intersects the requested group codes against the component's
     * configured default(s) so a fetch can never actually reach any group
     * that isn't set up in Options, however the requested codes got here.
     *
     * @param   string  $requestedCodes  CSV group codes as requested.
     *
     * @return  string  CSV group codes, restricted to the configured default(s).
     *
     * @since   1.0.0
     */
    private function clampToAllowedGroupCodes(string $requestedCodes): string {
        $allowed = array_filter(array_map('trim', explode(',', $this->getDefaultGroupCodes())));

        if (empty($allowed)) {
            return '';
        }

        $requested = array_filter(array_map('trim', explode(',', $requestedCodes)));

        // No specific codes requested (e.g. the screen just uses the
        // default outright) - allow the full configured set.
        if (empty($requested)) {
            return implode(',', $allowed);
        }

        return implode(',', array_intersect($requested, $allowed));
    }

    /**
     * Look up the cached rows built by fetchAvailableWalks() for the ticked
     * Walks Manager ids - used by ImportController::import() so it never has
     * to trust anything about a walk's data coming back from the browser
     * except which ids were ticked.
     *
     * @param   array  $wmIds  The submitted Walks Manager ids to import.
     *
     * @return  array  Row objects (see mapItem()), keyed by wm id, for the ids that were actually cached.
     *
     * @since   1.0.0
     */
    public function getCachedItems(array $wmIds): array {
        $cached = Factory::getApplication()->getUserState(self::STATE_KEY, []);

        $selected = [];

        foreach ($wmIds as $wmId) {
            $wmId = (string) $wmId;

            if (isset($cached[$wmId])) {
                $selected[$wmId] = $cached[$wmId];
            }
        }

        return $selected;
    }

    /**
     * Walks Manager ids already present in the Past Walks table, with the
     * matching record's id and current state, so the import list can show
     * them (rather than silently dropping them) with their current status.
     *
     * @return  array  walks_manager_id => stdClass{id, walks_manager_id, state}
     *
     * @since   1.0.0
     */
    private function getAlreadyImportedByWmId(): array {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
                ->select($db->quoteName(['id', 'walks_manager_id', 'state']))
                ->from($db->quoteName('#__ra_library_routes'))
                ->where($db->quoteName('walks_manager_id') . ' IS NOT NULL');
        $db->setQuery($query);

        try {
            return $db->loadObjectList('walks_manager_id') ?: [];
        } catch (\RuntimeException $e) {
            return [];
        }
    }

    /**
     * Map one raw Walks Manager feed item into the flat shape the import
     * screen and PastwalkModel::save() both work with. Field names mirror
     * Site\Library\Jsonwalks\Sourcewalksmanager::convertToInternalFormat(),
     * which already does this same mapping for the site's own walk display -
     * that's the verified source of truth for what each feed property means.
     *
     * @param   \stdClass  $item  One raw item from Feed::getGroupEventItems().
     *
     * @return  \stdClass|null  The mapped row, or null if the item is missing something essential.
     *
     * @since   1.0.0
     */
    private function mapItem(\stdClass $item): ?\stdClass {
        $walkDate = \DateTime::createFromFormat(self::TIMEFORMAT, (string) ($item->start_date_time ?? ''));

        if ($walkDate === false) {
            return null;
        }

        $leader = '';

        if (!empty($item->walk_leader) && !\is_array($item->walk_leader) && isset($item->walk_leader->name)) {
            $leader = (string) $item->walk_leader->name;
        }

        $grade = '';

        if (!empty($item->difficulty) && isset($item->difficulty->description)) {
            $grade = (string) $item->difficulty->description;
        }

        $distanceKm = isset($item->distance_km) && $item->distance_km !== null ? (float) $item->distance_km : null;

        $location = $item->start_location ?? $item->meeting_location ?? null;
        $gridReference = '';
        $latitude = null;
        $longitude = null;

        if ($location !== null) {
            $gridReference = (string) ($location->grid_reference_6 ?? '');
            $latitude = isset($location->latitude) && $location->latitude !== null ? (float) $location->latitude : null;
            $longitude = isset($location->longitude) && $location->longitude !== null ? (float) $location->longitude : null;
        }

        $photos = [];

        if (!empty($item->media)) {
            foreach ($item->media as $mediaItem) {
                $thumb = '';
                $medium = '';

                foreach ($mediaItem->styles ?? [] as $style) {
                    if (($style->style ?? '') === 'thumbnail') {
                        $thumb = (string) $style->url;
                    }

                    if (($style->style ?? '') === 'medium') {
                        $medium = (string) $style->url;
                    }
                }

                if ($medium === '') {
                    continue;
                }

                $photos[] = (object) [
                            'url' => $medium,
                            'thumb' => $thumb !== '' ? $thumb : $medium,
                            'alt' => (string) ($mediaItem->alt ?? ''),
                ];
            }
        }

        return (object) [
                    'wm_id' => (string) $item->id,
                    'title' => (string) ($item->title ?? ''),
                    'walk_date' => $walkDate->format('Y-m-d'),
                    'walk_date_display' => $walkDate->format('D j M Y'),
                    'leader' => $leader,
                    'grade' => $grade,
                    'distance_km' => $distanceKm,
                    'distance_miles' => $distanceKm !== null ? round($distanceKm * 0.621371, 1) : null,
                    'description' => (string) ($item->description ?? ''),
                    'group_code' => (string) ($item->group_code ?? ''),
                    'group_name' => (string) ($item->group_name ?? ''),
                    'grid_reference' => $gridReference,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'photos' => $photos,
        ];
    }

    /**
     * Friendly label + Bootstrap badge class for a Past Walk's state column,
     * for the "already imported" badge on the import screen. Same value set
     * as administrator/forms/pastwalk.xml's state field (1/0/2/-2).
     *
     * @param   int  $state  The stored state value.
     *
     * @return  array  ['label' => string, 'class' => string]
     *
     * @since   1.0.0
     */
    public static function stateLabel(int $state): array {
        switch ($state) {
            case 1:
                return ['label' => Text::_('JPUBLISHED'), 'class' => 'bg-success'];

            case 0:
                return ['label' => Text::_('JUNPUBLISHED'), 'class' => 'bg-secondary'];

            case 2:
                return ['label' => Text::_('JARCHIVED'), 'class' => 'bg-info'];

            case -2:
                return ['label' => Text::_('JTRASHED'), 'class' => 'bg-danger'];

            default:
                return ['label' => (string) $state, 'class' => 'bg-secondary'];
        }
    }
}
