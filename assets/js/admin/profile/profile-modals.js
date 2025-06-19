/**
 * Profile Modals JavaScript
 * Handles modal interactions for the profile page
 */

jQuery(function ($) {
    // Universal Modal Handler für alle AI-Buttons mit data-modal-target
    $(document).on('click', '[data-modal-target]', function (e) {
        e.preventDefault();
        var modalTarget = $(this).data('modal-target');
        var promptType = $(this).data('prompt-type');

        if (modalTarget) {
            var $modal = $('#' + modalTarget);
            if ($modal.length) {
                $modal.removeClass('hidden').addClass('flex');

                // Optional: Prompt-Typ für weitere Verarbeitung setzen
                if (promptType) {
                    $modal.attr('data-modal-type', promptType);
                }

                console.log('Athena AI: Modal geöffnet für Typ:', promptType);
            } else {
                console.warn('Athena AI: Modal nicht gefunden:', modalTarget);
            }
        }
    });

    // Universal Close Handler für alle Modals
    $(document).on('click', '[data-modal-close], .modal-close', function (e) {
        e.preventDefault();
        var $modal = $(this).closest('.fixed'); // Annahme: Modals haben .fixed Klasse
        if ($modal.length) {
            $modal.addClass('hidden').removeClass('flex');
        }
    });

    // Close modals on backdrop click (Klick außerhalb des Modal-Inhalts)
    $(document).on('click', '.fixed.flex', function (e) {
        if (e.target === this) {
            $(this).addClass('hidden').removeClass('flex');
        }
    });

    // Close modals on ESC key
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('.fixed.flex').addClass('hidden').removeClass('flex');
        }
    });

    // Set flag to indicate external script loaded
    window.athenaAiModalsLoaded = true;

    console.log('Athena AI: Profile Modals JavaScript loaded');
});
