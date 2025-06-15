(function ($) {
    'use strict';

    // Update input state for floating labels
    function updateInputState(input) {
        const parent = input.closest('.form-group');
        const floatingLabel = parent ? parent.querySelector('.floating-label') : null;

        if (!parent || !floatingLabel) return;

        if (input.value && input.value.trim() !== '') {
            input.setAttribute('data-filled', 'true');
            parent.classList.add('has-value');
            floatingLabel.classList.add('floating');
        } else {
            input.removeAttribute('data-filled');
            parent.classList.remove('has-value');
            floatingLabel.classList.remove('floating');
        }

        // Always ensure the floating label is visible
        floatingLabel.style.opacity = '1';
    }

    // Initialize floating labels
    function initFloatingLabels() {
        // Handle input events for all form controls
        const formControls = document.querySelectorAll(
            '.form-group input, .form-group textarea, .form-group select'
        );

        formControls.forEach((input) => {
            // Check if the input has a value on page load
            updateInputState(input);

            // Add event listeners
            input.addEventListener('focus', function () {
                const parent = this.closest('.form-group');
                if (parent) {
                    parent.classList.add('focused');
                    updateInputState(this);
                }
            });

            input.addEventListener('blur', function () {
                const parent = this.closest('.form-group');
                if (parent) {
                    parent.classList.remove('focused');
                    updateInputState(this);
                }
            });

            // Handle input changes in real-time
            input.addEventListener('input', function () {
                updateInputState(this);
            });

            // Handle change events for selects
            input.addEventListener('change', function () {
                updateInputState(this);
            });
        });

        // Debug: Log how many floating labels were initialized
        console.log(`Athena AI: Initialized ${formControls.length} floating label controls`);
    }

    // Re-initialize floating labels (useful after dynamic content changes)
    function reinitFloatingLabels() {
        console.log('Athena AI: Re-initializing floating labels...');
        initFloatingLabels();
    }

    // Make reinit function globally available
    window.athenaAIReinitFloatingLabels = reinitFloatingLabels;

    // Debug function for floating labels
    window.athenaAIDebugFloatingLabels = function () {
        const formGroups = document.querySelectorAll('.form-group');
        console.log('=== Athena AI Floating Labels Debug ===');
        console.log(`Found ${formGroups.length} form groups`);

        formGroups.forEach((group, index) => {
            const input = group.querySelector('input, textarea, select');
            const label = group.querySelector('.floating-label');

            console.log(`Group ${index + 1}:`);
            console.log('  Input:', input ? input.tagName + '#' + input.id : 'Not found');
            console.log('  Label:', label ? 'Found' : 'Not found');
            console.log('  Input value:', input ? `"${input.value}"` : 'N/A');
            console.log('  Has data-filled:', input ? input.hasAttribute('data-filled') : 'N/A');
            console.log('  Group classes:', group.className);
            console.log('  Label classes:', label ? label.className : 'N/A');
            console.log('---');
        });

        return {
            totalGroups: formGroups.length,
            withInputs: Array.from(formGroups).filter((g) =>
                g.querySelector('input, textarea, select')
            ).length,
            withLabels: Array.from(formGroups).filter((g) => g.querySelector('.floating-label'))
                .length,
        };
    };

    $(document).ready(function () {
        // Initialize any admin-specific JavaScript here
        console.log('Athena AI admin initialized');

        // Initialize floating labels
        initFloatingLabels();

        // Re-initialize after a short delay to catch any dynamically loaded content
        setTimeout(initFloatingLabels, 500);

        // Feed Dropdown Handling
        const feedCheckboxes = document.querySelectorAll('input[name="feed_ids[]"]');

        if (feedCheckboxes.length > 0) {
            // Select All / Clear All buttons
            const selectAllButton = document.getElementById('select-all-feeds');
            const clearAllButton = document.getElementById('clear-all-feeds');

            if (selectAllButton && clearAllButton) {
                selectAllButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    feedCheckboxes.forEach((checkbox) => (checkbox.checked = true));
                });

                clearAllButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    feedCheckboxes.forEach((checkbox) => (checkbox.checked = false));
                });
            }

            // Manuelle Initialisierung des Dropdowns, falls Flowbite nicht richtig funktioniert
            const dropdownButton = document.getElementById('feedFilterDropdownButton');
            const dropdown = document.getElementById('feedFilterDropdown');

            if (dropdownButton && dropdown) {
                dropdownButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdown.classList.toggle('hidden');
                });

                // Schließen des Dropdowns beim Klicken außerhalb
                document.addEventListener('click', function (event) {
                    if (
                        !dropdownButton.contains(event.target) &&
                        !dropdown.contains(event.target)
                    ) {
                        dropdown.classList.add('hidden');
                    }
                });
            }
        }
    });
})(jQuery);

// Laden von Flowbite
document.addEventListener('DOMContentLoaded', function () {
    // Flowbite als ESM importieren
    const script = document.createElement('script');
    script.src = 'node_modules/flowbite/dist/flowbite.min.js';
    script.onload = function () {
        console.log('Flowbite loaded');

        // Initialisiere Dropdowns manuell nach dem Laden von Flowbite
        if (typeof initFlowbite === 'function') {
            initFlowbite();
        } else if (window.Flowbite) {
            console.log('Initializing dropdowns manually');
            const dropdownButton = document.getElementById('feedFilterDropdownButton');
            const dropdown = document.getElementById('feedFilterDropdown');

            if (dropdownButton && dropdown) {
                new window.Flowbite.Dropdown(dropdownButton, dropdown);
            }
        }
    };
    document.head.appendChild(script);
});
