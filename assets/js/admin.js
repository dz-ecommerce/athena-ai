(function ($) {
  "use strict";

  $(document).ready(function () {
    // Initialize any admin-specific JavaScript here
    console.log("Athena AI admin initialized");

    // Initialize Flowbite
    if (typeof window.initFlowbite === "function") {
      window.initFlowbite();
    }

    // Feed Checkbox Handling
    const feedCheckboxes = document.querySelectorAll(
      'input[name="feed_ids[]"]'
    );
    if (feedCheckboxes.length > 0) {
      // "Select All" / "Clear All" Funktionalität
      const feedFilterContainer = document.querySelector(
        ".grid.grid-cols-1.sm\\:grid-cols-2"
      );
      if (feedFilterContainer) {
        const selectAllDiv = document.createElement("div");
        selectAllDiv.className =
          "flex items-center justify-between w-full col-span-full mb-2 pb-2 border-b border-gray-200";
        selectAllDiv.innerHTML = `
          <div class="text-sm font-medium text-gray-700">${feedCheckboxes.length} Feeds</div>
          <div class="space-x-2">
            <button type="button" id="select-all-feeds" class="text-sm text-blue-600 hover:text-blue-800">Select All</button>
            <span class="text-gray-300">|</span>
            <button type="button" id="clear-all-feeds" class="text-sm text-blue-600 hover:text-blue-800">Clear All</button>
          </div>
        `;
        feedFilterContainer.prepend(selectAllDiv);

        // Event Listeners
        document
          .getElementById("select-all-feeds")
          .addEventListener("click", function () {
            feedCheckboxes.forEach((checkbox) => (checkbox.checked = true));
          });

        document
          .getElementById("clear-all-feeds")
          .addEventListener("click", function () {
            feedCheckboxes.forEach((checkbox) => (checkbox.checked = false));
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
