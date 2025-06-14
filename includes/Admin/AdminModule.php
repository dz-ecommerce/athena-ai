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

        // Enqueue scripts for profile page
        if (strpos($hook, 'athena-ai-profile') !== false || strpos($hook, 'athena-ai-profiles') !== false) {
            // Profile modals JavaScript
            wp_enqueue_script(
                'athena-ai-profile-modals',
                ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/profile-modals.js',
                ['jquery'],
                ATHENA_AI_VERSION,
                true
            );

            // Profile AJAX JavaScript
            wp_enqueue_script(
                'athena-ai-profile-ajax',
                ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/profile-ajax.js',
                ['jquery'],
                ATHENA_AI_VERSION,
                true
            );
        }

        // Enqueue main profile form script
        wp_enqueue_script(
            'athena-ai-admin',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/ProfileForm.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
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
        
        // Localize ajaxurl for profile scripts
        wp_localize_script(
            'athena-ai-profile-ajax',
            'ajaxurl',
            admin_url('admin-ajax.php')
        );
    }
}
