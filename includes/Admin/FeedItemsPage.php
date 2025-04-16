<?php
declare(strict_types=1);

namespace AthenaAI\Admin;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class FeedItemsPage {
    private const MENU_SLUG = 'athena-feed-items';
    private const CAPABILITY = 'manage_options';

    public static function init(): void {
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_styles']);
    }

    public static function enqueue_styles(string $hook): void {
        if (!str_contains($hook, self::MENU_SLUG)) {
            return;
        }

        wp_enqueue_style(
            'athena-feed-items',
            plugins_url('assets/css/feed-items.css', ATHENA_PLUGIN_FILE),
            [],
            ATHENA_VERSION
        );
    }
}
    }

    public static function enqueue_styles(string $hook): void {
        if (!str_contains($hook, self::MENU_SLUG)) {
            return;
        }

        wp_enqueue_style(
            'athena-feed-items',
            plugins_url('assets/css/feed-items.css', ATHENA_PLUGIN_FILE),
            [],
            ATHENA_VERSION
        );
    }

    public static function render_page(): void {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Create an instance of our list table
        $list_table = new FeedItemsList();
        $list_table->prepare_items();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="feed-items-stats">
                <?php self::render_stats(); ?>
            </div>

            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page'] ?? ''); ?>" />
                <?php $list_table->display(); ?>
            </form>
        </div>
        <?php
    }

    private static function render_stats(): void {
        global $wpdb;

        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_items,
                COUNT(DISTINCT feed_id) as total_feeds,
                MAX(pub_date) as latest_item
            FROM {$wpdb->prefix}feed_raw_items"
        );

        ?>
        <div class="stats-box">
            <span class="stats-number"><?php echo esc_html(number_format_i18n($stats->total_items)); ?></span>
            <span class="stats-label"><?php esc_html_e('Total Items', 'athena-ai'); ?></span>
        </div>
        <div class="stats-box">
            <span class="stats-number"><?php echo esc_html(number_format_i18n($stats->total_feeds)); ?></span>
            <span class="stats-label"><?php esc_html_e('Active Feeds', 'athena-ai'); ?></span>
        </div>
        <div class="stats-box">
            <span class="stats-number"><?php echo esc_html(human_time_diff(strtotime($stats->latest_item))); ?></span>
            <span class="stats-label"><?php esc_html_e('Since Last Item', 'athena-ai'); ?></span>
        </div>
        <?php
    }
}
