<?php
declare(strict_types=1);

namespace AthenaAI\Models;

use AthenaAI\Models\FeedProcessor\FeedProcessorFactory;

if (!defined('ABSPATH')) {
    exit;
}

class Feed {
    private ?int $post_id = null;
    private ?string $url = null;
    private string $last_error = '';
    private ?\DateTime $last_checked = null;
    private int $update_interval = 3600; // Standard: 1 Stunde
    private bool $active = true;

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
     * Get the last error message
     * 
     * @return string The last error message
     */
    public function get_last_error(): string {
        return $this->last_error;
    }
    
    /**
     * Fetch feed content from the URL
     * 
     * @param string|null $url Optional URL to override the stored feed URL
     * @param bool $verbose_console Whether to output verbose debugging information to the JavaScript console
     * @return bool Whether the fetch was successful
     */
    public function fetch(?string $url = null, bool $verbose_console = false): bool {
        // Activate debug mode if needed
        $debug_mode = get_option('athena_ai_enable_debug_mode', false);
        
        // Clear any existing errors
        $this->last_error = '';
        
        // Get the URL to fetch
        $fetch_url = $url ?? $this->url;
        if (empty($fetch_url)) {
            $this->last_error = 'No feed URL provided';
            if ($debug_mode) {
                error_log("Athena AI: No feed URL provided");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: No feed URL provided");</script>';
            }
            $this->log_error('no_url', $this->last_error);
            $this->update_feed_error('no_url', $this->last_error);
            return false;
        }
        
        // Prüfen, ob es sich um einen speziellen Feed-Typ handelt
        $is_hubspot = strpos($fetch_url, 'hubspot.com') !== false;
        $is_guetersloh = strpos($fetch_url, 'guetersloh') !== false || strpos($fetch_url, 'gütersloh') !== false;
        
        if ($verbose_console) {
            echo '<script>console.log("Athena AI Feed: Fetching feed from URL: ' . esc_js($fetch_url) . '");</script>';
            if ($is_hubspot) {
                echo '<script>console.log("Athena AI Feed: Detected Hubspot feed");</script>';
            }
            if ($is_guetersloh) {
                echo '<script>console.log("Athena AI Feed: Detected Gütersloh feed");</script>';
            }
        }
        
        // Set up request arguments
        $args = [
            'timeout' => 30,
            'redirection' => 5,
            'sslverify' => false
        ];
        
        // Spezielle URL-Behandlung für Gütersloh-Feed
        if ($is_guetersloh) {
            // Prüfen, ob die URL korrekt formatiert ist
            if (strpos($fetch_url, 'https://www.guetersloh.de/rss') !== false) {
                // URL ist korrekt
                if ($verbose_console) {
                    echo '<script>console.log("Athena AI Feed: Using standard Gütersloh feed URL: ' . esc_js($fetch_url) . '");</script>';
                }
            } else {
                // Versuche, die URL zu korrigieren
                $corrected_url = 'https://www.guetersloh.de/rss';
                if ($verbose_console) {
                    echo '<script>console.log("Athena AI Feed: Correcting Gütersloh feed URL from ' . esc_js($fetch_url) . ' to ' . esc_js($corrected_url) . '");</script>';
                }
                $fetch_url = $corrected_url;
            }
            
            // Spezifische Header für den Gütersloh-Feed
            $args['headers'] = [
                'Accept' => 'application/xml, application/rss+xml, text/xml, */*',
                'Accept-Charset' => 'utf-8, iso-8859-1;q=0.8, *;q=0.7',
                'User-Agent' => 'Mozilla/5.0 (compatible; Athena AI Feed Fetcher/1.0; +https://athena-ai.com)'
            ];
            
            if ($verbose_console) {
                echo '<script>console.log("Athena AI Feed: Using special headers for Gütersloh feed");</script>';
            }
            
            // Sicherstellen, dass die Feed-Metadaten existieren
            $this->ensure_feed_metadata_exists();
        }
        // Special handling for Hubspot feed
        elseif ($is_hubspot) {
            $args['headers'] = [
                'Accept' => 'application/xml, application/rss+xml, text/xml, application/json, */*'
            ];
            
            if ($verbose_console) {
                echo '<script>console.log("Athena AI Feed: Using special headers for Hubspot feed");</script>';
            }
        }
        
        // Use WordPress HTTP API to fetch the content
        $response = wp_remote_get($fetch_url, $args);
        
        if (is_wp_error($response)) {
            $error_code = $response->get_error_code();
            $error_message = $response->get_error_message();
            
            if ($debug_mode) {
                error_log("Athena AI: Error fetching feed: {$error_code} - {$error_message}");
            }
            
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: Error fetching feed: ' . esc_js($error_code) . ' - ' . esc_js($error_message) . '");</script>';
            }
            
            $this->last_error = "HTTP error: {$error_code} - {$error_message}";
            $this->log_error($error_code, $error_message);
            $this->update_feed_error($error_code, $error_message);
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $error_message = "HTTP error: Status code {$status_code}";
            
            if ($debug_mode) {
                error_log("Athena AI: {$error_message}");
            }
            
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: ' . esc_js($error_message) . '");</script>';
            }
            
