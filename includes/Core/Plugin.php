<?php
namespace AthenaAI\Core;

use AthenaAI\Admin\FeedManager;
use AthenaAI\Admin\Settings;
use AthenaAI\Admin\FeedItemsPage;
use AthenaAI\Frontend\FeedDisplay;
use AthenaAI\Frontend\FeedWidget;

class Plugin {
    /**
     * @var \AthenaAI\Admin\FeedManager
     */
    private $feed_manager;

    /**
     * @var \AthenaAI\Admin\Settings
     */
    private $settings;
    
    /**
     * @var \AthenaAI\Frontend\FeedDisplay
     */
    private $feed_display;

    /**
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize components
        $this->feed_manager = new FeedManager();
        $this->settings = new Settings();
        $this->feed_display = new FeedDisplay();

        // Register hooks
        $this->register_hooks();
    }

    /**
     * Register hooks
     */
    public function register_hooks() {
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_admin_menu'], 99); // Run after post type registration
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('widgets_init', [$this, 'register_widgets']);
        add_action('admin_init', [$this, 'handle_admin_redirects']);
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Add capabilities to administrator role if needed
        $this->setup_capabilities();

        // Initialize the Feed Items page
        FeedItemsPage::init();

        // Initialize DB Schema Manager
        \AthenaAI\Repositories\SchemaManager::init();

        // Initialize Feed Processor
        \AthenaAI\Background\FeedProcessor::init();

        // Initialize Cron Scheduler service
        \AthenaAI\Services\CronScheduler::init();
    }

    /**
     * Setup plugin capabilities
     */
    public function setup_capabilities() {
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
     * Register widgets
     */
    public function register_widgets() {
        register_widget(FeedWidget::class);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add Settings as submenu of Feeds
        add_submenu_page(
            'edit.php?post_type=athena-feed',
            __('Settings', 'athena-ai'),
            __('Settings', 'athena-ai'),
            'manage_athena_ai',
            'athena-ai-settings',
            [$this->settings, 'render_page']
        );
        
        // Add ViewFeed News submenu
        /*add_submenu_page(
            'edit.php?post_type=athena-feed',
            __('ViewFeed News', 'athena-ai'),
            __('ViewFeed News', 'athena-ai'),
            'read',
            'athena-viewfeed-news',
            [$this, 'render_viewfeed_news_page']
        );*/

        // Ensure proper menu order
        global $submenu;
        if (isset($submenu['edit.php?post_type=athena-feed'])) {
            // Store the current menu items
            $menu_items = $submenu['edit.php?post_type=athena-feed'];
            
            // Reset the menu
            $submenu['edit.php?post_type=athena-feed'] = [];
            
            // Add items in the desired order
            foreach ($menu_items as $item) {
                if ($item[2] === 'edit.php?post_type=athena-feed') {
                    // All Feeds
                    $submenu['edit.php?post_type=athena-feed'][] = $item;
                }
            }
            
            foreach ($menu_items as $item) {
                if ($item[2] === 'post-new.php?post_type=athena-feed') {
                    // Add New
                    $submenu['edit.php?post_type=athena-feed'][] = $item;
                }
            }
            
            /*foreach ($menu_items as $item) {
                if ($item[2] === 'edit-tags.php?taxonomy=athena-feed-category&amp;post_type=athena-feed') {
                    // Categories
                    $submenu['edit.php?post_type=athena-feed'][] = $item;
                }
            }*/
            
            /*foreach ($menu_items as $item) {
                if ($item[2] === 'athena-viewfeed-news') {
                    // ViewFeed News
                    $submenu['edit.php?post_type=athena-feed'][] = $item;
                }
            }*/
            
            foreach ($menu_items as $item) {
                if ($item[2] === 'athena-ai-settings') {
                    // Settings
                    $submenu['edit.php?post_type=athena-feed'][] = $item;
                }
            }
        }
    }
    
    /**
     * Handle admin redirects
     */
    public function handle_admin_redirects() {
        // No redirects needed anymore as we're staying in the admin
        return;
    }

    /**
     * Render the ViewFeed News page
     */
    /*public function render_viewfeed_news_page() {
        if (!current_user_can('read')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'athena-ai'));
        }
        require_once ATHENA_AI_PLUGIN_DIR . 'templates/admin/view-feed-news.php';
    }*/

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Stelle sicher, dass $hook ein String ist
        if (!is_string($hook)) {
            $hook = '';
        }
        
        // Check if we're on an Athena AI admin page
        $is_athena_page = (
            ($hook !== '' && strpos($hook, 'athena-ai') !== false) ||
            ($hook !== '' && strpos($hook, 'post.php') !== false && function_exists('get_post_type') && get_post_type() === 'athena-feed') ||
            ($hook !== '' && strpos($hook, 'post-new.php') !== false && isset($_GET['post_type']) && $_GET['post_type'] === 'athena-feed') ||
            ($hook !== '' && strpos($hook, 'edit.php') !== false && isset($_GET['post_type']) && $_GET['post_type'] === 'athena-feed')
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