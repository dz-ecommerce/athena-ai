<?php
declare(strict_types=1);

namespace AthenaAI\Database;

if (!defined('ABSPATH')) {
    exit;
}

class DatabaseSetup {
    private const VERSION = '1.0.0';
    private const VERSION_OPTION = 'athena_ai_db_version';

    public static function init(): void {
        add_action('plugins_loaded', [self::class, 'check_version']);
        
        // Also check on admin page load for the feed items page
        add_action('admin_init', [self::class, 'check_tables']);
    }

    public static function check_version(): void {
        if (get_option(self::VERSION_OPTION) !== self::VERSION) {
            self::setup_tables();
        }
    }
    
    /**
     * Check if tables exist and create them if they don't
     */
    public static function check_tables(): void {
        global $wpdb;
        
        // Only run this check on the feed items page
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || !isset($_GET['page']) || $_GET['page'] !== 'athena-feed-items') {
            return;
        }
        
        // Check if the feed_metadata table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'");
        
        if (!$table_exists) {
            self::setup_tables();
        }
    }
    
    /**
     * Check if the required tables exist
     * 
     * @return bool True if all required tables exist
     */
    public static function tables_exist(): bool {
        global $wpdb;
        
        $metadata_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'");
        $items_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_raw_items'");
        $errors_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_errors'");
        
        return $metadata_exists && $items_exists && $errors_exists;
    }

    public static function setup_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Feed Metadata Table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}feed_metadata (
            feed_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            url VARCHAR(512) NOT NULL,
            last_checked DATETIME,
            update_interval INT DEFAULT 3600,
            active TINYINT(1) DEFAULT 1,
            UNIQUE KEY url (url)
        ) $charset_collate;";
        
        dbDelta($sql);

        // Feed Raw Items Table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}feed_raw_items (
            item_hash CHAR(32) PRIMARY KEY,
            feed_id BIGINT UNSIGNED,
            raw_content LONGTEXT,
            pub_date DATETIME,
            guid VARCHAR(255),
            FOREIGN KEY (feed_id) REFERENCES {$wpdb->prefix}feed_metadata(feed_id)
        ) $charset_collate;";
        
        dbDelta($sql);

        // Feed Errors Table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}feed_errors (
            error_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            feed_id BIGINT UNSIGNED,
            error_code VARCHAR(32),
            error_message TEXT,
            created DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (feed_id) REFERENCES {$wpdb->prefix}feed_metadata(feed_id)
        ) $charset_collate;";
        
        dbDelta($sql);

        update_option(self::VERSION_OPTION, self::VERSION);
    }
}
