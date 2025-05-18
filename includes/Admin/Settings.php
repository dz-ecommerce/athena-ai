<?php
namespace AthenaAI\Admin;

class Settings extends BaseAdmin {
    /**
     * Default settings
     */
    private $default_settings = [
        // GitHub Settings
        'github_token' => '',
        'github_owner' => 'dz-ecommerce',
        'github_repo' => 'athena-ai',

        // Text AI Settings
        'openai_api_key' => '',
        'openai_org_id' => '',
        'openai_default_model' => 'gpt-4',
        'openai_temperature' => '0.7',

        // Image AI Settings
        'dalle_size' => '1024x1024',
        'dalle_quality' => 'standard',
        'dalle_style' => 'vivid',

        'midjourney_api_key' => '',
        'midjourney_version' => '6',
        'midjourney_style' => 'raw',

        'stablediffusion_api_key' => '',
        'stablediffusion_model' => 'stable-diffusion-xl-1024-v1-0',
        'stablediffusion_steps' => '30',

        // Maintenance Settings
        'enable_debug_mode' => false,
        'feed_cron_interval' => 'hourly', // Default: hourly
    ];

    /**
     * Konstruktor - Registriert AJAX-Endpunkte
     */
    public function __construct() {
        // AJAX-Endpoint zum Leeren des Optionen-Caches
        add_action('wp_ajax_athena_flush_options_cache', [$this, 'ajax_flush_options_cache']);
        // Admin-Post Hook für Settings-Speichern
        add_action('admin_post_athena_save_settings', [$this, 'handle_save_settings']);
    }

    /**
     * Render the settings page
     */
    public function render_page() {
        // KEINE POST-Verarbeitung mehr hier!
        // Erfolgsmeldung anzeigen, falls vorhanden
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            add_settings_error(
                'athena_ai_messages',
                'athena_ai_message',
                __('Settings saved successfully!', 'athena-ai'),
                'updated'
            );
        }
        
        // Einstellungen auf Standardwerte zurücksetzen, wenn der Reset-Button geklickt wurde
        if (isset($_POST['reset_defaults']) && isset($_POST['_wpnonce_athena_ai_settings']) && wp_verify_nonce($_POST['_wpnonce_athena_ai_settings'], 'athena_ai_settings')) {
            $this->reset_to_defaults();
            
            // Auch hier Redirect zum Aktualisieren der Ansicht
            wp_redirect(add_query_arg('settings-reset', 'true'));
            exit;
        }

        // Führe Maintenance-Aktionen aus, wenn angefordert
        if (isset($_POST['fix_cron']) && isset($_POST['_wpnonce_athena_ai_maintenance']) && wp_verify_nonce($_POST['_wpnonce_athena_ai_maintenance'], 'athena_ai_maintenance')) {
            $this->fix_cron_schedule();
        }

        // Erstelle Datenbanktabellen, wenn angefordert
        if (isset($_POST['create_tables']) && isset($_POST['_wpnonce_athena_ai_maintenance']) && wp_verify_nonce($_POST['_wpnonce_athena_ai_maintenance'], 'athena_ai_maintenance')) {
            $this->create_database_tables();
        }

        // Generiere einen neuen Verschlüsselungs-Schlüssel, wenn der Button geklickt wurde
        if (isset($_POST['generate_encryption_key']) && isset($_POST['_wpnonce_athena_ai_settings']) && wp_verify_nonce($_POST['_wpnonce_athena_ai_settings'], 'athena_ai_settings')) {
            $new_key = bin2hex(random_bytes(32));
            update_option('athena_ai_encryption_key', $new_key);
            add_settings_error(
                'athena_ai_messages',
                'athena_ai_key_generated',
                __('New encryption key generated. Please copy it to your wp-config.php file.', 'athena-ai'),
                'updated'
            );
        }

        // Lese die aktuellen Einstellungen aus der Datenbank
        $settings = $this->get_settings();

        // Sammle Maintenance-Daten
        $maintenance_data = $this->get_maintenance_data();

