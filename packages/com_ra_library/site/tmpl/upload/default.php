<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

$app = Factory::getApplication();
$user = $app->getIdentity();

$menu = $app->getMenu()->getActive();
$params = $menu->getParams();

$textBefore = $params->get('text_before', '');
$textAfter = $params->get('text_after', '');

if ($user->guest) {
    echo '<p>You must be logged in to upload a file.</p>';
    return;
}

if ($textBefore) {

    echo '<div class="ra-upload-before">';
    echo HTMLHelper::_('content.prepare', $textBefore);
    echo '</div>';
}

echo '<form action="';
echo Route::_('index.php?option=com_ra_library&view=upload');
echo '" method="post"
      enctype="multipart/form-data"
      name="adminForm"
      id="adminForm">';
echo $this->form->renderField('file');

echo '<input type="hidden" name="task" value="upload.submit" />';
echo '<input type="hidden" name="' . Session::getFormToken() . '" value="1" />';
echo '<button type="submit" class="btn btn-primary">';
echo Text::_('Submit file');
echo '</button>';
echo '</form>';

if ($textAfter) {
    echo '<div class="ra-upload-after">';
    echo HTMLHelper::_('content.prepare', $textAfter);
    echo '</div>';
}