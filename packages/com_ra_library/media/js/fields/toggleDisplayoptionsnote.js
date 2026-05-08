/* 
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */

(function () {
    // Wait for the page to be ready
    window.addEventListener('DOMContentLoaded', function () {
        const original = Joomla.getOptions('form', {}).originalDisplayoption || '';
        const select = document.querySelector('select[name="jform[displayoption]"]');
        const note  = document.querySelector('.displayoption-note');

        if (!select || !note) return;

        function toggleNote(visible) {
            note.style.display = visible ? '' : 'none';
        }

        // Show note only if current value differs from saved
        if (select.value !== original) {
            toggleNote(true);
        } else {
            toggleNote(false);
        }

        select.addEventListener('change', function () {
            const isChanged = select.value !== original;
            toggleNote(isChanged);
        });
    });
})();