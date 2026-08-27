<?php
/**
 * Custom Joomla form field: "Folder" (dynamic / lazy-loading version)
 *
 * Renders a text display + "Browse…" button that opens a modal containing a
 * lazy-loading folder tree. Each level of the tree is fetched from the
 * server on demand (one AJAX call per expand click), so this scales fine to
 * thousands of folders — nothing is scanned or sent to the browser until the
 * user actually expands that branch. Compare with the earlier flat-dropdown
 * version, which scanned and rendered every folder on page load.
 *
 * The stored value is the folder path relative to the site root (e.g.
 * "images/gallery/2026"), same convention as before.
 *
 * REQUIRES: FolderController.php (the AJAX endpoint this field calls to list
 * a folder's immediate children) — see that file's own docblock for where it
 * must live. field-folder.js must also be published under your component's
 * media folder — see the constant below.
 *
 * WHERE TO PUT THIS FILE
 * -----------------------
 * Anywhere your component autoloads from, e.g.:
 *   administrator/components/com_yourcomponent/src/Field/FolderField.php
 *
 * Reference it from form XML with `addfieldprefix` (portable, doesn't need
 * to match your component's own namespace) — see example_folder_field_usage.xml.
 *
 * FIELD ATTRIBUTES
 * ----------------
 *   roots   comma-separated root folders, relative to the Joomla root.
 *           Default: "images,files". MUST match FolderController's
 *           ALLOWED_ROOTS constant, or the AJAX call will be rejected.
 */

namespace Ramblers\Component\Ra_library\Administrator\Field;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

class FolderField extends FormField {

    /**
     * The form field type.
     *
     * @var string
     */
    protected $type = 'Folder';

    /**
     * Change this if you rename the component option in your own install.
     *
     * @var string
     */
    private const COMPONENT_OPTION = 'com_ra_library';

    /**
     * Guards against injecting the <script>/<style> block more than once
     * when several Folder fields appear on the same page.
     *
     * @var bool
     */
    private static $assetsLoaded = false;

    /**
     * @return string HTML markup for the field
     */
    protected function getInput() {
        $this->loadAssets();

        $roots = array_filter(array_map('trim', explode(
                                ',',
                                (string) ($this->element['roots'] ?: 'images,files')
                        )));

        if (!$roots) {
            $roots = ['images', 'files'];
        }

        $fieldId = $this->id;
        $value = (string) $this->value;
        $ajaxUrl = Uri::base() . 'index.php?option=' . self::COMPONENT_OPTION . '&task=folder.children&format=json';
        $tokenName = Session::getFormToken();

        ob_start();
        ?>
        <div
            class="folder-field"
            id="<?php echo $fieldId; ?>_wrap"
            data-field-id="<?php echo $fieldId; ?>"
            data-ajax-url="<?php echo htmlspecialchars($ajaxUrl, ENT_QUOTES, 'UTF-8'); ?>"
            data-token-name="<?php echo htmlspecialchars($tokenName, ENT_QUOTES, 'UTF-8'); ?>"
            data-roots="<?php echo htmlspecialchars(implode(',', $roots), ENT_QUOTES, 'UTF-8'); ?>"
            >
            <div class="input-group">
                <input
                    type="text"
                    id="<?php echo $fieldId; ?>_display"
                    class="form-control folder-field-display"
                    value="<?php echo $value !== '' ? htmlspecialchars('/' . $value, ENT_QUOTES, 'UTF-8') : ''; ?>"
                    placeholder="<?php echo htmlspecialchars(Text::_('Select a folder'), ENT_QUOTES, 'UTF-8'); ?>"
                    readonly
                    >
                <button type="button" class="btn btn-secondary folder-field-browse">
                    <?php echo Text::_('Browse'); ?>
                </button>
            </div>

            <input
                type="hidden"
                id="<?php echo $fieldId; ?>"
                name="<?php echo htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8'); ?>"
                value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"
                <?php echo $this->required ? 'required' : ''; ?>
                >

            <div class="modal fade folder-field-modal" id="<?php echo $fieldId; ?>_modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?php echo Text::_('Select a folder to use'); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <ul class="folder-field-tree list-unstyled" id="<?php echo $fieldId; ?>_tree"></ul>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <?php echo Text::_('JCANCEL'); ?>
                            </button>
                            <button type="button" class="btn btn-primary" id="<?php echo $fieldId; ?>_confirm" disabled>
                                <?php echo Text::_('COM_YOURCOMPONENT_FIELD_FOLDER_BROWSE'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue the field's JS/CSS once per page load, however many Folder
     * fields appear on the form.
     */
    private function loadAssets(): void {
        if (self::$assetsLoaded) {
            return;
        }

        self::$assetsLoaded = true;
        // Joomla only ships the Bootstrap Modal JS component to a page when
        // something explicitly asks for it — otherwise window.bootstrap.Modal
        // is never populated and `new bootstrap.Modal(...)` throws "is not a
        // constructor". This is that ask; it must run before field-folder.js.
        HTMLHelper::_('bootstrap.modal');

        $doc = Factory::getApplication()->getDocument();

        // Adjust this path to wherever you publish field-folder.js in your component.
        $doc->addScript(
                //    Uri::root(true) . '/administrator/components/' . self::COMPONENT_OPTION . '/media/js/fields/field-folder.js',
                Uri::root(true) . '/media/' . self::COMPONENT_OPTION . '/js/fields/field-folder.js',
                ['version' => 'auto'],
                ['defer' => true]
        );
        // Same media-root convention as the script above, just under css/fields/.
        $doc->addStyleSheet(
                Uri::root(true) . '/media/' . self::COMPONENT_OPTION . '/css/fields/field-folder.css',
                ['version' => 'auto']
        );

        $doc->addStyleDeclaration(
                '.folder-field-tree{max-height:50vh;overflow:auto}'
                . '.folder-field-tree ul{list-style:none}'
                . '.folder-field-row{padding:.15rem 0}'
                . '.folder-field-label{cursor:pointer;padding:.1rem .35rem;border-radius:.25rem}'
                . '.folder-field-label:hover{background:var(--bs-tertiary-bg,#f1f1f1)}'
                . '.folder-field-label.is-selected{background:var(--bs-primary,#0d6efd);color:#fff}'
                . '.folder-field-toggle{width:1.25rem;text-align:center}'
        );
    }
}
