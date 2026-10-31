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
use \Ramblers\Component\Ra_library\Administrator\Helper\ImageGalleryHelper;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
        ->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');

// Background photo uploader (saved records only - see photouploader.js and
// ImageUploadTrait::uploadImage()).
$uploadLimits = ImageGalleryHelper::getUploadLimits();
$uploadLimits['saved'] = \count((array) ($this->item->images ?? []));
$uploadLimits['remaining'] = max(0, $uploadLimits['maxImages'] - $uploadLimits['saved']);
$recordIsSaved = !empty($this->item->id);

// Keeps the "Photo limits (n of 50 used)" note current (see photolimits.js).
$wa->registerAndUseScript('com_ra_library.photolimits', 'media/com_ra_library/js/fields/photolimits.js', [], ['defer' => true]);
$this->document->addScriptOptions('com_ra_library.photolimits', ['max' => (int) $uploadLimits['maxImages']]);
Text::script('COM_RA_LIBRARY_PHOTO_LIMITS_SUMMARY');
Text::script('COM_RA_LIBRARY_PHOTO_LIMIT_COUNT');
Text::script('COM_RA_LIBRARY_PHOTOS_REMOVE');
Text::script('COM_RA_LIBRARY_PHOTOS_REMOVE_CONFIRM');
$fmtLimit = static function ($bytes) {
    return $bytes > 0 ? HTMLHelper::_('number.bytes', $bytes, 'auto', 1) : Text::_('COM_RA_LIBRARY_PHOTO_LIMIT_NONE');
};

if ($recordIsSaved) {
    $wa->registerAndUseStyle('com_ra_library.photouploader', 'media/com_ra_library/css/fields/photouploader.css');
    $wa->registerAndUseScript('com_ra_library.photouploader', 'media/com_ra_library/js/fields/photouploader.js', [], ['defer' => true]);
    $this->document->addScriptOptions('com_ra_library.photouploader', [
        'url' => Route::_('index.php?option=com_ra_library&task=pastwalk.uploadImage&format=json', false),
        'token' => \Joomla\CMS\Session\Session::getFormToken(),
        'id' => (int) $this->item->id,
        'maxFileBytes' => $uploadLimits['maxFileBytes'],
        'remaining' => $uploadLimits['remaining'],
    ]);

    foreach (['WAITING', 'DONE', 'RELOAD', 'DUP_SHORT', 'ERR_SERVER', 'ERR_NETWORK', 'ERR_TYPE', 'ERR_SIZE', 'ERR_FULL', 'SUM_ADDED', 'SUM_SKIPPED', 'SUM_FAILED'] as $key) {
        Text::script('COM_RA_LIBRARY_UPLOAD_' . $key);
    }
}

// "Reorder photos" sub-tab (grid of the same photos - see photogrid.js).
$wa->registerAndUseStyle('com_ra_library.photogrid', 'media/com_ra_library/css/fields/photogrid.css');
$wa->registerAndUseScript('com_ra_library.photogrid', 'media/com_ra_library/js/fields/photogrid.js', [], ['defer' => true]);
// Hover preview + large viewer: also needed by the reorder grid and by photos
// uploaded in this session, even when the gallery was empty on page load.
$wa->registerAndUseStyle('fields.imagepreview', 'media/com_ra_library/css/fields/imagepreview.css');
$wa->registerAndUseScript('fields.imagepreview', 'media/com_ra_library/js/fields/imagepreview.js', [], ['defer' => true]);
Text::script('COM_RA_LIBRARY_IMAGE_VIEW_LARGER');
Text::script('COM_RA_LIBRARY_IMAGE_VIEWER_CLOSE');
Text::script('COM_RA_LIBRARY_IMAGE_VIEWER_PREV');
Text::script('COM_RA_LIBRARY_IMAGE_VIEWER_NEXT');
Text::script('COM_RA_LIBRARY_IMAGE_VIEWER_COUNT');
Text::script('COM_RA_LIBRARY_PHOTOS_FEATURED');
Text::script('COM_RA_LIBRARY_PHOTOS_MOVE_EARLIER');
Text::script('COM_RA_LIBRARY_PHOTOS_MOVE_LATER');

// Reload the form when the category changes so category-specific custom
// fields show straight away (see categoryreload.js).
$wa->registerAndUseScript('com_ra_library.categoryreload', 'media/com_ra_library/js/fields/categoryreload.js', [], ['defer' => true], ['core']);
$wa->useScript('webcomponent.core-loader');
$this->document->addScriptOptions('com_ra_library.categoryreload', ['section' => 'pastwalk']);
Text::script('COM_RA_LIBRARY_CATEGORY_RELOAD_FILES');
?>

