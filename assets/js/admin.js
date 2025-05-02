(function ($) {
    'use strict';

    $(document).ready(function () {
        // Initialize any admin-specific JavaScript here
        console.log('Athena AI admin initialized');

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
