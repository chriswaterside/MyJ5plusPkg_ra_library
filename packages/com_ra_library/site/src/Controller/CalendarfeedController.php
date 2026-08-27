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
        $typeMask = $input->getString('type', null);
        $dowMask = $input->getString('dow', null);
        $gradesMask = $input->getString('grades', null);
        $distanceMask = $input->getString('dist', null);
        $limit = $input->getInteger('limit', 0);

        if ($groups === '') {
            throw new \RuntimeException('Incorrect feed options', 400);
        }
        if ($typeMask === null or $dowMask === null or $gradesMask === null or $distanceMask === null) {
            throw new \RuntimeException('Incorrect feed options', 400);
        }
        // fetch all walks and events for these groups
        $options = new Feedoptions();
        $options->addWalksManagerGroupWalks($groups);

        $feed = new WalksFeed($options);

        // remove filtered walks/events

        $types = ['Walks', 'Events'];
        $items = $this->getNamesFromMask($typeMask, $types);
        if (!in_array("Walks", $items)) {
            $feed->filterWalks();  // filter/remove walks
        }
        if (!in_array("Events", $items)) {
            $feed->filterEvents();  // filter/remove events
        }

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $items = $this->getNamesFromMask($dowMask, $days);
        $feed->filterDayofweek($items);

        $grades = ['Easy Access', 'Easy', 'Leisurely', 'Moderate', 'Strenuous', 'Technical'];
        $items = $this->getNamesFromMask($gradesMask, $grades);
        $feed->filterNationalGrade($items);

        // 'Not defined: See description',
        // 'Up to 3 miles (5 km)',
        // '3+ to 5 miles (5-8 km)',
        // '5+ to 8 miles (8-13 km)',
        // '8+ to 10 miles (13-16 km)',
        // '10+ to 13 miles (16-21 km)',
        // '13+ to 15 miles (21-24 km)',
        // '15+ miles (24 km)'
        $distances = [[-1, 0], [0, 3], [3, 5], [5, 8], [8, 10], [10, 13], [13, 15], [15, 99999]];
        $items = $this->getNamesFromMask($distanceMask, $distances);
        $feed->filterFeedDistances($items);
        // finally limit number of walks/events
        $feed->limitNumberWalks($limit);

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

    public function getNamesFromMask(int $mask, $options): array {
        return array_values(array_filter($options, fn($_, $i) => $mask & (1 << $i), ARRAY_FILTER_USE_BOTH));
    }
}
