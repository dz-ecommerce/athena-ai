<?php
namespace AthenaAI\Admin;

class Settings extends BaseAdmin {
    /**
     * Default settings
     */
    private $default_settings = [
        // Text AI Settings
        'openai_api_key' => '',
        'openai_org_id' => '',
        'openai_default_model' => 'gpt-4',
        'openai_temperature' => '0.7',
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

        // Lese die aktuellen Einstellungen aus der Datenbank
        $settings = $this->get_settings();

        $this->render_template('settings', [
            'title' => $this->__('Athena AI Settings', 'athena-ai'),
            'settings' => $settings,
            'nonce_field' => $this->get_nonce_field('athena_ai_settings', '_wpnonce_athena_ai_settings'),
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
            ],
        ]);
    }

    /**
     * Save settings using standard WordPress functions
     */
    public function save_settings() {
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
                update_option($option_name, $value);
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
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
        
        return $settings;
    }

    /**
     * Get current settings
     */
    private function get_settings() {
        $settings = [];
        
        // Text AI Settings
        $encrypted_key = get_option('athena_ai_openai_api_key', '');
        $settings['openai_api_key'] = $encrypted_key ? $this->decrypt_api_key($encrypted_key) : '';
        $settings['openai_org_id'] = get_option('athena_ai_openai_org_id', $this->default_settings['openai_org_id']);
        $settings['openai_default_model'] = get_option('athena_ai_openai_default_model', $this->default_settings['openai_default_model']);
        $settings['openai_temperature'] = get_option('athena_ai_openai_temperature', $this->default_settings['openai_temperature']);
        
        return $settings;
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

    // Holt den Verschlüsselungs-Schlüssel aus der Datenbank
    private function get_encryption_key() {
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
