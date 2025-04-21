<?php
/**
 * Database Upgrade Functionality
 *
 * @package AthenaAI
 */

namespace AthenaAI\Admin;

/**
 * Handles database upgrades for the plugin
 */
class DatabaseUpgrade {
    /**
     * Initialize the database upgrade functionality
     */
    public static function init(): void {
        add_action('admin_init', [self::class, 'register_upgrade_page']);
        add_action('admin_post_athena_upgrade_database', [self::class, 'handle_upgrade_database']);
    }

    /**
     * Register the upgrade page
     */
    public static function register_upgrade_page(): void {
        add_submenu_page(
            null, // Kein Menüeintrag
            __('Datenbank-Upgrade', 'athena-ai'),
            __('Datenbank-Upgrade', 'athena-ai'),
            'manage_options',
            'athena-database-upgrade',
            [self::class, 'render_upgrade_page']
        );
    }

    /**
     * Render the upgrade page
     */
    public static function render_upgrade_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben nicht die erforderlichen Berechtigungen, um diese Seite anzuzeigen.', 'athena-ai'));
        }

        $success = isset($_GET['success']) ? (bool) $_GET['success'] : false;
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Athena AI Datenbank-Upgrade', 'athena-ai'); ?></h1>

            <?php if ($success): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Datenbank wurde erfolgreich aktualisiert!', 'athena-ai'); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html($error); ?></p>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2><?php esc_html_e('Datenbank-Tabellen aktualisieren', 'athena-ai'); ?></h2>
                <p><?php esc_html_e('Dieses Tool aktualisiert die Datenbankstruktur der Athena AI-Tabellen. Verwenden Sie es, wenn Sie Probleme mit fehlenden Spalten oder Tabellen haben.', 'athena-ai'); ?></p>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="athena_upgrade_database">
                    <?php wp_nonce_field('athena_upgrade_database', 'athena_upgrade_database_nonce'); ?>
                    <p>
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Datenbank jetzt aktualisieren', 'athena-ai'); ?>
                        </button>
                    </p>
                </form>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2><?php esc_html_e('Was wird aktualisiert?', 'athena-ai'); ?></h2>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><?php esc_html_e('Hinzufügen der Spalte "item_hash" zur Tabelle "feed_raw_items"', 'athena-ai'); ?></li>
                    <li><?php esc_html_e('Hinzufügen der Spalte "last_error" zur Tabelle "feed_metadata"', 'athena-ai'); ?></li>
                    <li><?php esc_html_e('Änderung des Primärschlüssels in der Tabelle "feed_raw_items"', 'athena-ai'); ?></li>
                    <li><?php esc_html_e('Hinzufügen von Indizes für verbesserte Leistung', 'athena-ai'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Handle the database upgrade action
     */
    public static function handle_upgrade_database(): void {
        // Überprüfe die Nonce
        if (!isset($_POST['athena_upgrade_database_nonce']) || !wp_verify_nonce($_POST['athena_upgrade_database_nonce'], 'athena_upgrade_database')) {
            wp_die(__('Sicherheitsüberprüfung fehlgeschlagen.', 'athena-ai'));
        }

        // Überprüfe die Berechtigungen
        if (!current_user_can('manage_options')) {
            wp_die(__('Sie haben nicht die erforderlichen Berechtigungen, um diese Aktion auszuführen.', 'athena-ai'));
        }

        // Führe das Datenbankupgrade durch
        $result = self::upgrade_database();

        // Leite zurück zur Upgrade-Seite
        if ($result === true) {
            wp_redirect(admin_url('admin.php?page=athena-database-upgrade&success=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=athena-database-upgrade&error=' . urlencode($result)));
        }
        exit;
    }

    /**
     * Upgrade the database tables
     *
     * @return true|string True on success, error message on failure
     */
    public static function upgrade_database() {
        global $wpdb;
        
        // Unterdrücke Warnungen und Fehler, die durch Ausgaben verursacht werden
        ob_start();
        
        // Erzwinge ein Update des Datenbankschemas
        \delete_option('athena_ai_db_version');
        
        // Führe das Datenbankschema-Setup aus
        require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
        
        try {
            // Feed Metadata Tabelle aktualisieren
            $metadata_table = $wpdb->prefix . 'feed_metadata';
            
            // Prüfe, ob die Tabelle existiert
            $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $metadata_table)) === $metadata_table;
            
            if ($table_exists) {
                // Prüfe, ob die Spalte 'last_error' existiert
                $column_exists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM {$metadata_table} LIKE %s", 'last_error'));
                
                if (empty($column_exists)) {
                    // Füge die Spalte hinzu
                    $wpdb->query("ALTER TABLE {$metadata_table} ADD COLUMN last_error TEXT AFTER last_error_message");
                }
            }
            
            // Feed Raw Items Tabelle aktualisieren
            $items_table = $wpdb->prefix . 'feed_raw_items';
            
            // Prüfe, ob die Tabelle existiert
            $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $items_table)) === $items_table;
            
            if ($table_exists) {
                // Prüfe, ob die Spalte 'item_hash' existiert
                $column_exists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM {$items_table} LIKE %s", 'item_hash'));
                
                if (empty($column_exists)) {
                    // Füge die Spalte hinzu
                    $wpdb->query("ALTER TABLE {$items_table} ADD COLUMN item_hash CHAR(32) NOT NULL AFTER id");
                    $wpdb->query("ALTER TABLE {$items_table} ADD INDEX (item_hash)");
                    
                    // Aktualisiere bestehende Einträge mit Hash-Werten
                    $wpdb->query("UPDATE {$items_table} SET item_hash = MD5(guid) WHERE guid IS NOT NULL AND guid != ''");
                }
                
                // Prüfe, ob die Spalte 'id' existiert
                $column_exists = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM {$items_table} LIKE %s", 'id'));
                
                if (empty($column_exists)) {
                    // Füge die ID-Spalte hinzu und ändere den Primärschlüssel
                    $wpdb->query("ALTER TABLE {$items_table} DROP PRIMARY KEY");
                    $wpdb->query("ALTER TABLE {$items_table} ADD COLUMN id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY FIRST");
                    $wpdb->query("ALTER TABLE {$items_table} ADD UNIQUE KEY (item_hash, feed_id)");
                }
            }
            
            // Aktualisiere die Schemaversion
            \update_option('athena_ai_db_version', \AthenaAI\Repositories\SchemaManager::VERSION);
            
            // Verwerfe gepufferte Ausgabe
            ob_end_clean();
            
            return true;
        } catch (\Exception $e) {
            // Verwerfe gepufferte Ausgabe
            ob_end_clean();
            return $e->getMessage();
        }
    }
    
    /**
     * Fügt einen Link zur Datenbank-Upgrade-Seite in den Admin-Bereich ein
     */
    public static function add_upgrade_link_to_plugins_page($links, $file) {
        if (plugin_basename(ATHENA_AI_PLUGIN_FILE) === $file) {
            $upgrade_link = '<a href="' . admin_url('admin.php?page=athena-database-upgrade') . '">' . __('Datenbank-Upgrade', 'athena-ai') . '</a>';
            array_unshift($links, $upgrade_link);
        }
        
        return $links;
    }
}
