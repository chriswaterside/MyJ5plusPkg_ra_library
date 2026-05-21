<?php

defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Router\Route;

$this->document->getWebAssetManager()->useScript('modal-content-select');

$formAction = Route::_('index.php?option=com_ra_library&view=folder&layout=modal&tmpl=component');

echo '<form action="' . $formAction . '" method="post" name="adminForm" id="adminForm">';
echo "Click folder names to open";
echo '<div class="container-fluid">';

$siteRoot = rtrim(str_replace('\\', '/', JPATH_SITE), '/');

$renderFolderTree = function (string $path, string $siteRoot, int $level = 0) use (&$renderFolderTree) {
    if (!is_dir($path)) {
        return;
    }

    $folderName = basename($path);
    $relativeFolder = ltrim(str_replace($siteRoot, '', str_replace('\\', '/', $path)), '/');

    echo '<details' . ($level === 0 ? '' : ' class="ms-3"') . '>';

    echo '<summary class="d-flex align-items-center justify-content-start p-2 bg-light rounded-top">';
    echo '<button type="button" class="btn btn-sm btn-outline-primary me-2 px-3" data-content-select';
    echo ' data-id="' . htmlspecialchars($relativeFolder, ENT_QUOTES) . '"';
    echo ' data-title="' . htmlspecialchars($relativeFolder, ENT_QUOTES) . '"';
    echo ' title="Select folder">';
  //  echo '<i class="fa fa-check me-1"></i>Select';
    echo 'Select';
    echo '</button>';
    echo '<span class="fw-medium flex-grow-1">' . htmlspecialchars($folderName, ENT_QUOTES) . '</span>';
    echo '</summary>';

    $value = $relativeFolder;

    $files = Folder::files($path, '.', false, true);
    if ($files) {
        echo '<ul class="list-unstyled ms-3">';
        foreach ($files as $file) {
            echo '<li>' . htmlspecialchars(basename($file), ENT_QUOTES) . '</li>';
        }
        echo '</ul>';
    }

    $subfolders = Folder::folders($path, '.', false, true);
    if ($subfolders) {
        foreach ($subfolders as $subfolder) {
            $renderFolderTree($subfolder, $siteRoot, $level + 1);
        }
    }

    echo '</details>';
};

foreach ($this->roots as $rootLabel => $fsRoot) {
    echo '<section class="mb-4">';
    echo '<h3>' . htmlspecialchars($rootLabel, ENT_QUOTES) . '</h3>';

    if (!is_dir($fsRoot)) {
        echo '<p>No folder found.</p>';
        echo '</section>';
        continue;
    }

    $renderFolderTree($fsRoot, $siteRoot);
    echo '</section>';
}

echo '</div>';
echo '</form>';
