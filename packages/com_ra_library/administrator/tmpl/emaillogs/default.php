<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_ra_library.admin')
        ->useScript('com_ra_library.admin');

$user = Factory::getApplication()->getIdentity();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
$canOrder = $user->authorise('core.edit.state', 'com_ra_library');

if (!empty($saveOrder)) {
    $saveOrderingUrl = 'index.php?option=com_ra_library&task=emaillogs.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}
?>

<form action="<?php echo Route::_('index.php?option=com_ra_library&view=emaillogs'); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

                <div class="clearfix"></div>
                <table class="table table-striped" id="emaillogList">
                    <thead>
                        <tr>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_RA_LIBRARY_EMAILLOGS_DATETIME', 'a.datetime', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_RA_LIBRARY_EMAILLOGS_TO', 'a.to', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_RA_LIBRARY_EMAILLOGS_TITLE', 'a.title', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_RA_LIBRARY_EMAILLOGS_REPLYTO', 'a.replyto', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_RA_LIBRARY_EMAILLOGS_SENT', 'a.sent', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_RA_LIBRARY_EMAILLOGS_MESSAGE', 'a.message', $listDirn, $listOrder); ?>
                            </th>

                            <th scope="col" class="w-3 d-none d-lg-table-cell" >

                                <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>					</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
                                <?php echo $this->pagination->getListFooter(); ?>
                            </td>
                        </tr>
                    </tfoot>
                    <tbody <?php if (!empty($saveOrder)) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" <?php endif; ?>>
                        <?php
                        foreach ($this->items as $i => $item) :
                            $ordering = ($listOrder == 'a.ordering');
                            $canCreate = $user->authorise('core.create', 'com_ra_library');
                            $canEdit = $user->authorise('core.edit', 'com_ra_library');
                            $canCheckin = $user->authorise('core.manage', 'com_ra_library');
                            $canChange = $user->authorise('core.edit.state', 'com_ra_library');
                            ?>
                            <tr class="row<?php echo $i % 2; ?>" data-draggable-group='1' data-transition>
                                <td>
                                    <a href="<?php echo Route::_('index.php?option=com_ra_library&view=emaillog&id=' . (int) $item->id); ?>">
                                        <?php echo $item->datetime; ?>
                                </td>
                                <td>
                                    <?php echo $item->to; ?>
                                </td>
                                <td>
                                    <?php echo $this->escape($item->title); ?>
                                </td>
                                <td>
                                    <?php echo $item->replyto; ?>
                                </td>

                                <?php
                                if ($item->sent) {
                                    echo '<td>' . 'Yes' . '</td>';
                                } else {
                                    echo '<td>' . 'No' . '</td>';
                                }
                                ?>

                                <td>
                                    <?php echo $item->message; ?>
                                </td>

                                <td class="d-none d-lg-table-cell">
                                    <?php echo $item->id; ?>

                                </td>


                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <input type="hidden" name="task" value=""/>
                <input type="hidden" name="boxchecked" value="0"/>
                <input type="hidden" name="list[fullorder]" value="<?php echo $listOrder; ?> <?php echo $listDirn; ?>"/>
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>