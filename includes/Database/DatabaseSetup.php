<?php
declare(strict_types=1);

namespace AthenaAI\Database;

if (!defined('ABSPATH')) {
    exit();
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

        // Check if the feed_raw_items table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_raw_items'");

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

        $items_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_raw_items'");
        $errors_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_errors'");
        $metadata_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}feed_metadata'");

        return $items_exists && $errors_exists && $metadata_exists;
    }

    public static function setup_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Feed Metadata Table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}feed_metadata (
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

        // Feed Raw Items Table - now references post IDs directly
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}feed_raw_items (
            item_hash CHAR(32) PRIMARY KEY,
            feed_id BIGINT UNSIGNED NOT NULL, -- This now references the post ID of the athena-feed post type
            raw_content LONGTEXT,
            pub_date DATETIME,
            guid VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (feed_id),
            INDEX (pub_date)
        ) $charset_collate;";

        dbDelta($sql);

        // Feed Errors Table - now references post IDs directly
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}feed_errors (
            error_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            feed_id BIGINT UNSIGNED NOT NULL, -- This now references the post ID of the athena-feed post type
            error_code VARCHAR(32),
            error_message TEXT,
            created DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (feed_id),
            INDEX (created)
        ) $charset_collate;";

        dbDelta($sql);

        update_option(self::VERSION_OPTION, self::VERSION);
    }
}
