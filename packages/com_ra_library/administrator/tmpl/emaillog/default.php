<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Anand
 * @author     Super User <dev@component-creator.com>
 * @copyright  2023 Super User
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

$item=$this->item;
?>

<div class="item_fields">
    <table class="table">
        
            <?php
            echo '<tr><th>Id</th>';
            echo '<td>' . $item->id . '</td></tr>';
            echo '<tr><th>datetime</th>';
            echo '<td>' . $item->datetime . '</td></tr>';
            echo '<tr><th>To</th>';
            echo '<td>' . $item->to . '</td></tr>';
            echo '<tr><th>Reply to</th>';
            echo '<td>' . $item->replyto . '</td></tr>';
            echo '<tr><th>Title</th>';
            echo '<td>' . $this->escape($item->title) . '</td></tr>';
            echo '<tr><th>Sent</th>';
            if ($item->sent) {
                echo '<td>' . 'Yes' . '</td></tr>';
            } else {
                echo '<td>' . 'No' . '</td></tr>';
            }
            echo '<tr><th>Message</th>';
            echo '<td>' . $item->message . '</td></tr>';
            ?>
        
    </table>

</div>

