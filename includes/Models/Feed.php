<?php
declare(strict_types=1);

namespace AthenaAI\Models;

if (!defined('ABSPATH')) {
    exit;
}

class Feed {
    private int $post_id;
    private string $url;
    private ?\DateTime $last_checked;
    private int $update_interval;
    private bool $active;

    /**
     * Constructor for the Feed class
     * 
     * @param string $url The feed URL
     * @param int $update_interval The update interval in seconds
     * @param bool $active Whether the feed is active
     */
    public function __construct(
        string $url,
        int $update_interval = 3600,
        bool $active = true
    ) {
        $this->url = esc_url_raw($url);
        $this->update_interval = $update_interval;
        $this->active = $active;
    }

    /**
     * Fetch feed content from the URL
     * 
     * @return bool Whether the fetch was successful
     */
    public function fetch(): bool {
        $response = wp_safe_remote_get($this->url, [
            'timeout' => 15,
            'headers' => ['Accept' => 'application/rss+xml, application/atom+xml']
        ]);

        if (is_wp_error($response)) {
            $this->log_error($response->get_error_code(), $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return $this->process_feed_content($body);
    }

    /**
     * Process the feed content
     * 
     * @param string $content The feed content
     * @return bool Whether the processing was successful
     */
    private function process_feed_content(string $content): bool {
        global $wpdb;

        // Use libxml internal errors for better error handling
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            $error_msg = !empty($errors) ? $errors[0]->message : 'Failed to parse feed XML';
            $this->log_error('xml_parse_error', $error_msg);
            libxml_clear_errors();
            return false;
        }

        // Handle different feed formats (RSS, Atom, etc.)
        $items = [];
        if (isset($xml->channel) && isset($xml->channel->item)) {
            // RSS format
            $items = $xml->channel->item;
        } elseif (isset($xml->entry)) {
            // Atom format
            $items = $xml->entry;
        } elseif (isset($xml->item)) {
            // Some RSS variants
            $items = $xml->item;
        }
        
        if (empty($items)) {
            $this->log_error('no_items_found', 'No items found in feed');
            return false;
        }

        $processed = 0;
        $errors = 0;

        // Begin transaction for better database consistency
        $wpdb->query('START TRANSACTION');

        try {
            foreach ($items as $item) {
                // Extract GUID - handle different feed formats
                $guid = '';
                if (isset($item->guid)) {
                    $guid = (string)$item->guid;
                } elseif (isset($item->id)) {
                    $guid = (string)$item->id;
                } elseif (isset($item->link)) {
                    // Use link as fallback
                    $guid = (string)$item->link;
                }
                
                // Extract publication date - handle different formats
                $pub_date = '';
                if (isset($item->pubDate)) {
                    $pub_date = (string)$item->pubDate;
                } elseif (isset($item->published)) {
                    $pub_date = (string)$item->published;
                } elseif (isset($item->updated)) {
                    $pub_date = (string)$item->updated;
                } elseif (isset($item->date)) {
                    $pub_date = (string)$item->date;
                }
                
                // Skip items without required data
                if (empty($guid)) {
                    $errors++;
                    continue;
                }
                
                // If no publication date is found, use current time
                if (empty($pub_date)) {
                    $pub_date = current_time('mysql');
                }
                
                // Create a unique hash for the item
                $item_hash = md5($guid . $pub_date);
                $raw_content = wp_json_encode($item);
                $formatted_date = date('Y-m-d H:i:s', strtotime($pub_date));
                
                // Ensure the feed_id exists before inserting
                if (!$this->post_id) {
                    throw new \Exception('Invalid feed ID');
                }

                // Store raw item using replace to handle duplicates
                $result = $wpdb->replace(
                    $wpdb->prefix . 'feed_raw_items',
                    [
                        'item_hash' => $item_hash,
                        'feed_id' => $this->post_id,
                        'raw_content' => $raw_content,
                        'pub_date' => $formatted_date,
                        'guid' => $guid
                    ],
                    ['%s', '%d', '%s', '%s', '%s']
                );
                
                if ($result === false) {
                    $errors++;
                } else {
                    $processed++;
                }
            }
            
            // Update feed metadata
            $this->update_feed_metadata($processed, $errors);
            
            // Commit transaction
            $wpdb->query('COMMIT');
        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            $this->log_error('db_error', $e->getMessage());
            return false;
        }

        $this->update_last_checked();
        return $processed > 0;
    }

    /**
     * Log an error for this feed
     * 
     * @param string $code The error code
     * @param string $message The error message
     */
    private function log_error(string $code, string $message): void {
        global $wpdb;
        
        // Log to database
        $wpdb->insert(
            $wpdb->prefix . 'feed_errors',
            [
                'feed_id' => $this->post_id,
                'error_code' => $code,
                'error_message' => $message,
                'created' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s']
        );
        
        // Update feed metadata with last error
        $this->update_feed_error($code, $message);
        
        // Also log to WordPress error log
        error_log(sprintf(
            'Athena AI Feed Error [%s]: %s (Feed ID: %d)',
            $code,
            $message,
            $this->post_id
        ));
    }
    
    /**
     * Update feed metadata with error information
     * 
     * @param string $code The error code
     * @param string $message The error message
     */
    private function update_feed_error(string $code, string $message): void {
        global $wpdb;
        
        $now = current_time('mysql');
        
        // Check if metadata exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT feed_id FROM {$wpdb->prefix}feed_metadata WHERE feed_id = %d",
            $this->post_id
        ));
        
        if ($exists) {
            // Update existing metadata
            $wpdb->update(
                $wpdb->prefix . 'feed_metadata',
                [
                    'last_error_date' => $now,
                    'last_error_message' => sprintf('[%s] %s', $code, $message),
                    'updated_at' => $now
                ],
                ['feed_id' => $this->post_id],
                ['%s', '%s', '%s'],
                ['%d']
            );
        } else {
            // Insert new metadata with error
            $wpdb->insert(
                $wpdb->prefix . 'feed_metadata',
                [
                    'feed_id' => $this->post_id,
                    'last_error_date' => $now,
                    'last_error_message' => sprintf('[%s] %s', $code, $message),
                    'fetch_interval' => $this->update_interval,
                    'created_at' => $now,
                    'updated_at' => $now
                ],
                ['%d', '%s', '%s', '%d', '%s', '%s']
            );
        }
    }

