/**
 * Profile AJAX JavaScript
 * Handles AJAX requests for AI content generation
 */

jQuery(function ($) {
    // Company Description AJAX Handler
    $('#athena-ai-create-content').on('click', function () {
        var pageId = $('#athena-ai-page-select').val();
        var extraInfo = $('#athena-ai-modal-extra-info').val();
        var modelProvider = $('input[name="athena-ai-model-provider"]:checked').val();
        var debugField = $('#athena-ai-modal-debug');

        if (!extraInfo.trim()) {
            alert('Bitte gib zusätzliche Informationen ein');
            return;
        }

        debugField
            .html(
                '<div class="p-3 text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><div class="mt-2">AI-Antwort wird generiert...</div></div>'
            )
            .show();

        var promptIntro = $('#athena-ai-prompt-intro').val();
        var promptLimit = $('#athena-ai-prompt-limit').val();
        var fullPrompt = promptIntro + '\n\n' + extraInfo + '\n\n' + promptLimit;

        var testOnly = $('#athena-ai-test-only').is(':checked');

        if (testOnly) {
            var debugInfo =
                'Test-Modus aktiviert. Keine API-Anfrage gesendet.\n\n' +
                'Ausgewählte Seite: ' +
                (pageId ? 'ID: ' + pageId : 'Keine') +
                '\n' +
                'Zusätzliche Informationen: ' +
                extraInfo +
                '\n' +
                'KI-Anbieter: ' +
                modelProvider +
                '\n\n' +
                'Generierter Prompt:\n' +
                fullPrompt;

            var htmlOutput =
                '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' +
                '<strong>Debug-Informationen (Test-Modus):</strong><pre>' +
                debugInfo +
                '</pre></div>';

            debugField.html(htmlOutput);
            return;
        }

        $.post(
            ajaxurl,
            {
                action: 'athena_ai_modal_debug',
                page_id: pageId,
                extra_info: extraInfo,
                model_provider: modelProvider,
                custom_prompt: fullPrompt,
            },
            function (response) {
                var parts = response.split('--- OPENAI ANTWORT ---');
                var debugInfo = parts[0];
                var aiResponse = parts.length > 1 ? parts[1] : '';

                var htmlOutput =
                    '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' +
                    '<strong>Debug-Informationen:</strong><pre>' +
                    debugInfo +
                    '</pre></div>';

                if (aiResponse) {
                    if (aiResponse.trim().startsWith('###')) {
                        var errorText = aiResponse.trim().substring(3).trim();

                        htmlOutput +=
                            '<div class="ai-response">' +
                            '<h3 class="text-xl font-bold mb-2 text-red-600">OpenAI Fehler:</h3>' +
                            '<div class="bg-red-50 p-4 border border-red-300 rounded shadow-sm overflow-auto text-red-700" style="max-height: 400px;">' +
                            errorText.replace(/\n/g, '<br>') +
                            '</div></div>';
                    } else {
                        htmlOutput +=
                            '<div class="ai-response">' +
                            '<h3 class="text-xl font-bold mb-2">OpenAI Antwort:</h3>' +
                            '<div class="bg-white p-4 border border-gray-300 rounded shadow-sm overflow-auto" style="max-height: 400px;">' +
                            aiResponse.replace(/\n/g, '<br>') +
                            '</div></div>';

                        $('#athena-ai-transfer-content')
                            .removeClass('opacity-50 cursor-not-allowed')
                            .addClass('bg-green-600 hover:bg-green-700')
                            .prop('disabled', false);
                        window.athenaAiResponse = aiResponse;
                    }
                }

                debugField.html(htmlOutput);
            }
        ).fail(function (xhr, textStatus, errorThrown) {
            debugField.html(
                '<div class="p-3 bg-red-100 text-red-800 border border-red-300 rounded">' +
                    '<strong>Fehler:</strong> Die Anfrage konnte nicht verarbeitet werden. ' +
                    textStatus +
                    ' ' +
                    errorThrown +
                    '</div>'
            );
        });
    });

    // Transfer Content Handler
    $('#athena-ai-transfer-content').on('click', function () {
        if (window.athenaAiResponse) {
            var targetField = $('#athena-ai-target-field').val();

            if (targetField) {
                var fieldSelector = 'textarea[name="athena_ai_profiles[' + targetField + ']"]';
                var cleanedResponse = window.athenaAiResponse.replace(/^\s+/, '').trim();
                $(fieldSelector).val(cleanedResponse);

                $('#athena-ai-modal').addClass('hidden').removeClass('flex');
                alert('Der Inhalt wurde erfolgreich in das Feld "' + targetField + '" übertragen.');
            }
        }
    });

    // Products AJAX Handler
    $('#athena-ai-create-content-products').on('click', function () {
        var pageId = $('#athena-ai-page-select-products').val();
        var extraInfo = $('#athena-ai-modal-extra-info-products').val();
        var modelProvider = $('input[name="athena-ai-model-provider-products"]:checked').val();
        var debugField = $('#athena-ai-modal-debug-products');

        if (!extraInfo.trim()) {
            alert('Bitte gib zusätzliche Informationen ein');
            return;
        }

        debugField
            .html(
                '<div class="p-3 text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><div class="mt-2">AI-Antwort wird generiert...</div></div>'
            )
            .show();

        var promptIntro = $('#athena-ai-prompt-intro-products').val();
        var promptLimit = $('#athena-ai-prompt-limit-products').val();
        var fullPrompt = promptIntro + '\n\n' + extraInfo + '\n\n' + promptLimit;

        var testOnly = $('#athena-ai-test-only-products').is(':checked');

        if (testOnly) {
            var debugInfo =
                'Test-Modus aktiviert. Keine API-Anfrage gesendet.\n\n' +
                'Ausgewählte Seite: ' +
                (pageId ? 'ID: ' + pageId : 'Keine') +
                '\n' +
                'Zusätzliche Informationen: ' +
                extraInfo +
                '\n' +
                'KI-Anbieter: ' +
                modelProvider +
                '\n\n' +
                'Generierter Prompt:\n' +
                fullPrompt;

            var htmlOutput =
                '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' +
                '<strong>Debug-Informationen (Test-Modus):</strong><pre>' +
                debugInfo +
                '</pre></div>';

            debugField.html(htmlOutput);
            return;
        }

        $.post(
            ajaxurl,
            {
                action: 'athena_ai_modal_debug_products',
                page_id: pageId,
                extra_info: extraInfo,
                model_provider: modelProvider,
                custom_prompt: fullPrompt,
            },
            function (response) {
                var parts = response.split('--- OPENAI ANTWORT ---');
                var debugInfo = parts[0];
                var aiResponse = parts.length > 1 ? parts[1] : '';

                var htmlOutput =
                    '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' +
                    '<strong>Debug-Informationen:</strong><pre>' +
                    debugInfo +
                    '</pre></div>';

                if (aiResponse) {
                    if (aiResponse.trim().startsWith('###')) {
                        var errorText = aiResponse.trim().substring(3).trim();

                        htmlOutput +=
                            '<div class="ai-response">' +
                            '<h3 class="text-xl font-bold mb-2 text-red-600">OpenAI Fehler:</h3>' +
                            '<div class="bg-red-50 p-4 border border-red-300 rounded shadow-sm overflow-auto text-red-700" style="max-height: 400px;">' +
                            errorText.replace(/\n/g, '<br>') +
                            '</div></div>';
                    } else {
                        htmlOutput +=
                            '<div class="ai-response">' +
                            '<h3 class="text-xl font-bold mb-2">OpenAI Antwort:</h3>' +
                            '<div class="bg-white p-4 border border-gray-300 rounded shadow-sm overflow-auto" style="max-height: 400px;">' +
                            aiResponse.replace(/\n/g, '<br>') +
                            '</div></div>';

                        $('#athena-ai-transfer-content-products')
                            .removeClass('opacity-50 cursor-not-allowed')
                            .addClass('bg-green-600 hover:bg-green-700')
                            .prop('disabled', false);
                        window.athenaAiResponseProducts = aiResponse;
                    }
                }

                debugField.html(htmlOutput);
            }
        ).fail(function (xhr, textStatus, errorThrown) {
            debugField.html(
                '<div class="p-3 bg-red-100 text-red-800 border border-red-300 rounded">' +
                    '<strong>Fehler:</strong> Die Anfrage konnte nicht verarbeitet werden. ' +
                    textStatus +
                    ' ' +
                    errorThrown +
                    '</div>'
            );
        });
    });

    // Transfer Products Content Handler
    $('#athena-ai-transfer-content-products').on('click', function () {
        if (window.athenaAiResponseProducts) {
            var targetField = $('#athena-ai-target-field-products').val();

            if (targetField) {
                var fieldSelector = 'textarea[name="athena_ai_profiles[' + targetField + ']"]';
                var cleanedResponse = window.athenaAiResponseProducts.replace(/^\s+/, '').trim();
                $(fieldSelector).val(cleanedResponse);

                $('#athena-ai-products-modal').addClass('hidden').removeClass('flex');
                alert('Der Inhalt wurde erfolgreich in das Feld "' + targetField + '" übertragen.');
            }
        }
    });
});
