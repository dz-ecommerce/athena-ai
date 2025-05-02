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

use AthenaAI\Services\LoggerService;

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

        // Force re-scheduling of cron job on plugin activation
        add_action('admin_init', [self::class, 'debug_cron_health']);
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
            'display' => 'Every Minute',
        ];

        $schedules['athena_5min'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => 'Every 5 Minutes',
        ];

        $schedules['athena_15min'] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => 'Every 15 Minutes',
        ];

        $schedules['athena_30min'] = [
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display' => 'Every 30 Minutes',
        ];

        $schedules['athena_45min'] = [
            'interval' => 45 * MINUTE_IN_SECONDS,
            'display' => 'Every 45 Minutes',
        ];

        $schedules['athena_2hours'] = [
            'interval' => 2 * HOUR_IN_SECONDS,
            'display' => 'Every 2 Hours',
        ];

        $schedules['athena_6hours'] = [
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => 'Every 6 Hours',
        ];

        $schedules['athena_12hours'] = [
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => 'Every 12 Hours',
        ];

        return $schedules;
    }

    /**
     * Debug cron health and fix any issues
     */
    public static function debug_cron_health(): void {
        // Only run for admin users
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if we're running this function too frequently
        $last_check = get_option('athena_cron_health_check', 0);
        if (time() - $last_check < 60) {
            return; // Don't run more than once per minute
        }

        // Update the last check time
        update_option('athena_cron_health_check', time());

        // Get the interval setting
        $interval = get_option('athena_ai_feed_cron_interval', 'hourly');

        // Get the current scheduled time
        $timestamp = wp_next_scheduled('athena_fetch_feeds');

        // Get the scheduled interval
        $current_schedule = wp_get_schedule('athena_fetch_feeds');

        // Is the cron event scheduled and is the interval correct?
        $logger = LoggerService::getInstance()->setComponent('CronScheduler');

        if (!$timestamp) {
            $logger->warn(
                'Feed fetch cron event not scheduled. Scheduling now with interval: ' . $interval
            );

            // Schedule the event
            wp_schedule_event(time(), $interval, 'athena_fetch_feeds');

            // Get the new timestamp to verify
            $new_timestamp = wp_next_scheduled('athena_fetch_feeds');

            if ($new_timestamp) {
                $logger->info(
                    'Feed fetch cron event scheduled successfully for: ' .
                        date('Y-m-d H:i:s', (int) $new_timestamp)
                );
            } else {
                $logger->error('Failed to schedule feed fetch cron event');
            }
        } elseif ($current_schedule !== $interval) {
            $logger->warn(
                'Feed fetch cron interval mismatch. Current: ' .
                    $current_schedule .
                    ', Expected: ' .
                    $interval .
                    '. Re-scheduling.'
            );

            // Remove the existing schedule
            wp_unschedule_event($timestamp, 'athena_fetch_feeds');

            // Schedule with the correct interval
            wp_schedule_event(time(), $interval, 'athena_fetch_feeds');

            // Get the new timestamp to verify
            $new_timestamp = wp_next_scheduled('athena_fetch_feeds');

            if ($new_timestamp) {
                $logger->info(
                    'Feed fetch cron event re-scheduled successfully for: ' .
                        date('Y-m-d H:i:s', (int) $new_timestamp)
                );
            } else {
                $logger->error('Failed to re-schedule feed fetch cron event');
            }
        } else {
            // Everything seems fine, log that
            $logger->debug(
                'Feed fetch cron is healthy. Next scheduled run: ' .
                    date('Y-m-d H:i:s', (int) $timestamp) .
                    ' with interval: ' .
                    $interval
            );
        }

        // Check if WordPress cron is being triggered correctly
        $doing_cron = get_transient('doing_cron');
        if (!$doing_cron) {
            $last_cron_time = get_option('athena_last_cron_time', 0);
            $current_time = time();

            if ($current_time - $last_cron_time > 3600) {
                // If no cron for more than an hour
                $logger->warn(
                    'WordPress cron may not be running correctly. Last run: ' .
                        ($last_cron_time ? date('Y-m-d H:i:s', (int) $last_cron_time) : 'Never')
                );

                // Check if an alternative WP Cron is needed
                if (!defined('ALTERNATE_WP_CRON') || !ALTERNATE_WP_CRON) {
                    $logger->info(
                        'Consider adding define("ALTERNATE_WP_CRON", true); to wp-config.php'
                    );
                }

                // Force a cron run
                spawn_cron();

                update_option('athena_last_cron_time', $current_time);
            }
        }
    }

    /**
     * Make sure the feed‑fetch cron event is registered with the configured
     * interval.  Reschedules automatically if the interval was changed.
     */
    public static function ensure_feed_fetch_scheduled(): void {
        $interval = get_option('athena_ai_feed_cron_interval', 'hourly');
        $timestamp = wp_next_scheduled('athena_fetch_feeds');
        $current_interval = wp_get_schedule('athena_fetch_feeds');

        // Debug logging
        $debug_mode = get_option('athena_ai_enable_debug_mode', false);
        if ($debug_mode) {
            $logger = LoggerService::getInstance()->setComponent('CronScheduler');
            $logger->debug(
                'Ensuring feed fetch is scheduled. Current timestamp: ' .
                    ($timestamp ? date('Y-m-d H:i:s', (int) $timestamp) : 'None') .
                    ', Current interval: ' .
                    ($current_interval ?: 'None') .
                    ", Expected interval: {$interval}"
            );
        }

        // If not scheduled or interval changed → reschedule.
        if (!$timestamp || $current_interval !== $interval) {
            if ($timestamp) {
                // Make sure we clear the old event
                wp_clear_scheduled_hook('athena_fetch_feeds');
            }

            // Schedule the new event
            $result = wp_schedule_event(time(), $interval, 'athena_fetch_feeds');

            if ($debug_mode) {
                $new_timestamp = wp_next_scheduled('athena_fetch_feeds');
                $logger->info(
                    'Feed fetch cron job ' .
                        ($timestamp ? 're-' : '') .
                        "scheduled with interval: {$interval}" .
                        ($new_timestamp
                            ? ', next run at: ' . date('Y-m-d H:i:s', (int) $new_timestamp)
                            : ', scheduling failed')
                );

                if ($result === false) {
                    $logger->error(
                        'Failed to schedule cron event. WP Error: ' .
                            print_r(error_get_last(), true)
                    );
                }
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
