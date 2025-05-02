<?php
declare(strict_types=1);

namespace AthenaAI\Background;

if (!defined('ABSPATH')) {
    exit();
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
        // Get feeds that need updating using the new method in Feed class
        $feeds = \AthenaAI\Models\Feed::get_feeds_to_update();

        $processed = 0;

        foreach ($feeds as $feed) {
            if ($feed->fetch()) {
                $processed++;
            }
        }

        // Update the last fetch time if any feeds were processed
        if ($processed > 0) {
            update_option('athena_last_feed_fetch', time());
        }
    }
}
