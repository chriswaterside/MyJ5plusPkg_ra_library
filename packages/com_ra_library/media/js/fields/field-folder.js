/**
 * Lazy-loading folder tree for the custom "Folder" form field.
 *
 * Publish this at:
 *   administrator/components/com_yourcomponent/media/js/field-folder.js
 * (matching the path FolderField.php's loadAssets() enqueues).
 *
 * No dependencies beyond Bootstrap's modal JS, which Joomla's admin
 * template already loads on every backend page.
 */
(function () {
    'use strict';

    function qs(sel, ctx) {
        return (ctx || document).querySelector(sel);
    }

    function qsa(sel, ctx) {
        return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
    }

    function initFolderField(root) {
        var fieldId = root.dataset.fieldId;
        var ajaxUrl = root.dataset.ajaxUrl;
        var tokenName = root.dataset.tokenName;
        var roots = root.dataset.roots.split(',').map(function (s) {
            return s.trim();
        }).filter(Boolean);

        var hiddenInput = qs('#' + fieldId, root);
        var displayInput = qs('#' + fieldId + '_display', root);
        var modalEl = qs('#' + fieldId + '_modal', root);
        var treeEl = qs('#' + fieldId + '_tree', root);
        var confirmBtn = qs('#' + fieldId + '_confirm', root);
        var browseBtn = qs('.folder-field-browse', root);

        var cache = new Map(); // path -> [{name, path}, ...]
        var pending = {path: null};
        var modal = null;

        // Created lazily, on first use, rather than at page-load time: this
        // guarantees Bootstrap's JS has fully finished loading by the time we
        // need it, regardless of script-loading order. If it's still missing
        // by the time someone clicks Browse, fall back to a plain show/hide
        // and say why in the console rather than throwing.
        function getModal() {
            if (modal !== null) {
                return modal;
            }

            if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                modal = new window.bootstrap.Modal(modalEl);
            } else {
                modal = false;
                // eslint-disable-next-line no-console
                console.warn(
                        'FolderField: window.bootstrap.Modal is unavailable — falling back to a plain '
                        + 'show/hide. Make sure HTMLHelper::_(\'bootstrap.modal\') is called server-side '
                        + '(see FolderField.php loadAssets()).'
                        );
            }

            return modal;
        }

        function fetchChildren(path) {
            if (cache.has(path)) {
                return Promise.resolve(cache.get(path));
            }

            var body = new URLSearchParams();
            body.set('path', path);
            body.set(tokenName, '1');

            return fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body.toString(),
                credentials: 'same-origin'
            })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        var folders = (data && data.folders) || [];
                        cache.set(path, folders);
                        return folders;
                    })
                    .catch(function () {
                        return [];
                    });
        }

        function selectNode(labelEl, path) {
            qsa('.folder-field-label.is-selected', treeEl).forEach(function (el) {
                el.classList.remove('is-selected');
            });
            labelEl.classList.add('is-selected');
            pending.path = path;
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Use "/' + path + '"';
        }

        function buildNode(name, path) {
            var li = document.createElement('li');

            var row = document.createElement('div');
            row.className = 'folder-field-row d-flex align-items-center';

            var toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'folder-field-toggle btn btn-sm btn-link p-0 me-1';
            toggle.textContent = '▸'; // ▸
            toggle.setAttribute('aria-label', 'Expand');

            var label = document.createElement('span');
            label.className = 'folder-field-label';
            label.textContent = name;
            label.setAttribute('role', 'button');
            label.tabIndex = 0;

            row.appendChild(toggle);
            row.appendChild(label);
            li.appendChild(row);

            var childList = document.createElement('ul');
            childList.className = 'ps-4';
            childList.hidden = true;
            li.appendChild(childList);

            var loaded = false;

            function expand() {
                if (loaded) {
                    childList.hidden = !childList.hidden;
                    toggle.textContent = childList.hidden ? '▸' : '▾';
                    return;
                }

                toggle.textContent = '…'; // …

                fetchChildren(path).then(function (folders) {
                    loaded = true;

                    if (!folders.length) {
                        toggle.disabled = true;
                        toggle.textContent = '';
                        return;
                    }

                    folders.forEach(function (folder) {
                        childList.appendChild(buildNode(folder.name, folder.path));
                    });

                    childList.hidden = false;
                    toggle.textContent = '▾'; // ▾
                });
            }

            toggle.addEventListener('click', expand);
            label.addEventListener('click', function () {
                selectNode(label, path);
            });
            label.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectNode(label, path);
                }
            });

            return li;
        }

        function buildRoots() {
            treeEl.innerHTML = '';
            roots.forEach(function (rootName) {
                treeEl.appendChild(buildNode('/' + rootName, rootName));
            });
        }

        browseBtn.addEventListener('click', function () {
            if (!treeEl.dataset.built) {
                buildRoots();
                treeEl.dataset.built = '1';
            }

            pending = {path: null};
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Use this folder';

            var m = getModal();

            if (m) {
                m.show();
            } else {
                modalEl.style.display = 'block';
            }
        });

        confirmBtn.addEventListener('click', function () {
            if (pending.path === null) {
                return;
            }

            hiddenInput.value = pending.path;
            displayInput.value = '/' + pending.path;
            hiddenInput.dispatchEvent(new Event('change', {bubbles: true}));

            var m = getModal();

            if (m) {
                m.hide();
            } else {
                modalEl.style.display = 'none';
            }
        });

        // Harmless no-op once a real Modal instance exists (Bootstrap handles
        // its own dismiss buttons); only actually closes anything when the
        // no-bootstrap fallback path above is in play.
        qsa('[data-bs-dismiss="modal"]', modalEl).forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!getModal()) {
                    modalEl.style.display = 'none';
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        qsa('.folder-field').forEach(initFolderField);
    });
}());