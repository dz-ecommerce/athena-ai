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
     * Feldkonfiguration für die Sanitization
     */
    private const FIELD_CONFIG = [
        'company_name' => 'text',
        'company_industry' => 'text', 
        'company_description' => 'textarea',
        'company_products' => 'textarea',
        'company_usps' => 'textarea',
        'target_audience' => 'textarea',
        'age_group' => 'array',
        'company_values' => 'textarea',
        'expertise_areas' => 'textarea',
        'certifications' => 'textarea',
        'seo_keywords' => 'textarea',
        'avoided_topics' => 'textarea',
        'customer_type' => 'text',
        'preferred_tone' => 'text',
        'tonality' => 'array'
    ];

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
     * Sanitiert die Profileinstellungen basierend auf der Feldkonfiguration.
     *
     * @param array $input Die Eingabewerte.
     * @return array Die sanitierten Werte.
     */
    public static function sanitize_profile_settings(array $input): array {
        $sanitized = [];
        
        foreach (self::FIELD_CONFIG as $field => $type) {
            if (!isset($input[$field])) continue;
            
            $sanitized[$field] = match($type) {
                'text' => sanitize_text_field($input[$field]),
                'textarea' => sanitize_textarea_field($input[$field]),
                'array' => is_array($input[$field]) ? array_map('sanitize_text_field', $input[$field]) : []
            };
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
     * Holt die Branchenkonfiguration (für Template-Nutzung).
     */
    public static function get_industry_groups(): array {
        return [
            'Dienstleistungen' => [
                'accounting' => 'Buchhaltung & Steuern',
                'advertising' => 'Werbung & Marketing',
                'consulting' => 'Unternehmensberatung',
                'financial' => 'Finanzdienstleistungen',
                'insurance' => 'Versicherungen',
                'legal' => 'Rechtsberatung',
                'real_estate' => 'Immobilien'
            ],
            'IT & Technologie' => [
                'it_services' => 'IT-Dienstleistungen',
                'software' => 'Softwareentwicklung',
                'web_design' => 'Webdesign & -entwicklung',
                'ecommerce' => 'E-Commerce',
                'telecommunications' => 'Telekommunikation',
                'data_analytics' => 'Datenanalyse',
                'cloud_computing' => 'Cloud Computing',
                'cybersecurity' => 'IT-Sicherheit'
            ],
            'Handel & Einzelhandel' => [
                'retail' => 'Einzelhandel',
                'wholesale' => 'Großhandel',
                'ecommerce_retail' => 'Online-Handel',
                'consumer_goods' => 'Konsumgüter',
                'food_retail' => 'Lebensmittelhandel',
                'fashion' => 'Mode & Bekleidung',
                'electronics_retail' => 'Elektronik',
                'furniture' => 'Möbel & Einrichtung'
            ],
            'Produktion & Fertigung' => [
                'manufacturing' => 'Fertigungsindustrie',
                'automotive' => 'Automobilindustrie',
                'aerospace' => 'Luft- und Raumfahrt',
                'electronics' => 'Elektronik & Elektrotechnik',
                'chemicals' => 'Chemische Industrie',
                'pharma' => 'Pharmazeutische Industrie',
                'machinery' => 'Maschinenbau',
                'textiles' => 'Textilindustrie'
            ],
            'Gesundheitswesen' => [
                'healthcare' => 'Gesundheitswesen',
                'medical_practice' => 'Arztpraxis',
                'hospital' => 'Krankenhaus',
                'biotech' => 'Biotechnologie',
                'medical_devices' => 'Medizintechnik',
                'pharmaceutical' => 'Pharmaindustrie',
                'healthcare_services' => 'Gesundheitsdienstleistungen',
                'eldercare' => 'Altenpflege'
            ],
            'Bildung & Forschung' => [
                'education' => 'Bildungseinrichtungen',
                'school' => 'Schulen',
                'university' => 'Hochschulen & Universitäten',
                'vocational_training' => 'Berufsbildung',
                'research' => 'Forschungseinrichtungen',
                'e_learning' => 'E-Learning & Online-Bildung'
            ],
            'Weitere Branchen' => [
                'agriculture' => 'Landwirtschaft',
                'architecture' => 'Architektur & Ingenieurwesen',
                'art' => 'Kunst & Design',
                'beauty' => 'Schönheit & Kosmetik',
                'construction' => 'Bauwesen',
                'energy' => 'Energie & Versorgung',
                'entertainment' => 'Unterhaltung & Freizeit',
                'food' => 'Gastronomie & Lebensmittel',
                'hospitality' => 'Hotellerie & Gastgewerbe',
                'media' => 'Medien & Kommunikation',
                'transport' => 'Transport & Logistik',
                'travel' => 'Tourismus & Reisen',
                'other' => 'Sonstige'
            ]
        ];
    }
}
