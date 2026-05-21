<?php

namespace Ramblers\Component\Ra_library\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Feedoptions;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Feed as WalksFeed;
use Ramblers\Component\Ra_library\Site\Library\Event\Feed as EventFeed;
use Ramblers\Component\Ra_library\Site\Library\Event\Group;

// index.php?option=com_ra_library&task=calendarfeed.calendarfeed&groups=...

class CalendarfeedController extends BaseController {

    public function calendarfeed(): void {
        $app = Factory::getApplication();
        $input = $app->input;

        $groups = $input->getString('groups', '');

        if ($groups === '') {
            throw new \RuntimeException('Incorrect feed options', 400);
        }

        $options = new Feedoptions();
        $options->addWalksMangerGroupWalks($groups);

        $feed = new WalksFeed($options);
        $group = new Group();
        $group->addWalks($feed);

        $events = new EventFeed();
        $ics = $events->getText($group);

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="calendar.ics"');
        header('X-Content-Type-Options: nosniff');

        echo $ics;
        $app->close();
    }
}
