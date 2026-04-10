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
use \Joomla\CMS\Language\Text;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
        ->useScript('form.validate');

$wa->registerAndUseStyle(
        'com_ra_library.custom',
        'administrator/components/com_ra_library/assets/css/ra_library.css'
);
HTMLHelper::_('bootstrap.tooltip');
?>

<form
    action="<?php echo Route::_('index.php?option=com_ra_library&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="librarydisplay-form" class="form-validate form-horizontal">


    <?php
    echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'programme'));
    ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'programme', Text::_('COM_RA_LIBRARY_TAB_PROGRAMME', true)); ?>
    <div class="row-fluid">
        <div class="col-md-12 form-horizontal">
            <fieldset class="adminform">
                <legend><?php echo Text::_('COM_RA_LIBRARY_FIELDSET_PROGRAMME'); ?></legend>
                <?php
                echo $this->form->renderField('title');

// Get ALL fields in your "options" group
                $optionsFields = $this->form->getGroup('options');

                if ($optionsFields && count($optionsFields)) {
                    echo '<div class="card">';
                    echo '<div class="card-header">';
                    echo '<h3>' . Text::_('Options') . '</h3>';
                    echo '</div>';
                    echo '<div class="card-body">';
                    foreach ($optionsFields as $field) {
                      echo  $field->renderField();
                    }
                    echo '</div>';
                    echo '</div>';
                }

                if ($this->state->params->get('save_history', 1)) :
                    ?>
                    <div class="control-group">
                        <div class="control-label"><?php echo $this->form->getLabel('version_note'); ?></div>
                        <div class="controls"><?php echo $this->form->getInput('version_note'); ?></div>
                    </div>
                <?php endif; ?>
            </fieldset>
        </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <input type="hidden" name="jform[id]" value="<?php echo isset($this->item->id) ? $this->item->id : ''; ?>" />

    <input type="hidden" name="jform[state]" value="<?php echo isset($this->item->state) ? $this->item->state : ''; ?>" />

    <?php echo $this->form->renderField('created_by'); ?>
    <?php echo $this->form->renderField('modified_by'); ?>


    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>

</form>
