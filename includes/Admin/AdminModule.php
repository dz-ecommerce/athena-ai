<?php
/**
 * Main admin module class
 */
namespace AthenaAI\Admin;

use AthenaAI\Admin\Controllers\ProfileController;
use AthenaAI\Admin\Models\Profile;
use AthenaAI\Admin\Services\ProfileService;
use AthenaAI\Admin\Services\AIService;
use AthenaAI\Admin\Views\ProfileView;

class AdminModule {
    /**
     * @var ProfileController Profile controller instance
     */
    private $profile_controller;
    
    /**
     * @var string The plugin name
     */
    private $plugin_name;
    
    /**
     * @var string The plugin version
     */
    private $version;
    
    /**
     * Constructor
     * 
     * @param string $plugin_name The name of the plugin
     * @param string $version The version of the plugin
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Initialize the admin module
     */
    public function init() {
        // Initialize dependencies
        $profile_model = new Profile();
        $profile_service = new ProfileService($profile_model);
        $ai_service = new AIService();
        
        // Initialize controllers
        $this->profile_controller = new ProfileController(
            $profile_service,
            $ai_service,
            new ProfileView($profile_service)
        );
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Athena AI', 'athena-ai'),
            __('Athena AI', 'athena-ai'),
            'manage_options',
            'athena-ai',
            [$this, 'dashboard_page'],
            'dashicons-admin-generic',
            30
        );

        add_submenu_page(
            'athena-ai',
            __('Profile', 'athena-ai'),
            __('Profile', 'athena-ai'),
            'manage_options',
            'athena-ai-profile',
            [$this->profile_controller, 'renderProfilePage']
        );
    }

    /**
     * Render the dashboard page
     */
    public function dashboard_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Include the template
        include ATHENA_AI_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    /**
     * Enqueue admin assets
     * 
     * @param string $hook Current admin page
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'athena-ai') === false) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'athena-ai-admin',
            ATHENA_AI_PLUGIN_URL . 'assets/css/admin.css',
            [],
            ATHENA_AI_VERSION
        );

        // Enqueue Prompt Manager
        wp_enqueue_script(
            'athena-ai-prompt-manager',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/PromptManager.js',
            [],
            ATHENA_AI_VERSION,
            true
        );

        // Profile modals JavaScript (always load on athena-ai pages)
        wp_enqueue_script(
            'athena-ai-profile-modals',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/profile-modals.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );

        // Profile AJAX JavaScript (always load on athena-ai pages)
        wp_enqueue_script(
            'athena-ai-profile-ajax',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/profile-ajax.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );

        // Enqueue main profile form script
        wp_enqueue_script(
            'athena-ai-admin',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/ProfileForm.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );

        // Prompt-Konfiguration laden
        $this->enqueue_prompt_config();

        // Localize ajaxurl globally for all profile scripts
        wp_localize_script(
            'athena-ai-profile-modals',
            'ajaxurl',
            admin_url('admin-ajax.php')
        );
        
        wp_localize_script(
            'athena-ai-profile-ajax',
            'ajaxurl',
            admin_url('admin-ajax.php')
        );

        // Localize script with AJAX URL and nonce
        wp_localize_script(
            'athena-ai-admin',
            'athenaAiAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('athena_ai_ajax_nonce'),
                'i18n' => [
                    'error' => __('An error occurred', 'athena-ai'),
                    'saving' => __('Saving...', 'athena-ai'),
                    'saved' => __('Saved!', 'athena-ai'),
                ]
            ]
        );
    }

    /**
     * Prompt-Konfiguration ins Frontend laden
     */
    private function enqueue_prompt_config() {
        $prompt_manager = \AthenaAI\Core\PromptManager::get_instance();
        
        // Prompt-Konfiguration als JSON ins DOM einbetten
        $config = [
            'prompts' => [],
            'global' => [],
            'validation' => []
        ];
        
        // Alle verfügbaren Modals laden
        foreach ($prompt_manager->get_available_modals() as $modal_type) {
            $config['prompts'][$modal_type] = $prompt_manager->get_prompt($modal_type);
        }
        
        // Globale Einstellungen
        $config['global'] = [
            'default_provider' => $prompt_manager->get_global_setting('default_provider'),
            'test_mode_available' => $prompt_manager->get_global_setting('test_mode_available'),
            'debug_mode' => $prompt_manager->get_global_setting('debug_mode')
        ];
        
        // Validierungsregeln
        $config['validation'] = $prompt_manager->get_validation_rules();
        
        // JSON-Konfiguration ins DOM einbetten
        wp_add_inline_script(
            'athena-ai-prompt-manager',
            'window.athenaAiPromptConfig = ' . wp_json_encode($config) . ';',
            'before'
        );
        
        // Alternativ: Als verstecktes DOM-Element
        add_action('admin_footer', function() use ($config) {
            echo '<script type="application/json" id="athena-ai-prompt-config">' . 
                 wp_json_encode($config) . 
                 '</script>';
        });
    }
}
