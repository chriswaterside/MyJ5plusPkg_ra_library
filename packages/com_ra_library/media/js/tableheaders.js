/* 
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.table-headers-container').forEach(container => {
        const list = container.querySelector('.headers-list');
        const hiddenInput = container.querySelector('.headers-json');
        let nextIndex = list.children.length;
        
        // ✅ Fixed: Create HTML template string directly
        function createHeaderRow(index, value = '') {
            return `<div class="header-row" data-index="${index}">
                        <span class="handle">☰</span>
                        <input type="text" class="header-text" value="${value}" placeholder="Column Header">
                        <button type="button" class="btn btn-sm btn-danger remove-header">×</button>
                    </div>`;
        }
        
        // Make sortable
        new Sortable(list, {
            handle: '.handle',
            animation: 150,
            onEnd: updateHiddenInput
        });
        
        // Add header button
        container.querySelector('.add-header-btn').addEventListener('click', function() {
            list.insertAdjacentHTML('beforeend', createHeaderRow(nextIndex, ''));
            nextIndex++;
            updateHiddenInput();
        });
        
        // Remove buttons
        list.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-header')) {
                e.target.closest('.header-row').remove();
                updateHiddenInput();
            }
        });
        
        // Text changes
        list.addEventListener('input', updateHiddenInput);
        
        function updateHiddenInput() {
            const headers = Array.from(list.querySelectorAll('.header-text'))
                .map(input => input.value.trim())
                .filter(value => value !== '');
            
            hiddenInput.value = JSON.stringify(headers);
        }
    });
});