<?php
declare(strict_types=1);

namespace AthenaAI\Admin;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class FeedItemsPage {
    private const MENU_SLUG = 'athena-feed-items';
    private const CAPABILITY = 'manage_options';

    public static function init(): void {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_styles']);
    }

    public static function register_menu(): void {
        add_menu_page(
            __('Feed Items', 'athena-ai'),
            __('Feed Items', 'athena-ai'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [self::class, 'render_page'],
            'dashicons-rss',
            56
        );
    }

    public static function enqueue_styles(string $hook): void {
        if (strpos($hook, self::MENU_SLUG) === false) {
            return;
        }

        wp_enqueue_style(
            'athena-feed-items',
            plugins_url('assets/css/feed-items.css', ATHENA_AI_PLUGIN_FILE),
            [],
            ATHENA_VERSION
        );
    }

    public static function render_page(): void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Feed Items', 'athena-ai'); ?></h1>
            <div class="stats-box"></div>
            <div class="stats-box"></div>
            <div class="stats-box"></div>
            <!-- Add your table or content here -->
        </div>
        <?php
    }
}
