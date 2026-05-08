<?php

namespace Ramblers\Component\Ra_library\Site\View\Upload;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView {

    protected $form;

    public function display($tpl = null) {
        $this->form = $this->get('Form');

        // set allowed file types
        $app = Factory::getApplication();
        $menu = $app->getMenu()->getActive();
        $params = $menu->getParams();
        $extensions = $params->get('allowed_extensions', '*');
        //      $acceptValue = '.' . preg_replace('/\s*,\s*/', ',.', $allowedExts);
        $acceptValue = preg_replace_callback('/([^,]+)(?=(?:,|$))/', function ($matches) {
            return (strpos($matches[1], '.') === 0 ? $matches[1] : '.' . ltrim($matches[1]));
        }, $extensions);
        $this->form->setFieldAttribute('file', 'accept', $acceptValue);

        parent::display($tpl);
    }
}
