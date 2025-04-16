<?php
/**
 * Plugin Name: Athena AI
 * Plugin URI: https://your-domain.com/athena-ai
 * Description: A powerful AI integration plugin for WordPress
 * Version: 1.0.92
 * Author: Your Name
 * Author URI: https://your-domain.com
 * Text Domain: athena-ai
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('ATHENA_AI_VERSION', '1.0.92');
define('ATHENA_AI_PLUGIN_FILE', __FILE__);
define('ATHENA_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ATHENA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ATHENA_AI_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'AthenaAI\\';
    $base_dir = ATHENA_AI_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Load plugin text domain
 */
function athena_ai_load_textdomain() {
    load_plugin_textdomain('athena-ai', false, dirname(ATHENA_AI_PLUGIN_BASENAME) . '/languages');
}
add_action('init', 'athena_ai_load_textdomain');

/**
 * Initialize the plugin
 */
function athena_ai_init() {
    // Initialize main plugin class
    $plugin = new \AthenaAI\Core\Plugin();
    $plugin->init();
    
    // Register Feed Items menu directly
    add_action('admin_menu', 'athena_ai_register_feed_items_menu');

    // Initialize GitHub updater
    $updater = new \AthenaAI\Core\UpdateChecker(
        'dz-ecommerce',           // GitHub username/organization
        'athena-ai',              // Repository name
        null                      // No token needed for public repositories
    );
    $updater->init();
}
add_action('plugins_loaded', 'athena_ai_init');

/**
 * Activation callback
 */
function athena_ai_activate() {
    // Create an instance of the Plugin class
    $plugin = new \AthenaAI\Core\Plugin();
    
    // Setup capabilities
    $plugin->setup_capabilities();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'athena_ai_activate');

/**
 * Deactivation callback
 */
function athena_ai_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'athena_ai_deactivate');

/**
 * Register Feed Items menu
 */
function athena_ai_register_feed_items_menu() {
    // Add as a top-level menu instead of submenu
    add_menu_page(
        __('Feed Items', 'athena-ai'),
        __('Feed Items', 'athena-ai'),
        'manage_options',
        'athena-feed-items',
        'athena_ai_render_feed_items_page',
        'dashicons-rss',
        31
    );
}

/**
 * Render the Feed Items page
 */
function athena_ai_render_feed_items_page() {
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Feed Items', 'athena-ai') . '</h1>';
    
    // Display feed items if available
    global $wpdb;
    
    $items = $wpdb->get_results(
        "SELECT ri.*, p.post_title as feed_title 
        FROM {$wpdb->prefix}feed_raw_items ri
        JOIN {$wpdb->posts} p ON ri.feed_id = p.ID 
        WHERE p.post_type = 'athena-feed' AND p.post_status = 'publish'
        ORDER BY ri.pub_date DESC
        LIMIT 20"
    );
    
    if (empty($items)) {
        echo '<p>' . esc_html__('No feed items found.', 'athena-ai') . '</p>';
    } else {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Title', 'athena-ai') . '</th>';
        echo '<th>' . esc_html__('Feed', 'athena-ai') . '</th>';
        echo '<th>' . esc_html__('Date', 'athena-ai') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($items as $item) {
            $raw_content = json_decode($item->raw_content);
            $title = isset($raw_content->title) ? (string)$raw_content->title : '';
            $link = isset($raw_content->link) ? (string)$raw_content->link : '';
            
            if (empty($title) && isset($raw_content->description)) {
                $title = wp_trim_words((string)$raw_content->description, 10, '...');
            }
            
            echo '<tr>';
            echo '<td>' . ($link ? '<a href="' . esc_url($link) . '" target="_blank">' . esc_html($title) . '</a>' : esc_html($title)) . '</td>';
            echo '<td>' . esc_html($item->feed_title) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->pub_date))) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    echo '</div>';
}