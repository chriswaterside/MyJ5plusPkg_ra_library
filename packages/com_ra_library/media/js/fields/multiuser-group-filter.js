(function () {
    'use strict';

    function initGroupFilter() {
        document.querySelectorAll('.group-filter[data-target="multiuser"]').forEach((selector) => {
            const wrapper = selector.closest('.multiuser-wrapper');
            const items = wrapper ? wrapper.querySelectorAll('.user-checkbox[data-group-filter="multiuser"]') : [];
            if (!items.length)
                return;

            function applyFilter() {
                const selectedGroup = parseInt(selector.value, 10) || 0;

                items.forEach((item) => {
                    let groups = [];
                    try {
                        groups = JSON.parse(item.dataset.userGroups || item.dataset.groups || '[]');
                    } catch {
                        groups = [];
                    }

                    const show = selectedGroup === 0 || groups.includes(selectedGroup);
                    item.style.display = show ? '' : 'none';
                });
            }

            selector.addEventListener('change', applyFilter);
            applyFilter();
        });
    }

    // Initialise at first load
    document.addEventListener('DOMContentLoaded', initGroupFilter);

    // Re-run whenever Joomla dynamically updates content (form loaded via AJAX, repeatable field, etc.)
    document.addEventListener('joomla:updated', initGroupFilter);

    // Optional delayed reinit
    setTimeout(initGroupFilter, 100);
})();