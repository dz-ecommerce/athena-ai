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

// Spezielle Behandlung für Full Assistant Modal
$is_full_assistant = ($modal_type === 'full_assistant');
?>

<!-- Universal AI Modal -->
<div id="<?php echo esc_attr($modal_id); ?>" class="fixed z-50 inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden overflow-y-auto overflow-x-hidden" data-modal-type="<?php echo esc_attr($modal_type); ?>">
    <div class="bg-white rounded-lg shadow-lg w-[60vw] p-6 relative max-h-[80vh] overflow-y-auto overflow-x-hidden">
        <button type="button" class="athena-ai-modal-close absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl font-bold">&times;</button>
        <h2 class="text-lg font-semibold mb-4"><?php echo esc_html($modal_title); ?></h2>
        
        <?php if ($is_full_assistant): ?>
            <!-- Full Assistant spezifische Inhalte -->
            <div class="mb-6">
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-4">
                    <h3 class="font-semibold text-purple-800 mb-2">
                        <i class="fas fa-magic mr-2"></i>
                        <?php esc_html_e('Full Assistant Modus', 'athena-ai'); ?>
                    </h3>
                    <p class="text-purple-700 text-sm">
                        <?php esc_html_e('Dieser Assistent generiert automatisch alle Profilinhalte basierend auf Ihren Eingaben. Alle Textfelder werden sequenziell gefüllt.', 'athena-ai'); ?>
                    </p>
                </div>
                
                <!-- Zu generierende Inhalte -->
                <div class="mb-4">
                    <h4 class="font-medium text-gray-700 mb-3"><?php esc_html_e('Folgende Inhalte werden generiert:', 'athena-ai'); ?></h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i><?php esc_html_e('Unternehmensbeschreibung', 'athena-ai'); ?></div>
                        <div class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i><?php esc_html_e('Produkte & Dienstleistungen', 'athena-ai'); ?></div>
                        <div class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i><?php esc_html_e('Alleinstellungsmerkmale', 'athena-ai'); ?></div>
                        <div class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i><?php esc_html_e('Zielgruppe', 'athena-ai'); ?></div>
                        <div class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i><?php esc_html_e('Expertise-Bereiche', 'athena-ai'); ?></div>
                        <div class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i><?php esc_html_e('SEO-Keywords', 'athena-ai'); ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
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
        
        <!-- Seiten-Auswahl -->
        <select class="athena-ai-page-select block w-full border border-gray-300 rounded px-3 py-2 mb-4 flex-grow max-w-full box-border">
            <option value=""><?php esc_html_e('-- Seite wählen (optional) --', 'athena-ai'); ?></option>
            <?php foreach ($pages as $page): ?>
                <option value="<?php echo esc_attr($page->ID); ?>"><?php echo esc_html($page->post_title); ?></option>
            <?php endforeach; ?>
        </select>
        
        <!-- Zusätzliche Informationen -->
        <textarea class="athena-ai-modal-extra-info block w-full border border-gray-300 rounded px-3 py-2 mb-4" rows="4" placeholder="<?php esc_attr_e($is_full_assistant ? 'Grundlegende Informationen zu Ihrem Unternehmen (Branche, Zielgruppe, Besonderheiten...)' : 'Zusätzliche Informationen hinterlegen', 'athena-ai'); ?>"></textarea>
        
        <!-- Action Buttons -->
        <div class="flex space-x-2">
            <?php if ($is_full_assistant): ?>
                <button type="button" class="athena-ai-create-content bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded shadow-sm w-1/2">
                    <i class="fas fa-magic mr-2"></i>
                    <?php esc_html_e('Alle Inhalte generieren', 'athena-ai'); ?>
                </button>
                <button type="button" class="athena-ai-transfer-content bg-gray-400 text-white font-semibold py-2 px-4 rounded shadow-sm w-1/2 opacity-50 cursor-not-allowed" disabled>
                    <i class="fas fa-download mr-2"></i>
                    <?php esc_html_e('Alle Inhalte übertragen', 'athena-ai'); ?>
                </button>
            <?php else: ?>
                <button type="button" class="athena-ai-create-content bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow-sm w-1/2">
                    <?php esc_html_e('Content erstellen', 'athena-ai'); ?>
                </button>
                <button type="button" class="athena-ai-transfer-content bg-gray-400 text-white font-semibold py-2 px-4 rounded shadow-sm w-1/2 opacity-50 cursor-not-allowed" disabled>
                    <?php esc_html_e('Content übertragen', 'athena-ai'); ?>
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Progress Bar für Full Assistant -->
        <?php if ($is_full_assistant): ?>
            <div class="athena-ai-progress-container mt-4 hidden">
                <div class="mb-2">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span class="athena-ai-progress-text">Vorbereitung...</span>
                        <span class="athena-ai-progress-count">0/6</span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="athena-ai-progress-bar bg-purple-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Debug Output -->
        <div class="athena-ai-modal-debug mt-4 p-3 bg-gray-100 border border-gray-300 rounded text-xs font-mono text-gray-700" style="display:none;"></div>
    </div>
</div> 