<?php

namespace Ramblers\Component\Ra_library\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Feedoptions;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Feed as WalksFeed;
use Ramblers\Component\Ra_library\Site\Library\Ics\Content;
use Ramblers\Component\Ra_library\Site\Library\Ics\Feedfilters;

/*
 * index.php?option=com_ra_library&task=calendarfeed.calendarfeed&groups=...
 *
 * There are two ways to say which walks/events are wanted:
 *
 * 1. By rule
 *      groups   comma separated group codes (required), e.g. SR05,WR10
 *      notype   walks and/or events to leave out
 *      nodow    days to leave out: mon,tue,wed,thu,fri,sat,sun
 *      nograde  grades to leave out: ea (easy access), e (easy), l (leisurely), m (moderate), s (strenuous), t (technical)
 *      nodist   distances to leave out: undefined,upto3,3to5,5to8,8to10,10to13,13to15,over15
 *      limit    maximum number of walks/events
 *    The no... filters are optional: a filter that is left out leaves everything in
 *    (see Library/Ics/Feedfilters.php).
 *
 * 2. By list
 *      ids      comma separated Walks Manager ids (digits and commas only, at most MAX_IDS)
 *               Walks/events more than ID_WINDOW_DAYS old are dropped. If nothing is left
 *               the calendar holds one notice event saying the subscription can be removed.
 *
 * Both accept download=1 to send the file as an attachment instead of inline (subscription).
 */

class CalendarfeedController extends BaseController {

    const MAX_IDS = 100;
    // walks/events older than this many days are not returned in ids mode
    const ID_WINDOW_DAYS = 7;

    public function calendarfeed(): void {
        $app = Factory::getApplication();
        $input = $app->input;

        try {
            $groups = $input->getString('groups', '');
            $limit = $input->getInteger('limit', 0);
            // list and download parameters
            $ids = $input->getString('ids', '');
            $download = $input->getInt('download', 0) === 1;

            $byIds = ($ids !== '');
            $ics = new Content();

            if ($byIds) {
                // fetch walks/events by list
                if (!preg_match('/^[0-9]+(,[0-9]+)*$/', $ids) || count(explode(',', $ids)) > self::MAX_IDS) {
                    throw new \RuntimeException('Incorrect feed options', 400);
                }
                $options = new Feedoptions();
                $options->addWalksManagerIds($ids);
                $feed = new WalksFeed($options);
                $feed->filterDateBefore($this->cutoffDate(self::ID_WINDOW_DAYS));
                $walks = $feed->getWalks();
                if ($walks->totalWalks() === 0) {
                    // everything has finished (or been withdrawn): tell the subscriber to remove the subscription
                    $this->addRemoveNotice($ics, $ids);
                } else {
                    $walks->getIcalendarFile($ics);
                }
            } else {
                // fetch walks/events by rule
                if ($groups === '') {
                    throw new \RuntimeException('Incorrect feed options', 400);
                }
                $types = Feedfilters::remaining($input->getString('notype', ''), Feedfilters::TYPES);
                $days = Feedfilters::remaining($input->getString('nodow', ''), Feedfilters::DAYS);
                $grades = Feedfilters::remaining($input->getString('nograde', ''), Feedfilters::GRADES);
                $distances = Feedfilters::remaining($input->getString('nodist', ''), Feedfilters::DISTANCES);

                $options = new Feedoptions();
                $options->addWalksManagerGroupWalks($groups);
                $feed = new WalksFeed($options);

                // remove filtered walks/events; a filter that leaves everything in removes nothing
                if (!in_array('walks', $types)) {
                    $feed->filterWalks();  // filter/remove walks
                }
                if (!in_array('events', $types)) {
                    $feed->filterEvents();  // filter/remove events
                }
                if (!Feedfilters::isAll($days, Feedfilters::DAYS)) {
                    $feed->filterDayofweek(array_map(fn($code) => Feedfilters::DAYS[$code], $days));
                }
                if (!Feedfilters::isAll($grades, Feedfilters::GRADES)) {
                    $feed->filterNationalGrade(array_map(fn($code) => Feedfilters::GRADES[$code], $grades));
                }
                if (!Feedfilters::isAll($distances, Feedfilters::DISTANCES)) {
                    $feed->filterFeedDistances(array_map(fn($code) => Feedfilters::DISTANCES[$code][1], $distances));
                }
                // finally limit number of walks/events
                $feed->limitNumberWalks($limit);
                $feed->getWalks()->getIcalendarFile($ics);
            }

            header('Content-Type: text/calendar; charset=utf-8');
            // download=1 sends the file as an attachment
            if ($download) {
                header('Content-Disposition: attachment; filename="walks.ics"');
            } else {
                header('Content-Disposition: inline; filename="calendar.ics"');
            }
            // Let browsers/proxies reuse the answer briefly (matches the 10 minute feed cache)
            header('Cache-Control: public, max-age=600');
            header('X-Content-Type-Options: nosniff');
            echo $ics->text();
        } catch (\Throwable $e) {
            header('Content-Type: text/plain; charset=utf-8', true);
            http_response_code(400);
            echo 'Incorrect feed options: ' . $e->getMessage();
        }

        $app->close();
    }

    // Midnight, $days days ago, by the London calendar date. Walk dates are floating London times,
    // held as DateTime objects in PHP's default timezone, so the date string is re-read in that timezone.
    private function cutoffDate(int $days): \DateTime {
        $london = new \DateTime('today', new \DateTimeZone('Europe/London'));
        $london->modify('-' . $days . ' days');
        return new \DateTime($london->format('Y-m-d') . ' 00:00:00');
    }

    // Single all-day notice event dated today (London). Fixed UID per ids list, so the same event is
    // updated on each refresh instead of a new one being added.
    private function addRemoveNotice(Content $ics, string $ids): void {
        $today = new \DateTime('today', new \DateTimeZone('Europe/London'));
        $tomorrow = (clone $today)->modify('+1 day');
        $ics->addRecord("BEGIN:VEVENT");
        $ics->addRecord("UID:ramblers-removesubscription-" . md5($ids) . "@ramblers-webs.org.uk");
        $ics->addRecord("DTSTAMP:" . gmdate('Ymd\THis\Z'));
        $ics->addRecord("SEQUENCE:" . time());
        $ics->addRecord("DTSTART;VALUE=DATE:" . $today->format('Ymd'));
        $ics->addRecord("DTEND;VALUE=DATE:" . $tomorrow->format('Ymd'));
        $ics->addRecord("TRANSP:TRANSPARENT");
        $ics->addRecord("SUMMARY:", "Ramblers walk finished - remove this calendar subscription");
        $ics->addRecord("DESCRIPTION:", "This walk or event has finished, or is no longer available. "
                . "You can now remove this subscription from your calendar app.");
        $ics->addRecord("END:VEVENT");
    }
}
