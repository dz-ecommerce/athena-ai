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
        
        // Unternehmenswerte
        if (isset($input['company_values'])) {
            $sanitized['company_values'] = sanitize_textarea_field($input['company_values']);
        }
        
        // Fachwissen und Expertise
        if (isset($input['expertise_areas'])) {
            $sanitized['expertise_areas'] = sanitize_textarea_field($input['expertise_areas']);
        }
        
        if (isset($input['certifications'])) {
            $sanitized['certifications'] = sanitize_textarea_field($input['certifications']);
        }
        
        // Wichtige Keywords
        if (isset($input['seo_keywords'])) {
            $sanitized['seo_keywords'] = sanitize_textarea_field($input['seo_keywords']);
        }
        
        // Zusätzliche Informationen
        if (isset($input['avoided_topics'])) {
            $sanitized['avoided_topics'] = sanitize_textarea_field($input['avoided_topics']);
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
}
