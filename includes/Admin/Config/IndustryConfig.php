<?php
/**
 * Industry Configuration
 * 
 * Holds industry/branch configurations for select fields
 */

declare(strict_types=1);

namespace AthenaAI\Admin\Config;

if (!defined('ABSPATH')) {
    exit();
}

class IndustryConfig {
    
    /**
     * Get all industry groups and their options
     */
    public static function getIndustryGroups(): array {
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
    
    /**
     * Get age group options
     */
    public static function getAgeGroups(): array {
        return [
            '18-25' => '18-25',
            '26-35' => '26-35',
            '36-45' => '36-45',
            '46-60' => '46-60',
            '60+' => '60+'
        ];
    }
    
    /**
     * Get tonality options
     */
    public static function getTonalities(): array {
        return [
            'professional' => 'Professionell',
            'friendly' => 'Freundlich',
            'humorous' => 'Humorvoll',
            'informative' => 'Informativ',
            'authoritative' => 'Autoritativ'
        ];
    }
    
    /**
     * Get customer type options
     */
    public static function getCustomerTypes(): array {
        return [
            'b2b' => 'B2B',
            'b2c' => 'B2C',
            'both' => 'Beides'
        ];
    }
    
    /**
     * Get preferred tone options
     */
    public static function getPreferredTones(): array {
        return [
            'formal' => 'Formell "Sie"',
            'informal' => 'Informell "Du"'
        ];
    }
} 