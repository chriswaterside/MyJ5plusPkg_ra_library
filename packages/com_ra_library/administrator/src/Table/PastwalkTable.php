<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\Table;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table as Table;
use \Joomla\Database\DatabaseDriver;
use Ramblers\Component\Ra_library\Administrator\Helper\Ra_libraryHelper;

/**
 * Pastwalk table.
 *
 * Phase 1: a plain content table (no per-item asset tree/versioning/tagging
 * yet - those can be layered on later if per-editor ACL on individual walks
 * is ever needed; for now access is controlled at the component level via
 * the pastwalks.* actions in access.xml).
 *
 * @since 1.0.0
 */
class PastwalkTable extends Table {

    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    boolean
     * @since  4.0.0
     */
    protected $_supportNullValue = true;

    /**
     * Constructor
     *
     * @param   JDatabase  &$db  A database connector object
     */
    public function __construct(DatabaseDriver $db) {
        $this->typeAlias = 'com_ra_library.pastwalk';
        parent::__construct('#__ra_library_routes', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    /**
     * Get the type alias for the history/content_types mapping
     *
     * @return  string
     *
     * @since   1.0.0
     */
    public function getTypeAlias() {
        if (!empty($this->record_type)) {
            return 'com_ra_library.' . $this->record_type;
        }

        return $this->typeAlias;
    }

    /**
     * Overloaded bind function - stamps created/created_by on first save.
     *
     * @param   array  $array   Named array
     * @param   mixed  $ignore  Optional array or list of parameters to ignore
     *
     * @return  boolean  True on success.
     *
     * @since   1.0.0
     */
    public function bind($array, $ignore = '') {
        $user = Factory::getApplication()->getIdentity();
        $input = Factory::getApplication()->input;
        $task = $input->getString('task', '');

        if (empty($array['id']) && empty($array['created_by'])) {
            $array['created_by'] = $user->id;
        }

        if (empty($array['id']) && empty($array['created'])) {
            $array['created'] = Factory::getDate()->toSql();
        }

        if ($task === 'apply' || $task === 'save') {
            $array['modified_by'] = $user->id;
            $array['modified'] = Factory::getDate()->toSql();
        }

        // Optional numeric columns: an empty form field arrives here as ''
        // (not null), which MySQL strict mode rejects for DECIMAL columns.
        // Coerce blanks to null so these stay genuinely optional.
        foreach (['distance_km', 'start_latitude', 'start_longitude'] as $nullableNumeric) {
            if (isset($array[$nullableNumeric]) && $array[$nullableNumeric] === '') {
                $array[$nullableNumeric] = null;
            }
        }

        // start_grid_reference is the only location field the admin form
        // actually shows (same "type a grid ref, not lat/long" convention
        // as the image gallery) - start_latitude/start_longitude are
        // recalculated from it on every save, here, rather than trusting
        // whatever (if anything) was already in those columns. Walks
        // imported from Walks Manager already arrive with a grid reference
        // set (see ImportController), so this recalculation is a no-op for
        // them, just keeping the two forms of storing the same location in
        // sync for hand-entered/edited records too.
        if (array_key_exists('start_grid_reference', $array)) {
            [$startLatitude, $startLongitude] = Ra_libraryHelper::latLongFromGridReference(
                trim((string) $array['start_grid_reference'])
            );
            $array['start_latitude'] = $startLatitude;
            $array['start_longitude'] = $startLongitude;
        }

        // walks_manager_id is nullable but carries a UNIQUE index (to stop a
        // walk being imported twice) - '' is not null, so a second blank
        // manually-created walk collides with the first on that index.
        if (isset($array['walks_manager_id']) && $array['walks_manager_id'] === '') {
            $array['walks_manager_id'] = null;
        }

        // catid is NOT NULL DEFAULT 0 (no category selected = uncategorised,
        // not null) - same '' vs proper-type issue as above, different fix.
        // Rather than leaving it as the meaningless 0, point it at the real
        // "Uncategorised" category row (script.php creates one on install/
        // update) so the walk still shows up under something in Category
        // Manager instead of just disappearing.
        if (isset($array['catid']) && $array['catid'] === '') {
            $recordType = $array['record_type'] ?? $this->record_type ?? 'pastwalk';
            $extension = 'com_ra_library.' . $recordType;

            $db = $this->getDbo();
            $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__categories'))
                ->where($db->quoteName('extension') . ' = ' . $db->quote($extension))
                ->where($db->quoteName('title') . ' = ' . $db->quote('Uncategorised'))
                ->setLimit(1);
            $db->setQuery($query);
            $array['catid'] = (int) $db->loadResult();
        }

        // walk_date is nullable, but MySQL strict mode still rejects '' for
        // a DATE column (it wants an actual date or a real NULL). The form
        // marks this required so it should always be set, but guard anyway.
        if (isset($array['walk_date']) && $array['walk_date'] === '') {
            $array['walk_date'] = null;
        }

        return parent::bind($array, $ignore);
    }

    /**
     * Overloaded check function - ensures ordering is set for new rows.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function check() {
        if (property_exists($this, 'ordering') && $this->id == 0) {
            $this->ordering = self::getNextOrder();
        }

        if (empty($this->title)) {
            $this->setError(\Joomla\CMS\Language\Text::_('COM_RA_LIBRARY_PASTWALK_FIELD_TITLE_LABEL') . ' ' . \Joomla\CMS\Language\Text::_('JLIB_DATABASE_ERROR_MUSTNOTBEBLANK'));

            return false;
        }

        return parent::check();
    }
}
