<?php
/**
 * Feed Fetcher class
 * 
 * @package AthenaAI\Admin
 */

namespace AthenaAI\Admin;

use AthenaAI\Models\Feed;

/**
 * FeedFetcher class
 */
class FeedFetcher {
    
    /**
     * Initialize the feed fetcher
     */
    public static function init(): void {
        // Register the feed fetch action
        add_action('athena_fetch_feeds', [self::class, 'fetch_all_feeds']);
        
        // Register the cron event if not already registered
        if (!wp_next_scheduled('athena_fetch_feeds')) {
            wp_schedule_event(time(), 'hourly', 'athena_fetch_feeds');
        }
        
        // Add custom cron schedules
        add_filter('cron_schedules', [self::class, 'add_cron_schedules']);
    }
    
    /**
     * Add custom cron schedules
     * 
     * @param array $schedules Existing cron schedules
     * @return array Modified cron schedules
     */
    public static function add_cron_schedules(array $schedules): array {
        // Add a 15-minute schedule
        $schedules['athena_15min'] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => __('Every 15 minutes', 'athena-ai')
        ];
        
        // Add a 30-minute schedule
        $schedules['athena_30min'] = [
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display' => __('Every 30 minutes', 'athena-ai')
        ];
        
        // Add a 2-hour schedule
        $schedules['athena_2hours'] = [
            'interval' => 2 * HOUR_IN_SECONDS,
            'display' => __('Every 2 hours', 'athena-ai')
        ];
        
        // Add a 6-hour schedule
        $schedules['athena_6hours'] = [
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __('Every 6 hours', 'athena-ai')
        ];
        
        // Add a 12-hour schedule
        $schedules['athena_12hours'] = [
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => __('Every 12 hours', 'athena-ai')
        ];
        
