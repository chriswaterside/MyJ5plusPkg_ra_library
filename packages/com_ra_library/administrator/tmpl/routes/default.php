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
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Ramblers\Component\Ra_library\Administrator\Helper\Ra_libraryHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

$user = Factory::getApplication()->getIdentity();
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
?>

<form action="<?php echo Route::_('index.php?option=com_ra_library&view=routes'); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

                <div class="clearfix"></div>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table table-striped" id="routeList">
                        <thead>
                            <tr>
                                <th class="w-1 text-center">
                                    <input type="checkbox" autocomplete="off" class="form-check-input" name="checkall-toggle" value=""
                                           title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)"/>
                                </th>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                                </th>
                                <th class='left'>
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_RA_LIBRARY_ROUTES_TITLE', 'a.title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo Text::_('COM_RA_LIBRARY_PASTWALKS_FILES_HEADING'); ?>
                                </th>
                                <th scope="col" class="w-10 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_RA_LIBRARY_PASTWALK_FIELD_NATIONAL_GRADE_LABEL', 'a.national_grade', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_RA_LIBRARY_PASTWALK_FIELD_DISTANCE_MILES_LABEL', 'a.distance_km', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <td colspan="7">
                                    <?php echo $this->pagination->getListFooter(); ?>
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) :
                                $canEdit = $user->authorise('routes.edit', 'com_ra_library') || $user->authorise('routes.edit.own', 'com_ra_library');
                                $canChange = $user->authorise('routes.edit.state', 'com_ra_library');
                                $canCheckin = $user->authorise('routes.manage', 'com_ra_library');
                                ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'routes.', $canChange, 'cb'); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($item->checked_out)) : ?>
                                            <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'routes.', $canCheckin); ?>
                                        <?php endif; ?>
                                        <?php if ($canEdit) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_ra_library&task=route.edit&id=' . (int) $item->id); ?>">
                                                <?php echo $this->escape($item->title); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo $this->escape($item->title); ?>
                                        <?php endif; ?>
                                        <?php $categoryPath = !empty($item->catid) ? Ra_libraryHelper::getCategoryTitlePath((int) $item->catid) : ''; ?>
                                        <?php if ($categoryPath !== '') : ?>
                                            <div class="small text-muted"><?php echo $this->escape($categoryPath); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="d-none d-md-table-cell small">
                                        <?php
                                        $fileCounts = [];

                                        if (!empty($item->image_count)) {
                                            $fileCounts[] = (int) $item->image_count . ' ' . Text::_('COM_RA_LIBRARY_PASTWALKS_FILES_IMAGES');
                                        }

                                        if (!empty($item->gpx_count)) {
                                            $fileCounts[] = (int) $item->gpx_count . ' ' . Text::_('COM_RA_LIBRARY_PASTWALKS_FILES_GPX');
                                        }

                                        if (!empty($item->document_count)) {
                                            $fileCounts[] = (int) $item->document_count . ' ' . Text::_('COM_RA_LIBRARY_PASTWALKS_FILES_PDF');
                                        }
                                        ?>
                                        <?php if (empty($fileCounts)) : ?>
                                            <span class="text-muted">&mdash;</span>
                                        <?php else : ?>
                                            <?php echo implode('<br>', array_map([$this, 'escape'], $fileCounts)); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center d-none d-md-table-cell">
                                        <?php echo $this->escape($item->national_grade ?? ''); ?>
                                    </td>
                                    <td class="text-center d-none d-md-table-cell">
                                        <?php echo $item->distance_km !== null ? round($item->distance_km / 1.609344, 1) . ' mi' : ''; ?>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <?php echo $item->id; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <input type="hidden" name="task" value=""/>
                <input type="hidden" name="boxchecked" value="0"/>
                <?php // No manual list[fullordering] hidden field here - the searchtools
                      // "list" sublayout already renders that field itself; see
                      // tmpl/pastwalks/default.php for the fuller explanation. ?>
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
