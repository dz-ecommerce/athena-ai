(function ($) {
  "use strict";

  $(document).ready(function () {
    // Initialize any admin-specific JavaScript here
    console.log("Athena AI admin initialized");

    // Initialize Flowbite
    if (typeof window.initFlowbite === "function") {
      window.initFlowbite();
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
