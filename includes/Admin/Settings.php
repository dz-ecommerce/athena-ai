<?php
namespace AthenaAI\Admin;

class Settings extends BaseAdmin {
    /**
     * Render the settings page
     */
    public function render_page() {
        if (isset($_POST['submit']) && $this->verify_nonce('athena_ai_settings')) {
            $this->save_settings();
        }

        $settings = $this->get_settings();
        
        $this->render_template('settings', [
            'title' => $this->__('Athena AI Settings', 'athena-ai'),
            'settings' => $settings,
            'nonce_field' => $this->get_nonce_field('athena_ai_settings'),
        ]);
    }

    /**
     * Save settings
     */
    private function save_settings() {
        $settings = [
            'athena_ai_api_key' => sanitize_text_field($_POST['athena_ai_api_key'] ?? ''),
            'athena_ai_enabled' => isset($_POST['athena_ai_enabled']),
        ];

        foreach ($settings as $key => $value) {
            update_option($key, $value);
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
        return [
            'api_key' => get_option('athena_ai_api_key', ''),
            'enabled' => get_option('athena_ai_enabled', true),
        ];
    }
} 