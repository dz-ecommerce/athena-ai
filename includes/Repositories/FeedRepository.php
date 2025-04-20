<?php
/**
 * Feed Repository class
 *
 * @package AthenaAI\Repositories
 */

declare(strict_types=1);

namespace AthenaAI\Repositories;

use AthenaAI\Models\Feed;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repository class for Feed data access operations.
 */
class FeedRepository {
    /**
     * Get a feed by its post ID
     * 
     * @param int $post_id The post ID
     * @return Feed|null The feed object or null if not found
     */
    public function get_by_id(int $post_id): ?Feed {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'athena-feed') {
            return null;
        }
        
        $url = get_post_meta($post_id, '_athena_feed_url', true);
        if (empty($url)) {
            return null;
        }
        
        $update_interval = (int) get_post_meta($post_id, '_athena_feed_update_interval', true);
        $active = get_post_meta($post_id, '_athena_feed_active', true) !== '0';
        
        $feed = new Feed($url, $update_interval, $active);
        $feed->set_id($post_id);
        
        // Load last checked time if available
        $last_checked = get_post_meta($post_id, '_athena_feed_last_checked', true);
        if ($last_checked) {
            try {
                $datetime = new \DateTime($last_checked);
                $feed->set_last_checked($datetime);
            } catch (\Exception $e) {
                // Invalid datetime format, ignore
            }
        }
        
        return $feed;
    }
    
    /**
     * Get all active feeds
     * 
     * @return array Array of Feed objects
     */
    public function get_all_active(): array {
        $feeds = [];
        
        $posts = get_posts([
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
        
        foreach ($posts as $post) {
            $feed = $this->get_by_id($post->ID);
            if ($feed) {
                $feeds[] = $feed;
            }
        }
        
        return $feeds;
    }
    
    /**
     * Get feeds that need to be updated
     * 
     * @return array Array of Feed objects
     */
    public function get_feeds_to_update(): array {
        $feeds = [];
        $current_time = current_time('timestamp');
        
        $posts = get_posts([
            'post_type' => 'athena-feed',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_athena_feed_active',
                    'value' => '1',
                    'compare' => '='
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => '_athena_feed_last_checked',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => '_athena_feed_last_checked',
                        'value' => date('Y-m-d H:i:s', $current_time - 3600), // Default 1 hour
                        'compare' => '<',
                        'type' => 'DATETIME'
                    ]
                ]
            ]
        ]);
        
        foreach ($posts as $post) {
            $feed = $this->get_by_id($post->ID);
            if ($feed) {
                $feeds[] = $feed;
            }
        }
        
        return $feeds;
    }
    
    /**
     * Ensure feed metadata exists in the database
     * 
     * @param Feed $feed The feed to check
     * @return bool Whether the metadata exists or was created successfully
     */
    public function ensure_feed_metadata_exists(Feed $feed): bool {
        global $wpdb;
        
        if (!$feed->get_id()) {
            return false;
        }
        
        $feed_id = $feed->get_id();
        $metadata_table = $wpdb->prefix . 'feed_metadata';
        
        // Check if the feed metadata already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$metadata_table} WHERE feed_id = %d",
                $feed_id
            )
        );
        
        if ($exists) {
            return true;
        }
        
        // Insert new metadata record
        $result = $wpdb->insert(
            $metadata_table,
            [
                'feed_id' => $feed_id,
                'last_fetched' => null,
                'error' => false,
                'last_error' => '',
            ],
            [
                '%d',
                '%s',
                '%d',
                '%s'
            ]
        );
        
        return $result !== false;
    }
    
    /**
     * Update feed metadata
     * 
     * @param Feed $feed      The feed to update
     * @param int  $new_items Number of new items
     * @return bool Whether the update was successful
     */
    public function update_feed_metadata(Feed $feed, int $new_items): bool {
        global $wpdb;
        
        if (!$feed->get_id()) {
            return false;
        }
        
        // Ensure metadata exists
        if (!$this->ensure_feed_metadata_exists($feed)) {
            return false;
        }
        
        $feed_id = $feed->get_id();
        $metadata_table = $wpdb->prefix . 'feed_metadata';
        
        // Update the metadata
        $result = $wpdb->update(
            $metadata_table,
            [
                'last_fetched' => current_time('mysql'),
                'error' => false,
                'last_error' => '',
                'last_fetch_items_count' => $new_items
            ],
            ['feed_id' => $feed_id],
            [
                '%s',
                '%d',
                '%s',
                '%d'
            ],
            ['%d']
        );
        
        // Update post meta for last checked time
        $now = new \DateTime();
        $feed->set_last_checked($now);
        update_post_meta($feed_id, '_athena_feed_last_checked', $now->format('Y-m-d H:i:s'));
        
        return $result !== false;
    }
    
    /**
     * Update feed metadata with error information
     * 
     * @param Feed   $feed    The feed to update
     * @param string $code    The error code
     * @param string $message The error message
     * @return bool Whether the update was successful
     */
    public function update_feed_error(Feed $feed, string $code, string $message): bool {
        global $wpdb;
        
        if (!$feed->get_id()) {
            return false;
        }
        
        // Ensure metadata exists
        if (!$this->ensure_feed_metadata_exists($feed)) {
            return false;
        }
        
        $feed_id = $feed->get_id();
        $metadata_table = $wpdb->prefix . 'feed_metadata';
        
        // Create an error message with code and timestamp
        $error_message = sprintf(
            '[%s] %s - %s',
            $code,
            current_time('mysql'),
            $message
        );
        
        // Update the metadata
        $result = $wpdb->update(
            $metadata_table,
            [
                'last_fetched' => current_time('mysql'),
                'error' => true,
                'last_error' => $error_message
            ],
            ['feed_id' => $feed_id],
            [
                '%s',
                '%d',
                '%s'
            ],
            ['%d']
        );
        
        // Update post meta for last checked time
        $now = new \DateTime();
        $feed->set_last_checked($now);
        update_post_meta($feed_id, '_athena_feed_last_checked', $now->format('Y-m-d H:i:s'));
        
        return $result !== false;
    }
}
