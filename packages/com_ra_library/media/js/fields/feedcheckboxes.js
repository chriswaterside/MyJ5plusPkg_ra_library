/**
 * Feed Checkboxes Field - FIXED ID Matching
 */

function initFeedcheckboxes(wrapper) {
    wrapper.removeAttribute('data-initialized');
    wrapper.dataset.initialized = 'true';

    const checkboxes = wrapper.querySelectorAll('input[type="checkbox"]');
    const hiddenInput = wrapper.querySelector('input[type="hidden"]'); // or add a specific class
    const selectedContainer = wrapper.querySelector('.selected-container'); // add this class in your layout

    if (!hiddenInput || !selectedContainer || checkboxes.length === 0) return;

    const updateField = () => {
        const selectedData = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => ({
                code: cb.value,
                name: cb.parentElement.textContent.trim()
            }));

        hiddenInput.value = JSON.stringify(selectedData);
        selectedContainer.innerHTML = '';
        selectedData.forEach(item => {
            const chip = document.createElement('span');
            chip.className = 'selected-chip';
            chip.dataset.code = item.code;
            chip.innerHTML = `${item.name} <span class="remove-x">&times;</span>`;
            selectedContainer.appendChild(chip);
        });
    };

    // Clear existing listeners on this wrapper
    checkboxes.forEach(cb => {
        cb.removeEventListener('change', updateField);
        cb.addEventListener('change', updateField);
    });

    selectedContainer.removeEventListener('click', updateField);
    selectedContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-x')) {
            const chip = e.target.closest('.selected-chip');
            const code = chip.dataset.code;
            const checkbox = wrapper.querySelector(
                `input[type="checkbox"][value="${CSS.escape(code)}"]`
            );
            if (checkbox) {
                checkbox.checked = false;
                chip.remove();
            }
            updateField();
        }
    });

    updateField();
}

function safeInit() {
    document.querySelectorAll('.feedcheckboxes-field').forEach(initFeedcheckboxes);
}

// Aggressive init
function pollInit() {
    safeInit();
  //  setTimeout(safeInit, 300);
 //   setTimeout(safeInit, 800);
 //   setTimeout(safeInit, 1500);
}
function rebirthOnShowon(event) {
    safeInit();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', pollInit);
} else {
    pollInit();
}

// Joomla events
['subform-row-add', 'subform-row-remove'].forEach(event => {
    document.addEventListener(event, safeInit);
});

// !! NEW: watch for showon changes and re‑init field wrappers
document.addEventListener('joomla:showon', rebirthOnShowon);
document.addEventListener('joomla:showon-hide', rebirthOnShowon);