<?php
/**
 * Fallback Scripts for AI Modals
 * 
 * @package AthenaAI
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<script>
// Fallback JavaScript - wird nur ausgeführt, wenn die externen Dateien nicht geladen werden
jQuery(document).ready(function($) {
    // Prüfen ob die externen Scripts geladen wurden
    if (typeof window.athenaAiModalsLoaded === 'undefined') {
        console.log('Athena AI: Loading fallback modal handlers');
        
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
    }
    
    // Prüfen ob AJAX Script geladen wurde, wenn nicht, Fallback laden
    if (typeof window.athenaAiAjaxLoaded === 'undefined') {
        console.log('Athena AI: Loading fallback AJAX handlers');
        
        // Company Description AJAX Handler
        $('#athena-ai-create-content').on('click', function () {
            var pageId = $('#athena-ai-page-select').val();
            var extraInfo = $('#athena-ai-modal-extra-info').val();
            var modelProvider = $('input[name="athena-ai-model-provider"]:checked').val();
            var debugField = $('#athena-ai-modal-debug');
            var testOnly = $('#athena-ai-test-only').is(':checked');

            if (!extraInfo.trim()) {
                alert('Bitte gib zusätzliche Informationen ein');
                return;
            }

            debugField.show().html(
                '<div class="p-3 text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><div class="mt-2">AI-Antwort wird generiert...</div></div>'
            );

            var promptIntro = $('#athena-ai-prompt-intro').val();
            var promptLimit = $('#athena-ai-prompt-limit').val();
            var fullPrompt = promptIntro + '\n\n' + extraInfo + '\n\n' + promptLimit;

            if (testOnly) {
                var debugInfo =
                    'Test-Modus aktiviert. Keine API-Anfrage gesendet.\n\n' +
                    'Ausgewählte Seite: ' + (pageId ? 'ID: ' + pageId : 'Keine') + '\n' +
                    'Zusätzliche Informationen: ' + extraInfo + '\n' +
                    'KI-Anbieter: ' + modelProvider + '\n\n' +
                    'Generierter Prompt:\n' + fullPrompt;

                debugField.html(
                    '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' +
                    '<strong>Debug-Informationen (Test-Modus):</strong><pre>' + debugInfo + '</pre></div>'
                );
                return;
            }

            // AJAX Request
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'athena_ai_modal_debug',
                page_id: pageId,
                extra_info: extraInfo,
                model_provider: modelProvider,
                custom_prompt: fullPrompt,
            }, function (response) {
                var parts = response.split('--- OPENAI ANTWORT ---');
                var debugInfo = parts[0];
                var aiResponse = parts.length > 1 ? parts[1] : '';

                var htmlOutput =
                    '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' +
                    '<strong>Debug-Informationen:</strong><pre>' + debugInfo + '</pre></div>';

                if (aiResponse) {
                    if (aiResponse.trim().startsWith('###')) {
                        var errorText = aiResponse.trim().substring(3).trim();
                        htmlOutput +=
                            '<div class="ai-response">' +
                            '<h3 class="text-xl font-bold mb-2 text-red-600">AI Fehler:</h3>' +
                            '<div class="bg-red-50 p-4 border border-red-300 rounded shadow-sm overflow-auto text-red-700" style="max-height: 400px;">' +
                            errorText.replace(/\n/g, '<br>') + '</div></div>';
                    } else {
                        htmlOutput +=
                            '<div class="ai-response">' +
                            '<h3 class="text-xl font-bold mb-2">AI Antwort (' + modelProvider + '):</h3>' +
                            '<div class="bg-white p-4 border border-gray-300 rounded shadow-sm overflow-auto" style="max-height: 400px;">' +
                            aiResponse.replace(/\n/g, '<br>') + '</div></div>';

                        $('#athena-ai-transfer-content')
                            .removeClass('opacity-50 cursor-not-allowed bg-gray-400')
                            .addClass('bg-green-600 hover:bg-green-700')
                            .prop('disabled', false);
                        window.athenaAiResponse = aiResponse;
                    }
                }
                debugField.html(htmlOutput);
            }).fail(function (xhr, textStatus, errorThrown) {
                debugField.html(
                    '<div class="p-3 bg-red-100 text-red-800 border border-red-300 rounded">' +
                    '<strong>Fehler:</strong> Die Anfrage konnte nicht verarbeitet werden. HTTP ' + xhr.status + '</div>'
                );
            });
        });

        // Transfer Content Handler
        $('#athena-ai-transfer-content').on('click', function () {
            if (window.athenaAiResponse) {
                var targetField = $('#athena-ai-target-field').val();
                if (targetField) {
                    var fieldSelector = 'textarea[name="athena_ai_profiles[' + targetField + ']"]';
                    var targetElement = $(fieldSelector);
                    if (targetElement.length) {
                        var cleanedResponse = window.athenaAiResponse.replace(/^\s+/, '').trim();
                        targetElement.val(cleanedResponse);
                        $('#athena-ai-modal').addClass('hidden').removeClass('flex');
                        alert('Der AI-generierte Inhalt wurde erfolgreich in das Feld übertragen.');
                    }
                }
            } else {
                alert('Kein AI-Content verfügbar. Bitte zuerst Content generieren.');
            }
        });

        // Products AJAX Handler
        $('#athena-ai-create-content-products').on('click', function () {
            var pageId = $('#athena-ai-page-select-products').val();
            var extraInfo = $('#athena-ai-modal-extra-info-products').val();
            var modelProvider = $('input[name="athena-ai-model-provider-products"]:checked').val();
            var debugField = $('#athena-ai-modal-debug-products');
            var testOnly = $('#athena-ai-test-only-products').is(':checked');

            if (!extraInfo.trim()) {
                alert('Bitte gib zusätzliche Informationen ein');
                return;
            }

            debugField.show().html(
                '<div class="p-3 text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><div class="mt-2">AI-Antwort wird generiert...</div></div>'
            );

            var promptIntro = $('#athena-ai-prompt-intro-products').val();
            var promptLimit = $('#athena-ai-prompt-limit-products').val();
            var fullPrompt = promptIntro + '\n\n' + extraInfo + '\n\n' + promptLimit;

            if (testOnly) {
                var debugInfo =
                    'Test-Modus aktiviert. Keine API-Anfrage gesendet.\n\n' +
                    'Ausgewählte Seite: ' + (pageId ? 'ID: ' + pageId : 'Keine') + '\n' +
                    'Zusätzliche Informationen: ' + extraInfo + '\n' +
                    'KI-Anbieter: ' + modelProvider + '\n\n' +
                    'Generierter Prompt:\n' + fullPrompt;

                debugField.html(
                    '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' +
                    '<strong>Debug-Informationen (Test-Modus):</strong><pre>' + debugInfo + '</pre></div>'
                );
                return;
            }

            // AJAX Request
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'athena_ai_modal_debug_products',
                page_id: pageId,
                extra_info: extraInfo,
                model_provider: modelProvider,
                custom_prompt: fullPrompt,
            }, function (response) {
                var parts = response.split('--- OPENAI ANTWORT ---');
                var debugInfo = parts[0];
                var aiResponse = parts.length > 1 ? parts[1] : '';

                var htmlOutput =
                    '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' +
                    '<strong>Debug-Informationen:</strong><pre>' + debugInfo + '</pre></div>';

                if (aiResponse) {
                    if (aiResponse.trim().startsWith('###')) {
                        var errorText = aiResponse.trim().substring(3).trim();
                        htmlOutput +=
                            '<div class="ai-response">' +
                            '<h3 class="text-xl font-bold mb-2 text-red-600">AI Fehler:</h3>' +
                            '<div class="bg-red-50 p-4 border border-red-300 rounded shadow-sm overflow-auto text-red-700" style="max-height: 400px;">' +
                            errorText.replace(/\n/g, '<br>') + '</div></div>';
                    } else {
                        htmlOutput +=
                            '<div class="ai-response">' +
                            '<h3 class="text-xl font-bold mb-2">AI Antwort (' + modelProvider + '):</h3>' +
                            '<div class="bg-white p-4 border border-gray-300 rounded shadow-sm overflow-auto" style="max-height: 400px;">' +
                            aiResponse.replace(/\n/g, '<br>') + '</div></div>';

                        $('#athena-ai-transfer-content-products')
                            .removeClass('opacity-50 cursor-not-allowed bg-gray-400')
                            .addClass('bg-green-600 hover:bg-green-700')
                            .prop('disabled', false);
                        window.athenaAiResponseProducts = aiResponse;
                    }
                }
                debugField.html(htmlOutput);
            }).fail(function (xhr, textStatus, errorThrown) {
                debugField.html(
                    '<div class="p-3 bg-red-100 text-red-800 border border-red-300 rounded">' +
                    '<strong>Fehler:</strong> Die Anfrage konnte nicht verarbeitet werden. HTTP ' + xhr.status + '</div>'
                );
            });
        });

        // Products Transfer Content Handler
        $('#athena-ai-transfer-content-products').on('click', function () {
            if (window.athenaAiResponseProducts) {
                var targetField = $('#athena-ai-target-field-products').val();
                if (targetField) {
                    var fieldSelector = 'textarea[name="athena_ai_profiles[' + targetField + ']"]';
                    var targetElement = $(fieldSelector);
                    if (targetElement.length) {
                        var cleanedResponse = window.athenaAiResponseProducts.replace(/^\s+/, '').trim();
                        targetElement.val(cleanedResponse);
                        $('#athena-ai-products-modal').addClass('hidden').removeClass('flex');
                        alert('Der AI-generierte Inhalt wurde erfolgreich in das Produktfeld übertragen.');
                    }
                }
            } else {
                alert('Kein AI-Content verfügbar. Bitte zuerst Content generieren.');
            }
        });
    }
});
</script> 