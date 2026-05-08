<?php

/*
 * Check if gpx file exists.
 *      parameters
 *         Get data
 *             name - name of image
 *           
 * 
 *      url
 *         index.php?option=com_ra_library&view=checkimage&format=json
 * 
 * 
 */

namespace Ramblers\Component\Ra_library\Site\View\Checkimage;

use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\View\JsonView as BaseJsonView;

// No direct access
defined('_JEXEC') or die;

class JsonView extends BaseJsonView {

    public function display($tpl = null) {
        try {
            $filename = 'images/gpxsymbols/' . htmlspecialchars($_GET["file"]);
            $result = file_exists($filename);
            $record = (object) [
                        'exists' => $result,
            ];
            echo new JsonResponse($record);
        } catch (Exception $e) {
            echo new JsonResponse($e);
        }
    }
}
