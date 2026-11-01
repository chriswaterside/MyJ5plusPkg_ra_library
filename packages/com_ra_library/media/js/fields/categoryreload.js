/**
 * Reload the edit form when the category changes, so that custom fields which
 * are assigned to specific categories appear/disappear straight away.
 *
 * Mirrors what Joomla does for articles (Joomla.categoryHasChanged), but works
 * with the plain "category" field type. The reload posts the form to
 * <section>.reload, which stores what has been typed in the session and
 * redirects back to the edit form - nothing is saved.
 *
 * Options (Joomla script options 'com_ra_library.categoryreload'):
 *   section  - controller name, 'pastwalk' or 'route'
 */
(function (Joomla) {
    'use strict';

    var opts = (Joomla && Joomla.getOptions) ? Joomla.getOptions('com_ra_library.categoryreload') : null;
    if (!opts || !opts.section) {
        return;
    }

    var select = document.getElementById('jform_catid');
    if (!select || !select.form) {
        return;
    }

    var current = select.value;

    function hasPendingFiles(form) {
        var inputs = form.querySelectorAll('input[type=file]');
        for (var i = 0; i < inputs.length; i++) {
            if (inputs[i].files && inputs[i].files.length) {
                return true;
            }
        }
        return false;
    }

    select.addEventListener('change', function () {
        if (select.value === current) {
            return;
        }

        var form = select.form;

        // Files chosen in the browser cannot survive a reload.
        if (hasPendingFiles(form) && !window.confirm(Joomla.Text._('COM_RA_LIBRARY_CATEGORY_RELOAD_FILES'))) {
            select.value = current;
            return;
        }

        current = select.value;

        var loader = document.createElement('joomla-core-loader');
        document.body.appendChild(loader);

        var task = form.querySelector('input[name=task]');
        if (task) {
            task.value = opts.section + '.reload';
        }
        Joomla.submitform(opts.section + '.reload', form);
    });
})(window.Joomla);
