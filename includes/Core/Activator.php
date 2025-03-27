<?php
namespace AthenaAI\Core;

class Activator {
    /**
     * Activate the plugin
     */
    public static function activate() {
        // Create necessary database tables
        self::create_tables();
        
        // Create default options
        self::create_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create necessary database tables
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Example table creation
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}athena_ai_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            message text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create default options
     */
    private static function create_default_options() {
        $default_options = [
            'athena_ai_version' => ATHENA_AI_VERSION,
            'athena_ai_api_key' => '',
            'athena_ai_enabled' => true,
        ];

        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
} 