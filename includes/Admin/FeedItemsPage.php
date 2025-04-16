<?php
declare(strict_types=1);

namespace AthenaAI\Admin;

use AthenaAI\Database\DatabaseSetup;

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
     * Initialize the class
     */
    public static function init(): void {
        // Add AJAX handlers
        add_action('wp_ajax_athena_fetch_feeds', [self::class, 'handle_manual_fetch']);
        
        // Register the admin page
        add_action('admin_menu', [self::class, 'register_admin_page']);
    }
    
    /**
     * Register the admin page
     */
    public static function register_admin_page(): void {
        add_submenu_page(
            'edit.php?post_type=athena-feed',
            __('Feed Items', 'athena-ai'),
            __('Feed Items', 'athena-ai'),
            self::CAPABILITY,
            'athena-feed-items',
            [self::class, 'render_page']
        );
    }
    
    /**
     * Render the feed items page
     */
    public static function render_page(): void {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'athena-ai'));
        }
        
        // Check if tables exist
        if (!DatabaseSetup::tables_exist()) {
            // Try to create tables
            DatabaseSetup::setup_tables();
            
            // Check again
            if (!DatabaseSetup::tables_exist()) {
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
        
        $items_per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $items_per_page;
        
        // Get feed items with feed info
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
     * Handle the manual feed fetch AJAX request
     */
    public static function handle_manual_fetch(): void {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'athena-ai')]);
            return;
        }
        
        // Check if tables exist
        if (!\AthenaAI\Database\DatabaseSetup::tables_exist()) {
            // Try to create tables
            \AthenaAI\Database\DatabaseSetup::setup_tables();
            
            // Check again
            if (!\AthenaAI\Database\DatabaseSetup::tables_exist()) {
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
}