        return $schedules;
    }
    
    /**
     * Fetch all feeds
     * 
     * @param bool $force_fetch Whether to force fetch all feeds regardless of their update interval
     * @return array Array with success and error counts
     */
    public static function fetch_all_feeds(bool $force_fetch = false): array {
        global $wpdb;
        
        // Ensure required tables exist
        if (!self::ensure_required_tables()) {
            return ['success' => 0, 'error' => 0, 'message' => 'Required tables do not exist'];
        }
        
        // Get feeds to update
        if ($force_fetch) {
            // Get all active feeds if force fetching
            $feeds = get_posts([
                'post_type' => 'athena-feed',
                'post_status' => 'publish',
                'numberposts' => -1,
                'meta_query' => [
                    [
                        'key' => '_athena_feed_active',
                        'value' => '1',
                        'compare' => '='
                    ]
                ]
            ]);
        } else {
            // Get only feeds that need updating based on their interval
            $feeds = self::get_feeds_due_for_update();
        }
        
        $success_count = 0;
        $error_count = 0;
        $messages = [];
        
        // Process each feed
        foreach ($feeds as $feed_post) {
            // Ensure feed metadata exists
            self::ensure_feed_metadata_exists($feed_post->ID);
            
            // Get feed object and fetch content
            $feed = Feed::get_by_id($feed_post->ID);
            
            if ($feed && $feed->fetch()) {
                $success_count++;
            } else {
                $error_count++;
                $messages[] = sprintf('Failed to fetch feed: %s', get_the_title($feed_post->ID));
            }
            
            // Add a small delay to prevent overwhelming external servers
            usleep(500000); // 0.5 second delay
        }
        
        // Update last fetch time
        update_option('athena_last_feed_fetch', time());
        
        // Log the results
        $log_message = sprintf(
            'Athena AI: Fetched %d feeds successfully, %d failed.',
            $success_count,
            $error_count
        );
        error_log($log_message);
        
        return [
            'success' => $success_count,
            'error' => $error_count,
            'message' => $log_message,
            'details' => $messages
        ];
    }
    
    /**
     * Ensure all required tables exist
     * 
     * @return bool True if all required tables exist or were created successfully
     */
    private static function ensure_required_tables(): bool {
        global $wpdb;
        
        // Check if the required tables exist
        $metadata_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'");
        $items_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_raw_items'");
        $errors_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_errors'");
        
        // If all tables exist, we're good
        if ($metadata_exists && $items_exists && $errors_exists) {
            return true;
        }
        
        // Otherwise, create the missing tables
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create feed_metadata table if it doesn't exist
        if (!$metadata_exists) {
            $sql = "CREATE TABLE {$wpdb->prefix}feed_metadata (
                feed_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                last_fetched DATETIME DEFAULT NULL,
                fetch_interval INT DEFAULT 3600,
                fetch_count INT DEFAULT 0,
                last_error_date DATETIME DEFAULT NULL,
                last_error_message TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) $charset_collate;";
            
            dbDelta($sql);
        }
        
        // Create feed_raw_items table if it doesn't exist
        if (!$items_exists) {
            $sql = "CREATE TABLE {$wpdb->prefix}feed_raw_items (
                item_hash CHAR(32) PRIMARY KEY,
                feed_id BIGINT UNSIGNED NOT NULL,
                raw_content LONGTEXT,
                pub_date DATETIME,
                guid VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (feed_id),
                INDEX (pub_date)
            ) $charset_collate;";
            
            dbDelta($sql);
        }
        
        // Create feed_errors table if it doesn't exist
        if (!$errors_exists) {
            $sql = "CREATE TABLE {$wpdb->prefix}feed_errors (
                error_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                feed_id BIGINT UNSIGNED NOT NULL,
                error_code VARCHAR(32),
                error_message TEXT,
                created DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (feed_id),
                INDEX (created)
            ) $charset_collate;";
            
            dbDelta($sql);
        }
        
        // Check if all tables were created successfully
        $metadata_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'");
        $items_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_raw_items'");
        $errors_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_errors'");
        
        return $metadata_exists && $items_exists && $errors_exists;
    }
    
    /**
     * Ensures that a feed metadata entry exists for the given feed ID
     * 
     * @param int $feed_id The feed ID (post_id)
     * @return bool True if the metadata exists or was created successfully
     */
    private static function ensure_feed_metadata_exists(int $feed_id): bool {
        global $wpdb;
        
        // Check if entry already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT feed_id FROM {$wpdb->prefix}feed_metadata WHERE feed_id = %d",
                $feed_id
            )
        );
        
        // If no entry exists, create one
        if (!$exists) {
            // Get feed update interval from post meta
            $update_interval = get_post_meta($feed_id, '_athena_feed_update_interval', true);
            if (!$update_interval) {
                $update_interval = 3600; // Default: 1 hour
            }
            
            $now = current_time('mysql');
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'feed_metadata',
                [
                    'feed_id' => $feed_id,
                    'last_fetched' => $now,
                    'fetch_interval' => $update_interval,
                    'fetch_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now
                ],
                ['%d', '%s', '%d', '%d', '%s', '%s']
            );
            
            return $result !== false;
        }
        
        return true;
    }
    
    /**
     * Get feeds that are due for an update based on their fetch interval
     * 
     * @return array Array of feed post objects
     */
    private static function get_feeds_due_for_update(): array {
        global $wpdb;
        
        // Get current time
        $now = current_time('mysql');
        
        // Find feeds that need updating
        $query = $wpdb->prepare(
            "SELECT f.feed_id 
            FROM {$wpdb->prefix}feed_metadata f
            JOIN {$wpdb->posts} p ON f.feed_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_athena_feed_active'
            WHERE p.post_type = 'athena-feed'
            AND p.post_status = 'publish'
            AND (pm.meta_value = '1' OR pm.meta_value IS NULL)
            AND (
                f.last_fetched IS NULL
                OR TIMESTAMPDIFF(SECOND, f.last_fetched, %s) > f.fetch_interval
            )",
            $now
        );
        
        $feed_ids = $wpdb->get_col($query);
        
        if (empty($feed_ids)) {
            return [];
        }
        
        // Get the actual feed posts
        return get_posts([
            'post_type' => 'athena-feed',
            'post_status' => 'publish',
            'numberposts' => -1,
            'include' => $feed_ids
        ]);
    }
}
