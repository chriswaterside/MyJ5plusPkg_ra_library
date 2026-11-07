<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.Ra_styling
 * @license     GNU General Public License version 2 or later
 */

namespace Ramblers\Plugin\System\Ra_styling\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

/**
 * RA Styling System Plugin: optionally adds ramblers.css, a custom CSS file and/or a custom JS file.
 */
final class Ra_styling extends CMSPlugin implements SubscriberInterface {

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array {
        return ['onBeforeCompileHead' => 'onBeforeCompileHead'];
    }

    public function onBeforeCompileHead(): void {
        $app = $this->getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $doc = $app->getDocument();
        if (!($doc instanceof HtmlDocument)) {
            return;
        }

        $wa = $doc->getWebAssetManager();

        // 1. ramblers.css
        if ((int) $this->params->get('include_ramblers_css', 0) === 1) {
            $cssFile = 'media/plg_system_ra_styling/css/ramblers.css';
            $wa->registerAndUseStyle(
                'plg_system_ra_styling.ramblers',
                $cssFile,
                ['version' => is_file(JPATH_ROOT . '/' . $cssFile) ? filemtime(JPATH_ROOT . '/' . $cssFile) : 'auto']
            );
        }

        // 2. user-defined CSS file
        if ((int) $this->params->get('custom_css_enabled', 0) === 1) {
            $file = ltrim(trim((string) $this->params->get('custom_css_file', '')), '/');
            if ($file !== '' && is_file(JPATH_ROOT . '/' . $file)) {
                $wa->registerAndUseStyle('plg_system_ra_styling.custom_css', $file, ['version' => 'auto']);
            }
        }

        // 3. user-defined JS file
        if ((int) $this->params->get('custom_js_enabled', 0) === 1) {
            $file = ltrim(trim((string) $this->params->get('custom_js_file', '')), '/');
            if ($file !== '' && is_file(JPATH_ROOT . '/' . $file)) {
                $wa->registerAndUseScript('plg_system_ra_styling.custom_js', $file, ['version' => 'auto']);
            }
        }
    }
}
