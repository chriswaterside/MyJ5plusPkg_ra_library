<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\View\Import;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Ramblers\Component\Ra_library\Administrator\Helper\Ra_libraryHelper;

/**
 * View for the "Import from Walks Manager" screen - fetches candidate walks
 * on a plain GET (nothing is written here) and lists them with checkboxes;
 * the actual write happens in ImportController::import() on submit.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView {

    /**
     * @var  array
     * @since 1.0.0
     */
    public $items = [];

    /**
     * @var  string|null
     * @since 1.0.0
     */
    public $fetchError = null;

    /**
     * @var  string
     * @since 1.0.0
     */
    public $groupCodes = '';

    /**
     * The configured default group(s) - {code, name} pairs - shown read-only
     * on the screen. Importing is always restricted to these; there's no
     * field to pick a different group here any more (see Options).
     *
     * @var  array
     * @since 1.0.0
     */
    public $configuredGroups = [];

    /**
     * @var  string
     * @since 1.0.0
     */
    public $period = '3months';

    /**
     * The category to file imported walks into - 0 means "Uncategorised"
     * (see ImportController::import(), which sends '' rather than 0 for
     * that case - PastwalkTable::bind() only auto-resolves an empty string
     * to the real Uncategorised category id, not a literal 0).
     *
     * @var  int
     * @since 1.0.0
     */
    public $catid = 0;

    /**
     * The rendered category field for picking the import target category -
     * built and bound in display() by buildCatidField().
     *
     * @var  \Joomla\CMS\Form\FormField|null
     * @since 1.0.0
     */
    public $catidField = null;

    /**
     * Preset lookback periods offered on the screen - value => [label, relative date string].
     *
     * @var  array
     * @since 1.0.0
     */
    public $periodOptions = [
        '1month' => ['COM_RA_LIBRARY_IMPORT_PERIOD_1MONTH', '1 month'],
        '3months' => ['COM_RA_LIBRARY_IMPORT_PERIOD_3MONTHS', '3 months'],
        '6months' => ['COM_RA_LIBRARY_IMPORT_PERIOD_6MONTHS', '6 months'],
        '1year' => ['COM_RA_LIBRARY_IMPORT_PERIOD_1YEAR', '1 year'],
        '2years' => ['COM_RA_LIBRARY_IMPORT_PERIOD_2YEARS', '2 years'],
        '5years' => ['COM_RA_LIBRARY_IMPORT_PERIOD_5YEARS', '5 years'],
        '10years' => ['COM_RA_LIBRARY_IMPORT_PERIOD_10YEARS', '10 years'],
    ];

    /**
     * Display the view.
     *
     * @param   string  $tpl  Template name
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function display($tpl = null) {
        /** @var \Ramblers\Component\Ra_library\Administrator\Model\ImportModel $model */
        $model = $this->getModel();

        $input = Factory::getApplication()->input;

        // Importing is always restricted to the group(s) configured under
        // Options - there's no way to pick a different group on this screen
        // any more, so the group codes always come from the component
        // default, never from the request. (ImportModel::fetchAvailableWalks()
        // also clamps to the same default itself, as a second line of
        // defence.)
        $this->groupCodes = $model->getDefaultGroupCodes();
        $this->configuredGroups = $model->getDefaultGroupsForDisplay();
        $this->period = $input->getCmd('period', '3months');
        $this->catid = $input->getInt('catid', 0);
        $this->catidField = $this->buildCatidField($this->catid);

        if (!isset($this->periodOptions[$this->period])) {
            $this->period = '3months';
        }

        if ($this->groupCodes !== '') {
            $periodString = $this->periodOptions[$this->period][1];
            $result = $model->fetchAvailableWalks($this->groupCodes, $periodString);
            $this->items = $result['items'];
            $this->fetchError = $result['error'];
        } else {
            $this->fetchError = Text::_('COM_RA_LIBRARY_IMPORT_NO_GROUP_CODE');
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function addToolbar() {
        ToolbarHelper::title(Text::_('COM_RA_LIBRARY_IMPORT_TITLE'), 'download');

        $toolbar = Toolbar::getInstance('toolbar');
        $canDo = Ra_libraryHelper::getPastwalkActions();

        if ($canDo->get('pastwalks.manage')) {
            $toolbar->preferences('com_ra_library');
        }

        ToolbarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_ra_library&view=pastwalks');
    }

    /**
     * Build a one-field ad-hoc Form wrapping the standard "category" field
     * (extension=com_ra_library.pastwalk - Import is always for Past
     * Walks), bound to the given value, so the template can render it like
     * any other Joomla form field. 0 (the default) is labelled
     * "Uncategorised" rather than "All", since here you're choosing the ONE
     * category every imported walk should land in, not filtering a list.
     *
     * @param   int  $value  The catid to bind as the current selection.
     *
     * @return  \Joomla\CMS\Form\FormField
     *
     * @since   1.0.0
     */
    private function buildCatidField(int $value) {
        $xml = '<form addfieldprefix="Ramblers\\Component\\Ra_library\\Administrator\\Field">'
                . '<field name="catid" type="category" extension="com_ra_library.pastwalk" '
                . 'label="COM_RA_LIBRARY_IMPORT_CATID_LABEL">'
                . '<option value="0">COM_RA_LIBRARY_IMPORT_CATID_UNCATEGORISED</option>'
                . '</field>'
                . '</form>';

        $form = new Form('com_ra_library.import', ['control' => false]);
        $form->load($xml);
        $form->bind(['catid' => $value]);

        return $form->getField('catid');
    }
}
