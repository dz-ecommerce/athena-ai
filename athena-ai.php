<?php
/**
 * Plugin Name: Athena AI
 * Plugin URI: https://your-domain.com/athena-ai
 * Description: A powerful AI integration plugin for WordPress
 * Version: 1.0.141
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
define('ATHENA_AI_VERSION', '1.0.141');
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
 * Registrieren der benutzerdefinierten Cron-Intervalle
 */
function athena_ai_add_cron_intervals($schedules) {
    // 1 Minute Intervall
    $schedules['athena_1min'] = [
        'interval' => 60,
        'display' => __('Every Minute', 'athena-ai')
    ];
    
    // 5 Minuten Intervall
    $schedules['athena_5min'] = [
        'interval' => 300,
        'display' => __('Every 5 Minutes', 'athena-ai')
    ];
    
    // 15 Minuten Intervall
    $schedules['athena_15min'] = [
        'interval' => 900,
        'display' => __('Every 15 Minutes', 'athena-ai')
    ];
    
    // 30 Minuten Intervall
    $schedules['athena_30min'] = [
        'interval' => 1800,
        'display' => __('Every 30 Minutes', 'athena-ai')
    ];
    
    // 45 Minuten Intervall
    $schedules['athena_45min'] = [
        'interval' => 2700,
        'display' => __('Every 45 Minutes', 'athena-ai')
    ];
    
    return $schedules;
}
add_filter('cron_schedules', 'athena_ai_add_cron_intervals');

/**
 * Initialize the plugin
 */
