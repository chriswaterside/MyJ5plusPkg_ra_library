/*
 * Photos tab on the Past Walk / Route edit forms: a second sub-tab ("Reorder
 * photos") that shows the same saved photos as a grid of thumbnails you can
 * drag into a new order (or nudge with the arrow buttons - which also works
 * on touch screens and from the keyboard) or remove with the red cross.
 *
 * It does not keep any data of its own: reordering the grid reorders the
 * photo rows of the "Photo details" subform in place (that is what the Joomla
 * subform's own Move button does), and the server saves photos in the order
 * the rows are submitted. Captions, Featured and so on are therefore edited
 * on the details tab only; the grid just reflects them.
 *
 * Only photos already stored appear in the grid - a row you have just added
 * (file chosen but not yet saved) has no thumbnail to show until you Save.
 */
(function () {
    'use strict';

    function text(key, fallback) {
        return (window.Joomla && Joomla.Text && Joomla.Text._(key)) || fallback;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var wrap = document.getElementById('ra-photo-tabs');
        var grid = document.getElementById('ra-photo-grid');
        var empty = document.getElementById('ra-photo-grid-empty');
        var note = document.getElementById('ra-photo-grid-removed');

        if (!wrap || !grid) {
            return;
        }

        var tabs = wrap.querySelectorAll('[data-ra-photos-tab]');
        var panes = {
            list: document.getElementById('ra-photos-pane-list'),
            grid: document.getElementById('ra-photos-pane-grid')
        };
        var dragged = null;

        /* ---------- the subform being reordered ---------- */

        function subform() {
            return panes.list.querySelector('.subform-repeatable');
        }

        function allRows() {
            var container = subform();

            return container ? Array.prototype.slice.call(container.querySelectorAll(':scope > .subform-repeatable-group')) : [];
        }

        function pathInput(row) {
            return row.querySelector('input[name$="[thumbnail_path]"]');
        }

        function isPhotoRow(row) {
            var input = pathInput(row);

            return !!(input && input.value);
        }

        /* ---------- building the grid ---------- */

        function number() {
            Array.prototype.forEach.call(grid.children, function (tile, i) {
                tile.querySelector('.ra-tile-num').textContent = String(i + 1);
            });
        }

        function makeButton(label, glyph, delta) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-sm btn-secondary';
            b.setAttribute('aria-label', label);
            b.title = label;
            b.textContent = glyph;
            b.addEventListener('click', function () {
                move(b.closest('.ra-tile'), delta);
            });

            return b;
        }

        function buildTile(row) {
            var tile = document.createElement('li');
            var captionInput = row.querySelector('input[name$="[caption]"]');
            var featured = row.querySelector('input[type="checkbox"][name$="[featured]"]');
            var sourceImg = row.querySelector('img');
            var captionText = captionInput ? captionInput.value.trim() : '';

            tile.className = 'ra-tile';
            tile.draggable = true;
            tile._row = row;

            var num = document.createElement('span');
            num.className = 'ra-tile-num';
            tile.appendChild(num);

            if (featured && featured.checked) {
                var star = document.createElement('span');
                star.className = 'ra-tile-star';
                star.title = text('COM_RA_LIBRARY_PHOTOS_FEATURED', 'Featured');
                star.textContent = '\u2605';
                tile.appendChild(star);
            }

            var img = document.createElement('img');
            img.src = sourceImg ? sourceImg.getAttribute('src') : '';
            img.alt = captionText;
            img.draggable = false;

            // Same link the details list uses, so imagepreview.js opens the
            // large viewer (and hover preview) from the grid too. The link is
            // not draggable itself, so dragging it still moves the whole tile.
            var sourceLink = row.querySelector('a.ra-photo-thumb');
            var link = document.createElement('a');

            link.className = 'ra-photo-thumb ra-tile-link';
            link.href = sourceLink ? sourceLink.getAttribute('href') : img.src;
            link.target = '_blank';
            link.rel = 'noopener';
            link.draggable = false;
            link.title = text('COM_RA_LIBRARY_IMAGE_VIEW_LARGER', 'View larger');
            link.appendChild(img);
            tile.appendChild(link);

            var cap = document.createElement('span');
            cap.className = 'ra-tile-caption';
            cap.textContent = captionText;
            tile.appendChild(cap);

            var buttons = document.createElement('span');
            buttons.className = 'ra-tile-buttons';
            buttons.appendChild(makeButton(text('COM_RA_LIBRARY_PHOTOS_MOVE_EARLIER', 'Move earlier'), '\u25c0', -1));
            buttons.appendChild(makeButton(text('COM_RA_LIBRARY_PHOTOS_MOVE_LATER', 'Move later'), '\u25b6', 1));

            var del = document.createElement('button');
            var delLabel = text('COM_RA_LIBRARY_PHOTOS_REMOVE', 'Remove photo');

            del.type = 'button';
            del.className = 'btn btn-sm btn-danger ra-tile-remove';
            del.setAttribute('aria-label', delLabel);
            del.title = delLabel;
            del.textContent = '\u00d7';
            del.addEventListener('click', function () {
                removeTile(tile);
            });
            buttons.appendChild(del);
            tile.appendChild(buttons);

            return tile;
        }

        function render() {
            grid.textContent = '';

            allRows().forEach(function (row) {
                if (isPhotoRow(row)) {
                    grid.appendChild(buildTile(row));
                }
            });

            number();
            empty.hidden = grid.children.length > 0;
        }

        /* ---------- writing the new order back to the subform rows ---------- */

        function commit() {
            var container = subform();

            if (!container) {
                return;
            }

            var rows = allRows();
            var order = Array.prototype.map.call(grid.children, function (tile) {
                return tile._row;
            });
            var slots = [];

            rows.forEach(function (row, i) {
                if (isPhotoRow(row)) {
                    slots.push(i);
                }
            });

            // Photo rows take the photo-row positions in the new order; any
            // blank not-yet-saved rows stay exactly where they were.
            var result = rows.slice();

            slots.forEach(function (slot, i) {
                if (order[i]) {
                    result[slot] = order[i];
                }
            });

            var template = container.querySelector(':scope > template');

            result.forEach(function (row) {
                container.insertBefore(row, template);
            });

            number();
        }

        function move(tile, delta) {
            var sibling = delta < 0 ? tile.previousElementSibling : tile.nextElementSibling;

            if (!sibling) {
                return;
            }

            if (delta < 0) {
                grid.insertBefore(tile, sibling);
            } else {
                grid.insertBefore(sibling, tile);
            }

            commit();

            var focusTarget = tile.querySelector('.ra-tile-buttons button:' + (delta < 0 ? 'first-child' : 'last-child'));

            if (focusTarget) {
                focusTarget.focus();
            }
        }

        /* ---------- removing a photo ---------- */

        // Takes the photo's row out of the Photo details subform, exactly as
        // that row's own Remove button does. The stored photo is only deleted
        // when the form is saved (Save drops any photo not in the form).
        function removeTile(tile) {
            var row = tile._row;
            var sf = row && row.closest('joomla-field-subform');

            if (!sf || typeof sf.removeRow !== 'function') {
                return;
            }

            var captionInput = row.querySelector('input[name$="[caption]"]');
            var caption = captionInput ? captionInput.value.trim() : '';
            var message = text('COM_RA_LIBRARY_PHOTOS_REMOVE_CONFIRM', 'Remove this photo? It is deleted for good when you click Save.');

            if (caption) {
                message = caption + '\n\n' + message;
            }

            if (!window.confirm(message)) {
                return;
            }

            sf.removeRow(row);
            render();

            if (note) {
                note.hidden = false;
            }

            document.dispatchEvent(new CustomEvent('ra:photos-changed'));
        }

        /* ---------- drag and drop (mouse) ---------- */

        grid.addEventListener('dragstart', function (event) {
            var tile = event.target.closest ? event.target.closest('.ra-tile') : null;

            if (!tile) {
                return;
            }

            dragged = tile;
            tile.classList.add('ra-tile-dragging');
            event.dataTransfer.effectAllowed = 'move';
            // Firefox will not start a drag without some data.
            event.dataTransfer.setData('text/plain', 'ra-photo');
        });

        grid.addEventListener('dragover', function (event) {
            if (!dragged) {
                return;
            }

            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';

            var over = event.target.closest ? event.target.closest('.ra-tile') : null;

            if (!over || over === dragged) {
                return;
            }

            var box = over.getBoundingClientRect();
            var after = event.clientX > box.left + box.width / 2;
            var reference = after ? over.nextElementSibling : over;

            // Already in that spot (just before/after the tile under the pointer)?
            if (reference === dragged || reference === dragged.nextElementSibling) {
                return;
            }

            grid.insertBefore(dragged, reference);
            number();
        });

        grid.addEventListener('drop', function (event) {
            if (dragged) {
                event.preventDefault();
            }
        });

        grid.addEventListener('dragend', function () {
            if (dragged) {
                dragged.classList.remove('ra-tile-dragging');
                dragged = null;
                commit();
            }
        });

        // The background uploader adds rows while this tab may be showing.
        document.addEventListener('ra:photos-changed', function () {
            if (!panes.grid.hidden) {
                render();
            }
        });

        /* ---------- the two sub-tabs ---------- */

        function show(name) {
            Array.prototype.forEach.call(tabs, function (tab) {
                var active = tab.getAttribute('data-ra-photos-tab') === name;

                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
                tab.tabIndex = active ? 0 : -1;
            });

            panes.list.hidden = name !== 'list';
            panes.grid.hidden = name !== 'grid';

            if (name === 'grid') {
                render();
            }
        }

        Array.prototype.forEach.call(tabs, function (tab) {
            tab.addEventListener('click', function () {
                show(tab.getAttribute('data-ra-photos-tab'));
            });

            tab.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
                    var other = tab.getAttribute('data-ra-photos-tab') === 'list' ? 'grid' : 'list';

                    show(other);
                    wrap.querySelector('[data-ra-photos-tab="' + other + '"]').focus();
                }
            });
        });
    });
})();
