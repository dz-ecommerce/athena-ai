<?php
declare(strict_types=1);

namespace AthenaAI\Repositories;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * Handles creation and verification of Athena‑AI database schema.
 *
 * Moved from AthenaAI\Database\DatabaseSetup (legacy alias created for BC).
 */
class SchemaManager {
    private const VERSION = '1.0.1'; // Erhöhte Version für Datenbankschema-Update
    private const VERSION_OPTION = 'athena_ai_db_version';

    public static function init(): void {
        \add_action('plugins_loaded', [self::class, 'check_version']);
        \add_action('admin_init', [self::class, 'check_tables']);
    }

    public static function check_version(): void {
        if (\get_option(self::VERSION_OPTION) !== self::VERSION) {
            self::setup_tables();
        }
    }

    /**
     * Ensure tables exist whenever a relevant admin screen is loaded.
     */
    public static function check_tables(): void {
        global $wpdb;

        // Only check on specific admin pages to avoid unnecessary DB queries
        if (\function_exists('get_current_screen')) {
            $screen = \get_current_screen();
            if (
                !$screen ||
                !\in_array($screen->id, ['athena-feed', 'athena_page_athena-feed-items'])
            ) {
                return;
            }
        }
        // Only verify the main table – others are handled in setup_tables().
        $table_exists = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->prefix . 'feed_raw_items')
        );

        if (!$table_exists) {
            self::setup_tables();
        }
    }

    /**
     * Confirm that all required tables are present.
     */
    public static function tables_exist(): bool {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $items = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $prefix . 'feed_raw_items'));
        $errors = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $prefix . 'feed_errors'));
        $metadata = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $prefix . 'feed_metadata')
        );

        return (bool) ($items && $errors && $metadata);
    }

    /**
     * Create / update feed‑related tables using dbDelta.
     */
    public static function setup_tables(): void {
        global $wpdb;
        require_once \ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Feed Metadata
        \dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}feed_metadata (
            feed_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
            last_fetched DATETIME DEFAULT NULL,
            fetch_interval INT DEFAULT 3600,
            fetch_count INT DEFAULT 0,
            last_error_date DATETIME DEFAULT NULL,
            last_error_message TEXT,
            last_error TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;");

        // Raw Items
        \dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}feed_raw_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_hash CHAR(32) NOT NULL,
            feed_id BIGINT UNSIGNED NOT NULL,
            raw_content LONGTEXT,
            pub_date DATETIME,
            guid VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (feed_id),
            INDEX (pub_date),
            INDEX (item_hash),
            UNIQUE KEY (item_hash, feed_id)
        ) $charset_collate;");
        
        // Feed Item Categories
        \dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}feed_item_categories (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_id BIGINT UNSIGNED NOT NULL,
            category VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (item_id),
            INDEX (category),
            UNIQUE KEY (item_id, category)
        ) $charset_collate;");

        // Errors
        \dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}feed_errors (
            error_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            feed_id BIGINT UNSIGNED NOT NULL,
            error_code VARCHAR(32),
            error_message TEXT,
            created DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (feed_id),
            INDEX (created)
        ) $charset_collate;");

        // Aktualisiere die Schemaversion in der Datenbank
        \update_option(self::VERSION_OPTION, self::VERSION);
    }
}

// ----------------------------------------------------------------------------
// Backwards‑compatibility: alias old class name to new implementation.
// ----------------------------------------------------------------------------
\class_alias(__NAMESPACE__ . '\\SchemaManager', 'AthenaAI\\Database\\DatabaseSetup');
