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
     * Render the settings page
     */
    public function render_page() {
        // Debug-Ausgabe vor der Verarbeitung
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Athena AI: Pre-processing - API Key in DB: ' . 
                (empty(get_option('athena_ai_openai_api_key')) ? 'EMPTY' : 'SET (' . substr(get_option('athena_ai_openai_api_key'), 0, 5) . '...)'));
            error_log('Athena AI: Pre-processing - Org ID in DB: ' . 
                get_option('athena_ai_openai_org_id', 'NOT SET'));
            error_log('Athena AI: Pre-processing - Default Model in DB: ' . 
                get_option('athena_ai_openai_default_model', 'NOT SET'));
            error_log('Athena AI: Pre-processing - Temperature in DB: ' . 
                get_option('athena_ai_openai_temperature', 'NOT SET'));
        }
        
        if (isset($_POST['submit']) && $this->verify_nonce('athena_ai_settings')) {
            // Versuch mit der regulären Speichermethode
            $this->save_settings();
            
            // Zusätzlich den direkten Force-Save-Mechanismus verwenden
            $this->force_save_settings();
            
            // Nach dem Speichern sofort neu laden, um die aktuellen Werte anzuzeigen
            // Dieser Ansatz verhindert Probleme mit veralteten Werten im Speicher
            wp_safe_redirect(add_query_arg('settings-updated', 'true', $_SERVER['REQUEST_URI']));
            exit;
        }
        
        // Einstellungen auf Standardwerte zurücksetzen, wenn der Reset-Button geklickt wurde
        if (isset($_POST['reset_defaults']) && $this->verify_nonce('athena_ai_settings')) {
            $this->reset_to_defaults();
        }

        // Führe Maintenance-Aktionen aus, wenn angefordert
        if (isset($_POST['fix_cron']) && $this->verify_nonce('athena_ai_maintenance')) {
            $this->fix_cron_schedule();
        }

        // Erstelle Datenbanktabellen, wenn angefordert
        if (isset($_POST['create_tables']) && $this->verify_nonce('athena_ai_maintenance')) {
            $this->create_database_tables();
        }

        $settings = $this->get_settings();

        // Sammle Maintenance-Daten
        $maintenance_data = $this->get_maintenance_data();

        $this->render_template('settings', [
            'title' => $this->__('Athena AI Settings', 'athena-ai'),
            'settings' => $settings,
            'nonce_field' => $this->get_nonce_field('athena_ai_settings'),
            'maintenance_nonce_field' => $this->get_nonce_field('athena_ai_maintenance'),
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
        ]);
    }

    /**
     * Save settings with direct database access and verification
     */
    private function save_settings() {
        // Enable error logging
        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            define('WP_DEBUG_LOG', true);
        }
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            define('WP_DEBUG', true);
        }
        
        // Log start of save process
        error_log('=== ATHENA AI SETTINGS SAVE PROCESS STARTED ===');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Initialize settings array
        $settings = [];
        $results = [];
        
        // Process OpenAI settings first
        $openai_settings = [
            'athena_ai_openai_api_key' => [
                'value' => sanitize_text_field($_POST['athena_ai_openai_api_key'] ?? ''),
                'type' => 'string',
                'sensitive' => true
            ],
            'athena_ai_openai_org_id' => [
                'value' => sanitize_text_field($_POST['athena_ai_openai_org_id'] ?? ''),
                'type' => 'string',
                'sensitive' => false
            ],
            'athena_ai_openai_default_model' => [
                'value' => sanitize_text_field($_POST['athena_ai_openai_default_model'] ?? 'gpt-4'),
                'type' => 'string',
                'sensitive' => false
            ],
            'athena_ai_openai_temperature' => [
                'value' => isset($_POST['athena_ai_openai_temperature']) ? 
                    max(0, min(2, (float)$_POST['athena_ai_openai_temperature'])) : 0.7,
                'type' => 'float',
                'sensitive' => false
            ]
        ];
        
        // Process other settings
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
            'athena_ai_stablediffusion_steps' => intval($_POST['athena_ai_stablediffusion_steps'] ?? 30)
        ];
        
        // Save OpenAI settings with verification
        foreach ($openai_settings as $key => $setting) {
            $value = $setting['value'];
            $log_value = $setting['sensitive'] ? 
                (strlen($value) > 3 ? substr($value, 0, 3) . '...' . substr($value, -3) : '***') : 
                $value;
            
            // Save using direct database access
            global $wpdb;
            $result = $wpdb->query($wpdb->prepare(
                "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) 
                VALUES (%s, %s, 'yes') 
                ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
                $key,
                $value
            ));
            
            // Clear cache
            wp_cache_delete($key, 'options');
            
            // Verify the saved value
            $saved_value = get_option($key, 'NOT_SAVED');
            $is_saved = $saved_value !== 'NOT_SAVED';
            
            // Log the result
            error_log(sprintf(
                "Setting '%s': %s | Saved: %s | Verified: %s",
                $key,
                $log_value,
                $result !== false ? 'YES' : 'NO',
                $is_saved ? 'YES' : 'NO'
            ));
            
            $results[$key] = $result !== false && $is_saved;
            $settings[$key] = $value;
        }
        
        // Save other settings
        foreach ($other_settings as $key => $value) {
            $result = update_option($key, $value);
            $results[$key] = $result;
            $settings[$key] = $value;
            error_log(sprintf("Setting '%s' saved: %s", $key, $result ? 'SUCCESS' : 'FAILED'));
        }
        
        // Log final results
        error_log('=== ATHENA AI SETTINGS SAVE RESULTS ===');
        error_log('All settings saved successfully: ' . (in_array(false, $results, true) ? 'NO' : 'YES'));
        error_log('======================================');

        // Image AI Settings
        $settings['athena_ai_dalle_size'] = sanitize_text_field(
            $_POST['athena_ai_dalle_size'] ?? ''
        );
        $settings['athena_ai_dalle_quality'] = sanitize_text_field(
            $_POST['athena_ai_dalle_quality'] ?? ''
        );
        $settings['athena_ai_dalle_style'] = sanitize_text_field(
            $_POST['athena_ai_dalle_style'] ?? ''
        );

        $settings['athena_ai_midjourney_api_key'] = sanitize_text_field(
            $_POST['athena_ai_midjourney_api_key'] ?? ''
        );
        $settings['athena_ai_midjourney_version'] = sanitize_text_field(
            $_POST['athena_ai_midjourney_version'] ?? ''
        );
        $settings['athena_ai_midjourney_style'] = sanitize_text_field(
            $_POST['athena_ai_midjourney_style'] ?? ''
        );

        $settings['athena_ai_stablediffusion_api_key'] = sanitize_text_field(
            $_POST['athena_ai_stablediffusion_api_key'] ?? ''
        );
        $settings['athena_ai_stablediffusion_model'] = sanitize_text_field(
            $_POST['athena_ai_stablediffusion_model'] ?? ''
        );
        $settings['athena_ai_stablediffusion_steps'] = intval(
            $_POST['athena_ai_stablediffusion_steps'] ?? 30
        );

        // Maintenance Settings
        $settings['athena_ai_enable_debug_mode'] = isset($_POST['athena_ai_enable_debug_mode'])
            ? '1'
            : '0';

        // Feed-Abrufintervall korrekt speichern
        $feed_cron_interval = sanitize_text_field(
            $_POST['athena_ai_feed_cron_interval'] ?? 'hourly'
        );
        $settings['athena_ai_feed_cron_interval'] = $feed_cron_interval;

        if (WP_DEBUG) {
            error_log("Athena AI: Saving feed cron interval: {$feed_cron_interval}");
        }

            error_log("Logging der update_option Aufrufe in save_settings:");
        
        // Die update_option Aufrufe für die restlichen Optionen überspringen, diese werden direkt gespeichert
        $skip_options = [
            'athena_ai_openai_api_key', 
            'athena_ai_openai_org_id', 
            'athena_ai_openai_default_model',
            'athena_ai_openai_temperature'
        ];
        
        // Überprüfen der gespeicherten Werte nach der Speicherung
        error_log("======= ATHENA SETTINGS VERIFICATION =======\n");
        error_log("API Key nach Speicherung: " . (empty(get_option('athena_ai_openai_api_key')) ? 'LEER' : 'WERT GESETZT'));
        error_log("Org ID nach Speicherung: " . get_option('athena_ai_openai_org_id', 'NICHT GESETZT'));
        error_log("Default Model nach Speicherung: " . get_option('athena_ai_openai_default_model', 'NICHT GESETZT'));
        error_log("Temperature nach Speicherung: " . get_option('athena_ai_openai_temperature', 'NICHT GESETZT'));

        // Nur die Optionen speichern, die nicht bereits direkt gespeichert wurden
        foreach ($settings as $key => $value) {
            if (!in_array($key, $skip_options)) {
                $result = update_option($key, $value);
                error_log("Option speichern [{$key}]: " . ($result ? 'ERFOLG' : 'FEHLER') . " - Wert: {$value}");
            } else {
                error_log("Option überspringen [{$key}]: Wurde bereits direkt gespeichert.");
            }
        }

        // Wenn sich das Cron-Intervall geändert hat, den Cron-Job neu planen
        $old_interval = get_option('athena_ai_feed_cron_interval', 'hourly');
        $new_interval = $settings['athena_ai_feed_cron_interval'];

        if ($old_interval !== $new_interval) {
            // Bestehenden Cron-Job entfernen
            $timestamp = wp_next_scheduled('athena_fetch_feeds');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'athena_fetch_feeds');
            }

            // Neuen Cron-Job mit dem neuen Intervall planen
            wp_schedule_event(time(), $new_interval, 'athena_fetch_feeds');

            // Erfolgsmeldung hinzufügen
            add_settings_error(
                'athena_ai_messages',
                'athena_ai_cron_rescheduled',
                $this->__(
                    'Feed fetch cron job has been rescheduled with new interval.',
                    'athena-ai'
                ),
                'updated'
            );
        }

        // Prüfen, ob Validierungsfehler vorliegen
        if (!empty($validation_errors)) {
            foreach ($validation_errors as $error) {
                add_settings_error(
                    'athena_ai_messages',
                    'athena_ai_validation_error',
                    $error,
                    'error'
                );
            }
        } else {
            // Erfolgsmeldung für den Benutzer anzeigen
            add_settings_error(
                'athena_ai_messages',
                'athena_ai_message',
                $this->__('All settings have been successfully saved.', 'athena-ai'),
                'updated'
            );
        }
    }

    /**
     * Get current settings
     *
     * @return array
     */
    private function get_settings() {
        $settings = [];

        // GitHub Settings
        $settings['github_token'] = get_option('athena_ai_github_token', '');
        $settings['github_owner'] = get_option(
            'athena_ai_github_owner',
            $this->default_settings['github_owner']
        );
        $settings['github_repo'] = get_option(
            'athena_ai_github_repo',
            $this->default_settings['github_repo']
        );

        // Text AI Settings
        // Verbesserte Abfrage für API-Schlüssel
        $openai_api_key = get_option('athena_ai_openai_api_key', '');
        $openai_org_id = get_option('athena_ai_openai_org_id', '');
        
        // Spezielles Debug-Logging für Diagnosezwecke
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Athena AI: Retrieved API Key from DB: ' . 
                (empty($openai_api_key) ? 'EMPTY' : 'SET (length: ' . strlen($openai_api_key) . ')'));
            error_log('Athena AI: Retrieved Org ID from DB: ' . 
                (empty($openai_org_id) ? 'EMPTY' : 'SET (length: ' . strlen($openai_org_id) . ')'));
        }
        
        $settings['openai_api_key'] = $openai_api_key;
        $settings['openai_org_id'] = $openai_org_id;
        $settings['openai_default_model'] = get_option(
            'athena_ai_openai_default_model',
            $this->default_settings['openai_default_model']
        );
        $settings['openai_temperature'] = get_option(
            'athena_ai_openai_temperature',
            $this->default_settings['openai_temperature']
        );

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
     * Hilfsmethod für direktes Speichern einer Option in der Datenbank
     */
    private function force_save_option($option_name, $option_value) {
        global $wpdb;
        
        // Option-Wert serialisieren, wenn es kein einfacher Datentyp ist
        if (!is_scalar($option_value)) {
            $option_value = maybe_serialize($option_value);
        }
        
        // Prüfen, ob die Option bereits existiert
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s",
            $option_name
        ));
        
        if ($exists) {
            // Option aktualisieren
            $result = $wpdb->update(
                $wpdb->options,
                ['option_value' => $option_value],
                ['option_name' => $option_name]
            );
            error_log("Force Update Option [{$option_name}]: " . ($result !== false ? 'ERFOLG' : 'FEHLER') . " - Wert: {$option_value}");
        } else {
            // Option erstellen
            $result = $wpdb->insert(
                $wpdb->options,
                [
                    'option_name' => $option_name,
                    'option_value' => $option_value,
                    'autoload' => 'yes'
                ]
            );
            error_log("Force Insert Option [{$option_name}]: " . ($result !== false ? 'ERFOLG' : 'FEHLER') . " - Wert: {$option_value}");
        }
        
        // Cache leeren, damit die Änderungen sofort wirksam werden
        wp_cache_delete($option_name, 'options');
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
}
