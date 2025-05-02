<?php
/**
 * Feed Cron Manager
 *
 * @package AthenaAI\Cron
 */

declare(strict_types=1);

namespace AthenaAI\Cron;

use AthenaAI\Services\FeedService;
use AthenaAI\Services\LoggerService;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Manages scheduled tasks for feed fetching and processing.
 */
class FeedCronManager {
    /**
     * Feed service.
     *
     * @var FeedService
     */
    private FeedService $feed_service;

    /**
     * Logger service.
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Cron hook name for prefetching.
     *
     * @var string
     */
    const PREFETCH_HOOK = 'athena_feed_prefetch_cron';

    /**
     * Cron hook name for processing.
     *
     * @var string
     */
    const PROCESS_HOOK = 'athena_feed_process_cron';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->feed_service = FeedService::create();
        $this->logger = LoggerService::getInstance()->setComponent('FeedCronManager');

        // Registriere die Cron-Hooks
        add_action(self::PREFETCH_HOOK, [$this, 'prefetch_feeds']);
        add_action(self::PROCESS_HOOK, [$this, 'process_cached_feeds']);

        // Registriere die Aktivierungs- und Deaktivierungshooks
        register_activation_hook(ATHENA_AI_PLUGIN_FILE, [$this, 'schedule_events']);
        register_deactivation_hook(ATHENA_AI_PLUGIN_FILE, [$this, 'clear_scheduled_events']);
    }

    /**
     * Factory-Methode zum Erstellen einer FeedCronManager-Instanz.
     *
     * @return FeedCronManager
     */
    public static function create(): FeedCronManager {
        return new self();
    }

    /**
     * Initialisiert die FeedCronManager-Klasse.
     */
    public function init() {
        // Registriere Aktivierungs- und Deaktivierungshooks
        if (!wp_next_scheduled(self::PREFETCH_HOOK)) {
            $this->schedule_events();
        }
    }

    /**
     * Registriert die Cron-Events.
     */
    public function schedule_events() {
        $this->logger->info('Scheduling feed cron events');

        // Prefetching alle 30 Minuten
        if (!wp_next_scheduled(self::PREFETCH_HOOK)) {
            wp_schedule_event(time(), 'hourly', self::PREFETCH_HOOK);
            $this->logger->info('Scheduled feed prefetch event (hourly)');
        }

        // Verarbeitung alle 15 Minuten
        if (!wp_next_scheduled(self::PROCESS_HOOK)) {
            wp_schedule_event(time(), 'twicehourly', self::PROCESS_HOOK);
            $this->logger->info('Scheduled feed processing event (twice hourly)');
        }

        // Registriere benutzerdefinierte Zeitpläne, falls nicht vorhanden
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);
    }

    /**
     * Fügt benutzerdefinierte Cron-Zeitpläne hinzu.
     *
     * @param array $schedules Bestehende Zeitpläne.
     * @return array Aktualisierte Zeitpläne.
     */
    public function add_cron_schedules($schedules) {
        if (!isset($schedules['twicehourly'])) {
            $schedules['twicehourly'] = [
                'interval' => 1800, // 30 Minuten
                'display' => __('Twice Hourly', 'athena-ai'),
            ];
        }

        return $schedules;
    }

    /**
     * Entfernt die geplanten Cron-Events.
     */
    public function clear_scheduled_events() {
        $this->logger->info('Clearing feed cron events');

        $timestamp = wp_next_scheduled(self::PREFETCH_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::PREFETCH_HOOK);
            $this->logger->info('Unscheduled feed prefetch event');
        }

        $timestamp = wp_next_scheduled(self::PROCESS_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::PROCESS_HOOK);
            $this->logger->info('Unscheduled feed processing event');
        }
    }

    /**
     * Führt das Prefetching aller Feeds durch.
     */
    public function prefetch_feeds() {
        $this->logger->info('Cron: Starting feed prefetching');

        global $wpdb;

        // Hole alle Feed-Einträge aus der Datenbank
        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
                'athena_feed',
                'publish'
            )
        );

        if (empty($posts)) {
            $this->logger->info('Cron: No feeds found in database');
            return;
        }

        $this->logger->info('Cron: Found ' . count($posts) . ' feeds to prefetch');

        $success_count = 0;
        $error_count = 0;

        foreach ($posts as $post) {
            $feed_url = get_post_meta($post->ID, '_feed_url', true);

            if (empty($feed_url)) {
                $this->logger->warn('Cron: Feed ID ' . $post->ID . ' has no URL');
                continue;
            }

            $this->logger->info('Cron: Prefetching feed: ' . $feed_url);

            if ($this->feed_service->prefetchFeed($feed_url, false)) {
                $success_count++;
                $this->logger->info('Cron: Successfully prefetched feed: ' . $feed_url);
            } else {
                $error_count++;
                $this->logger->error('Cron: Failed to prefetch feed: ' . $feed_url);
            }
        }

        $this->logger->info(
            'Cron: Prefetching completed. Success: ' . $success_count . ', Errors: ' . $error_count
        );
    }

    /**
     * Verarbeitet alle zwischengespeicherten Feeds.
     */
    public function process_cached_feeds() {
        $this->logger->info('Cron: Starting cached feed processing');

        $results = $this->feed_service->processCachedFeeds(false);

        $this->logger->info(
            'Cron: Processing completed. Success: ' .
                $results['success'] .
                ', Errors: ' .
                $results['failed']
        );

        if (!empty($results['errors'])) {
            foreach ($results['errors'] as $error) {
                $this->logger->error('Cron: ' . $error);
            }
        }
    }
}
