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
    private const NONCE_ACTION = 'athena_manual_feed_fetch';

    public static function init(): void {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_scripts']);
        add_action('wp_ajax_athena_manual_feed_fetch', [self::class, 'handle_manual_fetch']);
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

    public static function enqueue_scripts(string $hook): void {
        if (strpos($hook, self::MENU_SLUG) === false) {
            return;
        }

        wp_enqueue_style(
            'athena-feed-items',
            plugins_url('assets/css/feed-items.css', ATHENA_AI_PLUGIN_FILE),
            [],
            ATHENA_AI_VERSION
        );
        
        wp_enqueue_script(
            'athena-feed-items',
            plugins_url('assets/js/feed-items.js', ATHENA_AI_PLUGIN_FILE),
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );
        
        wp_localize_script(
            'athena-feed-items',
            'athenaFeedItems',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(self::NONCE_ACTION),
                'fetchingText' => __('Fetching feeds...', 'athena-ai'),
                'fetchSuccessText' => __('Feeds fetched successfully!', 'athena-ai'),
                'fetchErrorText' => __('Error fetching feeds. Please try again.', 'athena-ai')
            ]
        );
    }

    public static function render_page(): void {
        $last_fetch_time = get_option('athena_last_feed_fetch', 0);
        $next_scheduled = wp_next_scheduled('athena_process_feeds');
        ?>
        <div class="wrap athena-feed-items-page">
            <h1><?php esc_html_e('Feed Items', 'athena-ai'); ?></h1>
            
            <div class="feed-status-container">
                <div class="stats-box">
                    <h3><?php esc_html_e('Last Fetch', 'athena-ai'); ?></h3>
                    <p id="last-fetch-time">
                        <?php if ($last_fetch_time): ?>
                            <?php echo esc_html(human_time_diff($last_fetch_time, time()) . ' ' . __('ago', 'athena-ai')); ?>
                            <br>
                            <small><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_fetch_time)); ?></small>
                        <?php else: ?>
                            <?php esc_html_e('Never', 'athena-ai'); ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="stats-box">
                    <h3><?php esc_html_e('Next Scheduled Fetch', 'athena-ai'); ?></h3>
                    <p id="next-fetch-time">
                        <?php if ($next_scheduled): ?>
                            <?php echo esc_html(human_time_diff(time(), $next_scheduled) . ' ' . __('from now', 'athena-ai')); ?>
                            <br>
                            <small><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_scheduled)); ?></small>
                        <?php else: ?>
                            <?php esc_html_e('Not scheduled', 'athena-ai'); ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="stats-box action-box">
                    <h3><?php esc_html_e('Manual Fetch', 'athena-ai'); ?></h3>
                    <p>
                        <button id="manual-fetch-button" class="button button-primary">
                            <?php esc_html_e('Fetch Feeds Now', 'athena-ai'); ?>
                        </button>
                    </p>
                    <div id="fetch-status" class="fetch-status"></div>
                </div>
            </div>
            
            <div class="feed-items-container">
                <!-- Feed items will be displayed here -->
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle the manual feed fetch AJAX request
     */
    public static function handle_manual_fetch(): void {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'athena-ai')]);
            return;
        }
        
        // Process all active feeds
        global $wpdb;
        
        $feeds = $wpdb->get_results(
            "SELECT feed_id FROM {$wpdb->prefix}feed_metadata WHERE active = 1"
        );
        
        $processed = 0;
        $errors = 0;
        
        foreach ($feeds as $feed_data) {
            $feed = \AthenaAI\Models\Feed::get_by_id((int)$feed_data->feed_id);
            if ($feed && $feed->fetch()) {
                $processed++;
            } else {
                $errors++;
            }
        }
        
        // Update the last fetch time
        update_option('athena_last_feed_fetch', time());
        
        wp_send_json_success([
            'message' => sprintf(
                __('Processed %d feeds with %d errors.', 'athena-ai'),
                $processed,
                $errors
            ),
            'lastFetchTime' => human_time_diff(time(), time()) . ' ' . __('ago', 'athena-ai'),
            'lastFetchTimeFormatted' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), time()),
            'nextFetchTime' => wp_next_scheduled('athena_process_feeds') ? 
                human_time_diff(time(), wp_next_scheduled('athena_process_feeds')) . ' ' . __('from now', 'athena-ai') : 
                __('Not scheduled', 'athena-ai'),
            'nextFetchTimeFormatted' => wp_next_scheduled('athena_process_feeds') ? 
                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), wp_next_scheduled('athena_process_feeds')) : 
                ''
        ]);
    }
}
