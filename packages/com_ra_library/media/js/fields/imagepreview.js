/*
 * Photo gallery rows on the Past Walk / Route edit forms: hover a thumbnail
 * for a larger preview, click it for a full-size viewer (Prev/Next across
 * every photo in the gallery, arrow keys, Esc to close). Uses event
 * delegation so rows added or re-ordered in the subform keep working.
 * Ctrl/Cmd/Shift-click or middle-click still opens the large file in a new tab.
 * Also used by the tiles of the "Reorder photos" grid (photogrid.js).
 */
(function () {
    'use strict';

    var SELECTOR = 'a.ra-photo-thumb';
    var hover = null;
    var hoverFor = null;
    var viewer = null;
    var viewerItems = [];
    var viewerIndex = 0;
    var returnFocus = null;

    function text(key, fallback) {
        return (window.Joomla && Joomla.Text && Joomla.Text._(key)) || fallback;
    }

    function thumbFrom(target) {
        return target && target.closest ? target.closest(SELECTOR) : null;
    }

    function captionFor(anchor) {
        // A tile in the reorder grid points back at its Photo details row.
        var tile = anchor.closest('.ra-tile');
        var row = tile && tile._row ? tile._row : anchor.closest('.subform-repeatable-group, tr');
        var input = row ? row.querySelector('input[name$="[caption]"]') : null;

        return input ? input.value.trim() : '';
    }

    /* ---------- hover preview ---------- */

    function placeHover(x, y) {
        if (!hover) {
            return;
        }

        var w = hover.offsetWidth;
        var h = hover.offsetHeight;
        var left = x + 18;
        var top = y + 12;

        if (left + w > window.innerWidth - 8) {
            left = Math.max(8, x - w - 18);
        }
        if (top + h > window.innerHeight - 8) {
            top = Math.max(8, window.innerHeight - h - 8);
        }

        hover.style.left = left + 'px';
        hover.style.top = top + 'px';
    }

    function showHover(anchor, event) {
        if (viewer) {
            return;
        }

        hideHover();

        hover = document.createElement('div');
        hover.className = 'ra-photo-hover';

        var img = document.createElement('img');
        img.alt = '';
        img.src = anchor.getAttribute('href');
        // Reposition once the real size is known.
        img.addEventListener('load', function () {
            placeHover(event.clientX, event.clientY);
        });
        hover.appendChild(img);
        document.body.appendChild(hover);
        hoverFor = anchor;
        placeHover(event.clientX, event.clientY);
    }

    function hideHover() {
        if (hover && hover.parentNode) {
            hover.parentNode.removeChild(hover);
        }
        hover = null;
        hoverFor = null;
    }

    document.addEventListener('mouseover', function (event) {
        var anchor = thumbFrom(event.target);

        if (anchor && anchor !== hoverFor) {
            showHover(anchor, event);
        }
    });

    document.addEventListener('mousemove', function (event) {
        if (hover) {
            placeHover(event.clientX, event.clientY);
        }
    });

    document.addEventListener('mouseout', function (event) {
        var anchor = thumbFrom(event.target);

        if (anchor && !anchor.contains(event.relatedTarget)) {
            hideHover();
        }
    });

    /* ---------- click viewer ---------- */

    function button(cls, label, glyph) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = cls;
        b.setAttribute('aria-label', label);
        b.textContent = glyph;

        return b;
    }

    function render() {
        var item = viewerItems[viewerIndex];
        var img = viewer.querySelector('img');
        var caption = viewer.querySelector('.ra-photo-viewer-caption');
        var count = viewer.querySelector('.ra-photo-viewer-count');

        img.src = item.href;
        img.alt = item.caption;
        caption.textContent = item.caption;
        count.textContent = text('COM_RA_LIBRARY_IMAGE_VIEWER_COUNT', 'Photo %s of %s')
            .replace('%s', viewerIndex + 1).replace('%s', viewerItems.length);

        var single = viewerItems.length < 2;
        viewer.querySelector('.ra-photo-viewer-prev').hidden = single;
        viewer.querySelector('.ra-photo-viewer-next').hidden = single;
    }

    function step(delta) {
        viewerIndex = (viewerIndex + delta + viewerItems.length) % viewerItems.length;
        render();
    }

    function closeViewer() {
        if (!viewer) {
            return;
        }

        viewer.parentNode.removeChild(viewer);
        viewer = null;
        document.removeEventListener('keydown', onKey, true);

        if (returnFocus && returnFocus.focus) {
            returnFocus.focus();
        }
    }

    function onKey(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            closeViewer();
        } else if (event.key === 'ArrowLeft' && viewerItems.length > 1) {
            event.preventDefault();
            step(-1);
        } else if (event.key === 'ArrowRight' && viewerItems.length > 1) {
            event.preventDefault();
            step(1);
        }
    }

    function openViewer(anchor) {
        hideHover();

        // Step through the photos of the list the click came from: the reorder
        // grid (in its current order) or the Photo details list - never both,
        // or every photo would appear twice.
        var scope = anchor.closest('.ra-photo-grid, joomla-field-subform') || document;
        var anchors = Array.prototype.slice.call(scope.querySelectorAll(SELECTOR));

        viewerItems = anchors.map(function (a) {
            return {href: a.getAttribute('href'), caption: captionFor(a)};
        });
        viewerIndex = Math.max(0, anchors.indexOf(anchor));
        returnFocus = anchor;

        viewer = document.createElement('div');
        viewer.className = 'ra-photo-viewer';
        viewer.setAttribute('role', 'dialog');
        viewer.setAttribute('aria-modal', 'true');

        var close = button('ra-photo-viewer-close', text('COM_RA_LIBRARY_IMAGE_VIEWER_CLOSE', 'Close'), '\u00d7');
        var prev = button('ra-photo-viewer-prev', text('COM_RA_LIBRARY_IMAGE_VIEWER_PREV', 'Previous photo'), '\u2039');
        var next = button('ra-photo-viewer-next', text('COM_RA_LIBRARY_IMAGE_VIEWER_NEXT', 'Next photo'), '\u203a');
        var img = document.createElement('img');
        var caption = document.createElement('div');
        var count = document.createElement('div');

        caption.className = 'ra-photo-viewer-caption';
        count.className = 'ra-photo-viewer-count';

        viewer.appendChild(close);
        viewer.appendChild(prev);
        viewer.appendChild(next);
        viewer.appendChild(img);
        viewer.appendChild(caption);
        viewer.appendChild(count);

        close.addEventListener('click', closeViewer);
        prev.addEventListener('click', function () { step(-1); });
        next.addEventListener('click', function () { step(1); });
        // Click on the dark backdrop (not the photo or buttons) closes.
        viewer.addEventListener('click', function (event) {
            if (event.target === viewer) {
                closeViewer();
            }
        });

        document.body.appendChild(viewer);
        document.addEventListener('keydown', onKey, true);
        render();
        close.focus();
    }

    document.addEventListener('click', function (event) {
        var anchor = thumbFrom(event.target);

        if (!anchor || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey) {
            return;
        }

        event.preventDefault();
        openViewer(anchor);
    });
})();
