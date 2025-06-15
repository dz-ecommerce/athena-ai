    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'athena-ai_page_athena-ai-profile') {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'athena-ai-admin-profile',
            ATHENA_AI_PLUGIN_URL . 'assets/css/admin/profile.css',
            [],
            ATHENA_AI_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'athena-ai-admin-profile',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/profile.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );

        // Enqueue Prompt Manager
        wp_enqueue_script(
            'athena-ai-prompt-manager',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/PromptManager.js',
            [],
            ATHENA_AI_VERSION,
            true
        );

        // AJAX data
        wp_localize_script('athena-ai-admin-profile', 'athenaAiAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('athena_ai_nonce'),
            'strings' => [
                'saving' => __('Speichern...', 'athena-ai'),
                'saved' => __('Gespeichert!', 'athena-ai'),
                'error' => __('Fehler beim Speichern', 'athena-ai'),
            ]
        ]);

        // Prompt-Konfiguration laden
        $this->enqueue_prompt_config();
    }

    /**
     * Prompt-Konfiguration ins Frontend laden
     */
    private function enqueue_prompt_config() {
        $prompt_manager = Athena_AI_Prompt_Manager::get_instance();
        
        // Prompt-Konfiguration als JSON ins DOM einbetten
        $config = [
            'prompts' => [],
            'global' => [],
            'validation' => []
        ];
        
        // Alle verfügbaren Modals laden
        foreach ($prompt_manager->get_available_modals() as $modal_type) {
            $config['prompts'][$modal_type] = $prompt_manager->get_prompt($modal_type);
        }
        
        // Globale Einstellungen
        $config['global'] = [
            'default_provider' => $prompt_manager->get_global_setting('default_provider'),
            'test_mode_available' => $prompt_manager->get_global_setting('test_mode_available'),
            'debug_mode' => $prompt_manager->get_global_setting('debug_mode')
        ];
        
        // Validierungsregeln
        $config['validation'] = $prompt_manager->get_validation_rules();
        
        // JSON-Konfiguration ins DOM einbetten
        wp_add_inline_script(
            'athena-ai-prompt-manager',
            'window.athenaAiPromptConfig = ' . wp_json_encode($config) . ';',
            'before'
        );
        
        // Alternativ: Als verstecktes DOM-Element
        add_action('admin_footer', function() use ($config) {
            echo '<script type="application/json" id="athena-ai-prompt-config">' . 
                 wp_json_encode($config) . 
                 '</script>';
        });
    } 