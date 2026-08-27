<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\View\Route;
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Factory;
use \Ramblers\Component\Ra_library\Administrator\Helper\Ra_libraryHelper;
use \Joomla\CMS\Language\Text;

/**
 * View class for a single Route.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView {

    protected $state;
    protected $item;
    protected $form;

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
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');

        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function addToolbar() {
        Factory::getApplication()->input->set('hidemainmenu', true);

        $user = Factory::getApplication()->getIdentity();
        $isNew = ($this->item->id == 0);

        if (isset($this->item->checked_out)) {
            $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
        } else {
            $checkedOut = false;
        }

        $canDo = Ra_libraryHelper::getRouteActions();

        ToolbarHelper::title(Text::_($isNew ? 'COM_RA_LIBRARY_TITLE_ROUTE' : 'COM_RA_LIBRARY_TITLE_ROUTE'), "generic");

        if (!$checkedOut && ($canDo->get('routes.edit') || $canDo->get('routes.edit.own') || $canDo->get('routes.create'))) {
            ToolbarHelper::apply('route.apply', 'JTOOLBAR_APPLY');
            ToolbarHelper::save('route.save', 'JTOOLBAR_SAVE');
        }

        if (!$checkedOut && $canDo->get('routes.create')) {
            ToolbarHelper::custom('route.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
        }

        if (empty($this->item->id)) {
            ToolbarHelper::cancel('route.cancel', 'JTOOLBAR_CANCEL');
        } else {
            ToolbarHelper::cancel('route.cancel', 'JTOOLBAR_CLOSE');
        }
    }
}
