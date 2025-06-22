<?php
/**
 * Admin-Bootstrap-Klasse für das Athena AI Plugin.
 *
 * @package AthenaAI\Admin
 */

namespace AthenaAI\Admin;

use AthenaAI\Admin\DatabaseUpgrade;
use AthenaAI\Admin\ProfilePage;

/**
 * Initialisiert alle Admin-spezifischen Komponenten.
 */
class AdminBootstrap {
    /**
     * Admin-Komponenten initialisieren.
     *
     * @return void
     */
    public static function init(): void {
        // Admin-Menüs registrieren - mit früher Priorität
        \add_action('admin_menu', [self::class, 'register_admin_menus'], 9);

        // Admin-Klassen initialisieren
        self::init_admin_classes();

        // Admin-Assets
        \add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
    }

    /**
     * Admin-Menüs registrieren.
     *
     * @return void
     */
    public static function register_admin_menus(): void {
        // Feed-Items-Seite als Hauptmenü
        // Use the Athena logo SVG as the menu icon
        $svg_icon = \file_get_contents(
            \plugin_dir_path(dirname(__DIR__)) . 'assets/img/athena-logo.svg'
        );
        $svg_base64 = 'data:image/svg+xml;base64,' . \base64_encode($svg_icon);

        \add_menu_page(
            \__('Feed Items', 'athena-ai'),
            \__('Feed Items', 'athena-ai'),
            'manage_options',
            'athena-feed-items',
            [FeedItemsPage::class, 'render_page'],
            $svg_base64,
            31
        );

        // New AI Athena Post als Untermenü
        \add_submenu_page(
            'athena-feed-items',
            \__('New AI Athena Post', 'athena-ai'),
            \__('New AI Athena Post', 'athena-ai'),
            'manage_options',
            'athena-new-ai-post',
            [self::class, 'render_new_ai_post_page']
        );

        // Maintenance-Seite als Untermenü
        \add_submenu_page(
            'athena-feed-items',
            \__('Feed Maintenance', 'athena-ai'),
            \__('Maintenance', 'athena-ai'),
            'manage_options',
            'athena-feed-maintenance',
            [Maintenance::class, 'render_maintenance_page']
        );

        // Datenbank-Upgrade-Seite als Untermenü
        \add_submenu_page(
            'athena-feed-items',
            \__('Datenbank-Upgrade', 'athena-ai'),
            \__('Datenbank-Upgrade', 'athena-ai'),
            'manage_options',
            'athena-database-upgrade',
            [DatabaseUpgrade::class, 'render_upgrade_page']
        );
    }

    /**
     * Render the New AI Athena Post page
     */
    public static function render_new_ai_post_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'athena-ai'));
        }

        // Include the template for the new AI post page
        include_once ATHENA_AI_PLUGIN_DIR . 'templates/admin/new-ai-post.php';
    }

    /**
     * Admin-Klassen initialisieren.
     *
     * @return void
     */
    private static function init_admin_classes(): void {
        // Admin-Kernklassen initialisieren
        new Settings();
        new FeedManager();
        new StylesManager();
        new FeedItemsManager();

        // Feed-Cache-Einstellungen initialisieren
        if (class_exists('\\AthenaAI\\Admin\\FeedCacheSettings')) {
            FeedCacheSettings::create();
        }

        // Feed-Klassen initialisieren - diese sollten eventuell in Services verschoben werden
        FeedFetcher::init();
        Maintenance::init();

        // Debug-Seite initialisieren
        DebugPage::init();

        // Datenbank-Upgrade-Seite initialisieren
        DatabaseUpgrade::init();

        // Profilseite initialisieren
        ProfilePage::register();

        // AI Post Controller initialisieren
        \AthenaAI\Admin\Controllers\AIPostController::init();
    }

    /**
     * Admin-Assets laden.
     *
     * @param string $hook Der aktuelle Admin-Hook.
     * @return void
     */
    public static function enqueue_admin_assets($hook): void {
        // Diese Methode kann später aus Plugin::enqueue_admin_assets hierher verschoben werden
    }
}
