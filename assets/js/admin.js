(function ($) {
  "use strict";

  $(document).ready(function () {
    // Initialize any admin-specific JavaScript here
    console.log("Athena AI admin initialized");

    // Initialize Flowbite
    if (typeof window.initFlowbite === "function") {
      window.initFlowbite();
    }

    // Feed Dropdown Handling
    const feedCheckboxes = document.querySelectorAll(
      'input[name="feed_ids[]"]'
    );

    if (feedCheckboxes.length > 0) {
      // Select All / Clear All buttons
      const selectAllButton = document.getElementById("select-all-feeds");
      const clearAllButton = document.getElementById("clear-all-feeds");

      if (selectAllButton && clearAllButton) {
        selectAllButton.addEventListener("click", function (e) {
          e.preventDefault();
          feedCheckboxes.forEach((checkbox) => (checkbox.checked = true));
        });

        clearAllButton.addEventListener("click", function (e) {
          e.preventDefault();
          feedCheckboxes.forEach((checkbox) => (checkbox.checked = false));
        });
      }

      // Dropdown toggle button functionality
      const dropdownButton = document.getElementById(
        "feedFilterDropdownButton"
      );
      const dropdown = document.getElementById("feedFilterDropdown");

      if (dropdownButton && dropdown) {
        // Flowbite handles the dropdown toggle, but we add manual toggle in case needed
        dropdownButton.addEventListener("click", function () {
          dropdown.classList.toggle("hidden");
        });

        // Close dropdown when clicking outside
        document.addEventListener("click", function (event) {
          if (
            !dropdownButton.contains(event.target) &&
            !dropdown.contains(event.target)
          ) {
            dropdown.classList.add("hidden");
          }
        });
      }
    }
  });
})(jQuery);

// Import Flowbite if ESM is supported
document.addEventListener("DOMContentLoaded", () => {
  const script = document.createElement("script");
  script.src = "node_modules/flowbite/dist/flowbite.min.js";
  script.onload = function () {
    console.log("Flowbite loaded");
  };
  document.head.appendChild(script);
});
