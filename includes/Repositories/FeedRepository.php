<?php
/**
 * Feed Repository class
 *
 * @package AthenaAI\Repositories
 */

declare(strict_types=1);

namespace AthenaAI\Repositories;

use AthenaAI\Models\Feed;
use AthenaAI\Services\LoggerService;
use DateTime;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FeedRepository-Klasse für die Verwaltung von Feed-Datensätzen
 *
 * Diese Klasse stellt Methoden zum Speichern, Aktualisieren, Abrufen und Entfernen von
 * Feed-Einträgen in der WordPress-Datenbank bereit. Sie verwendet WordPress-Funktionen
 * aus dem globalen Namespace, was durch den führenden Backslash (\) gekennzeichnet ist.
 *
 * @since 1.0.0
 */

/**
 * Repository class for Feed data access operations.
 */
class FeedRepository {
    private $logger;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->logger = LoggerService::getInstance()->setComponent('FeedRepository');
    }
    
    /**
     * Speichert einen Feed in der Datenbank
     * 
     * @param Feed $feed Der zu speichernde Feed
     * @return bool|int Bei Erfolg die Post-ID, sonst false
     */
    public function save(Feed $feed) {
        // Sanitize URL
        $url = \esc_url_raw($feed->get_url());
        
        // Erstelle einen neuen Post
        $post_id = \wp_insert_post([
            'post_title' => $feed->generate_title(),
            'post_type' => 'athena-feed',
            'post_status' => 'publish'
        ]);
        
        if (\is_wp_error($post_id)) {
            $this->logger->error(sprintf('Failed to create feed post: %s', $post_id->get_error_message()));
            return false;
        }
        
        // Setze Post-Meta
        \update_post_meta($post_id, '_athena_feed_url', $url);
        \update_post_meta($post_id, '_athena_feed_update_interval', $feed->get_update_interval());
        \update_post_meta($post_id, '_athena_feed_active', $feed->is_active() ? '1' : '0');
        
        // Aktualisiere die post_id im Feed-Objekt
        $feed->set_post_id($post_id);
        
        $this->logger->info(sprintf('Created new feed with ID %d: %s', $post_id, $url));
        
        return $post_id;
    }
    
    /**
     * Aktualisiert einen bestehenden Feed in der Datenbank
     * 
     * @param Feed $feed Der zu aktualisierende Feed
     * @return bool Erfolg oder Misserfolg
     */
    public function update(Feed $feed): bool {
        $post_id = $feed->get_post_id();
        
        if (empty($post_id)) {
            $this->logger->error('Cannot update feed - no post ID provided');
            return false;
        }
        
        // Sanitize URL
        $url = \esc_url_raw($feed->get_url());
        
        // Aktualisiere Post-Meta
        \update_post_meta($post_id, '_athena_feed_url', $url);
        \update_post_meta($post_id, '_athena_feed_update_interval', $feed->get_update_interval());
        \update_post_meta($post_id, '_athena_feed_active', $feed->is_active() ? '1' : '0');
        
        $this->logger->info(sprintf('Updated feed with ID %d', $post_id));
        
        return true;
    }
    
    /**
     * Get a feed by its post ID
     * 
     * @param int $post_id The post ID
     * @return Feed|null The feed object or null if not found
     */
    public function get_by_id(int $post_id): ?Feed {
        $post = \get_post($post_id);
        
        if (!$post || $post->post_type !== 'athena-feed') {
            return null;
        }
        
        $url = \get_post_meta($post_id, '_athena_feed_url', true);
        if (empty($url)) {
            return null;
        }
        
        $update_interval = (int) \get_post_meta($post_id, '_athena_feed_update_interval', true);
        $active = \get_post_meta($post_id, '_athena_feed_active', true) !== '0';
        
        $feed = new Feed($url, $update_interval, $active);
        $feed->set_post_id($post_id);
        
        // Load last checked time if available
        $last_checked = \get_post_meta($post_id, '_athena_feed_last_checked', true);
        if ($last_checked) {
            try {
                $datetime = new DateTime($last_checked);
                $feed->set_last_checked($datetime);
            } catch (Exception $e) {
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
        
        $posts = \get_posts([
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
        $current_time = \current_time('timestamp');
        
        $posts = \get_posts([
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
                        'value' => \date('Y-m-d H:i:s', \current_time('timestamp') - 3600), // Default 1 hour
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
        
        if (!$feed->get_post_id()) {
            return false;
        }
        
        $feed_id = $feed->get_post_id();
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
        
        if (!$feed->get_post_id()) {
            return false;
        }
        
        // Ensure metadata exists
        if (!$this->ensure_feed_metadata_exists($feed)) {
            return false;
        }
        
        $feed_id = $feed->get_post_id();
        $metadata_table = $wpdb->prefix . 'feed_metadata';
        
        // Update the metadata
        $result = $wpdb->update(
            $metadata_table,
            [
                'last_fetched' => \current_time('mysql'),
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
        
        // Get current time as MySQL timestamp
        $now = new DateTime(\current_time('mysql'));
        $feed->set_last_checked($now);
        \update_post_meta($feed_id, '_athena_feed_last_checked', $now->format('Y-m-d H:i:s'));
        
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
        
        if (!$feed->get_post_id()) {
            return false;
        }
        
        // Ensure metadata exists
        if (!$this->ensure_feed_metadata_exists($feed)) {
            return false;
        }
        
        $feed_id = $feed->get_post_id();
        $metadata_table = $wpdb->prefix . 'feed_metadata';
        
        // Create an error message with code and timestamp
        $error_message = sprintf(
            '[%s] %s - %s',
            $code,
            \current_time('mysql'),
            $message
        );
        
        // Update the metadata
        $result = $wpdb->update(
            $metadata_table,
            [
                'last_fetched' => \current_time('mysql'),
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
        
        // Get current time as MySQL timestamp
        $now = new DateTime(\current_time('mysql'));
        $feed->set_last_checked($now);
        \update_post_meta($feed_id, '_athena_feed_last_checked', $now->format('Y-m-d H:i:s'));
        
        // Also log the error to the feed_errors table
        $this->log_error($feed, $code, $message);
        
        return $result !== false;
    }

    /**
     * Log an error for a feed
     * 
     * @param Feed   $feed    The feed to log an error for
     * @param string $code    The error code
     * @param string $message The error message
     * @return void
     */
    public function log_error(Feed $feed, string $code, string $message): void {
        global $wpdb;
        
        // First check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_errors'");
        if (!$table_exists) {
            // Log to WordPress error log instead
            \error_log("Athena AI Feed Error ({$code}): {$message}");
            return;
        }
        
        try {
            // Nur in die Datenbank schreiben, wenn feed ID gesetzt ist
            if ($feed->get_post_id() !== null) {
                $data = [
                    'feed_id' => $feed->get_post_id(),
                    'error_code' => $code,
                    'error_message' => $message,
                    'created_at' => \current_time('mysql')
                ];
                $formats = ['%d', '%s', '%s', '%s'];
                
                $wpdb->insert($wpdb->prefix . 'feed_errors', $data, $formats);
                
                if ($wpdb->last_error) {
                    \error_log("Athena AI: Error logging feed error: {$wpdb->last_error}");
                    \error_log("Athena AI Feed Error ({$code}): {$message}");
                }
            }
        } catch (\Exception $e) {
            // Log exception to WordPress error log
            \error_log("Athena AI: Exception logging feed error: {$e->getMessage()}");
            \error_log("Athena AI Feed Error ({$code}): {$message}");
        }
        
        // Always log to WordPress error log
        $feed_id_info = $feed->get_post_id() !== null ? "(Feed ID: {$feed->get_post_id()})" : "(URL: {$feed->get_url()})"; 
        \error_log(sprintf(
            'Athena AI Feed Error [%s]: %s %s',
            $code,
            $message,
            $feed_id_info
        ));
    }

    /**
     * Save a feed item to the database
     * 
     * @param Feed $feed The feed the item belongs to
     * @param array $item The feed item data
     * @param string $guid The item's unique identifier
     * @param string $pub_date The item's publication date
     * @return bool Whether the save was successful
     */
    public function save_feed_item(Feed $feed, array $item, string $guid, string $pub_date): bool {
        global $wpdb;
        
        if (!$feed->get_post_id()) {
            return false;
        }
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_raw_items'");
        if (!$table_exists) {
            return false;
        }
        
        // Check if an item with this GUID already exists for this feed
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE feed_id = %d AND guid = %s",
            $feed->get_post_id(),
            $guid
        ));
        
        // If it exists, we'll consider it a success but not insert it again
        if ($existing) {
            return true;
        }
        
        // Prepare JSON content with error handling
        $json_content = \wp_json_encode($item, JSON_UNESCAPED_UNICODE);
        if ($json_content === false) {
            return false;
        }
        
        // Insert the data
        $result = $wpdb->insert(
            $wpdb->prefix . 'feed_raw_items',
            [
                'feed_id' => $feed->get_post_id(),
                'guid' => $guid,
                'pub_date' => $pub_date,
                'content' => $json_content,
                'created_at' => \current_time('mysql')
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );
        
        return $result !== false;
    }
    
    /**
     * Process and save multiple feed items
     * 
     * @param Feed $feed The feed the items belong to
     * @param array $items Array of feed items
     * @return array Returns statistics about the operation: ['processed' => int, 'new_items' => int, 'existing_items' => int, 'errors' => int]
     */
    public function process_feed_items(Feed $feed, array $items): array {
        global $wpdb;
        
        $stats = [
            'processed' => 0,
            'new_items' => 0,
            'existing_items' => 0,
            'errors' => 0
        ];
        
        if (!$feed->get_post_id()) {
            return $stats;
        }
        
        // Ensure feed metadata exists before processing items
        if (!$this->ensure_feed_metadata_exists($feed)) {
            return $stats;
        }
        
        // Check if the feed_raw_items table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_raw_items'");
        if (!$table_exists) {
            return $stats;
        }
        
        // Begin transaction for better database consistency
        $wpdb->query('START TRANSACTION');
        
        try {
            foreach ($items as $item) {
                $stats['processed']++;
                
                // Extract GUID - handle different feed formats with type safety
                $guid = '';
                
                // Try to find the GUID in the item
                if (isset($item['guid']) && !empty($item['guid'])) {
                    $guid = (string) $item['guid'];
                } elseif (isset($item['id']) && !empty($item['id'])) {
                    $guid = (string) $item['id'];
                } elseif (isset($item['link']) && !empty($item['link'])) {
                    $guid = (string) $item['link'];
                } elseif (isset($item['title']) && !empty($item['title'])) {
                    // Generate a GUID from the title if we can't find anything else
                    $guid = 'title-' . \md5((string) $item['title']);
                } else {
                    // Generate a random GUID as a last resort
                    $guid = 'feed-item-' . \uniqid();
                }
                
                // Extract publication date with a safe default
                $pub_date = \current_time('mysql');
                
                // Try to find the publication date in the item
                if (isset($item['pubDate']) && !empty($item['pubDate'])) {
                    $pub_date = (string) $item['pubDate'];
                } elseif (isset($item['published']) && !empty($item['published'])) {
                    $pub_date = (string) $item['published'];
                } elseif (isset($item['date']) && !empty($item['date'])) {
                    $pub_date = (string) $item['date'];
                }
                
                // Try to format the date properly
                try {
                    $date = new \DateTime($pub_date);
                    $pub_date = $date->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    // If we can't parse the date, use the current time
                    $pub_date = \current_time('mysql');
                }
                
                // Check if an item with this GUID already exists for this feed
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE feed_id = %d AND guid = %s",
                    $feed->get_post_id(),
                    $guid
                ));
                
                if ($existing) {
                    $stats['existing_items']++;
                    continue;
                }
                
                // Prepare JSON content with error handling
                $json_content = \wp_json_encode($item, JSON_UNESCAPED_UNICODE);
                if ($json_content === false) {
                    $stats['errors']++;
                    continue;
                }
                
                // Insert the data
                $result = $wpdb->insert(
                    $wpdb->prefix . 'feed_raw_items',
                    [
                        'item_hash' => \md5($guid),  // Generiere einen Hash als Primary Key
                        'feed_id' => $feed->get_post_id(),
                        'guid' => $guid,
                        'pub_date' => $pub_date,
                        'raw_content' => $json_content, // Korrigiert von 'content' zu 'raw_content'
                        'created_at' => \current_time('mysql')
                    ],
                    [
                        '%s',  // item_hash
                        '%d',  // feed_id
                        '%s',  // guid
                        '%s',  // pub_date
                        '%s',  // raw_content
                        '%s'   // created_at
                    ]
                );
                
                if ($result === false) {
                    $stats['errors']++;
                } else {
                    $stats['new_items']++;
                }
            }
            
            // Update feed metadata
            $this->update_feed_metadata($feed, $stats['new_items']);
            
            // Commit the transaction
            $wpdb->query('COMMIT');
        } catch (\Exception $e) {
            // Rollback transaction on error
            $wpdb->query('ROLLBACK');
            
            // Log the error
            $this->log_error($feed, 'process_items_error', $e->getMessage());
            $this->update_feed_error($feed, 'process_items_error', $e->getMessage());
            
            return $stats;
        }
        
        return $stats;
    }
}
