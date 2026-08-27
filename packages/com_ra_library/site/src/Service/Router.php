<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Site\Service;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Factory;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Categories\CategoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Class Ra_libraryRouter
 *
 */
class Router extends RouterView {

    private $noIDs;

    /**
     * The category factory
     *
     * @var    CategoryFactoryInterface
     *
     * @since  1.0.0
     */
    private $categoryFactory;

    /**
     * The category cache
     *
     * @var    array
     *
     * @since  1.0.0
     */
    private $categoryCache = [];

    public function __construct(SiteApplication $app, AbstractMenu $menu, CategoryFactoryInterface $categoryFactory, DatabaseInterface $db) {
        $params = ComponentHelper::getParams('com_ra_library');
        $this->noIDs = (bool) $params->get('sef_ids');
        $this->categoryFactory = $categoryFactory;

        // Register the site views so the router rules (e.g. StandardRules)
        // have entries to match against - without this, $this->getViews()
        // is empty and lookups like $views['librarydisplay'] warn/fail.
        $librarydisplay = new RouterViewConfiguration('librarydisplay');
        $this->registerView($librarydisplay);

        $upload = new RouterViewConfiguration('upload');
        $this->registerView($upload);

        // Fixed, always-available single-item pages for Past Walks/Routes -
        // reached only via a generated link (?view=pastwalk&id=X), never
        // chosen as a menu item type (there's no menu type registered for
        // these - see site/src/View/Pastwalk|Route). Registering them here
        // just lets StandardRules build/parse their id-keyed URLs.
        $pastwalk = new RouterViewConfiguration('pastwalk');
        $pastwalk->setKey('id');
        $this->registerView($pastwalk);

        $route = new RouterViewConfiguration('route');
        $route->setKey('id');
        $this->registerView($route);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Method to get categories from cache
     *
     * @param   array  $options   The options for retrieving categories
     *
     * @return  CategoryInterface  The object containing categories
     *
     * @since   1.0.0
     */
    private function getCategories(array $options = []): CategoryInterface {
        $key = serialize($options);

        if (!isset($this->categoryCache[$key])) {
            $this->categoryCache[$key] = $this->categoryFactory->createCategory($options);
        }

        return $this->categoryCache[$key];
    }
}
