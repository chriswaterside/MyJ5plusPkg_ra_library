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
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
        ->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');
?>

<form
    action="<?php echo Route::_('index.php?option=com_ra_library&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="route-form" class="form-validate form-horizontal">

    <?php echo HTMLHelper::_('uitab.startTabSet', 'routeTab', ['active' => 'details']); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'routeTab', 'details', Text::_('COM_RA_LIBRARY_TAB_DETAILS')); ?>
    <?php echo $this->form->renderFieldset('details'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'routeTab', 'images', Text::_('COM_RA_LIBRARY_FIELDSET_IMAGES')); ?>
    <?php echo $this->form->renderFieldset('images'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'routeTab', 'gpx', Text::_('COM_RA_LIBRARY_FIELDSET_GPX')); ?>
    <?php echo $this->form->renderFieldset('gpx'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'routeTab', 'documents', Text::_('COM_RA_LIBRARY_FIELDSET_DOCUMENTS')); ?>
    <?php echo $this->form->renderFieldset('documents'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'routeTab', 'layout', Text::_('COM_RA_LIBRARY_FIELDSET_LAYOUT')); ?>
    <?php echo $this->form->renderFieldset('layout'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'routeTab', 'route_points', Text::_('COM_RA_LIBRARY_FIELDSET_ROUTEPOINTS')); ?>
    <?php echo $this->form->renderFieldset('route_points'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <input type="hidden" name="jform[id]" value="<?php echo isset($this->item->id) ? $this->item->id : ''; ?>" />
    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
