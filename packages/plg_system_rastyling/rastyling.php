<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.RaStyling
 *
 * @copyright   Copyright (C) 2024 RaStyling. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;

/**
 * RaStyling System Plugin
 *
 * Single-file plugin — no service provider or DI container needed.
 * Joomla discovers this class by the conventional name PlgSystemRastyling.
 *
 * @since  1.0.0
 */
class PlgSystemRastyling extends CMSPlugin {

    /**
     * Auto-load the plugin's language file.
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Inject CSS / JS assets before the document head is compiled.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onBeforeCompileHead(): void {

        $app = Factory::getApplication();

        // Front-end only.
        if (!$app->isClient('site')) {
            return;
        }

        $doc = $app->getDocument();

        // HTML documents only.
        if (!($doc instanceof HtmlDocument)) {
            return;
        }

        //   $baseUrl = Uri::root();
        // 1. Optional: ramblers.css (expected at <site-root>/css/ramblers.css)
        if ((int) $this->params->get('include_ramblers_css', 0) === 1) {
            // $doc->addStyleSheet('modules/mod_rafooter/css/ramblers.css', ['version' => 'auto']);
            Load::addStyleSheet('modules/mod_rafooter/css/ramblers.css');
        }

        // 2. Optional: user-defined CSS file
        if ((int) $this->params->get('custom_css_enabled', 0) === 1) {
            $customCSS = trim((string) $this->params->get('custom_css_file', ''));
            $cssPath = JPATH_ROOT . '/' . ltrim($customCSS, '/');
            if ($customCSS !== '' && file_exists($cssPath)) {
                $doc->addStyleSheet(Uri::root() . ltrim($customCSS, '/'), ['version' => 'auto']);
            }
        }

        // 3. Optional: user-defined JS file (deferred)
        if ((int) $this->params->get('custom_js_enabled', 0) === 1) {
            $customJs = trim((string) $this->params->get('custom_js_file', ''));
            $jsPath = JPATH_ROOT . '/' . ltrim($customJs, '/');
            if ($customJs !== '' && file_exists($jsPath)) {
                $doc->addScript(Uri::root() . ltrim($customJs, '/'), ['version' => 'auto']);
            }
        }
    }

    /**
     * Resolve a user value to a full URL.
     *
     * @param   string  $path     Path or URL supplied by the user.
     * @param   string  $baseUrl  Site root URL (with trailing slash).
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function resolveUrl(string $path, string $baseUrl): string {
        if (
                str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')
        ) {
            return $path;
        }

        return $baseUrl . ltrim($path, '/');
    }
}
