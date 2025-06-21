<?php
/**
 * Template für die Athena AI Profile-Seite
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

// Lade Component Helper Funktionen
include_once ATHENA_AI_PLUGIN_DIR . 'templates/admin/components/component-helpers.php';
?>
<div class="wrap athena-ai-admin">
    <!-- Header -->
    <div class="flex justify-between items-center bg-white shadow-sm px-6 py-5 mb-6 rounded-lg border border-gray-100">
        <h1 class="text-2xl font-bold text-gray-800 m-0 flex items-center">
            <span class="bg-purple-100 text-purple-600 p-2 rounded-lg mr-3">
                <i class="fa-solid fa-user-circle"></i>
            </span>
            <?php esc_html_e('Profile', 'athena-ai'); ?>
        </h1>
        <button type="button" 
                class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-6 py-3 rounded-lg font-medium flex items-center space-x-2 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105"
                data-modal-target="athena-ai-full-assistant-modal"
                data-prompt-type="full_assistant">
            <i class="fas fa-magic"></i>
            <span><?php esc_html_e('Athena AI Full Assistent', 'athena-ai'); ?></span>
        </button>
    </div>

    <!-- Content -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-100 p-6">
        <form method="post" action="options.php" class="space-y-6">
            <?php
            settings_fields('athena_ai_profile_settings');
            do_settings_sections('athena_ai_profile_settings');
            
            // Holen der gespeicherten Profildaten
            $profile_data = get_option('athena_ai_profiles', []);
            ?>

            <!-- Unternehmens-Stammdaten Section -->
            <div class="mb-8 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-700 mb-4">
                    <?php esc_html_e('Unternehmens-Stammdaten', 'athena-ai'); ?>
                </h3>
                <p class="text-gray-600 mb-6">
                    <?php esc_html_e('Diese Informationen werden für die KI-basierte Erstellung von Blogbeiträgen verwendet.', 'athena-ai'); ?>
                </p>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/company-profile-section.php'; ?>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/products-services-section.php'; ?>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/target-audience-section.php'; ?>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/expertise-section.php'; ?>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/keywords-section.php'; ?>
            </div>

            
            <!-- Action Buttons -->
            <div class="flex justify-between items-center space-x-4">
                <button type="button" 
                        id="athena-clear-settings-btn"
                        class="bg-red-600 hover:bg-red-700 focus:ring-2 focus:ring-red-500 text-white rounded-lg px-4 py-2 font-medium flex items-center space-x-2 transition-colors duration-200">
                    <i class="fas fa-trash-alt"></i>
                    <span><?php esc_html_e('Einstellungen löschen', 'athena-ai'); ?></span>
                </button>
                
                <?php submit_button(__('Einstellungen speichern', 'athena-ai'), 'primary', 'submit', false, ['class' => 'bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 text-white rounded-lg px-4 py-2 font-medium flex items-center space-x-2']); ?>
            </div>
        </form>
    </div>
</div>

<!-- AI Modals für alle Prompt-Typen -->
<?php 
// Alle verfügbaren Prompt-Typen aus der YAML-Konfiguration
$prompt_types = [
    'company_description',
    'products', 
    'target_audience',
    'company_usps',
    'expertise_areas',
    'seo_keywords'
];

// Rendere nur die Modals ohne Buttons (Buttons sind bereits in den Sections)
foreach ($prompt_types as $prompt_type) {
    $args = [
        'modal_type' => $prompt_type,
        'modal_id' => 'athena-ai-' . $prompt_type . '-modal'
    ];
    athena_ai_render_modal_html($args);
}

// Rendere das spezielle Full Assistant Modal
$full_assistant_args = [
    'modal_type' => 'full_assistant',
    'modal_id' => 'athena-ai-full-assistant-modal',
    'modal_title' => __('Athena AI Full Assistent', 'athena-ai')
];
athena_ai_render_modal_html($full_assistant_args);
?>

<!-- Debug-Bereich (nur für Entwicklung) -->
<?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
<div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded">
    <h3 class="text-lg font-semibold mb-2">🔧 Debug-Informationen</h3>
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <strong>Prompt-Typen:</strong>
            <ul class="list-disc list-inside">
                <?php foreach ($prompt_types as $type): ?>
                    <li><?php echo esc_html($type); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <strong>JavaScript-Tests:</strong>
            <button type="button" onclick="athenaAIDebugFloatingLabels()" class="bg-blue-500 text-white px-2 py-1 rounded text-xs">Test Floating Labels</button>
            <button type="button" onclick="console.log('Prompt Manager:', window.athenaAiPromptManager)" class="bg-green-500 text-white px-2 py-1 rounded text-xs">Test Prompt Manager</button>
            <button type="button" onclick="athenaAIDebugScripts()" class="bg-red-500 text-white px-2 py-1 rounded text-xs">Check Scripts</button>

        </div>
    </div>
    
    <!-- DEBUG: Zeige ob Scripts geladen werden -->
    <div class="mt-4 p-3 bg-blue-50 border">
        <strong>Script Loading Status:</strong><br>
        Hook Suffix: <?php echo esc_html($_GET['page'] ?? 'unknown'); ?><br>
        Current Screen: <?php echo esc_html(get_current_screen()->id ?? 'unknown'); ?><br>
        Scripts should be loaded: <?php echo wp_script_is('athena-ai-profile-modals', 'enqueued') ? 'YES' : 'NO'; ?>
    </div>
</div>

<script>
function athenaAIDebugScripts() {
    console.log('=== ATHENA AI DEBUG ===');
    console.log('jQuery loaded:', typeof jQuery !== 'undefined' ? 'YES' : 'NO');
    console.log('Modals script loaded:', typeof window.athenaAiModalsLoaded !== 'undefined' ? 'YES' : 'NO');
    console.log('Prompt Config available:', typeof window.athenaAiPromptConfig !== 'undefined' ? 'YES' : 'NO');
    console.log('AI-Buttons found:', jQuery('[data-modal-target]').length);
    console.log('Modals found:', jQuery('.fixed.hidden').length);
    
    // Test AI-Button Click
    if (jQuery('[data-modal-target]').length > 0) {
        console.log('Testing first AI-Button...');
        jQuery('[data-modal-target]').first().trigger('click');
    }
}
</script>
<?php endif; ?>

<!-- Clear Settings Functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const clearSettingsBtn = document.getElementById('athena-clear-settings-btn');
    if (clearSettingsBtn) {
        clearSettingsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            clearAllSettings();
        });
    }
});

function clearAllSettings() {
    // Bestätigungsdialog anzeigen
    if (!confirm('Sind Sie sicher, dass Sie alle Einstellungen löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        return;
    }
    
    // Alle Formularfelder identifizieren und leeren
    const fieldIds = [
        'company_name',
        'company_industry', 
        'company_description',
        'company_products',
        'company_usps',
        'target_audience',
        'expertise_areas',
        'seo_keywords'
    ];
    
    let clearedCount = 0;
    
    // Text-Felder und Textareas leeren
    fieldIds.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field && field.value.trim()) {
            field.value = '';
            // Trigger events für floating labels
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('blur', { bubbles: true }));
            clearedCount++;
        }
    });
    
    // Select-Felder zurücksetzen
    const selectFields = document.querySelectorAll('select[name^="athena_ai_profiles"]');
    selectFields.forEach(select => {
        if (select.selectedIndex > 0) {
            select.selectedIndex = 0;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            clearedCount++;
        }
    });
    
    // Radio-Buttons zurücksetzen
    const radioButtons = document.querySelectorAll('input[type="radio"][name^="athena_ai_profiles"]:checked');
    radioButtons.forEach(radio => {
        radio.checked = false;
        radio.dispatchEvent(new Event('change', { bubbles: true }));
        clearedCount++;
    });
    
    // Checkboxen zurücksetzen
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name^="athena_ai_profiles"]:checked');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        clearedCount++;
    });
    
    // Erfolgs-Benachrichtigung anzeigen
    if (clearedCount > 0) {
        showClearNotification(`${clearedCount} Felder wurden geleert.`, 'success');
    } else {
        showClearNotification('Keine Felder zum Leeren gefunden.', 'info');
    }
}

function showClearNotification(message, type = 'success') {
    // Erstelle Benachrichtigung
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'info' ? 'bg-blue-500' : 'bg-yellow-500';
    const icon = type === 'success' ? 'check' : type === 'info' ? 'info-circle' : 'exclamation-triangle';
    
    notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
    notification.innerHTML = `
        <div class="flex items-center space-x-2">
            <i class="fas fa-${icon}"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Benachrichtigung anzeigen
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Nach 3 Sekunden ausblenden
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}
</script>

<!-- Full Assistant funktioniert jetzt über das universelle Modal-System -->

<!-- Beispiel für einzelne Modal-Erstellung (falls benötigt) -->
<?php 
/*
// Einzelne Modals können auch so erstellt werden:
athena_ai_render_modal('company_description');
athena_ai_render_modal('products');

// etc.
*/
?>


