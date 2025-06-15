<?php
/**
 * Products AI Modal Template
 * 
 * @package AthenaAI
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get pages for the dropdown
$pages = get_pages(['sort_column' => 'post_title', 'sort_order' => 'asc']);
?>

<!-- Produkte und Dienstleistungen Modal -->
<div id="athena-ai-products-modal" class="fixed z-50 inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden overflow-y-auto overflow-x-hidden">
    <div class="bg-white rounded-lg shadow-lg w-[50vw] p-6 relative max-h-[80vh] overflow-y-auto overflow-x-hidden">
        <button type="button" id="athena-ai-products-modal-close" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl font-bold">&times;</button>
        <h2 class="text-lg font-semibold mb-4">Athena AI Assistent für Produkte und Dienstleistungen</h2>
        
        <!-- KI-Anbieter auswählen -->
        <div class="mb-4">
            <label for="athena-ai-model-provider-products" class="block mb-2 font-medium">KI-Anbieter auswählen</label>
            <div class="flex space-x-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="athena-ai-model-provider-products" value="openai" class="form-radio h-4 w-4 text-blue-600" checked>
                    <span class="ml-2">OpenAI</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="athena-ai-model-provider-products" value="gemini" class="form-radio h-4 w-4 text-blue-600">
                    <span class="ml-2">Google Gemini</span>
                </label>
            </div>
            <div class="mt-3">
                <label class="inline-flex items-center">
                    <input type="checkbox" id="athena-ai-test-only-products" class="form-checkbox h-4 w-4 text-blue-600">
                    <span class="ml-2">Nur Output testen</span>
                </label>
            </div>
        </div>
        
        <!-- Hidden Input Fields für Prompt-Teile -->
        <input type="hidden" id="athena-ai-prompt-intro-products" value="Analysiere den bereitgestellten Text und identifiziere alle darin genannten Produkte und Dienstleistungen. Liste diese in einer klaren, durch Komma und Leerzeichen getrennten Aufzählung auf. Füge keine zusätzlichen Informationen hinzu und fasse die Begriffe präzise zusammen.">
        <input type="hidden" id="athena-ai-prompt-limit-products" value="Maximal 20 Proukte oder Dienstleistungen.">
        <input type="hidden" id="athena-ai-target-field-products" value="company_products">
        
        <select id="athena-ai-page-select-products" class="block w-full border border-gray-300 rounded px-3 py-2 mb-4 flex-grow max-w-full box-border">
            <option value="">-- Seite wählen (optional) --</option>
            <?php foreach ($pages as $page): ?>
                <option value="<?php echo esc_attr($page->ID); ?>"><?php echo esc_html($page->post_title); ?></option>
            <?php endforeach; ?>
        </select>
        <textarea id="athena-ai-modal-extra-info-products" class="block w-full border border-gray-300 rounded px-3 py-2 mb-4" rows="4" placeholder="Zusätzliche Informationen hinterlegen"></textarea>
        <div class="flex space-x-2">
            <button type="button" id="athena-ai-create-content-products" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow-sm w-1/2">Create Content</button>
            <button type="button" id="athena-ai-transfer-content-products" class="bg-gray-400 text-white font-semibold py-2 px-4 rounded shadow-sm w-1/2 opacity-50 cursor-not-allowed" disabled>Transfer Content</button>
        </div>
        <div id="athena-ai-modal-debug-products" class="mt-4 p-3 bg-gray-100 border border-gray-300 rounded text-xs font-mono text-gray-700" style="display:none;"></div>
    </div>
</div> 