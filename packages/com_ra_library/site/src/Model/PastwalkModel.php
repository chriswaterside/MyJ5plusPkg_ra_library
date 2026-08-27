<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Ramblers\Component\Ra_library\Site\Helper\FrontendHelper;

/**
 * Model for the "Single Past Walk" page - a fixed, always-available
 * component view (index.php?option=com_ra_library&view=pastwalk&id=X), not
 * something that needs a Display Options row or menu item set up first.
 * It always renders using the one global Past Walks item template
 * (Components > Ra_library > Options), the same one Blog uses for its
 * intro - see DisplayHelper::displayRecordBlog() / ItemRenderer.
 *
 * @since  1.0.0
 */
class PastwalkModel extends BaseDatabaseModel
{
    protected $recordType = 'pastwalk';

    private $item = false;

    /**
     * @param   int|null  $id  Falls back to the "id" request variable.
     *
     * @return  \stdClass|null
     *
     * @since   1.0.0
     */
    public function getItem(?int $id = null): ?\stdClass
    {
        if ($this->item === false) {
            $id = $id ?: Factory::getApplication()->input->getInt('id', 0);
            $this->item = FrontendHelper::getPublishedItem($this->recordType, $id);
        }

        return $this->item;
    }
}
