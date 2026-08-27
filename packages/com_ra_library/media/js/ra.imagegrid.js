/**
 * Past Walks / Routes image gallery pagination - shows/hides the
 * {image_grid} thumbnails built by ItemRenderer::buildImageGrid() a page at
 * a time, once a record has more photos than Components > Ra_library >
 * Options > Image Gallery > Images per page.
 *
 * Deliberately a small, self-contained module rather than reusing the
 * existing ra.paginatedList (media/js/ra.paginatedDataList.js) - that
 * shared utility hides its own pagination controls whenever the total item
 * count is under a hardcoded 20, regardless of the itemsPerPage actually
 * configured, which would strand users on page 1 with no way to reach the
 * rest of the photos for any "images per page" setting below 20. Every
 * photo is still rendered server-side up front (no AJAX for "page 2") -
 * this only toggles which ones are visible, so media/js/ra.lightbox.js can
 * still Prev/Next across the whole gallery regardless of which page is
 * currently shown.
 *
 * Markup it works against (already produced server-side, only present when
 * pagination is actually needed):
 *   <div class="ra-image-grid">
 *     <a class="ra-image-grid-item" data-page="1" href="..." title="...">...</a>
 *     <a class="ra-image-grid-item" data-page="1" href="..." title="...">...</a>
 *     <a class="ra-image-grid-item" data-page="2" href="..." title="...">...</a>
 *     ...
 *   </div>
 *   <div class="ra-image-grid-pager" data-total-pages="3" data-current-page="1">
 *     <button class="ra-image-grid-prev" disabled>&laquo; Previous</button>
 *     <span class="ra-image-grid-pager-status">Page 1 of 3</span>
 *     <button class="ra-image-grid-next">Next &raquo;</button>
 *   </div>
 *
 * @since 1.0.0
 */
var ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
(function () {
    'use strict';

    function showPage(grid, pager, page) {
        var totalPages = parseInt(pager.getAttribute('data-total-pages'), 10) || 1;
        page = Math.min(Math.max(page, 1), totalPages);

        var items = grid.querySelectorAll('.ra-image-grid-item[data-page]');

        for (var i = 0; i < items.length; i++) {
            var itemPage = parseInt(items[i].getAttribute('data-page'), 10) || 1;
            items[i].classList.toggle('ra-page-hidden', itemPage !== page);
        }

        pager.setAttribute('data-current-page', page);

        var status = pager.querySelector('.ra-image-grid-pager-status');
        if (status) {
            status.textContent = status.textContent.replace(/\d+/, page);
        }

        var prevBtn = pager.querySelector('.ra-image-grid-prev');
        var nextBtn = pager.querySelector('.ra-image-grid-next');

        if (prevBtn) {
            prevBtn.disabled = (page <= 1);
        }

        if (nextBtn) {
            nextBtn.disabled = (page >= totalPages);
        }
    }

    function initPager(pager) {
        var grid = pager.previousElementSibling;

        if (!grid || !grid.classList.contains('ra-image-grid')) {
            return;
        }

        var prevBtn = pager.querySelector('.ra-image-grid-prev');
        var nextBtn = pager.querySelector('.ra-image-grid-next');

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                var current = parseInt(pager.getAttribute('data-current-page'), 10) || 1;
                showPage(grid, pager, current - 1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                var current = parseInt(pager.getAttribute('data-current-page'), 10) || 1;
                showPage(grid, pager, current + 1);
            });
        }

        showPage(grid, pager, 1);
    }

    // Exposed so content injected after page load - the item-preview
    // popup (ra.itempreview.js) fetches a whole item, photos and all,
    // after the initial page-load scan below has already run - can (re)run
    // this scoped to just the newly-inserted markup, rather than every
    // pager on the page needing to already exist at DOMContentLoaded.
    ra.imageGrid = ra.imageGrid || {};
    ra.imageGrid.initAll = function (root) {
        root = root || document;
        var pagers = root.querySelectorAll('.ra-image-grid-pager');

        for (var i = 0; i < pagers.length; i++) {
            initPager(pagers[i]);
        }
    };

    // Load::addScript() (see site/src/Helper/DisplayHelper.php etc.) doesn't
    // set a "defer" attribute, so this script can run before the gallery
    // markup further down the page has been parsed yet - a plain
    // querySelectorAll() at load time would then find nothing, silently
    // doing nothing (pager visible, buttons unwired, no images hidden).
    // Wait for DOMContentLoaded unless the document is already past that
    // point (e.g. this script happened to load after the page finished
    // parsing).
    function init() {
        ra.imageGrid.initAll(document);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
