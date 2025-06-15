<?php
/**
 * Universal AI Modal Template
 * 
 * Universelles Modal für alle AI-Prompts basierend auf YAML-Konfiguration
 * 
 * @package AthenaAI
 * @since 2.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get pages for the dropdown
$pages = get_pages(['sort_column' => 'post_title', 'sort_order' => 'asc']);

// Modal-Konfiguration (wird via JavaScript gesetzt)
$modal_id = $args['modal_id'] ?? 'athena-ai-universal-modal';
$modal_type = $args['modal_type'] ?? 'company_description';
$modal_title = $args['modal_title'] ?? __('Athena AI Assistent', 'athena-ai');
?>

<!-- Universal AI Modal -->
<div id="<?php echo esc_attr($modal_id); ?>" class="fixed z-50 inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden overflow-y-auto overflow-x-hidden" data-modal-type="<?php echo esc_attr($modal_type); ?>">
    <div class="bg-white rounded-lg shadow-lg w-[50vw] p-6 relative max-h-[80vh] overflow-y-auto overflow-x-hidden">
        <button type="button" class="athena-ai-modal-close absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl font-bold">&times;</button>
        <h2 class="text-lg font-semibold mb-4"><?php echo esc_html($modal_title); ?></h2>
        
        <!-- KI-Anbieter auswählen -->
        <div class="mb-4">
            <label class="block mb-2 font-medium"><?php esc_html_e('KI-Anbieter auswählen', 'athena-ai'); ?></label>
            <div class="flex space-x-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="athena-ai-model-provider-<?php echo esc_attr($modal_type); ?>" value="openai" class="form-radio h-4 w-4 text-blue-600" checked>
                    <span class="ml-2">OpenAI</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="athena-ai-model-provider-<?php echo esc_attr($modal_type); ?>" value="gemini" class="form-radio h-4 w-4 text-blue-600">
                    <span class="ml-2">Google Gemini</span>
                </label>
            </div>
            <div class="mt-3">
                <label class="inline-flex items-center">
                    <input type="checkbox" class="athena-ai-test-only form-checkbox h-4 w-4 text-blue-600">
                    <span class="ml-2"><?php esc_html_e('Nur Output testen', 'athena-ai'); ?></span>
                </label>
            </div>
        </div>
        
        <!-- Prompt-Konfiguration wird über YAML und PromptManager geladen -->
        
        <!-- Seiten-Auswahl -->
        <select class="athena-ai-page-select block w-full border border-gray-300 rounded px-3 py-2 mb-4 flex-grow max-w-full box-border">
            <option value=""><?php esc_html_e('-- Seite wählen (optional) --', 'athena-ai'); ?></option>
            <?php foreach ($pages as $page): ?>
                <option value="<?php echo esc_attr($page->ID); ?>"><?php echo esc_html($page->post_title); ?></option>
            <?php endforeach; ?>
        </select>
        
        <!-- Zusätzliche Informationen -->
        <textarea class="athena-ai-modal-extra-info block w-full border border-gray-300 rounded px-3 py-2 mb-4" rows="4" placeholder="<?php esc_attr_e('Zusätzliche Informationen hinterlegen', 'athena-ai'); ?>"></textarea>
        
        <!-- Action Buttons -->
        <div class="flex space-x-2">
            <button type="button" class="athena-ai-create-content bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow-sm w-1/2">
                <?php esc_html_e('Content erstellen', 'athena-ai'); ?>
            </button>
            <button type="button" class="athena-ai-transfer-content bg-gray-400 text-white font-semibold py-2 px-4 rounded shadow-sm w-1/2 opacity-50 cursor-not-allowed" disabled>
                <?php esc_html_e('Content übertragen', 'athena-ai'); ?>
            </button>
        </div>
        
        <!-- Debug Output -->
        <div class="athena-ai-modal-debug mt-4 p-3 bg-gray-100 border border-gray-300 rounded text-xs font-mono text-gray-700" style="display:none;"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var modalId = '<?php echo esc_js($modal_id); ?>';
    var modalType = '<?php echo esc_js($modal_type); ?>';
    var $modal = $('#' + modalId);
    
    // Modal öffnen/schließen
    $modal.find('.athena-ai-modal-close').on('click', function() {
        $modal.addClass('hidden').removeClass('flex');
    });
    
    // Modal schließen bei Backdrop-Click
    $modal.on('click', function(e) {
        if (e.target === this) {
            $(this).addClass('hidden').removeClass('flex');
        }
    });
    
    // Content erstellen
    $modal.find('.athena-ai-create-content').on('click', function() {
        var pageId = $modal.find('.athena-ai-page-select').val();
        var extraInfo = $modal.find('.athena-ai-modal-extra-info').val();
        var modelProvider = $modal.find('input[name="athena-ai-model-provider-' + modalType + '"]:checked').val();
        var debugField = $modal.find('.athena-ai-modal-debug');
        var testOnly = $modal.find('.athena-ai-test-only').is(':checked');
        
        if (!extraInfo.trim()) {
            alert('<?php esc_js_e('Bitte gib zusätzliche Informationen ein', 'athena-ai'); ?>');
            return;
        }
        
        debugField.show().html(
            '<div class="p-3 text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><div class="mt-2"><?php esc_js_e('AI-Antwort wird generiert...', 'athena-ai'); ?></div></div>'
        );
        
        // Prompt aus Prompt Manager abrufen
        var fullPrompt = '';
        if (window.athenaAiPromptManager && window.athenaAiPromptManager.loaded) {
            fullPrompt = window.athenaAiPromptManager.buildFullPrompt(modalType, extraInfo);
        } else {
            console.warn('Athena AI: Prompt Manager nicht verfügbar für Modal-Typ:', modalType);
            debugField.html('<div class="text-red-600">Fehler: Prompt-Konfiguration nicht verfügbar</div>');
            return;
        }
        
        if (testOnly) {
            var debugInfo = 
                '<?php esc_js_e('Test-Modus aktiviert. Keine API-Anfrage gesendet.', 'athena-ai'); ?>\n\n' +
                '<?php esc_js_e('Modal-Typ:', 'athena-ai'); ?> ' + modalType + '\n' +
                '<?php esc_js_e('Ausgewählte Seite:', 'athena-ai'); ?> ' + (pageId ? 'ID: ' + pageId : '<?php esc_js_e('Keine', 'athena-ai'); ?>') + '\n' +
                '<?php esc_js_e('Zusätzliche Informationen:', 'athena-ai'); ?> ' + extraInfo + '\n' +
                '<?php esc_js_e('KI-Anbieter:', 'athena-ai'); ?> ' + modelProvider + '\n\n' +
                '<?php esc_js_e('Generierter Prompt:', 'athena-ai'); ?>\n' + fullPrompt;
            
            debugField.html(
                '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' +
                '<strong><?php esc_js_e('Debug-Informationen (Test-Modus):', 'athena-ai'); ?></strong><pre>' + debugInfo + '</pre></div>'
            );
            return;
        }
        
        // AJAX Request
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'athena_ai_modal_debug_universal',
            modal_type: modalType,
            page_id: pageId,
            extra_info: extraInfo,
            model_provider: modelProvider,
            custom_prompt: fullPrompt,
            nonce: '<?php echo wp_create_nonce('athena_ai_nonce'); ?>'
        }, function(response) {
            var parts = response.split('--- OPENAI ANTWORT ---');
            var debugInfo = parts[0];
            var aiResponse = parts.length > 1 ? parts[1] : '';
            
            var htmlOutput = 
                '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' +
                '<strong><?php esc_js_e('Debug-Informationen:', 'athena-ai'); ?></strong><pre>' + debugInfo + '</pre></div>';
            
            if (aiResponse) {
                htmlOutput += 
                    '<div class="ai-response bg-blue-50 p-3 mb-4 text-sm border border-blue-200 rounded">' +
                    '<strong><?php esc_js_e('AI-Antwort:', 'athena-ai'); ?></strong><div class="mt-2">' + aiResponse + '</div></div>';
                
                // Globale Variable für Transfer setzen
                window['athenaAiResponse' + modalType.charAt(0).toUpperCase() + modalType.slice(1)] = aiResponse;
                
                // Transfer Button aktivieren
                $modal.find('.athena-ai-transfer-content').removeClass('opacity-50 cursor-not-allowed').prop('disabled', false);
            }
            
            debugField.html(htmlOutput);
        }).fail(function() {
            debugField.html('<div class="text-red-600"><?php esc_js_e('Fehler bei der API-Anfrage', 'athena-ai'); ?></div>');
        });
    });
    
    // Content übertragen
    $modal.find('.athena-ai-transfer-content').on('click', function() {
        var responseVar = 'athenaAiResponse' + modalType.charAt(0).toUpperCase() + modalType.slice(1);
        
        if (window[responseVar]) {
            // Zielfeld aus Prompt Manager abrufen
            var targetField = '';
            if (window.athenaAiPromptManager && window.athenaAiPromptManager.loaded) {
                targetField = window.athenaAiPromptManager.getTargetField(modalType);
            }
            
            if (targetField) {
                var fieldSelector = 'textarea[name="athena_ai_profiles[' + targetField + ']"], input[name="athena_ai_profiles[' + targetField + ']"]';
                var targetElement = $(fieldSelector);
                if (targetElement.length) {
                    var cleanedResponse = window[responseVar].replace(/^\s+/, '').trim();
                    targetElement.val(cleanedResponse);
                    $modal.addClass('hidden').removeClass('flex');
                    alert('<?php esc_js_e('Der AI-generierte Inhalt wurde erfolgreich übertragen.', 'athena-ai'); ?>');
                } else {
                    alert('<?php esc_js_e('Zielfeld nicht gefunden:', 'athena-ai'); ?> ' + targetField);
                }
            } else {
                alert('<?php esc_js_e('Zielfeld-Konfiguration nicht verfügbar.', 'athena-ai'); ?>');
            }
        } else {
            alert('<?php esc_js_e('Kein AI-Content verfügbar. Bitte zuerst Content generieren.', 'athena-ai'); ?>');
        }
    });
});
</script> 