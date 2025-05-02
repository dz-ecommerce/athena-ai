<?php
/**
 * Bootstrap-Klasse für das Athena AI Plugin.
 *
 * @package AthenaAI\Core
 */

namespace AthenaAI\Core;

use AthenaAI\Admin\AdminBootstrap;
use AthenaAI\Services\CronScheduler;
use AthenaAI\Core\I18n;
use AthenaAI\Helpers\StringHelper;

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

        // Internationalisierung initialisieren
        I18n::init();

        // Plugin-Komponenten initialisieren
        \add_action('plugins_loaded', [self::class, 'load_components']);

        // CLI-Kommandos initialisieren, wenn WP-CLI verfügbar ist
        if (defined('WP_CLI') && WP_CLI) {
            self::init_cli_commands();
        }
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

    // Die load_textdomain Methode wurde in die Hauptdatei verschoben, um Probleme mit
    // "Translation loading triggered too early" zu vermeiden

    /**
     * Plugin-Komponenten laden.
     *
     * @return void
     */
    public static function load_components(): void {
        // PHP-Funktionen mit sicheren Versionen überschreiben
        self::init_php_function_wrappers();

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
     * Initialisiert die Wrappers für PHP-Funktionen, um Deprecated-Warnungen zu vermeiden.
     *
     * @return void
     */
    private static function init_php_function_wrappers(): void {
        // Fehlerhandler für die spezifischen Deprecated-Warnungen registrieren
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            // Nur bestimmte Deprecated-Warnungen abfangen
            if (
                $errno === E_DEPRECATED &&
                strpos($errfile, 'wp-includes/functions.php') !== false &&
                ((strpos($errstr, 'strpos()') !== false && $errline === 7360) ||
                    (strpos($errstr, 'str_replace()') !== false && $errline === 2195))
            ) {
                return true; // Diese Warnung unterdrücken
            }
            // Andere Fehler normal behandeln lassen
            return false;
        }, E_DEPRECATED);

        // Globale Funktionen definieren, die die problematischen Funktionen überschreiben
        if (!function_exists('wp_safe_strpos')) {
            function wp_safe_strpos($haystack, $needle, $offset = 0) {
                return StringHelper::safe_strpos($haystack, $needle, $offset);
            }
        }

        if (!function_exists('wp_safe_str_replace')) {
            function wp_safe_str_replace($search, $replace, $subject, &$count = null) {
                return StringHelper::safe_str_replace($search, $replace, $subject, $count);
            }
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
            'dz-ecommerce', // GitHub-Benutzername/Organisation
            'athena-ai', // Repository-Name
            null // Kein Token für öffentliche Repositories nötig
        );
        $updater->init();

        // Feed Cron Manager initialisieren
        if (class_exists('\\AthenaAI\\Cron\\FeedCronManager')) {
            $feed_cron_manager = \AthenaAI\Cron\FeedCronManager::create();
            $feed_cron_manager->init();
        }
    }

    /**
     * Initialisiert WP-CLI Kommandos.
     *
     * @return void
     */
    private static function init_cli_commands(): void {
        // Feed CLI-Kommando registrieren
        if (class_exists('\\AthenaAI\\Cli\\FeedCommand')) {
            new \AthenaAI\Cli\FeedCommand();
            \WP_CLI::add_command('athena feed', '\\AthenaAI\\Cli\\FeedCommand');
        }
    }
}
