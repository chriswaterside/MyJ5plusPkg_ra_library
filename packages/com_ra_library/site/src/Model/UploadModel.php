<?php


namespace Ramblers\Component\Ra_library\Site\Model;
defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\FormModel;

class UploadModel extends FormModel
{
    public function getForm($data = [], $loadData = true): ?Form
    {
        return $this->loadForm(
            'com_ra_library.upload',
            'upload',
            ['control' => 'jform', 'load_data' => $loadData]
        );
    }

    protected function loadFormData()
    {
        return $this->getState('upload.data', []);
    }
}