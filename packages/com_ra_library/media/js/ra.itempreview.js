/**
 * "Open items in a popup" (Components > Ra_library > Options > Item
 * Preview) - when that setting is on, ItemRenderer::buildValues() and
 * DisplayHelper::renderItemsTable() mark every "view this item" link
 * (title_link, and the Table/Map+Table row and marker links) with
 * class="ra-item-preview-link" instead of a plain href. This is what
 * actually intercepts a click on one of those links and shows the item in
 * a ra.modals popup instead of navigating to its own page.
 *
 * The item itself is NOT re-rendered here - it's fetched from its own,
 * real single-item URL (the same href the link already points to) with
 * &tmpl=component appended, Joomla's own convention for "render this page
 * without the site template's header/footer/modules around it" (already
 * used elsewhere in this component - see the SelectFolder field's
 * layout=modal picker). That guarantees the popup shows exactly what the
 * real page would, with no separate rendering path to keep in sync.
 *
 * tmpl=component still returns a full (if minimal) HTML document, so this
 * extracts just the .ra-single-item element out of it (see
 * site/tmpl/pastwalk|route/default.php) rather than injecting the whole
 * fetched document into the page.
 *
 * Injecting HTML via innerHTML does not execute any <script> tags it
 * contains - which matters here because a fetched item with its own
 * {gpx_map} relies on exactly that (its bootstrap script, written via
 * Joomla's addScriptDeclaration() and so possibly sitting in the fetched
 * document's <head> rather than inside .ra-single-item itself, is what
 * actually builds the map). This walks both the fetched <head> and the
 * extracted fragment for inline (no src) scripts and re-creates them so
 * they run. External <script src="..."> tags are deliberately left alone -
 * DisplayHelper::loadRecordDisplayAssets() already primes the base
 * Leaflet/ra.js stack on any page this popup can be triggered from
 * (whether or not that page has a map of its own), so those assets are
 * already present by the time a popup's own inline bootstrap script runs.
 *
 * If JavaScript is unavailable, or the fetch fails for any reason, the
 * underlying <a href="..."> still works as a normal link to the item's
 * own page - this only intercepts the click, it isn't required for the
 * item to be reachable at all.
 *
 * @since 1.0.0
 */
(function () {
    'use strict';

    function previewUrl(href) {
        return href + (href.indexOf('?') === -1 ? '?' : '&') + 'tmpl=component';
    }

    // Only inline (no src - those are already covered by
    // DisplayHelper::loadRecordDisplayAssets() priming the base asset
    // stack on any page this popup can open from) *JavaScript* scripts are
    // re-run. Joomla's own pages routinely include other inline <script>
    // tags that aren't JavaScript at all - most commonly
    // type="application/json" blocks (Joomla.getOptions() config) - and
    // creating a real <script> element from one of those without checking
    // its type makes the browser try to parse JSON as JS, throw a
    // SyntaxError, and (since that error isn't caught here) abort the rest
    // of the batch, including whatever real bootstrap script came after it.
    function isRunnableScript(script) {
        if (script.src) {
            return false;
        }

        var type = (script.getAttribute('type') || '').toLowerCase();

        return type === '' || type === 'text/javascript' || type === 'application/javascript';
    }

    // A map's bootstrap script (see Leaflet\Script::add()) is written as
    // window.addEventListener('load', function () { ra.bootstrapper(...); }) -
    // fine on a normally-loaded page, but the 'load' event on THIS window
    // already fired long before the popup's fetch even started, so simply
    // re-running that script verbatim only registers a listener for an
    // event that will never happen again - ra.bootstrapper() never runs,
    // and any {gpx_map} in the popup stays an empty div forever.
    //
    // Rather than pattern-match/rewrite that specific script text, this
    // runs every re-executed script with its own 'window' shadowed by a
    // small stand-in whose addEventListener('load', fn) calls fn()
    // straight away instead of registering it - any OTHER event still
    // passes through to the real window untouched. Everything else the
    // script references (ra, etc.) still resolves normally, since
    // Function() bodies run in the global scope - only the local
    // "window" parameter is different.
    function runScript(scriptText) {
        var immediateLoadWindow = {
            addEventListener: function (event, handler) {
                if (event === 'load') {
                    handler();
                } else {
                    window.addEventListener(event, handler);
                }
            }
        };

        // eslint-disable-next-line no-new-func
        var run = new Function('window', scriptText);
        run(immediateLoadWindow);
    }

    function runInlineScripts(scripts) {
        scripts.forEach(function (oldScript) {
            if (!isRunnableScript(oldScript)) {
                return;
            }

            try {
                runScript(oldScript.textContent);
            } catch (err) {
                // One bad/unexpected script shouldn't stop the rest of the
                // batch (e.g. a genuine bootstrap script) from still running.
            }
        });
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest ? e.target.closest('.ra-item-preview-link') : null;

        if (!link) {
            return;
        }

        e.preventDefault();

        var href = link.getAttribute('href');

        fetch(previewUrl(href))
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Preview fetch failed: ' + response.status);
                    }

                    return response.text();
                })
                .then(function (html) {
                    var parsed = new DOMParser().parseFromString(html, 'text/html');
                    var item = parsed.querySelector('.ra-single-item') || parsed.body;

                    var wrapper = document.createElement('div');
                    wrapper.className = 'ra-item-preview-modal';
                    wrapper.innerHTML = item.outerHTML || item.innerHTML;

                    var headScripts = Array.prototype.slice.call(parsed.head ? parsed.head.querySelectorAll('script') : []);
                    var itemScripts = Array.prototype.slice.call(wrapper.querySelectorAll('script'));

                    ra.modals.createModal(wrapper, true);

                    // ra.imagegrid.js's own DOMContentLoaded scan ran long
                    // before this content existed, so any {image_grid} pager
                    // just fetched in needs re-initialising explicitly, scoped
                    // to this popup only - otherwise every photo shows at once
                    // with an inert, do-nothing pager underneath.
                    if (ra.imageGrid) {
                        ra.imageGrid.initAll(wrapper);
                    }

                    runInlineScripts(headScripts.concat(itemScripts));
                })
                .catch(function () {
                    // Couldn't show the popup for some reason - fall back to a
                    // normal navigation rather than leaving the click doing
                    // nothing.
                    window.location.href = href;
                });
    });
})();
