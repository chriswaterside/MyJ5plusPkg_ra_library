<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;

/**
 * Install/update script for com_ra_library.
 *
 * Makes sure both the "Past Walks" category tree (extension
 * com_ra_library.pastwalk) and the "Routes" category tree (extension
 * com_ra_library.route) have an Uncategorised row, the same way
 * com_content/com_contact/etc do, so a record saved without a category
 * chosen still shows up somewhere in Category Manager instead of just
 * vanishing (catid pointing at a row that doesn't exist).
 *
 * @since  1.0.0
 */
class Com_Ra_libraryInstallerScript
{
    /**
     * @param   \Joomla\CMS\Installer\InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function install($parent)
    {
        $this->createUncategorisedCategory('pastwalk');
        $this->createUncategorisedCategory('route');

        return true;
    }

    /**
     * @param   \Joomla\CMS\Installer\InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function update($parent)
    {
        $this->createUncategorisedCategory('pastwalk');
        $this->createUncategorisedCategory('route');
        $this->removeObsoleteFrontendFiles();

        return true;
    }

    /**
     * v5.0.28 shipped 6 dedicated menu item types for Past Walks/Routes
     * (Blog/List/Single) before settling, at Chris's request, on reusing
     * the single existing "Ramblers Display Option" menu type for Blog/List
     * instead (v5.0.29), then (v5.0.38) reintroducing a much simpler fixed
     * "pastwalk"/"route" single-item view/model (no Display Options row or
     * menu item needed at all) once it became clear Blog/List linking to
     * the single item needed one to always exist rather than being
     * something an admin sets up per site. Joomla's component updater only
     * adds/overwrites files listed in the current package - it never
     * deletes files that existed in an older version but are absent from
     * the new one - so anyone who installed v5.0.28 still has ITS
     * Pastwalks/Routes (Blog/List) view+model files on disk (distinct from
     * the CURRENT Pastwalk/Route singular ones, which ARE part of this
     * package and must NOT be touched here), and the old menu types keep
     * showing up in "New Menu Item" even after updating. This removes only
     * the genuinely obsolete ones, so nobody has to do it by hand. Safe to
     * run on every update - is_dir()/is_file() guards mean this is a no-op
     * for anyone who never had v5.0.28 (or has already been cleaned up by
     * a previous update).
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function removeObsoleteFrontendFiles()
    {
        $siteComponentPath = JPATH_SITE . '/components/com_ra_library';
        $adminComponentPath = JPATH_ADMINISTRATOR . '/components/com_ra_library';

        // NOTE: only the PLURAL Pastwalks/Routes (Blog/List) view+tmpl dirs
        // from v5.0.28 are obsolete. The singular Pastwalk/Route ones are
        // current, legitimate v5.0.38+ files (the fixed single-item view) -
        // do not add those here, or every update would delete its own
        // freshly-installed files straight back out again.
        $obsoleteDirs = [
            $siteComponentPath . '/src/View/Pastwalks',
            $siteComponentPath . '/src/View/Routes',
            $siteComponentPath . '/tmpl/pastwalks',
            $siteComponentPath . '/tmpl/routes',
        ];

        $obsoleteFiles = [
            $siteComponentPath . '/src/Model/PastwalksModel.php',
            $siteComponentPath . '/src/Model/RoutesModel.php',
            // Superseded by layouthelp-syntax.html + layouthelp-fields-*.html (v5.0.38).
            $adminComponentPath . '/tmpl/librarydisplay/layouthelp.html',
        ];

        foreach ($obsoleteDirs as $dir) {
            if (is_dir($dir)) {
                try {
                    Folder::delete($dir);
                } catch (\Exception $e) {
                    // Best-effort only - if this fails (permissions etc.) the
                    // old menu types just keep showing up, nothing else breaks.
                }
            }
        }

        foreach ($obsoleteFiles as $file) {
            if (is_file($file)) {
                try {
                    File::delete($file);
                } catch (\Exception $e) {
                    // Best-effort only, as above.
                }
            }
        }
    }

    /**
     * Creates the Uncategorised category for the given record type's
     * category tree, if one doesn't already exist for that extension, and
     * backfills any of that record type's rows still sitting at catid = 0
     * onto it.
     *
     * @param   string  $recordType  'pastwalk' or 'route'.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function createUncategorisedCategory($recordType)
    {
        $extension = 'com_ra_library.' . $recordType;

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if (!$this->tableHasColumn($db, '#__ra_library_routes', 'record_type')) {
            // Schema update hasn't run yet (or failed) - bail out quietly
            // rather than fatal on a query against a column that doesn't
            // exist yet. install()/update() will simply have no-op'd.
            return;
        }

        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote($extension))
            ->where($db->quoteName('title') . ' = ' . $db->quote('Uncategorised'))
            ->setLimit(1);
        $db->setQuery($query);
        $uncategorisedId = (int) $db->loadResult();

        if (!$uncategorisedId) {
            $uncategorisedId = $this->insertUncategorisedCategory($extension);
        } else {
            // This category may already exist from before the parent_id/
            // language fix below - repair it in place if so (see
            // repairUncategorisedCategory() for why this was ever wrong).
            $this->repairUncategorisedCategory($extension, $uncategorisedId);
        }

        if ($uncategorisedId) {
            // Backfill: any row of this record type saved before this
            // category existed (or saved without a category picked)
            // currently sits at catid = 0, which doesn't correspond to a
            // real row - move those onto the Uncategorised category so they
            // actually show up somewhere. Scoped to this record_type only,
            // since ra_library_routes now holds both past walks and routes.
            $update = $db->getQuery(true)
                ->update($db->quoteName('#__ra_library_routes'))
                ->set($db->quoteName('catid') . ' = ' . (int) $uncategorisedId)
                ->where($db->quoteName('catid') . ' = 0')
                ->where($db->quoteName('record_type') . ' = ' . $db->quote($recordType));
            $db->setQuery($update)->execute();
        }
    }

    /**
     * Defensive check for whether a column exists on a table, used so this
     * script can no-op cleanly instead of fataling if it somehow runs
     * before/without the schema update having applied record_type yet.
     *
     * @param   \Joomla\Database\DatabaseInterface  $db          Database driver.
     * @param   string                              $table       Table name (with #__ prefix placeholder).
     * @param   string                              $columnName  Column to check for.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    private function tableHasColumn($db, $table, $columnName)
    {
        try {
            $columns = $db->getTableColumns($table);
        } catch (\Exception $e) {
            return false;
        }

        return isset($columns[$columnName]);
    }

    /**
     * Inserts the Uncategorised category row for the given category tree.
     *
     * @param   string  $extension  The category extension string.
     *
     * @return  int  The new category id, or 0 on failure.
     *
     * @since   1.0.0
     */
    private function insertUncategorisedCategory($extension)
    {
        $category = Table::getInstance('Category');

        $data = array(
            'extension'       => $extension,
            'title'           => 'Uncategorised',
            'description'     => '',
            'published'       => 1,
            'access'          => 1,
            'language'        => '*',
            'level'           => 1,
            'path'            => 'uncategorised',
            'params'          => '{}',
            'metadesc'        => '',
            'metakey'         => '',
            'metadata'        => '{}',
            'created_time'    => Factory::getDate()->toSql(),
            'created_user_id' => (int) (Factory::getApplication()->getIdentity()->id ?? 0),
            'rules'           => array(),
            'parent_id'       => 1,
        );

        if (!$category->bind($data)) {
            Factory::getApplication()->enqueueMessage(
                'Ra_library (' . $extension . '): could not bind the default Uncategorised category (' . $category->getError() . ')',
                'warning'
            );

            return 0;
        }

        // IMPORTANT: binding 'parent_id' above only sets the plain column
        // value - Joomla's Table\Nested (which Table\Category extends) does
        // NOT use that to decide where to insert a new node. It uses an
        // internal _location_id, only set via setLocation(), and defaults
        // to null when unset. In Nested::store(), `null >= 0` and
        // `null == 0` are both true in PHP, so without this call the node
        // was silently inserted as a brand new ROOT-level node (parent_id
        // forced to 0, level 0) regardless of what was bound above - which
        // is exactly what caused the phantom duplicate "No parent" entries
        // in every category's Parent picker. This call is what actually
        // tells store() to place the new row under category id 1 (the true
        // site root).
        $category->setLocation(1, 'last-child');

        if (!$category->check()) {
            Factory::getApplication()->enqueueMessage(
                'Ra_library (' . $extension . '): could not validate the default Uncategorised category (' . $category->getError() . ')',
                'warning'
            );

            return 0;
        }

        if (!$category->store(true)) {
            Factory::getApplication()->enqueueMessage(
                'Ra_library (' . $extension . '): could not save the default Uncategorised category (' . $category->getError() . ')',
                'warning'
            );

            return 0;
        }

        return (int) $category->id;
    }

