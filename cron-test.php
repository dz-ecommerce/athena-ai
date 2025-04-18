<?php
/**
 * WordPress Cron Test Script
 * 
 * This script checks the WordPress cron system and the Athena AI feed fetcher cron event.
 */

// Load WordPress
require_once('wp-load.php');

// Check if WordPress cron is disabled
if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
    echo "WordPress cron is disabled in wp-config.php\n";
} else {
    echo "WordPress cron is enabled\n";
}

// Check if the Athena feed fetcher cron event is scheduled
$next_scheduled = wp_next_scheduled('athena_fetch_feeds');
if ($next_scheduled) {
    echo "Athena feed fetcher is scheduled to run at: " . date('Y-m-d H:i:s', $next_scheduled) . "\n";
    echo "Current server time is: " . date('Y-m-d H:i:s', time()) . "\n";
    
    // Calculate time difference
    $time_diff = $next_scheduled - time();
    if ($time_diff > 0) {
        echo "Next run in: " . gmdate("H:i:s", $time_diff) . "\n";
    } else {
        echo "Event is overdue by: " . gmdate("H:i:s", abs($time_diff)) . "\n";
    }
} else {
    echo "Athena feed fetcher is NOT scheduled!\n";
}

// List all scheduled cron events
echo "\nAll scheduled cron events:\n";
$cron_array = _get_cron_array();
if (!empty($cron_array)) {
    foreach ($cron_array as $timestamp => $cron_hooks) {
        foreach ($cron_hooks as $hook => $events) {
            echo date('Y-m-d H:i:s', $timestamp) . " - " . $hook . "\n";
        }
    }
} else {
    echo "No cron events scheduled.\n";
}

// Check if the feed fetcher class is properly initialized
echo "\nChecking if FeedFetcher is initialized:\n";
if (class_exists('\AthenaAI\Admin\FeedFetcher')) {
    echo "FeedFetcher class exists\n";
    
    // Try to manually trigger the feed fetch
    echo "\nAttempting to manually fetch feeds:\n";
    $result = \AthenaAI\Admin\FeedFetcher::fetch_all_feeds(true);
    if (is_array($result)) {
        echo "Fetch result: " . print_r($result, true) . "\n";
    } else {
        echo "Fetch failed or returned unexpected result\n";
    }
} else {
    echo "FeedFetcher class does not exist or is not loaded\n";
}
