<?php
declare(strict_types=1);

namespace AthenaAI\Admin;

if (!defined('ABSPATH')) {
    exit();
}

class FeedItemsList extends \WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => 'feed_item',
            'plural' => 'feed_items',
            'ajax' => false,
        ]);
    }

    public function prepare_items(): void {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $this->items = $this->get_feed_items($per_page, $offset);
        $total_items = $this->get_total_items();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns(), 'title'];
    }

    public function get_columns(): array {
        return [
            'title' => __('Title', 'athena-ai'),
            'categories' => __('Categories', 'athena-ai'),
            'feed_url' => __('Feed Source', 'athena-ai'),
            'pub_date' => __('Published', 'athena-ai'),
            'status' => __('Status', 'athena-ai'),
        ];
    }

    public function get_sortable_columns(): array {
        return [
            'pub_date' => ['pub_date', true],
            'feed_url' => ['feed_url', false],
        ];
    }

    private function get_feed_items(int $per_page, int $offset): array {
        global $wpdb;

        $orderby = isset($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'pub_date';
        $order = isset($_GET['order']) ? sanitize_key($_GET['order']) : 'DESC';

        if (!in_array($orderby, ['pub_date', 'feed_url'])) {
            $orderby = 'pub_date';
        }
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        // First check if feed_raw_items has id column
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}feed_raw_items");
        $has_id_column = false;
        foreach ($columns as $column) {
            if ($column->Field === 'id') {
                $has_id_column = true;
                break;
            }
        }

        // Build filter conditions
        $where_conditions = ["p.post_type = 'athena-feed' AND p.post_status = 'publish'"];
        $query_params = [];

        // Feed filter
        $feed_filter = isset($_GET['feed_id']) && !empty($_GET['feed_id']) ? intval($_GET['feed_id']) : 0;
        if ($feed_filter) {
            $where_conditions[] = 'ri.feed_id = %d';
            $query_params[] = $feed_filter;
        }

        // Date filter
        $date_filter = isset($_GET['date_filter']) && !empty($_GET['date_filter']) 
            ? sanitize_text_field($_GET['date_filter']) : '';
        if ($date_filter) {
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $week_start = date('Y-m-d', strtotime('this week'));
            $last_week_start = date('Y-m-d', strtotime('last week'));
            $last_week_end = date('Y-m-d', strtotime('last week +6 days'));
            $month_start = date('Y-m-01');
            $last_month_start = date('Y-m-01', strtotime('last month'));
            $last_month_end = date('Y-m-t', strtotime('last month'));

            switch ($date_filter) {
                case 'today':
                    $where_conditions[] = 'DATE(ri.pub_date) = %s';
                    $query_params[] = $today;
                    break;
                case 'yesterday':
                    $where_conditions[] = 'DATE(ri.pub_date) = %s';
                    $query_params[] = $yesterday;
                    break;
                case 'this_week':
                    $where_conditions[] = 'DATE(ri.pub_date) >= %s';
                    $query_params[] = $week_start;
                    break;
                case 'last_week':
                    $where_conditions[] = 'DATE(ri.pub_date) >= %s AND DATE(ri.pub_date) <= %s';
                    $query_params[] = $last_week_start;
                    $query_params[] = $last_week_end;
                    break;
                case 'this_month':
                    $where_conditions[] = 'DATE(ri.pub_date) >= %s';
                    $query_params[] = $month_start;
                    break;
                case 'last_month':
                    $where_conditions[] = 'DATE(ri.pub_date) >= %s AND DATE(ri.pub_date) <= %s';
                    $query_params[] = $last_month_start;
                    $query_params[] = $last_month_end;
                    break;
            }
        }

        // Search filter
        $search_term = isset($_GET['search_term']) && !empty($_GET['search_term']) 
            ? sanitize_text_field($_GET['search_term']) : '';
        if (!empty($search_term)) {
            $where_conditions[] = "(
                JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.title')) LIKE %s 
                OR JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.description')) LIKE %s
            )";
            $search_like = '%' . $wpdb->esc_like($search_term) . '%';
            $query_params[] = $search_like;
            $query_params[] = $search_like;
        }

        $where_clause = implode(' AND ', $where_conditions);

        if ($has_id_column) {
            // Use id column for JOIN with categories
            $sql = "SELECT ri.*, p.post_title as feed_title, pm.meta_value as feed_url, 
                    JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.title')) as title,
                    GROUP_CONCAT(DISTINCT fic.category SEPARATOR ', ') as categories
                    FROM {$wpdb->prefix}feed_raw_items ri
                    JOIN {$wpdb->posts} p ON ri.feed_id = p.ID
                    JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_athena_feed_url'
                    LEFT JOIN {$wpdb->prefix}feed_item_categories fic ON ri.id = fic.item_id
                    WHERE {$where_clause}
                    GROUP BY ri.id
                    ORDER BY ri.{$orderby} {$order}
                    LIMIT %d OFFSET %d";
        } else {
            // Fallback: No JOIN with categories table
            $sql = "SELECT ri.*, p.post_title as feed_title, pm.meta_value as feed_url, 
                    JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.title')) as title
                    FROM {$wpdb->prefix}feed_raw_items ri
                    JOIN {$wpdb->posts} p ON ri.feed_id = p.ID
                    JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_athena_feed_url'
                    WHERE {$where_clause}
                    ORDER BY ri.{$orderby} {$order}
                    LIMIT %d OFFSET %d";
        }

        // Add pagination parameters
        $query_params[] = $per_page;
        $query_params[] = $offset;

        $items = $wpdb->get_results($wpdb->prepare($sql, $query_params), ARRAY_A);

        return $items ?: [];
    }

    private function get_total_items(): int {
        global $wpdb;
        
        // Build same filter conditions as get_feed_items
        $where_conditions = ["p.post_type = 'athena-feed' AND p.post_status = 'publish'"];
        $query_params = [];

        // Feed filter
        $feed_filter = isset($_GET['feed_id']) && !empty($_GET['feed_id']) ? intval($_GET['feed_id']) : 0;
        if ($feed_filter) {
            $where_conditions[] = 'ri.feed_id = %d';
            $query_params[] = $feed_filter;
        }

        // Date filter
        $date_filter = isset($_GET['date_filter']) && !empty($_GET['date_filter']) 
            ? sanitize_text_field($_GET['date_filter']) : '';
        if ($date_filter) {
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $week_start = date('Y-m-d', strtotime('this week'));
            $last_week_start = date('Y-m-d', strtotime('last week'));
            $last_week_end = date('Y-m-d', strtotime('last week +6 days'));
            $month_start = date('Y-m-01');
            $last_month_start = date('Y-m-01', strtotime('last month'));
            $last_month_end = date('Y-m-t', strtotime('last month'));

            switch ($date_filter) {
                case 'today':
                    $where_conditions[] = 'DATE(ri.pub_date) = %s';
                    $query_params[] = $today;
                    break;
                case 'yesterday':
                    $where_conditions[] = 'DATE(ri.pub_date) = %s';
                    $query_params[] = $yesterday;
                    break;
                case 'this_week':
                    $where_conditions[] = 'DATE(ri.pub_date) >= %s';
                    $query_params[] = $week_start;
                    break;
                case 'last_week':
                    $where_conditions[] = 'DATE(ri.pub_date) >= %s AND DATE(ri.pub_date) <= %s';
                    $query_params[] = $last_week_start;
                    $query_params[] = $last_week_end;
                    break;
                case 'this_month':
                    $where_conditions[] = 'DATE(ri.pub_date) >= %s';
                    $query_params[] = $month_start;
                    break;
                case 'last_month':
                    $where_conditions[] = 'DATE(ri.pub_date) >= %s AND DATE(ri.pub_date) <= %s';
                    $query_params[] = $last_month_start;
                    $query_params[] = $last_month_end;
                    break;
            }
        }

        // Search filter
        $search_term = isset($_GET['search_term']) && !empty($_GET['search_term']) 
            ? sanitize_text_field($_GET['search_term']) : '';
        if (!empty($search_term)) {
            $where_conditions[] = "(
                JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.title')) LIKE %s 
                OR JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.description')) LIKE %s
            )";
            $search_like = '%' . $wpdb->esc_like($search_term) . '%';
            $query_params[] = $search_like;
            $query_params[] = $search_like;
        }

        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT COUNT(*) 
                FROM {$wpdb->prefix}feed_raw_items ri
                JOIN {$wpdb->posts} p ON ri.feed_id = p.ID
                WHERE {$where_clause}";

        if (empty($query_params)) {
            return (int) $wpdb->get_var($sql);
        } else {
            return (int) $wpdb->get_var($wpdb->prepare($sql, $query_params));
        }
    }

    public function column_default($item, $column_name): string {
        return esc_html($item[$column_name] ?? '');
    }

    public function column_title($item): string {
        $title = $item['title'] ?? __('(no title)', 'athena-ai');
        $actions = [
            'view' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                \AthenaAI\Core\SafetyWrapper::esc_url(
                    isset($item['raw_content']) && is_string($item['raw_content'])
                        ? json_decode($item['raw_content'])->link ?? '#'
                        : '#'
                ),
                __('View Original', 'athena-ai')
            ),
        ];

        return sprintf(
            '<strong>%1$s</strong> %2$s',
            esc_html($title ?? ''),
            $this->row_actions($actions)
        );
    }

    public function column_feed_url($item): string {
        return sprintf(
            '<a href="%1$s" target="_blank">%2$s</a>',
            \AthenaAI\Core\SafetyWrapper::esc_url(
                isset($item['feed_url']) && is_string($item['feed_url']) ? $item['feed_url'] : ''
            ),
            esc_html(
                isset($item['feed_url']) && is_string($item['feed_url'])
                    ? wp_parse_url($item['feed_url'], PHP_URL_HOST)
                    : ''
            )
        );
    }

    public function column_pub_date($item): string {
        $timestamp = strtotime($item['pub_date']);
        return sprintf(
            '<span title="%1$s">%2$s</span>',
            esc_attr(date('Y-m-d H:i:s', $timestamp ?: time())),
            esc_html(human_time_diff($timestamp ?: time()) . ' ' . __('ago', 'athena-ai'))
        );
    }

    public function column_categories($item): string {
        $categories = $item['categories'] ?? '';
        
        if (empty($categories)) {
            // Fallback: Try to extract categories from raw_content JSON
            if (isset($item['raw_content']) && is_string($item['raw_content'])) {
                $raw_data = json_decode($item['raw_content'], true);
                if (isset($raw_data['categories']) && is_array($raw_data['categories'])) {
                    $categories = implode(', ', $raw_data['categories']);
                }
            }
        }
        
        if (empty($categories)) {
            return '<span class="no-categories">' . esc_html__('No categories', 'athena-ai') . '</span>';
        }
        
        // Split categories and create spans for better styling
        $category_list = explode(', ', $categories);
        $category_spans = array_map(function($cat) {
            return '<span class="category-tag">' . esc_html(trim($cat)) . '</span>';
        }, $category_list);
        
        return implode(' ', $category_spans);
    }

    public function column_status($item): string {
        // Here we could add status indicators for processing state
        return '<span class="status-new">' . esc_html__('New', 'athena-ai') . '</span>';
    }

    protected function get_table_classes(): array {
        return ['widefat', 'fixed', 'striped', 'feed-items-table'];
    }

    public static function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get statistics
        global $wpdb;
        $feed_count = $wpdb->get_var(
            "SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'athena-feed' 
            AND post_status = 'publish'"
        );
        
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items");
        
        $last_fetch = get_option('athena_last_feed_fetch');
        $last_fetch_text = $last_fetch
            ? human_time_diff($last_fetch, time()) . ' ' . __('ago', 'athena-ai')
            : __('Never', 'athena-ai');

        // Get feeds for filter dropdown
        $feeds = get_posts([
            'post_type' => 'athena-feed',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        // Get filter values
        $feed_filter = isset($_GET['feed_id']) && !empty($_GET['feed_id']) ? intval($_GET['feed_id']) : 0;
        $date_filter = isset($_GET['date_filter']) && !empty($_GET['date_filter']) 
            ? sanitize_text_field($_GET['date_filter']) : '';
        $search_term = isset($_GET['search_term']) && !empty($_GET['search_term']) 
            ? sanitize_text_field($_GET['search_term']) : '';

        // Support for multiple feed selection
        $feed_filter_ids = isset($_GET['feed_ids']) && is_array($_GET['feed_ids'])
            ? array_map('intval', $_GET['feed_ids']) : [];
        $feed_filter_array = !empty($feed_filter_ids) ? $feed_filter_ids : ($feed_filter ? [$feed_filter] : []);

        // Create an instance of our list table
        $list_table = new self();
        $list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Feed Items', 'athena-ai'); ?></h1>
            
            <!-- Action Buttons -->
            <div class="page-title-action" style="margin-left: 10px;">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline;">
                    <?php wp_nonce_field('athena_fetch_feeds_nonce'); ?>
                    <input type="hidden" name="action" value="athena_fetch_feeds">
                    <button type="submit" class="button button-primary">
                        <i class="fa-solid fa-sync-alt"></i> <?php esc_html_e('Fetch Feeds Now', 'athena-ai'); ?>
                    </button>
                </form>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=athena-feed-maintenance')); ?>" class="button">
                    <i class="fa-solid fa-tools"></i> <?php esc_html_e('Debug Cron Health', 'athena-ai'); ?>
                </a>
                
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=athena-feed')); ?>" class="button button-secondary">
                    <i class="fa-solid fa-plus"></i> <?php esc_html_e('Add New Feed', 'athena-ai'); ?>
                </a>
            </div>
            
            <hr class="wp-header-end">

            <!-- Statistics Boxes -->
            <div class="feed-stats-container">
                <div class="stats-box">
                    <div class="stats-number"><?php echo esc_html(number_format_i18n($feed_count)); ?></div>
                    <div class="stats-label"><?php esc_html_e('Feed Sources', 'athena-ai'); ?></div>
                </div>
                <div class="stats-box">
                    <div class="stats-number"><?php echo esc_html(number_format_i18n($total_items)); ?></div>
                    <div class="stats-label"><?php esc_html_e('Total Items', 'athena-ai'); ?></div>
                </div>
                <div class="stats-box">
                    <div class="stats-number"><?php echo esc_html($last_fetch_text); ?></div>
                    <div class="stats-label"><?php esc_html_e('Last Fetch', 'athena-ai'); ?></div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get" id="feed-filter-form">
                        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page'] ?? ''); ?>" />
                        
                        <!-- Date Filter -->
                        <label for="date-filter" class="screen-reader-text"><?php esc_html_e('Filter by Date', 'athena-ai'); ?></label>
                        <select name="date_filter" id="date-filter">
                            <option value=""><?php esc_html_e('All Time', 'athena-ai'); ?></option>
                            <option value="today" <?php selected($date_filter, 'today'); ?>><?php esc_html_e('Today', 'athena-ai'); ?></option>
                            <option value="yesterday" <?php selected($date_filter, 'yesterday'); ?>><?php esc_html_e('Yesterday', 'athena-ai'); ?></option>
                            <option value="this_week" <?php selected($date_filter, 'this_week'); ?>><?php esc_html_e('This Week', 'athena-ai'); ?></option>
                            <option value="last_week" <?php selected($date_filter, 'last_week'); ?>><?php esc_html_e('Last Week', 'athena-ai'); ?></option>
                            <option value="this_month" <?php selected($date_filter, 'this_month'); ?>><?php esc_html_e('This Month', 'athena-ai'); ?></option>
                            <option value="last_month" <?php selected($date_filter, 'last_month'); ?>><?php esc_html_e('Last Month', 'athena-ai'); ?></option>
                        </select>

                        <!-- Search Field -->
                        <label for="post-search-input" class="screen-reader-text"><?php esc_html_e('Search Items', 'athena-ai'); ?></label>
                        <input type="search" id="post-search-input" name="search_term" value="<?php echo esc_attr($search_term); ?>" placeholder="<?php esc_attr_e('Search items...', 'athena-ai'); ?>" />

                        <!-- Feed Filter -->
                        <label for="feed-filter-select" class="screen-reader-text"><?php esc_html_e('Filter by Feed', 'athena-ai'); ?></label>
                        <select name="feed_id" id="feed-filter-select">
                            <option value=""><?php esc_html_e('All Feeds', 'athena-ai'); ?></option>
                            <?php foreach ($feeds as $feed): ?>
                                <option value="<?php echo esc_attr($feed->ID); ?>" <?php selected($feed_filter, $feed->ID); ?>>
                                    <?php echo esc_html($feed->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?php esc_attr_e('Apply Filters', 'athena-ai'); ?>" />
                        
                        <?php if ($date_filter || $search_term || $feed_filter): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=' . ($_REQUEST['page'] ?? ''))); ?>" class="button">
                                <?php esc_html_e('Clear Filters', 'athena-ai'); ?>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- List Table -->
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page'] ?? ''); ?>" />
                <?php if ($date_filter): ?>
                    <input type="hidden" name="date_filter" value="<?php echo esc_attr($date_filter); ?>" />
                <?php endif; ?>
                <?php if ($search_term): ?>
                    <input type="hidden" name="search_term" value="<?php echo esc_attr($search_term); ?>" />
                <?php endif; ?>
                <?php if ($feed_filter): ?>
                    <input type="hidden" name="feed_id" value="<?php echo esc_attr($feed_filter); ?>" />
                <?php endif; ?>
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
            <span class="stats-number"><?php echo esc_html(
                number_format_i18n($stats->total_items)
            ); ?></span>
            <span class="stats-label"><?php esc_html_e('Total Items', 'athena-ai'); ?></span>
        </div>
        <div class="stats-box">
            <span class="stats-number"><?php echo esc_html(
                number_format_i18n($stats->total_feeds)
            ); ?></span>
            <span class="stats-label"><?php esc_html_e('Active Feeds', 'athena-ai'); ?></span>
        </div>
        <div class="stats-box">
            <span class="stats-number"><?php echo esc_html(
                human_time_diff(strtotime($stats->latest_item))
            ); ?></span>
            <span class="stats-label"><?php esc_html_e('Since Last Item', 'athena-ai'); ?></span>
        </div>
        <?php
    }
}