<form
    action="<?php echo Route::_('index.php?option=com_ra_library&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="pastwalk-form" class="form-validate form-horizontal">

    <?php echo HTMLHelper::_('uitab.startTabSet', 'pastwalkTab', ['active' => 'details']); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'pastwalkTab', 'details', Text::_('COM_RA_LIBRARY_TAB_DETAILS')); ?>
    <?php echo $this->form->renderFieldset('details'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'pastwalkTab', 'images', Text::_('COM_RA_LIBRARY_FIELDSET_IMAGES')); ?>
    <div class="mb-4 p-3 border rounded">
        <?php if ($recordIsSaved) : ?>
            <div class="ra-uploader" id="ra-uploader">
                <label class="form-label fw-bold" for="ra-uploader-input"><?php echo Text::_('COM_RA_LIBRARY_UPLOAD_LABEL'); ?></label>
                <input type="file" class="form-control" id="ra-uploader-input" multiple accept="image/jpeg,image/png,image/gif">
                <div class="form-text"><?php echo Text::_('COM_RA_LIBRARY_UPLOAD_DESC'); ?></div>
                <ul class="ra-uploader-list" id="ra-uploader-list" aria-live="polite"></ul>
                <div id="ra-uploader-status" class="form-text" aria-live="polite"></div>
            </div>
        <?php else : ?>
            <div class="alert alert-info mb-0"><?php echo Text::_('COM_RA_LIBRARY_UPLOAD_SAVE_FIRST'); ?></div>
        <?php endif; ?>
        <details class="mt-2 small ra-photo-limits">
            <summary id="ra-photo-limits-summary"><?php echo Text::sprintf('COM_RA_LIBRARY_PHOTO_LIMITS_SUMMARY', (int) $uploadLimits['saved'], (int) $uploadLimits['maxImages']); ?></summary>
            <ul class="mb-0 mt-1">
                <li id="ra-photo-limits-count"><?php echo Text::sprintf('COM_RA_LIBRARY_PHOTO_LIMIT_COUNT', (int) $uploadLimits['maxImages'], (int) $uploadLimits['saved'], (int) $uploadLimits['remaining']); ?></li>
                <li><?php echo Text::sprintf('COM_RA_LIBRARY_PHOTO_LIMIT_EACH', $fmtLimit($uploadLimits['maxFileBytes'])); ?></li>
                <li><?php echo Text::_('COM_RA_LIBRARY_PHOTO_LIMIT_TYPES'); ?></li>
                <li><?php echo Text::sprintf('COM_RA_LIBRARY_PHOTO_LIMIT_STORED', (int) $uploadLimits['maxPixels']); ?></li>
                <li><?php echo Text::_('COM_RA_LIBRARY_PHOTO_LIMIT_IMMEDIATE'); ?></li>
            </ul>
        </details>
    </div>
    <div class="ra-photo-tabs" id="ra-photo-tabs">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link active" id="ra-photos-tab-list" role="tab"
                        data-ra-photos-tab="list" aria-selected="true" aria-controls="ra-photos-pane-list"><?php echo Text::_('COM_RA_LIBRARY_PHOTOS_TAB_LIST'); ?></button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link" id="ra-photos-tab-grid" role="tab" tabindex="-1"
                        data-ra-photos-tab="grid" aria-selected="false" aria-controls="ra-photos-pane-grid"><?php echo Text::_('COM_RA_LIBRARY_PHOTOS_TAB_GRID'); ?></button>
            </li>
        </ul>
        <div id="ra-photos-pane-list" role="tabpanel" aria-labelledby="ra-photos-tab-list">
            <?php echo $this->form->renderFieldset('images'); ?>
        </div>
        <div id="ra-photos-pane-grid" role="tabpanel" aria-labelledby="ra-photos-tab-grid" hidden>
            <p class="form-text"><?php echo Text::_('COM_RA_LIBRARY_PHOTOS_GRID_HELP'); ?></p>
            <p class="alert alert-warning py-2" id="ra-photo-grid-removed" hidden><?php echo Text::_('COM_RA_LIBRARY_PHOTOS_REMOVED_NOTE'); ?></p>
            <ol class="ra-photo-grid" id="ra-photo-grid"></ol>
            <p class="text-muted" id="ra-photo-grid-empty" hidden><?php echo Text::_('COM_RA_LIBRARY_PHOTOS_GRID_EMPTY'); ?></p>
        </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'pastwalkTab', 'gpx', Text::_('COM_RA_LIBRARY_FIELDSET_GPX')); ?>
    <?php echo $this->form->renderFieldset('gpx'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'pastwalkTab', 'documents', Text::_('COM_RA_LIBRARY_FIELDSET_DOCUMENTS')); ?>
    <?php echo $this->form->renderFieldset('documents'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'pastwalkTab', 'layout', Text::_('COM_RA_LIBRARY_FIELDSET_LAYOUT')); ?>
    <?php echo $this->form->renderFieldset('layout'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php
    // Custom Fields (Components > Ra_library > Fields): the System - Fields
    // plugin adds one "fields-<groupid>" fieldset per field group to this
    // form (nothing is added when no fields are defined for this context).
    foreach ($this->form->getFieldsets() as $fieldsetName => $fieldset) {
        if (strpos($fieldsetName, 'fields-') !== 0) {
            continue;
        }

        $fieldsLabel = !empty($fieldset->label) ? Text::_($fieldset->label) : Text::_('JGLOBAL_FIELDS');

        echo HTMLHelper::_('uitab.addTab', 'pastwalkTab', $fieldsetName, $fieldsLabel);
        echo $this->form->renderFieldset($fieldsetName);
        echo HTMLHelper::_('uitab.endTab');
    }
    ?>

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <input type="hidden" name="jform[id]" value="<?php echo isset($this->item->id) ? $this->item->id : ''; ?>" />
    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
