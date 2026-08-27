<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_library
 * @author     Chris Vaughan <ruby.tuesday@ramblers-webs.org.uk>
 * @copyright  2026 Chris Vaughan
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_library\Administrator\Helper;
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Object\CMSObject;
use Ramblers\Component\Ra_library\Site\Library\Geodesy\OsCoordinateTransformer;
use Ramblers\Component\Ra_library\Site\Library\Geodesy\OsGridReference;

/**
 * Ra_library helper.
 *
 * @since  1.0.0
 */
class Ra_libraryHelper
{
	/**
	 * Gets the files attached to an item
	 *
	 * @param   int     $pk     The item's id
	 *
	 * @param   string  $table  The table's name
	 *
	 * @param   string  $field  The field's name
	 *
	 * @return  array  The files
	 */
	public static function getFiles($pk, $table, $field)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query
			->select($field)
			->from($table)
			->where('id = ' . (int) $pk);

		$db->setQuery($query);

		return explode(',', $db->loadResult());
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return  CMSObject
	 *
	 * @since   1.0.0
	 */
	public static function getActions()
	{
		$user = Factory::getApplication()->getIdentity();
		$result = new CMSObject;

		$assetName = 'com_ra_library';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * Gets a list of the Past Walks actions that can be performed.
	 *
	 * Separate from getActions() above: these check the pastwalks.*
	 * actions (added for the Past Walks feature) rather than core.*,
	 * so a user group can be restricted to Past Walks only.
	 *
	 * @return  CMSObject
	 *
	 * @since   1.0.0
	 */
	public static function getPastwalkActions()
	{
		$user = Factory::getApplication()->getIdentity();
		$result = new CMSObject;

		$assetName = 'com_ra_library';

		$actions = array(
			'pastwalks.manage', 'pastwalks.create', 'pastwalks.edit',
			'pastwalks.edit.own', 'pastwalks.edit.state', 'pastwalks.delete'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * Gets a list of the Routes actions that can be performed.
	 *
	 * Separate from getActions()/getPastwalkActions(): these check the
	 * routes.* actions so a user group can be restricted to Routes only,
	 * independently of Past Walks or the rest of com_ra_library.
	 *
	 * @return  CMSObject
	 *
	 * @since   1.0.0
	 */
	public static function getRouteActions()
	{
		$user = Factory::getApplication()->getIdentity();
		$result = new CMSObject;

		$assetName = 'com_ra_library';

		$actions = array(
			'routes.manage', 'routes.create', 'routes.edit',
			'routes.edit.own', 'routes.edit.state', 'routes.delete'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * Build the full category "branch" path for a category id - its own
	 * title plus every ancestor's, root first - e.g. "Peak District /
	 * Easy Walks" rather than just "Easy Walks". Used by the Past Walks /
	 * Routes admin list views, which previously showed only the item's
	 * immediate category under its title.
	 *
	 * Each category id looked up is cached for the life of the request,
	 * so listing many items sharing the same categories/ancestors only
	 * looks each one up once.
	 *
	 * @param   int     $catid      The item's own (leaf) category id.
	 * @param   string  $separator  Between each level.
	 *
	 * @return  string  '' if $catid is 0/not found.
	 *
	 * @since   1.0.0
	 */
	public static function getCategoryTitlePath($catid, $separator = ' / ')
	{
		static $cache = array();

		$titles = array();
		$guard = 0;

		while ($catid > 0 && $guard < 20)
		{
			if (!array_key_exists($catid, $cache))
			{
				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->getQuery(true)
					->select(array('title', 'parent_id', 'level'))
					->from($db->quoteName('#__categories'))
					->where($db->quoteName('id') . ' = ' . (int) $catid);
				$db->setQuery($query);
				$cache[$catid] = $db->loadObject() ?: null;
			}

			$info = $cache[$catid];

			if (!$info || (int) $info->level < 1)
			{
				break;
			}

			array_unshift($titles, $info->title);
			$catid = (int) $info->parent_id;
			$guard++;
		}

		return implode($separator, $titles);
	}

	/**
	 * Parses a British National Grid reference (e.g. "SK364514") into
	 * WGS84 latitude/longitude - shared by anything that lets an admin type
	 * a grid reference instead of raw lat/long: the image gallery
	 * (ImageGalleryHelper) and Past Walk / Route start locations
	 * (PastwalkTable::bind()). Returns [null, null] if blank or unparseable
	 * rather than throwing, since a bad/incomplete grid reference shouldn't
	 * block saving the rest of the record.
	 *
	 * @param   string  $gridReference  e.g. "SK364514".
	 *
	 * @return  array  [latitude, longitude] floats, or [null, null].
	 *
	 * @since   1.0.0
	 */
	public static function latLongFromGridReference(string $gridReference): array
	{
		if ($gridReference === '')
		{
			return [null, null];
		}

		$eastingNorthing = OsGridReference::parse($gridReference);

		if ($eastingNorthing === null)
		{
			return [null, null];
		}

		static $transformer = null;
		$transformer = $transformer ?? new OsCoordinateTransformer();

		[$latitude, $longitude] = $transformer->osgb36ToWgs84($eastingNorthing[0], $eastingNorthing[1]);

		return [$latitude, $longitude];
	}
}
