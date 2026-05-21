<?php

/**
 * @module      RA Header
 * @author      Chris Vaughan
 * @copyright   Copyright (C) 2023 Chris Vaughan webmaster@ramblers-webs.org.uk All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

// -----------------------------------------------------------------------
// Assets / CSS
// -----------------------------------------------------------------------
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle(
    'mod_raheader.style',
    'modules/mod_raheader/css/ramblersheader.css'
);

$document = Factory::getApplication()->getDocument();

// -----------------------------------------------------------------------
// Background colour inline style
// -----------------------------------------------------------------------
$background_style = $params->get('background_style', '_part');
if ($background_style === '') {
    $background_style = '_part';
}

$background_color = getRAColor($params->get('background_color'), $params->get('background_custom_color'));
if ($background_color !== '') {
    $document->addStyleDeclaration('#rt-top { background-color: ' . $background_color . '; }');
}

// -----------------------------------------------------------------------
// Build the header HTML
// -----------------------------------------------------------------------
$header = '';
$header .= '<div id="ramblersheader" class="ra' . $background_style . ' ' . $moduleclass_sfx . '">';

// Background / horizon image
$image    = $params->get('horizon_image', '');
$h_height = (int) $params->get('header_height', 94);
$h_padding = (int) $params->get('header_margin_top', 0);

if ($image !== '') {
    $document->addStyleDeclaration(
        'div#ramblersheader { background-image: url(' . Uri::base() . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '); '
        . 'padding-top: ' . $h_padding . 'px; height: ' . $h_height . 'px; }'
    );
}

// Logo
$limage = $params->get('logo_image', '');
if ($limage !== '') {
    $width        = (int) $params->get('logo_width', 91);
    $height       = (int) $params->get('logo_height', 91);
    $url          = $params->get('logo_url', '');
    $target       = $params->get('logo_url_target', '_blank');
    $logoMarginTop = (int) $params->get('logo_margin_top', 0);

    $header .= getRaImage('ralogo', $limage, $width, $height, $url, $target, $logoMarginTop);
    $document->addStyleDeclaration('img#ralogo { height: ' . $height . 'px; }');
}

// Title
$website_title = $params->get('website_title', '');
$title_color   = getRAColor($params->get('title_color'), $params->get('title_color_value'));
if (trim((string) $website_title) !== '') {
    $header .= '<h1 id="ramblerstitle" style="color: ' . htmlspecialchars($title_color, ENT_QUOTES, 'UTF-8') . '">'
             . htmlspecialchars($website_title, ENT_QUOTES, 'UTF-8')
             . '</h1>';
}

// Subtitle
$website_subtitle = $params->get('website_subtitle', '');
$subtitle_color   = getRAColor($params->get('subtitle_color'), $params->get('subtitle_color_value'));
if (trim((string) $website_subtitle) !== '') {
    $header .= '<h2 id="ramblerssubtitle" style="color: ' . htmlspecialchars($subtitle_color, ENT_QUOTES, 'UTF-8') . '">'
             . htmlspecialchars($website_subtitle, ENT_QUOTES, 'UTF-8')
             . '</h2>';
}

$header .= '</div>';
echo $header;

// -----------------------------------------------------------------------
// Helper functions
// -----------------------------------------------------------------------

/**
 * Resolve a Ramblers Pantone colour option to a hex string.
 */
function getRAColor(?string $option, ?string $customvalue): string
{
    $map = [
        'pantone0110' => '#D7A900',
        'pantone0159' => '#C75B12',
        'pantone0186' => '#C60C30',
        'pantone0555' => '#206C49',
        'pantone0583' => '#A8B400',
        'pantone1815' => '#782327',
        'pantone4485' => '#5B491F',
        'pantone5565' => '#8BA69C',
        'pantone7474' => '#007A87',
        'white'       => '#FFFFFF',
    ];

    if (isset($map[$option])) {
        return $map[$option];
    }

    if ($option === 'Transparent') {
        return '';
    }

    // 'Custom' or any unrecognised value — fall back to the custom field
    return (string) $customvalue;
}

/**
 * Build an <img> tag (optionally wrapped in an <a>).
 */
function getRaImage(string $id, string $image, int $width, int $height, string $url, string $target, int $logoMarginTop): string
{
    if ($image === '') {
        return '';
    }

    $img = '<img style="margin-top: ' . $logoMarginTop . 'px;" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"'
         . ' alt="Logo"'
         . ' src="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '"'
         . ' height="' . $height . '"'
         . ' width="' . $width . '" />';

    if ($url !== '') {
        $img = '<a target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"'
             . ' href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
             . $img . '</a>';
    }

    return $img;
}
