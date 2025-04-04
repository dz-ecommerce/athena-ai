<?php
namespace AthenaAI\Core;

class Plugin {
    /**
     * @var Plugin
     */
    private static $instance = null;

    /**
     * @var \AthenaAI\Admin\Admin
     */
    private $admin;

    /**
     * @var \AthenaAI\Admin\Settings
     */
    private $settings;

    /**
     * @var \AthenaAI\Admin\Feed
     */
    private $feed;

    /**
     * @var \AthenaAI\Admin\FeedManager
     */
    private $feed_manager;

    /**
     * Initialize the plugin
     */
    public function init() {
        // Initialize admin components
        if (is_admin()) {
            $this->init_admin();
        }

        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Initialize admin components
     */
    private function init_admin() {
        $this->admin = new \AthenaAI\Admin\Admin();
        $this->settings = new \AthenaAI\Admin\Settings();
        $this->feed = new \AthenaAI\Admin\Feed();
        $this->feed_manager = new \AthenaAI\Admin\FeedManager();
        $this->feed_manager->init();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        // Main menu - Feeds
        add_menu_page(
            __('Athena AI', 'athena-ai'),
            __('Athena AI', 'athena-ai'),
            'manage_options',
            'edit.php?post_type=athena-feed', // Link to feed post type
            null,
            'dashicons-admin-generic',
            30
        );

        // Settings
        add_submenu_page(
            'edit.php?post_type=athena-feed',
            __('Settings', 'athena-ai'),
            __('Settings', 'athena-ai'),
            'manage_options',
            'athena-ai-settings',
            [$this->settings, 'render_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'athena-ai') === false && strpos($hook, 'post.php') === false && strpos($hook, 'post-new.php') === false && strpos($hook, 'edit.php') === false) {
            return;
        }

        wp_enqueue_style(
            'athena-ai-admin',
            ATHENA_AI_PLUGIN_URL . 'assets/css/admin.css',
            [],
            ATHENA_AI_VERSION
        );

        wp_enqueue_script(
            'athena-ai-admin',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );

        wp_localize_script('athena-ai-admin', 'athenaAiAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('athena-ai-nonce'),
        ]);
    }

    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
} 