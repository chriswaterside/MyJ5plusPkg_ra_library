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
class Com_Ra_libraryInstallerScript {

    /**
     * @param   \Joomla\CMS\Installer\InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function install($parent) {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->ensureCoreTablesExist($db);

        $this->createUncategorisedCategory('pastwalk');
        $this->createUncategorisedCategory('route');

        return true;
    }

    /**
     * Runs after Joomla has installed/updated the component (and rebuilt its
     * admin menu) - used to group the admin submenu, see groupAdminMenu().
     *
     * @param   string                                    $type    install, update, discover_install...
     * @param   \Joomla\CMS\Installer\InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean
     *
     * @since   1.0.14
     */
    public function postflight($type, $parent) {
        if (\in_array($type, ['install', 'update', 'discover_install'], true)) {
            try {
                $this->groupAdminMenu();
            } catch (\Throwable $e) {
                // Purely cosmetic - on any problem the flat, already
                // logically-ordered submenu from the manifest simply stays.
            }
        }

        return true;
    }

    /**
     * Label the groups in the flat admin submenu the installer builds from
     * the manifest, so it reads
     *
     *   Display Options
     *   PAST WALKS   (label)
     *     Past Walks | Categories | Fields | Field Groups
     *   ROUTES       (label)
     *     Routes     | Categories | Fields | Field Groups
     *
     * using Joomla's own menu "separator with a title" item (rendered by the
     * administrator sidebar as a small group label), and shortens the item
     * titles now the label says which section they belong to.
     *
     * Deliberately NOT nested headings: com_ra_library's items already sit
     * on the sidebar's third level (Components > RA Library > item), and the
     * sidebar neither styles nor supports a fourth level - a heading there
     * gets a dropdown toggle with no dropdown (JavaScript error on click) and
     * its children lose their indent.
     *
     * Runs in postflight, i.e. after Joomla has (re)built the flat menu - which
     * it does afresh on every install/update, so this is re-applied each time.
     * The separators carry this component's component_id, so Joomla removes
     * them together with the rest of the menu on update/uninstall. Items are
     * matched by their link; anything not found is simply left alone.
     *
     * @return  void
     *
     * @since   1.0.14
     */
    private function groupAdminMenu() {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $extensionId = (int) $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('extension_id'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_ra_library'))
        )->loadResult();

        if (!$extensionId) {
            return;
        }

        $topId = (int) $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__menu'))
                ->where($db->quoteName('client_id') . ' = 1')
                ->where($db->quoteName('menutype') . ' = ' . $db->quote('main'))
                ->where($db->quoteName('parent_id') . ' = 1')
                ->where($db->quoteName('component_id') . ' = ' . $extensionId)
                ->order($db->quoteName('id') . ' DESC')
        )->loadResult();

        if (!$topId) {
            return;
        }

        $groups = [
            ['title' => 'Past Walks', 'alias' => 'ra-group-pastwalks', 'items' => [
                'index.php?option=com_ra_library&view=pastwalks' => 'Past Walks',
                'index.php?option=com_categories&extension=com_ra_library.pastwalk' => 'Categories',
                'index.php?option=com_fields&view=fields&context=com_ra_library.pastwalk' => 'Fields',
                'index.php?option=com_fields&view=groups&context=com_ra_library.pastwalk' => 'Field Groups',
            ]],
            ['title' => 'Routes', 'alias' => 'ra-group-routes', 'items' => [
                'index.php?option=com_ra_library&view=routes' => 'Routes',
                'index.php?option=com_categories&extension=com_ra_library.route' => 'Categories',
                'index.php?option=com_fields&view=fields&context=com_ra_library.route' => 'Fields',
                'index.php?option=com_fields&view=groups&context=com_ra_library.route' => 'Field Groups',
            ]],
        ];

