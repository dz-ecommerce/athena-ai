<?php
/**
 * Plugin Name: Athena AI
 * Plugin URI: https://your-domain.com/athena-ai
 * Description: A powerful AI integration plugin for WordPress
 * Version: 1.0.93
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
define('ATHENA_AI_VERSION', '1.0.93');
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
    // Check if user has capability
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'athena-ai'));
    }
    
    // Process actions
    if (isset($_POST['athena_fetch_feeds']) && check_admin_referer('athena_fetch_feeds_nonce')) {
        // Fetch feeds manually
        do_action('athena_fetch_feeds');
        
        // Redirect to prevent form resubmission
        wp_redirect(add_query_arg('message', 'feeds-fetched', admin_url('admin.php?page=athena-feed-items')));
        exit;
    }
    
    // Get feed items with feed info
    global $wpdb;
    
    $items_per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $items_per_page;
    
    // Get feed items
    $items = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ri.*, p.post_title as feed_title 
            FROM {$wpdb->prefix}feed_raw_items ri
            JOIN {$wpdb->posts} p ON ri.feed_id = p.ID 
            WHERE p.post_type = 'athena-feed' AND p.post_status = 'publish'
            ORDER BY ri.pub_date DESC
            LIMIT %d OFFSET %d",
            $items_per_page,
            $offset
        )
    );
    
    // Get total count for pagination
    $total_items = $wpdb->get_var(
        "SELECT COUNT(*) 
        FROM {$wpdb->prefix}feed_raw_items ri
        JOIN {$wpdb->posts} p ON ri.feed_id = p.ID 
        WHERE p.post_type = 'athena-feed' AND p.post_status = 'publish'"
    );
    
    // Get feed count
    $feed_count = $wpdb->get_var(
        "SELECT COUNT(*) 
        FROM {$wpdb->posts} 
        WHERE post_type = 'athena-feed' 
        AND post_status = 'publish'"
    );
    
    // Get last fetch time
    $last_fetch = get_option('athena_last_feed_fetch');
    $last_fetch_text = $last_fetch ? human_time_diff($last_fetch, time()) . ' ' . __('ago', 'athena-ai') : __('Never', 'athena-ai');
    
    // Pagination
    $total_pages = ceil($total_items / $items_per_page);
    
    // Start output
    echo '<div class="wrap">';
    
    // Display admin notices
    if (isset($_GET['message']) && $_GET['message'] === 'feeds-fetched') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Feeds fetched successfully.', 'athena-ai') . '</p></div>';
    }
    
    echo '<h1 class="wp-heading-inline">' . esc_html__('Feed Items', 'athena-ai') . '</h1>';
    
    // Add fetch button
    echo '<form method="post" style="display:inline;">';
    wp_nonce_field('athena_fetch_feeds_nonce');
    echo '<input type="submit" name="athena_fetch_feeds" class="page-title-action" value="' . esc_attr__('Fetch Feeds Now', 'athena-ai') . '">';
    echo '</form>';
    
    // Display stats
    echo '<div class="athena-feed-stats" style="margin: 15px 0; padding: 10px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
    echo '<p>';
    echo '<strong>' . esc_html__('Feed Statistics:', 'athena-ai') . '</strong> ';
    echo sprintf(
        esc_html__('Total Feeds: %1$s | Total Items: %2$s | Last Fetch: %3$s | Next Scheduled Fetch: %4$s', 'athena-ai'),
        '<strong>' . esc_html($feed_count) . '</strong>',
        '<strong>' . esc_html($total_items) . '</strong>',
        '<strong>' . esc_html($last_fetch_text) . '</strong>',
        '<strong>' . (wp_next_scheduled('athena_fetch_feeds') ? human_time_diff(time(), wp_next_scheduled('athena_fetch_feeds')) : __('Not scheduled', 'athena-ai')) . '</strong>'
    );
    echo '</p>';
    echo '</div>';
    
    // Display feed items
    if (empty($items)) {
        echo '<div class="notice notice-warning"><p>' . esc_html__('No feed items found. Try fetching feeds or add new feeds.', 'athena-ai') . '</p></div>';
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
        
        // Pagination
        if ($total_pages > 1) {
            $page_links = paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $current_page,
            ]);
            
            if ($page_links) {
                echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
            }
        }
    }
    
    // Add link to manage feeds
    echo '<p><a href="' . esc_url(admin_url('edit.php?post_type=athena-feed')) . '" class="button">' . esc_html__('Manage Feeds', 'athena-ai') . '</a></p>';
    
    echo '</div>';
}