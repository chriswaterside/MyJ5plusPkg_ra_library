document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sortable-list').forEach(container => {
        const sortable = new Sortable(container, {
            handle: '.sortable-handle',
            animation: 150,
            draggable: '.sortable-item:has(input:checked)'
            // NO onEnd / ensureSelectedAtTop / ensureUnselectedAtBottom
        });

        container.addEventListener('change', function(e) {
            if (e.target.type === 'checkbox') {
                // Do nothing here re‑ordering; rely on PHP‑emitted order.
            }
        });
    });
});