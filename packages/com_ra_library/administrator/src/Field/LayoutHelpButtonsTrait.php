<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Shared by any admin Field class whose value is a SimpleTemplate layout
 * string (LayoutHelpTextareaField, and TableitemsField for the "Customise
 * Table layout" field) - appends two small "Syntax" / "Fields" buttons
 * after the field's own label, which open the same documentation
 * previously shown via the Customise tab's <details> block
 * (administrator/tmpl/librarydisplay/layouthelp-syntax.html and
 * layouthelp-fields-{type}.html), now in a ra.modals popup instead.
 *
 * Only ever adds the buttons when the field's XML explicitly declares a
 * helptype="walks|pastwalks|routes|categorytree|tableoptions" attribute - so
 * a plain use of TableitemsField for something unrelated (e.g.
 * table_iconmarkers) is completely unaffected.
 *
 * "walks"/"pastwalks"/"routes"/"categorytree" get both a "Syntax" button
 * (the shared {placeholder}/{if:x}...{/if} template syntax, from the single
 * layouthelp-syntax.html) and a "Fields" button (per-type keyword list, from
 * layouthelp-fields-{type}.html).
 *
 * "tableoptions" (the table_options "options" column, used for both the SQL
 * and JSON feed table sources) only gets the "Fields" button - it isn't a
 * template string, just space-separated keywords, so the generic template
 * Syntax content doesn't apply and is skipped for this type.
 *
 * @since  1.0.0
 */
trait LayoutHelpButtonsTrait {

    /**
     * Registers the shared JS/CSS this needs - safe to call from every
     * field instance that uses this trait, Joomla's WebAssetManager
     * de-duplicates by path so it's only actually included once per page
     * regardless of how many fields call it.
     *
     * @since   1.0.0
     */
    protected function loadLayoutHelpAssets(): void {
        // The popup itself is ra.modals (media/js/ra.js) - not loaded by
        // default on every admin screen this field type might appear on
        // (e.g. Components > Ra_library > Options), so load it here rather
        // than assuming it's already present.
        HTMLHelper::_('script', 'com_ra_library/ra.js', ['relative' => true]);
        HTMLHelper::_('stylesheet', 'com_ra_library/ramblerslibrary.css', ['relative' => true]);
        HTMLHelper::_('script', 'com_ra_library/fields/layouthelp.js', ['relative' => true]);
        HTMLHelper::_('stylesheet', 'com_ra_library/fields/layouthelp.css', ['relative' => true]);
    }

    /**
     * Builds the "Syntax" / "Fields" buttons plus their (hidden) popup
     * content, to append after the field's own getLabel() output. Returns
     * an empty string if the field's XML doesn't declare a helptype -
     * fields using this trait's host class for an unrelated purpose are
     * left exactly as they were.
     *
     * @since   1.0.0
     */
    protected function renderLayoutHelpButtons(): string {
        $helpType = (string) ($this->element['helptype'] ?? '');

        // Types whose Fields content is a template-placeholder reference,
        // paired with the shared Syntax button/content.
        $syntaxTypes = ['walks', 'pastwalks', 'routes', 'categorytree'];

        // All types this trait knows how to render help for.
        $knownTypes = [...$syntaxTypes, 'tableoptions'];

        if (!\in_array($helpType, $knownTypes, true)) {
            return '';
        }

        $this->loadLayoutHelpAssets();

        $showSyntax = \in_array($helpType, $syntaxTypes, true);
        $fieldsContent = $this->loadLayoutHelpFile(__DIR__ . '/../../tmpl/librarydisplay/layouthelp-fields-' . $helpType . '.html');
        $fieldsId = $this->id . '_layouthelp_fields';

        $html = '<span class="ra-layouthelp-buttons">';

        if ($showSyntax) {
            $syntaxId = $this->id . '_layouthelp_syntax';
            $html .= '<button type="button" class="ra-layouthelp-btn" data-target="' . $syntaxId . '">'
                    . htmlspecialchars(Text::_('COM_RA_LIBRARY_LAYOUTHELP_SYNTAX_BUTTON')) . '</button>';
        }

        $html .= '<button type="button" class="ra-layouthelp-btn" data-target="' . $fieldsId . '">'
                . htmlspecialchars(Text::_('COM_RA_LIBRARY_LAYOUTHELP_FIELDS_BUTTON')) . '</button>';
        $html .= '</span>';

        if ($showSyntax) {
            $syntaxContent = $this->loadLayoutHelpFile(__DIR__ . '/../../tmpl/librarydisplay/layouthelp-syntax.html');
            $html .= '<div id="' . $syntaxId . '" class="ra-layouthelp-content">' . $syntaxContent . '</div>';
        }

        $html .= '<div id="' . $fieldsId . '" class="ra-layouthelp-content">' . $fieldsContent . '</div>';

        return $html;
    }

    /**
     * @since   1.0.0
     */
    private function loadLayoutHelpFile(string $path): string {
        if (!is_file($path)) {
            return '';
        }

        $content = file_get_contents($path);

        return $content !== false ? $content : '';
    }
}
