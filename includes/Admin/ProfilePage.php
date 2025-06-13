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

use AthenaAI\Admin\Core\FieldSanitizer;
use AthenaAI\Admin\Models\Profile;
use AthenaAI\Admin\Config\IndustryConfig;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * ProfilePage-Klasse zur Verwaltung von Athena AI Profilen.
 */
class ProfilePage {
    
    /**
     * @var Profile Profile model instance
     */
    private static $profile_model;

    /**
     * Registriert die Profilseite im Admin-Menü.
     */
    public static function register(): void {
        self::$profile_model = new Profile();
        
        add_action('admin_menu', [self::class, 'add_profile_page'], 20);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    /**
     * Fügt die Profilseite zum Admin-Menü hinzu.
     */
    public static function add_profile_page(): void {
        add_submenu_page(
            'edit.php?post_type=athena-feed',
            __('Athena AI Profiles', 'athena-ai'),
            __('Profiles', 'athena-ai'),
            'manage_athena_ai',
            'athena-ai-profiles',
            [self::class, 'render_page']
        );
    }

    /**
     * Registriert die Einstellungen für die Profilseite.
     */
    public static function register_settings(): void {
        register_setting(
            'athena_ai_profile_settings',
            'athena_ai_profiles', 
            ['sanitize_callback' => [self::class, 'sanitize_profile_settings']]
        );
    }
    
    /**
     * Sanitiert die Profileinstellungen mittels FieldSanitizer.
     */
    public static function sanitize_profile_settings(array $input): array {
        return FieldSanitizer::sanitize($input, Profile::getFieldTypes());
    }

    /**
     * Rendert die Profilseite.
     */
    public static function render_page(): void {
        require_once plugin_dir_path(dirname(__DIR__)) . 'templates/admin/profile.php';
    }
    
    /**
     * Holt die Branchenkonfiguration (für Template-Nutzung).
     */
    public static function get_industry_groups(): array {
        return IndustryConfig::getIndustryGroups();
    }
}
