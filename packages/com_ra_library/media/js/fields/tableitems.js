/* 
 * Table Items Custom Field - Joomla 5/6 - PRODUCTION READY
 * Supports filters='["normal","raw"]' - raw fields render as textarea
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */
(function () {
    'use strict';

    function debounce(func, delay) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => func.apply(this, args), delay);
        };
    }

    function initContainer(container) {
        const itemsList = container.querySelector('.items-list');
        if (!itemsList) return;

        const hiddenInput = container.querySelector('.items-json');
        const addBtn = container.querySelector('.add-item-btn');
        const fieldsPerItem = parseInt(container.dataset.fields || 2);
        const allowSort = (container.dataset.sort || 'true') !== 'false';
        const classes = JSON.parse(container.dataset.classes || '[]');
        const widths = JSON.parse(container.dataset.widths || '[]');
        const inputTypes = JSON.parse(container.dataset.inputtypes || Array(fieldsPerItem).fill('text'));
        const filters = JSON.parse(container.dataset.filters || Array(fieldsPerItem).fill('normal'));  // NEW

        const debouncedUpdate = debounce(function () {
            const items = Array.from(itemsList.querySelectorAll('.item-row'));
            const data = items.map(item => {
                const inputs = item.querySelectorAll('.field-input');
                const rowObj = {};
                inputs.forEach((input) => {
                    const fieldName = input.dataset.fieldname;
                    rowObj[fieldName] = input.value;
                });
                return rowObj;
            }).filter(row => Object.values(row).some(v => v.trim()));

            hiddenInput.value = JSON.stringify(data);
        }, 250);

        function updateJSON() {
            debouncedUpdate();
        }

        // Update existing rows to hide/show handles
        itemsList.querySelectorAll('.item-row').forEach(row => {
            const handle = row.querySelector('.handle');
            if (handle) {
                handle.style.display = allowSort ? '' : 'none';
            }
        });

        // Sortable initialization (only if allowed)
        if (allowSort && window.Sortable) {
            if (itemsList.sortableInstance) {
                itemsList.sortableInstance.destroy();
            }
            itemsList.sortableInstance = new Sortable(itemsList, {
                handle: '.handle',
                animation: 150,
                onEnd: updateJSON
            });
        }

        // Add row button
        if (addBtn && !addBtn.dataset.initialized) {
            addBtn.dataset.initialized = 'true';
            addBtn.addEventListener('click', function () {
                const index = itemsList.children.length;
                const newRow = createItemRow(index, fieldsPerItem, container, allowSort, filters);  // NEW: pass filters
                itemsList.appendChild(newRow);
                updateJSON();
            });
        }

        // Delegate remove buttons
        itemsList.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-item')) {
                e.target.closest('.item-row').remove();
                updateJSON();
            }
        });

        // Delegate input events (works for both input and textarea)
        itemsList.addEventListener('input', function (e) {
            if (e.target.matches('.field-input')) {
                debouncedUpdate();
            }
        }, true);

        updateJSON();
    }

    function createItemRow(index, numFields, container, allowSort, filters) {  // NEW: filters param
        const fieldNames = JSON.parse(container.dataset.fieldnames || '[]');
        const hints = JSON.parse(container.dataset.hints || JSON.stringify(fieldNames));
        const classes = JSON.parse(container.dataset.classes || '[]');
        const widths = JSON.parse(container.dataset.widths || '[]');
        const inputTypes = JSON.parse(container.dataset.inputtypes || Array(numFields).fill('text'));

        let inputsHtml = '';
        for (let f = 0; f < numFields; f++) {
            const name = fieldNames[f] || `Field ${f + 1}`;
            const hint = hints[f] || name;
            const inputType = inputTypes[f] || 'text';
            const fieldClass = classes[f] ? ` ${classes[f]}` : '';
            const style = widths[f] ? ` style="width: ${widths[f]};"` : '';
            const filter = filters[f] || 'normal';
            const isRaw = (filter === 'raw');

            if (isRaw) {
                // NEW: Textarea for raw fields
                inputsHtml += `<textarea class="field-input field-${f + 1}${fieldClass}" data-fieldname="${name}" placeholder="${hint}"${style}></textarea>`;
            } else {
                // Regular input
                inputsHtml += `<input type="${inputType}" class="field-input field-${f + 1}${fieldClass}" data-fieldname="${name}" placeholder="${hint}"${style}>`;
            }
        }

        const handleHtml = allowSort ? '<span class="handle">☰</span>' : '';

        const div = document.createElement('div');
        div.className = 'item-row';
        div.dataset.index = index;
        div.innerHTML = `
            ${handleHtml}
            <div class="inputs">${inputsHtml}</div>
            <button type="button" class="btn btn-sm btn-danger remove-item">×</button>
        `;
        return div;
    }

    // INITIALIZATION (unchanged)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllContainers);
    } else {
        initAllContainers();
    }

    function initAllContainers() {
        document.querySelectorAll('.table-items-container').forEach(initContainer);
    }

    let scanInterval;
    function startScanning() {
        if (scanInterval) return;
        scanInterval = setInterval(function () {
            document.querySelectorAll('.table-items-container:not([data-js-init])').forEach(function (container) {
                container.dataset.jsInit = 'true';
                initContainer(container);
            });
        }, 500);
    }

    setTimeout(startScanning, 100);

    document.addEventListener('showon-toggle', function (event, target) {
        const containers = target.matches('.table-items-container')
                ? [target]
                : target.querySelectorAll('.table-items-container');
        containers.forEach(initContainer);
    });

})();