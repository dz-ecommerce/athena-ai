<?php
/**
 * Main AthenaAI class file
 *
 * @package AthenaAI
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Ensure plugin constants are defined
if (!defined('ATHENA_AI_VERSION')) {
    // Diese Datei sollte normalerweise über die Hauptdatei geladen werden,
    // wo die Konstanten bereits definiert sind
    return;
}

use AthenaAI\Admin\AdminModule;

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
class AthenaAI {
    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @var Athena_Loader
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @var string
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @var string
     */
    protected $version;

    /**
     * The admin module instance.
     *
     * @var \AthenaAI\Admin\AdminModule
     */
    protected $admin_module;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->version = ATHENA_AI_VERSION;
        $this->plugin_name = 'athena-ai';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // The class responsible for orchestrating the actions and filters of the core plugin.
        require_once ATHENA_AI_PLUGIN_DIR . 'includes/class-athena-loader.php';

        // The class responsible for defining internationalization functionality.
        require_once ATHENA_AI_PLUGIN_DIR . 'includes/class-athena-i18n.php';

        // Admin module
        require_once ATHENA_AI_PLUGIN_DIR . 'includes/Admin/AdminModule.php';
        require_once ATHENA_AI_PLUGIN_DIR . 'includes/Admin/Models/Profile.php';
        require_once ATHENA_AI_PLUGIN_DIR . 'includes/Admin/Services/ProfileService.php';
        require_once ATHENA_AI_PLUGIN_DIR . 'includes/Admin/Services/AIService.php';
        require_once ATHENA_AI_PLUGIN_DIR . 'includes/Admin/Controllers/ProfileController.php';

        $this->loader = new Athena_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale() {
        $plugin_i18n = new Athena_i18n();
        $plugin_i18n->set_domain($this->get_plugin_name());

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        $this->admin_module = new \AthenaAI\Admin\AdminModule($this->get_plugin_name(), $this->get_version());
        
        // Initialize admin module
        $this->loader->add_action('admin_init', $this->admin_module, 'init');
        
        // Enqueue admin scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');
        
        // Add admin menu
        $this->loader->add_action('admin_menu', $this->admin_module, 'add_admin_menu');
        
        // Initialize AJAX handlers
        $this->initialize_ajax_handlers();
    }
    
    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        // Public hooks will be added here
    }
    
    /**
     * Initialize AJAX handlers.
     */
    private function initialize_ajax_handlers() {
        // Register AJAX actions for both logged-in and non-logged-in users
        $ajax_actions = [
            'save_profile' => 'ajax_save_profile',
            'generate_content' => 'ajax_generate_content',
            'extract_product_info' => 'ajax_extract_product_info'
        ];
        
        foreach ($ajax_actions as $action => $handler) {
            $this->loader->add_action('wp_ajax_athena_ai_' . $action, $this->admin_module, $handler);
        }
    }
    
    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }
    
    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return string The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return Athena_Loader Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }
    
    /**
     * Retrieve the version number of the plugin.
     *
     * @return string The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Enqueue admin-specific styles and scripts.
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'athena-ai') === false) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            ATHENA_AI_PLUGIN_URL . 'assets/css/admin.css',
            [],
            $this->version
        );

        // Profile modals JavaScript
        wp_enqueue_script(
            'athena-ai-profile-modals',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/profile-modals.js',
            ['jquery'],
            $this->version,
            true
        );

        // Profile AJAX JavaScript
        wp_enqueue_script(
            'athena-ai-profile-ajax',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/profile-ajax.js',
            ['jquery'],
            $this->version,
            true
        );

        // Debug script for troubleshooting
        wp_enqueue_script(
            'athena-ai-profile-debug',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/profile-debug.js',
            ['jquery'],
            $this->version,
            true
        );

        // Enqueue main profile form script
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/ProfileForm.js',
            ['jquery'],
            $this->version,
            true
        );

        // Localize ajaxurl for all profile scripts
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
            $this->plugin_name . '-admin',
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

        // Also localize the old variable name for compatibility
        wp_localize_script(
            $this->plugin_name . '-admin',
            'athenaAi',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('athena_ai_nonce'),
                'i18n' => [
                    'error' => __('An error occurred. Please try again.', 'athena-ai'),
                    'success' => __('Settings saved successfully!', 'athena-ai')
                ]
            ]
        );
    }
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.0.0
 */
function run_athena_ai() {
    $plugin = new AthenaAI();
    $plugin->run();
}

// Initialize the plugin
run_athena_ai();
