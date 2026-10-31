/*
 * Keeps the "Photo limits (n of 50 used)" note on the Past Walk / Route edit
 * forms up to date as photos are uploaded or removed, and tells the uploader
 * how much room is left.
 *
 * "Used" is the number of photos currently in the Photo details list (rows
 * with a stored thumbnail), so it also drops as soon as a photo is removed
 * from the list - even though the photo is only deleted for good on Save.
 *
 * Options (Joomla.getOptions('com_ra_library.photolimits')): { max: 50 }
 * Exposes window.RaPhotos = { count(), max, remaining() }.
 */
(function () {
    'use strict';

    var opts = (window.Joomla && Joomla.getOptions && Joomla.getOptions('com_ra_library.photolimits')) || {};
    var max = opts.max || 50;

    function text(key, fallback) {
        return (window.Joomla && Joomla.Text && Joomla.Text._(key)) || fallback;
    }

    function fill(template, values) {
        var i = 0;

        return template.replace(/%[sd]/g, function () {
            return values[i++];
        });
    }

    function count() {
        var sf = document.querySelector('#ra-photos-pane-list joomla-field-subform');
        var n = 0;

        if (!sf) {
            return 0;
        }

        Array.prototype.forEach.call(sf.querySelectorAll(':scope > .subform-repeatable-group'), function (row) {
            var input = row.querySelector('input[name$="[thumbnail_path]"]');

            if (input && input.value) {
                n++;
            }
        });

        return n;
    }

    window.RaPhotos = {
        max: max,
        count: count,
        remaining: function () {
            return Math.max(0, max - count());
        }
    };

    function update() {
        var used = count();
        var summary = document.getElementById('ra-photo-limits-summary');
        var line = document.getElementById('ra-photo-limits-count');

        if (summary) {
            summary.textContent = fill(text('COM_RA_LIBRARY_PHOTO_LIMITS_SUMMARY', 'Photo limits (%d of %d used)'), [used, max]);
        }

        if (line) {
            line.textContent = fill(text('COM_RA_LIBRARY_PHOTO_LIMIT_COUNT', 'Up to %d photos in total. This gallery has %d, so there is room for %d more.'), [max, used, Math.max(0, max - used)]);
        }
    }

    document.addEventListener('ra:photos-changed', update);

    // Fires just BEFORE the row is taken out, so look again once it has gone.
    document.addEventListener('subform-row-remove', function () {
        setTimeout(update, 0);
    });

    document.addEventListener('DOMContentLoaded', update);
})();
