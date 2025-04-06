<?php
namespace AthenaAI\Core;

use AthenaAI\Admin\Feed;
use AthenaAI\Admin\Settings;

class Plugin {
    /**
     * @var \AthenaAI\Admin\Feed
     */
    private $feed;

    /**
     * @var \AthenaAI\Admin\Settings
     */
    private $settings;

    /**
     * @var \AthenaAI\Admin\FeedManager
     */
    private $feed_manager;

    /**
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize components
        $this->feed = new \AthenaAI\Admin\Feed();
        $this->settings = new \AthenaAI\Admin\Settings();
        $this->feed_manager = new \AthenaAI\Admin\FeedManager();

        // Admin hooks - register these on init to ensure proper loading order
        add_action('init', [$this, 'register_hooks']);
    }

    /**
     * Register hooks
     */
    public function register_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 9); // Run before post type registration
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Initialize feed manager
        $this->feed_manager->init();
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Add capabilities to administrator role if needed
        $this->setup_capabilities();
    }

    /**
     * Setup plugin capabilities
     */
    public function setup_capabilities() {
        // Register post type to ensure capabilities are available
        $this->feed->register_post_type();

        // Add capabilities to administrator role
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('edit_athena_feed');
            $admin->add_cap('read_athena_feed');
            $admin->add_cap('delete_athena_feed');
            $admin->add_cap('edit_athena_feeds');
            $admin->add_cap('edit_others_athena_feeds');
            $admin->add_cap('publish_athena_feeds');
            $admin->add_cap('read_private_athena_feeds');
            $admin->add_cap('manage_athena_ai');
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu - Athena AI
        add_menu_page(
            __('Athena AI', 'athena-ai'),
            __('Athena AI', 'athena-ai'),
            'manage_athena_ai',
            'athena-ai',
            null,
            'dashicons-rss',
            30
        );

        // Add Settings as submenu
        add_submenu_page(
            'athena-ai',
            __('Settings', 'athena-ai'),
            __('Settings', 'athena-ai'),
            'manage_athena_ai',
            'athena-ai-settings',
            [$this->settings, 'render_page']
        );

        // Add Feeds submenu
        add_submenu_page(
            'athena-ai',
            __('Feeds', 'athena-ai'),
            __('Feeds', 'athena-ai'),
            'edit_athena_feeds',
            'edit.php?post_type=athena-feed'
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Check if we're on an Athena AI admin page
        $is_athena_page = (
            strpos($hook, 'athena-ai') !== false ||
            (strpos($hook, 'post.php') !== false && get_post_type() === 'athena-feed') ||
            (strpos($hook, 'post-new.php') !== false && isset($_GET['post_type']) && $_GET['post_type'] === 'athena-feed') ||
            (strpos($hook, 'edit.php') !== false && isset($_GET['post_type']) && $_GET['post_type'] === 'athena-feed')
        );

        if (!$is_athena_page) {
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