function athena_ai_init() {
    // Initialize main plugin class
    $plugin = new \AthenaAI\Core\Plugin();
    $plugin->init();
    
    // Register Feed Items menu directly
    add_action('admin_menu', 'athena_ai_register_feed_items_menu');
    
    // Initialize admin classes
    if (is_admin()) {
        new \AthenaAI\Admin\Settings();
        new \AthenaAI\Admin\FeedManager();
        new \AthenaAI\Admin\StylesManager(); // Neue StylesManager-Klasse für Tailwind CSS
        new \AthenaAI\Admin\FeedItemsManager(); // Manager für Feed-Items AJAX-Funktionalität
    }
    
    // Initialize feed classes
    \AthenaAI\Admin\FeedFetcher::init();
    \AthenaAI\Admin\Maintenance::init(); // Statische init-Methode aufrufen statt Objekt zu erstellen

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
    // Globale Variablen deklarieren
    global $wpdb;
    
    // Check if user has capability
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'athena-ai'));
    }
    
    // Process actions
    $fetch_result = null;
    $show_success_message = false;
    $show_error_message = false;
    
    // Check for feed_fetched parameter (from debug action)
    // Nur anzeigen, wenn es auch tatsächlich Erfolge gab
    if (isset($_GET['feed_fetched']) && $_GET['feed_fetched'] == 1 && isset($_GET['success']) && $_GET['success'] > 0) {
        $show_success_message = true;
    }
    
    if (isset($_POST['athena_fetch_feeds']) && check_admin_referer('athena_fetch_feeds_nonce')) {
        // Zuerst sicherstellen, dass die Datenbankstruktur korrekt ist
        // Dies muss vor dem Abrufen der Feeds erfolgen, um Fehler zu vermeiden
        \AthenaAI\Admin\FeedFetcher::check_and_update_schema();
        
        // Fehlerausgabe unterdrücken, um Probleme zu vermeiden
        $wpdb->suppress_errors(true);
        $show_errors = $wpdb->show_errors;
        $wpdb->show_errors = false;
        
        // Fetch feeds manually with force flag set to true
        // Aktiviere die erweiterte Fehlerausgabe in der Konsole
        $fetch_result = \AthenaAI\Admin\FeedFetcher::fetch_all_feeds(true, true);
        
        // Fehlerausgabe wiederherstellen
        $wpdb->show_errors = $show_errors;
        $wpdb->suppress_errors(false);
        
        // Nur Erfolgsmeldung anzeigen, wenn tatsächlich Feeds erfolgreich abgerufen wurden
        if ($fetch_result['success'] > 0) {
            $show_success_message = true;
        } else {
            $show_success_message = false; // Explizit auf false setzen, falls keine Erfolge
        }
        
        if ($fetch_result['error'] > 0) {
            $show_error_message = true;
        }
    }
    
    // Get feed items with feed info
    
    // Get filter parameters
    $feed_filter = isset($_GET['feed_id']) ? intval($_GET['feed_id']) : 0;
    $date_filter = isset($_GET['date_filter']) ? sanitize_text_field($_GET['date_filter']) : '';
    
    // Items per page - allow customization via filter
    $items_per_page = apply_filters('athena_feed_items_per_page', 20);
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $items_per_page;
    
    // Build query conditions
    $where_clauses = [];
    $query_params = [$items_per_page, $offset];
    
    if ($feed_filter > 0) {
        $where_clauses[] = 'ri.feed_id = %d';
        array_unshift($query_params, $feed_filter);
    }
    
    if ($date_filter) {
        switch ($date_filter) {
            case 'today':
                $where_clauses[] = 'DATE(ri.pub_date) = CURDATE()';
                break;
            case 'yesterday':
                $where_clauses[] = 'DATE(ri.pub_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)';
                break;
            case 'this_week':
                $where_clauses[] = 'YEARWEEK(ri.pub_date, 1) = YEARWEEK(CURDATE(), 1)';
                break;
            case 'last_week':
                $where_clauses[] = 'YEARWEEK(ri.pub_date, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)';
                break;
            case 'this_month':
                $where_clauses[] = 'MONTH(ri.pub_date) = MONTH(CURDATE()) AND YEAR(ri.pub_date) = YEAR(CURDATE())';
                break;
            case 'last_month':
                $where_clauses[] = 'MONTH(ri.pub_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(ri.pub_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))';
                break;
        }
    }
    
    // Construct WHERE clause
    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
    }
    
    // Get feed items
    $query = "SELECT ri.*, p.post_title as feed_title 
        FROM {$wpdb->prefix}feed_raw_items ri
        LEFT JOIN {$wpdb->posts} p ON ri.feed_id = p.ID
        $where_sql
        ORDER BY ri.pub_date DESC
        LIMIT %d OFFSET %d";
    
    $items = $wpdb->get_results($wpdb->prepare($query, ...$query_params));
    
    // Count total items with the same filters (without pagination)
    $count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items ri $where_sql";
    $count_params = array_slice($query_params, 0, count($query_params) - 2);
    $total_items = $wpdb->get_var($count_params ? $wpdb->prepare($count_query, ...$count_params) : $count_query);
    
    // Count total feeds
    $feed_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'athena-feed' AND post_status = 'publish'"
    );
    
    // Get active feeds for filter dropdown
    $feeds = get_posts([
        'post_type' => 'athena-feed',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    // Get last fetch time
    $last_fetch = get_option('athena_last_feed_fetch');
    $last_fetch_text = $last_fetch ? human_time_diff($last_fetch, time()) . ' ' . __('ago', 'athena-ai') : __('Never', 'athena-ai');
    
    // Check if feed_metadata table exists before trying to query it
    $metadata_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'");
    $fetch_stats = null;
    
    if ($metadata_table_exists) {
        // Suppress errors to prevent issues with missing columns
        $wpdb->suppress_errors(true);
        $show_errors = $wpdb->show_errors;
        $wpdb->show_errors = false;
        
        // Use a simple query that works regardless of schema
        $fetch_stats = $wpdb->get_row("SELECT 
            COUNT(DISTINCT feed_id) as active_feeds,
            COUNT(*) as total_fetches
            FROM {$wpdb->prefix}feed_metadata");
            
        // Restore error display settings
        $wpdb->show_errors = $show_errors;
        $wpdb->suppress_errors(false);
    }
    
    // Pagination
    $total_pages = ceil($total_items / $items_per_page);
    
    // Render the new Tailwind CSS template
    include ATHENA_AI_PLUGIN_DIR . 'templates/admin/feed-items.php';
}