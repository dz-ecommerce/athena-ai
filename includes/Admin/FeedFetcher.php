<?php
/**
 * Feed Fetcher class
 * 
 * @package AthenaAI\Admin
 */

namespace AthenaAI\Admin;

use AthenaAI\Models\Feed;
use AthenaAI\Services\LoggerService;
use AthenaAI\Services\FeedService;

/**
 * FeedFetcher class
 */
class FeedFetcher {
    
    /**
     * Initialize the FeedFetcher
     */
    public static function init(): void {
        // Cron‑Handling in dedizierten Service ausgelagert
        \AthenaAI\Services\CronScheduler::init();
        
        // Füge den Event-Hook zum eigentlichen Fetch hinzu
        \add_action('athena_fetch_feeds', [self::class, 'fetch_all_feeds']);
        
        // Check and update database schema if needed – use init hook before headers
        \add_action('init', [self::class, 'check_and_update_schema'], 5);
        
        // Add debug action to manually trigger feed fetch (for testing)
        \add_action('admin_post_athena_debug_fetch_feeds', function() {
            if (!\current_user_can('manage_options')) {
                \wp_die(\__('You do not have sufficient permissions to access this page.', 'athena-ai'));
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
            ob_start();
            $result = self::fetch_all_feeds(true, true);
            $console_scripts = ob_get_clean();
            
            // Fehlerausgabe wiederherstellen
            $wpdb->show_errors = $show_errors;
            $wpdb->suppress_errors(false);
            
            // Debug-Informationen protokollieren
            $logger = LoggerService::getInstance()->setComponent('FeedFetcher');
            $logger->info('Manual feed fetch triggered via admin-post.php');
            $logger->info(sprintf('Fetch result - Success: %d, Errors: %d, New items: %d', 
                $result['success'], 
                $result['error'],
                $result['new_items']
            ));
            
            // Statt Weiterleitung mit wp_redirect, die JavaScript-Weiterleitung verwenden
            // Dies vermeidet das "Headers already sent"-Problem
            // Parameter für Erfolg und neue Items hinzufügen
            $redirect_url = \add_query_arg([
                'page' => 'athena-feed-items',
                'feed_fetched' => 1,
                'success' => $result['success'],
                'error' => $result['error'],
                'new_items' => $result['new_items']
            ], \admin_url('admin.php'));
            
            echo '<html><head></head><body>';
            echo $console_scripts;
            echo '<p>' . \__('Feed fetch completed. Redirecting in 3 seconds...', 'athena-ai') . '</p>';
            // Stelle sicher, dass $redirect_url ein String ist
            $redirect_url = is_string($redirect_url) ? $redirect_url : '';
            echo '<script>setTimeout(function(){ window.location.href = "' . \esc_url($redirect_url) . '"; }, 3000);</script>';
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
    // Cron‑Schedules now registered in CronScheduler service – keep method for
    // BC but delegate.
    public static function add_cron_schedules(array $schedules): array {
        return \AthenaAI\Services\CronScheduler::add_cron_schedules($schedules);
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
        
        // Initialisiere das Results-Array mit Standardwerten
        $results = [
            'success' => 0,
            'error' => 0,
            'new_items' => 0,
            'details' => []
        ];
        
        // Get debug mode setting
        $debug_mode = \get_option('athena_debug_mode', '0') === '1';
        $logger = LoggerService::getInstance()->setComponent('FeedFetcher');
        
        if ($debug_mode) {
            $logger->debug('Starting feed fetch process...');
        }
        
        if ($verbose_console) {
            echo '<script>console.log("Athena AI Feed Fetcher: Starting feed fetch process...");</script>';
            echo '<script>console.group("Environment Information");</script>';
            echo '<script>console.log("WordPress Version: ' . \get_bloginfo('version') . '");</script>';
            echo '<script>console.log("PHP Version: ' . phpversion() . '");</script>';
        }
        
        // Ensure required tables exist
        if (!self::ensure_required_tables()) {
            $logger->error('Required tables do not exist. Aborting feed fetch.');
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed Fetcher: Required tables do not exist. Aborting feed fetch.");</script>';
            }
            $results['error']++;
            $results['details'][] = 'Required database tables do not exist';
            return $results;
        }
        
        // Get all active feeds if force fetch is enabled, otherwise get only feeds due for update
        $feeds = $force_fetch 
            ? \get_posts([
                'post_type' => 'athena-feed',
                'post_status' => 'publish',
                'numberposts' => -1,
                'meta_query' => [
                    [
                        'key' => '_athena_feed_active',
                        'value' => '0',
                        'compare' => '!='
                    ]
                ]
            ]) 
            : self::get_feeds_due_for_update();
        
        if (empty($feeds)) {
            $logger->info('No feeds to update found.');
            if ($verbose_console) {
                echo '<script>console.warn("Athena AI Feed Fetcher: No feeds to process. Exiting.");</script>';
            }
            return $results;
        }
        
        if ($debug_mode) {
            $logger->info(sprintf('Found %d feeds to update', count($feeds)));
        }
        
        if ($verbose_console) {
            echo '<script>console.log("Athena AI Feed Fetcher: Found ' . count($feeds) . ' feeds to update");</script>';
        }
        
        // Zähler für neue Feed-Items
        $total_new_items = 0;
        
        // Verarbeite jeden Feed einzeln
        foreach ($feeds as $feed_post) {
            $result = self::process_single_feed($feed_post, $logger, $debug_mode, $verbose_console);
            
            // Aktualisiere die Ergebnisse
            $results['success'] += $result['success'];
            $results['error'] += $result['error'];
            $total_new_items += $result['new_items'];
            
            if (isset($result['details']) && !empty($result['details'])) {
                $results['details'] = array_merge($results['details'], $result['details']);
            }
        }
        
        // Aktualisiere den Zeitstempel für den letzten Feed-Abruf
        \update_option('athena_last_feed_fetch', time());
        \update_option('athena_last_feed_new_items', $total_new_items);
        
        // Füge die Gesamtzahl der neuen Elemente zum Ergebnis hinzu
        $results['new_items'] = $total_new_items;
        
        if ($debug_mode) {
            $logger->info(sprintf('Feed fetch process completed. Success: %d, Errors: %d, New items: %d',
                $results['success'], 
                $results['error'],
                $total_new_items
            ));
        }
        
        if ($verbose_console) {
            echo '<script>console.log("Athena AI Feed Fetcher: Feed fetch process completed. Success: ' . $results['success'] . ', Errors: ' . $results['error'] . ', New items: ' . $total_new_items . '");</script>';
            
            // Detaillierte Fehlerinformationen in die Konsole ausgeben
            if (!empty($results['details'])) {
                echo '<script>console.group("Athena AI Feed Fetcher: Error Details");</script>';
                foreach ($results['details'] as $index => $error_message) {
                    echo '<script>console.error("Error ' . ($index + 1) . ': ' . \esc_js($error_message) . '");</script>';
                }
                echo '<script>console.groupEnd();</script>';
            }
        }
        
        return $results;
    }
    
    /**
     * Process a single feed and return processing results
     * 
     * @param object $feed_post The feed post object
     * @param LoggerService $logger Logger instance
     * @param bool $debug_mode Whether debug mode is enabled
     * @param bool $verbose_console Whether to output verbose console logs
     * @return array Processing results
     */
    private static function process_single_feed($feed_post, LoggerService $logger, bool $debug_mode, bool $verbose_console): array {
        global $wpdb;
        
        $result = [
            'success' => 0,
            'error' => 0,
            'new_items' => 0,
            'details' => []
        ];
        
        try {
            // Ensure feed metadata exists
            if (!self::ensure_feed_metadata_exists($feed_post->ID)) {
                $logger->error('Failed to ensure feed metadata for feed ID ' . $feed_post->ID);
                if ($verbose_console) {
                    echo '<script>console.error("Athena AI Feed Fetcher: Failed to ensure feed metadata for feed: ' . \esc_js($feed_post->post_title) . '");</script>';
                }
                $result['error']++;
                $result['details'][] = 'Failed to ensure feed metadata for feed: ' . $feed_post->post_title;
                return $result;
            }
            
            // Get feed URL
            $feed_url = \get_post_meta($feed_post->ID, '_athena_feed_url', true);
            
            if (empty($feed_url)) {
                $logger->error('Feed URL is empty for feed ID ' . $feed_post->ID);
                if ($verbose_console) {
                    echo '<script>console.error("Athena AI Feed Fetcher: Feed URL is empty for feed: ' . \esc_js($feed_post->post_title) . '");</script>';
                }
                $result['error']++;
                $result['details'][] = 'Feed URL is empty for feed: ' . $feed_post->post_title;
                return $result;
            }
            
            $logger->info(sprintf('Processing feed: %s (ID: %d, URL: %s)', 
                $feed_post->post_title, 
                $feed_post->ID, 
                $feed_url
            ));
            
            if ($verbose_console) {
                echo '<script>console.log("Athena AI Feed Fetcher: Processing feed: ' . \esc_js($feed_post->post_title) . ' (ID: ' . $feed_post->ID . ', URL: ' . \esc_js($feed_url) . ')");</script>';
            }
            
            // Zähle die aktuellen Feed-Items vor dem Abruf
            $before_count = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE feed_id = %d",
                $feed_post->ID
            ));
            
            $logger->debug(sprintf('Feed %s has %d items before fetch', 
                $feed_post->post_title, 
                $before_count
            ));
            
            // Get feed update interval
            $feed_update_interval = (int) \get_post_meta($feed_post->ID, '_athena_feed_update_interval', true);
            if (empty($feed_update_interval)) {
                $feed_update_interval = 3600; // Default: 1 hour
            }
            
            // Create feed model and process it
            $feed = new Feed(
                \esc_url_raw($feed_url),
                $feed_update_interval
            );
            $feed->set_post_id($feed_post->ID);
            
            // Use FeedService for processing - use the static factory method to properly initialize all dependencies
            $feedService = \AthenaAI\Services\FeedService::create();
            $success = $feedService->fetch_and_process_feed($feed, $verbose_console);
            
            if ($success) {
                $result['success']++;
                
                // Get the count after fetching
                $after_count = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE feed_id = %d",
                    $feed_post->ID
                ));
                
                $logger->debug(sprintf('Feed %s has %d items after fetch', 
                    $feed_post->post_title, 
                    $after_count
                ));                
                
                // Calculate new items
                $new_items = max(0, $after_count - $before_count);
                $result['new_items'] = $new_items;
                
                // Save the count of new items for this feed
                \update_post_meta($feed_post->ID, '_athena_feed_last_new_items', $new_items);
                
                $logger->info(sprintf('Successfully fetched feed: %s. New items: %d', 
                    $feed_post->post_title, 
                    $new_items
                ));
            } else {
                $result['error']++;
                $result['details'][] = 'Failed to fetch feed: ' . $feed_post->post_title;
                
                $logger->error(sprintf('Failed to fetch feed: %s', $feed_post->post_title));                  
                
                if ($verbose_console) {
                // Get the last error from the feed
                $last_error = '';
                if (method_exists($feed, 'get_last_error')) {
                    $last_error = $feed->get_last_error();
                }
                if (!empty($last_error)) {
                    echo '<script>console.error("Athena AI Feed Fetcher: Failed to fetch feed: ' . \esc_js($feed_post->post_title) . ' - Error: ' . \esc_js($last_error) . '");</script>';
                    } else {
                        echo '<script>console.error("Athena AI Feed Fetcher: Failed to fetch feed: ' . \esc_js($feed_post->post_title) . ' - Unknown error");</script>';
                    }
                }
            }
        } catch (\Exception $e) {
            $result['error']++;
            $result['details'][] = 'Error processing feed ' . $feed_post->post_title . ': ' . $e->getMessage();
            
            $logger->error(sprintf('Exception while processing feed: %s', $e->getMessage()));              
            
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed Fetcher: Exception while processing feed ' . \esc_js($feed_post->post_title) . ': ' . \esc_js($e->getMessage()) . '");</script>';
            }
        }
        
        return $result;
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
        if (!defined('ABSPATH')) {
            require_once dirname(dirname(dirname(__FILE__))) . '/wp-admin/includes/upgrade.php';
        } else {
            require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
        }
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
            \dbDelta($sql);
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
            \dbDelta($sql);
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
            \dbDelta($sql);
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
        $debug_mode = \get_option('athena_ai_enable_debug_mode', false);
        // Keine Konsolenausgaben in dieser Methode, da sie während der WordPress-Initialisierung aufgerufen wird
        // und "Headers already sent"-Fehler verursachen kann
        
        // Fehlerausgabe unterdrücken, um "Headers already sent"-Probleme zu vermeiden
        $wpdb->suppress_errors(true);
        $show_errors = $wpdb->show_errors;
        $wpdb->show_errors = false;
        
        try {
            // Logger-Instanz für Debug-Modus vorbereiten
            $logger = null;
            if ($debug_mode) {
                $logger = \AthenaAI\Services\LoggerService::getInstance()->setComponent('FeedFetcher');
            }
            
            // 1. Prüfe und aktualisiere feed_metadata Tabelle
            self::check_and_update_feed_metadata_table($logger);
            
            // 2. Prüfe und aktualisiere feed_raw_items Tabelle
            self::check_and_update_feed_raw_items_table($logger);
            
        } catch (\Exception $e) {
            if ($debug_mode) {
                $logger = \AthenaAI\Services\LoggerService::getInstance()->setComponent('FeedFetcher');
                $logger->error('Exception in check_and_update_schema: ' . $e->getMessage());
            }
            // Keine Konsolenausgaben hier, um "Headers already sent"-Probleme zu vermeiden
        }
        
        // Restore error display settings
        $wpdb->show_errors = $show_errors;
        $wpdb->suppress_errors(false);
    }

    /**
     * Check and update the feed_metadata table structure
     * 
     * @param \AthenaAI\Services\LoggerService|null $logger Logger instance for debug output
     */
    private static function check_and_update_feed_metadata_table($logger = null): void {
        global $wpdb;
        
        // Prüfen, ob die Tabelle existiert
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'") === $wpdb->prefix . 'feed_metadata';
        
        if (!$table_exists) {
            // Tabelle existiert nicht, daher nichts zu aktualisieren
            if ($logger) {
                $logger->error('feed_metadata table does not exist, cannot update schema');
            }
            return;
        }
        
        // Alle Spalten abrufen, um den aktuellen Zustand der Tabelle zu prüfen
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}feed_metadata");
        
        if ($columns === false || $columns === null) {
            if ($logger) {
                $logger->error('Failed to get columns from feed_metadata table. Error: ' . $wpdb->last_error);
            }
            return;
        }
        
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        if ($logger) {
            $logger->debug('Current feed_metadata table columns: ' . implode(', ', $column_names));
        }
        
        // Prüfen und Hinzufügen der last_fetched-Spalte, die in den Fehlermeldungen fehlt
        if (!in_array('last_fetched', $column_names)) {
            $result = $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_metadata ADD COLUMN last_fetched DATETIME DEFAULT NULL");
            if ($logger) {
                $logger->info('Added missing column last_fetched to feed_metadata table. Result: ' . ($result !== false ? 'success' : 'failed'));
            }
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
    }

    /**
     * Check and update the feed_raw_items table structure
     * 
     * @param \AthenaAI\Services\LoggerService|null $logger Logger instance for debug output
     */
    private static function check_and_update_feed_raw_items_table($logger = null): void {
        global $wpdb;
        
        // Prüfen, ob die Tabelle existiert
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_raw_items'") === $wpdb->prefix . 'feed_raw_items';
        
        // Wenn die Tabelle nicht existiert, erstellen wir sie mit der korrekten Struktur
        if (!$table_exists) {
            // Tabelle existiert nicht, daher müssen wir sie erstellen
            if ($logger) {
                $logger->error('feed_raw_items table does not exist, creating it');
            }
            
            // Tabelle erstellen
            if (!defined('ABSPATH')) {
                require_once dirname(dirname(dirname(__FILE__))) . '/wp-admin/includes/upgrade.php';
            } else {
                require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
            }
            
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE {$wpdb->prefix}feed_raw_items (
                id BIGINT UNSIGNED AUTO_INCREMENT,
                item_hash CHAR(32) NOT NULL,
                feed_id BIGINT UNSIGNED NOT NULL,
                raw_content LONGTEXT,
                pub_date DATETIME,
                guid VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY (item_hash),
                INDEX (feed_id),
                INDEX (pub_date)
            ) $charset_collate;";
            
            \dbDelta($sql);
            
            if ($logger) {
                $logger->info('Created feed_raw_items table with correct structure');
            }
            return;
        }
        
        // Die Tabelle existiert, wir prüfen die Spalten
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}feed_raw_items");
        
        if ($columns === false || $columns === null) {
            if ($logger) {
                $logger->error('Failed to get columns from feed_raw_items table. Error: ' . $wpdb->last_error);
            }
            return;
        }
        
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        if ($logger) {
            $logger->debug('Current feed_raw_items table columns: ' . implode(', ', $column_names));
        }
        
        // Prüfen, ob die ID-Spalte existiert, falls nicht, fügen wir sie hinzu
        if (!in_array('id', $column_names)) {
            // Wir müssen eine ID-Spalte hinzufügen
            $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items ADD COLUMN id BIGINT UNSIGNED AUTO_INCREMENT FIRST");
            
            // Prüfen, ob es einen bestehenden Primärschlüssel gibt
            $primary_key = $wpdb->get_results("SHOW KEYS FROM {$wpdb->prefix}feed_raw_items WHERE Key_name = 'PRIMARY'");
            
            if (!empty($primary_key)) {
                // Es gibt bereits einen Primärschlüssel, wir müssen ihn zuerst entfernen
                $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items DROP PRIMARY KEY");
                
                if ($logger) {
                    $logger->info('Dropped existing primary key from feed_raw_items table');
                }
            }
            
            // Jetzt setzen wir id als Primärschlüssel
            $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items ADD PRIMARY KEY (id)");
            
            if ($logger) {
                $logger->info('Added id column and set it as primary key for feed_raw_items table');
            }
        }
        
        // Prüfen und Hinzufügen der item_hash-Spalte, die in den Fehlermeldungen fehlt
        if (!in_array('item_hash', $column_names)) {
            // Wir fügen die Spalte hinzu
            $result = $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items ADD COLUMN item_hash CHAR(32) NOT NULL AFTER id");
            
            if ($result === false) {
                if ($logger) {
                    $logger->error('Failed to add item_hash column to feed_raw_items table. Error: ' . $wpdb->last_error);
                }
                return;
            }
            
            // Wir müssen prüfen, ob die Tabelle bereits Daten enthält
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items");
            
            // Wenn Daten vorhanden sind, generieren wir Hash-Werte für bestehende Einträge
            if ($count > 0) {
                // Wir verwenden eine Kombination aus feed_id und guid als Basis für den Hash
                $wpdb->query("UPDATE {$wpdb->prefix}feed_raw_items SET item_hash = MD5(CONCAT(feed_id, '-', guid)) WHERE item_hash = ''");
                
                if ($logger) {
                    $logger->info('Generated hash values for existing feed items');
                }
            }
            
            // Wir fügen einen UNIQUE KEY für item_hash hinzu
            $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items ADD UNIQUE KEY (item_hash)");
            
            if ($logger) {
                $logger->info('Added UNIQUE KEY constraint for item_hash column');
            }
        }
        
        // Weitere Spalten prüfen und hinzufügen
        if (!in_array('raw_content', $column_names)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items ADD COLUMN raw_content LONGTEXT");
            
            if ($logger) {
                $logger->info('Added raw_content column to feed_raw_items table');
            }
        }
        
        if (!in_array('pub_date', $column_names)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items ADD COLUMN pub_date DATETIME");
            
            if ($logger) {
                $logger->info('Added pub_date column to feed_raw_items table');
            }
        }
        
        if (!in_array('guid', $column_names)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items ADD COLUMN guid VARCHAR(255)");
            
            if ($logger) {
                $logger->info('Added guid column to feed_raw_items table');
            }
        }
        
        if (!in_array('created_at', $column_names)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}feed_raw_items ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
            
            if ($logger) {
                $logger->info('Added created_at column to feed_raw_items table');
            }
        }
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
        $debug_mode = \get_option('athena_ai_enable_debug_mode', false);
        $verbose_console = true; // Immer Konsolenausgaben aktivieren für diese kritische Funktion
        
        // Prüfen, ob die Tabelle existiert
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'") === $wpdb->prefix . 'feed_metadata';
        if (!$table_exists) {
            if ($debug_mode) {
                \error_log("Athena AI: Feed metadata table does not exist. Cannot ensure metadata.");
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed Fetcher: Feed metadata table does not exist. Cannot ensure metadata.");</script>';
            }
            return false;
        }
        
        try {
            // Hole die Feed-URL aus den Post-Meta-Daten
            $feed_url = \get_post_meta($feed_id, '_athena_feed_url', true);
            
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
                        \esc_url_raw($feed_url)
                    )
                );
                
                if ($exists_by_url) {
                    if ($debug_mode) {
                        \error_log("Athena AI: Feed metadata already exists with URL {$feed_url} for feed ID {$exists_by_url}, but trying to create for feed ID {$feed_id}");
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
                            \error_log("Athena AI: Updated feed metadata from feed ID {$exists_by_url} to {$feed_id}. Result: " . ($update_result !== false ? 'success' : 'failed'));
                        }
                    }
                    
                    $exists = true;
                }
            }
            
            // If no entry exists, create one
            if (!$exists) {
                // Get feed update interval from post meta
                $update_interval = \get_post_meta($feed_id, '_athena_feed_update_interval', true);
                if (!$update_interval) {
                    $update_interval = 3600; // Default: 1 hour
                }
                
                $now = \current_time('mysql');
                
                // Prüfen, ob die Spalten in der Tabelle existieren
                $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}feed_metadata");
                
                if (!$columns) {
                    if ($debug_mode) {
                        \error_log("Athena AI: Failed to get columns from feed_metadata table. Error: " . $wpdb->last_error);
                    }
                    return false;
                }
                
                $column_names = array_map(function($col) { return $col->Field; }, $columns);
                
                if ($debug_mode) {
                    \error_log("Athena AI: Feed metadata table columns: " . implode(', ', $column_names));
                    \error_log("Athena AI: Feed ID: {$feed_id}, Update interval: {$update_interval}");
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
                    $data['url'] = \esc_url_raw($feed_url);
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
                        \error_log("Athena AI: Failed to insert feed metadata for feed ID {$feed_id}. Error: " . $wpdb->last_error);
                    }
                    if ($verbose_console) {
                        echo '<script>console.error("Athena AI Feed Fetcher: Failed to insert feed metadata for feed ID ' . $feed_id . '. Database error: ' . \esc_js($wpdb->last_error) . '");</script>';
                        echo '<script>console.log("Data being inserted: ", ' . \json_encode($data) . ');</script>';
                        echo '<script>console.log("Format types: ", ' . \json_encode($format_types) . ');</script>';
                    }
                    return false;
                }
                
                if ($debug_mode) {
                    \error_log("Athena AI: Successfully created feed metadata for feed ID {$feed_id}");
                }
                
                return true;
            }
            
            return true;
        } catch (\Exception $e) {
            if ($debug_mode) {
                \error_log("Athena AI: Exception in ensure_feed_metadata_exists: " . $e->getMessage());
            }
            if ($verbose_console) {
                echo '<script>console.error("Athena AI Feed Fetcher: Exception in ensure_feed_metadata_exists for feed ID ' . $feed_id . ': ' . \esc_js($e->getMessage()) . '");</script>';
                echo '<script>console.log("Exception trace: ", "' . \esc_js($e->getTraceAsString()) . '");</script>';
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
        $debug_mode = \get_option('athena_ai_enable_debug_mode', false);
        
        // Get current time
        $now = \current_time('mysql');
        
        if ($debug_mode) {
            \error_log('Athena AI: Checking for feeds due for update at ' . $now);
            
            // Zähle alle aktiven Feeds
            $total_feeds = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} p 
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_athena_feed_active' 
                WHERE p.post_type = 'athena-feed' 
                AND p.post_status = 'publish' 
                AND (pm.meta_value = '1' OR pm.meta_value IS NULL)"
            );
            
            \error_log('Athena AI: Total active feeds: ' . $total_feeds);
            
            // Prüfe, ob die Metadaten-Tabelle existiert
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'") === $wpdb->prefix . 'feed_metadata';
            if ($debug_mode) {
                \error_log('Athena AI: Feed metadata table does not exist!');
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
