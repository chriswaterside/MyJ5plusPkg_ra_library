<?php

/**
 * AJAX endpoint used by FolderField / field-folder.js to lazily list a
 * folder's immediate sub-folders, one level at a time.
 *
 * WHERE TO PUT THIS FILE
 * -----------------------
 * Unlike FolderField.php, this class IS resolved through your component's
 * own MVC namespace (Joomla builds controllers via the component's
 * registered PSR-4 namespace, not addfieldprefix). So:
 *
 *   1. Change the namespace below to match your component's real namespace
 *      + "\Administrator\Controller" (check the <namespace> tag in your
 *      component's manifest XML — it'll look like
 *      "YourVendor\Component\Yourcomponent").
 *   2. Save the file as:
 *      administrator/components/com_yourcomponent/src/Controller/FolderController.php
 *   3. Update FolderField.php's COMPONENT_OPTION constant and the
 *      task=folder.children URL if you rename anything.
 *
 * SECURITY
 * --------
 * This endpoint takes a client-supplied `path` and reads a directory, which
 * is exactly the shape of a classic path-traversal vulnerability if done
 * carelessly. resolveSafePath() is deliberately strict:
 *   - the first path segment MUST be one of ALLOWED_ROOTS (allow-list, not
 *     a blocklist of "../")
 *   - Path::clean() collapses any "." / ".." segments
 *   - realpath() is then used to resolve symlinks and confirm the final,
 *     real, on-disk path is still physically inside the real root folder
 *     before anything is read
 * Do not relax this to "just check for ../" — that class of check is
 * routinely bypassable (encoded separators, symlinks, etc).
 *
 * Keep ALLOWED_ROOTS in sync with the `roots` attribute you use on the
 * FolderField in your XML.
 */

namespace Ramblers\Component\Ra_library\Administrator\Controller;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;

class FolderController extends BaseController {

    /**
     * Root folders (relative to JPATH_ROOT) this endpoint is allowed to
     * browse. Must match the `roots` attribute used on the field.
     *
     * @var string[]
     */
    private const ALLOWED_ROOTS = ['images', 'files'];

    /**
     * AJAX task: list the immediate sub-folders of a given path.
     * Reached via index.php?option=com_yourcomponent&task=folder.children
     *
     * Always exits via $app->close() with a JSON body of the shape:
     *   { "folders": [ { "name": "2026", "path": "images/gallery/2026" }, ... ] }
     * or on failure:
     *   { "error": "..." }
     */
    public function children(): void {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->sendHeaders();

        // CSRF check — the request must carry Joomla's per-session form token.
        if (!Session::checkToken('post')) {
            http_response_code(403);
            echo json_encode(['error' => Text::_('JINVALID_TOKEN')]);
            $app->close();
        }

        // Permission check — swap this for whatever ACL action fits your
        // component (this requires at least core.manage on the component).
        $user = $app->getIdentity();

        if ($user === null || !$user->authorise('core.manage', 'com_yourcomponent')) {
            http_response_code(403);
            echo json_encode(['error' => Text::_('JERROR_ALERTNOAUTHOR')]);
            $app->close();
        }

        $requested = $app->getInput()->post->getString('path', '');
        $safePath = $this->resolveSafePath($requested);

        if ($safePath === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid path']);
            $app->close();
        }

        $children = [];

        // Non-recursive, one level only — that's what makes this lazy/dynamic.
        foreach (Folder::folders($safePath, '.', false, false) as $name) {
            $childRelative = ltrim($requested . '/' . $name, '/');

            $children[] = [
                'name' => $name,
                'path' => $childRelative,
            ];
        }

        echo json_encode(['folders' => $children]);
        $app->close();
    }

    /**
     * Resolve a client-supplied relative path (e.g. "images/gallery") to a
     * real, on-disk absolute path — or null if it's invalid or would escape
     * the allowed roots.
     */
    private function resolveSafePath(string $relative): ?string {
        $relative = trim(str_replace('\\', '/', $relative), '/');

        if ($relative === '' || strpos($relative, "\0") !== false) {
            return null;
        }

        $segments = explode('/', $relative);
        $root = array_shift($segments);

        if (!\in_array($root, self::ALLOWED_ROOTS, true)) {
            return null;
        }

        $rootReal = realpath(JPATH_ROOT . '/' . $root);

        if ($rootReal === false) {
            return null;
        }

        // Path::clean() collapses any "." / ".." segments Joomla-side first.
        $clean = Path::clean(JPATH_ROOT . '/' . $relative);
        $real = realpath($clean);

        if ($real === false || !is_dir($real)) {
            return null;
        }

        // Final, authoritative check: the resolved real path must be the
        // root itself, or physically inside it (symlinks included).
        if ($real !== $rootReal && strpos($real . \DIRECTORY_SEPARATOR, $rootReal . \DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }

        return $real;
    }
}
