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
    }
    
    /**
     * Fetch all feeds
     */
    public static function fetch_all_feeds(): void {
        // Get all active feeds
        $feeds = get_posts([
            'post_type' => 'athena-feed',
            'post_status' => 'publish',
            'numberposts' => -1,
        ]);
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($feeds as $feed_post) {
            $feed = Feed::get_by_id($feed_post->ID);
            
            if ($feed && $feed->fetch()) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        // Update last fetch time
        update_option('athena_last_feed_fetch', time());
        
        // Log the results
        error_log(sprintf(
            'Athena AI: Fetched %d feeds successfully, %d failed.',
            $success_count,
            $error_count
        ));
    }
}
