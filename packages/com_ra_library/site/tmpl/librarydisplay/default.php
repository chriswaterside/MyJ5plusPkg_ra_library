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

use Ramblers\Component\Ra_library\Site\Helper\DisplayHelper;

echo '<div class="item_fields">';
if ($this->params->get('show_page_heading')) {
    echo '<div class="page-header">';
    echo '<h1>' . $this->escape($this->params->get('page_heading')) . '</h1>';
    echo '</div>';
}
$displayoption=$this->item->displayoption; 
$options=$this->item->options;
$display = new DisplayHelper($displayoption,$options);
$display->Display();

echo '</div>';
