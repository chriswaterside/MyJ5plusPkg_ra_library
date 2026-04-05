<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\View\Emaillogs;
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Ramblers\Component\Ra_library\Administrator\Helper\Ra_libraryHelper;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\HTML\Helpers\Sidebar;
/**
 * View class for a list of Emaillogs.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
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
	protected function addToolbar()
	{
		$state = $this->get('State');
		$canDo = Ra_libraryHelper::getActions();

		ToolbarHelper::title(Text::_('COM_RA_LIBRARY_TITLE_EMAILLOGS'), "generic");

		$toolbar = Toolbar::getInstance('toolbar');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/src/View/Emaillogs';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
			//	$toolbar->addNew('emaillog.add');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			$dropdown = $toolbar->dropdownButton('status-group')
				->text('JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('fas fa-ellipsis-h')
				->buttonClass('btn btn-action')
				->listCheck(true);

		//	$childBar = $dropdown->getChildToolbar();

			if (isset($this->items[0]->state))
			{
			//	$childBar->publish('emaillogs.publish')->listCheck(true);
			//	$childBar->unpublish('emaillogs.unpublish')->listCheck(true);
			//	$childBar->archive('emaillogs.archive')->listCheck(true);
			}
			elseif (isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
//				$toolbar->delete('emaillogs.delete')
//				->text('JTOOLBAR_EMPTY_TRASH')
//				->message('JGLOBAL_CONFIRM_DELETE')
//				->listCheck(true);
			}

			//$childBar->standardButton('duplicate')
			//	->text('JTOOLBAR_DUPLICATE')
			//	->icon('fas fa-copy')
			//	->task('emaillogs.duplicate')
			//	->listCheck(true);

			if (isset($this->items[0]->checked_out))
			{
			//	$childBar->checkin('emaillogs.checkin')->listCheck(true);
			}

			if (isset($this->items[0]->state))
			{
			//	$childBar->trash('emaillogs.trash')->listCheck(true);
			}
		}

		

		// Show trash and delete for components that uses the state field
		if (isset($this->items[0]->state))
		{

			if ($this->state->get('filter.state') == ContentComponent::CONDITION_TRASHED && $canDo->get('core.delete'))
			{
				$toolbar->delete('emaillogs.delete')
					->text('JTOOLBAR_EMPTY_TRASH')
					->message('JGLOBAL_CONFIRM_DELETE')
					->listCheck(true);
			}
		}

		if ($canDo->get('core.admin'))
		{
			$toolbar->preferences('com_ra_library');
		}

		// Set sidebar action
		Sidebar::setAction('index.php?option=com_ra_library&view=emaillogs');
	}
	
	/**
	 * Method to order fields 
	 *
	 * @return void 
	 */
	protected function getSortFields()
	{
		return array(
			'a.`id`' => Text::_('JGRID_HEADING_ID'),
			'a.`datetime`' => Text::_('COM_RA_LIBRARY_EMAILLOGS_DATETIME'),
			'a.`to`' => Text::_('COM_RA_LIBRARY_EMAILLOGS_TO'),
			'a.`title`' => Text::_('COM_RA_LIBRARY_EMAILLOGS_TITLE'),
			'a.`replyto`' => Text::_('COM_RA_LIBRARY_EMAILLOGS_REPLYTO'),
			'a.`sent`' => Text::_('COM_RA_LIBRARY_EMAILLOGS_SENT'),
			'a.`message`' => Text::_('COM_RA_LIBRARY_EMAILLOGS_MESSAGE'),
		);
	}

	/**
	 * Check if state is set
	 *
	 * @param   mixed  $state  State
	 *
	 * @return bool
	 */
	public function getState($state)
	{
		return isset($this->state->{$state}) ? $this->state->{$state} : false;
	}
}
