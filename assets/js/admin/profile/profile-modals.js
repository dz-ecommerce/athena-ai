/**
 * Profile Modals JavaScript
 * Handles modal interactions for the profile page
 */

jQuery(function ($) {
    // Company Description Modal
    $('#athena-ai-assistant-btn').on('click', function () {
        $('#athena-ai-modal').removeClass('hidden').addClass('flex');
    });

    $('#athena-ai-modal-close').on('click', function () {
        $('#athena-ai-modal').addClass('hidden').removeClass('flex');
    });

    // Close modal on backdrop click
    $('#athena-ai-modal').on('click', function (e) {
        if (e.target === this) {
            $(this).addClass('hidden').removeClass('flex');
        }
    });

    // Products Modal
    $('#athena-ai-products-assistant-btn').on('click', function () {
        $('#athena-ai-products-modal').removeClass('hidden').addClass('flex');
    });

    $('#athena-ai-products-modal-close').on('click', function () {
        $('#athena-ai-products-modal').addClass('hidden').removeClass('flex');
    });

    // Close products modal on backdrop click
    $('#athena-ai-products-modal').on('click', function (e) {
        if (e.target === this) {
            $(this).addClass('hidden').removeClass('flex');
        }
    });

    // Set flag to indicate external script loaded
    window.athenaAiModalsLoaded = true;
});
