/* 
 * Layout Check Status - SMART POSITIONING MERGED
 * Dynamic fields + focus + anti-clipping
 * copyright: Chris Vaughan
 */
document.addEventListener('DOMContentLoaded', function () {
    initAllFields();

    // Scan for dynamically added fields (tableitems new rows)
    let scanInterval = setInterval(function () {
        const newFields = document.querySelectorAll('.layout-check:not([data-status-init])');
        if (newFields.length === 0) {
//            if (document.querySelectorAll('.layout-check[data-status-init]').length > 0) {
//                clearInterval(scanInterval);
//            }
            return;
        }
        initField(newFields[0]);
    }, 1000);

    function initAllFields() {
        const fields = document.querySelectorAll('.layout-check:not([data-status-init])');
        fields.forEach(initField);
    }

    function initField(field) {
        if (field.dataset.statusInit === 'true')
            return;


        // Create status box (your existing styles + unique ID)
        const statusBox = document.createElement('div');
        statusBox.className = 'layout-check-status';
        statusBox.id = 'status-' + Math.random().toString(36).substr(2, 9);  // NEW: Unique ID

        statusBox.style.cssText = `
            position: fixed !important;
            top: auto !important;
            left: auto !important;
            right: auto !important;
        `;

        const wrapper = field.closest('.control-group, .field-group, .form-group') || field.parentNode;  // NEW: Better container
        wrapper.style.position = 'relative';
        wrapper.appendChild(statusBox);  // NEW: appendChild instead of insertBefore

        // NEW: Smart positioning
        function positionStatus() {
            const rect = field.getBoundingClientRect();
            const statusRect = statusBox.getBoundingClientRect();
            if (field.offsetWidth > 0) {
                statusBox.style.width = field.offsetWidth + 'px';
            }
            if (rect.left > 0) {
                statusBox.style.left = rect.left + 'px';
            }
            const topSpace = rect.top;
            if (topSpace > 50) {
                // Top (your original preference)
                statusBox.style.top = (rect.top - statusRect.height - 10) + 'px';
                statusBox.style.left = rect.left + 'px';
            } else {
                // Bottom fallback (anti-clip)
                statusBox.style.top = (rect.bottom + 5) + 'px';
                statusBox.style.left = rect.left + 'px';
            }
        }

        // Event handlers (your existing + positioning)
        field.addEventListener('input', function () {
            positionStatus();  // NEW     
            updateStatus.call(this);

        });

        field.addEventListener('focus', function () {
            positionStatus();  // NEW: Position on focus
            updateStatus.call(this);
        });

        field.addEventListener('blur', function () {
            statusBox.style.visibility = 'hidden';
            statusBox.style.opacity = '0';
        });

        // Initial setup
        positionStatus();
        updateStatus.call(field);
        if (field.dataset.statusInit !== 'true') {
            statusBox.style.visibility = 'hidden';
        }
        field.dataset.statusInit = 'true';

        function updateStatus() {
            const value = this.value.trim();
            const result = validateInput(value);
            if (result.okay === true) {
                statusBox.style.background = '#d4edda';
                statusBox.style.borderColor = '#c3e6cb';
            } else {
                statusBox.style.background = '#f8d7da';
                statusBox.style.borderColor = '#f5c6cb';
            }
            statusBox.textContent = result.msg;

            statusBox.style.visibility = 'visible';
            statusBox.style.opacity = '1';
        }
    }

    function validateInput(value) {
        try {
            var out = getStatus(value, false);
            return {okay: true, msg: out};
        } catch (e) {
            return {okay: false, msg: e.message};
        }
    }

    // Re-init on Joomla showon toggle
    document.addEventListener('showon-toggle', function (event) {
        const newFields = event.target.querySelectorAll('.layout-check:not([data-status-init])');
        newFields.forEach(initField);
    });

    function  getStatus(text) {
        var temp = new ra.SimpleTemplate(text);
        var fields = temp.getFields();
        out = "Fields found: " + fields.join(", ");
        return  out;
    }


});