<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Librarydisplay controller class.
 *
 * @since  1.0.0
 */
class LibrarydisplayController extends FormController {

    protected $view_list = 'librarydisplays';

    public function save($key = null, $keyName = 'id') {
        $data = $this->input->post->get('jform', [], 'array');

        $model = $this->getModel();
        $result = $model->save($data);

        if ($result) {
            Factory::getApplication()->enqueueMessage(Text::_('Item saved successfully'), 'success');
            $this->setRedirect('index.php?option=com_ra_library&view=librarydisplays');
        } else {
            Factory::getApplication()->enqueueMessage(Text::_('Error saving item'), 'error');
            $this->setRedirect('index.php?option=com_ra_library&view=item&layout=edit&id=' . (int) $data['id']);
        }
    }
}
