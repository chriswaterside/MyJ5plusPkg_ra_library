<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks;

/**
 * Description of addSchema
 *
 * @author chris
 */
use Joomla\CMS\Factory;

class Addschema {

    function display($walks) {
        $no = 0;
        $schemawalks = array();
        $walks->sort(Walk::SORT_DATE, Walk::SORT_TIME, Walk::SORT_DISTANCE);
        $items = $walks->allWalks();
        foreach ($items as $walk) {
            if (!$walk->isCancelled()) {
                $no += 1;
                $schemawalks[] = $walk->_getWalkSchema();
            }
            if ($no > 7) {
                break;
            }
        }

        $script = json_encode($schemawalks);
        $script = str_replace('"context":', '"@context":', $script);
        $script = str_replace('"type":', '"@type":', $script);
        $script = str_replace('\/', '/', $script);
        $doc = Factory::getDocument();
        $doc->addScriptDeclaration($script, "application/ld+json");
    }
}
