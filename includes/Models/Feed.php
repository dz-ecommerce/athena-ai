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
        // Debug-Logging aktivieren
        $debug_mode = get_option('athena_ai_enable_debug_mode', false);
        
        if ($debug_mode) {
            error_log("Athena AI: Fetching feed from URL: {$this->url}");
        }
        
        $response = wp_safe_remote_get($this->url, [
            'timeout' => 15,
            'headers' => ['Accept' => 'application/rss+xml, application/atom+xml']
        ]);

        if (is_wp_error($response)) {
            $error_code = $response->get_error_code();
            $error_message = $response->get_error_message();
            
            if ($debug_mode) {
                error_log("Athena AI: Error fetching feed: {$error_code} - {$error_message}");
            }
            
            $this->log_error($error_code, $error_message);
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $error_message = "HTTP error: Status code {$status_code}";
            
            if ($debug_mode) {
                error_log("Athena AI: {$error_message}");
            }
            
            $this->log_error('http_error', $error_message);
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            if ($debug_mode) {
                error_log("Athena AI: Feed response body is empty");
            }
            
            $this->log_error('empty_response', 'Feed response body is empty');
            return false;
        }
        
        if ($debug_mode) {
            $body_length = strlen($body);
            error_log("Athena AI: Received feed content (length: {$body_length} bytes)");
        }
        
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
        
        // Debug-Logging aktivieren
        $debug_mode = get_option('athena_ai_enable_debug_mode', false);

        // Validate content
        if (empty($content)) {
            if ($debug_mode) {
                error_log("Athena AI: Feed content is empty");
            }
            $this->log_error('empty_content', 'Feed content is empty');
            return false;
        }

        // Use libxml internal errors for better error handling
        libxml_use_internal_errors(true);
        
        // Attempt to load XML with error suppression
        $xml = @simplexml_load_string($content);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            $error_msg = !empty($errors) ? $errors[0]->message : 'Failed to parse feed XML';
            
            if ($debug_mode) {
                error_log("Athena AI: XML parse error: {$error_msg}");
                if (!empty($errors)) {
                    foreach ($errors as $index => $error) {
                        if ($index < 3) { // Limit to first 3 errors to avoid log spam
                            error_log("Athena AI: XML Error {$index}: Line {$error->line}, Column {$error->column}: {$error->message}");
                        }
                    }
                }
            }
            
            $this->log_error('xml_parse_error', $error_msg);
            libxml_clear_errors();
            return false;
        }
        
        if ($debug_mode) {
            error_log("Athena AI: XML parsed successfully");
        }

        // Handle different feed formats (RSS, Atom, etc.)
        $items = [];
        $feed_type = 'unknown';
        
        if (isset($xml->channel) && isset($xml->channel->item)) {
            // RSS format
            $items = $xml->channel->item;
            $feed_type = 'RSS';
            
            if ($debug_mode) {
                $feed_title = isset($xml->channel->title) ? (string)$xml->channel->title : 'Unknown';
                error_log("Athena AI: Detected RSS feed: '{$feed_title}'");
            }
        } elseif (isset($xml->entry)) {
            // Atom format
            $items = $xml->entry;
            $feed_type = 'Atom';
            
            if ($debug_mode) {
                $feed_title = isset($xml->title) ? (string)$xml->title : 'Unknown';
                error_log("Athena AI: Detected Atom feed: '{$feed_title}'");
            }
        } elseif (isset($xml->item)) {
            // Some RSS variants
            $items = $xml->item;
            $feed_type = 'RSS variant';
            
            if ($debug_mode) {
                error_log("Athena AI: Detected RSS variant feed");
            }
        }
        
        if (empty($items)) {
            if ($debug_mode) {
                error_log("Athena AI: No items found in feed");
                // Dump the first level of XML structure for debugging
                $xml_keys = get_object_vars($xml);
                error_log("Athena AI: XML structure: " . print_r(array_keys($xml_keys), true));
            }
            
            $this->log_error('no_items_found', 'No items found in feed');
            return false;
        }
        
        if ($debug_mode) {
            $item_count = count($items);
            error_log("Athena AI: Found {$item_count} items in {$feed_type} feed");
        }

        $processed = 0;
        $errors = 0;
        $new_items = 0;
        $existing_items = 0;

        // Begin transaction for better database consistency
        $wpdb->query('START TRANSACTION');

        try {
            // Debug-Logging aktivieren
            $debug_mode = get_option('athena_ai_enable_debug_mode', false);
            
            // Prüfen, ob bereits Items für diesen Feed existieren
            if ($debug_mode) {
                $existing_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE feed_id = %d",
                    $this->post_id
                ));
                error_log("Athena AI: Feed ID {$this->post_id} already has {$existing_count} items in database");
            }
            
            foreach ($items as $item) {
                // Extract GUID - handle different feed formats with type safety
                $guid = '';
                if (isset($item->guid) && !empty($item->guid)) {
                    $guid = is_object($item->guid) && isset($item->guid->__toString) ? (string)$item->guid : (string)$item->guid;
                } elseif (isset($item->id) && !empty($item->id)) {
                    $guid = is_object($item->id) && isset($item->id->__toString) ? (string)$item->id : (string)$item->id;
                } elseif (isset($item->link) && !empty($item->link)) {
                    // Use link as fallback
                    $guid = is_object($item->link) && isset($item->link->__toString) ? (string)$item->link : (string)$item->link;
                }
                
                // Extract publication date - handle different formats with type safety
                $pub_date = '';
                if (isset($item->pubDate) && !empty($item->pubDate)) {
                    $pub_date = is_object($item->pubDate) && isset($item->pubDate->__toString) ? (string)$item->pubDate : (string)$item->pubDate;
                } elseif (isset($item->published) && !empty($item->published)) {
                    $pub_date = is_object($item->published) && isset($item->published->__toString) ? (string)$item->published : (string)$item->published;
                } elseif (isset($item->updated) && !empty($item->updated)) {
                    $pub_date = is_object($item->updated) && isset($item->updated->__toString) ? (string)$item->updated : (string)$item->updated;
                } elseif (isset($item->date) && !empty($item->date)) {
                    $pub_date = is_object($item->date) && isset($item->date->__toString) ? (string)$item->date : (string)$item->date;
                }
                
                // Skip items without required data
                if (empty($guid)) {
                    if ($debug_mode) {
                        error_log("Athena AI: Skipping item without GUID");
                    }
                    $errors++;
                    continue;
                }
                
                // If no publication date is found, use current time
                if (empty($pub_date)) {
                    $pub_date = current_time('mysql');
                    if ($debug_mode) {
                        error_log("Athena AI: No publication date found, using current time");
                    }
                }
                
                // Create a unique hash for the item
                $item_hash = md5($guid . $pub_date);
                
                // Check if item already exists
                if ($debug_mode) {
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE item_hash = %s",
                        $item_hash
                    ));
                    
                    if ($exists) {
                        error_log("Athena AI: Item with hash {$item_hash} already exists in database");
                    }
                }
                
                // Safely encode item to JSON with error handling
                $raw_content = wp_json_encode($item);
                if ($raw_content === false) {
                    // Log JSON encoding error and skip this item
                    if ($debug_mode) {
                        error_log("Athena AI: Failed to encode feed item to JSON");
                    }
                    $this->log_error('json_encode_error', 'Failed to encode feed item to JSON');
                    $errors++;
                    continue;
                }
                
                // Safely parse the date with error handling
                $timestamp = strtotime($pub_date);
                if ($timestamp === false) {
                    // Use current time if date parsing fails
                    $formatted_date = current_time('mysql');
                    if ($debug_mode) {
                        error_log("Athena AI: Failed to parse date '{$pub_date}', using current time");
                    }
                } else {
                    $formatted_date = date('Y-m-d H:i:s', $timestamp);
                    if ($debug_mode) {
                        error_log("Athena AI: Parsed date '{$pub_date}' to '{$formatted_date}'");
                    }
                }
                
                // Ensure the feed_id exists before inserting
                if (!$this->post_id) {
                    if ($debug_mode) {
                        error_log("Athena AI: Invalid feed ID");
                    }
                    throw new \Exception('Invalid feed ID');
                }

                // Validate data before inserting
                if (empty($item_hash) || empty($guid) || empty($raw_content)) {
                    if ($debug_mode) {
                        error_log("Athena AI: Invalid or incomplete feed item data");
                    }
                    $this->log_error('invalid_item_data', 'Invalid or incomplete feed item data');
                    $errors++;
                    continue;
                }
                
                // Check if item already exists before inserting
                $item_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE item_hash = %s",
                    $item_hash
                ));
                
                // Store raw item using replace to handle duplicates with error handling
                try {
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
                        if ($debug_mode) {
                            error_log("Athena AI: Database error: " . ($wpdb->last_error ?: 'Failed to insert feed item'));
                        }
                        $this->log_error('db_insert_error', $wpdb->last_error ?: 'Failed to insert feed item');
                        $errors++;
                    } else {
                        $processed++;
                        
                        // Track if this was a new item or an existing one
                        if ($item_exists) {
                            $existing_items++;
                            if ($debug_mode) {
                                error_log("Athena AI: Updated existing item with GUID: {$guid}");
                            }
                        } else {
                            $new_items++;
                            if ($debug_mode) {
                                error_log("Athena AI: Inserted new item with GUID: {$guid}");
                            }
                        }
                    }
                } catch (\Exception $e) {
                    if ($debug_mode) {
                        error_log("Athena AI: Exception during database operation: " . $e->getMessage());
                    }
                    $this->log_error('db_exception', $e->getMessage());
                    $errors++;
                }
            }
            
            // Log summary of processing
            if ($debug_mode) {
                error_log("Athena AI: Feed processing summary - Processed: {$processed}, New items: {$new_items}, Updated items: {$existing_items}, Errors: {$errors}");
            }
            
            // Update feed metadata
            $this->update_feed_metadata($processed, $errors);
            
            // Commit transaction
            $wpdb->query('COMMIT');
        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            if ($debug_mode) {
                error_log("Athena AI: Transaction rolled back due to error: " . $e->getMessage());
            }
            $this->log_error('db_error', $e->getMessage());
            return false;
        }

        $this->update_last_checked();
        return $processed > 0;
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
     * Update feed metadata
     * 
     * @param int $processed Number of processed items
     * @param int $errors Number of errors
     */
    private function update_feed_metadata(int $processed, int $errors): void {
        global $wpdb;
        
        // First check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'");
        if (!$table_exists) {
            // Log error but don't fail the whole process
            error_log("Athena AI: feed_metadata table does not exist");
            return;
        }
        
        // Check if metadata exists with error suppression
        $wpdb->suppress_errors(true);
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}feed_metadata WHERE feed_id = %d",
            $this->post_id
        ));
        $wpdb->suppress_errors(false);
        
        if ($wpdb->last_error) {
            error_log("Athena AI: Error checking feed metadata: {$wpdb->last_error}");
            return;
        }
        
        $now = current_time('mysql');
        $data = [
            'last_fetch_date' => $now,
            'items_count' => $processed,
            'updated_at' => $now
        ];
        
        // Add error data if there were errors
        if ($errors > 0) {
            $data['last_error_date'] = $now;
            $data['last_error_message'] = "Failed to process {$errors} items";
        }
        
        // Prepare format array based on data types
        $formats = [];
        foreach ($data as $key => $value) {
            if (is_int($value)) {
                $formats[] = '%d';
            } elseif (is_float($value)) {
                $formats[] = '%f';
            } else {
                $formats[] = '%s';
            }
        }
        
        try {
            if ($exists) {
                // Update existing record with error handling
                $result = $wpdb->update(
                    $wpdb->prefix . 'feed_metadata',
                    $data,
                    ['feed_id' => $this->post_id],
                    $formats,
                    ['%d']
                );
                
                if ($result === false && $wpdb->last_error) {
                    error_log("Athena AI: Error updating feed metadata: {$wpdb->last_error}");
                }
            } else {
                // Insert new record with error handling
                $data['feed_id'] = $this->post_id;
                $data['created_at'] = $now;
                $formats[] = '%d'; // for feed_id
                $formats[] = '%s'; // for created_at
                
                $result = $wpdb->insert(
                    $wpdb->prefix . 'feed_metadata',
                    $data,
                    $formats
                );
                
                if ($result === false && $wpdb->last_error) {
                    error_log("Athena AI: Error inserting feed metadata: {$wpdb->last_error}");
                }
            }
        } catch (\Exception $e) {
            error_log("Athena AI: Exception in feed metadata update: {$e->getMessage()}");
        }
    }

    /**
     * Log an error for this feed
     * 
     * @param string $code The error code
     * @param string $message The error message
     */
    private function log_error(string $code, string $message): void {
        global $wpdb;
        
        // First check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_errors'");
        if (!$table_exists) {
            // Log to WordPress error log instead
            error_log("Athena AI Feed Error ({$code}): {$message}");
            return;
        }
        
        try {
            // Log to database with error handling
            $result = $wpdb->insert(
                $wpdb->prefix . 'feed_errors',
                [
                    'feed_id' => $this->post_id,
                    'error_code' => $code,
                    'error_message' => $message,
                    'created' => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s']
            );
            
            if ($result === false && $wpdb->last_error) {
                // Log to WordPress error log as fallback
                error_log("Athena AI: Failed to log feed error to database: {$wpdb->last_error}");
                error_log("Athena AI Feed Error ({$code}): {$message}");
            }
        } catch (\Exception $e) {
            // Log exception to WordPress error log
            error_log("Athena AI: Exception logging feed error: {$e->getMessage()}");
            error_log("Athena AI Feed Error ({$code}): {$message}");
        }
        
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
        
        // First check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'");
        if (!$table_exists) {
            // Log error but don't fail the whole process
            error_log("Athena AI: feed_metadata table does not exist");
            return;
        }
        
        $now = current_time('mysql');
        
        // Check if metadata exists with error suppression
        $wpdb->suppress_errors(true);
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}feed_metadata WHERE feed_id = %d",
            $this->post_id
        ));
        $wpdb->suppress_errors(false);
        
        if ($wpdb->last_error) {
            error_log("Athena AI: Error checking feed metadata: {$wpdb->last_error}");
            return;
        }
        
        try {
            if ($exists) {
                // Update existing metadata with error information
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
                // Insert new metadata with error information
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
            
            if ($wpdb->last_error) {
                error_log("Athena AI: Error updating feed metadata with error: {$wpdb->last_error}");
            }
        } catch (\Exception $e) {
            error_log("Athena AI: Exception in feed error update: {$e->getMessage()}");
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
