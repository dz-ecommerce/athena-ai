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

        'anthropic_api_key' => '',
        'anthropic_model' => 'claude-2',

        'google_api_key' => '',
        'google_model' => 'gemini-pro',
        'google_safety_setting' => 'standard',

        'mistral_api_key' => '',
        'mistral_model' => 'mistral-medium',

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
        'feed_cron_interval' => 'hourly' // Default: hourly
    ];

    /**
     * Render the settings page
     */
    public function render_page() {
        if (isset($_POST['submit']) && $this->verify_nonce('athena_ai_settings')) {
            $this->save_settings();
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
                    'gpt-4' => 'GPT-4',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                ],
                'anthropic' => [
                    'claude-2' => 'Claude 2',
                    'claude-instant' => 'Claude Instant',
                ],
                'google' => [
                    'gemini-pro' => 'Gemini Pro',
                    'gemini-ultra' => 'Gemini Ultra',
                ],
                'mistral' => [
                    'mistral-small' => 'Mistral Small',
                    'mistral-medium' => 'Mistral Medium',
                    'mistral-large' => 'Mistral Large',
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
     * Save settings
     */
    private function save_settings() {
        $settings = [];

        // GitHub Settings
        $settings['athena_ai_github_token'] = sanitize_text_field($_POST['athena_ai_github_token'] ?? '');
        $settings['athena_ai_github_owner'] = sanitize_text_field($_POST['athena_ai_github_owner'] ?? '');
        $settings['athena_ai_github_repo'] = sanitize_text_field($_POST['athena_ai_github_repo'] ?? '');

        // Text AI Settings
        $settings['athena_ai_openai_api_key'] = sanitize_text_field($_POST['athena_ai_openai_api_key'] ?? '');
        $settings['athena_ai_openai_org_id'] = sanitize_text_field($_POST['athena_ai_openai_org_id'] ?? '');
        $settings['athena_ai_openai_default_model'] = sanitize_text_field($_POST['athena_ai_openai_default_model'] ?? '');
        $settings['athena_ai_openai_temperature'] = floatval($_POST['athena_ai_openai_temperature'] ?? 0.7);

        $settings['athena_ai_anthropic_api_key'] = sanitize_text_field($_POST['athena_ai_anthropic_api_key'] ?? '');
        $settings['athena_ai_anthropic_model'] = sanitize_text_field($_POST['athena_ai_anthropic_model'] ?? '');

        $settings['athena_ai_google_api_key'] = sanitize_text_field($_POST['athena_ai_google_api_key'] ?? '');
        $settings['athena_ai_google_model'] = sanitize_text_field($_POST['athena_ai_google_model'] ?? '');
        $settings['athena_ai_google_safety_setting'] = sanitize_text_field($_POST['athena_ai_google_safety_setting'] ?? '');

        $settings['athena_ai_mistral_api_key'] = sanitize_text_field($_POST['athena_ai_mistral_api_key'] ?? '');
        $settings['athena_ai_mistral_model'] = sanitize_text_field($_POST['athena_ai_mistral_model'] ?? '');

        // Image AI Settings
        $settings['athena_ai_dalle_size'] = sanitize_text_field($_POST['athena_ai_dalle_size'] ?? '');
        $settings['athena_ai_dalle_quality'] = sanitize_text_field($_POST['athena_ai_dalle_quality'] ?? '');
        $settings['athena_ai_dalle_style'] = sanitize_text_field($_POST['athena_ai_dalle_style'] ?? '');

        $settings['athena_ai_midjourney_api_key'] = sanitize_text_field($_POST['athena_ai_midjourney_api_key'] ?? '');
        $settings['athena_ai_midjourney_version'] = sanitize_text_field($_POST['athena_ai_midjourney_version'] ?? '');
        $settings['athena_ai_midjourney_style'] = sanitize_text_field($_POST['athena_ai_midjourney_style'] ?? '');

        $settings['athena_ai_stablediffusion_api_key'] = sanitize_text_field($_POST['athena_ai_stablediffusion_api_key'] ?? '');
        $settings['athena_ai_stablediffusion_model'] = sanitize_text_field($_POST['athena_ai_stablediffusion_model'] ?? '');
        $settings['athena_ai_stablediffusion_steps'] = intval($_POST['athena_ai_stablediffusion_steps'] ?? 30);
        
        // Maintenance Settings
        $settings['athena_ai_enable_debug_mode'] = isset($_POST['athena_ai_enable_debug_mode']) ? '1' : '0';
        
        // Feed-Abrufintervall korrekt speichern
        $feed_cron_interval = sanitize_text_field($_POST['athena_ai_feed_cron_interval'] ?? 'hourly');
        $settings['athena_ai_feed_cron_interval'] = $feed_cron_interval;
        
        if (WP_DEBUG) {
            error_log("Athena AI: Saving feed cron interval: {$feed_cron_interval}");
        }

        foreach ($settings as $key => $value) {
            update_option($key, $value);
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
                $this->__('Feed fetch cron job has been rescheduled with new interval.', 'athena-ai'),
                'updated'
            );
        }

        add_settings_error(
            'athena_ai_messages',
            'athena_ai_message',
            $this->__('Settings Saved', 'athena-ai'),
            'updated'
        );
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
        $settings['github_owner'] = get_option('athena_ai_github_owner', $this->default_settings['github_owner']);
        $settings['github_repo'] = get_option('athena_ai_github_repo', $this->default_settings['github_repo']);

        // Text AI Settings
        $settings['openai_api_key'] = get_option('athena_ai_openai_api_key', '');
        $settings['openai_org_id'] = get_option('athena_ai_openai_org_id', '');
        $settings['openai_default_model'] = get_option('athena_ai_openai_default_model', $this->default_settings['openai_default_model']);
        $settings['openai_temperature'] = get_option('athena_ai_openai_temperature', $this->default_settings['openai_temperature']);

        $settings['anthropic_api_key'] = get_option('athena_ai_anthropic_api_key', '');
        $settings['anthropic_model'] = get_option('athena_ai_anthropic_model', $this->default_settings['anthropic_model']);

        $settings['google_api_key'] = get_option('athena_ai_google_api_key', '');
        $settings['google_model'] = get_option('athena_ai_google_model', $this->default_settings['google_model']);
        $settings['google_safety_setting'] = get_option('athena_ai_google_safety_setting', $this->default_settings['google_safety_setting']);

        $settings['mistral_api_key'] = get_option('athena_ai_mistral_api_key', '');
        $settings['mistral_model'] = get_option('athena_ai_mistral_model', $this->default_settings['mistral_model']);

        // Image AI Settings
        $settings['dalle_size'] = get_option('athena_ai_dalle_size', $this->default_settings['dalle_size']);
        $settings['dalle_quality'] = get_option('athena_ai_dalle_quality', $this->default_settings['dalle_quality']);
        $settings['dalle_style'] = get_option('athena_ai_dalle_style', $this->default_settings['dalle_style']);

        $settings['midjourney_api_key'] = get_option('athena_ai_midjourney_api_key', '');
        $settings['midjourney_version'] = get_option('athena_ai_midjourney_version', $this->default_settings['midjourney_version']);
        $settings['midjourney_style'] = get_option('athena_ai_midjourney_style', $this->default_settings['midjourney_style']);

        $settings['stablediffusion_api_key'] = get_option('athena_ai_stablediffusion_api_key', '');
        $settings['stablediffusion_model'] = get_option('athena_ai_stablediffusion_model', $this->default_settings['stablediffusion_model']);
        $settings['stablediffusion_steps'] = get_option('athena_ai_stablediffusion_steps', $this->default_settings['stablediffusion_steps']);

        // Maintenance Settings
        $settings['enable_debug_mode'] = get_option('athena_ai_enable_debug_mode', $this->default_settings['enable_debug_mode']);
        // Feed-Cron-Intervall korrekt abrufen
        $settings['feed_cron_interval'] = get_option('athena_ai_feed_cron_interval', $this->default_settings['feed_cron_interval']);

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
        
        $data['feed_items_table_exists'] = $wpdb->get_var("SHOW TABLES LIKE '{$feed_items_table}'") === $feed_items_table;
        $data['feed_metadata_table_exists'] = $wpdb->get_var("SHOW TABLES LIKE '{$feed_metadata_table}'") === $feed_metadata_table;
        
        // Feed-Items zählen
        if ($data['feed_items_table_exists']) {
            $data['feed_items_count'] = $wpdb->get_var("SELECT COUNT(*) FROM {$feed_items_table}");
        } else {
            $data['feed_items_count'] = 0;
        }
        
        // Feed-Metadaten zählen
        if ($data['feed_metadata_table_exists']) {
            $data['feed_metadata_count'] = $wpdb->get_var("SELECT COUNT(*) FROM {$feed_metadata_table}");
        } else {
            $data['feed_metadata_count'] = 0;
        }
        
        // Cron-Status prüfen
        $data['cron_event_scheduled'] = wp_next_scheduled('athena_fetch_feeds') !== false;
        if ($data['cron_event_scheduled']) {
            $data['next_cron_run'] = wp_next_scheduled('athena_fetch_feeds');
            $data['next_cron_run_human'] = human_time_diff(time(), $data['next_cron_run']) . ' ' . __('from now', 'athena-ai');
        }
        
        // Letzte Fetch-Zeit
        $data['last_fetch_time'] = get_option('athena_last_feed_fetch', 0);
        if ($data['last_fetch_time'] > 0) {
            $data['last_fetch_human'] = human_time_diff($data['last_fetch_time'], time()) . ' ' . __('ago', 'athena-ai');
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
     * Erstellt die erforderlichen Datenbanktabellen für das Feed-System
     */
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
                $this->__('There was an error creating the feed database tables. Please check the error log for details.', 'athena-ai'),
                'error'
            );
        }
    }
}