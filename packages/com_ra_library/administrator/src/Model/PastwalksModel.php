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

use \Joomla\CMS\MVC\Model\ListModel;
use \Joomla\CMS\Factory;
use \Joomla\Database\DatabaseInterface;

/**
 * Methods supporting a list of Pastwalk records.
 *
 * @since  1.0.0
 */
class PastwalksModel extends ListModel {

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since   1.0.0
     */
    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'state', 'a.state',
                'ordering', 'a.ordering',
                'created_by', 'a.created_by',
                'modified_by', 'a.modified_by',
                'title', 'a.title',
                'walk_date', 'a.walk_date',
                'catid', 'a.catid',
                'national_grade', 'a.national_grade',
                // These aren't orderable columns - they're here because
                // ListModel::getActiveFilters() (and, via that, the search
                // tools panel's "filters are active" display) only reports
                // a filter as active if its name is in this same array.
                // Without these, filtering still worked (getListQuery()
                // reads the state directly) but the panel had no idea any
                // filter was set, so the dropdowns looked empty/reverted.
                'category_id', 'day_of_week', 'month', 'year', 'needs_review',
                // Allows ordering by the day-of-week SQL expression too.
                'DAYOFWEEK(a.walk_date)',
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   Elements order
     * @param   string  $direction  Order direction
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function populateState($ordering = null, $direction = null) {
        parent::populateState('a.walk_date', 'DESC');

        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $categoryId = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
        $this->setState('filter.category_id', $categoryId);

        $dayOfWeek = $this->getUserStateFromRequest($this->context . '.filter.day_of_week', 'filter_day_of_week');
        $this->setState('filter.day_of_week', $dayOfWeek);

        $month = $this->getUserStateFromRequest($this->context . '.filter.month', 'filter_month');
        $this->setState('filter.month', $month);

        $year = $this->getUserStateFromRequest($this->context . '.filter.year', 'filter_year');
        $this->setState('filter.year', $year);

        $needsReview = $this->getUserStateFromRequest($this->context . '.filter.needs_review', 'filter_needs_review');
        $this->setState('filter.needs_review', $needsReview);
    }

    /**
     * Method to get the data that should be injected in the filter form.
     *
     * ListModel::loadFormData() only pre-fills list.ordering/list.direction
     * separately - it never reconstructs the combined list.fullordering
     * value the "Sort by" dropdown actually binds to. Left alone, that
     * field keeps resubmitting whatever raw value happened to be sitting in
     * the session from the last full-page submit (which may not match the
     * true current ordering), so picking a different filter - day of week,
     * month, category, anything that re-submits the whole form - silently
     * puts the sort back to the default. Recomputing it here from the real
     * current state fixes that.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.0.0
     */
    protected function loadFormData() {
        $data = parent::loadFormData();

        $fullOrdering = $this->getState('list.ordering') . ' ' . $this->getState('list.direction');

        if (is_array($data->list ?? null)) {
            $data->list['fullordering'] = $fullOrdering;
        } else {
            if (!isset($data->list)) {
                $data->list = new \stdClass();
            }

            $data->list->fullordering = $fullOrdering;
        }

        return $data;
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string A store id.
     *
     * @since   1.0.0
     */
    protected function getStoreId($id = '') {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.state');
        $id .= ':' . $this->getState('filter.category_id');
        $id .= ':' . $this->getState('filter.day_of_week');
        $id .= ':' . $this->getState('filter.month');
        $id .= ':' . $this->getState('filter.year');
        $id .= ':' . $this->getState('filter.needs_review');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  \Joomla\Database\DatabaseQuery
     *
     * @since   1.0.0
     */
    protected function getListQuery() {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($this->getState('list.select', 'DISTINCT a.*'));
        $query->from('`#__ra_library_routes` AS a');

        // Shared table with Routes - only ever list actual past walks here.
        $query->where('a.record_type = ' . $db->quote('pastwalk'));

        // Category title, for display
        $query->select('c.title AS category_title');
        $query->join('LEFT', '#__categories AS c ON c.id = a.catid');

        // Attachment counts, for the list's compact "Files" column - scalar
        // subqueries rather than a join+GROUP BY, since three separate
        // counts (images / gpx / documents) from two different tables would
        // otherwise need joins that multiply rows against each other.
        $query->select(
            '(SELECT COUNT(*) FROM ' . $db->quoteName('#__ra_library_images') . ' img WHERE img.record_id = a.id) AS image_count'
        );
        $query->select(
            '(SELECT COUNT(*) FROM ' . $db->quoteName('#__ra_library_attachments')
            . ' att WHERE att.record_id = a.id AND att.attachment_type = ' . $db->quote('gpx') . ') AS gpx_count'
        );
        $query->select(
            '(SELECT COUNT(*) FROM ' . $db->quoteName('#__ra_library_attachments')
            . ' att WHERE att.record_id = a.id AND att.attachment_type = ' . $db->quote('document') . ') AS document_count'
        );

        // Checked-out user, for display
        $query->select('uc.name AS uEditor');
        $query->join('LEFT', '#__users AS uc ON uc.id = a.checked_out');

        // Filter by published state
        $published = $this->getState('filter.state');

        if (is_numeric($published)) {
            $query->where('a.state = ' . (int) $published);
        } elseif (empty($published)) {
            $query->where('(a.state IN (0, 1))');
        }

        // Filter by category
        $categoryId = $this->getState('filter.category_id');

        if (is_numeric($categoryId)) {
            $query->where('a.catid = ' . (int) $categoryId);
        }

        // Filter by day of week: 0 = Sunday .. 6 = Saturday (PHP date('w') convention),
        // MySQL DAYOFWEEK() returns 1 = Sunday .. 7 = Saturday, hence the +1.
        $dayOfWeek = $this->getState('filter.day_of_week');

        if ($dayOfWeek !== null && $dayOfWeek !== '') {
            $query->where('DAYOFWEEK(a.walk_date) = ' . ((int) $dayOfWeek + 1));
        }

        // Filter by month
        $month = $this->getState('filter.month');

        if (!empty($month)) {
            $query->where('MONTH(a.walk_date) = ' . (int) $month);
        }

        // Filter by year
        $year = $this->getState('filter.year');

        if (!empty($year) && is_numeric($year)) {
            $query->where('YEAR(a.walk_date) = ' . (int) $year);
        }

        // Filter by the "needs review" flag (walks brought in via Import
        // from Walks Manager that haven't been opened/saved since).
        $needsReview = $this->getState('filter.needs_review');

        if ($needsReview !== null && $needsReview !== '') {
            $query->where('a.needs_review = 1');
        }

        // Filter by search in title, or id: prefix
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where('a.title LIKE ' . $search);
            }
        }

        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'a.walk_date');
        $orderDirn = $this->state->get('list.direction', 'DESC');

        if ($orderCol && $orderDirn) {
            $query->order($db->escape($orderCol . ' ' . $orderDirn) . ', ' . $db->quoteName('a.title') . ' ASC');
        }

        return $query;
    }
}
