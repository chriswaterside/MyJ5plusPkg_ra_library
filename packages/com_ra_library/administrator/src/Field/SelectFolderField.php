<?php

namespace Ramblers\Component\Ra_library\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ModalSelectField;

class SelectFolderField extends ModalSelectField
{
    protected $type = 'SelectFolder';

    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        $data['urlSelect'] = 'index.php?option=com_ra_library&view=folder&layout=modal&tmpl=component';
        $data['select'] = true;
        $data['clear'] = true;
        $data['titleSelect'] = $this->titleSelect ?: 'Select folder';

        return $data;
    }
}