/**
 * Admin "Syntax" / "Fields" help buttons - see
 * administrator/src/Field/LayoutHelpButtonsTrait.php, which renders these
 * buttons after a template field's label, each pointing (via data-target)
 * at a hidden div holding the relevant documentation. Clicking a button
 * shows that content in the component's existing ra.modals popup, the same
 * mechanism used for every other admin/front-end popup in this component -
 * nothing bespoke here.
 *
 * @since 1.0.0
 */
(function () {
    'use strict';

    function openHelp(title, html) {
        var wrapper = document.createElement('div');

        var heading = document.createElement('h3');
        heading.textContent = title;
        wrapper.appendChild(heading);

        var content = document.createElement('div');
        content.innerHTML = html;
        wrapper.appendChild(content);

        ra.modals.createModal(wrapper, false);
    }

    function initButton(button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-target');
            var target = targetId ? document.getElementById(targetId) : null;

            if (!target) {
                return;
            }

            openHelp(button.textContent.trim(), target.innerHTML);
        });
    }

    function init() {
        var buttons = document.querySelectorAll('.ra-layouthelp-btn');

        for (var i = 0; i < buttons.length; i++) {
            initButton(buttons[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
