<?php
/**
 * Bootstrap-Klasse für das Athena AI Plugin.
 *
 * @package AthenaAI\Core
 */

namespace AthenaAI\Core;

use AthenaAI\Admin\AdminBootstrap;
use AthenaAI\Services\CronScheduler;

/**
 * Zentrale Bootstrap-Klasse, die das Plugin initialisiert.
 *
 * Diese Klasse ist verantwortlich für:
 * - Registrierung von Aktivierungs-/Deaktivierungshooks
 * - Laden der Textdomain
 * - Initialisierung der Core-Komponenten
 * - Unterscheidung zwischen Admin und Frontend
 */
class Bootstrap {
    /**
     * Plugin initialisieren.
     *
     * @return void
     */
    public static function init(): void {
        // Plugin-Aktivierungs- und Deaktivierungshooks registrieren
        \register_activation_hook(ATHENA_AI_PLUGIN_FILE, [self::class, 'activate']);
        \register_deactivation_hook(ATHENA_AI_PLUGIN_FILE, [self::class, 'deactivate']);
        
        // Text-Domain laden - Wichtig: Nach init hook ausführen
        \add_action('init', [self::class, 'load_textdomain'], 10);
        
        // Plugin-Komponenten initialisieren
        \add_action('plugins_loaded', [self::class, 'load_components']);
    }
    
    /**
     * Bei Plugin-Aktivierung ausgeführter Code.
     *
     * @return void
     */
    public static function activate(): void {
        // Plugin-Instanz erstellen und Capabilities einrichten
        $plugin = new Plugin();
        $plugin->setup_capabilities();
        
        // Rewrite-Rules aktualisieren
        \flush_rewrite_rules();
    }
    
    /**
     * Bei Plugin-Deaktivierung ausgeführter Code.
     *
     * @return void
     */
    public static function deactivate(): void {
        // Cron-Jobs entfernen
        CronScheduler::clear_feed_fetch();
        
        // Rewrite-Rules aktualisieren
        \flush_rewrite_rules();
    }
    
    /**
     * Text-Domain für Übersetzungen laden.
     *
     * @return void
     */
    public static function load_textdomain(): void {
        // Keine Prüfung auf init mehr notwendig, da wir bereits im init hook sind
        // durch den add_action('init', ...) Aufruf
        \load_plugin_textdomain(
            'athena-ai',
            false,
            \dirname(\plugin_basename(ATHENA_AI_PLUGIN_FILE)) . '/languages'
        );
    }
    
    /**
     * Plugin-Komponenten laden.
     *
     * @return void
     */
    public static function load_components(): void {
        // Hauptplugin-Klasse initialisieren
        $plugin = new Plugin();
        $plugin->init();
        
        // Services initialisieren
        self::init_services();
        
        // Admin-Komponenten nur im Admin-Bereich initialisieren
        if (\is_admin()) {
            AdminBootstrap::init();
        } else {
            // Frontend-spezifischen Code hier initialisieren
            // self::init_frontend();
        }
    }
    
    /**
     * Plugin-Services initialisieren.
     *
     * @return void
     */
    private static function init_services(): void {
        // GitHub-Updater initialisieren
        $updater = new UpdateChecker(
            'dz-ecommerce',  // GitHub-Benutzername/Organisation
            'athena-ai',     // Repository-Name
            null             // Kein Token für öffentliche Repositories nötig
        );
        $updater->init();
    }
}
