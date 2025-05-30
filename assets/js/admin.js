(function ($) {
    'use strict';

    // Update input state for floating labels
    function updateInputState(input) {
        const parent = input.parentElement;
        const floatingLabel = parent.querySelector('.floating-label');
        
        if (input.value) {
            input.setAttribute('data-filled', 'true');
            parent.classList.add('has-value');
        } else {
            input.removeAttribute('data-filled');
            parent.classList.remove('has-value');
        }
        
        // Always ensure the floating label is visible
        if (floatingLabel) {
            floatingLabel.style.opacity = '1';
        }
    }

    // Initialize floating labels
    function initFloatingLabels() {
        // Handle input events
        document.querySelectorAll('.form-group input, .form-group textarea, .form-group select').forEach(input => {
            // Check if the input has a value on page load
            updateInputState(input);

            // Add event listeners
            input.addEventListener('focus', function() {
                const parent = this.parentElement;
                parent.classList.add('focused');
                updateInputState(this);
            });

            input.addEventListener('blur', function() {
                const parent = this.parentElement;
                parent.classList.remove('focused');
                updateInputState(this);
            });
            
            // Initialize the label state
            updateInputState(input);

            // Handle input changes in real-time
            input.addEventListener('input', function() {
                updateInputState(this);
            });
        });
    }


    $(document).ready(function () {
        // Initialize any admin-specific JavaScript here
        console.log('Athena AI admin initialized');
        
        // Initialize floating labels
        initFloatingLabels();

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
