/**
 * Profile Modals JavaScript
 * Handles modal interactions for the profile page
 */

jQuery(function ($) {
    console.log('Athena AI: Profile Modals JavaScript loaded');

    // Universal Modal Handler für alle AI-Buttons mit data-modal-target
    $(document).on('click', '[data-modal-target]', function (e) {
        e.preventDefault();
        var modalTarget = $(this).data('modal-target');
        var promptType = $(this).data('prompt-type');

        console.log(
            'Athena AI: Button clicked, Modal Target:',
            modalTarget,
            'Prompt Type:',
            promptType
        );

        if (modalTarget) {
            var $modal = $('#' + modalTarget);
            if ($modal.length) {
                console.log('Athena AI: Modal found, opening...');
                $modal.removeClass('hidden').addClass('flex');

                // Optional: Prompt-Typ für weitere Verarbeitung setzen
                if (promptType) {
                    $modal.attr('data-modal-type', promptType);
                }

                console.log('Athena AI: Modal geöffnet für Typ:', promptType);
            } else {
                console.warn('Athena AI: Modal nicht gefunden:', modalTarget);
                console.log(
                    'Athena AI: Verfügbare Modals:',
                    $('.fixed')
                        .map(function () {
                            return this.id;
                        })
                        .get()
                );
            }
        }
    });

    // Universal Close Handler für verschiedene Close-Button-Varianten
    $(document).on(
        'click',
        '.athena-ai-modal-close, [data-modal-close], .modal-close',
        function (e) {
            e.preventDefault();
            console.log('Athena AI: Close button clicked');

            // Finde das nächste Modal-Container
            var $modal = $(this).closest('.fixed');
            if ($modal.length) {
                console.log('Athena AI: Closing modal:', $modal.attr('id'));
                $modal.addClass('hidden').removeClass('flex');
            } else {
                // Fallback: Alle sichtbaren Modals schließen
                $('.fixed.flex').addClass('hidden').removeClass('flex');
                console.log('Athena AI: Fallback - alle Modals geschlossen');
            }
        }
    );

    // Close modals on backdrop click (Klick außerhalb des Modal-Inhalts)
    $(document).on('click', '.fixed.flex', function (e) {
        // Nur schließen wenn direkt auf das Backdrop (fixed container) geklickt wurde
        if (e.target === this) {
            console.log('Athena AI: Backdrop click - closing modal');
            $(this).addClass('hidden').removeClass('flex');
        }
    });

    // Close modals on ESC key
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            var $openModals = $('.fixed.flex');
            if ($openModals.length > 0) {
                console.log('Athena AI: ESC key - closing', $openModals.length, 'modals');
                $openModals.addClass('hidden').removeClass('flex');
            }
        }
    });

    // Debug: Log alle verfügbaren AI-Buttons und Modals
    $(document).ready(function () {
        setTimeout(function () {
            var $aiButtons = $('[data-modal-target]');
            var $modals = $('.fixed');

            console.log('=== ATHENA AI MODAL DEBUG ===');
            console.log('AI-Buttons gefunden:', $aiButtons.length);
            $aiButtons.each(function (index) {
                console.log(
                    '  Button',
                    index + 1,
                    '- Target:',
                    $(this).data('modal-target'),
                    'Type:',
                    $(this).data('prompt-type')
                );
            });

            console.log('Modals gefunden:', $modals.length);
            $modals.each(function (index) {
                console.log(
                    '  Modal',
                    index + 1,
                    '- ID:',
                    this.id,
                    'Type:',
                    $(this).data('modal-type')
                );
            });
            console.log('==============================');
        }, 500);
    });

    // Set flag to indicate external script loaded
    window.athenaAiModalsLoaded = true;
});
