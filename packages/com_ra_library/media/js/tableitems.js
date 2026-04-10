/* 
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.table-items-container').forEach(container => {
        const itemsList = container.querySelector('.items-list');
        const hiddenInput = container.querySelector('.items-json');
        const addBtn = container.querySelector('.add-item-btn');
        const fieldsPerItem = parseInt(container.dataset.fields || 2);

        // Make updateJSON accessible to all handlers
        function updateJSON() {
            const items = Array.from(itemsList.querySelectorAll('.item-row'));
            const data = items.map(item => {
                const inputs = item.querySelectorAll('.field-input');
                return Array.from(inputs).map(input => input.value);
            }).filter(row => row.some(v => v.trim())); // Skip empty rows
            hiddenInput.value = JSON.stringify(data);
        }

        // Sortable
        new Sortable(itemsList, {
            handle: '.handle',
            animation: 150,
            onEnd: function (evt) {
                updateJSON();
            }
        });

        // Add row
        addBtn.addEventListener('click', function () {
            const index = itemsList.children.length;
            const newRow = createItemRow(index, fieldsPerItem);
            itemsList.appendChild(newRow);
            updateJSON();
        });

        // Delegate remove (handles dynamic adds)
        itemsList.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-item')) {
                e.target.closest('.item-row').remove();
                updateJSON();
            }
        });

        // Initial update
        updateJSON();

        function createItemRow(index, numFields) {
            // Fix: Read custom field names from container data attr
            const fieldNames = JSON.parse(container.dataset.fieldnames || '[]');
            let inputsHtml = '';
            for (let f = 0; f < numFields; f++) {
                const name = fieldNames[f] || `Field ${f + 1}`;
                inputsHtml += `<input type="text" class="field-input field-${f + 1}" data-fieldname="${name}" placeholder="${name}">`;
            }
            const div = document.createElement('div');
            div.className = 'item-row';
            div.dataset.index = index;
            div.innerHTML = `
        <span class="handle">☰</span>
        <div class="inputs">${inputsHtml}</div>
        <button type="button" class="btn btn-sm btn-danger remove-item">×</button>
    `;
            return div;
        }
    });
});