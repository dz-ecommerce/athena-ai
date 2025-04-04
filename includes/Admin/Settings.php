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
        'stablediffusion_steps' => '30'
    ];

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

        return $settings;
    }
}