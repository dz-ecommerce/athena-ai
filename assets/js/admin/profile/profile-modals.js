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

    // Universal Content erstellen Handler mit Full Assistant Support
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

        // Spezielle Behandlung für Full Assistant
        if (modalType === 'full_assistant') {
            executeFullAssistant($modal, {
                extraInfo: extraInfo,
                modelProvider: modelProvider,
                testOnly: testOnly,
                pageId: pageId,
            });
            return;
        }

        // Standard Single-Modal Logik
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

    // Universal Content übertragen Handler mit Full Assistant Support
    $(document).on('click', '.athena-ai-transfer-content', function (e) {
        e.preventDefault();
        console.log('Athena AI: Content übertragen button clicked');

        var $modal = $(this).closest('.fixed');
        var modalType = $modal.data('modal-type');

        // Spezielle Behandlung für Full Assistant
        if (modalType === 'full_assistant') {
            transferAllFullAssistantContent($modal);
            return;
        }

        // Standard Single-Modal Transfer Logik
        var responseVar =
            'athenaAiResponse' + modalType.charAt(0).toUpperCase() + modalType.slice(1);

        console.log('Athena AI: Transfer - Modal Type:', modalType, 'Response Var:', responseVar);

        if (window[responseVar]) {
            // Einfache Zuordnung von Modal-Typ zu Feld-Namen
            var fieldMappings = {
                company_description: 'company_description',
                products: 'company_products',
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

    /**
     * Full Assistant Execution Logic
     */
    function executeFullAssistant($modal, options) {
        console.log('Athena AI: Starting Full Assistant execution', options);

        // UI Elemente
        var $progressContainer = $modal.find('.athena-ai-progress-container');
        var $progressBar = $modal.find('.athena-ai-progress-bar');
        var $progressText = $modal.find('.athena-ai-progress-text');
        var $progressCount = $modal.find('.athena-ai-progress-count');
        var $createBtn = $modal.find('.athena-ai-create-content');
        var $transferBtn = $modal.find('.athena-ai-transfer-content');
        var $debugField = $modal.find('.athena-ai-modal-debug');

        // Progress anzeigen
        $progressContainer.removeClass('hidden');
        $createBtn.prop('disabled', true).addClass('opacity-50');

        // Set default values for empty fields
        setDefaultFormValues();

        // Prompt-Sequenz definieren
        var promptSequence = [
            { type: 'company_description', name: 'Unternehmensbeschreibung' },
            { type: 'products', name: 'Produkte & Dienstleistungen' },
            { type: 'company_usps', name: 'Alleinstellungsmerkmale' },
            { type: 'target_audience', name: 'Zielgruppe' },
            { type: 'expertise_areas', name: 'Expertise-Bereiche' },
            { type: 'seo_keywords', name: 'SEO-Keywords' },
        ];

        // Debug-Bereich vorbereiten
        $debugField.show().html('<h4 class="font-semibold mb-2">Full Assistant Ausführung</h4>');

        // Speichere generierte Inhalte
        window.athenaAiFullAssistantResults = {};

        // Starte Sequenz
        executePromptSequence(promptSequence, 0, options, $modal, function (success, results) {
            console.log('Athena AI: Full Assistant completed', success, results);

            // Progress auf 100%
            updateProgress($modal, 6, 6, 'Abgeschlossen!');

            // Buttons zurücksetzen
            $createBtn.prop('disabled', false).removeClass('opacity-50');

            if (success) {
                // Transfer Button aktivieren
                $transferBtn
                    .removeClass('opacity-50 cursor-not-allowed bg-gray-400')
                    .addClass('bg-green-600 hover:bg-green-700')
                    .prop('disabled', false);

                // Erfolgs-Nachricht anzeigen
                var successHtml =
                    '<div class="bg-green-50 border border-green-200 rounded p-3 mt-4">' +
                    '<i class="fas fa-check-circle text-green-500 mr-2"></i>' +
                    '<strong class="text-green-800">Alle Inhalte erfolgreich generiert!</strong><br>' +
                    '<span class="text-green-700 text-sm">Klicken Sie auf "Alle Inhalte übertragen" um die Felder zu füllen.</span>' +
                    '</div>';
                $debugField.append(successHtml);
            } else {
                $debugField.append(
                    '<div class="bg-red-50 border border-red-200 rounded p-3 mt-4 text-red-800">' +
                        '<i class="fas fa-exclamation-circle mr-2"></i>Einige Inhalte konnten nicht generiert werden.</div>'
                );
            }
        });
    }

    /**
     * Execute Prompt Sequence for Full Assistant
     */
    function executePromptSequence(prompts, index, options, $modal, callback) {
        if (index >= prompts.length) {
            callback(true, window.athenaAiFullAssistantResults);
            return;
        }

        var currentPrompt = prompts[index];
        var progressNum = index + 1;

        // Update Progress
        updateProgress(
            $modal,
            progressNum,
            prompts.length,
            'Generiere ' + currentPrompt.name + '...'
        );

        // Prüfe ob Feld bereits gefüllt ist
        var targetField = getTargetFieldForPrompt(currentPrompt.type);
        if (targetField && targetField.value.trim() && !options.overwriteExisting) {
            console.log('Athena AI: Skipping', currentPrompt.type, '- field already has content');

            // Als bereits vorhanden markieren
            window.athenaAiFullAssistantResults[currentPrompt.type] = {
                content: targetField.value,
                skipped: true,
            };

            // Nächster Prompt
            setTimeout(() => {
                executePromptSequence(prompts, index + 1, options, $modal, callback);
            }, 500);
            return;
        }

        // Generate content for this prompt
        generateSinglePromptContent(currentPrompt, options, function (success, content) {
            if (success && content) {
                window.athenaAiFullAssistantResults[currentPrompt.type] = {
                    content: content,
                    generated: true,
                };

                // Debug-Output hinzufügen
                var debugEntry =
                    '<div class="bg-blue-50 border border-blue-200 rounded p-2 mb-2 text-sm">' +
                    '<strong>' +
                    currentPrompt.name +
                    ':</strong> <span class="text-green-600">✓ Generiert</span>' +
                    '</div>';
                $modal.find('.athena-ai-modal-debug').append(debugEntry);
            } else {
                console.error('Athena AI: Failed to generate', currentPrompt.type);

                // Fallback-Content verwenden
                var fallbackContent = getDemoContent(currentPrompt.type);
                window.athenaAiFullAssistantResults[currentPrompt.type] = {
                    content: fallbackContent,
                    fallback: true,
                };

                var debugEntry =
                    '<div class="bg-yellow-50 border border-yellow-200 rounded p-2 mb-2 text-sm">' +
                    '<strong>' +
                    currentPrompt.name +
                    ':</strong> <span class="text-yellow-600">⚠ Fallback verwendet</span>' +
                    '</div>';
                $modal.find('.athena-ai-modal-debug').append(debugEntry);
            }

            // Kurze Pause und weiter zum nächsten Prompt
            setTimeout(() => {
                executePromptSequence(prompts, index + 1, options, $modal, callback);
            }, 1000);
        });
    }

    /**
     * Generate content for a single prompt
     */
    function generateSinglePromptContent(promptInfo, options, callback) {
        if (options.testOnly) {
            // Test-Modus: Sofort Demo-Content zurückgeben
            var testContent = getDemoContent(promptInfo.type);
            setTimeout(() => callback(true, testContent), 500);
            return;
        }

        // Echte AI-Anfrage
        $.post(ajaxurl || '/wp-admin/admin-ajax.php', {
            action: 'athena_ai_prompt',
            modal_type: promptInfo.type,
            page_id: options.pageId,
            extra_info: options.extraInfo,
            model_provider: options.modelProvider,
            nonce: athenaAiAdmin?.nonce || '',
        })
            .done(function (response) {
                if (response && response.success && response.data) {
                    var content = response.data.content || response.data;
                    callback(true, content);
                } else {
                    console.error('Athena AI API Error:', response);
                    callback(false, null);
                }
            })
            .fail(function (xhr, status, error) {
                console.error('Athena AI AJAX Error:', error);
                callback(false, null);
            });
    }

    /**
     * Transfer all Full Assistant generated content
     */
    function transferAllFullAssistantContent($modal) {
        if (!window.athenaAiFullAssistantResults) {
            alert('Keine generierten Inhalte verfügbar. Bitte zuerst Inhalte generieren.');
            return;
        }

        var transferCount = 0;
        var totalCount = Object.keys(window.athenaAiFullAssistantResults).length;

        // Alle Inhalte übertragen
        Object.keys(window.athenaAiFullAssistantResults).forEach(function (promptType) {
            var result = window.athenaAiFullAssistantResults[promptType];
            var targetField = getTargetFieldForPrompt(promptType);

            if (targetField && result.content) {
                targetField.value = result.content.trim();
                // Trigger events for floating labels
                $(targetField).trigger('input').trigger('blur');
                transferCount++;
            }
        });

        // Modal schließen und Erfolg anzeigen
        $modal.addClass('hidden').removeClass('flex');

        // Erfolgs-Benachrichtigung
        showTempNotification(
            `${transferCount} von ${totalCount} Inhalten erfolgreich übertragen!`,
            'success'
        );

        // Cleanup
        delete window.athenaAiFullAssistantResults;
    }

    /**
     * Helper Functions
     */
    function updateProgress($modal, current, total, text) {
        var percentage = Math.round((current / total) * 100);
        $modal.find('.athena-ai-progress-bar').css('width', percentage + '%');
        $modal.find('.athena-ai-progress-text').text(text);
        $modal.find('.athena-ai-progress-count').text(current + '/' + total);
    }

    function getTargetFieldForPrompt(promptType) {
        var fieldMappings = {
            company_description: 'company_description',
            products: 'company_products',
            company_usps: 'company_usps',
            target_audience: 'target_audience',
            expertise_areas: 'expertise_areas',
            seo_keywords: 'seo_keywords',
        };

        var fieldName = fieldMappings[promptType];
        if (!fieldName) return null;

        return document.getElementById(fieldName);
    }

    function setDefaultFormValues() {
        // Set company name if empty
        var companyNameField = document.getElementById('company_name');
        if (companyNameField && !companyNameField.value.trim()) {
            companyNameField.value = 'Muster GmbH';
            $(companyNameField).trigger('input').trigger('blur');
        }

        // Set industry
        var industryField = document.getElementById('company_industry');
        if (industryField && !industryField.value) {
            industryField.value = 'it_services';
            $(industryField).trigger('change');
        }
    }

    function getDemoContent(promptType) {
        var demoContents = {
            company_description:
                'Wir sind ein innovatives IT-Unternehmen, das sich auf maßgeschneiderte Softwarelösungen und digitale Transformation spezialisiert hat. Mit über 10 Jahren Erfahrung unterstützen wir Unternehmen dabei, ihre Geschäftsprozesse zu optimieren und erfolgreich in der digitalen Welt zu agieren.',
            products:
                'Webentwicklung, Mobile Apps, Cloud-Lösungen, E-Commerce Plattformen, CRM-Systeme, Datenanalyse-Tools',
            company_usps:
                'Agile Entwicklungsmethoden, 24/7 Support, Kostenlose Beratung, Langjährige Erfahrung, Individuelle Lösungen',
            target_audience:
                'Mittelständische Unternehmen aus verschiedenen Branchen mit berufstätigen Entscheidern im Alter von 25-54 Jahren, die ihre digitalen Prozesse modernisieren möchten. Unsere Kunden schätzen persönliche Betreuung und nachhaltige Lösungen.',
            expertise_areas:
                'PHP/Laravel Development\nReact/Vue.js Frontend\nAWS Cloud Architecture\nDatabase Design\nAPI Integration\nSEO Optimierung',
            seo_keywords:
                'Webentwicklung\nSoftware Entwicklung\nDigitale Transformation\nIT Beratung\nCloud Lösungen',
        };

        return demoContents[promptType] || `Generierter Inhalt für ${promptType}`;
    }

    function showTempNotification(message, type = 'success') {
        var notification = $('<div></div>');
        var bgColor =
            type === 'success'
                ? 'bg-green-500'
                : type === 'info'
                  ? 'bg-blue-500'
                  : type === 'error'
                    ? 'bg-red-500'
                    : 'bg-yellow-500';
        var icon =
            type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info';

        notification.addClass(
            `fixed top-4 right-4 ${bgColor} text-white px-4 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`
        );
        notification.html(`
            <div class="flex items-center space-x-2">
                <i class="fas fa-${icon}-circle"></i>
                <span class="text-sm">${message}</span>
            </div>
        `);

        $('body').append(notification);

        // Show notification
        setTimeout(() => {
            notification.removeClass('translate-x-full');
        }, 100);

        // Hide after 4 seconds
        setTimeout(() => {
            notification.addClass('translate-x-full');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 4000);
    }

    // Debug: Log alle verfügbaren AI-Buttons und Modals
    $(document).ready(function () {
        setTimeout(function () {
            var $aiButtons = $('[data-modal-target]');
            var $modals = $('.fixed');
            var $createButtons = $('.athena-ai-create-content');
            var $transferButtons = $('.athena-ai-transfer-content');

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

            console.log('Content erstellen Buttons gefunden:', $createButtons.length);
            console.log('Content übertragen Buttons gefunden:', $transferButtons.length);
            console.log('==============================');
        }, 1000);
    });
});
