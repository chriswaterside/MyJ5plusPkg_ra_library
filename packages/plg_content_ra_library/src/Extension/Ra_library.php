<?php

//              Part of the RA_library package

namespace Ramblers\Plugin\Content\Ra_library\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Ramblers\Component\Ra_library\Site\Helper\PluginHelper;

final class Ra_library extends CMSPlugin implements SubscriberInterface {

    public static function getSubscribedEvents(): array {
        return [
            'onContentPrepare' => 'onContentPrepare',
        ];
    }

    public function onContentPrepare(ContentPrepareEvent $event): void {
        $context = $event->getContext();
        $item = $event->getItem();

        if ($this->getApplication()->isClient('api') || !isset($item->text)) {
            return;
        }

        $text = $item->text;

        // Log that we are in onContentPrepare (for debugging)
        //   $logger = \Joomla\CMS\Factory::getLogger();
        //   $logger->info('Ra_library onContentPrepare fired for context: ' . $context);
        // Match {ra_library:VIEW:ID}
        if (!preg_match_all('#\{ra\_library\:([a-z0-9_]+)\:([0-9]+)\}#', $text, $matches, PREG_SET_ORDER)) {
            return;
        }

        foreach ($matches as $match) {
            $viewName = $match[1];
            $displayId = (int) $match[2];

            if ($displayId <= 0) {
                continue;
            }

            $display = $this->loadDisplay($displayId);

            if (!$display) {
                $html = "<p class='alert-error'><b>ERROR: RA Library Display not found ID:" . $displayId . "</b></p>";
            } else {
                $html = PluginHelper::renderView($viewName, $display);
            }

            $text = preg_replace(
                    '#\{ra\_library\:' . preg_quote($viewName, '#') . '\:' . $displayId . '\}#i',
                    $html,
                    $text,
                    1
            );
        }

        $item->text = $text;
    }

    private function loadDisplay($id) {


        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__ra_library_displays'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
        return $db->setQuery($query)->loadObject();
    }
}
