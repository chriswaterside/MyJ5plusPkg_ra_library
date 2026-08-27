<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\Extension;

defined('_JEXEC') or die;

use Ramblers\Component\Ra_library\Administrator\Service\Html\RA_LIBRARY;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Association\AssociationServiceTrait;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Tag\TagServiceTrait;
use Psr\Container\ContainerInterface;
use Joomla\CMS\Categories\CategoryServiceInterface;

/**
 * Component class for Ra_library
 *
 * @since  1.0.0
 */
class Ra_libraryComponent extends MVCComponent implements RouterServiceInterface, BootableExtensionInterface, CategoryServiceInterface
{
	use AssociationServiceTrait;
	use RouterServiceTrait;
	use HTMLRegistryAwareTrait;
	use CategoryServiceTrait, TagServiceTrait {
		CategoryServiceTrait::getTableNameForSection insteadof TagServiceTrait;
		CategoryServiceTrait::getStateColumnForSection insteadof TagServiceTrait;
	}

	/** @inheritdoc  */
	public function boot(ContainerInterface $container)
	{
		$db = $container->get('DatabaseDriver');
		$this->getRegistry()->register('ra_library', new RA_LIBRARY($db));
	}

	
/**
	 * Returns the table for the count items functions for the given section.
	 *
	 * @param   string  $section  The section
	 *
	 * @return  string|null
	 *
	 * @since   4.0.0
	 */
	protected function getTableNameForSection(?string $section = null)
	{
		// NOTE: bare table name, no '#__' - ContentHelper::countRelations()
		// prepends '#__' itself before quoting, so including it here would
		// double it up (confirmed against Joomla core source).
		switch ($section)
		{
			case 'pastwalk':
			case 'route':
			default:
				// Both sections share the one physical table - catid values
				// never collide across extensions (Joomla issues #__categories
				// ids globally, not per-extension), so counting by catid alone
				// is accurate without also filtering on record_type here.
				return 'ra_library_routes';
		}
	}

	/**
	 * Adds Count Items for Category Manager.
	 *
	 * @param   \stdClass[]  $items    The category objects
	 * @param   string       $section  The section
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function countItems(array $items, string $section)
	{
		// Canonical pattern per Joomla's own "Implementing Categories in your
		// Component" docs: hand a config object to ContentHelper::countRelations()
		// rather than querying it ourselves - this is also what gives the
		// Published/Unpublished/Archived/Trashed breakdown in the Category
		// Manager, which a hand-rolled query wouldn't have provided.
		$config = (object) [
			'related_tbl'   => $this->getTableNameForSection($section),
			'state_col'     => $this->getStateColumnForSection($section),
			'group_col'     => 'catid',
			'relation_type' => 'category_or_group',
		];

		ContentHelper::countRelations($items, $config);
	}
}