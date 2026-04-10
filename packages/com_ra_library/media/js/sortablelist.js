/* 
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sortable-list').forEach(container => {
        const sortable = new Sortable(container, {
            handle: '.sortable-handle',
            animation: 150,
            // Only drag SELECTED items
            draggable: '.sortable-item:has(input:checked)',
            onEnd: function() {
                // Reorder only selected items
                updateOrder(container);
            }
        });

        // Listen for checkbox changes
        container.addEventListener('change', function(e) {
            if (e.target.type === 'checkbox') {
                updateOrder(container);
            }
        });
    });

    function updateOrder(container) {
        const selectedItems = Array.from(container.querySelectorAll('input:checked'))
            .map(cb => cb.closest('.sortable-item'));
        
        // Update visual order of selected items only
        selectedItems.forEach((item, index) => {
            container.appendChild(item); // Move to end in order
        });
    }
});