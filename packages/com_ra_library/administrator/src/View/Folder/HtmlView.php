<?php

namespace Ramblers\Component\Ra_library\Administrator\View\Folder;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    public array $roots = [];

    public function display($tpl = null): void
    {
        $this->document->getWebAssetManager()->useScript('modal-content-select');

        $this->roots = [
            'images' => JPATH_SITE . '/images',
            'files'  => JPATH_SITE . '/files',
        ];

        parent::display($tpl);
    }
}