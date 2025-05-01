<?php
declare(strict_types=1);

namespace AthenaAI\Admin;

use AthenaAI\Repositories\SchemaManager;

/**
 * Admin page for feed items
 */
class FeedItemsPage {
    /**
     * Capability required to access this page
     */
    const CAPABILITY = 'manage_options';
    
    /**
     * Nonce action for AJAX requests
     */
    const NONCE_ACTION = 'athena_feed_items_nonce';
    
    /**
     * Initialize the FeedItemsPage
     */
    public static function init(): void {
        // Handle the fetch feeds action
        add_action('admin_post_athena_fetch_feeds', [self::class, 'handle_fetch_feeds']);
        
        // Add AJAX handlers
        add_action('wp_ajax_athena_fetch_feeds', [self::class, 'handle_manual_fetch']);
        
        // Add debug cron health handler
        add_action('admin_post_athena_debug_cron_health', [self::class, 'handle_debug_cron_health']);
    }
    
    /**
     * Render the feed items page
     */
    public static function render_page(): void {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'athena-ai'));
        }
        
        // Check if tables exist
        if (!SchemaManager::tables_exist()) {
            // Try to create tables
            SchemaManager::setup_tables();
            
            // Check again
            if (!SchemaManager::tables_exist()) {
                wp_die(__('Database tables could not be created. Please check your database permissions.', 'athena-ai'));
            }
        }
        
        // Process actions
        self::process_actions();
        
        // Sync feeds from custom post types
        self::sync_feeds_from_post_types();
        
        // Clean up feeds
        self::clean_up_feeds();
        
        // Get feed items
        global $wpdb;
        
        // Get filter values from GET parameters
        $feed_filter = isset($_GET['feed_id']) && !empty($_GET['feed_id']) ? intval($_GET['feed_id']) : 0;
        $date_filter = isset($_GET['date_filter']) && !empty($_GET['date_filter']) ? sanitize_text_field($_GET['date_filter']) : '';
        $search_term = isset($_GET['search_term']) && !empty($_GET['search_term']) ? sanitize_text_field($_GET['search_term']) : '';
        
        // Unterstützung für Checkbox-Mehrfachauswahl
        $feed_filter_ids = isset($_GET['feed_ids']) && is_array($_GET['feed_ids']) ? array_map('intval', $_GET['feed_ids']) : [];
        if (!empty($feed_filter_ids)) {
            $feed_filter = implode(',', $feed_filter_ids);
            $feed_filter_array = $feed_filter_ids; // Variable für das Template
        } else {
            $feed_filter_array = $feed_filter ? [$feed_filter] : [];
        }
        
        $items_per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $items_per_page;
        
        // Build query conditions for filters
        $where_conditions = ["p.post_type = 'athena-feed' AND p.post_status = 'publish'"];
        $query_params = [];
        
        // Add feed filter if specified
        if ($feed_filter) {
            if (strpos($feed_filter, ',') !== false) {
                // Mehrere Feed-IDs - verwende IN-Klausel
                $feed_ids = explode(',', $feed_filter);
                $placeholders = array_fill(0, count($feed_ids), '%d');
                $where_conditions[] = "ri.feed_id IN (" . implode(',', $placeholders) . ")";
                $query_params = array_merge($query_params, $feed_ids);
            } else {
                // Einzelne Feed-ID
                $where_conditions[] = "ri.feed_id = %d";
                $query_params[] = $feed_filter;
            }
        }
        
        // Add date filter if specified
        if ($date_filter) {
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $week_start = date('Y-m-d', strtotime('this week'));
            $last_week_start = date('Y-m-d', strtotime('last week'));
            $last_week_end = date('Y-m-d', strtotime('last week +6 days'));
            $month_start = date('Y-m-01');
            $last_month_start = date('Y-m-01', strtotime('last month'));
            $last_month_end = date('Y-m-t', strtotime('last month'));
            
            switch ($date_filter) {
                case 'today':
                    $where_conditions[] = "DATE(ri.pub_date) = %s";
                    $query_params[] = $today;
                    break;
                case 'yesterday':
                    $where_conditions[] = "DATE(ri.pub_date) = %s";
                    $query_params[] = $yesterday;
                    break;
                case 'this_week':
                    $where_conditions[] = "DATE(ri.pub_date) >= %s";
                    $query_params[] = $week_start;
                    break;
                case 'last_week':
                    $where_conditions[] = "DATE(ri.pub_date) >= %s AND DATE(ri.pub_date) <= %s";
                    $query_params[] = $last_week_start;
                    $query_params[] = $last_week_end;
                    break;
                case 'this_month':
                    $where_conditions[] = "DATE(ri.pub_date) >= %s";
                    $query_params[] = $month_start;
                    break;
                case 'last_month':
                    $where_conditions[] = "DATE(ri.pub_date) >= %s AND DATE(ri.pub_date) <= %s";
                    $query_params[] = $last_month_start;
                    $query_params[] = $last_month_end;
                    break;
            }
        }
        
        // Add search term if specified
        if (!empty($search_term)) {
            // Suche in den JSON-Daten sowohl im Titel als auch in der Beschreibung
            $where_conditions[] = "(
                JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.title')) LIKE %s 
                OR JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.description')) LIKE %s
            )";
            $search_like = '%' . $wpdb->esc_like($search_term) . '%';
            $query_params[] = $search_like;
            $query_params[] = $search_like;
        }
        
        // Combine conditions
        $where_clause = implode(' AND ', $where_conditions);
        
        // Build the main query
        $sql = "SELECT ri.*, p.post_title as feed_title 
                FROM {$wpdb->prefix}feed_raw_items ri
                JOIN {$wpdb->posts} p ON ri.feed_id = p.ID 
                WHERE {$where_clause}
                ORDER BY ri.pub_date DESC";
        
        // Add pagination parameters - these are always needed
        if (empty($query_params)) {
            // If no filter parameters, add pagination without prepare
            $sql .= " LIMIT {$items_per_page} OFFSET {$offset}";
            $items = $wpdb->get_results($sql);
        } else {
            // If we have filter parameters, use prepare with pagination
            $sql .= " LIMIT %d OFFSET %d";
            $query_params[] = $items_per_page;
            $query_params[] = $offset;
            $items = $wpdb->get_results($wpdb->prepare($sql, $query_params));
        }
        
        // Get total count query
        $count_sql = "SELECT COUNT(*) 
                      FROM {$wpdb->prefix}feed_raw_items ri
                      JOIN {$wpdb->posts} p ON ri.feed_id = p.ID 
                      WHERE {$where_clause}";
        
        // Execute count query (no need for pagination parameters)
        if (!empty($query_params)) {
            // Remove the pagination parameters we just added
            array_pop($query_params); // Remove offset
            array_pop($query_params); // Remove limit
            $total_items = $wpdb->get_var($wpdb->prepare($count_sql, $query_params));
        } else {
            $total_items = $wpdb->get_var($count_sql);
        }
        
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
        
        // Prepare items for display
        $display_items = [];
        foreach ($items as $item) {
            $raw_content = json_decode($item->raw_content);
            $title = isset($raw_content->title) ? (string)$raw_content->title : '';
            $link = isset($raw_content->link) ? (string)$raw_content->link : '';
            
            if (empty($title) && isset($raw_content->description)) {
                $title = wp_trim_words((string)$raw_content->description, 10, '...');
            }
            
            // Get feed post
            $feed_post = get_post($item->feed_id);
            $feed_title = $feed_post ? $feed_post->post_title : __('Unknown Feed', 'athena-ai');
            
            $display_items[] = [
                'id' => $item->item_hash,
                'title' => $title,
                'link' => $link,
                'feed_title' => $feed_title,
                'pub_date' => $item->pub_date,
                'raw_content' => $raw_content,
            ];
        }
        
        // Get feeds for filter
        $feeds = get_posts([
            'post_type' => 'athena-feed',
            'post_status' => 'publish',
            'numberposts' => -1,
        ]);
        
        $feed_options = [];
        foreach ($feeds as $feed) {
            $feed_url = get_post_meta($feed->ID, '_athena_feed_url', true);
            $feed_options[$feed->ID] = [
                'title' => $feed->post_title,
                'url' => $feed_url,
            ];
        }
        
        // Include the template
        include_once ATHENA_AI_PLUGIN_DIR . 'templates/admin/feed-items.php';
    }
    
    /**
     * Process actions
     */
    private static function process_actions(): void {
        if (!isset($_GET['action']) || !isset($_GET['_wpnonce'])) {
            return;
        }
        
        $action = sanitize_text_field($_GET['action']);
        $nonce = sanitize_text_field($_GET['_wpnonce']);
        
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            wp_die(__('Security check failed. Please try again.', 'athena-ai'));
        }
        
        if ('fetch_all' === $action) {
            // Process all feeds
            \AthenaAI\Background\FeedProcessor::process_feeds();
            
            // Redirect to remove action from URL
            wp_redirect(remove_query_arg(['action', '_wpnonce']));
            exit;
        } elseif ('reset_all' === $action) {
            global $wpdb;
            
            // Delete all feed items
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}feed_raw_items");
            
            // Redirect to remove action from URL
            wp_redirect(remove_query_arg(['action', '_wpnonce']));
            exit;
        }
    }
    
    /**
     * Clean up any orphaned feed items
     */
    private static function clean_up_feeds(): void {
        global $wpdb;
        
        // Get all feed IDs (post IDs) from custom post types
        $feed_post_ids = get_posts([
            'post_type' => 'athena-feed',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
        
        if (empty($feed_post_ids)) {
            // If no feeds exist, delete all feed items
            $wpdb->query("DELETE FROM {$wpdb->prefix}feed_raw_items");
            return;
        }
        
        // Delete any feed items that aren't associated with existing feeds
        $feed_ids_string = implode(',', array_map('intval', $feed_post_ids));
        $wpdb->query("DELETE FROM {$wpdb->prefix}feed_raw_items WHERE feed_id NOT IN ($feed_ids_string)");
    }
    
    /**
     * Ensure all feeds have required meta data
     */
    private static function sync_feeds_from_post_types(): void {
        // Get all published feed post types
        $feed_posts = get_posts([
            'post_type' => 'athena-feed',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        if (empty($feed_posts)) {
            return;
        }
        
        foreach ($feed_posts as $post) {
            // Get the feed URL from post meta
            $feed_url = get_post_meta($post->ID, '_athena_feed_url', true);
            
            if (empty($feed_url)) {
                continue;
            }
            
            // Ensure feed has required meta data
            if (!get_post_meta($post->ID, '_athena_feed_update_interval', true)) {
                update_post_meta($post->ID, '_athena_feed_update_interval', 3600); // Default: 1 hour
            }
            
            if (!get_post_meta($post->ID, '_athena_feed_active', true)) {
                update_post_meta($post->ID, '_athena_feed_active', '1'); // Active by default
            }
        }
    }
    
    /**
     * Handle the feed fetch action (non-AJAX).     
     * 
     * @return void
     */
    public static function handle_fetch_feeds(): void {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'athena-ai'));
        }
        
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'athena_fetch_feeds_nonce')) {
            wp_die(__('Security check failed. Please try again.', 'athena-ai'));
        }
        
        // Fetch feeds - set verbose_console to false to prevent output before headers
        $result = \AthenaAI\Admin\FeedFetcher::fetch_all_feeds(true, false);
        
        // Redirect back with results
        wp_redirect(add_query_arg([
            'page' => 'athena-feed-items',
            'feed_fetched' => 1,
            'success' => $result['success'],
            'error' => $result['error'],
            'new_items' => $result['new_items']
        ], admin_url('admin.php')));
        exit;
    }
    
    /**
     * Handle the manual feed fetch AJAX request
     */
    public static function handle_manual_fetch(): void {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'athena-ai')]);
            return;
        }
        
        // Check if tables exist
        if (!\AthenaAI\Repositories\SchemaManager::tables_exist()) {
            // Try to create tables
            \AthenaAI\Repositories\SchemaManager::setup_tables();
            
            // Check again
            if (!\AthenaAI\Repositories\SchemaManager::tables_exist()) {
                wp_send_json_error(['message' => __('Database tables could not be created. Please check your database permissions.', 'athena-ai')]);
                return;
            }
        }
        
        // Get feed ID from request
        $feed_id = isset($_POST['feed_id']) ? intval($_POST['feed_id']) : 0;
        
        if (!$feed_id) {
            wp_send_json_error(['message' => __('No feed specified.', 'athena-ai')]);
            return;
        }
        
        // Get feed
        $feed = \AthenaAI\Models\Feed::get_by_id($feed_id);
        
        if (!$feed) {
            wp_send_json_error(['message' => __('Feed not found.', 'athena-ai')]);
            return;
        }
        
        // Fetch feed
        $result = $feed->fetch();
        
        if ($result) {
            wp_send_json_success(['message' => __('Feed fetched successfully.', 'athena-ai')]);
        } else {
            wp_send_json_error(['message' => __('Failed to fetch feed. Please check the feed URL and try again.', 'athena-ai')]);
        }
    }

    /**
     * Debug cron health handler
     */
    public static function handle_debug_cron_health(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'athena-ai'));
        }
        
        check_admin_referer('athena_debug_cron_health_nonce');
        
        // Force cron re-scheduling
        \AthenaAI\Services\CronScheduler::debug_cron_health();
        
        // Get next scheduled time
        $next_scheduled = wp_next_scheduled('athena_fetch_feeds');
        $current_interval = wp_get_schedule('athena_fetch_feeds');
        $expected_interval = get_option('athena_ai_feed_cron_interval', 'hourly');
        
        $redirect_url = add_query_arg([
            'page' => 'athena-feed-items',
            'cron_debugged' => 1,
            'next_scheduled' => $next_scheduled ? date('Y-m-d H:i:s', $next_scheduled) : 'None',
            'current_interval' => $current_interval ?: 'None',
            'expected_interval' => $expected_interval
        ], admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
}
