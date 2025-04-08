<?php
namespace AthenaAI\Core;

use AthenaAI\Admin\FeedManager;
use AthenaAI\Admin\Settings;
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
        
        // Add View Feeds submenu
        add_submenu_page(
            'edit.php?post_type=athena-feed',
            __('View Feeds', 'athena-ai'),
            __('View Feeds', 'athena-ai'),
            'read',
            'athena-view-feeds',
            [$this, 'redirect_to_feeds_page']
        );

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
            
            foreach ($menu_items as $item) {
                if ($item[2] === 'edit-tags.php?taxonomy=athena-feed-category&amp;post_type=athena-feed') {
                    // Categories
                    $submenu['edit.php?post_type=athena-feed'][] = $item;
                }
            }
            
            foreach ($menu_items as $item) {
                if ($item[2] === 'athena-view-feeds') {
                    // View Feeds
                    $submenu['edit.php?post_type=athena-feed'][] = $item;
                }
            }
            
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
        // Check if we're on the view feeds page
        if (isset($_GET['page']) && $_GET['page'] === 'athena-view-feeds') {
            $feeds_page = get_page_by_path('athena-feeds');
            if ($feeds_page) {
                wp_redirect(get_permalink($feeds_page->ID));
                exit;
            }
        }
    }

    /**
     * Redirect to the feeds page
     */
    public function redirect_to_feeds_page() {
        // Display a message instead of redirecting, since the redirect is handled in handle_admin_redirects
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Feeds', 'athena-ai') . '</h1>';
        
        $feeds_page = get_page_by_path('athena-feeds');
        if (!$feeds_page) {
            echo '<p>' . esc_html__('The feeds page has not been created yet. Please visit the site after plugin activation to create it automatically.', 'athena-ai') . '</p>';
        } else {
            echo '<p>' . esc_html__('If you are not automatically redirected, please click the link below:', 'athena-ai') . '</p>';
            echo '<p><a href="' . esc_url(get_permalink($feeds_page->ID)) . '" class="button button-primary">' . esc_html__('View Feeds', 'athena-ai') . '</a></p>';
        }
        
        echo '</div>';
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