    /**
     * Repairs an Uncategorised category created by an earlier version of
     * this script that had the setLocation() bug described in
     * insertUncategorisedCategory() above - i.e. one that ended up sitting
     * at parent_id = 0 / level = 0 (a second root) instead of properly
     * nested under category id 1, and/or with language left as '' instead
     * of '*'. Safe to run repeatedly: once parent_id is correctly 1, this
     * is a no-op.
     *
     * @param   string  $extension   The category extension string (for messages only).
     * @param   int     $categoryId  The id of the Uncategorised category to check/repair.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function repairUncategorisedCategory($extension, $categoryId)
    {
        $category = Table::getInstance('Category');

        if (!$category->load($categoryId)) {
            return;
        }

        $needsRepair = ((int) $category->parent_id !== 1) || ($category->language !== '*');

        if (!$needsRepair) {
            return;
        }

        // Table\Nested::check() validates the object's CURRENT parent_id
        // property (it must be non-zero and reference an existing row) -
        // it doesn't know about the pending move requested via
        // setLocation() below, so the broken parent_id has to be corrected
        // here too, not just via setLocation().
        $category->parent_id = 1;
        $category->language = '*';

        // This is what actually performs the tree surgery: since $category
        // already has a primary key (existing row), Nested::store() will
        // call moveByReference(1, 'last-child', ...) to properly relocate
        // this node (and recompute lft/rgt/level) under category id 1,
        // rather than leaving it as a disconnected second root.
        $category->setLocation(1, 'last-child');

        if (!$category->check() || !$category->store(true)) {
            Factory::getApplication()->enqueueMessage(
                'Ra_library (' . $extension . '): could not repair the misplaced Uncategorised category (' . $category->getError() . ')',
                'warning'
            );
        }
    }
}
