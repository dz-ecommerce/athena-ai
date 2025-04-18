<?php
/**
 * Feed Fetcher class
 * 
 * @package AthenaAI\Admin
 */

namespace AthenaAI\Admin;

use AthenaAI\Models\Feed;

/**
 * FeedFetcher class
 */
class FeedFetcher {
    
    /**
     * Initialize the FeedFetcher
     */
    public static function init(): void {
        // Register the cron hook
        add_action('athena_fetch_feeds', [self::class, 'fetch_all_feeds']);
        
        // Holen des konfigurierten Intervalls
        $interval = get_option('athena_ai_feed_cron_interval', 'hourly');
        
        // Schedule the event if it's not already scheduled or if the interval has changed
        $timestamp = wp_next_scheduled('athena_fetch_feeds');
        $current_interval = wp_get_schedule('athena_fetch_feeds');
        
        if (!$timestamp || $current_interval !== $interval) {
            // Bestehenden Cron-Job entfernen, falls vorhanden
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'athena_fetch_feeds');
            }
            
            // Neuen Cron-Job mit dem konfigurierten Intervall planen
            wp_schedule_event(time(), $interval, 'athena_fetch_feeds');
            
            if (WP_DEBUG) {
                error_log("Athena AI: Feed fetch cron job scheduled with interval: {$interval}");
            }
        }
        
        // Add custom cron schedules
        add_filter('cron_schedules', [self::class, 'add_cron_schedules']);
        
        // Check and update database schema if needed - use init hook to run before headers are sent
        add_action('init', [self::class, 'check_and_update_schema'], 5);
        
        // Add a manual check on admin_init to ensure the cron is registered
        // This helps in cases where the cron might have been unregistered
        add_action('admin_init', function() {
            if (!wp_next_scheduled('athena_fetch_feeds')) {
                $interval = get_option('athena_ai_feed_cron_interval', 'hourly');
                wp_schedule_event(time(), $interval, 'athena_fetch_feeds');
                error_log('Athena AI: Rescheduled missing feed fetch cron event with interval: ' . $interval);
            }
        });
        
        // Add debug action to manually trigger feed fetch (for testing)
        add_action('admin_post_athena_debug_fetch_feeds', function() {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.', 'athena-ai'));
            }
            
            // Zuerst sicherstellen, dass die Datenbankstruktur korrekt ist
            // Dies muss vor dem Abrufen der Feeds erfolgen, um Fehler zu vermeiden
            self::check_and_update_schema();
            
            // Fehlerausgabe unterdrücken, um "Headers already sent"-Probleme zu vermeiden
            global $wpdb;
            $wpdb->suppress_errors(true);
            $show_errors = $wpdb->show_errors;
            $wpdb->show_errors = false;
            
            // Feeds mit Force-Flag abrufen und erweiterte Fehlerausgabe aktivieren
            $result = self::fetch_all_feeds(true, true);
            
            // Fehlerausgabe wiederherstellen
            $wpdb->show_errors = $show_errors;
            $wpdb->suppress_errors(false);
            
            // Debug-Informationen protokollieren
            if (get_option('athena_ai_enable_debug_mode', false)) {
                error_log('Athena AI: Manual feed fetch triggered via admin-post.php');
                error_log('Athena AI: Fetch result - Success: ' . $result['success'] . ', Errors: ' . $result['error']);
            }
            
            // Statt Weiterleitung mit wp_redirect, die JavaScript-Weiterleitung verwenden
            // Dies vermeidet das "Headers already sent"-Problem
            // Parameter für Erfolg und neue Items hinzufügen
            $redirect_url = add_query_arg([
                'page' => 'athena-feed-items',
                'feed_fetched' => 1,
                'success' => $result['success'],
                'error' => $result['error'],
                'new_items' => $result['new_items']
            ], admin_url('admin.php'));
            
            echo '<html><head>';
            echo '<meta http-equiv="refresh" content="0;URL=\'' . esc_url($redirect_url) . '\" />';
            echo '</head><body>';
            echo '<p>' . __('Feed fetch completed. Redirecting...', 'athena-ai') . '</p>';
            echo '</body></html>';
            exit;
        });
    }
    
    /**
     * Add custom cron schedules
     * 
     * @param array $schedules Existing cron schedules
     * @return array Modified cron schedules
     */
    public static function add_cron_schedules(array $schedules): array {
        // Add a 15-minute schedule
        $schedules['athena_15min'] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => __('Every 15 minutes', 'athena-ai')
        ];
        
        // Add a 30-minute schedule
        $schedules['athena_30min'] = [
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display' => __('Every 30 minutes', 'athena-ai')
        ];
        
        // Add a 2-hour schedule
        $schedules['athena_2hours'] = [
            'interval' => 2 * HOUR_IN_SECONDS,
            'display' => __('Every 2 hours', 'athena-ai')
        ];
        
        // Add a 6-hour schedule
        $schedules['athena_6hours'] = [
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __('Every 6 hours', 'athena-ai')
        ];
        
        // Add a 12-hour schedule
        $schedules['athena_12hours'] = [
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => __('Every 12 hours', 'athena-ai')
        ];
        
        return $schedules;
    }
    
    /**
     * Fetch all feeds
     * 
     * @param bool $force_fetch Whether to force fetch all feeds regardless of their update interval
     * @param bool $verbose_console Whether to output verbose debugging information to the JavaScript console
     * @return array Array with success, error, and new items counts
     */
    public static function fetch_all_feeds(bool $force_fetch = false, bool $verbose_console = false): array {
        global $wpdb;
        
        $debug_mode = get_option('athena_ai_enable_debug_mode', false);
        
        if ($debug_mode) {
            error_log('Athena AI: Starting feed fetch process. Force fetch: ' . ($force_fetch ? 'Yes' : 'No'));
        }
        
        // Ensure required tables exist
        if (!self::ensure_required_tables()) {
            if ($debug_mode) {
                error_log('Athena AI: Required tables do not exist. Aborting feed fetch.');
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed Fetcher: Required database tables do not exist. Aborting feed fetch.");</script>';
            }
            $results['error']++;
            $results['details'][] = 'Required database tables do not exist';
            return $results;
        }
        
        // Get feeds to update
        $feeds = $force_fetch ? 
            get_posts([
                'post_type' => 'athena-feed',
                'post_status' => 'publish',
                'numberposts' => -1,
            ]) : 
            self::get_feeds_due_for_update();
        
        if ($debug_mode) {
            error_log('Athena AI: Found ' . count($feeds) . ' feeds to process');
        }
        
        if ($verbose_console) {
            echo '<script>console.log("Athena AI Feed Fetcher: Found ' . count($feeds) . ' feeds to process");</script>';
        }
        
        if (empty($feeds)) {
            if ($debug_mode) {
                error_log('Athena AI: No feeds to process. Exiting.');
            }
            if ($verbose_console) {
                echo '<script>console.warn("Athena AI Feed Fetcher: No feeds to process. Exiting.");</script>';
            }
            return $results;
        }
        
        // Zähler für neue Feed-Items
        $total_new_items = 0;
        
        foreach ($feeds as $feed_post) {
            // Stelle sicher, dass $wpdb auch in diesem Kontext verfügbar ist
            global $wpdb;
            
            try {
                // Ensure feed metadata exists
                if (!self::ensure_feed_metadata_exists($feed_post->ID)) {
                    if ($debug_mode) {
                        error_log('Athena AI: Failed to ensure feed metadata for feed ID ' . $feed_post->ID);
                    }
                    if ($verbose_console) {
                        echo '<script>console.error("Athena AI Feed Fetcher: Failed to ensure feed metadata for feed: ' . esc_js($feed_post->post_title) . ' (ID: ' . $feed_post->ID . ')");</script>';
                    }
                    $results['error']++;
                    $results['details'][] = 'Failed to ensure feed metadata for feed: ' . $feed_post->post_title;
                    continue;
                }
                
                // Get feed URL
                $feed_url = get_post_meta($feed_post->ID, '_athena_feed_url', true);
                
                if (empty($feed_url)) {
                    if ($debug_mode) {
                        error_log('Athena AI: Feed URL is empty for feed ID ' . $feed_post->ID);
                    }
                    if ($verbose_console) {
                        echo '<script>console.error("Athena AI Feed Fetcher: Feed URL is empty for feed: ' . esc_js($feed_post->post_title) . ' (ID: ' . $feed_post->ID . ')");</script>';
                    }
                    $results['error']++;
                    $results['details'][] = 'Feed URL is empty for feed: ' . $feed_post->post_title;
                    continue;
                }
                
                // Prüfen, ob es der Gütersloh-Feed ist
                $is_guetersloh = strpos($feed_url, 'guetersloh') !== false || strpos($feed_url, 'gütersloh') !== false;
                
                // Bei Gütersloh-Feed direkt die Metadaten in der Datenbank erstellen
                if ($is_guetersloh) {
                    if ($verbose_console) {
                        echo '<script>console.log("Athena AI Feed Fetcher: Direct database handling for Gütersloh feed...");</script>';
                    }
                    
                    // Direkte Datenbankbearbeitung
                    $now = current_time('mysql');
                    $update_interval = get_post_meta($feed_post->ID, '_athena_feed_update_interval', true) ?: 3600;
                    
                    // Lösche zuerst alle vorhandenen Metadaten für diesen Feed
                    $wpdb->delete(
                        $wpdb->prefix . 'feed_metadata',
                        ['feed_id' => $feed_post->ID],
                        ['%d']
                    );
                    
                    if ($verbose_console) {
                        echo '<script>console.log("Athena AI Feed Fetcher: Cleaned existing Gütersloh feed metadata");</script>';
                    }
                    
                    // Füge neue Metadaten ein
                    $data = [
                        'feed_id' => $feed_post->ID,
                        'url' => esc_url_raw($feed_url),
                        'fetch_interval' => $update_interval,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                    
                    $formats = ['%d', '%s', '%d', '%s', '%s'];
                    
                    $wpdb->suppress_errors(true);
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'feed_metadata',
                        $data,
                        $formats
                    );
                    $wpdb->suppress_errors(false);
                    
                    if ($result === false) {
                        if ($verbose_console) {
                            echo '<script>console.error("Athena AI Feed Fetcher: Failed to create feed metadata directly: ' . esc_js($wpdb->last_error) . '");</script>';
                        }
                    } else {
                        if ($verbose_console) {
                            echo '<script>console.log("Athena AI Feed Fetcher: Successfully created feed metadata directly");</script>';
                        }
                    }
                }
                
                if ($debug_mode) {
                    error_log('Athena AI: Processing feed: ' . $feed_post->post_title . ' (ID: ' . $feed_post->ID . ', URL: ' . $feed_url . ')');
                }
                
                if ($verbose_console) {
                    echo '<script>console.log("Athena AI Feed Fetcher: Processing feed: ' . esc_js($feed_post->post_title) . ' (ID: ' . $feed_post->ID . ', URL: ' . esc_js($feed_url) . ')");</script>';
                }
                
                // Zähle die aktuellen Feed-Items vor dem Abruf
                $before_count = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE feed_id = %d",
                    $feed_post->ID
                ));
                
                if ($debug_mode) {
                    error_log("Athena AI: Feed {$feed_post->post_title} has {$before_count} items before fetch");
                }
                
                // Create feed model
                $feed = Feed::get_by_id($feed_post->ID);
                
                if (!$feed) {
                    if ($debug_mode) {
                        error_log('Athena AI: Failed to create Feed object for feed ID ' . $feed_post->ID);
                    }
                    if ($verbose_console) {
                        echo '<script>console.error("Athena AI Feed Fetcher: Failed to create Feed object for feed: ' . esc_js($feed_post->post_title) . ' (ID: ' . $feed_post->ID . ')");</script>';
                    }
                    
                    // Für den Gütersloh-Feed einen alternativen Ansatz versuchen
                    if ($is_guetersloh) {
                        if ($verbose_console) {
                            echo '<script>console.log("Athena AI Feed Fetcher: Attempting alternative approach for Gütersloh feed...");</script>';
                        }
                        
                        // Manuell ein Feed-Objekt erstellen
                        $feed = new Feed($feed_url, 3600, true);
                        // Feed-ID manuell setzen
                        $feed->set_id($feed_post->ID);
                        
                        if ($verbose_console) {
                            echo '<script>console.log("Athena AI Feed Fetcher: Manually created Gütersloh feed object");</script>';
                        }
                    } else {
                        $results['error']++;
                        $results['details'][] = 'Failed to create Feed object for feed: ' . $feed_post->post_title;
                        continue;
                    }
                }
                
                // Fetch feed content
                $fetch_result = $feed->fetch($feed_url, $verbose_console);
                
                if ($fetch_result) {
                    $results['success']++;
                    
                    if ($verbose_console) {
                        echo '<script>console.log("Athena AI Feed Fetcher: Successfully fetched feed: ' . esc_js($feed_post->post_title) . '");</script>';
                    }
                    
                    // Zähle die Feed-Items nach dem Abruf
                    $after_count = (int)$wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE feed_id = %d",
                        $feed_post->ID
                    ));
                    
                    if ($debug_mode) {
                        error_log("Athena AI: Feed {$feed_post->post_title} has {$after_count} items after fetch");
                    }
                    
                    // Berechne die Anzahl der neuen Items
                    $new_items = max(0, $after_count - $before_count);
                    $total_new_items += $new_items;
                    
                    // Speichere die Anzahl der neuen Items für diesen Feed
                    update_post_meta($feed_post->ID, '_athena_feed_last_new_items', $new_items);
                    
                    if ($debug_mode) {
                        error_log("Athena AI: Successfully fetched feed: {$feed_post->post_title}. New items: {$new_items}");
                    }
                } else {
                    $results['error']++;
                    $results['details'][] = 'Failed to fetch feed: ' . $feed_post->post_title;
                    
                    if ($debug_mode) {
                        error_log('Athena AI: Failed to fetch feed: ' . $feed_post->post_title);
                    }
                    
                    if ($verbose_console) {
                        // Hole den letzten Fehler aus der Feed-Klasse
                        $last_error = $feed->get_last_error();
                        $error_message = !empty($last_error) ? $last_error : 'Unknown error';
                        echo '<script>console.error("Athena AI Feed Fetcher: Failed to fetch feed: ' . esc_js($feed_post->post_title) . ' - Error: ' . esc_js($error_message) . '");</script>';
                    }
                }
            } catch (\Exception $e) {
                $results['error']++;
                $results['details'][] = 'Error processing feed ' . $feed_post->post_title . ': ' . $e->getMessage();
                
                if ($debug_mode) {
                    error_log('Athena AI: Exception while processing feed: ' . $e->getMessage());
                }
                
                if ($verbose_console) {
                    echo '<script>console.error("Athena AI Feed Fetcher: Exception while processing feed ' . esc_js($feed_post->post_title) . ': ' . esc_js($e->getMessage()) . '");</script>';
                }
            }
            
            // Add a small delay to prevent overwhelming external servers
            usleep(500000); // 0.5 second delay
        }
        
        // Speichere die Gesamtzahl der neuen Items
        $results['new_items'] = $total_new_items;
        update_option('athena_last_feed_new_items', $total_new_items);
        update_option('athena_last_feed_fetch', time());
        
        if ($debug_mode) {
            error_log("Athena AI: Feed fetch process completed. Success: {$results['success']}, Errors: {$results['error']}, New items: {$total_new_items}");
        }
        
        if ($verbose_console) {
            echo '<script>console.log("Athena AI Feed Fetcher: Feed fetch process completed. Success: ' . $results['success'] . ', Errors: ' . $results['error'] . ', New items: ' . $total_new_items . '");</script>';
            
            // Detaillierte Fehlerinformationen in die Konsole ausgeben
            if (!empty($results['details'])) {
                echo '<script>console.group("Athena AI Feed Fetcher: Error Details");</script>';
                foreach ($results['details'] as $index => $error_message) {
                    echo '<script>console.error("Error ' . ($index + 1) . ': ' . esc_js($error_message) . '");</script>';
                }
                echo '<script>console.groupEnd();</script>';
            }
        }
        
        return $results;
    }
    
    /**
     * Ensure all required tables exist
     * 
     * @return bool True if all required tables exist or were created successfully
     */
    private static function ensure_required_tables(): bool {
        global $wpdb;
        
        // Check if the required tables exist
        $metadata_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'");
        $items_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_raw_items'");
        $errors_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_errors'");
        
        // If all tables exist, we're good
        if ($metadata_exists && $items_exists && $errors_exists) {
            // Check if we need to update the schema
            self::check_and_update_schema();
            return true;
        }
        
        // Otherwise, create the missing tables
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create feed_metadata table if it doesn't exist
        if (!$metadata_exists) {
            $sql = "CREATE TABLE {$wpdb->prefix}feed_metadata (
                feed_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                last_fetched DATETIME DEFAULT NULL,
                fetch_interval INT DEFAULT 3600,
                fetch_count INT DEFAULT 0,
                last_error_date DATETIME DEFAULT NULL,
                last_error_message TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) $charset_collate;";
            
            dbDelta($sql);
        }
        
        // Create feed_raw_items table if it doesn't exist
        if (!$items_exists) {
            $sql = "CREATE TABLE {$wpdb->prefix}feed_raw_items (
                item_hash CHAR(32) PRIMARY KEY,
                feed_id BIGINT UNSIGNED NOT NULL,
                raw_content LONGTEXT,
                pub_date DATETIME,
                guid VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (feed_id),
                INDEX (pub_date)
            ) $charset_collate;";
            
            dbDelta($sql);
        }
        
        // Create feed_errors table if it doesn't exist
        if (!$errors_exists) {
            $sql = "CREATE TABLE {$wpdb->prefix}feed_errors (
                error_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                feed_id BIGINT UNSIGNED NOT NULL,
                error_code VARCHAR(32),
                error_message TEXT,
                created DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (feed_id),
                INDEX (created)
            ) $charset_collate;";
            
            dbDelta($sql);
        }
        
        // Check if all tables were created successfully
        $metadata_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'");
        $items_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_raw_items'");
        $errors_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_errors'");
        
        return $metadata_exists && $items_exists && $errors_exists;
    }
    
    /**
     * Check and update the database schema if needed
     */
    public static function check_and_update_schema(): void {
        global $wpdb;
        
        // Debug-Logging aktivieren
        $debug_mode = get_option('athena_ai_enable_debug_mode', false);
        // Keine Konsolenausgaben in dieser Methode, da sie während der WordPress-Initialisierung aufgerufen wird
        // und "Headers already sent"-Fehler verursachen kann
        
        // Fehlerausgabe unterdrücken, um "Headers already sent"-Probleme zu vermeiden
        $wpdb->suppress_errors(true);
        $show_errors = $wpdb->show_errors;
        $wpdb->show_errors = false;
        
        try {
            // Prüfen, ob die Tabelle existiert
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'") === $wpdb->prefix . 'feed_metadata';
            
            if (!$table_exists) {
                // Tabelle existiert nicht, daher nichts zu aktualisieren
                if ($debug_mode) {
                    error_log('Athena AI: feed_metadata table does not exist, cannot update schema');
                }
                // Keine Konsolenausgaben hier, um "Headers already sent"-Probleme zu vermeiden
                return;
            }
            
            // Alle Spalten abrufen, um den aktuellen Zustand der Tabelle zu prüfen
            $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}feed_metadata");
            
            if ($columns === false || $columns === null) {
                if ($debug_mode) {
                    error_log('Athena AI: Failed to get columns from feed_metadata table. Error: ' . $wpdb->last_error);
                }
                // Keine Konsolenausgaben hier, um "Headers already sent"-Probleme zu vermeiden
                return;
            }
            
            $column_names = array_map(function($col) { return $col->Field; }, $columns);
            
            if ($debug_mode) {
                error_log('Athena AI: Current feed_metadata table columns: ' . implode(', ', $column_names));
            }
            // Keine Konsolenausgaben hier, um "Headers already sent"-Probleme zu vermeiden
            
            // Prüfen und Hinzufügen der last_fetched-Spalte, die in den Fehlermeldungen fehlt
            if (!in_array('last_fetched', $column_names)) {
                $result = $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_metadata ADD COLUMN last_fetched DATETIME DEFAULT NULL");
                if ($debug_mode) {
                    error_log('Athena AI: Added missing column last_fetched to feed_metadata table. Result: ' . ($result !== false ? 'success' : 'failed'));
                }
                // Keine Konsolenausgaben hier, um "Headers already sent"-Probleme zu vermeiden
            }
            
            // Add missing columns one by one without referencing other columns
            if (!in_array('fetch_interval', $column_names)) {
                $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_metadata ADD COLUMN fetch_interval INT DEFAULT 3600");
            }
            
            if (!in_array('fetch_count', $column_names)) {
                $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_metadata ADD COLUMN fetch_count INT DEFAULT 0");
            }
            
            if (!in_array('last_error_date', $column_names)) {
                $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_metadata ADD COLUMN last_error_date DATETIME DEFAULT NULL");
            }
            
            if (!in_array('last_error_message', $column_names)) {
                $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_metadata ADD COLUMN last_error_message TEXT");
            }
            
            if (!in_array('created_at', $column_names)) {
                $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_metadata ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
            }
            
            if (!in_array('updated_at', $column_names)) {
                $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_metadata ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP");
            }
        } catch (\Exception $e) {
            if ($debug_mode) {
                error_log('Athena AI: Exception in check_and_update_schema: ' . $e->getMessage());
            }
            // Keine Konsolenausgaben hier, um "Headers already sent"-Probleme zu vermeiden
        }
        
        // Restore error display settings
        $wpdb->show_errors = $show_errors;
        $wpdb->suppress_errors(false);
    }
    
    /**
     * Ensures that a feed metadata entry exists for the given feed ID
     * 
     * @param int $feed_id The feed ID (post_id)
     * @return bool True if the metadata exists or was created successfully
     */
    private static function ensure_feed_metadata_exists(int $feed_id): bool {
        global $wpdb;
        
        // Debug-Logging aktivieren
        $debug_mode = get_option('athena_ai_enable_debug_mode', false);
        $verbose_console = true; // Immer Konsolenausgaben aktivieren für diese kritische Funktion
        
        // Prüfen, ob die Tabelle existiert
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'") === $wpdb->prefix . 'feed_metadata';
        if (!$table_exists) {
            if ($debug_mode) {
                error_log("Athena AI: Feed metadata table does not exist. Cannot ensure metadata.");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed Fetcher: Feed metadata table does not exist. Cannot ensure metadata.");</script>';
            }
            return false;
        }
        
        try {
            // Hole die Feed-URL aus den Post-Meta-Daten
            $feed_url = get_post_meta($feed_id, '_athena_feed_url', true);
            
            // Check if entry already exists by feed_id or URL
            $exists = false;
            
            // Prüfen, ob ein Eintrag mit dieser feed_id existiert
            $exists_by_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT feed_id FROM {$wpdb->prefix}feed_metadata WHERE feed_id = %d",
                    $feed_id
                )
            );
            
            if ($exists_by_id) {
                $exists = true;
            }
            
            // Prüfen, ob ein Eintrag mit dieser URL existiert (falls URL vorhanden)
            if (!$exists && !empty($feed_url)) {
                $exists_by_url = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT feed_id FROM {$wpdb->prefix}feed_metadata WHERE url = %s",
                        esc_url_raw($feed_url)
                    )
                );
                
                if ($exists_by_url) {
                    if ($debug_mode) {
                        error_log("Athena AI: Feed metadata already exists with URL {$feed_url} for feed ID {$exists_by_url}, but trying to create for feed ID {$feed_id}");
                    }
                    
                    // Wenn ein Eintrag mit dieser URL existiert, aber eine andere feed_id hat,
                    // aktualisieren wir den vorhandenen Eintrag mit der neuen feed_id
                    if ($exists_by_url != $feed_id) {
                        $update_result = $wpdb->update(
                            $wpdb->prefix . 'feed_metadata',
                            ['feed_id' => $feed_id],
                            ['feed_id' => $exists_by_url],
                            ['%d'],
                            ['%d']
                        );
                        
                        if ($debug_mode) {
                            error_log("Athena AI: Updated feed metadata from feed ID {$exists_by_url} to {$feed_id}. Result: " . ($update_result !== false ? 'success' : 'failed'));
                        }
                    }
                    
                    $exists = true;
                }
            }
            
            // If no entry exists, create one
            if (!$exists) {
                // Get feed update interval from post meta
                $update_interval = get_post_meta($feed_id, '_athena_feed_update_interval', true);
                if (!$update_interval) {
                    $update_interval = 3600; // Default: 1 hour
                }
                
                $now = current_time('mysql');
                
                // Prüfen, ob die Spalten in der Tabelle existieren
                $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}feed_metadata");
                
                if (!$columns) {
                    if ($debug_mode) {
                        error_log("Athena AI: Failed to get columns from feed_metadata table. Error: " . $wpdb->last_error);
                    }
                    return false;
                }
                
                $column_names = array_map(function($col) { return $col->Field; }, $columns);
                
                if ($debug_mode) {
                    error_log("Athena AI: Feed metadata table columns: " . implode(', ', $column_names));
                    error_log("Athena AI: Feed ID: {$feed_id}, Update interval: {$update_interval}");
                }
                
                // Feed-URL wurde bereits oben geholt
                
                // Daten für das Einfügen vorbereiten
                $data = [
                    'feed_id' => $feed_id,
                    'fetch_interval' => $update_interval,
                    'fetch_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
                
                // Prüfen, ob last_fetched-Spalte existiert
                if (in_array('last_fetched', $column_names)) {
                    $data['last_fetched'] = $now;
                }
                
                // Prüfen, ob url-Spalte existiert und URL-Wert setzen
                if (in_array('url', $column_names) && !empty($feed_url)) {
                    $data['url'] = esc_url_raw($feed_url);
                }
                
                // Formatierungstypen für die Daten
                $format_types = [];
                foreach ($data as $key => $value) {
                    if (is_int($value)) {
                        $format_types[] = '%d';
                    } elseif (is_float($value)) {
                        $format_types[] = '%f';
                    } else {
                        $format_types[] = '%s';
                    }
                }
                
                $result = $wpdb->insert(
                    $wpdb->prefix . 'feed_metadata',
                    $data,
                    $format_types
                );
                
                if ($result === false) {
                    if ($debug_mode) {
                        error_log("Athena AI: Failed to insert feed metadata for feed ID {$feed_id}. Error: " . $wpdb->last_error);
                    }
                    if ($verbose_console) {
                        echo '<script>console.error("Athena AI Feed Fetcher: Failed to insert feed metadata for feed ID ' . $feed_id . '. Database error: ' . esc_js($wpdb->last_error) . '");</script>';
                        echo '<script>console.log("Data being inserted: ", ' . json_encode($data) . ');</script>';
                        echo '<script>console.log("Format types: ", ' . json_encode($format_types) . ');</script>';
                    }
                    return false;
                }
                
                if ($debug_mode) {
                    error_log("Athena AI: Successfully created feed metadata for feed ID {$feed_id}");
                }
                
                return true;
            }
            
            return true;
        } catch (\Exception $e) {
            if ($debug_mode) {
                error_log("Athena AI: Exception in ensure_feed_metadata_exists: " . $e->getMessage());
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed Fetcher: Exception in ensure_feed_metadata_exists for feed ID ' . $feed_id . ': ' . esc_js($e->getMessage()) . '");</script>';
                echo '<script>console.log("Exception trace: ", "' . esc_js($e->getTraceAsString()) . '");</script>';
            }
            return false;
        }
    }
    
    
    /**
     * Get feeds that are due for an update based on their fetch interval
     * 
     * @return array Array of feed post objects
     */
    private static function get_feeds_due_for_update(): array {
        global $wpdb;
        
        // Debug-Logging aktivieren
        $debug_mode = get_option('athena_ai_enable_debug_mode', false);
        
        // Get current time
        $now = current_time('mysql');
        
        if ($debug_mode) {
            error_log('Athena AI: Checking for feeds due for update at ' . $now);
            
            // Zähle alle aktiven Feeds
            $total_feeds = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} p 
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_athena_feed_active' 
                WHERE p.post_type = 'athena-feed' 
                AND p.post_status = 'publish' 
                AND (pm.meta_value = '1' OR pm.meta_value IS NULL)"
            );
            
            error_log('Athena AI: Total active feeds: ' . $total_feeds);
            
            // Prüfe, ob die Metadaten-Tabelle existiert
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'") === $wpdb->prefix . 'feed_metadata';
            if (!$table_exists) {
                error_log('Athena AI: Feed metadata table does not exist!');
            }
        }
        
        // Find feeds that need updating
        $query = $wpdb->prepare(
            "SELECT f.feed_id, f.last_fetched, f.fetch_interval, 
                   TIMESTAMPDIFF(SECOND, f.last_fetched, %s) as seconds_since_last_fetch
            FROM {$wpdb->prefix}feed_metadata f
            JOIN {$wpdb->posts} p ON f.feed_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_athena_feed_active'
            WHERE p.post_type = 'athena-feed'
            AND p.post_status = 'publish'
            AND (pm.meta_value = '1' OR pm.meta_value IS NULL)",
            $now
        );
        
        if ($debug_mode) {
            // Hole alle Feeds mit ihren Zeitintervallen
            $all_feeds = $wpdb->get_results($query);
            
            if (!empty($all_feeds)) {
                error_log('Athena AI: Found ' . count($all_feeds) . ' feeds in metadata table');
                
                foreach ($all_feeds as $feed) {
                    $feed_title = get_the_title($feed->feed_id);
                    $is_due = ($feed->last_fetched === null || $feed->seconds_since_last_fetch > $feed->fetch_interval);
                    
                    error_log(sprintf(
                        'Athena AI: Feed "%s" (ID: %d) - Last fetched: %s, Interval: %d seconds, Time since last fetch: %d seconds, Due for update: %s',
                        $feed_title,
                        $feed->feed_id,
                        $feed->last_fetched ?: 'never',
                        $feed->fetch_interval,
                        $feed->seconds_since_last_fetch ?: 0,
                        $is_due ? 'YES' : 'NO'
                    ));
                }
            } else {
                error_log('Athena AI: No feeds found in metadata table');
            }
        }
        
        // Jetzt die eigentliche Abfrage für fällige Feeds
        $query = $wpdb->prepare(
            "SELECT f.feed_id 
            FROM {$wpdb->prefix}feed_metadata f
            JOIN {$wpdb->posts} p ON f.feed_id = p.ID
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_athena_feed_active'
            WHERE p.post_type = 'athena-feed'
            AND p.post_status = 'publish'
            AND (pm.meta_value = '1' OR pm.meta_value IS NULL)
            AND (
                f.last_fetched IS NULL
                OR TIMESTAMPDIFF(SECOND, f.last_fetched, %s) > f.fetch_interval
            )",
            $now
        );
        
        $feed_ids = $wpdb->get_col($query);
        
        if ($debug_mode) {
            if (empty($feed_ids)) {
                error_log('Athena AI: No feeds are due for update');
            } else {
                error_log('Athena AI: ' . count($feed_ids) . ' feeds are due for update');
            }
        }
        
        if (empty($feed_ids)) {
            return [];
        }
        
        // Get the actual feed posts
        $feeds = get_posts([
            'post_type' => 'athena-feed',
            'post_status' => 'publish',
            'numberposts' => -1,
            'include' => $feed_ids
        ]);
        
        if ($debug_mode && !empty($feeds)) {
            foreach ($feeds as $feed) {
                error_log('Athena AI: Feed due for update: ' . $feed->post_title . ' (ID: ' . $feed->ID . ')');
            }
        }
        
        return $feeds;
    }
}
