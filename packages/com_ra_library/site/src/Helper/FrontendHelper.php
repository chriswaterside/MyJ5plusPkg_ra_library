<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

/**
 * Shared DB access for the front-end Past Walks / Routes views (List, Blog,
 * Single) - both record types live in the same #__ra_library_routes table,
 * distinguished by record_type, exactly like the admin side.
 *
 * @since  1.0.0
 */
class FrontendHelper {

    /**
     * Fetch one saved Display Options configuration row.
     *
     * @param   int  $displayId  The #__ra_library_displays id (a menu item's "Display Configuration" param).
     *
     * @return  \stdClass|null  ->displayoption (string) and ->options (decoded stdClass), or null if not found.
     *
     * @since   1.0.0
     */
    public static function getDisplayConfig(int $displayId): ?\stdClass {
        if ($displayId <= 0) {
            return null;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
                ->select(['displayoption', 'options'])
                ->from($db->quoteName('#__ra_library_displays'))
                ->where($db->quoteName('id') . ' = ' . $displayId)
                ->where($db->quoteName('state') . ' = 1');
        $db->setQuery($query);
        $row = $db->loadObject();

        if (!$row) {
            return null;
        }

        $row->options = json_decode((string) $row->options) ?: new \stdClass();

        return $row;
    }

    /**
     * Count published items of one record type, optionally within one category.
     *
     * @param   string  $recordType  'pastwalk' or 'route'.
     * @param   int     $catid       0 = all categories.
     *
     * @return  int
     *
     * @since   1.0.0
     */
    public static function getPublishedItemCount(string $recordType, int $catid = 0): int {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__ra_library_routes'))
                ->where($db->quoteName('record_type') . ' = ' . $db->quote($recordType))
                ->where($db->quoteName('state') . ' = 1')
                // Imported-but-not-yet-reviewed items are published (so they can
                // be found/edited in the admin) but stay off every front-end
                // display until someone opens and saves them, clearing the flag.
                ->where($db->quoteName('needs_review') . ' = 0');

        if ($catid > 0) {
            $query->where($db->quoteName('catid') . ' = ' . $catid);
        }

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    /**
     * Fetch a page of published items of one record type, newest walk_date
     * first for past walks, alphabetical for routes (routes have no date).
     *
     * @param   string  $recordType  'pastwalk' or 'route'.
     * @param   int     $catid       0 = all categories.
     * @param   int     $limitstart  Pagination offset.
     * @param   int     $limit       Page size (0 = all).
     *
     * @return  array  Rows with an extra ->category_title property joined in.
     *
     * @since   1.0.0
     */
    public static function getPublishedItems(string $recordType, int $catid = 0, int $limitstart = 0, int $limit = 10): array {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
                ->select('r.*, c.title AS category_title')
                ->from($db->quoteName('#__ra_library_routes', 'r'))
                ->leftJoin($db->quoteName('#__categories', 'c') . ' ON c.id = r.catid')
                ->where('r.' . $db->quoteName('record_type') . ' = ' . $db->quote($recordType))
                ->where('r.' . $db->quoteName('state') . ' = 1')
                // See getPublishedItemCount() - keep not-yet-reviewed imports off
                // the front end until someone has opened and saved them.
                ->where('r.' . $db->quoteName('needs_review') . ' = 0');

        if ($catid > 0) {
            $query->where('r.' . $db->quoteName('catid') . ' = ' . $catid);
        }

        if ($recordType === 'pastwalk') {
            $query->order('r.walk_date DESC, r.id DESC');
        } else {
            $query->order('r.title ASC');
        }

        $db->setQuery($query, $limitstart, $limit ?: 0);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Fetch one published item by id, of the given record type.
     *
     * @param   string  $recordType  'pastwalk' or 'route'.
     * @param   int     $id          The record id.
     *
     * @return  \stdClass|null
     *
     * @since   1.0.0
     */
    public static function getPublishedItem(string $recordType, int $id): ?\stdClass {
        if ($id <= 0) {
            return null;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
                ->select('r.*, c.title AS category_title')
                ->from($db->quoteName('#__ra_library_routes', 'r'))
                ->leftJoin($db->quoteName('#__categories', 'c') . ' ON c.id = r.catid')
                ->where('r.' . $db->quoteName('id') . ' = ' . $id)
                ->where('r.' . $db->quoteName('record_type') . ' = ' . $db->quote($recordType))
                ->where('r.' . $db->quoteName('state') . ' = 1')
                // See getPublishedItemCount() - a not-yet-reviewed import's own
                // single-item page isn't reachable either, for consistency with
                // it being absent from every list/blog/table.
                ->where('r.' . $db->quoteName('needs_review') . ' = 0');
        $db->setQuery($query);

        return $db->loadObject() ?: null;
    }

    /**
     * Fetch the child categories of one category (or the top-level
     * categories if $parentCatId is 0), published only, in menu order -
     * used by the "Category Tree" display to drill down a level at a time.
     *
     * @param   string  $recordType   'pastwalk' or 'route'.
     * @param   int     $parentCatId  0 = top level (the extension's real root row, whatever its id is).
     *
     * @return  array  Rows with id, title, description, plus an item_count of published items directly in that category.
     *
     * @since   1.0.0
     */
    public static function getChildCategories(string $recordType, int $parentCatId = 0): array {
        $extension = 'com_ra_library.' . $recordType;

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
                ->select(['c.id', 'c.title', 'c.description'])
                ->from($db->quoteName('#__categories', 'c'))
                ->where('c.' . $db->quoteName('extension') . ' = ' . $db->quote($extension))
                ->where('c.' . $db->quoteName('published') . ' = 1')
                ->order('c.lft ASC');

        if ($parentCatId > 0) {
            $query->where('c.' . $db->quoteName('parent_id') . ' = ' . $parentCatId);
        } else {
            // Top level - the real root row's own id varies per install, so
            // use level=1 (the first real level under any extension's root)
            // rather than guessing/looking up the root id.
            $query->where('c.' . $db->quoteName('level') . ' = 1');
        }

        $db->setQuery($query);
        $categories = $db->loadObjectList() ?: [];

        foreach ($categories as $category) {
            $category->item_count = self::getPublishedItemCount($recordType, (int) $category->id);
            $category->total_item_count = self::getCategoryTotalItemCount($recordType, (int) $category->id);
            $category->featured_image_row = self::getCategoryBranchFeaturedImage($recordType, (int) $category->id);
        }

        return $categories;
    }

    /**
     * All category ids in one branch - the category itself plus every
     * descendant, however many levels deep - found in a single range query
     * against Joomla's nested-set lft/rgt columns rather than walking
     * parent_id level by level.
     *
     * @param   string  $recordType  'pastwalk' or 'route'.
     * @param   int     $catid       The branch's own top category id.
     *
     * @return  int[]
     *
     * @since   1.0.0
     */
    private static function getCategoryBranchIds(string $recordType, int $catid): array {
        if ($catid <= 0) {
            return [];
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
                ->select(['lft', 'rgt'])
                ->from($db->quoteName('#__categories'))
                ->where($db->quoteName('id') . ' = ' . $catid);
        $db->setQuery($query);
        $range = $db->loadObject();

        if (!$range) {
            return [];
        }

        $extension = 'com_ra_library.' . $recordType;

        $query = $db->getQuery(true)
                ->select('c.id')
                ->from($db->quoteName('#__categories', 'c'))
                ->where('c.' . $db->quoteName('extension') . ' = ' . $db->quote($extension))
                ->where('c.' . $db->quoteName('lft') . ' >= ' . (int) $range->lft)
                ->where('c.' . $db->quoteName('rgt') . ' <= ' . (int) $range->rgt);
        $db->setQuery($query);

        return array_map('intval', $db->loadColumn() ?: []);
    }

    /**
     * Total published items anywhere in one branch (the category itself
     * plus every descendant category) - "total items in that branch", as
     * distinct from getPublishedItemCount()'s single-category count ("number
     * of items at that level").
     *
     * @param   string  $recordType  'pastwalk' or 'route'.
     * @param   int     $catid       The branch's own top category id.
     *
     * @return  int
     *
     * @since   1.0.0
     */
    public static function getCategoryTotalItemCount(string $recordType, int $catid): int {
        $catIds = self::getCategoryBranchIds($recordType, $catid);

        if (empty($catIds)) {
            return 0;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__ra_library_routes'))
                ->where($db->quoteName('record_type') . ' = ' . $db->quote($recordType))
                ->where($db->quoteName('state') . ' = 1')
                // See getPublishedItemCount() - keep the category tree's own
                // counts consistent with what's actually shown in its lists.
                ->where($db->quoteName('needs_review') . ' = 0')
                ->where($db->quoteName('catid') . ' IN (' . implode(',', $catIds) . ')');
        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    /**
     * The featured image to represent one branch in the Category Tree
     * display: walks/routes in the branch's own natural order (newest walk
     * first, alphabetical for routes), and within the first item that has
     * any gallery photo at all, its own featured photo (or its first photo,
     * if none is marked featured) - i.e. "the featured image from the first
     * item with a featured item", read across the whole branch, not just
     * this one category.
     *
     * @param   string  $recordType  'pastwalk' or 'route'.
     * @param   int     $catid       The branch's own top category id.
     *
     * @return  \stdClass|null  ->thumbnail_path, ->caption, or null if nothing in the branch has any photo.
     *
     * @since   1.0.0
     */
    public static function getCategoryBranchFeaturedImage(string $recordType, int $catid): ?\stdClass {
        $catIds = self::getCategoryBranchIds($recordType, $catid);

        if (empty($catIds)) {
            return null;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
                ->select(['i.thumbnail_path', 'i.caption'])
                ->from($db->quoteName('#__ra_library_routes', 'r'))
                ->innerJoin($db->quoteName('#__ra_library_images', 'i') . ' ON i.record_id = r.id')
                ->where('r.' . $db->quoteName('record_type') . ' = ' . $db->quote($recordType))
                ->where('r.' . $db->quoteName('state') . ' = 1')
                // See getPublishedItemCount() - don't pull a category's featured
                // image from an item that isn't shown anywhere itself yet.
                ->where('r.' . $db->quoteName('needs_review') . ' = 0')
                ->where('r.' . $db->quoteName('catid') . ' IN (' . implode(',', $catIds) . ')');

        if ($recordType === 'pastwalk') {
            $query->order('r.walk_date DESC, r.id DESC, i.featured DESC, i.ordering ASC');
        } else {
            $query->order('r.title ASC, i.featured DESC, i.ordering ASC');
        }

        $db->setQuery($query, 0, 1);

        return $db->loadObject() ?: null;
    }

    /**
     * Fetch one category's own title/parent_id/level - used by the
     * "Category Tree" display for the page heading and the "up one level"
     * link.
     *
     * @param   int  $catid  The category id.
     *
     * @return  \stdClass|null  ->id, ->title, ->parent_id, ->level, or null if not found/not published.
     *
     * @since   1.0.0
     */
    public static function getCategoryInfo(int $catid): ?\stdClass {
        if ($catid <= 0) {
            return null;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
                ->select(['id', 'title', 'parent_id', 'level'])
                ->from($db->quoteName('#__categories'))
                ->where($db->quoteName('id') . ' = ' . $catid)
                ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);

        return $db->loadObject() ?: null;
    }

    /**
     * Walk a category up to the root, returning the chain root-first,
     * leaf-last - used for the Single Item page's category hierarchy
     * breadcrumb. Stops at level 1 (the topmost real category) so the
     * invisible Joomla ROOT category (id=1, level 0) is never included.
     *
     * @param   int  $catid  The leaf category id (the item's own catid).
     *
     * @return  array  \stdClass rows (->id, ->title, ->parent_id, ->level), root first.
     *
     * @since   1.0.0
     */
    public static function getCategoryBreadcrumbChain(int $catid): array {
        $chain = [];
        $guard = 0;

        while ($catid > 0 && $guard < 20) {
            $info = self::getCategoryInfo($catid);

            if (!$info || (int) $info->level < 1) {
                break;
            }

            array_unshift($chain, $info);
            $catid = (int) $info->parent_id;
            $guard++;
        }

        return $chain;
    }

    /**
     * Find the id of the first published Category Tree Display Options row
     * for a record type - the page the breadcrumb links point at. There's
     * no requirement for exactly one to exist; if the admin hasn't set one
     * up (or has disabled it), this returns null and the breadcrumb falls
     * back to plain (non-linked) text.
     *
     * @param   string  $recordType  'pastwalk' or 'route'.
     *
     * @return  int|null
     *
     * @since   1.0.0
     */
    public static function getCategoryTreeDisplayId(string $recordType): ?int {
        static $cache = [];

        if (array_key_exists($recordType, $cache)) {
            return $cache[$recordType];
        }

        $displayOption = $recordType === 'route' ? 'routes_categorytree' : 'pastwalks_categorytree';

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__ra_library_displays'))
                ->where($db->quoteName('displayoption') . ' = ' . $db->quote($displayOption))
                ->where($db->quoteName('state') . ' = 1')
                ->order($db->quoteName('id') . ' ASC');
        $db->setQuery($query, 0, 1);
        $id = (int) $db->loadResult();

        $cache[$recordType] = $id > 0 ? $id : null;

        return $cache[$recordType];
    }

    /**
     * Build the front-end link to a specific category on that record
     * type's Category Tree display (see getCategoryTreeDisplayId()). Null
     * if there's no such display configured to link to.
     *
     * @param   string  $recordType  'pastwalk' or 'route'.
     * @param   int     $catid       The category to link to.
     * @param   bool    $xhtml       Pass through to Route::_().
     *
     * @return  string|null
     *
     * @since   1.0.0
     */
    public static function getCategoryTreeLinkUrl(string $recordType, int $catid, bool $xhtml = true): ?string {
        $displayId = self::getCategoryTreeDisplayId($recordType);

        if ($displayId === null) {
            return null;
        }

        return Route::_(
                        'index.php?option=com_ra_library&view=librarydisplay&id=' . $displayId . '&catid=' . $catid,
                        $xhtml
                );
    }

    /**
     * Build the front-end link to browse to a given category position on
     * the Category Tree display - just the CURRENT page's own URL with its
     * "catid" query param set/replaced, so this works automatically
     * whichever menu item/display id is showing the tree, with no need to
     * know that id here.
     *
     * @param   int  $catid  0 = top level.
     *
     * @return  string
     *
     * @since   1.0.0
     */
    public static function getCategoryTreeUrl(int $catid = 0): string {
        $uri = clone \Joomla\CMS\Uri\Uri::getInstance();

        if ($catid > 0) {
            $uri->setVar('catid', $catid);
        } else {
            $uri->delVar('catid');
        }

        return $uri->toString(['path', 'query']);
    }

    /**
     * Build the front-end link to a single record's page. This is a fixed,
     * always-available component view (view=pastwalk / view=route) - not
     * routed through a Display Options row or menu item at all, since the
     * single-item page has no per-page configuration left to pick (it
     * always renders with the one global item template - see
     * DisplayHelper::getGlobalItemTemplateIntro()/More()). Blog and List
     * can therefore always link straight to it with no setup required.
     *
     * @param   string  $recordType  'pastwalk' or 'route'.
     * @param   int     $recordId    The past walk / route id.
     * @param   bool    $xhtml       Pass through to Route::_().
     *
     * @return  string
     *
     * @since   1.0.0
     */
    public static function getItemUrl(string $recordType, int $recordId, bool $xhtml = true): string {
        $view = $recordType === 'route' ? 'route' : 'pastwalk';

        return Route::_('index.php?option=com_ra_library&view=' . $view . '&id=' . $recordId, $xhtml);
    }
}
