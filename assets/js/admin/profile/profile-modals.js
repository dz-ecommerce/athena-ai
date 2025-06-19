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

    // Universal Content erstellen Handler
    $(document).on('click', '.athena-ai-create-content', function (e) {
        e.preventDefault();
        console.log('Athena AI: Content erstellen button clicked');

        var $modal = $(this).closest('.fixed');
        var modalType = $modal.data('modal-type');
        var pageId = $modal.find('.athena-ai-page-select').val();
        var extraInfo = $modal.find('.athena-ai-modal-extra-info').val();
        var modelProvider = $modal
            .find('input[name="athena-ai-model-provider-' + modalType + '"]:checked')
            .val();
        var debugField = $modal.find('.athena-ai-modal-debug');
        var testOnly = $modal.find('.athena-ai-test-only').is(':checked');

        console.log(
            'Athena AI: Content erstellen - Modal Type:',
            modalType,
            'Extra Info:',
            extraInfo
        );

        if (!extraInfo.trim()) {
            alert('Bitte gib zusätzliche Informationen ein');
            return;
        }

        debugField
            .show()
            .html(
                '<div class="p-3 text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><div class="mt-2">AI-Antwort wird generiert...</div></div>'
            );

        // Für Test-Modus: Einfachen Debug-Output anzeigen
        if (testOnly) {
            var debugInfo =
                'Test-Modus aktiviert. Keine API-Anfrage gesendet.\n\n' +
                'Modal-Typ: ' +
                modalType +
                '\n' +
                'Ausgewählte Seite: ' +
                (pageId ? 'ID: ' + pageId : 'Keine') +
                '\n' +
                'Zusätzliche Informationen: ' +
                extraInfo +
                '\n' +
                'KI-Anbieter: ' +
                modelProvider;

            debugField.html(
                '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' +
                    '<strong>Debug-Informationen (Test-Modus):</strong><pre>' +
                    debugInfo +
                    '</pre></div>'
            );

            // Simuliere eine AI-Antwort für Test
            var testResponse =
                'Das ist eine Test-Antwort für den Modal-Typ: ' + modalType + '. ' + extraInfo;
            window['athenaAiResponse' + modalType.charAt(0).toUpperCase() + modalType.slice(1)] =
                testResponse;

            // Transfer Button aktivieren
            $modal
                .find('.athena-ai-transfer-content')
                .removeClass('opacity-50 cursor-not-allowed')
                .prop('disabled', false);
            return;
        }

        // AJAX Request an WordPress
        $.post(ajaxurl || '/wp-admin/admin-ajax.php', {
            action: 'athena_ai_prompt',
            modal_type: modalType,
            page_id: pageId,
            extra_info: extraInfo,
            model_provider: modelProvider,
            nonce: athenaAiAdmin?.nonce || '',
        })
            .done(function (response) {
                console.log('Athena AI: AJAX Response:', response);

                var htmlOutput =
                    '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' +
                    '<strong>Debug-Informationen:</strong><pre>' +
                    JSON.stringify(
                        {
                            modalType: modalType,
                            pageId: pageId,
                            extraInfo: extraInfo,
                            modelProvider: modelProvider,
                        },
                        null,
                        2
                    ) +
                    '</pre></div>';

                if (response && response.success && response.data) {
                    var aiResponse = response.data.content || response.data;
                    htmlOutput +=
                        '<div class="ai-response bg-blue-50 p-3 mb-4 text-sm border border-blue-200 rounded">' +
                        '<strong>AI-Antwort:</strong><div class="mt-2">' +
                        aiResponse +
                        '</div></div>';

                    // Globale Variable für Transfer setzen
                    window[
                        'athenaAiResponse' + modalType.charAt(0).toUpperCase() + modalType.slice(1)
                    ] = aiResponse;

                    // Transfer Button aktivieren
                    $modal
                        .find('.athena-ai-transfer-content')
                        .removeClass('opacity-50 cursor-not-allowed')
                        .prop('disabled', false);
                } else {
                    htmlOutput +=
                        '<div class="text-red-600">Fehler: ' +
                        (response.data || 'Unbekannter Fehler') +
                        '</div>';
                }

                debugField.html(htmlOutput);
            })
            .fail(function (xhr, status, error) {
                console.error('Athena AI: AJAX Fehler:', status, error);
                debugField.html(
                    '<div class="text-red-600">Fehler bei der API-Anfrage: ' + error + '</div>'
                );
            });
    });

    // Universal Content übertragen Handler
    $(document).on('click', '.athena-ai-transfer-content', function (e) {
        e.preventDefault();
        console.log('Athena AI: Content übertragen button clicked');

        var $modal = $(this).closest('.fixed');
        var modalType = $modal.data('modal-type');
        var responseVar =
            'athenaAiResponse' + modalType.charAt(0).toUpperCase() + modalType.slice(1);

        console.log('Athena AI: Transfer - Modal Type:', modalType, 'Response Var:', responseVar);

        if (window[responseVar]) {
            // Einfache Zuordnung von Modal-Typ zu Feld-Namen
            var fieldMappings = {
                company_description: 'company_description',
                products: 'company_products',
                company_values: 'company_values',
                target_audience: 'target_audience',
                company_usps: 'company_usps',
                expertise_areas: 'expertise_areas',
                seo_keywords: 'seo_keywords',
            };

            var targetField = fieldMappings[modalType];

            if (targetField) {
                var fieldSelector =
                    'textarea[name="athena_ai_profiles[' +
                    targetField +
                    ']"], input[name="athena_ai_profiles[' +
                    targetField +
                    ']"]';
                var targetElement = $(fieldSelector);
                console.log(
                    'Athena AI: Searching for field:',
                    fieldSelector,
                    'Found:',
                    targetElement.length
                );

                if (targetElement.length) {
                    var cleanedResponse = window[responseVar].replace(/^\s+/, '').trim();
                    targetElement.val(cleanedResponse);
                    $modal.addClass('hidden').removeClass('flex');
                    alert('Der AI-generierte Inhalt wurde erfolgreich übertragen.');
                } else {
                    alert('Zielfeld nicht gefunden: ' + targetField);
                }
            } else {
                alert('Zielfeld-Konfiguration nicht verfügbar für Modal-Typ: ' + modalType);
            }
        } else {
            alert('Kein AI-Content verfügbar. Bitte zuerst Content generieren.');
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
