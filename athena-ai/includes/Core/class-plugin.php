<?php
/**
 * Main plugin class
 *
 * @package AthenaAI
 * @subpackage Core
 */

namespace AthenaAI\Core;

/**
 * Plugin class
 */
class Plugin {
    /**
     * Plugin instance
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Admin instance
     *
     * @var \AthenaAI\Admin\Admin
     */
    private $admin;

    /**
     * Settings instance
     *
     * @var \AthenaAI\Admin\Settings
     */
    private $settings;

    /**
     * Feed instance
     *
     * @var \AthenaAI\Admin\Feed
     */
    private $feed;

    /**
     * Feed Manager instance
     *
     * @var \AthenaAI\Admin\FeedManager
     */
    private $feed_manager;

    /**
     * Initialize the plugin
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Add admin menu items
     *
     * @return void
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Athena AI', 'athena-ai'),
            __('Athena AI', 'athena-ai'),
            'manage_options',
            'athena-ai',
            [$this->admin, 'render_overview_page'],
            'dashicons-admin-generic',
            30
        );

        add_submenu_page(
            'athena-ai',
            __('Overview', 'athena-ai'),
            __('Overview', 'athena-ai'),
            'manage_options',
            'athena-ai',
            [$this->admin, 'render_overview_page']
        );

        add_submenu_page(
            'athena-ai',
            __('Feed', 'athena-ai'),
            __('Feed', 'athena-ai'),
            'manage_options',
            'athena-ai-feed',
            [$this->feed, 'render_page']
        );

        // Add Feed Management submenu
        add_submenu_page(
            'athena-ai',
            __('Feed Management', 'athena-ai'),
            __('Feed Management', 'athena-ai'),
            'manage_options',
            'edit.php?post_type=athena-feed'
        );

        add_submenu_page(
            'athena-ai',
            __('Settings', 'athena-ai'),
            __('Settings', 'athena-ai'),
            'manage_options',
            'athena-ai-settings',
            [$this->settings, 'render_page']
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook The current admin page.
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'athena-ai') === false) {
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
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
