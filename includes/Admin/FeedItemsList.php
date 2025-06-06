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

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ri.*, p.post_title as feed_title, pm.meta_value as feed_url, 
                JSON_UNQUOTE(JSON_EXTRACT(ri.raw_content, '$.title')) as title
                FROM {$wpdb->prefix}feed_raw_items ri
                JOIN {$wpdb->posts} p ON ri.feed_id = p.ID
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_athena_feed_url'
                WHERE p.post_type = 'athena-feed' AND p.post_status = 'publish'
                ORDER BY {$orderby} {$order}
                LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        return $items ?: [];
    }

    private function get_total_items(): int {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}feed_raw_items");
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

        // Create an instance of our list table
        $list_table = new self();
        $list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="feed-items-stats">
                <?php self::render_stats(); ?>
            </div>

            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr(
                    isset($_REQUEST['page']) && is_string($_REQUEST['page'])
                        ? $_REQUEST['page']
                        : ''
                ); ?>" />
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
