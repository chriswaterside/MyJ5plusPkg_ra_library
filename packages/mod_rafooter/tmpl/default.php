<?php
/**
 * @module      RA Footer
 * @author      Chris Vaughan
 * @website     http://ramblers-webs.org.uk/
 * @copyright   Copyright (C) 2013 Chris Vaughan <webmaster@ramblers-webs.org.uk>. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use RamblersWebs\Module\Rafooter\Helper\RafooterHelper;

$revisionversion = "3.0.13";

$startyear                = $params->get('startyear');
$copyrighttext            = $params->get('copyrighttext');
$ramblerswebs             = $params->get('ramblerswebs');
$ramblersdisableprivacy   = $params->get('ramblersdisableprivacy');
$footersize               = $params->get('footersize');
$footerstyle              = $params->get('footerstyle');
$background_color         = $params->get('background_color');
$custom_background_color  = $params->get('custom_background_colour_value');
$textcolour               = $params->get('textcolour');
$standardimage            = $params->get('standardimage');
$footer_image             = $params->get('footer_image');
$imageposition            = $params->get('imageposition');
$customcssfile            = $params->get('custom_css_file');
$alternativecssfile       = $params->get('alternative_css_file');

$document = Factory::getDocument();
$baseUrl  = Uri::base();

echo "<div class='mod-footer-content'>";

$url = null;
if ($footerstyle == 1) {
    if ($standardimage == 1) {
        $url = $baseUrl . "modules/mod_rafooter/images/footer-bg.png";
    } elseif ($footer_image !== null) {
        $url = $baseUrl . $footer_image;
    }

    if ($url !== null) {
        $text = "#rt-bottom {background: url(" . $url . ") no-repeat scroll bottom " . $imageposition . "; background-size:contain; min-height: 120px}";
    } else {
        $text = "";
    }

    $backcolour = RafooterHelper::getFooterColor($background_color, $custom_background_color);
    $text .= "#rt-footer,#rt-copyright {background-color: " . $backcolour . " !important;}";
    $document->addStyleDeclaration($text);

    if ($textcolour == 0) {
        $document->addStyleSheet($baseUrl . 'modules/mod_rafooter/css/footerwhitestyle.css?rev=' . $revisionversion);
    } else {
        $document->addStyleSheet($baseUrl . 'modules/mod_rafooter/css/footerdarkstyle.css?rev=' . $revisionversion);
    }
}

// Add stylesheets
if ($alternativecssfile !== null && $alternativecssfile !== '') {
    $document->addStyleSheet($baseUrl . $alternativecssfile);
} else {
    $document->addStyleSheet($baseUrl . 'modules/mod_rafooter/css/ramblers.css?rev=' . $revisionversion);
    if ($customcssfile !== null && $customcssfile !== '') {
        $document->addStyleSheet($baseUrl . $customcssfile);
    }
}

// Build footer HTML
$copyright_symbol = '&copy;';
$current_year     = date('Y');

$footer = '<div class="footer" id="rafooter">';

switch ($footersize) {
    case 0: // short
        $footer .= '<div>Ramblers Charity England &amp; Wales No: 1093577 Scotland No: SC039799</div>';
        $footer .= '<div>' . $copyright_symbol . ' ' . $copyrighttext . '-' . $current_year . '</div>';
        if ($ramblerswebs != 0) {
            $footer .= '<div>Hosted by <a href="https://www.ramblers-webs.org.uk/" target="_blank" rel="noopener noreferrer">www.ramblers-webs.org.uk</a></div>';
        }
        break;

    case 1: // full
        $footer .= 'Copyright ' . $copyright_symbol . ' ' . $startyear . '-' . $current_year . ' ' . $copyrighttext . '<br />';
        if ($ramblerswebs != 0) {
            $footer .= 'Hosted by <a href="https://www.ramblers-webs.org.uk/" target="_blank" rel="noopener noreferrer">www.ramblers-webs.org.uk</a>. Centrally funded hosting for Areas and Groups<br />';
        }
        $footer .= "The Ramblers' Association is a company limited by guarantee, registered in England and Wales. ";
        $footer .= "Company registration number: 4458492. Ramblers Charity England &amp; Wales No: 1093577 Scotland No: SC039799.<br />";
        $footer .= "Registered office: First Floor, 10 Queen Street Place, London EC4R 1BE";
        break;

    case 2: // no copyright text, just year range
        $footer .= 'Copyright ' . $copyright_symbol . ' ' . $startyear . '-' . $current_year . ' ' . $copyrighttext . '<br />';
        break;
}

$footer .= '</div>';

if (!$ramblersdisableprivacy) {
    $footer .= '<p><a href="https://www.ramblers.org.uk/privacy" target="_blank" rel="noopener noreferrer">Ramblers Privacy Policy</a></p>';
}

echo $footer;
echo "</div>";