            $this->last_error = $error_message;
            $this->log_error('http_error', $error_message);
            $this->update_feed_error('http_error', $error_message);
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            if ($debug_mode) {
                error_log("Athena AI: Feed response body is empty");
            }
            
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: Feed response body is empty");</script>';
            }
            
            $this->last_error = 'Feed response body is empty';
            $this->log_error('empty_response', $this->last_error);
            $this->update_feed_error('empty_response', $this->last_error);
            return false;
        }
        
        if ($debug_mode) {
            $body_length = strlen($body);
            error_log("Athena AI: Received feed content (length: {$body_length} bytes)");
        }
        
        // Ausgabe von Feed-Informationen für Debugging
        if ($verbose_console) {
            // Prüfe die ersten Zeichen des Inhalts, um zu sehen, ob es JSON sein könnte
            $content_start = substr($body, 0, 10);
            $content_type = wp_remote_retrieve_header($response, 'content-type');
            
            echo '<script>console.log("Athena AI Feed: Content-Type from header: ' . esc_js($content_type) . '");</script>';
            
            if (strpos($content_start, '{') === 0 || strpos($content_start, '[') === 0) {
                echo '<script>console.log("Athena AI Feed: Content appears to be JSON format");</script>';
            } else if (strpos($content_start, '<?xml') !== false || strpos($body, '<?xml') !== false) {
                echo '<script>console.log("Athena AI Feed: Content appears to be XML format");</script>';
            } else {
                echo '<script>console.log("Athena AI Feed: Content format could not be determined");</script>';
            }
            
            // Zeige die ersten 200 Zeichen des Inhalts
            $preview = substr($body, 0, 200);
            $preview = str_replace(["\n", "\r"], " ", $preview);
            $preview = htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
            echo '<script>console.log("Athena AI Feed: Content preview: ' . esc_js($preview) . '...");</script>';
        }
        
        // Versuche, den Feed-Inhalt zu verarbeiten
        $result = $this->process_feed_content($body, $verbose_console);
        
        if (!$result && empty($this->last_error)) {
            $this->last_error = 'Failed to process feed content';
            $this->log_error('process_error', $this->last_error);
            $this->update_feed_error('process_error', $this->last_error);
        }
        
        return false;
    }
    
    /**
     * Process the feed content
     * 
     * @param string $content The feed content
     * @param bool $verbose_console Whether to output verbose debugging information to the JavaScript console
     * @return bool Whether the processing was successful
     */
    private function process_feed_content(string $content, bool $verbose_console = false): bool {
        global $wpdb;
        
        // Debug-Logging aktivieren
        $debug_mode = get_option('athena_ai_enable_debug_mode', false);

        // Validate content
        if (empty($content)) {
            if ($debug_mode) {
                error_log("Athena AI: Feed content is empty");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: Feed content is empty");</script>';
            }
            $this->last_error = 'Feed content is empty';
            $this->log_error('empty_content', $this->last_error);
            $this->update_feed_error('empty_content', $this->last_error);
            return false;
        }

        // Log feed URL for debugging
        if ($verbose_console) {
            echo '<script>console.log("Athena AI Feed: Processing content from URL: ' . esc_js($this->url) . '");</script>';
        }
        
        try {
            // Use our new FeedProcessorFactory to determine and create the appropriate processor
            if ($verbose_console) {
                echo '<script>console.log("Athena AI Feed: Using feed processor factory to determine feed type");</script>';
            }
            
            // Process the feed content with the appropriate processor
            $items = FeedProcessorFactory::process($content, $this->url, $verbose_console);
            
            if ($items === false || empty($items)) {
                $this->last_error = 'No items found in feed';
                if ($debug_mode) {
                    error_log("Athena AI: No items found in feed: {$this->url}");
                }
                if ($verbose_console) {
                    echo '<script>console.error("Athena AI Feed: No items found in feed: ' . esc_js($this->url) . '");</script>';
                }
                $this->log_error('no_items', $this->last_error);
                $this->update_feed_error('no_items', $this->last_error);
                return false;
            }
            
            // Process the extracted feed items
            return $this->process_feed_items($items, $verbose_console);
        } catch (\Exception $e) {
            $this->last_error = 'Feed processing error: ' . $e->getMessage();
            if ($debug_mode) {
                error_log("Athena AI: Feed processing error: {$e->getMessage()} - URL: {$this->url}");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: Processing error: ' . esc_js($e->getMessage()) . ' - URL: ' . esc_js($this->url) . '");</script>';
            }
            $this->log_error('processing_error', $this->last_error);
            $this->update_feed_error('processing_error', $this->last_error);
            return false;
        }
    }

    /**
     * Process the extracted feed items
     * 
     * @param array $items The extracted feed items
     * @param bool $verbose_console Whether to output verbose debugging information to the JavaScript console
     * @return bool True on success, false on failure
     */
    private function process_feed_items(array $items, bool $verbose_console = false): bool {
        global $wpdb;
        
        // Debug-Logging aktivieren
        $debug_mode = get_option('athena_ai_enable_debug_mode', false);

        if ($verbose_console) {
            echo '<script>console.group("Athena AI Feed: Processing ' . count($items) . ' items for feed ID ' . esc_js($this->post_id) . '");</script>';
        }

        // Begin transaction for better database consistency
        $wpdb->query('START TRANSACTION');

        try {
            // Ensure feed metadata exists before processing items
            if (!$this->ensure_feed_metadata_exists()) {
                if ($debug_mode) {
                    error_log("Athena AI: Failed to ensure feed metadata exists for feed ID {$this->post_id}");
                }
                if ($verbose_console) {
                    echo '<script>console.error("Athena AI Feed: Failed to ensure feed metadata exists for feed ID ' . $this->post_id . '");</script>';
                }
                throw new \Exception('Failed to ensure feed metadata exists');
            }
            
            // Prüfen, ob die feed_raw_items Tabelle existiert
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_raw_items'");
            if (!$table_exists) {
                if ($debug_mode) {
                    error_log("Athena AI: feed_raw_items table does not exist");
                }
                if ($verbose_console) {
                    echo '<script>console.error("Athena AI Feed: feed_raw_items table does not exist");</script>';
                }
                throw new \Exception('feed_raw_items table does not exist');
            }
            
            // Prüfen, ob bereits Items für diesen Feed existieren
            if ($debug_mode) {
                $existing_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE feed_id = %d",
                    $this->post_id
                ));
                error_log("Athena AI: Feed ID {$this->post_id} already has {$existing_count} items in database");
            }
            
            $processed = 0;
            $errors = 0;
            $new_items = 0;
            $existing_items = 0;

        foreach ($items as $item) {
            // Extract GUID - handle different feed formats with type safety
            $guid = '';
            
            // Debug-Ausgabe für Item-Struktur
            if ($verbose_console) {
                $item_keys = [];                
                if (is_object($item)) {
                    $item_keys = get_object_vars($item);
                } elseif (is_array($item)) {
                    $item_keys = $item;
                }
                echo '<script>console.log("Item properties: ", ' . json_encode(array_keys($item_keys)) . ');</script>';
            }
            
            // Generische GUID-Extraktion für alle Feeds
            // Diese Logik ersetzt sowohl die spezielle Gütersloh-Logik als auch die Standard-Logik
            if (isset($item->guid) && !empty($item->guid)) {
                $guid = is_object($item->guid) && isset($item->guid->__toString) ? (string)$item->guid : (string)$item->guid;
                if ($verbose_console) {
                    echo '<script>console.log("Using guid property: ' . esc_js($guid) . '");</script>';
                }
            } elseif (isset($item->id) && !empty($item->id)) {
                $guid = is_object($item->id) && isset($item->id->__toString) ? (string)$item->id : (string)$item->id;
                if ($verbose_console) {
                    echo '<script>console.log("Using id property: ' . esc_js($guid) . '");</script>';
                }
            } elseif (isset($item->link) && !empty($item->link)) {
                // Use link as fallback
                $guid = is_object($item->link) && isset($item->link->__toString) ? (string)$item->link : (string)$item->link;
                if ($verbose_console) {
                    echo '<script>console.log("Using link as GUID: ' . esc_js($guid) . '");</script>';
                }
            } elseif (isset($item->title) && !empty($item->title)) {
                // Fallback: Verwende Titel als GUID
                $title = is_object($item->title) && isset($item->title->__toString) ? (string)$item->title : (string)$item->title;
                $guid = 'item-' . md5($title);
                if ($verbose_console) {
                    echo '<script>console.log("Generated GUID from title: ' . esc_js($guid) . '");</script>';
                }
            } else {
                // Generiere einen zufälligen GUID als letzten Ausweg
                $guid = 'feed-item-' . uniqid();
                if ($verbose_console) {
                    echo '<script>console.log("Generated random GUID: ' . esc_js($guid) . '");</script>';
                }
            }
            
            // Extract publication date - handle different formats with type safety
            $pub_date = '';
            
            // Generische Datumsextraktion für alle Feeds
            if (isset($item->pubDate) && !empty($item->pubDate)) {
                $pub_date = is_object($item->pubDate) && isset($item->pubDate->__toString) ? (string)$item->pubDate : (string)$item->pubDate;
                if ($verbose_console) {
                    echo '<script>console.log("Using pubDate property: ' . esc_js($pub_date) . '");</script>';
                }
            } elseif (isset($item->published) && !empty($item->published)) {
                $pub_date = is_object($item->published) && isset($item->published->__toString) ? (string)$item->published : (string)$item->published;
                if ($verbose_console) {
                    echo '<script>console.log("Using published property: ' . esc_js($pub_date) . '");</script>';
                }
            } elseif (isset($item->updated) && !empty($item->updated)) {
                $pub_date = is_object($item->updated) && isset($item->updated->__toString) ? (string)$item->updated : (string)$item->updated;
                if ($verbose_console) {
                    echo '<script>console.log("Using updated property: ' . esc_js($pub_date) . '");</script>';
                }
            } elseif (isset($item->date) && !empty($item->date)) {
                $pub_date = is_object($item->date) && isset($item->date->__toString) ? (string)$item->date : (string)$item->date;
                if ($verbose_console) {
                    echo '<script>console.log("Using date property: ' . esc_js($pub_date) . '");</script>';
                }
            } else {
                // Aktuelles Datum als Fallback
                $pub_date = current_time('mysql');
                if ($verbose_console) {
                    echo '<script>console.log("Using current time as fallback: ' . esc_js($pub_date) . '");</script>';
                }
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
            
            try {
                // Vor dem Einfügen prüfen, ob das Item bereits existiert
                $existing_item = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}feed_raw_items WHERE feed_id = %d AND guid = %s",
                    $this->post_id,
                    $guid
                ));
                
                if ($existing_item) {
                    if ($verbose_console) {
                        echo '<script>console.log("Item with GUID ' . esc_js($guid) . ' already exists, updating...");</script>';
                    }
                    
                    // Item aktualisieren
                    $result = $wpdb->update(
                        $wpdb->prefix . 'feed_raw_items',
                        [
                            'title' => $title ?? '',
                            'link' => $link ?? '',
                            'description' => $description ?? '',
                            'content' => $content ?? '',
                            'pub_date' => $pub_date ?? date('Y-m-d H:i:s'),
                            'updated_at' => current_time('mysql'),
                            'raw_data' => json_encode($item),
                        ],
                        [
                            'id' => $existing_item
                        ]
                    );
                    
                    if ($result !== false) {
                        $existing_items++;
                    } else {
                        $errors++;
                        if ($verbose_console) {
                            echo '<script>console.error("Failed to update item with GUID ' . esc_js($guid) . ': ' . esc_js($wpdb->last_error) . '");</script>';
                        }
                    }
                } else {
                    // Neues Item einfügen
                    if ($verbose_console) {
                        echo '<script>console.log("Inserting new item with GUID ' . esc_js($guid) . '");</script>';
                    }
                    
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'feed_raw_items',
                        [
                            'feed_id' => $this->post_id,
                            'guid' => $guid,
                            'title' => $title ?? '',
                            'link' => $link ?? '',
                            'description' => $description ?? '',
                            'content' => $content ?? '',
                            'pub_date' => $pub_date ?? date('Y-m-d H:i:s'),
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql'),
                            'raw_data' => json_encode($item),
                        ]
                    );
                    
                    if ($result === false) {
                        if ($debug_mode) {
                            error_log("Athena AI: Failed to insert feed item: {$wpdb->last_error}");
                        }
                        if ($verbose_console) {
                            echo '<script>console.error("Athena AI Feed: Failed to insert feed item: ' . esc_js($wpdb->last_error) . '");</script>';
                        }
                        $errors++;
                    } else {
                        $new_items++;
                    }
                }
            } catch (\Exception $e) {
                if ($debug_mode) {
                    error_log("Athena AI: Error processing item: {$e->getMessage()}");
                }
                if ($verbose_console) {
                    echo '<script>console.error("Athena AI Feed: Error processing item: ' . esc_js($e->getMessage()) . '");</script>';
                }
                $errors++;
            }
            
            // Überwache den Fortschritt
            $processed++;
            if ($verbose_console && $processed % 10 === 0) {
                echo '<script>console.log("Processed ' . $processed . '/' . count($items) . ' items so far...");</script>';
            }
        }
        
        // Commit the transaction if we have more processed items than errors
        if ($errors < $processed) {
            $wpdb->query('COMMIT');
            if ($verbose_console) {
                echo '<script>console.log("Athena AI Feed: Committed transaction. Processed: ' . $processed . ', New: ' . $new_items . ', Existing: ' . $existing_items . ', Errors: ' . $errors . '");</script>';
                echo '<script>console.groupEnd();</script>';
            }
            // Update feed metadata
            $this->update_feed_metadata($new_items);
            return true;
        } else {
            // Rollback if we have more errors than processed items
            $wpdb->query('ROLLBACK');
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: Rolled back transaction due to too many errors. Processed: ' . $processed . ', Errors: ' . $errors . '");</script>';
                echo '<script>console.groupEnd();</script>';
            }
            return false;
        }
    } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: ' . esc_js($e->getMessage()) . '");</script>';
                echo '<script>console.trace("Stack trace");</script>';
                echo '<script>console.groupEnd();</script>';
            }
            if ($debug_mode) {
                error_log("Athena AI: Transaction rolled back due to error: " . $e->getMessage());
                error_log("Athena AI: Error trace: " . $e->getTraceAsString());
            }
            $this->log_error('db_error', $e->getMessage());
            $this->update_feed_error('db_error', $e->getMessage());
            return false;
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
     * Set the feed ID (post ID)
     *
     * @param int $id The post ID to set
     * @return void
     */
    public function set_id(int $id): void {
        $this->post_id = $id;
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
