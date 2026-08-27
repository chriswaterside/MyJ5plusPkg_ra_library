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

/**
 * Pastwalk controller class.
 *
 * @since  1.0.0
 */
class PastwalkController extends FormController {

    protected $view_list = 'pastwalks';

    /**
     * Gate opening a blank "Add" form behind pastwalks.create, rather than
     * the core.create action used by the rest of the component.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    protected function allowAdd($data = array()) {
        return Factory::getApplication()->getIdentity()->authorise('pastwalks.create', 'com_ra_library');
    }

    /**
     * Gate opening/saving an existing record behind pastwalks.edit (or
     * pastwalks.edit.own for records the user created themselves).
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key.
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    protected function allowEdit($data = array(), $key = 'id') {
        $user = Factory::getApplication()->getIdentity();

        if ($user->authorise('pastwalks.edit', 'com_ra_library')) {
            return true;
        }

        $recordId = (int) ($data[$key] ?? 0);

        if ($recordId && $user->authorise('pastwalks.edit.own', 'com_ra_library')) {
            $model = $this->getModel();
            $record = $model->getItem($recordId);

            if ($record && (int) $record->created_by === (int) $user->id) {
                return true;
            }
        }

        return false;
    }
}
