<?php
declare(strict_types=1);

namespace AthenaAI\Models;

use AthenaAI\Services\FeedProcessor\FeedProcessorFactory;
use AthenaAI\Services\LoggerService;

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
        // Logger für konsistentes Logging konfigurieren
        $logger = LoggerService::getInstance()
            ->setComponent('Feed')
            ->setVerboseMode($verbose_console);
        
        // Clear any existing errors
        $this->last_error = '';
        
        // Get the URL to fetch
        $fetch_url = $url ?? $this->url;
        if (empty($fetch_url)) {
            $this->last_error = 'No feed URL provided';
            $logger->error("No feed URL provided");
            $this->log_error('no_url', $this->last_error);
            $this->update_feed_error('no_url', $this->last_error);
            return false;
        }
        
        // HTTP-Client aus dem Services-Namespace verwenden
        $http_client = new \AthenaAI\Services\FeedHttpClient();
        $http_client->setVerboseMode($verbose_console);
        $logger->info("Fetching feed from URL: {$fetch_url}");
        
        // HTTP-Anfrage durchführen
        $content = $http_client->fetch($fetch_url);
        
        // Prüfen, ob die Anfrage erfolgreich war
        if ($content === false) {
            $this->last_error = 'HTTP request failed';
            $logger->error("HTTP request failed - URL: {$fetch_url}");
            $this->log_error('http_request_failed', $this->last_error);
            $this->update_feed_error('http_request_failed', $this->last_error);
            return false;
        }
        
        // Prüfen, ob der Inhalt leer ist
        if (empty($content)) {
            $this->last_error = 'Feed content is empty';
            $logger->error("Feed content is empty - URL: {$fetch_url}");
            $this->log_error('empty_content', $this->last_error);
            $this->update_feed_error('empty_content', $this->last_error);
            return false;
        }

        // Process the feed content
        if (!$this->process_feed_content($content, $verbose_console)) {
            return false;
        }
        
        $logger->info("Successfully fetched and processed feed from URL: {$fetch_url}");
        return true;
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
        
        // Logger für konsistentes Logging
        $logger = LoggerService::getInstance()
            ->setComponent('Feed')
            ->setVerboseMode($verbose_console);
        
        // Validate content
        if (empty($content)) {
            $this->last_error = 'Feed content is empty';
            $logger->error("Feed content is empty - URL: {$this->url}");
            $this->log_error('empty_content', $this->last_error);
            $this->update_feed_error('empty_content', $this->last_error);
            return false;
        }

        try {
            // Initialize database if not already done
            if (!$this->ensure_feed_metadata_exists()) {
                $this->last_error = 'Failed to initialize feed metadata';
                $logger->error("Failed to initialize feed metadata - URL: {$this->url}");
                $this->log_error('metadata_init_failed', $this->last_error);
                $this->update_feed_error('metadata_init_failed', $this->last_error);
                return false;
            }
            
            // Instanz der FeedProcessorFactory erstellen und konfigurieren
            $factory = new FeedProcessorFactory($verbose_console);
            
            // Feed-Content verarbeiten
            $items = $factory->process($content);
            
            if (!$items || !is_array($items)) {
                $this->last_error = 'Failed to extract feed items';
                $logger->error("Failed to extract feed items - URL: {$this->url}");
                $this->log_error('item_extraction_failed', $this->last_error);
                $this->update_feed_error('item_extraction_failed', $this->last_error);
                return false;
            }
            
            return $this->process_feed_items($items, $verbose_console);
        } catch (\Exception $e) {
            $this->last_error = $e->getMessage();
            $logger->error("Feed processing error: {$e->getMessage()} - URL: {$this->url}");
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
                    // Erstelle einen eindeutigen item_hash (Kombination aus GUID und Datum)
                    $item_hash = md5($guid . ($pub_date ?? date('Y-m-d H:i:s')));
                    
                    $result = $wpdb->update(
                        $wpdb->prefix . 'feed_raw_items',
                        [
                            'raw_content' => wp_json_encode($item),
                            'pub_date' => $formatted_date ?? current_time('mysql'),
                            'guid' => $guid,
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
                    
                    // Erstelle einen eindeutigen item_hash (Kombination aus GUID und Datum)
                    $item_hash = md5($guid . ($pub_date ?? date('Y-m-d H:i:s')));
                    
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'feed_raw_items',
                        [
                            'feed_id' => $this->post_id,
                            'item_hash' => $item_hash,
                            'guid' => $guid,
                            'raw_content' => wp_json_encode($item),
                            'pub_date' => $formatted_date ?? current_time('mysql'),
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
            // Nur in die Datenbank schreiben, wenn post_id gesetzt ist
            if ($this->post_id !== null) {
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
            }
        } catch (\Exception $e) {
            // Log exception to WordPress error log
            error_log("Athena AI: Exception logging feed error: {$e->getMessage()}");
            error_log("Athena AI Feed Error ({$code}): {$message}");
        }
        
        // Always log to WordPress error log
        $feed_id_info = $this->post_id !== null ? "(Feed ID: {$this->post_id})" : "(URL: {$this->url})"; 
        error_log(sprintf(
            'Athena AI Feed Error [%s]: %s %s',
            $code,
            $message,
            $feed_id_info
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
        
        // Debug-Logging aktivieren
        $debug_mode = get_option('athena_ai_enable_debug_mode', false);
        $verbose_console = defined('DOING_AJAX') && DOING_AJAX;
        
        // Wenn keine post_id gesetzt ist, können wir keine Metadaten aktualisieren
        if ($this->post_id === null) {
            if ($debug_mode) {
                error_log("Athena AI: Cannot update feed metadata with error - no feed ID available");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: Cannot update feed metadata - no feed ID available");</script>';
            }
            return;
        }
        
        if ($verbose_console) {
            echo '<script>console.log("Athena AI Feed: Updating feed metadata with error code [' . esc_js($code) . '] for feed ID ' . $this->post_id . '");</script>';
        }
        
        // First check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'");
        if (!$table_exists) {
            // Log error but don't fail the whole process
            if ($debug_mode) {
                error_log("Athena AI: feed_metadata table does not exist");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: feed_metadata table does not exist");</script>';
            }
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
            if ($debug_mode) {
                error_log("Athena AI: Error checking feed metadata: {$wpdb->last_error}");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: Error checking feed metadata: ' . esc_js($wpdb->last_error) . '");</script>';
            }
            
            // Trotz Fehler versuchen wir es trotzdem mit einem INSERT
            $exists = false;
        }
        
        try {
            if ($exists) {
                if ($verbose_console) {
                    echo '<script>console.log("Athena AI Feed: Updating existing feed metadata with error");</script>';
                }
                
                // Vor dem Update Fehler löschen
                $wpdb->last_error = '';
                
                // Update existing metadata with error information
                $wpdb->suppress_errors(true);
                $result = $wpdb->update(
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
                $wpdb->suppress_errors(false);
                
                if ($result === false && $wpdb->last_error) {
                    if ($debug_mode) {
                        error_log("Athena AI: Error updating feed metadata: {$wpdb->last_error}");
                    }
                    if ($verbose_console) {
                        echo '<script>console.error("Athena AI Feed: Error updating feed metadata: ' . esc_js($wpdb->last_error) . '");</script>';
                    }
                }
            } else {
                if ($verbose_console) {
                    echo '<script>console.log("Athena AI Feed: Creating new feed metadata with error");</script>';
                }
                
                // Bereite Daten vor
                $data = [
                    'feed_id' => $this->post_id,
                    'last_error_date' => $now,
                    'last_error_message' => sprintf('[%s] %s', $code, $message),
                    'fetch_interval' => $this->update_interval,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
                
                // Prüfe, ob URL-Spalte existiert
                $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}feed_metadata");
                if ($columns) {
                    foreach ($columns as $column) {
                        if ($column->Field === 'url' && !empty($this->url)) {
                            $data['url'] = esc_url_raw($this->url);
                            if ($verbose_console) {
                                echo '<script>console.log("Athena AI Feed: Adding URL to metadata: ' . esc_js($this->url) . '");</script>';
                            }
                            break;
                        }
                    }
                }
                
                // Vor dem Einfügen Fehler löschen
                $wpdb->last_error = '';
                
                // Insert new metadata with error information
                $wpdb->suppress_errors(true);
                $result = $wpdb->insert(
                    $wpdb->prefix . 'feed_metadata',
                    $data,
                    ['%d', '%s', '%s', '%d', '%s', '%s', '%s']
                );
                $wpdb->suppress_errors(false);
                
                if ($result === false && $wpdb->last_error) {
                    if ($debug_mode) {
                        error_log("Athena AI: Error creating feed metadata: {$wpdb->last_error}");
                    }
                    if ($verbose_console) {
                        echo '<script>console.error("Athena AI Feed: Error creating feed metadata: ' . esc_js($wpdb->last_error) . '");</script>';
                    }
                }
            }
        } catch (\Exception $e) {
            if ($debug_mode) {
                error_log("Athena AI: Exception in feed error update: {$e->getMessage()}");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: Exception in feed error update: ' . esc_js($e->getMessage()) . '");</script>';
            }
        }
    }

    /**
     * Update feed metadata
     * 
     * @param int $new_items Number of new items
     */
    private function update_feed_metadata(int $new_items): void {
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
            'items_count' => $new_items,
            'updated_at' => $now
        ];
        
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
     * Ensure feed metadata exists in the database
     * 
     * @return bool Whether the metadata exists or was created successfully
     */
    private function ensure_feed_metadata_exists(): bool {
        global $wpdb;
        
        // Debug-Logging aktivieren
        $debug_mode = get_option('athena_ai_enable_debug_mode', false);
        $verbose_console = false; // Initialisierung mit false
        
        // Prüfen, ob verbose_console aktiviert ist
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $verbose_console = true;
        }
        
        // Wenn keine post_id gesetzt ist, können wir keine Metadaten erstellen
        if ($this->post_id === null) {
            if ($debug_mode) {
                error_log("Athena AI: Cannot ensure feed metadata - no feed ID available");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: Cannot ensure feed metadata - no feed ID available");</script>';
            }
            return false;
        }
        
        if ($verbose_console) {
            echo '<script>console.log("Athena AI Feed: Ensuring feed metadata exists for feed ID ' . $this->post_id . '");</script>';
        }
        
        // First check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'");
        if (!$table_exists) {
            if ($debug_mode) {
                error_log("Athena AI: feed_metadata table does not exist");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: feed_metadata table does not exist");</script>';
            }
            return false;
        }
        
        // Vor der Prüfung die letzte DB-Fehlermeldung löschen
        $wpdb->last_error = '';
        
        // Check if metadata already exists with error suppression
        $wpdb->suppress_errors(true);
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}feed_metadata WHERE feed_id = %d",
            $this->post_id
        ));
        $wpdb->suppress_errors(false);
        
        // Prüfen, ob ein Datenbankfehler aufgetreten ist
        if ($wpdb->last_error) {
            if ($debug_mode) {
                error_log("Athena AI: Error checking feed metadata: {$wpdb->last_error}");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: Error checking feed metadata: ' . esc_js($wpdb->last_error) . '");</script>';
            }
            // Bei einem Fehler versuchen wir es trotzdem mit einem INSERT
            $exists = false;
        }
        
        if ($exists) {
            if ($verbose_console) {
                echo '<script>console.log("Athena AI Feed: Feed metadata already exists for feed ID ' . $this->post_id . '");</script>';
            }
            // Metadata already exists
            return true;
        }
        
        // Create new metadata entry
        $now = current_time('mysql');
        
        if ($verbose_console) {
            echo '<script>console.log("Athena AI Feed: Creating new feed metadata for feed ID ' . $this->post_id . '");</script>';
        }
        
        // Check if the URL column exists in the table
        $wpdb->suppress_errors(true);
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}feed_metadata");
        $wpdb->suppress_errors(false);
        
        if ($wpdb->last_error) {
            if ($debug_mode) {
                error_log("Athena AI: Error checking table columns: {$wpdb->last_error}");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: Error checking table columns: ' . esc_js($wpdb->last_error) . '");</script>';
            }
            return false;
        }
        
        $has_url_column = false;
        $has_last_fetched_column = false;
        
        if ($columns) {
            foreach ($columns as $column) {
                if ($column->Field === 'url') {
                    $has_url_column = true;
                }
                if ($column->Field === 'last_fetched') {
                    $has_last_fetched_column = true;
                }
            }
        }
        
        // Prepare data for insertion
        $data = [
            'feed_id' => $this->post_id,
            'fetch_interval' => $this->update_interval,
            'created_at' => $now,
            'updated_at' => $now
        ];
        
        $formats = ['%d', '%d', '%s', '%s'];
        
        // Add URL if the column exists
        if ($has_url_column && !empty($this->url)) {
            $data['url'] = esc_url_raw($this->url);
            $formats[] = '%s';
            
            if ($verbose_console) {
                echo '<script>console.log("Athena AI Feed: Adding URL to metadata: ' . esc_js($this->url) . '");</script>';
            }
        }
        
        // Add last_fetched if the column exists
        if ($has_last_fetched_column) {
            $data['last_fetched'] = $now;
            $formats[] = '%s';
            
            if ($verbose_console) {
                echo '<script>console.log("Athena AI Feed: Adding last_fetched to metadata: ' . esc_js($now) . '");</script>';
            }
        }
        
        // Vor dem Einfügen Fehler löschen
        $wpdb->last_error = '';
        
        // Insert the data
        $wpdb->suppress_errors(true);
        $result = $wpdb->insert(
            $wpdb->prefix . 'feed_metadata',
            $data,
            $formats
        );
        $wpdb->suppress_errors(false);
        
        if ($result === false) {
            if ($debug_mode) {
                error_log("Athena AI: Failed to create feed metadata: {$wpdb->last_error}");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed: Failed to create feed metadata: ' . esc_js($wpdb->last_error) . '");</script>';
                
                // Bei Duplikat-Fehler versuchen wir ein Update
                if (strpos($wpdb->last_error, 'Duplicate entry') !== false) {
                    echo '<script>console.log("Athena AI Feed: Attempting to update existing metadata instead...");</script>';
                    
                    // Entferne feed_id aus den Daten für das Update
                    unset($data['feed_id']);
                    
                    $update_result = $wpdb->update(
                        $wpdb->prefix . 'feed_metadata',
                        $data,
                        ['feed_id' => $this->post_id],
                        $formats,
                        ['%d']
                    );
                    
                    if ($update_result !== false) {
                        echo '<script>console.log("Athena AI Feed: Successfully updated existing metadata");</script>';
                        return true;
                    } else {
                        echo '<script>console.error("Athena AI Feed: Failed to update existing metadata: ' . esc_js($wpdb->last_error) . '");</script>';
                    }
                }
            }
            return false;
        }
        
        if ($debug_mode) {
            error_log("Athena AI: Created feed metadata for feed ID {$this->post_id}");
        }
        if ($verbose_console) {
            echo '<script>console.log("Athena AI Feed: Successfully created feed metadata for feed ID ' . $this->post_id . '");</script>';
        }
        
        return true;
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
