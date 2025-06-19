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

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/company-values-section.php'; ?>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/expertise-section.php'; ?>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/keywords-section.php'; ?>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/additional-info-section.php'; ?>
            </div>

            
            <?php submit_button(__('Einstellungen speichern', 'athena-ai'), 'primary', 'submit', false, ['class' => 'bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 text-white rounded-lg px-4 py-2']); ?>
        </form>
    </div>
</div>

<!-- AI Modals für alle Prompt-Typen -->
<?php 
// Alle verfügbaren Prompt-Typen aus der YAML-Konfiguration
$prompt_types = [
    'company_description',
    'products', 
    'company_values',
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

<!-- Beispiel für einzelne Modal-Erstellung (falls benötigt) -->
<?php 
/*
// Einzelne Modals können auch so erstellt werden:
athena_ai_render_modal('company_description');
athena_ai_render_modal('products');
athena_ai_render_modal('company_values');
// etc.
*/
?>


