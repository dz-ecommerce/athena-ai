<?php
/**
 * Cron Scheduler service
 *
 * Handles registration of custom intervals and (re‑)scheduling of the
 * "athena_fetch_feeds" cron event.  Extracted from FeedFetcher for better
 * modularity and easier maintenance.
 *
 * @package AthenaAI\Services
 */

declare(strict_types=1);

namespace AthenaAI\Services;

/**
 * Class CronScheduler
 */
class CronScheduler {

    /**
     * Bootstrap the service.
     */
    public static function init(): void {
        // Register custom intervals early.
        add_filter('cron_schedules', [self::class, 'add_cron_schedules']);

        // Ensure the feed‑fetch job is scheduled every page load (runs only once
        // per request).
        add_action('init', [self::class, 'ensure_feed_fetch_scheduled'], 5);

        // Extra safety: if the cron got removed, add it back when an admin page
        // loads.
        add_action('admin_init', [self::class, 'ensure_feed_fetch_scheduled']);
    }

    /* --------------------------------------------------------------------- */
    /*  Public helpers                                                       */
    /* --------------------------------------------------------------------- */

    /**
     * Register additional cron schedules used by Athena AI.
     *
     * @param array $schedules Existing schedules.
     * @return array Modified schedules.
     */
    public static function add_cron_schedules(array $schedules): array {
        // Verwende einfache Strings anstelle von Übersetzungsfunktionen, um das zu frühe Laden der Textdomain zu vermeiden
        // Die Anzeige dieser Texte erfolgt nur im Admin-Bereich und ist nicht kritisch für die Funktionalität
        $schedules['athena_1min'] = [
            'interval' => MINUTE_IN_SECONDS,
            'display'  => 'Every Minute',
        ];

        $schedules['athena_5min'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => 'Every 5 Minutes',
        ];

        $schedules['athena_15min'] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => 'Every 15 Minutes',
        ];

        $schedules['athena_30min'] = [
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display'  => 'Every 30 Minutes',
        ];

        $schedules['athena_45min'] = [
            'interval' => 45 * MINUTE_IN_SECONDS,
            'display'  => 'Every 45 Minutes',
        ];

        $schedules['athena_2hours'] = [
            'interval' => 2 * HOUR_IN_SECONDS,
            'display'  => 'Every 2 Hours',
        ];

        $schedules['athena_6hours'] = [
            'interval' => 6 * HOUR_IN_SECONDS,
            'display'  => 'Every 6 Hours',
        ];

        $schedules['athena_12hours'] = [
            'interval' => 12 * HOUR_IN_SECONDS,
            'display'  => 'Every 12 Hours',
        ];

        return $schedules;
    }

    /**
     * Make sure the feed‑fetch cron event is registered with the configured
     * interval.  Reschedules automatically if the interval was changed.
     */
    public static function ensure_feed_fetch_scheduled(): void {
        $interval         = get_option('athena_ai_feed_cron_interval', 'hourly');
        $timestamp        = wp_next_scheduled('athena_fetch_feeds');
        $current_interval = wp_get_schedule('athena_fetch_feeds');

        // If not scheduled or interval changed → reschedule.
        if (!$timestamp || $current_interval !== $interval) {
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'athena_fetch_feeds');
            }

            wp_schedule_event(time(), $interval, 'athena_fetch_feeds');

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Athena AI: Feed fetch cron job scheduled with interval: {$interval}");
            }
        }
    }

    /**
     * Convenience: remove the feed fetch event (e.g. on plugin de‑activation).
     */
    public static function clear_feed_fetch(): void {
        $timestamp = wp_next_scheduled('athena_fetch_feeds');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'athena_fetch_feeds');
        }
    }
}
