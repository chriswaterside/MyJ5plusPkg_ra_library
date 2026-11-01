/*
 * Background photo uploader on the saved Past Walk / Route edit forms.
 *
 * Photos are posted one per request (so the server's post_max_size and
 * max_file_uploads limits never come into play - only upload_max_filesize,
 * checked here first) to <section>.uploadImage, stored straight away, and a
 * matching row is added to the "Photo details" subform (with the new row's id
 * and thumbnail path) so the next Save keeps it - Save removes any stored
 * photo that is not in the submitted form.
 *
 * Options (Joomla.getOptions('com_ra_library.photouploader')):
 *   url           endpoint, ends in "&task=<section>.uploadImage&format=json"
 *   token         name of the Joomla form token field
 *   id            record id
 *   maxFileBytes  per-file limit in bytes (0 = none)
 *   remaining     photos that still fit in this gallery
 */
(function () {
    'use strict';

    function text(key, fallback) {
        return (window.Joomla && Joomla.Text && Joomla.Text._(key)) || fallback;
    }

    function fill(template, values) {
        var i = 0;
        return template.replace(/%s/g, function () {
            return values[i++];
        });
    }

    function formatBytes(bytes) {
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var zone = document.getElementById('ra-uploader');
        var input = document.getElementById('ra-uploader-input');
        var list = document.getElementById('ra-uploader-list');
        var status = document.getElementById('ra-uploader-status');
        var opts = (window.Joomla && Joomla.getOptions && Joomla.getOptions('com_ra_library.photouploader')) || null;

        if (!zone || !input || !list || !opts || !opts.url) {
            return;
        }

        var remaining = typeof opts.remaining === 'number' ? opts.remaining : 50;

        // Room left in the gallery: live from the form (photolimits.js) so
        // removed photos free up space, else the figure the page loaded with.
        function room() {
            return window.RaPhotos ? window.RaPhotos.remaining() : remaining;
        }
        var busy = false;
        var queue = [];
        var ok = 0;
        var skipped = 0;
        var failed = 0;

        function subformElement() {
            return document.querySelector('#ra-photos-pane-list joomla-field-subform');
        }

        /* ---------- adding the stored photo to the subform ---------- */

        function addRow(row) {
            var sf = subformElement();

            if (!sf || typeof sf.addRow !== 'function') {
                return false;
            }

            var rows = sf.getRows ? sf.getRows() : [];
            var newRow = sf.addRow(rows.length ? rows[rows.length - 1] : null);

            if (!newRow) {
                return false;
            }

            var idInput = newRow.querySelector('input[name$="[id]"]');
            var pathInput = newRow.querySelector('input[name$="[thumbnail_path]"]');

            if (!idInput || !pathInput) {
                return false;
            }

            idInput.value = String(row.id);
            pathInput.value = row.thumbnail_path;

            // Replace the "new row" placeholder text with the same thumbnail
            // link the server draws for saved rows (hover/click viewer picks it up).
            var holder = pathInput.parentNode;
            var placeholder = holder.querySelector('span.text-muted');
            var link = document.createElement('a');
            var img = document.createElement('img');

            link.className = 'ra-photo-thumb';
            link.href = row.large_url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.title = text('COM_RA_LIBRARY_IMAGE_VIEW_LARGER', 'View larger');
            img.src = row.thumbnail_url;
            img.alt = '';
            link.appendChild(img);

            if (placeholder) {
                holder.replaceChild(link, placeholder);
            } else {
                holder.appendChild(link);
            }

            // Lets the reorder grid redraw if it is the tab being shown.
            document.dispatchEvent(new CustomEvent('ra:photos-changed'));

            return true;
        }

        /* ---------- progress list ---------- */

        function addItem(name) {
            var li = document.createElement('li');
            var label = document.createElement('span');
            var state = document.createElement('span');

            li.className = 'ra-uploader-item';
            label.className = 'ra-uploader-name';
            label.textContent = name;
            state.className = 'ra-uploader-state';
            state.textContent = text('COM_RA_LIBRARY_UPLOAD_WAITING', 'Waiting');
            li.appendChild(label);
            li.appendChild(state);
            list.appendChild(li);

            return { li: li, state: state };
        }

        function setState(item, message, kind) {
            item.state.textContent = message;
            item.li.className = 'ra-uploader-item' + (kind ? ' ra-uploader-' + kind : '');
        }

        function summary() {
            var parts = [];

            if (ok) {
                parts.push(fill(text('COM_RA_LIBRARY_UPLOAD_SUM_ADDED', '%s added'), [ok]));
            }
            if (skipped) {
                parts.push(fill(text('COM_RA_LIBRARY_UPLOAD_SUM_SKIPPED', '%s skipped'), [skipped]));
            }
            if (failed) {
                parts.push(fill(text('COM_RA_LIBRARY_UPLOAD_SUM_FAILED', '%s failed'), [failed]));
            }

            status.textContent = parts.join(', ');
            status.classList.toggle('text-danger', failed > 0);
        }

        /* ---------- uploading ---------- */

        function parse(xhr) {
            try {
                return JSON.parse(xhr.responseText);
            } catch (e) {
                return null;
            }
        }

        function send(job, done) {
            var xhr = new XMLHttpRequest();
            var data = new FormData();

            data.append('id', String(opts.id));
            data.append('photo', job.file);
            data.append(opts.token, '1');

            xhr.open('POST', opts.url);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.addEventListener('progress', function (event) {
                if (event.lengthComputable) {
                    setState(job.item, Math.round(event.loaded / event.total * 100) + '%', 'busy');
                }
            });

            xhr.addEventListener('load', function () {
                var reply = parse(xhr);

                if (reply && reply.success && reply.data && reply.data.row) {
                    if (addRow(reply.data.row)) {
                        remaining--;
                        ok++;
                        setState(job.item, text('COM_RA_LIBRARY_UPLOAD_DONE', 'Added'), 'ok');
                    } else {
                        // Stored on the server but the form could not show it.
                        ok++;
                        setState(job.item, text('COM_RA_LIBRARY_UPLOAD_RELOAD', 'Added - reload the page to see it'), 'ok');
                    }
                } else if (reply && reply.data && (reply.data.status === 'duplicate')) {
                    skipped++;
                    setState(job.item, text('COM_RA_LIBRARY_UPLOAD_DUP_SHORT', 'Already in this gallery'), 'skip');
                } else {
                    failed++;
                    setState(job.item, (reply && reply.message) || text('COM_RA_LIBRARY_UPLOAD_ERR_SERVER', 'Upload failed - the file may be larger than the server allows (PHP settings upload_max_filesize / post_max_size).'), 'error');
                }

                done();
            });

            xhr.addEventListener('error', function () {
                failed++;
                setState(job.item, text('COM_RA_LIBRARY_UPLOAD_ERR_NETWORK', 'Network error'), 'error');
                done();
            });

            xhr.send(data);
        }

        function next() {
            var job = queue.shift();

            if (!job) {
                busy = false;
                summary();
                return;
            }

            busy = true;

            // Room is decided here, as each photo is about to be sent, so
            // duplicates and failures earlier in the batch do not use it up.
            if (room() <= 0) {
                failed++;
                setState(job.item, text('COM_RA_LIBRARY_UPLOAD_ERR_FULL', 'No room left in this gallery'), 'error');
                next();
                return;
            }

            setState(job.item, '0%', 'busy');
            send(job, next);
        }

        function enqueue(files) {
            files = Array.prototype.slice.call(files || []);

            if (!files.length) {
                return;
            }

            if (!busy) {
                ok = skipped = failed = 0;
                list.textContent = '';
            }

            files.forEach(function (file) {
                var item = addItem(file.name);

                if (!/^image\/(jpeg|png|gif)$/.test(file.type) && !/\.(jpe?g|png|gif)$/i.test(file.name)) {
                    failed++;
                    setState(item, text('COM_RA_LIBRARY_UPLOAD_ERR_TYPE', 'Not a JPEG, PNG or GIF'), 'error');
                } else if (opts.maxFileBytes && file.size > opts.maxFileBytes) {
                    failed++;
                    setState(item, fill(text('COM_RA_LIBRARY_UPLOAD_ERR_SIZE', 'Too large (%s; limit %s - PHP setting upload_max_filesize)'), [formatBytes(file.size), formatBytes(opts.maxFileBytes)]), 'error');
                } else {
                    queue.push({ file: file, item: item });
                }
            });

            if (!busy) {
                if (queue.length) {
                    next();
                } else {
                    summary();
                }
            }
        }

        input.addEventListener('change', function () {
            enqueue(input.files);
            input.value = '';
        });

        ['dragenter', 'dragover'].forEach(function (name) {
            zone.addEventListener(name, function (event) {
                event.preventDefault();
                zone.classList.add('ra-uploader-over');
            });
        });

        ['dragleave', 'drop'].forEach(function (name) {
            zone.addEventListener(name, function (event) {
                event.preventDefault();
                zone.classList.remove('ra-uploader-over');
            });
        });

        zone.addEventListener('drop', function (event) {
            enqueue(event.dataTransfer && event.dataTransfer.files);
        });

        // Leaving while uploads are running would abandon the queue.
        window.addEventListener('beforeunload', function (event) {
            if (busy) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    });
})();
