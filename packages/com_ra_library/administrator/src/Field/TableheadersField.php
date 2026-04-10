<?php
namespace Ramblers\Component\Ra_library\Administrator\Field;

/*
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */


\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\HTML\HTMLHelper;

class TableheadersField extends TextField
{
    protected $type = 'Tableheaders';

    protected function getInput()
    {
        $value = $this->value ? json_decode($this->value, true) : [];
        $defaultHeaders = ['Header 1', 'Header 2', 'Header 3']; // Starting headers
        
        HTMLHelper::_('script', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js');
        HTMLHelper::_('script', 'com_ra_library/tableheaders.js', ['version' => 'auto', 'relative' => true]);
        HTMLHelper::_('stylesheet', 'com_ra_library/tableheaders.css', ['version' => 'auto', 'relative' => true]);
        
        $html = '<div id="' . $this->id . '" class="table-headers-container" data-name="' . $this->name . '">';
        $html .= '<button type="button" class="btn btn-sm btn-success add-header-btn">+ Add Header</button>';
        $html .= '<div class="headers-list">';
        
        foreach ($defaultHeaders as $i => $header) {
            $savedValue = $value[$i] ?? $header;
            $html .= $this->renderHeaderField($i, $savedValue);
        }
        
        $html .= '</div>';
        $html .= '<input type="hidden" name="' . $this->name . '" class="headers-json" value="' . htmlspecialchars(json_encode($value)) . '">';
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderHeaderField($index, $value)
    {
        return '<div class="header-row" data-index="' . $index . '">
                    <span class="handle">☰</span>
                    <input type="text" class="header-text" value="' . htmlspecialchars($value) . '" placeholder="Column Header">
                    <button type="button" class="btn btn-sm btn-danger remove-header">×</button>
                </div>';
    }
}