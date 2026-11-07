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
use Joomla\CMS\Toolbar\Toolbar;
use RamblersWebs\Module\Rafooter\Helper\RafooterHelper;
use Ramblers\Component\Ra_library\Site\Library\Load\Load;

$startyear = $params->get('startyear');
$copyrighttext = $params->get('copyrighttext');
$ramblerswebs = $params->get('ramblerswebs');
$ramblersdisableprivacy = $params->get('ramblersdisableprivacy');
$footersize = $params->get('footersize');
$footerstyle = $params->get('footerstyle', 1);
$background_color = (string) $params->get('background_color', 'darkgrey');
$custom_background_color = $params->get('custom_background_colour_value');
$textcolour = $params->get('textcolour', 0);
$customcssfile = $params->get('custom_css_file');
$footerSelector = RafooterHelper::resolveTarget($params->get('footer_target'), $params->get('footer_selector'));

$document = Factory::getDocument();

echo "<div class='mod-footer-content'>";

if ($footerstyle == 1) {
    $backcolour = RafooterHelper::getFooterColor($background_color, $custom_background_color);
    $text = $footerSelector . " {background-color: " . $backcolour . " !important;}";

    // Text colours (white text on dark backgrounds, dark text otherwise)
    $textHex = ($textcolour == 0) ? '#FFFFFF' : '#000000';
    $linkHex = ($textcolour == 0) ? '#CCCCCC' : '#111111';
    $text .= $footerSelector . " {color: " . $textHex . ";}";
    $text .= RafooterHelper::scope($footerSelector, ' a') . " {color: " . $linkHex . ";}";
    $text .= RafooterHelper::scope($footerSelector, ' h1') . ", " . RafooterHelper::scope($footerSelector, ' h2') . ", "
            . RafooterHelper::scope($footerSelector, ' h3') . ", " . RafooterHelper::scope($footerSelector, ' h4') . ", "
            . RafooterHelper::scope($footerSelector, ' h5') . ", " . RafooterHelper::scope($footerSelector, ' h6')
            . " {color: " . $textHex . ";}";
    $document->addStyleDeclaration($text);
}

if ($customcssfile !== null && $customcssfile !== '') {
    Load::addStyleSheet($customcssfile);
}
// Build footer HTML: simple vertical lists, modelled on the ramblers.org.uk footer (no address)
$e = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$link = static fn(string $url, string $text): string =>
    '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $text . '</a>';

$document->addStyleDeclaration(
    '.rafooter ul{list-style:none;margin:0 0 1.5rem;padding:0}'
    . '.rafooter li{margin:0 0 .35rem;padding:0;font-size:.9em}'
    . '.rafooter li:last-child{margin-bottom:0}'
);

$year = date('Y');
$range = trim((string) $startyear) !== '' ? $startyear . '-' . $year : $year;

// 1. Ramblers Privacy Policy: a list of its own, shown first
$privacy = [];
if (!$ramblersdisableprivacy) {
    $privacy[] = $link('https://www.ramblers.org.uk/privacy', 'Ramblers Privacy Policy');
}

// 2. Company, charity, copyright and hosting details
$info = [];
if ($params->get('showterms', 1)) {
    $info[] = $link('https://www.ramblers.org.uk/about-us/terms-and-conditions', 'Terms and Conditions');
}
if ($footersize == 1) {
    $info[] = "The Ramblers' Association is a company limited by guarantee, registered in England and Wales. Company registration no. 4458492.";
}
if ($footersize == 1 || $footersize == 0) {
    $info[] = 'Registered charity, England &amp; Wales no. 1093577, Scotland no. SC039799.';
}
$info[] = '&copy; ' . $e((string) $copyrighttext) . ' ' . $range;
if ($ramblerswebs != 0 && $footersize != 2) {
    $info[] = 'Hosted by ' . $link('https://www.ramblers-webs.org.uk/', 'www.ramblers-webs.org.uk') . ($footersize == 1 ? ' - centrally funded hosting for Areas and Groups' : '');
}

$footer = '<div class="footer rafooter">';
foreach ([$privacy, $info] as $group) {
    if ($group) {
        $footer .= '<ul><li>' . implode('</li><li>', $group) . '</li></ul>';
    }
}
$footer .= '</div>';

echo $footer;
echo "</div>";
