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
