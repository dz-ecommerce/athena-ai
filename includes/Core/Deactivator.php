<?php
namespace AthenaAI\Core;

class Deactivator {
    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear any scheduled hooks
        wp_clear_scheduled_hook('athena_ai_daily_cleanup');
        
        // Note: We don't delete tables or options here
        // This should be done in an uninstall.php file if needed
    }
} 