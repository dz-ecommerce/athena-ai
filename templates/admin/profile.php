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

<?php
// Include modal templates
include ATHENA_AI_PLUGIN_DIR . 'templates/admin/modals/company-description-modal.php';
include ATHENA_AI_PLUGIN_DIR . 'templates/admin/modals/products-modal.php';
include ATHENA_AI_PLUGIN_DIR . 'templates/admin/modals/fallback-scripts.php';
?>