        foreach ($groups as $group) {
            $firstId = 0;

            foreach ($group['items'] as $link => $newTitle) {
                $id = (int) $db->setQuery(
                    $db->getQuery(true)
                        ->select($db->quoteName('id'))
                        ->from($db->quoteName('#__menu'))
                        ->where($db->quoteName('client_id') . ' = 1')
                        ->where($db->quoteName('menutype') . ' = ' . $db->quote('main'))
                        ->where($db->quoteName('component_id') . ' = ' . $extensionId)
                        ->where($db->quoteName('parent_id') . ' = ' . $topId)
                        ->where($db->quoteName('link') . ' = ' . $db->quote($link))
                        ->order($db->quoteName('id') . ' DESC')
                )->loadResult();

                if (!$id) {
                    continue;
                }

                if (!$firstId) {
                    $firstId = $id;
                }

                $db->setQuery(
                    $db->getQuery(true)
                        ->update($db->quoteName('#__menu'))
                        ->set($db->quoteName('title') . ' = ' . $db->quote($newTitle))
                        ->where($db->quoteName('id') . ' = ' . $id)
                )->execute();
            }

            if (!$firstId) {
                continue;
            }

            // The label: a titled separator placed just before the group's first item.
            // (text_separator = 1 is what makes the sidebar show the separator's title.)
            /** @var \Joomla\CMS\Table\Menu $label */
            $label = Table::getInstance('menu');
            $label->setLocation($firstId, 'before');

            $data = [
                'menutype' => 'main',
                'client_id' => 1,
                'title' => $group['title'],
                'alias' => $group['alias'],
                'type' => 'separator',
                'published' => 1,
                'parent_id' => $topId,
                'component_id' => $extensionId,
                'img' => 'class:component',
                'home' => 0,
                'link' => '',
                'path' => '',
                'params' => '{"text_separator":1}',
            ];

            if ($label->bind($data) && $label->check()) {
                $label->store();
            }
        }
    }

    /**
     * @param   \Joomla\CMS\Installer\InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    public function update($parent) {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->ensureCoreTablesExist($db);

        $this->createUncategorisedCategory('pastwalk');
        $this->createUncategorisedCategory('route');

        return true;
    }

    /**
     * Defensive schema self-heal for the Past Walks/Routes table and the
     * child tables that hang off it (images, attachments, route points).
     *
     * Why this is needed: reinstalling this package over a site where
     * com_ra_library is ALREADY registered - which is exactly how it gets
     * installed during normal development (re-uploading the same package
     * again and again) - is always treated by Joomla as an *update*, never
     * as a fresh install. That means sql/install.mysql.utf8.sql (which has
     * the full, consolidated CREATE TABLE for #__ra_library_routes) never
     * runs again; only whichever files under sql/updates/mysql/ have a
     * version number greater than what Joomla has recorded for this
     * extension in #__schemas actually execute. If that bookkeeping is
     * ever out of step with the real database - a DB restore/import that
     * didn't also carry over #__schemas, the table dropped by hand while
     * testing, a schema row left over from an earlier broken
     * install/uninstall, etc. - Joomla believes 1.0.3.sql/1.0.4.sql (the
     * files that create and then rename this table) already ran, and will
     * never run them again, so the table silently never (re)appears. This
     * is exactly the situation createUncategorisedCategory()'s
     * tableHasColumn() guard below was written to survive without a fatal
     * error - but merely surviving isn't the same as fixing it.
     *
     * CREATE TABLE IF NOT EXISTS makes this safe to run on every single
     * install/update: a no-op when the tables already exist (the normal
     * case), and self-healing on the rare site where they don't.
     *
     * @param   \Joomla\Database\DatabaseInterface  $db  Database driver.
     *
     * @return  void
     *
     * @since   1.0.11
     */
    private function ensureCoreTablesExist($db) {
        $queries = [
            'routes' => "CREATE TABLE IF NOT EXISTS `#__ra_library_routes` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`record_type` VARCHAR(20) NOT NULL DEFAULT 'pastwalk',
`walks_manager_id` VARCHAR(50) NULL DEFAULT NULL,
`needs_review` TINYINT(1) NOT NULL DEFAULT 0,
`walk_date` DATE NULL DEFAULT NULL,
`title` VARCHAR(255) NOT NULL DEFAULT \"\",
`walk_leader` VARCHAR(255) NULL DEFAULT \"\",
`description` MEDIUMTEXT NULL,
`route_guide` MEDIUMTEXT NULL,
`template_intro_override` MEDIUMTEXT NULL,
`template_more_override` MEDIUMTEXT NULL,
`distance_km` DECIMAL(6,2) NULL DEFAULT NULL,
`national_grade` VARCHAR(50) NULL DEFAULT \"\",
`gpx_path` VARCHAR(500) NULL DEFAULT \"\",
`catid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`start_latitude` DECIMAL(9,6) NULL DEFAULT NULL,
`start_longitude` DECIMAL(9,6) NULL DEFAULT NULL,
`start_grid_reference` VARCHAR(20) NULL DEFAULT \"\",
`state` TINYINT(1) NOT NULL DEFAULT 0,
`ordering` INT(11) NULL DEFAULT 0,
`checked_out` INT(11) UNSIGNED,
`checked_out_time` DATETIME NULL DEFAULT NULL,
`created` DATETIME NULL DEFAULT NULL,
`created_by` INT(11) NULL DEFAULT 0,
`modified` DATETIME NULL DEFAULT NULL,
`modified_by` INT(11) NULL DEFAULT 0,
PRIMARY KEY (`id`)
,UNIQUE KEY `idx_walks_manager_id` (`walks_manager_id`)
,KEY `idx_state` (`state`)
,KEY `idx_walk_date` (`walk_date`)
,KEY `idx_catid` (`catid`)
,KEY `idx_checked_out` (`checked_out`)
,KEY `idx_created_by` (`created_by`)
,KEY `idx_modified_by` (`modified_by`)
,KEY `idx_record_type` (`record_type`)
,KEY `idx_needs_review` (`needs_review`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;",
            'images' => "CREATE TABLE IF NOT EXISTS `#__ra_library_images` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`record_id` int(11) UNSIGNED NOT NULL,
`caption` VARCHAR(255) NULL DEFAULT \"\",
`description` TEXT NULL,
`thumbnail_path` VARCHAR(500) NULL DEFAULT \"\",
`large_path` VARCHAR(500) NULL DEFAULT \"\",
`grid_reference` VARCHAR(20) NULL DEFAULT \"\",
`latitude` DECIMAL(9,6) NULL DEFAULT NULL,
`longitude` DECIMAL(9,6) NULL DEFAULT NULL,
`featured` TINYINT(1) NOT NULL DEFAULT 0,
`ordering` INT(11) NULL DEFAULT 0,
PRIMARY KEY (`id`)
,KEY `idx_record_id` (`record_id`)
,KEY `idx_featured` (`featured`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;",
            'attachments' => "CREATE TABLE IF NOT EXISTS `#__ra_library_attachments` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`record_id` int(11) UNSIGNED NOT NULL,
`attachment_type` VARCHAR(20) NOT NULL DEFAULT 'document',
`title` VARCHAR(255) NOT NULL DEFAULT \"\",
`file_path` VARCHAR(500) NOT NULL DEFAULT \"\",
`file_size` INT(11) UNSIGNED NULL DEFAULT NULL,
`ordering` INT(11) NULL DEFAULT 0,
`featured` TINYINT(1) NOT NULL DEFAULT 0,
PRIMARY KEY (`id`)
,KEY `idx_record_id` (`record_id`)
,KEY `idx_attachment_type` (`attachment_type`)
,KEY `idx_featured` (`featured`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;",
            'route_points' => "CREATE TABLE IF NOT EXISTS `#__ra_library_route_points` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`record_id` int(11) UNSIGNED NOT NULL,
`title` VARCHAR(255) NOT NULL DEFAULT \"\",
`description` MEDIUMTEXT NULL,
`grid_reference` VARCHAR(20) NULL DEFAULT \"\",
`latitude` DECIMAL(9,6) NULL DEFAULT NULL,
`longitude` DECIMAL(9,6) NULL DEFAULT NULL,
`ordering` INT(11) NULL DEFAULT 0,
PRIMARY KEY (`id`)
,KEY `idx_record_id` (`record_id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;",
        ];

        foreach ($queries as $sql) {
            try {
                $db->setQuery($sql)->execute();
            } catch (\Exception $e) {
                // Best-effort only - if one of these somehow fails
                // (insufficient DB privileges, a conflicting table already
                // there under an incompatible definition, etc.) the rest of
                // install()/update() should still proceed rather than
                // fataling here; createUncategorisedCategory()'s own
                // tableHasColumn() guard still protects against a missing
                // record_type column after this.
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
    private function createUncategorisedCategory($recordType) {
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
    private function tableHasColumn($db, $table, $columnName) {
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
    private function insertUncategorisedCategory($extension) {
        $category = Table::getInstance('Category');

        $data = array(
            'extension' => $extension,
            'title' => 'Uncategorised',
            'description' => '',
            'published' => 1,
            'access' => 1,
            'language' => '*',
            'level' => 1,
            'path' => 'uncategorised',
            'params' => '{}',
            'metadesc' => '',
            'metakey' => '',
            'metadata' => '{}',
            'created_time' => Factory::getDate()->toSql(),
            'created_user_id' => (int) (Factory::getApplication()->getIdentity()->id ?? 0),
            'rules' => array(),
            'parent_id' => 1,
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
    private function repairUncategorisedCategory($extension, $categoryId) {
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
