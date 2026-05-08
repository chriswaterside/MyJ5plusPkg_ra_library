<?php

/*
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */

namespace Ramblers\Component\Ra_library\Site\View\Calendarfeed;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseView;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Feedoptions;
use Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Feed as WalksFeed;
use Ramblers\Component\Ra_library\Site\Library\Event\Feed as EventFeed;
use Ramblers\Component\Ra_library\Site\Library\Event\Group;

/**
 * View class for a list of Ra_library.
 *
 * @since  1.0.0
 */
class FeedView extends BaseView {

    public function display($tpl = null): void {
        $app = Factory::getApplication();
        $doc = $app->getDocument();
        $doc->setMimeEncoding('text/calendar; charset=utf-8');
        $input = $app->input;
        $groups = $input->get('groups', null, 'string');
        if ($groups === null) {
            $app->enqueueMessage('incorect feed options', 'error');
            $app->close(400);
        }

        $options = new Feedoptions();
        $options->addWalksMangerGroupWalks($groups);
        $feed = new WalksFeed($options);
        $group = new Group();
        $group->addWalks($feed);

        $events = new EventFeed();

        $output = $events->getText($group);
        echo $output;
        $app->close();
    }
}
