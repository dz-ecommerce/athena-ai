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

        // Einstellungsgruppen und Felder hinzufügen
        add_settings_section(
            'athena_ai_profile_section',
            __('Profile Configuration', 'athena-ai'),
            [self::class, 'profile_section_callback'],
            'athena_ai_profile_settings'
        );

        // Beispielfeld für Profileinstellungen
        add_settings_field(
            'athena_ai_default_profile',
            __('Default Profile', 'athena-ai'),
            [self::class, 'default_profile_callback'],
            'athena_ai_profile_settings',
            'athena_ai_profile_section'
        );
    }

    /**
     * Callback für die Profilsektion.
     *
     * @return void
     */
    public static function profile_section_callback(): void {
        echo '<p>' . esc_html__('Configure your Athena AI profiles here.', 'athena-ai') . '</p>';
    }

    /**
     * Callback für das Default-Profile-Feld.
     *
     * @return void
     */
    public static function default_profile_callback(): void {
        $options = get_option('athena_ai_profiles', ['default_profile' => 'default']);
        $default_profile = $options['default_profile'] ?? 'default';
        
        echo '<select id="athena_ai_default_profile" name="athena_ai_profiles[default_profile]">';
        echo '<option value="default"' . selected($default_profile, 'default', false) . '>' . esc_html__('Default', 'athena-ai') . '</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('Select the default profile for new feeds.', 'athena-ai') . '</p>';
    }

    /**
     * Sanitiert die Profileinstellungen.
     *
     * @param array $input Die Eingabewerte.
     * @return array Die sanitierten Werte.
     */
    public static function sanitize_profile_settings(array $input): array {
        $sanitized = [];
        
        if (isset($input['default_profile'])) {
            $sanitized['default_profile'] = sanitize_text_field($input['default_profile']);
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
