<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\View\Pastwalks;
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Ramblers\Component\Ra_library\Administrator\Helper\Ra_libraryHelper;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Joomla\CMS\HTML\Helpers\Sidebar;

/**
 * View class for a list of Pastwalks.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView {

    protected $items;
    protected $pagination;
    protected $state;
    public $filterForm;
    public $activeFilters;

    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
     *
     * @return void
     *
     * @throws Exception
     */
    public function display($tpl = null) {
        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        $this->addToolbar();

        $this->sidebar = Sidebar::render();
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
        $state = $this->get('State');
        $canDo = Ra_libraryHelper::getPastwalkActions();

        ToolbarHelper::title(Text::_('COM_RA_LIBRARY_TITLE_PASTWALKS'), "generic");

        $toolbar = Toolbar::getInstance('toolbar');

        if ($canDo->get('pastwalks.create')) {
            $toolbar->addNew('pastwalk.add');

            $toolbar->link(Text::_('COM_RA_LIBRARY_IMPORT_TOOLBAR_BUTTON'), 'index.php?option=com_ra_library&view=import')
                ->icon('fas fa-download');
        }

        if ($canDo->get('pastwalks.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                    ->text('JTOOLBAR_CHANGE_STATUS')
                    ->toggleSplit(false)
                    ->icon('fas fa-ellipsis-h')
                    ->buttonClass('btn btn-action')
                    ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            if (isset($this->items[0]->state)) {
                $childBar->publish('pastwalks.publish')->listCheck(true);
                $childBar->unpublish('pastwalks.unpublish')->listCheck(true);
                $childBar->archive('pastwalks.archive')->listCheck(true);
            }

            if (isset($this->items[0]->checked_out)) {
                $childBar->checkin('pastwalks.checkin')->listCheck(true);
            }

            if (isset($this->items[0]->state)) {
                $childBar->trash('pastwalks.trash')->listCheck(true);
            }
        }

        if (isset($this->items[0]->state)) {
            if ($this->state->get('filter.state') == ContentComponent::CONDITION_TRASHED && $canDo->get('pastwalks.delete')) {
                $toolbar->delete('pastwalks.delete')
                        ->text('JTOOLBAR_EMPTY_TRASH')
                        ->message('JGLOBAL_CONFIRM_DELETE')
                        ->listCheck(true);
            }
        }

        if ($canDo->get('pastwalks.manage')) {
            $toolbar->preferences('com_ra_library');
        }

        Sidebar::setAction('index.php?option=com_ra_library&view=pastwalks');
    }

    /**
     * Method to order fields
     *
     * @return void
     */
    protected function getSortFields() {
        return array(
            'a.walk_date' => Text::_('COM_RA_LIBRARY_PASTWALK_FIELD_WALK_DATE_LABEL'),
            'a.title' => Text::_('COM_RA_LIBRARY_PASTWALKS_TITLE'),
            'a.state' => Text::_('JSTATUS'),
            'a.id' => Text::_('JGRID_HEADING_ID'),
        );
    }

    /**
     * Check if state is set
     *
     * @param   mixed  $state  State
     *
     * @return bool
     */
    public function getState($state) {
        return isset($this->state->{$state}) ? $this->state->{$state} : false;
    }
}
