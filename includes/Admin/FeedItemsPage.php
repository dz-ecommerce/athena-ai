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
                'fetchErrorText' => __('Error fetching feeds. Please try again.', 'athena-ai'),
                'noFeedsText' => __('No active feeds found to process.', 'athena-ai'),
                'pendingText' => __('Pending', 'athena-ai'),
                'processingText' => __('Processing...', 'athena-ai'),
                'completedText' => __('Processed %d feeds with %d errors.', 'athena-ai'),
                'refreshingText' => __('Processing complete. Refreshing page...', 'athena-ai'),
                'summaryText' => __('Feed processing complete: %d feeds processed (%d with errors). %d new items added, %d items skipped.', 'athena-ai')
            ]
        );
    }

    public static function render_page(): void {
        // Check if tables exist
        $tables_exist = \AthenaAI\Database\DatabaseSetup::tables_exist();
        
        $last_fetch_time = get_option('athena_last_feed_fetch', 0);
        $next_scheduled = wp_next_scheduled('athena_process_feeds');
        
        // Initialize variables
        $feed_items = [];
        $feed_count = 0;
        $item_count = 0;
        
        if ($tables_exist) {
            // Check if we need to create a sample feed for testing
            self::maybe_create_sample_feed();
            
            // Get feed items to display
            global $wpdb;
            $feed_items = $wpdb->get_results(
                "SELECT ri.*, fm.url as feed_url 
                FROM {$wpdb->prefix}feed_raw_items ri 
                JOIN {$wpdb->prefix}feed_metadata fm ON ri.feed_id = fm.feed_id 
                ORDER BY ri.pub_date DESC 
                LIMIT 20"
            );
            
            // Get feed count
            $feed_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}feed_metadata WHERE active = 1");
            $item_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items");
        }
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
            
            <!-- Feed stats -->
            <div class="feed-stats-container">
                <div class="stats-box">
                    <h3><?php esc_html_e('Active Feeds', 'athena-ai'); ?></h3>
                    <div class="stats-number"><?php echo intval($feed_count); ?></div>
                </div>
                
                <div class="stats-box">
                    <h3><?php esc_html_e('Total Items', 'athena-ai'); ?></h3>
                    <div class="stats-number"><?php echo intval($item_count); ?></div>
                </div>
            </div>
            
            <!-- Active Feeds List -->
            <div class="active-feeds-container">
                <h2><?php esc_html_e('Active Feeds', 'athena-ai'); ?></h2>
                
                <?php if ($tables_exist): ?>
                    <?php 
                    // Get active feeds with their post titles
                    $active_feeds = [];
                    if ($feed_count > 0) {
                        $active_feeds = $wpdb->get_results(
                            "SELECT fm.feed_id, fm.url, fm.last_checked, 
                            (SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE feed_id = fm.feed_id) as item_count 
                            FROM {$wpdb->prefix}feed_metadata fm 
                            WHERE fm.active = 1",
                            ARRAY_A
                        );
                        
                        // Get post titles for each feed
                        foreach ($active_feeds as &$feed) {
                            $post_id = $wpdb->get_var($wpdb->prepare(
                                "SELECT post_id FROM {$wpdb->postmeta} 
                                WHERE meta_key = '_athena_feed_metadata_id' AND meta_value = %d",
                                $feed['feed_id']
                            ));
                            
                            if ($post_id) {
                                $feed['title'] = get_the_title($post_id);
                            } else {
                                $feed['title'] = __('Unknown Feed', 'athena-ai');
                            }
                        }
                    }
                    ?>
                    
                    <?php if (empty($active_feeds)): ?>
                        <div class="notice notice-info">
                            <p><?php esc_html_e('No active feeds found. Please add feeds through the Feeds menu.', 'athena-ai'); ?></p>
                        </div>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped active-feeds-table">
                            <thead>
                                <tr>
                                    <th class="column-title"><?php esc_html_e('Feed Name', 'athena-ai'); ?></th>
                                    <th class="column-url"><?php esc_html_e('URL', 'athena-ai'); ?></th>
                                    <th class="column-last-checked"><?php esc_html_e('Last Checked', 'athena-ai'); ?></th>
                                    <th class="column-items"><?php esc_html_e('Items', 'athena-ai'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_feeds as $feed): ?>
                                    <tr>
                                        <td class="column-title">
                                            <?php echo esc_html($feed['title']); ?>
                                        </td>
                                        <td class="column-url">
                                            <a href="<?php echo esc_url($feed['url']); ?>" target="_blank">
                                                <?php echo esc_html($feed['url']); ?>
                                            </a>
                                        </td>
                                        <td class="column-last-checked">
                                            <?php if ($feed['last_checked']): ?>
                                                <?php echo esc_html(human_time_diff(strtotime($feed['last_checked']), time()) . ' ' . __('ago', 'athena-ai')); ?>
                                                <br>
                                                <small><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($feed['last_checked']))); ?></small>
                                            <?php else: ?>
                                                <?php esc_html_e('Never', 'athena-ai'); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="column-items">
                                            <?php echo intval($feed['item_count']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="notice notice-warning">
                        <p><?php esc_html_e('Database tables are being set up. Please refresh the page in a few moments.', 'athena-ai'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Live feed processing container -->
            <div id="feed-processing-container" class="feed-processing-container" style="display: none;">
                <h2><?php esc_html_e('Processing Feeds', 'athena-ai'); ?></h2>
                
                <div class="feed-progress">
                    <div class="feed-progress-bar" id="feed-progress-bar" style="width: 0%;"></div>
                </div>
                
                <div class="feed-progress-stats">
                    <span id="feeds-processed">0</span> / <span id="feeds-total">0</span> <?php esc_html_e('feeds processed', 'athena-ai'); ?>
                </div>
                
                <table class="wp-list-table widefat fixed striped feed-processing-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Feed URL', 'athena-ai'); ?></th>
                            <th><?php esc_html_e('Last Checked', 'athena-ai'); ?></th>
                            <th><?php esc_html_e('Items', 'athena-ai'); ?></th>
                            <th><?php esc_html_e('Status', 'athena-ai'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="feed-processing-list">
                        <!-- Feed items will be added here dynamically -->
                    </tbody>
                </table>
            </div>
            
            <!-- Feed items list -->
            <div class="feed-items-container">
                <h2><?php esc_html_e('Recent Feed Items', 'athena-ai'); ?></h2>
                
                <?php if (!$tables_exist): ?>
                    <div class="notice notice-warning">
                        <p><?php esc_html_e('Database tables are being set up. Please refresh the page in a few moments.', 'athena-ai'); ?></p>
                    </div>
                <?php elseif (empty($feed_items)): ?>
                    <div class="notice notice-info">
                        <p><?php esc_html_e('No feed items found. Click the "Fetch Feeds Now" button to retrieve feed items.', 'athena-ai'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped feed-items-table">
                        <thead>
                            <tr>
                                <th class="column-title"><?php esc_html_e('Title', 'athena-ai'); ?></th>
                                <th class="column-feed_url"><?php esc_html_e('Feed', 'athena-ai'); ?></th>
                                <th class="column-pub_date"><?php esc_html_e('Published', 'athena-ai'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feed_items as $item): 
                                $content = json_decode($item->raw_content, true);
                                $title = isset($content['title']) ? $content['title'] : (isset($content->title) ? $content->title : __('Untitled', 'athena-ai'));
                                if (is_object($title) || is_array($title)) {
                                    $title = __('Untitled', 'athena-ai');
                                }
                            ?>
                                <tr>
                                    <td class="column-title">
                                        <?php echo esc_html($title); ?>
                                    </td>
                                    <td class="column-feed_url">
                                        <?php echo esc_html(parse_url($item->feed_url, PHP_URL_HOST)); ?>
                                    </td>
                                    <td class="column-pub_date">
                                        <?php echo esc_html(human_time_diff(strtotime($item->pub_date), time()) . ' ' . __('ago', 'athena-ai')); ?>
                                        <br>
                                        <small><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($item->pub_date))); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Sync feeds from custom post types to the feed metadata table
     * and clean up any feeds that aren't from custom post types
     */
    private static function maybe_create_sample_feed(): void {
        // First, clean up any existing feeds that aren't from custom post types
        self::clean_up_feeds();
        
        // Then sync feeds from custom post types
        self::sync_feeds_from_post_types();
    }
    
    /**
     * Clean up any feeds that aren't from custom post types
     */
    private static function clean_up_feeds(): void {
        global $wpdb;
        
        // Get all feed IDs from post meta
        $feed_post_ids = get_posts([
            'post_type' => 'athena-feed',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
        
        $feed_meta_ids = [];
        foreach ($feed_post_ids as $post_id) {
            $feed_id = get_post_meta($post_id, '_athena_feed_metadata_id', true);
            if ($feed_id) {
                $feed_meta_ids[] = $feed_id;
            }
        }
        
        if (empty($feed_meta_ids)) {
            // If no feeds from custom post types, delete all feeds from the metadata table
            $wpdb->query("DELETE FROM {$wpdb->prefix}feed_metadata");
            return;
        }
        
        // Delete any feeds that aren't from custom post types
        $feed_ids_string = implode(',', array_map('intval', $feed_meta_ids));
        $wpdb->query("DELETE FROM {$wpdb->prefix}feed_metadata WHERE feed_id NOT IN ($feed_ids_string)");
    }
    
    /**
     * Sync feeds from custom post types to the feed metadata table
     */
    private static function sync_feeds_from_post_types(): void {
        global $wpdb;
        
        // Get all published feed post types
        $feed_posts = get_posts([
            'post_type' => 'athena-feed',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        if (empty($feed_posts)) {
            return;
        }
        
        foreach ($feed_posts as $post) {
            // Get the feed URL from post meta
            $feed_url = get_post_meta($post->ID, '_athena_feed_url', true);
            
            if (empty($feed_url)) {
                continue;
            }
            
            // Check if this feed URL already exists in the feed_metadata table
            $existing_feed = $wpdb->get_var($wpdb->prepare(
                "SELECT feed_id FROM {$wpdb->prefix}feed_metadata WHERE url = %s",
                $feed_url
            ));
            
            if (!$existing_feed) {
                // Create a new feed in the feed_metadata table
                $feed = new \AthenaAI\Models\Feed(
                    $feed_url,
                    3600, // Default update interval
                    true  // Active by default
                );
                $feed->save();
                
                // Store the feed_id in post meta for future reference
                $feed_id = $feed->get_id();
                if ($feed_id !== null) {
                    update_post_meta($post->ID, '_athena_feed_metadata_id', $feed_id);
                }
            } else {
                // Store the feed_id in post meta for future reference
                update_post_meta($post->ID, '_athena_feed_metadata_id', $existing_feed);
            }
        }
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
        
        // Check if tables exist
        if (!\AthenaAI\Database\DatabaseSetup::tables_exist()) {
            // Try to create tables
            \AthenaAI\Database\DatabaseSetup::setup_tables();
            
            // Check again if tables exist
            if (!\AthenaAI\Database\DatabaseSetup::tables_exist()) {
                wp_send_json_error(['message' => __('Database tables could not be created. Please contact the administrator.', 'athena-ai')]);
                return;
            }
        }
        
        // Get the feed ID from the request if we're processing a single feed
        $feed_id = isset($_POST['feed_id']) ? intval($_POST['feed_id']) : 0;
        $action = isset($_POST['fetch_action']) ? sanitize_text_field($_POST['fetch_action']) : 'start';
        
        global $wpdb;
        
        // If we're starting the process, get all active feeds and return their info
        if ($action === 'start') {
            $feeds = $wpdb->get_results(
                "SELECT fm.feed_id, fm.url, fm.last_checked, 
                (SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE feed_id = fm.feed_id) as item_count 
                FROM {$wpdb->prefix}feed_metadata fm 
                WHERE fm.active = 1",
                ARRAY_A
            );
            
            if (empty($feeds)) {
                wp_send_json_error([
                    'message' => __('No feeds found. Please add feeds through the Feeds menu before fetching.', 'athena-ai')
                ]);
                return;
            }
            
            $feed_data = [];
            foreach ($feeds as $feed) {
                $feed_data[] = [
                    'id' => (int)$feed['feed_id'],
                    'url' => $feed['url'],
                    'last_checked' => $feed['last_checked'] ? human_time_diff(strtotime($feed['last_checked']), time()) . ' ' . __('ago', 'athena-ai') : __('Never', 'athena-ai'),
                    'item_count' => (int)$feed['item_count'],
                    'status' => 'pending'
                ];
            }
            
            wp_send_json_success([
                'feeds' => $feed_data,
                'total' => count($feed_data)
            ]);
            return;
        }
        
        // Process a single feed
        if ($action === 'process' && $feed_id > 0) {
            $feed = \AthenaAI\Models\Feed::get_by_id($feed_id);
            $result = [
                'id' => $feed_id,
                'processed' => false,
                'message' => '',
                'items' => 0,
                'error' => ''
            ];
            
            if (!$feed) {
                $result['error'] = __('Feed not found', 'athena-ai');
            } else {
                try {
                    // Get item count before fetching
                    $before_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE feed_id = %d",
                        $feed_id
                    ));
                    
                    // Fetch the feed
                    $success = $feed->fetch();
                    
                    // Get item count after fetching
                    $after_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items WHERE feed_id = %d",
                        $feed_id
                    ));
                    
                    $new_items = $after_count - $before_count;
                    
                    if ($success) {
                        $result['processed'] = true;
                        $result['items'] = $new_items;
                        $result['message'] = sprintf(
                            __('Fetched %d new items', 'athena-ai'),
                            $new_items
                        );
                    } else {
                        // Check if there were any errors logged
                        $error = $wpdb->get_row($wpdb->prepare(
                            "SELECT error_code, error_message FROM {$wpdb->prefix}feed_errors 
                            WHERE feed_id = %d ORDER BY created_at DESC LIMIT 1",
                            $feed_id
                        ));
                        
                        $result['error'] = $error ? 
                            sprintf(__('Error: %s - %s', 'athena-ai'), $error->error_code, $error->error_message) : 
                            __('Unknown error occurred', 'athena-ai');
                    }
                } catch (\Exception $e) {
                    $result['error'] = $e->getMessage();
                }
            }
            
            wp_send_json_success($result);
            return;
        }
        
        // If we're completing the process, update the last fetch time and return summary stats
        if ($action === 'complete') {
            // Get the processed count and error count from the request
            $processed_count = isset($_POST['processed_count']) ? intval($_POST['processed_count']) : 0;
            $error_count = isset($_POST['error_count']) ? intval($_POST['error_count']) : 0;
            
            // Get the total count of items before and after processing
            $before_count = get_option('athena_feed_items_count', 0);
            $current_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items");
            
            // Calculate new and skipped items
            $new_items_count = max(0, $current_count - $before_count);
            $skipped_items_count = $processed_count > 0 ? ($processed_count - $new_items_count) : 0;
            
            // Update the last fetch time and item count
            update_option('athena_last_feed_fetch', time());
            update_option('athena_feed_items_count', $current_count);
            
            wp_send_json_success([
                'message' => __('Feed processing completed', 'athena-ai'),
                'lastFetchTime' => human_time_diff(time(), time()) . ' ' . __('ago', 'athena-ai'),
                'lastFetchTimeFormatted' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), time()),
                'nextFetchTime' => wp_next_scheduled('athena_process_feeds') ? 
                    human_time_diff(time(), wp_next_scheduled('athena_process_feeds')) . ' ' . __('from now', 'athena-ai') : 
                    __('Not scheduled', 'athena-ai'),
                'nextFetchTimeFormatted' => wp_next_scheduled('athena_process_feeds') ? 
                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), wp_next_scheduled('athena_process_feeds')) : 
                    '',
                'newItemsCount' => $new_items_count,
                'skippedItemsCount' => $skipped_items_count,
                'totalItemsCount' => $current_count
            ]);
            return;
        }
        
        wp_send_json_error(['message' => __('Invalid action', 'athena-ai')]);
    }
}