        $this->render_template('settings', [
            'title' => $this->__('Athena AI Settings', 'athena-ai'),
            'settings' => $settings,
            'nonce_field' => $this->get_nonce_field('athena_ai_settings', '_wpnonce_athena_ai_settings'),
            'maintenance_nonce_field' => $this->get_nonce_field('athena_ai_maintenance', '_wpnonce_athena_ai_maintenance'),
            'maintenance' => $maintenance_data,
            'models' => [
                'openai' => [
                    'gpt-4o' => 'GPT-4o',
                    'gpt-4-turbo' => 'GPT-4 Turbo',
                    'gpt-4' => 'GPT-4',
                    'gpt-4-1106-preview' => 'GPT-4 (Version 1106)',
                    'gpt-4-vision-preview' => 'GPT-4 Vision',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    'gpt-3.5-turbo-16k' => 'GPT-3.5 Turbo (16K)',
                ],
                'dalle' => [
                    'sizes' => ['256x256', '512x512', '1024x1024'],
                    'qualities' => ['standard', 'hd'],
                    'styles' => ['vivid', 'natural'],
                ],
                'stablediffusion' => [
                    'models' => [
                        'stable-diffusion-xl-1024-v1-0' => 'Stable Diffusion XL',
                        'stable-diffusion-v1-5' => 'Stable Diffusion 1.5',
                    ],
                ],
            ],
            'show_generate_key_button' => !defined('ATHENA_AI_KEY_ENCRYPTION_SECRET') || !ATHENA_AI_KEY_ENCRYPTION_SECRET,
            'encryption_key' => get_option('athena_ai_encryption_key', ''),
        ]);
    }

    /**
     * Save settings using standard WordPress functions
     */
    public function save_settings() {
        // Enable error logging
        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            @ini_set('log_errors', 1);
            @ini_set('error_log', WP_CONTENT_DIR . '/debug.log');
        }
        
        // Log start of save process
        error_log('[ATHENA AI] ==================== SETTINGS SAVE PROCESS STARTED ====================');
        
        try {
            // Validate and sanitize all settings before saving
            $settings = $this->validate_and_sanitize_settings($_POST);
            
            // OpenAI API Key verschlüsselt speichern
            if (isset($_POST['athena_ai_openai_api_key'])) {
                $plain_key = sanitize_text_field($_POST['athena_ai_openai_api_key']);
                $encrypted_key = $this->encrypt_api_key($plain_key);
                update_option('athena_ai_openai_api_key', $encrypted_key);
                unset($settings['openai_api_key']); // Nicht nochmal speichern
            }
            
            // Save each setting individually
            foreach ($settings as $key => $value) {
                $option_name = 'athena_ai_' . $key;
                $result = update_option($option_name, $value);
                
                if ($result === false) {
                    error_log("[ATHENA AI] Failed to save setting: {$option_name}");
                }
            }
            
            // Update cron schedule if needed
            if (isset($settings['feed_cron_interval'])) {
                $old_interval = get_option('athena_ai_feed_cron_interval', 'hourly');
                $new_interval = $settings['feed_cron_interval'];
                
                if ($old_interval !== $new_interval) {
                    $this->update_cron_schedule($new_interval);
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('[ATHENA AI] EXCEPTION: ' . $e->getMessage());
            error_log('[ATHENA AI] ' . $e->getTraceAsString());
            return false;
        } finally {
            error_log('[ATHENA AI] ==================== SETTINGS SAVE PROCESS COMPLETED ====================');
        }
    }
    
    /**
     * Validate and sanitize all settings
     */
    private function validate_and_sanitize_settings(array $input): array {
        $settings = [];
        
        // OpenAI Settings
        if (isset($input['athena_ai_openai_api_key'])) {
            $settings['openai_api_key'] = sanitize_text_field($input['athena_ai_openai_api_key']);
        }
        
        if (isset($input['athena_ai_openai_org_id'])) {
            $settings['openai_org_id'] = sanitize_text_field($input['athena_ai_openai_org_id']);
        }
        
        if (isset($input['athena_ai_openai_default_model'])) {
            $settings['openai_default_model'] = sanitize_text_field($input['athena_ai_openai_default_model']);
        }
        
        if (isset($input['athena_ai_openai_temperature'])) {
            $settings['openai_temperature'] = max(0, min(2, (float)$input['athena_ai_openai_temperature']));
        }
        
        // GitHub Settings
        if (isset($input['athena_ai_github_token'])) {
            $settings['github_token'] = sanitize_text_field($input['athena_ai_github_token']);
        }
        
        if (isset($input['athena_ai_github_owner'])) {
            $settings['github_owner'] = sanitize_text_field($input['athena_ai_github_owner']);
        }
        
        if (isset($input['athena_ai_github_repo'])) {
            $settings['github_repo'] = sanitize_text_field($input['athena_ai_github_repo']);
        }
        
        // Image AI Settings
        if (isset($input['athena_ai_dalle_size'])) {
            $settings['dalle_size'] = sanitize_text_field($input['athena_ai_dalle_size']);
        }
        
        if (isset($input['athena_ai_dalle_quality'])) {
            $settings['dalle_quality'] = sanitize_text_field($input['athena_ai_dalle_quality']);
        }
        
        if (isset($input['athena_ai_dalle_style'])) {
            $settings['dalle_style'] = sanitize_text_field($input['athena_ai_dalle_style']);
        }
        
        if (isset($input['athena_ai_midjourney_api_key'])) {
            $settings['midjourney_api_key'] = sanitize_text_field($input['athena_ai_midjourney_api_key']);
        }
        
        if (isset($input['athena_ai_midjourney_version'])) {
            $settings['midjourney_version'] = sanitize_text_field($input['athena_ai_midjourney_version']);
        }
        
        if (isset($input['athena_ai_midjourney_style'])) {
            $settings['midjourney_style'] = sanitize_text_field($input['athena_ai_midjourney_style']);
        }
        
        if (isset($input['athena_ai_stablediffusion_api_key'])) {
            $settings['stablediffusion_api_key'] = sanitize_text_field($input['athena_ai_stablediffusion_api_key']);
        }
        
        if (isset($input['athena_ai_stablediffusion_model'])) {
            $settings['stablediffusion_model'] = sanitize_text_field($input['athena_ai_stablediffusion_model']);
        }
        
        if (isset($input['athena_ai_stablediffusion_steps'])) {
            $settings['stablediffusion_steps'] = intval($input['athena_ai_stablediffusion_steps']);
        }
        
        // Maintenance Settings
        $settings['enable_debug_mode'] = isset($input['athena_ai_enable_debug_mode']) ? '1' : '0';
        
        if (isset($input['athena_ai_feed_cron_interval'])) {
            $settings['feed_cron_interval'] = sanitize_text_field($input['athena_ai_feed_cron_interval']);
        }
        
        return $settings;
    }
    
    /**
     * Update the cron schedule
     */
    private function update_cron_schedule(string $new_interval): void {
        // Remove existing cron job
        $timestamp = wp_next_scheduled('athena_fetch_feeds');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'athena_fetch_feeds');
        }
        
        // Schedule new cron job
        wp_schedule_event(time(), $new_interval, 'athena_fetch_feeds');
        
        error_log("[ATHENA AI] Cron schedule updated to: {$new_interval}");
    }

    /**
     * Get current settings
     *
     * @return array
     */
    // Die log_current_settings_state Methode wurde entfernt, da ihre Funktionalität jetzt direkt in der get_settings Methode integriert ist.
    
    /**
     * Get current settings with proper error handling and fallbacks
     *
     * @return array
     */
    private function get_settings() {
        $settings = [];
        
        // GitHub Settings
        $settings['github_token'] = get_option('athena_ai_github_token', $this->default_settings['github_token']);
        $settings['github_owner'] = get_option('athena_ai_github_owner', $this->default_settings['github_owner']);
        $settings['github_repo'] = get_option('athena_ai_github_repo', $this->default_settings['github_repo']);
        
        // Text AI Settings
        $encrypted_key = get_option('athena_ai_openai_api_key', '');
        $settings['openai_api_key'] = $encrypted_key ? $this->decrypt_api_key($encrypted_key) : '';
        $settings['openai_org_id'] = get_option('athena_ai_openai_org_id', $this->default_settings['openai_org_id']);
        $settings['openai_default_model'] = get_option('athena_ai_openai_default_model', $this->default_settings['openai_default_model']);
        $settings['openai_temperature'] = get_option('athena_ai_openai_temperature', $this->default_settings['openai_temperature']);
        
        // Image AI Settings
        $settings['dalle_size'] = get_option('athena_ai_dalle_size', $this->default_settings['dalle_size']);
        $settings['dalle_quality'] = get_option('athena_ai_dalle_quality', $this->default_settings['dalle_quality']);
        $settings['dalle_style'] = get_option('athena_ai_dalle_style', $this->default_settings['dalle_style']);
        $settings['midjourney_api_key'] = get_option('athena_ai_midjourney_api_key', $this->default_settings['midjourney_api_key']);
        $settings['midjourney_version'] = get_option('athena_ai_midjourney_version', $this->default_settings['midjourney_version']);
        $settings['midjourney_style'] = get_option('athena_ai_midjourney_style', $this->default_settings['midjourney_style']);
        $settings['stablediffusion_api_key'] = get_option('athena_ai_stablediffusion_api_key', $this->default_settings['stablediffusion_api_key']);
        $settings['stablediffusion_model'] = get_option('athena_ai_stablediffusion_model', $this->default_settings['stablediffusion_model']);
        $settings['stablediffusion_steps'] = get_option('athena_ai_stablediffusion_steps', $this->default_settings['stablediffusion_steps']);
        
        // Maintenance Settings
        $settings['enable_debug_mode'] = get_option('athena_ai_enable_debug_mode', $this->default_settings['enable_debug_mode']);
        $settings['feed_cron_interval'] = get_option('athena_ai_feed_cron_interval', $this->default_settings['feed_cron_interval']);
        
        // Log der Einstellungen für Debugging-Zwecke
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = "=== ATHENA AI SETTINGS STATE: After loading settings ===\n";
            
            $critical_options = [
                'athena_ai_openai_api_key',
                'athena_ai_openai_org_id',
                'athena_ai_openai_default_model',
                'athena_ai_openai_temperature'
            ];
            
            foreach ($critical_options as $option) {
                $value = get_option($option, 'NOT_SET');
                $log_message .= sprintf(
                    "%s: %s\n",
                    $option,
                    $option === 'athena_ai_openai_api_key' && !empty($value) && $value !== 'NOT_SET' 
                        ? substr($value, 0, 3) . '...' . substr($value, -3) . " (length: " . strlen($value) . ")"
                        : (is_string($value) ? $value : print_r($value, true))
                );
            }
            
            error_log($log_message . "======================================\n");
        }
        
        return $settings;

        // Image AI Settings
        $settings['dalle_size'] = get_option(
            'athena_ai_dalle_size',
            $this->default_settings['dalle_size']
        );
        $settings['dalle_quality'] = get_option(
            'athena_ai_dalle_quality',
            $this->default_settings['dalle_quality']
        );
        $settings['dalle_style'] = get_option(
            'athena_ai_dalle_style',
            $this->default_settings['dalle_style']
        );

        $settings['midjourney_api_key'] = get_option('athena_ai_midjourney_api_key', '');
        $settings['midjourney_version'] = get_option(
            'athena_ai_midjourney_version',
            $this->default_settings['midjourney_version']
        );
        $settings['midjourney_style'] = get_option(
            'athena_ai_midjourney_style',
            $this->default_settings['midjourney_style']
        );

        $settings['stablediffusion_api_key'] = get_option('athena_ai_stablediffusion_api_key', '');
        $settings['stablediffusion_model'] = get_option(
            'athena_ai_stablediffusion_model',
            $this->default_settings['stablediffusion_model']
        );
        $settings['stablediffusion_steps'] = get_option(
            'athena_ai_stablediffusion_steps',
            $this->default_settings['stablediffusion_steps']
        );

        // Maintenance Settings
        $settings['enable_debug_mode'] = get_option(
            'athena_ai_enable_debug_mode',
            $this->default_settings['enable_debug_mode']
        );
        // Feed-Cron-Intervall korrekt abrufen
        $settings['feed_cron_interval'] = get_option(
            'athena_ai_feed_cron_interval',
            $this->default_settings['feed_cron_interval']
        );

        return $settings;
    }

    /**
     * Sammelt Daten für den Maintenance-Tab
     *
     * @return array
     */
    private function get_maintenance_data() {
        global $wpdb;
        $data = [];

        // Feed-Datenbank prüfen
        $feed_items_table = $wpdb->prefix . 'feed_raw_items';
        $feed_metadata_table = $wpdb->prefix . 'feed_metadata';

        $data['feed_items_table_exists'] =
            $wpdb->get_var("SHOW TABLES LIKE '{$feed_items_table}'") === $feed_items_table;
        $data['feed_metadata_table_exists'] =
            $wpdb->get_var("SHOW TABLES LIKE '{$feed_metadata_table}'") === $feed_metadata_table;

        // Feed-Items zählen
        if ($data['feed_items_table_exists']) {
            $data['feed_items_count'] = $wpdb->get_var("SELECT COUNT(*) FROM {$feed_items_table}");
        } else {
            $data['feed_items_count'] = 0;
        }

        // Feed-Metadaten zählen
        if ($data['feed_metadata_table_exists']) {
            $data['feed_metadata_count'] = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$feed_metadata_table}"
            );
        } else {
            $data['feed_metadata_count'] = 0;
        }

        // Cron-Status prüfen
        $data['cron_event_scheduled'] = wp_next_scheduled('athena_fetch_feeds') !== false;
        if ($data['cron_event_scheduled']) {
            $data['next_cron_run'] = wp_next_scheduled('athena_fetch_feeds');
            $data['next_cron_run_human'] =
                human_time_diff(time(), $data['next_cron_run']) . ' ' . __('from now', 'athena-ai');
        }

        // Letzte Fetch-Zeit
        $data['last_fetch_time'] = get_option('athena_last_feed_fetch', 0);
        if ($data['last_fetch_time'] > 0) {
            $data['last_fetch_human'] =
                human_time_diff($data['last_fetch_time'], time()) . ' ' . __('ago', 'athena-ai');
        } else {
            $data['last_fetch_human'] = __('Never', 'athena-ai');
        }

        // Anzahl der Feeds
        $data['feed_count'] = wp_count_posts('athena-feed')->publish;

        // WordPress-Cron-Status
        $data['wp_cron_disabled'] = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;

        return $data;
    }

    /**
     * Repariert den Cron-Zeitplan
     */
    private function fix_cron_schedule() {
        // Bestehenden Cron-Job entfernen
        $timestamp = wp_next_scheduled('athena_fetch_feeds');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'athena_fetch_feeds');
        }

        // Neuen Cron-Job planen
        wp_schedule_event(time(), 'hourly', 'athena_fetch_feeds');

        // Erfolgsmeldung anzeigen
        add_settings_error(
            'athena_ai_messages',
            'athena_ai_cron_fixed',
            $this->__('Feed fetch cron job has been rescheduled successfully.', 'athena-ai'),
            'updated'
        );
    }

    /**
     * Garantiert das Speichern der wichtigen Einstellungen durch direkte Datenbankzugriffe
     * Wird als Fallback verwendet, wenn die reguläre Speichermethode fehlschlägt
     */
    private function force_save_settings() {
        global $wpdb;
        
        error_log("======= ATHENA FORCE SAVE SETTINGS =======\n");
        
        // Kritische Einstellungen direkt mit dem $wpdb-Objekt speichern
        $api_key = sanitize_text_field($_POST['athena_ai_openai_api_key'] ?? '');
        $org_id = sanitize_text_field($_POST['athena_ai_openai_org_id'] ?? '');
        $default_model = sanitize_text_field($_POST['athena_ai_openai_default_model'] ?? 'gpt-4');
        $temperature = isset($_POST['athena_ai_openai_temperature']) ? (float)$_POST['athena_ai_openai_temperature'] : 0.7;
        
        // Direkte SQL-Abfragen zur Aktualisierung oder Erstellung der Optionen
        $this->force_save_option('athena_ai_openai_api_key', $api_key);
        $this->force_save_option('athena_ai_openai_org_id', $org_id);
        $this->force_save_option('athena_ai_openai_default_model', $default_model);
        $this->force_save_option('athena_ai_openai_temperature', $temperature);
        
        error_log("Force Save Verifizierung: API Key: " . (empty(get_option('athena_ai_openai_api_key')) ? 'FEHLER' : 'ERFOLG'));
        error_log("Force Save Verifizierung: Org ID: " . (empty(get_option('athena_ai_openai_org_id')) ? (empty($org_id) ? 'OK (leer)' : 'FEHLER') : 'ERFOLG'));
        error_log("Force Save Verifizierung: Default Model: " . (empty(get_option('athena_ai_openai_default_model')) ? 'FEHLER' : 'ERFOLG'));
        error_log("Force Save Verifizierung: Temperature: " . (get_option('athena_ai_openai_temperature', '') === '' ? 'FEHLER' : 'ERFOLG'));
    }
    
    // Die force_save_option Methode wurde entfernt, da wir nun die Standard-WordPress-Funktionen verwenden.
    
    /**
     * Validiert die API-Schlüssel und gibt etwaige Fehler zurück
     * 
     * @return array Liste der Validierungsfehler
     */
    private function validate_api_keys() {
        $errors = [];
        
        // OpenAI API-Key (minimal 30 Zeichen, beginnt mit 'sk-')
        $openai_key = sanitize_text_field($_POST['athena_ai_openai_api_key'] ?? '');
        if (!empty($openai_key) && (strlen($openai_key) < 30 || strpos($openai_key, 'sk-') !== 0)) {
            $errors[] = $this->__(
                'The OpenAI API key appears to be invalid. It should begin with "sk-" and be at least 30 characters long.',
                'athena-ai'
            );
        }
        
        // Midjourney API-Key (mindestens 20 Zeichen)
        $midjourney_key = sanitize_text_field($_POST['athena_ai_midjourney_api_key'] ?? '');
        if (!empty($midjourney_key) && strlen($midjourney_key) < 20) {
            $errors[] = $this->__(
                'The Midjourney API key appears to be too short. Please check if it is valid.',
                'athena-ai'
            );
        }
        
        // Stable Diffusion API-Key (mindestens 20 Zeichen)
        $stable_key = sanitize_text_field($_POST['athena_ai_stablediffusion_api_key'] ?? '');
        if (!empty($stable_key) && strlen($stable_key) < 20) {
            $errors[] = $this->__(
                'The Stable Diffusion API key appears to be too short. Please check if it is valid.',
                'athena-ai'
            );
        }
        
        return $errors;
    }
    
    /**
     * Setzt alle Einstellungen auf die Standardwerte zurück
     */
    public function reset_to_defaults() {
        // Alle Optionen auf Standardwerte zurücksetzen
        foreach ($this->default_settings as $key => $value) {
            $option_name = 'athena_ai_' . $key;
            update_option($option_name, $value);
        }
        
        // Erfolgsmeldung anzeigen
        add_settings_error(
            'athena_ai_messages',
            'athena_ai_reset',
            $this->__('All settings have been reset to default values.', 'athena-ai'),
            'updated'
        );
    }
    
    private function create_database_tables() {
        // Verwende die SchemaManager-Klasse, um die Tabellen zu erstellen
        $result = \AthenaAI\Repositories\SchemaManager::setup_tables();

        if ($result) {
            // Erfolgsmeldung anzeigen
            add_settings_error(
                'athena_ai_messages',
                'athena_ai_tables_created',
                $this->__('Feed database tables have been created successfully.', 'athena-ai'),
                'updated'
            );
        } else {
            // Fehlermeldung anzeigen
            add_settings_error(
                'athena_ai_messages',
                'athena_ai_tables_error',
                $this->__(
                    'There was an error creating the feed database tables. Please check the error log for details.',
                    'athena-ai'
                ),
                'error'
            );
        }
    }

    /**
     * AJAX-Handler zum Aktualisieren der DB-Ansicht ohne Cache
     */
    public function ajax_flush_options_cache() {
        // Überprüfe Nonce für Sicherheit
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'athena_flush_options_cache')) {
            wp_send_json_error(['message' => 'Sicherheitsüberprüfung fehlgeschlagen']);
            return;
        }
        
        // Überprüfe Benutzerberechtigungen
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unzureichende Berechtigungen']);
            return;
        }
        
        // Direkte Datenbankabfrage ohne Verwendung des Caches
        global $wpdb;
        $api_key = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
            'athena_ai_openai_api_key'
        ));
        
        wp_send_json_success([
            'message' => 'Settings view refreshed with direct database values',
            'api_key_length' => empty($api_key) ? 0 : strlen($api_key)
        ]);
    }

    // Die verify_db_settings Methode wurde entfernt, da wir nun die Standard-WordPress-Funktionen verwenden.

    // Prüft, ob der OpenAI API Key gültig ist
    private function is_openai_api_key_valid($api_key) {
        $ch = curl_init('https://api.openai.com/v1/models');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $http_code === 200;
    }

    // Neue Methode für admin_post Verarbeitung
    public function handle_save_settings() {
        if (!isset($_POST['_wpnonce_athena_ai_settings']) || !wp_verify_nonce($_POST['_wpnonce_athena_ai_settings'], 'athena_ai_settings')) {
            wp_die(__('Security check failed.', 'athena-ai'));
        }
        $this->save_settings();

        $api_key = isset($_POST['athena_ai_openai_api_key']) ? trim($_POST['athena_ai_openai_api_key']) : '';
        if ($api_key) {
            if ($this->is_openai_api_key_valid($api_key)) {
                update_option('athena_ai_openai_api_key_status', 'valid');
                add_settings_error(
                    'athena_ai_messages',
                    'athena_ai_api_key_valid',
                    __('OpenAI API Key is valid.', 'athena-ai'),
                    'updated'
                );
            } else {
                update_option('athena_ai_openai_api_key_status', 'invalid');
                add_settings_error(
                    'athena_ai_messages',
                    'athena_ai_api_key_invalid',
                    __('OpenAI API Key is invalid or has insufficient permissions.', 'athena-ai'),
                    'error'
                );
            }
        }

        $redirect_url = admin_url('edit.php?post_type=athena-feed&page=athena-ai-settings&settings-updated=true');
        wp_redirect($redirect_url);
        exit;
    }

    // Holt den Verschlüsselungs-Schlüssel: erst Konstante, dann Option
    private function get_encryption_key() {
        if (defined('ATHENA_AI_KEY_ENCRYPTION_SECRET') && ATHENA_AI_KEY_ENCRYPTION_SECRET) {
            // Optional: Lösche alten DB-Key, wenn Konstante gesetzt ist
            delete_option('athena_ai_encryption_key');
            return ATHENA_AI_KEY_ENCRYPTION_SECRET;
        }
        // Fallback: Option aus DB (für Migration/Kompatibilität)
        $option_name = 'athena_ai_encryption_key';
        $key = get_option($option_name, '');
        if (empty($key)) {
            $key = bin2hex(random_bytes(32));
            update_option($option_name, $key);
        }
        return $key;
    }

    // Verschlüsselt den API Key
    private function encrypt_api_key($plain_text) {
        $key = $this->get_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($plain_text, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    // Entschlüsselt den API Key
    private function decrypt_api_key($encrypted_text) {
        $key = $this->get_encryption_key();
        $data = base64_decode($encrypted_text);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
}
