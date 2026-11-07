<?php

/**
 * @package    Pkg_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Package install script for pkg_ra_library.
 *
 * Joomla installs plugins disabled. On a FRESH install of the package this
 * enables the plugins the package relies on, and gives System - RA Styling
 * its default settings (ramblers.css switched on).
 *
 * On an UPDATE nothing is touched, so a webmaster who has deliberately
 * disabled a plugin, or changed its options, keeps their choice.
 *
 * Save as script.php in the package root and reference it from the package
 * manifest (pkg_ra_library.xml) with:
 *
 *     <scriptfile>script.php</scriptfile>
 *
 * The class name must be Pkg_ plus the package element, i.e. the manifest's
 * <packagename>ra_library</packagename> gives Pkg_Ra_libraryInstallerScript.
 * (If your <packagename> differs, rename the class to match.)
 *
 * @since  5.0.0
 */
class Pkg_Ra_libraryInstallerScript
{
    /**
     * Plugins to enable on a fresh install.
     *
     * 'params' (optional) is written only if the plugin has no saved
     * parameters yet, i.e. they are defaults, never an overwrite.
     *
     * @var array
     */
    private $plugins = [
        [
            'folder'  => 'content',
            'element' => 'ra_library',
        ],
        [
            'folder'  => 'system',
            'element' => 'ra_styling',
            'params'  => [
                'include_ramblers_css' => '1',
                'custom_css_enabled'   => '0',
                'custom_css_file'      => '',
                'custom_js_enabled'    => '0',
                'custom_js_file'       => '',
            ],
        ],
    ];

    /**
     * @param   string  $type    install, update, discover_install
     * @param   \Joomla\CMS\Installer\InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean
     */
    public function postflight($type, $parent)
    {
        // The styling plugin was renamed rastyling -> ra_styling: carry the old
        // settings across and switch the old plugin off (any install type).
        $this->migrateOldStylingPlugin();

        // Fresh installs only (discover_install covers installs via
        // System > Discover). Never on update.
        if ($type !== 'install' && $type !== 'discover_install') {
            return true;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        foreach ($this->plugins as $plugin) {
            $this->enablePlugin($db, $plugin['folder'], $plugin['element']);

            if (!empty($plugin['params'])) {
                $this->setDefaultParams($db, $plugin['folder'], $plugin['element'], $plugin['params']);
            }
        }

        return true;
    }

    /**
     * Set enabled = 1 on the plugin's #__extensions row.
     */
    private function enablePlugin(DatabaseInterface $db, string $folder, string $element): void
    {
        try {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote($folder))
                ->where($db->quoteName('element') . ' = ' . $db->quote($element));
            $db->setQuery($query)->execute();
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage(
                'Could not enable plugin ' . $folder . '/' . $element . ': ' . $e->getMessage(),
                'warning'
            );
        }
    }

    /**
     * Write default parameters, but only if the plugin has none saved yet.
     */
    private function setDefaultParams(DatabaseInterface $db, string $folder, string $element, array $params): void
    {
        try {
            $query = $db->getQuery(true)
                ->select($db->quoteName('params'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote($folder))
                ->where($db->quoteName('element') . ' = ' . $db->quote($element));
            $existing = trim((string) $db->setQuery($query)->loadResult());

            // '' and '{}' both mean "nothing saved yet".
            if ($existing !== '' && $existing !== '{}') {
                return;
            }

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote($folder))
                ->where($db->quoteName('element') . ' = ' . $db->quote($element));
            $db->setQuery($query)->execute();
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage(
                'Could not set default options for plugin ' . $folder . '/' . $element . ': ' . $e->getMessage(),
                'warning'
            );
        }
    }

    /**
     * Move the settings of the old System - RaStyling plugin (element "rastyling")
     * to System - RA Styling (element "ra_styling"), then disable the old one so
     * ramblers.css is not loaded twice. The old plugin is NOT uninstalled:
     * the webmaster should do that in Extensions > Manage.
     */
    private function migrateOldStylingPlugin(): void
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $fetch = function (string $element) use ($db) {
                $query = $db->getQuery(true)
                    ->select($db->quoteName(['extension_id', 'enabled', 'params']))
                    ->from($db->quoteName('#__extensions'))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                    ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
                    ->where($db->quoteName('element') . ' = ' . $db->quote($element));

                return $db->setQuery($query)->loadObject();
            };

            $old = $fetch('rastyling');
            $new = $fetch('ra_styling');

            // Nothing to do unless the old plugin is still there and switched on
            if (!$old || !$new || (int) $old->enabled !== 1) {
                return;
            }

            $params = trim((string) $old->params);
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('extension_id') . ' = ' . (int) $new->extension_id);
            if ($params !== '' && $params !== '{}') {
                $query->set($db->quoteName('params') . ' = ' . $db->quote($params));
            }
            $db->setQuery($query)->execute();

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 0')
                ->where($db->quoteName('extension_id') . ' = ' . (int) $old->extension_id);
            $db->setQuery($query)->execute();

            Factory::getApplication()->enqueueMessage(
                'System - RaStyling has been replaced by System - RA Styling. Your settings were copied across. '
                . 'Please uninstall the old "System - RaStyling" plugin in Extensions > Manage.',
                'notice'
            );
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage(
                'Could not move the old RaStyling settings: ' . $e->getMessage(),
                'warning'
            );
        }
    }
}
