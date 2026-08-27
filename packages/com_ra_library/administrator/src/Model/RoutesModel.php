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
 * Methods supporting a list of Route records.
 *
 * Routes share the ra_library_routes table with Past Walks - this list is
 * scoped to record_type = 'route'. Unlike Past Walks there's no meaningful
 * date on a route, so there are no day/month/year filters or a date column
 * here - filtering is by category, national grade and search instead.
 *
 * @since  1.0.0
 */
class RoutesModel extends ListModel {

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
                'catid', 'a.catid',
                'national_grade', 'a.national_grade',
                'distance_km', 'a.distance_km',
                // Not orderable columns - present so ListModel::getActiveFilters()
                // reports them as active in the filter panel (see PastwalksModel
                // for the fuller explanation of why this is needed).
                'category_id',
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
        parent::populateState('a.title', 'ASC');

        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $categoryId = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
        $this->setState('filter.category_id', $categoryId);

        $nationalGrade = $this->getUserStateFromRequest($this->context . '.filter.national_grade', 'filter_national_grade');
        $this->setState('filter.national_grade', $nationalGrade);
    }

    /**
     * Method to get the data that should be injected in the filter form.
     *
     * Recomputes list.fullordering from the real current state - see
     * PastwalksModel::loadFormData() for why this override is necessary
     * (ListModel's own loadFormData() doesn't do this itself).
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
        $id .= ':' . $this->getState('filter.national_grade');

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

        // Shared table with Past Walks - only ever list actual routes here.
        $query->where('a.record_type = ' . $db->quote('route'));

        // Category title, for display
        $query->select('c.title AS category_title');
        $query->join('LEFT', '#__categories AS c ON c.id = a.catid');

        // Attachment counts, for the list's compact "Files" column - see
        // PastwalksModel::getListQuery() for why these are scalar
        // subqueries rather than a join+GROUP BY.
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

        // Filter by national grade
        $nationalGrade = $this->getState('filter.national_grade');

        if (!empty($nationalGrade)) {
            $query->where('a.national_grade = ' . $db->quote($nationalGrade));
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
        $orderCol = $this->state->get('list.ordering', 'a.title');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        if ($orderCol && $orderDirn) {
            $query->order($db->escape($orderCol . ' ' . $orderDirn) . ', ' . $db->quoteName('a.title') . ' ASC');
        }

        return $query;
    }
}
