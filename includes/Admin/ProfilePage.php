<?php
/**
 * Athena AI Profile Page
 *
 * Diese Klasse behandelt die Anzeige und Logik der Athena AI Profile-Verwaltungsseite.
 *
 * @package AthenaAI\Admin
 */

declare(strict_types=1);

namespace AthenaAI\Admin;

// WordPress-Funktionen importieren
use function add_action;
use function add_submenu_page;
use function register_setting;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function wp_enqueue_script;
use function wp_localize_script;
use function wp_add_inline_script;
use function wp_json_encode;
use function wp_create_nonce;
use function admin_url;
use function __;
use function plugin_dir_path;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * ProfilePage-Klasse zur Verwaltung von Athena AI Profilen.
 */
class ProfilePage {
    /**
     * Registriert die Profilseite im Admin-Menü.
     *
     * @return void
     */
    public static function register(): void {
        add_action('admin_menu', [self::class, 'add_profile_page'], 20);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    /**
     * Fügt die Profilseite zum Admin-Menü hinzu.
     * 
     * Diese Seite soll vor der Einstellungsseite erscheinen.
     *
     * @return void
     */
    public static function add_profile_page(): void {
        // Im Athena AI Hauptmenü registrieren (vor Settings)
        $parent_slug = 'edit.php?post_type=athena-feed';
        
        add_submenu_page(
            $parent_slug,
            __('Athena AI Profiles', 'athena-ai'),
            __('Profiles', 'athena-ai'),
            'manage_athena_ai', // Verwende die gleiche Berechtigung wie Settings
            'athena-ai-profiles',
            [self::class, 'render_page']
        );
    }

    /**
     * Registriert die Einstellungen für die Profilseite.
     *
     * @return void
     */
    public static function register_settings(): void {
        register_setting(
            'athena_ai_profile_settings',
            'athena_ai_profiles', 
            ['sanitize_callback' => [self::class, 'sanitize_profile_settings']]
        );
        
        // Keine Einstellungsgruppen oder Felder für Profile Configuration
    }
    
    /**
     * Sanitiert die Profileinstellungen.
     *
     * @param array $input Die Eingabewerte.
     * @return array Die sanitierten Werte.
     */
    public static function sanitize_profile_settings(array $input): array {
        $sanitized = [];
        
        // Unternehmensprofil
        if (isset($input['company_name'])) {
            $sanitized['company_name'] = sanitize_text_field($input['company_name']);
        }
        
        if (isset($input['company_industry'])) {
            $sanitized['company_industry'] = sanitize_text_field($input['company_industry']);
        }
        
        if (isset($input['company_description'])) {
            $sanitized['company_description'] = sanitize_textarea_field($input['company_description']);
        }
        
        // Produkte und Dienstleistungen
        if (isset($input['company_products'])) {
            $sanitized['company_products'] = sanitize_textarea_field($input['company_products']);
        }
        
        if (isset($input['company_usps'])) {
            $sanitized['company_usps'] = sanitize_textarea_field($input['company_usps']);
        }
        
        // Zielgruppe
        if (isset($input['target_audience'])) {
            $sanitized['target_audience'] = sanitize_textarea_field($input['target_audience']);
        }
        
        if (isset($input['age_group']) && is_array($input['age_group'])) {
            $sanitized['age_group'] = array_map('sanitize_text_field', $input['age_group']);
        }
        
        // Fachwissen und Expertise
        if (isset($input['expertise_areas'])) {
            $sanitized['expertise_areas'] = sanitize_textarea_field($input['expertise_areas']);
        }
        
        // Wichtige Keywords
        if (isset($input['seo_keywords'])) {
            $sanitized['seo_keywords'] = sanitize_textarea_field($input['seo_keywords']);
        }
        
        return $sanitized;
    }

    /**
     * Rendert die Profilseite.
     *
     * @return void
     */
    public static function render_page(): void {
        // Template für die Profilseite laden
        require_once plugin_dir_path(dirname(__DIR__)) . 'templates/admin/profile.php';
    }

    /**
     * Lädt JavaScript und CSS-Dateien für die Profile-Seite.
     *
     * @param string $hook_suffix Der aktuelle Admin-Hook
     * @return void
     */
    public static function enqueue_assets($hook_suffix): void {
        // Debug: Zeige immer welcher Hook aufgerufen wird
        error_log('Athena AI Debug: Hook called: ' . $hook_suffix);
        
        // TEMPORÄR: Lade Scripts auf allen Admin-Seiten für Debug
        // if ($hook_suffix !== 'athena-feed-items_page_athena-ai-profiles') {
        //     error_log('Athena AI Debug: Hook does not match, expected: athena-feed-items_page_athena-ai-profiles');
        //     return;
        // }
        
        error_log('Athena AI Debug: Enqueuing scripts for profile page');

        // AJAX Handler wird bereits im AjaxHandler Constructor registriert
        // add_action('wp_ajax_athena_ai_prompt', ['\AthenaAI\Admin\AjaxHandler', 'handle_prompt_request']);

        // Enqueue Prompt Manager
        wp_enqueue_script(
            'athena-ai-prompt-manager',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/PromptManager.js',
            [],
            ATHENA_AI_VERSION,
            true
        );

        // AI Modal JavaScript
        wp_enqueue_script(
            'athena-ai-ai-modal',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/AIModal.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );

        // Modal Base JavaScript
        wp_enqueue_script(
            'athena-ai-modal',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/Modal.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );

        // Profile modals JavaScript
        wp_enqueue_script(
            'athena-ai-profile-modals',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/profile-modals.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );

        // Profile AJAX JavaScript
        wp_enqueue_script(
            'athena-ai-profile-ajax',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/profile-ajax.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );

        // Profile Form JavaScript
        wp_enqueue_script(
            'athena-ai-profile-form',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/ProfileForm.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );

        // Products Modal JavaScript
        wp_enqueue_script(
            'athena-ai-products-modal',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/ProductsModal.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );

        // Company Description Modal JavaScript
        wp_enqueue_script(
            'athena-ai-company-description-modal',
            ATHENA_AI_PLUGIN_URL . 'assets/js/admin/profile/CompanyDescriptionModal.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );

        // Localize script with AJAX URL and nonce
        wp_localize_script(
            'athena-ai-profile-ajax',
            'athenaAiAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('athena_ai_ajax_nonce'),
                'i18n' => [
                    'error' => __('An error occurred', 'athena-ai'),
                    'saving' => __('Saving...', 'athena-ai'),
                    'saved' => __('Saved!', 'athena-ai'),
                ]
            ]
        );

        // Prompt-Konfiguration laden
        self::enqueue_prompt_config();
    }

    /**
     * Prompt-Konfiguration ins Frontend laden
     *
     * @return void
     */
    private static function enqueue_prompt_config(): void {
        $prompt_manager = \AthenaAI\Core\PromptManager::get_instance();
        
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
    }
}