    /**
     * Update the last checked timestamp
     */
    private function update_last_checked(): void {
        $now = current_time('mysql');
        update_post_meta($this->post_id, '_athena_feed_last_checked', $now);
        $this->last_checked = new \DateTime();
    }
    
    /**
     * Update feed metadata in the database
     * 
     * @param int $processed Number of processed items
     * @param int $errors Number of errors
     */
    private function update_feed_metadata(int $processed, int $errors = 0): void {
        global $wpdb;
        
        $now = current_time('mysql');
        
        // Check if metadata exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT feed_id FROM {$wpdb->prefix}feed_metadata WHERE feed_id = %d",
            $this->post_id
        ));
        
        if ($exists) {
            // Update existing metadata
            $wpdb->update(
                $wpdb->prefix . 'feed_metadata',
                [
                    'last_fetched' => $now,
                    'fetch_count' => $wpdb->prepare('fetch_count + %d', $processed),
                    'updated_at' => $now
                ],
                ['feed_id' => $this->post_id],
                ['%s', '%d', '%s'],
                ['%d']
            );
        } else {
            // Insert new metadata
            $wpdb->insert(
                $wpdb->prefix . 'feed_metadata',
                [
                    'feed_id' => $this->post_id,
                    'last_fetched' => $now,
                    'fetch_interval' => $this->update_interval,
                    'fetch_count' => $processed,
                    'created_at' => $now,
                    'updated_at' => $now
                ],
                ['%d', '%s', '%d', '%d', '%s', '%s']
            );
        }
    }

    /**
     * Get a feed by its post ID
     * 
     * @param int $post_id The post ID
     * @return self|null The feed object or null if not found
     */
    public static function get_by_id(int $post_id): ?self {
        // Check if post exists and is the correct type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'athena-feed') {
            return null;
        }

        $url = get_post_meta($post_id, '_athena_feed_url', true);
        if (empty($url)) {
            return null;
        }

        $update_interval = (int)get_post_meta($post_id, '_athena_feed_update_interval', true) ?: 3600;
        $active = (bool)get_post_meta($post_id, '_athena_feed_active', true) ?: true;
        $last_checked = get_post_meta($post_id, '_athena_feed_last_checked', true);

        $feed = new self($url, $update_interval, $active);
        $feed->post_id = $post_id;
        $feed->last_checked = $last_checked ? new \DateTime($last_checked) : null;
        
        return $feed;
    }

    /**
     * Get the feed ID (post ID)
     *
     * @return int|null The post ID or null if not set
     */
    public function get_id(): ?int {
        return isset($this->post_id) ? $this->post_id : null;
    }
    
    /**
     * Get the feed URL
     *
     * @return string The feed URL
     */
    public function get_url(): string {
        return $this->url;
    }
    
    /**
     * Set the feed URL
     *
     * @param string $url The new feed URL
     * @return void
     */
    public function set_url(string $url): void {
        $this->url = esc_url_raw($url);
    }
    
    /**
     * Save the feed to the database
     *
     * @return bool Whether the save was successful
     */
    public function save(): bool {
        // If we have a post_id, update the existing post
        if (isset($this->post_id)) {
            // Update post meta
            update_post_meta($this->post_id, '_athena_feed_url', $this->url);
            update_post_meta($this->post_id, '_athena_feed_update_interval', $this->update_interval);
            update_post_meta($this->post_id, '_athena_feed_active', $this->active ? '1' : '0');
            
            return true;
        } else {
            // Create a new post
            $post_id = wp_insert_post([
                'post_title' => parse_url($this->url, PHP_URL_HOST) ?: $this->url,
                'post_type' => 'athena-feed',
                'post_status' => 'publish'
            ]);
            
            if (is_wp_error($post_id)) {
                return false;
            }
            
            // Set post meta
            update_post_meta($post_id, '_athena_feed_url', $this->url);
            update_post_meta($post_id, '_athena_feed_update_interval', $this->update_interval);
            update_post_meta($post_id, '_athena_feed_active', $this->active ? '1' : '0');
            
            $this->post_id = $post_id;
            return true;
        }
    }
    
    /**
     * Get all active feeds
     * 
     * @return array Array of Feed objects
     */
    public static function get_all_active(): array {
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
            $feed = self::get_by_id($post->ID);
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
    public static function get_feeds_to_update(): array {
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
            $feed = self::get_by_id($post->ID);
            if ($feed) {
                $feeds[] = $feed;
            }
        }
        
        return $feeds;
    }
}
