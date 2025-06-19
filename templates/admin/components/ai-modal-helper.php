<?php
/**
 * AI Modal Helper Functions
 * 
 * Helper-Funktionen für die Verwendung des universellen AI-Modals
 * 
 * @package AthenaAI
 * @since 2.1.0
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rendert ein universelles AI-Modal für einen bestimmten Prompt-Typ
 * 
 * @param array $args {
 *     @type string $modal_type    Required. Der Prompt-Typ aus der YAML-Konfiguration
 *     @type string $modal_id      Optional. Eindeutige Modal-ID. Default: 'athena-ai-{modal_type}-modal'
 *     @type string $modal_title   Optional. Modal-Titel. Default: aus YAML oder 'Athena AI Assistent'
 *     @type string $button_text   Optional. Text für den Öffnen-Button. Default: 'AI Assistent'
 *     @type string $button_class  Optional. CSS-Klassen für den Button. Default: 'button button-primary'
 *     @type bool   $auto_render   Optional. Modal automatisch rendern. Default: true
 * }
 */
function athena_ai_render_modal($args = []) {
    $defaults = [
        'modal_type' => 'company_description',
        'modal_id' => '',
        'modal_title' => '',
        'button_text' => __('AI Assistent', 'athena-ai'),
        'button_class' => 'button button-primary',
        'auto_render' => true
    ];
    
    $args = wp_parse_args($args, $defaults);
    
    // Modal-ID generieren falls nicht angegeben
    if (empty($args['modal_id'])) {
        $args['modal_id'] = 'athena-ai-' . $args['modal_type'] . '-modal';
    }
    
    // Modal-Titel aus Prompt Manager abrufen falls nicht angegeben
    if (empty($args['modal_title'])) {
        $prompt_manager = \AthenaAI\Core\PromptManager::get_instance();
        $config = $prompt_manager->get_prompt($args['modal_type']);
        $args['modal_title'] = $config['title'] ?? __('Athena AI Assistent', 'athena-ai');
    }
    
    // Button rendern
    echo '<button type="button" class="' . esc_attr($args['button_class']) . '" data-modal-target="' . esc_attr($args['modal_id']) . '">';
    echo esc_html($args['button_text']);
    echo '</button>';
    
    // Modal rendern falls auto_render aktiviert
    if ($args['auto_render']) {
        athena_ai_render_modal_html($args);
    }
}

/**
 * Rendert nur das Modal-HTML ohne Button
 * 
 * @param array $args Modal-Konfiguration
 */
function athena_ai_render_modal_html($args) {
    include ATHENA_AI_PLUGIN_DIR . 'templates/admin/modals/universal-ai-modal.php';
}

/**
 * Rendert mehrere AI-Modals für verschiedene Prompt-Typen
 * 
 * @param array $modal_configs Array von Modal-Konfigurationen
 */
function athena_ai_render_multiple_modals($modal_configs) {
    foreach ($modal_configs as $config) {
        athena_ai_render_modal($config);
    }
}

/**
 * Erstellt einen AI-Button mit Icon
 * 
 * @param array $args Button-Konfiguration
 */
function athena_ai_button($args = []) {
    $defaults = [
        'prompt_type' => 'company_description',
        'text' => __('AI Assistent', 'athena-ai'),
        'icon' => 'fas fa-magic',
        'class' => 'athena-ai-btn',
        'style' => 'primary'
    ];
    
    $args = wp_parse_args($args, $defaults);
    $modal_id = 'athena-ai-' . $args['prompt_type'] . '-modal';
    
    $button_classes = [
        'bg-purple-600',
        'hover:bg-purple-700',
        'text-white',
        'font-semibold',
        'py-2',
        'px-4',
        'rounded',
        'shadow-sm',
        'transition-colors',
        'inline-flex',
        'items-center',
        'gap-2'
    ];
    
    ?>
    <button type="button" 
            class="<?php echo esc_attr(implode(' ', $button_classes)); ?>" 
            data-modal-target="<?php echo esc_attr($modal_id); ?>"
            data-prompt-type="<?php echo esc_attr($args['prompt_type']); ?>"
            title="<?php echo esc_attr($args['text']); ?>">
        <?php if (!empty($args['icon'])): ?>
            <i class="<?php echo esc_attr($args['icon']); ?>"></i>
        <?php endif; ?>
        <span><?php echo esc_html($args['text']); ?></span>
    </button>
    <?php
}

/**
 * Prüft ob ein Modal-Typ verfügbar ist
 * 
 * @param string $modal_type Modal-Typ
 * @return bool True wenn verfügbar
 */
function athena_ai_is_modal_available($modal_type) {
    $prompt_manager = \AthenaAI\Core\PromptManager::get_instance();
    return $prompt_manager->get_prompt($modal_type) !== null;
}

/**
 * Gibt alle verfügbaren Modal-Typen zurück
 * 
 * @return array Array von Modal-Typen
 */
function athena_ai_get_available_modals() {
    $prompt_manager = \AthenaAI\Core\PromptManager::get_instance();
    return $prompt_manager->get_available_modals();
} 