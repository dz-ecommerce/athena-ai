<?php
/**
 * Main admin module class
 */
class AdminModule {
    /**
     * @var ProfileController Profile controller instance
     */
    private $profile_controller;

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
        
        // Register hooks
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    /**
     * Add admin menu items
     */
    public function addAdminMenu() {
        add_menu_page(
            __('Athena AI', 'athena-ai'),
            __('Athena AI', 'athena-ai'),
            'manage_options',
            'athena-ai',
            '',
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
     * Enqueue admin assets
     * 
     * @param string $hook Current admin page
     */
    public function enqueueAdminAssets($hook) {
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

        // Enqueue scripts
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
    }
}
