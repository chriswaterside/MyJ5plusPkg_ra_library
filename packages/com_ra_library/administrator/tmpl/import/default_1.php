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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('bootstrap.tooltip');
?>

<form action="<?php echo Route::_('index.php?option=com_ra_library&view=import'); ?>" method="get" id="importSearchForm" class="mb-3">
    <input type="hidden" name="option" value="com_ra_library">
    <input type="hidden" name="view" value="import">

    <div class="mb-2">
        <label class="fw-bold"><?php echo Text::_('COM_RA_LIBRARY_IMPORT_GROUP_CODES_LABEL'); ?></label>
        <?php echo $this->groupCodesField->input; ?>
    </div>

    <div class="d-flex align-items-end gap-3">
        <div>
            <label for="period" class="fw-bold d-block"><?php echo Text::_('COM_RA_LIBRARY_IMPORT_PERIOD_LABEL'); ?></label>
            <select id="period" name="period" class="form-select" style="width:10em">
                <?php foreach ($this->periodOptions as $value => $option) : ?>
                    <option value="<?php echo $value; ?>" <?php echo $value === $this->period ? 'selected' : ''; ?>>
                        <?php echo Text::_($option[0]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary"><?php echo Text::_('COM_RA_LIBRARY_IMPORT_FETCH_BUTTON'); ?></button>
    </div>
</form>

<?php if ($this->fetchError !== null) : ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($this->fetchError); ?></div>
<?php endif; ?>

<?php if (empty($this->items) && $this->fetchError === null) : ?>
    <div class="alert alert-info"><?php echo Text::_('COM_RA_LIBRARY_IMPORT_NOTHING_TO_IMPORT'); ?></div>
<?php endif; ?>

<?php if (!empty($this->items)) : ?>
    <form action="<?php echo Route::_('index.php?option=com_ra_library&task=import.import'); ?>" method="post" id="importForm">
        <div class="mb-3">
            <label for="catid" class="fw-bold d-block"><?php echo Text::_('COM_RA_LIBRARY_IMPORT_CATID_LABEL'); ?></label>
            <?php echo $this->catidField->input; ?>
        </div>

        <p>
            <button type="submit" class="btn btn-primary"><?php echo Text::_('COM_RA_LIBRARY_IMPORT_SUBMIT_BUTTON'); ?></button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="RaImport.toggleAll(true)"><?php echo Text::_('JGLOBAL_CHECK_ALL'); ?></button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="RaImport.toggleAll(false)"><?php echo Text::_('COM_RA_LIBRARY_IMPORT_UNCHECK_ALL'); ?></button>
            &nbsp;|&nbsp;
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="RaImport.togglePhotos(null, true)"><?php echo Text::_('COM_RA_LIBRARY_IMPORT_ALL_PHOTOS'); ?></button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="RaImport.togglePhotos(null, false)"><?php echo Text::_('COM_RA_LIBRARY_IMPORT_NO_PHOTOS'); ?></button>
        </p>

        <?php foreach ($this->items as $row) : ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input ra-import-walk" name="wm_ids[]" value="<?php echo htmlspecialchars($row->wm_id); ?>"
                               id="wm_<?php echo htmlspecialchars($row->wm_id); ?>">
                        <label class="form-check-label" for="wm_<?php echo htmlspecialchars($row->wm_id); ?>">
                            <strong><?php echo htmlspecialchars($row->title); ?></strong>
                            &nbsp;-&nbsp;<?php echo htmlspecialchars($row->walk_date_display); ?>
                        </label>
                    </div>
                    <div class="text-muted small mt-1">
                        <?php if ($row->leader !== '') : ?>
                            <?php echo Text::_('COM_RA_LIBRARY_PASTWALK_FIELD_WALK_LEADER_LABEL'); ?>: <?php echo htmlspecialchars($row->leader); ?> &nbsp;|&nbsp;
                        <?php endif; ?>
                        <?php if ($row->grade !== '') : ?>
                            <?php echo htmlspecialchars($row->grade); ?> &nbsp;|&nbsp;
                        <?php endif; ?>
                        <?php if ($row->distance_miles !== null) : ?>
                            <?php echo $row->distance_miles; ?> <?php echo Text::_('COM_RA_LIBRARY_IMPORT_MILES_ABBR'); ?>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($row->photos)) : ?>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-link p-0" onclick="RaImport.togglePhotos('<?php echo htmlspecialchars($row->wm_id, ENT_QUOTES); ?>', true)"><?php echo Text::_('COM_RA_LIBRARY_IMPORT_ALL_PHOTOS'); ?></button>
                            &nbsp;/&nbsp;
                            <button type="button" class="btn btn-sm btn-link p-0" onclick="RaImport.togglePhotos('<?php echo htmlspecialchars($row->wm_id, ENT_QUOTES); ?>', false)"><?php echo Text::_('COM_RA_LIBRARY_IMPORT_NO_PHOTOS'); ?></button>
                            <div class="mt-1 d-flex flex-wrap gap-2 ra-import-photos" data-wm-id="<?php echo htmlspecialchars($row->wm_id, ENT_QUOTES); ?>">
                                <?php foreach ($row->photos as $index => $photo) : ?>
                                    <label class="text-center" style="cursor:pointer">
                                        <img src="<?php echo htmlspecialchars($photo->thumb); ?>" alt="<?php echo htmlspecialchars($photo->alt); ?>"
                                             style="max-width:90px;max-height:90px;display:block;border:2px solid transparent" class="ra-import-photo-thumb">
                                        <input type="checkbox" class="form-check-input" name="photos[<?php echo htmlspecialchars($row->wm_id); ?>][]" value="<?php echo (int) $index; ?>">
                                        <?php echo Text::_('COM_RA_LIBRARY_IMPORT_PHOTO_TICK'); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary"><?php echo Text::_('COM_RA_LIBRARY_IMPORT_SUBMIT_BUTTON'); ?></button>

        <?php echo HTMLHelper::_('form.token'); ?>
    </form>

    <script>
        var RaImport = {
            toggleAll: function (state) {
                document.querySelectorAll('.ra-import-walk').forEach(function (el) {
                    el.checked = state;
                });
            },
            togglePhotos: function (wmId, state) {
                var selector = wmId
                    ? '.ra-import-photos[data-wm-id="' + wmId + '"] input[type="checkbox"]'
                    : '.ra-import-photos input[type="checkbox"]';
                document.querySelectorAll(selector).forEach(function (el) {
                    el.checked = state;
                });
            }
        };
    </script>
<?php endif; ?>
