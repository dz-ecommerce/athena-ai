<?php
/**
 * Plugin Name: Athena AI
 * Plugin URI: https://your-domain.com/athena-ai
 * Description: A powerful AI integration plugin for WordPress
 * Version: 1.0.115
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
define('ATHENA_AI_VERSION', '1.0.115');
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
    
    // Initialize Feed Fetcher
    \AthenaAI\Admin\FeedFetcher::init();

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
    
    // Start output
    echo '<div class="wrap">';
    
    // Display admin notices - nur wenn tatsächlich Erfolge vorhanden sind
    if ((isset($_GET['message']) && $_GET['message'] === 'feeds-fetched' && isset($_GET['success']) && $_GET['success'] > 0) || 
        ($show_success_message && $fetch_result && $fetch_result['success'] > 0)) {
        
        echo '<div class="notice notice-success is-dismissible"><p>' . 
            sprintf(
                esc_html__('Feeds fetched successfully: %d feeds processed, %d new items added.', 'athena-ai'),
                $fetch_result ? $fetch_result['success'] : (isset($_GET['success']) ? intval($_GET['success']) : 0),
                $fetch_result ? $fetch_result['new_items'] : (isset($_GET['new_items']) ? intval($_GET['new_items']) : 0)
            ) . 
            '</p></div>';
    }
    
    if ($show_error_message && $fetch_result) {
        echo '<div class="notice notice-warning is-dismissible"><p>' . 
            sprintf(
                esc_html__('Some feeds failed to fetch: %d errors occurred.', 'athena-ai'),
                $fetch_result['error']
            ) . 
            '</p></div>';
            
        if (!empty($fetch_result['details'])) {
            echo '<div class="notice notice-warning is-dismissible"><ul>';
            foreach (array_slice($fetch_result['details'], 0, 5) as $error_message) {
                echo '<li>' . esc_html($error_message) . '</li>';
            }
            if (count($fetch_result['details']) > 5) {
                echo '<li>' . esc_html__('...and more errors. Check the error log for details.', 'athena-ai') . '</li>';
            }
            echo '</ul></div>';
        }
    }
    
    echo '<h1 class="wp-heading-inline">' . esc_html__('Feed Items', 'athena-ai') . '</h1>';
    
    // Add fetch button
    echo '<form method="post" style="display:inline;">';
    wp_nonce_field('athena_fetch_feeds_nonce');
    echo '<input type="submit" name="athena_fetch_feeds" class="page-title-action" value="' . esc_attr__('Fetch Feeds Now', 'athena-ai') . '">';
    echo '</form>';
    
    // Add debug button
    echo ' <a href="' . esc_url(admin_url('admin-post.php?action=athena_debug_fetch_feeds')) . '" class="page-title-action">' . esc_html__('Debug Cron Fetch', 'athena-ai') . '</a>';
    
    // Display stats
    echo '<div class="athena-feed-stats" style="margin: 15px 0; padding: 10px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
    echo '<p>';
    echo '<strong>' . esc_html__('Feed Statistics:', 'athena-ai') . '</strong> ';
    echo sprintf(
        esc_html__('Active Feeds: %1$s | Total Items: %2$s | Last Fetch: %3$s | Next Scheduled Fetch: %4$s', 'athena-ai'),
        '<strong>' . esc_html($feed_count) . '</strong>',
        '<strong>' . esc_html($total_items) . '</strong>',
        '<strong>' . esc_html($last_fetch_text) . '</strong>',
        '<strong>' . (wp_next_scheduled('athena_fetch_feeds') ? human_time_diff(time(), wp_next_scheduled('athena_fetch_feeds')) . ' ' . __('from now', 'athena-ai') : __('Not scheduled', 'athena-ai')) . '</strong>'
    );
    echo '</p>';
    
    if ($fetch_stats) {
        echo '<p>';
        // Use null coalescing operator to safely handle potentially missing properties
        $total_fetches = isset($fetch_stats->total_fetches) ? intval($fetch_stats->total_fetches) : 0;
        $items_per_feed = ($feed_count > 0) ? round($total_items / $feed_count, 1) : 0;
        
        // Hole die Anzahl der neuen Items aus dem letzten Fetch
        $last_new_items = get_option('athena_last_feed_new_items', 0);
        
        echo sprintf(
            esc_html__('Total Fetches: %1$s | Items Per Feed: %2$s | New Items from Last Fetch: %3$s', 'athena-ai'),
            '<strong>' . esc_html($total_fetches) . '</strong>',
            '<strong>' . esc_html($items_per_feed) . '</strong>',
            '<strong>' . esc_html($last_new_items) . '</strong>'
        );
        echo '</p>';
    }
    
    echo '</div>';
    
    // Display filters
    echo '<div class="athena-feed-filters" style="margin: 15px 0; padding: 10px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="athena-feed-items">';
    
    // Feed filter
    echo '<label for="feed_id" style="margin-right: 10px;">' . esc_html__('Filter by Feed:', 'athena-ai') . '</label>';
    echo '<select name="feed_id" id="feed_id" style="margin-right: 15px;">';
    echo '<option value="0">' . esc_html__('All Feeds', 'athena-ai') . '</option>';
    
    foreach ($feeds as $feed) {
        $selected = $feed_filter == $feed->ID ? 'selected' : '';
        echo '<option value="' . esc_attr($feed->ID) . '" ' . $selected . '>' . esc_html($feed->post_title) . '</option>';
    }
    
    echo '</select>';
    
    // Date filter
    echo '<label for="date_filter" style="margin-right: 10px;">' . esc_html__('Filter by Date:', 'athena-ai') . '</label>';
    echo '<select name="date_filter" id="date_filter" style="margin-right: 15px;">';
    echo '<option value="">' . esc_html__('All Dates', 'athena-ai') . '</option>';
    echo '<option value="today" ' . selected($date_filter, 'today', false) . '>' . esc_html__('Today', 'athena-ai') . '</option>';
    echo '<option value="yesterday" ' . selected($date_filter, 'yesterday', false) . '>' . esc_html__('Yesterday', 'athena-ai') . '</option>';
    echo '<option value="this_week" ' . selected($date_filter, 'this_week', false) . '>' . esc_html__('This Week', 'athena-ai') . '</option>';
    echo '<option value="last_week" ' . selected($date_filter, 'last_week', false) . '>' . esc_html__('Last Week', 'athena-ai') . '</option>';
    echo '<option value="this_month" ' . selected($date_filter, 'this_month', false) . '>' . esc_html__('This Month', 'athena-ai') . '</option>';
    echo '<option value="last_month" ' . selected($date_filter, 'last_month', false) . '>' . esc_html__('Last Month', 'athena-ai') . '</option>';
    echo '</select>';
    
    echo '<input type="submit" class="button" value="' . esc_attr__('Apply Filters', 'athena-ai') . '">';
    
    // Reset filters
    if ($feed_filter || $date_filter) {
        echo ' <a href="' . esc_url(admin_url('admin.php?page=athena-feed-items')) . '" class="button">' . esc_html__('Reset Filters', 'athena-ai') . '</a>';
    }
    
    echo '</form>';
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
        echo '<th>' . esc_html__('Actions', 'athena-ai') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($items as $item) {
            // Ensure raw_content is a string before decoding
            if (!isset($item->raw_content) || !is_string($item->raw_content)) {
                continue; // Skip this item if raw_content is missing or not a string
            }
            
            // Safely decode JSON with error handling
            $raw_content = json_decode($item->raw_content);
            if (json_last_error() !== JSON_ERROR_NONE || !is_object($raw_content)) {
                // Log the error and skip this item
                error_log('Athena AI: JSON decode error for feed item: ' . json_last_error_msg());
                continue;
            }
            
            // Safely extract and convert properties to strings
            $title = '';
            if (isset($raw_content->title)) {
                $title = is_scalar($raw_content->title) ? (string)$raw_content->title : '';
            }
            
            $link = '';
            if (isset($raw_content->link)) {
                $link = is_scalar($raw_content->link) ? (string)$raw_content->link : '';
            }
            
            $description = '';
            if (isset($raw_content->description)) {
                $description = is_scalar($raw_content->description) ? (string)$raw_content->description : '';
            }
            
            // Handle different feed formats
            if (empty($link) && isset($raw_content->guid) && is_scalar($raw_content->guid)) {
                $link = (string)$raw_content->guid;
            }
            
            if (empty($title) && !empty($description)) {
                $title = wp_trim_words($description, 10, '...');
            } elseif (empty($title)) {
                $title = __('(No Title)', 'athena-ai');
            }
            
            echo '<tr>';
            echo '<td>' . ($link ? '<a href="' . esc_url($link) . '" target="_blank">' . esc_html($title) . '</a>' : esc_html($title)) . '</td>';
            echo '<td>' . esc_html($item->feed_title) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->pub_date))) . '</td>';
            echo '<td>';
            if ($link) {
                echo '<a href="' . esc_url($link) . '" target="_blank" class="button button-small">' . esc_html__('View', 'athena-ai') . '</a> ';
            }
            echo '</td>';
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