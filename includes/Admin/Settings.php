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
        parent::__construct();
        
        // AJAX-Endpoint zum Leeren des Optionen-Caches
        add_action('wp_ajax_athena_flush_options_cache', [$this, 'ajax_flush_options_cache']);
    }

    /**
     * Render the settings page
     */
    public function render_page() {
        // Debug-Ausgabe vor der Verarbeitung
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log_current_settings_state('Pre-render settings state');
        }
        
        // Verarbeite Formular-Submission
        if (isset($_POST['submit']) && $this->verify_nonce('athena_ai_settings')) {
            try {
                // Speichere die Einstellungen mit der verbesserten Methode
                $save_result = $this->save_settings();
                
                // Erzwinge eine Neuladen der Seite nach dem Speichern
                // mit einem eindeutigen Parameter, um Cache-Probleme zu vermeiden
                $redirect_url = add_query_arg(
                    [
                        'settings-updated' => 'true',
                        'ts' => time() // Timestamp als Cache-Buster
                    ],
                    remove_query_arg('settings-updated')
                );
                
                if (isset($_POST['active_tab'])) {
                    $redirect_url = add_query_arg('tab', sanitize_text_field($_POST['active_tab']), $redirect_url);
                }
                
                wp_redirect($redirect_url);
                exit;
            } catch (Exception $e) {
                // Fehlerbehandlung
                error_log('Athena Settings Error: ' . $e->getMessage());
                add_settings_error(
                    'athena_ai_messages',
                    'athena_ai_error',
                    sprintf($this->__('Error saving settings: %s', 'athena-ai'), $e->getMessage()),
                    'error'
                );
            }
        }
        
        // Wenn die Einstellungen aktualisiert wurden, zeige eine Erfolgsmeldung an
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            add_settings_error(
                'athena_ai_messages',
                'athena_ai_message',
                __('Settings saved successfully!', 'athena-ai'),
                'updated'
            );
            
            // Logge den aktuellen Zustand nach dem Speichern
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->log_current_settings_state('Post-save settings state');
            }
        }
        
        // Einstellungen auf Standardwerte zurücksetzen, wenn der Reset-Button geklickt wurde
        if (isset($_POST['reset_defaults']) && $this->verify_nonce('athena_ai_settings')) {
            $this->reset_to_defaults();
            
            // Auch hier Redirect zum Aktualisieren der Ansicht
            wp_redirect(add_query_arg('settings-reset', 'true'));
            exit;
        }

        // Führe Maintenance-Aktionen aus, wenn angefordert
        if (isset($_POST['fix_cron']) && $this->verify_nonce('athena_ai_maintenance')) {
            $this->fix_cron_schedule();
        }

        // Erstelle Datenbanktabellen, wenn angefordert
        if (isset($_POST['create_tables']) && $this->verify_nonce('athena_ai_maintenance')) {
            $this->create_database_tables();
        }

        // Lese die aktuellen Einstellungen aus der Datenbank
        $settings = $this->get_settings();

        // Sammle Maintenance-Daten
        $maintenance_data = $this->get_maintenance_data();
        
        // Bestimme den aktiven Tab
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'github-settings';

        $this->render_template('settings', [
            'title' => $this->__('Athena AI Settings', 'athena-ai'),
            'settings' => $settings,
            'nonce_field' => $this->get_nonce_field('athena_ai_settings'),
            'maintenance_nonce_field' => $this->get_nonce_field('athena_ai_maintenance'),
            'maintenance' => $maintenance_data,
            'active_tab' => $active_tab,
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
        ]);
    }

    /**
     * Save settings with direct database access and verification
     */
    private function save_settings() {
        // Enable error logging
        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            @ini_set('log_errors', 1);
            @ini_set('error_log', WP_CONTENT_DIR . '/debug.log');
        }
        
        // DEBUG: Ausgabe der POST-Daten in Logdatei
        error_log('[ATHENA AI] ==================== POST DATA ====================');
        error_log(print_r($_POST, true));
        error_log('[ATHENA AI] ==================== END POST DATA ====================');
        
        // DEBUG: Zeige POST-Daten als Admin-Hinweis an
        add_settings_error(
            'athena_ai_messages',
            'athena_ai_post_debug',
            'POST-Daten: <pre style="max-height: 200px; overflow: auto; font-size: 12px; background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">' . 
            htmlspecialchars(print_r($_POST, true)) . 
            '</pre>',
            'info'
        );
        
        // Überprüfe DB-Status vor dem Speichern
        $this->verify_db_settings('Vor dem Speichern');
        
        // Log start of save process
        error_log('[ATHENA AI] ==================== SETTINGS SAVE PROCESS STARTED ====================');
        
        // Erzwinge globales Cache-Löschen vor dem Speichern
        wp_cache_flush();
        
        try {
            // Initialize results array
            $results = [];
            
            // Extrahiere und verarbeite die POST-Daten
            $openai_settings = [
                'athena_ai_openai_api_key' => sanitize_text_field($_POST['athena_ai_openai_api_key'] ?? ''),
                'athena_ai_openai_org_id' => sanitize_text_field($_POST['athena_ai_openai_org_id'] ?? ''),
                'athena_ai_openai_default_model' => sanitize_text_field($_POST['athena_ai_openai_default_model'] ?? 'gpt-4'),
                'athena_ai_openai_temperature' => isset($_POST['athena_ai_openai_temperature']) ? 
                    max(0, min(2, (float)$_POST['athena_ai_openai_temperature'])) : 0.7
            ];
            
            // Weitere Einstellungen extrahieren
            $other_settings = [
                'athena_ai_github_token' => sanitize_text_field($_POST['athena_ai_github_token'] ?? ''),
                'athena_ai_github_owner' => sanitize_text_field($_POST['athena_ai_github_owner'] ?? ''),
                'athena_ai_github_repo' => sanitize_text_field($_POST['athena_ai_github_repo'] ?? ''),
                'athena_ai_dalle_size' => sanitize_text_field($_POST['athena_ai_dalle_size'] ?? ''),
                'athena_ai_dalle_quality' => sanitize_text_field($_POST['athena_ai_dalle_quality'] ?? ''),
                'athena_ai_dalle_style' => sanitize_text_field($_POST['athena_ai_dalle_style'] ?? ''),
                'athena_ai_midjourney_api_key' => sanitize_text_field($_POST['athena_ai_midjourney_api_key'] ?? ''),
                'athena_ai_midjourney_version' => sanitize_text_field($_POST['athena_ai_midjourney_version'] ?? ''),
                'athena_ai_midjourney_style' => sanitize_text_field($_POST['athena_ai_midjourney_style'] ?? ''),
                'athena_ai_stablediffusion_api_key' => sanitize_text_field($_POST['athena_ai_stablediffusion_api_key'] ?? ''),
                'athena_ai_stablediffusion_model' => sanitize_text_field($_POST['athena_ai_stablediffusion_model'] ?? ''),
                'athena_ai_stablediffusion_steps' => intval($_POST['athena_ai_stablediffusion_steps'] ?? 30),
                'athena_ai_enable_debug_mode' => isset($_POST['athena_ai_enable_debug_mode']) ? '1' : '0',
                'athena_ai_feed_cron_interval' => sanitize_text_field($_POST['athena_ai_feed_cron_interval'] ?? 'hourly')
            ];
            
            // 1. Zuerst die kritischen OpenAI Einstellungen speichern
            error_log('[ATHENA AI] Speichere kritische OpenAI-Einstellungen...');
            foreach ($openai_settings as $key => $value) {
                $save_success = $this->force_save_option($key, $value);
                $results[$key] = $save_success;
                
                if (!$save_success) {
                    error_log("[ATHENA AI] KRITISCHER FEHLER: Option '{$key}' konnte nicht gespeichert werden!");
                }
            }
            
            // 2. Dann die anderen Einstellungen
            error_log('[ATHENA AI] Speichere weitere Einstellungen...');
            foreach ($other_settings as $key => $value) {
                $save_success = $this->force_save_option($key, $value);
                $results[$key] = $save_success;
            }
            
            // 3. Aktualisiere Cron-Jobs, wenn sich das Intervall geändert hat
            $old_interval = get_option('athena_ai_feed_cron_interval', 'hourly');
            $new_interval = $other_settings['athena_ai_feed_cron_interval'];
            
            if ($old_interval !== $new_interval) {
                error_log("[ATHENA AI] Cron-Intervall wurde geändert: {$old_interval} -> {$new_interval}");
                
                // Bestehenden Cron-Job entfernen
                $timestamp = wp_next_scheduled('athena_fetch_feeds');
                if ($timestamp) {
                    wp_unschedule_event($timestamp, 'athena_fetch_feeds');
                    error_log('[ATHENA AI] Bestehender Cron-Job wurde entfernt.');
                }
                
                // Neuen Cron-Job planen
                $schedule_result = wp_schedule_event(time(), $new_interval, 'athena_fetch_feeds');
                error_log('[ATHENA AI] Neuer Cron-Job wurde geplant: ' . ($schedule_result ? 'ERFOLG' : 'FEHLER'));
                
                // Erfolgsmeldung hinzufügen
                add_settings_error(
                    'athena_ai_messages',
                    'athena_ai_cron_rescheduled',
                    $this->__('Feed fetch cron job has been rescheduled with new interval.', 'athena-ai'),
                    'updated'
                );
            }
            
            // Zusammenfassung
            $all_success = !in_array(false, $results, true);
            error_log('[ATHENA AI] Alle Einstellungen erfolgreich gespeichert: ' . ($all_success ? 'JA' : 'NEIN'));
            
            if (!$all_success) {
                $failed_keys = array_keys(array_filter($results, function($v) { return $v === false; }));
                error_log('[ATHENA AI] Fehlerhafte Optionen: ' . implode(', ', $failed_keys));
            }
        } catch (Exception $e) {
            error_log('[ATHENA AI] EXCEPTION: ' . $e->getMessage());
            error_log('[ATHENA AI] ' . $e->getTraceAsString());
            return false;
        }
        
        // Final flush
        wp_cache_flush();
        error_log('[ATHENA AI] ==================== SETTINGS SAVE PROCESS COMPLETED ====================');
        
        // Überprüfe DB-Status nach dem Speichern
        $this->verify_db_settings('Nach dem Speichern');
        
        return $all_success ?? false;
    }

    /**
     * Get current settings
     *
     * @return array
     */
    /**
     * Logs the current state of all settings for debugging purposes
     * 
     * @param string $context Context for the log message
     */
    private function log_current_settings_state($context = '') {
        $settings_to_log = [
            'athena_ai_openai_api_key',
            'athena_ai_openai_org_id',
            'athena_ai_openai_default_model',
            'athena_ai_openai_temperature'
        ];
        
        $log_message = "=== ATHENA AI SETTINGS STATE: {$context} ===\n";
        
        foreach ($settings_to_log as $setting) {
            $value = get_option($setting, 'NOT_SET');
            $log_message .= sprintf(
                "%s: %s\n",
                $setting,
                $setting === 'athena_ai_openai_api_key' && !empty($value) && $value !== 'NOT_SET' 
                    ? substr($value, 0, 3) . '...' . substr($value, -3) . " (length: " . strlen($value) . ")"
                    : (is_string($value) ? $value : print_r($value, true))
            );
        }
        
        error_log($log_message . "======================================\n");
    }
    
    /**
     * Get current settings with proper error handling and fallbacks
     *
     * @return array
     */
    private function get_settings() {
        $settings = [];
        
        // Define all settings with their default values
        $setting_definitions = [
            // GitHub Settings
            'github_token' => 'athena_ai_github_token',
            'github_owner' => 'athena_ai_github_owner',
            'github_repo' => 'athena_ai_github_repo',
            
            // Text AI Settings
            'openai_api_key' => 'athena_ai_openai_api_key',
            'openai_org_id' => 'athena_ai_openai_org_id',
            'openai_default_model' => 'athena_ai_openai_default_model',
            'openai_temperature' => 'athena_ai_openai_temperature',
            
            // Image AI Settings
            'dalle_size' => 'athena_ai_dalle_size',
            'dalle_quality' => 'athena_ai_dalle_quality',
            'dalle_style' => 'athena_ai_dalle_style',
            'midjourney_api_key' => 'athena_ai_midjourney_api_key',
            'midjourney_version' => 'athena_ai_midjourney_version',
            'midjourney_style' => 'athena_ai_midjourney_style',
            'stablediffusion_api_key' => 'athena_ai_stablediffusion_api_key',
            'stablediffusion_model' => 'athena_ai_stablediffusion_model',
            'stablediffusion_steps' => 'athena_ai_stablediffusion_steps',
            
            // Maintenance Settings
            'enable_debug_mode' => 'athena_ai_enable_debug_mode',
            'feed_cron_interval' => 'athena_ai_feed_cron_interval'
        ];
        
        // Get all settings in one database query for better performance
        $all_options = [];
        $option_names = array_values($setting_definitions);
        
        if (!empty($option_names)) {
            global $wpdb;
            $placeholders = implode(', ', array_fill(0, count($option_names), '%s'));
            $query = $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN ($placeholders)",
                $option_names
            );
            
            $results = $wpdb->get_results($query, OBJECT_K);
            
            if ($results) {
                foreach ($results as $option) {
                    $all_options[$option->option_name] = maybe_unserialize($option->option_value);
                }
            }
        }
        
        // Set values with proper fallbacks
        foreach ($setting_definitions as $setting_key => $option_name) {
            if (array_key_exists($option_name, $all_options)) {
                $settings[$setting_key] = $all_options[$option_name];
            } elseif (array_key_exists($setting_key, $this->default_settings)) {
                $settings[$setting_key] = $this->default_settings[$setting_key];
            } else {
                $settings[$setting_key] = '';
            }
        }
        
        // Ensure sensitive data is properly handled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log_current_settings_state('After loading settings');
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
    
    /**
     * Direkte Speicherung einer Option in der Datenbank mit Umgehung des WP-Caches
     * 
     * @param string $option_name Name der Option
     * @param mixed $option_value Wert der Option
     * @return bool Erfolg der Operation
     */
    private function force_save_option($option_name, $option_value) {
        global $wpdb;
        
        // Serialisierung, falls nötig
        if (!is_scalar($option_value)) {
            $option_value = maybe_serialize($option_value);
        }
        
        // Sicherstellen, dass der Wert als String vorliegt
        if (is_bool($option_value)) {
            $option_value = $option_value ? '1' : '0';
        } elseif (is_numeric($option_value)) {
            $option_value = (string) $option_value;
        }
        
        // Vorab Cache leeren
        wp_cache_delete($option_name, 'options');
        
        // Existenzprüfung der Option
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT option_id FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
            $option_name
        ));
        
        // Ausführung mit Fehlerprüfung
        if ($exists) {
            // Update
            $success = $wpdb->update(
                $wpdb->options,
                ['option_value' => $option_value, 'autoload' => 'yes'],
                ['option_name' => $option_name],
                ['%s', '%s'],
                ['%s']
            );
        } else {
            // Insert
            $success = $wpdb->insert(
                $wpdb->options,
                [
                    'option_name' => $option_name,
                    'option_value' => $option_value,
                    'autoload' => 'yes'
                ],
                ['%s', '%s', '%s']
            );
        }
        
        // Erneut Cache leeren nach Operation
        wp_cache_delete($option_name, 'options');
        
        // Verifizieren, dass der Wert tatsächlich gespeichert wurde
        $saved_value = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
            $option_name
        ));
        
        $is_saved = ($saved_value !== null);
        
        // Log-Nachricht mit Privatsphärenschutz für API-Keys
        $log_value = $option_name === 'athena_ai_openai_api_key' && !empty($option_value) 
            ? substr($option_value, 0, 3) . '...' . substr($option_value, -3) . ' (' . strlen($option_value) . ' chars)'
            : $option_value;
            
        error_log(sprintf(
            "[ATHENA] Force Save Option [%s]: %s | Operation: %s | Verified: %s | Raw value length: %d",
            $option_name,
            $log_value,
            $success !== false ? 'SUCCESS' : 'FAILED',
            $is_saved ? 'YES' : 'NO',
            strlen($option_value)
        ));
        
        // Wenn die Direktabfrage fehlschlägt, versuche es mit WordPress-Funktionen
        if (!$is_saved) {
            error_log("[ATHENA] Direktes Speichern fehlgeschlagen. Versuche Fallback mit update_option()");
            $wp_success = update_option($option_name, $option_value, true);
            
            // Cache erneut leeren
            wp_cache_delete($option_name, 'options');
            
            // Verifizieren nach Fallback
            $saved_wp_value = $wpdb->get_var($wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
                $option_name
            ));
            
            $is_saved_wp = ($saved_wp_value !== null);
            error_log("[ATHENA] Fallback Ergebnis: " . ($wp_success ? 'SUCCESS' : 'FAILED') . " | Verified: " . ($is_saved_wp ? 'YES' : 'NO'));
            
            return $is_saved_wp;
        }
        
        return $is_saved;
    }
    
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
        // Bestehende Einstellungen löschen
        $settings = $this->get_settings();
        
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
     * AJAX-Handler zum Leeren des WordPress-Options-Caches
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
        
        global $wpdb;
        $critical_options = [
            'athena_ai_openai_api_key',
            'athena_ai_openai_org_id',
            'athena_ai_openai_default_model',
            'athena_ai_openai_temperature'
        ];
        
        // Leere den Cache für alle kritischen Optionen
        foreach ($critical_options as $option) {
            wp_cache_delete($option, 'options');
            error_log("[ATHENA] Cache for {$option} flushed via AJAX");
        }
        
        // Globaler Cache-Flush
        wp_cache_flush();
        
        // Verifiziere, dass Optionen nun direkt aus der Datenbank gelesen werden
        $api_key = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
            'athena_ai_openai_api_key'
        ));
        
        wp_send_json_success([
            'message' => 'Options cache successfully flushed',
            'api_key_length' => empty($api_key) ? 0 : strlen($api_key)
        ]);
    }

    /**
     * Überprüft und zeigt den aktuellen DB-Status der kritischen Optionen
     * 
     * @param string $context Ein Kontext für die Ausgabe (z.B. "Vor Speichern", "Nach Speichern")
     * @return array Status der Optionen
     */
    private function verify_db_settings($context = '') {
        global $wpdb;
        $critical_options = [
            'athena_ai_openai_api_key',
            'athena_ai_openai_org_id',
            'athena_ai_openai_default_model',
            'athena_ai_openai_temperature'
        ];
        
        $results = [];
        $output = "[ATHENA AI] DB SETTINGS VERIFICATION ({$context})\n";
        
        foreach ($critical_options as $option) {
            // Direkte DB-Abfrage
            $db_value = $wpdb->get_var($wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
                $option
            ));
            
            // get_option Wert
            $option_value = get_option($option, 'NOT_SET');
            
            // Status
            $db_status = isset($db_value) ? "OK" : "MISSING";
            $option_status = ($option_value !== 'NOT_SET') ? "OK" : "CACHE MISS";
            $match_status = ($db_value === $option_value) ? "MATCH" : "MISMATCH";
            
            // Log-freundliche Werte (API-Key maskieren)
            $log_db_value = $option === 'athena_ai_openai_api_key' && !empty($db_value) 
                ? substr($db_value, 0, 3) . '...' . substr($db_value, -3) . " (" . strlen($db_value) . " chars)"
                : $db_value;
                
            $log_option_value = $option === 'athena_ai_openai_api_key' && $option_value !== 'NOT_SET'
                ? substr($option_value, 0, 3) . '...' . substr($option_value, -3) . " (" . strlen($option_value) . " chars)"
                : $option_value;
            
            $results[$option] = [
                'db_value' => $db_value,
                'option_value' => $option_value,
                'db_status' => $db_status,
                'option_status' => $option_status,
                'match_status' => $match_status
            ];
            
            $output .= sprintf(
                "%s: DB[%s]=%s, get_option[%s]=%s, Match: %s\n",
                $option,
                $db_status,
                $log_db_value,
                $option_status,
                $log_option_value,
                $match_status
            );
        }
        
        error_log($output);
        
        // Zur Admin-Anzeige hinzufügen
        $html = '<table class="widefat" style="margin-top: 10px; font-size: 12px;">';
        $html .= '<thead><tr>';
        $html .= '<th>Option</th>';
        $html .= '<th>DB Status</th>';
        $html .= '<th>DB Value</th>';
        $html .= '<th>get_option Status</th>';
        $html .= '<th>get_option Value</th>';
        $html .= '<th>Match</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($results as $option => $data) {
            $display_db_value = $option === 'athena_ai_openai_api_key' && !empty($data['db_value']) 
                ? substr($data['db_value'], 0, 3) . '...' . substr($data['db_value'], -3) . " (" . strlen($data['db_value']) . ")"
                : $data['db_value'];
                
            $display_option_value = $option === 'athena_ai_openai_api_key' && $data['option_value'] !== 'NOT_SET'
                ? substr($data['option_value'], 0, 3) . '...' . substr($data['option_value'], -3) . " (" . strlen($data['option_value']) . ")"
                : $data['option_value'];
            
            $db_color = $data['db_status'] === 'OK' ? 'green' : 'red';
            $option_color = $data['option_status'] === 'OK' ? 'green' : 'orange';
            $match_color = $data['match_status'] === 'MATCH' ? 'green' : 'red';
            
            $html .= "<tr>";
            $html .= "<td>{$option}</td>";
            $html .= "<td style='color:{$db_color}'>{$data['db_status']}</td>";
            $html .= "<td>{$display_db_value}</td>";
            $html .= "<td style='color:{$option_color}'>{$data['option_status']}</td>";
            $html .= "<td>{$display_option_value}</td>";
            $html .= "<td style='color:{$match_color}'>{$data['match_status']}</td>";
            $html .= "</tr>";
        }
        
        $html .= '</tbody></table>';
        
        add_settings_error(
            'athena_ai_messages',
            'athena_ai_db_verify_' . str_replace(' ', '_', strtolower($context)),
            'DB-Verifikation (' . $context . '): ' . $html,
            'info'
        );
        
        return $results;
    }
}
