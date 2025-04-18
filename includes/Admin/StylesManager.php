<?php
/**
 * Styles Manager
 * 
 * @package AthenaAI\Admin
 */

namespace AthenaAI\Admin;

/**
 * StylesManager class
 */
class StylesManager extends BaseAdmin {
    
    /**
     * Initialize the StylesManager
     */
    public function __construct() {
        // Lade die Tailwind CSS Styles im Admin-Bereich
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        
        // Füge Body-Klasse für Tailwind-Scope hinzu
        add_filter('admin_body_class', [$this, 'add_admin_body_class']);
    }
    
    /**
     * Enqueue Tailwind CSS Styles im Admin-Bereich
     * 
     * @param string $hook_suffix Der aktuelle Admin-Hook
     */
    public function enqueue_admin_styles($hook_suffix) {
        // Nur auf Athena AI Plugin-Seiten laden
        if (strpos($hook_suffix, 'athena-ai') !== false || 
            isset($_GET['post_type']) && $_GET['post_type'] === 'athena-feed') {
            
            // Tailwind CSS
            wp_enqueue_style(
                'athena-ai-tailwind',
                ATHENA_AI_PLUGIN_URL . 'assets/css/athena-admin.css',
                [],
                ATHENA_AI_VERSION
            );
            
            // Google Fonts
            wp_enqueue_style(
                'athena-ai-google-fonts',
                'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
                [],
                ATHENA_AI_VERSION
            );
            
            // Font Awesome für Icons
            wp_enqueue_style(
                'athena-ai-fontawesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                [],
                '6.4.0'
            );
        }
    }
    
    /**
     * Fügt die athena-ai-admin Klasse zum Body hinzu für Tailwind-Scoping
     * 
     * @param string $classes Bestehende Klassen
     * @return string Modifizierte Klassen
     */
    public function add_admin_body_class($classes) {
        global $hook_suffix;
        
        // Nur auf Athena AI Plugin-Seiten anwenden
        if (strpos($hook_suffix, 'athena-ai') !== false ||
            (isset($_GET['post_type']) && $_GET['post_type'] === 'athena-feed')) {
            $classes .= ' athena-ai-admin';
        }
        
        return $classes;
    }
}
