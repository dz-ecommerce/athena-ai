<?php
declare(strict_types=1);

namespace AthenaAI\Background;

if (!defined('ABSPATH')) {
    exit;
}

class FeedProcessor {
    public static function init(): void {
        add_action('init', [self::class, 'schedule_events']);
        add_action('athena_process_feeds', [self::class, 'process_feeds']);
    }

    public static function schedule_events(): void {
        if (!wp_next_scheduled('athena_process_feeds')) {
            wp_schedule_event(time(), 'hourly', 'athena_process_feeds');
        }
    }

    public static function process_feeds(): void {
        global $wpdb;

        // Get active feeds that need updating
        $feeds = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT feed_id FROM {$wpdb->prefix}feed_metadata 
                WHERE active = 1 
                AND (last_checked IS NULL OR last_checked < DATE_SUB(%s, INTERVAL update_interval SECOND))",
                current_time('mysql')
            )
        );

        foreach ($feeds as $feed_data) {
            $feed = \AthenaAI\Models\Feed::get_by_id((int)$feed_data->feed_id);
            if ($feed) {
                $feed->fetch();
            }
        }
    }
}
