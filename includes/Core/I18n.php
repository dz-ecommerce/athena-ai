<?php
/**
 * Internationalisierungsklasse für das Athena AI Plugin.
 *
 * @package AthenaAI\Core
 */

namespace AthenaAI\Core;

/**
 * Verwaltet die Internationalisierung des Plugins.
 */
class I18n {
    /**
     * Domain des Plugins für Übersetzungen.
     *
     * @var string
     */
    private static $domain = 'athena-ai';

    /**
     * Initialisiert die Internationalisierung.
     *
     * @return void
     */
    public static function init(): void {
        // Textdomain auf dem admin_init Hook laden, um "Translation loading triggered too early" zu vermeiden
        \add_action('admin_init', [self::class, 'load_textdomain']);

        // Auch für Frontend-Anfragen auf dem wp Hook laden
        \add_action('wp', [self::class, 'load_textdomain']);
    }

    /**
     * Lädt die Textdomain des Plugins.
     *
     * @return void
     */
    public static function load_textdomain(): void {
        // Prüfen, ob die Textdomain bereits geladen wurde, um doppeltes Laden zu vermeiden
        if (!\is_textdomain_loaded(self::$domain)) {
            \load_plugin_textdomain(
                self::$domain,
                false,
                \dirname(\dirname(\dirname(\plugin_basename(__FILE__)))) . '/languages'
            );
        }
    }
}
