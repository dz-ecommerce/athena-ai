<?php
namespace AthenaAI\Frontend;

/**
 * Handles the display of feeds on the frontend
 */
class FeedDisplay {
    /**
     * Initialize the feed display
     */
    public function __construct() {
        add_shortcode('athena_feeds', [$this, 'render_feeds_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_athena_load_more_feeds', [$this, 'ajax_load_more_feeds']);
        add_action('wp_ajax_nopriv_athena_load_more_feeds', [$this, 'ajax_load_more_feeds']);
        
        // Register the feeds page
        add_action('init', [$this, 'register_feeds_page']);
        
        // Add rewrite rules for the feeds page
        add_action('init', [$this, 'add_rewrite_rules']);
        
        // Add query var for category filtering
        add_filter('query_vars', [$this, 'add_query_vars']);
        
        // Handle template for feeds page
        add_filter('template_include', [$this, 'feeds_page_template']);
        
        // Create cache directory on plugin activation
        add_action('init', [$this, 'create_cache_directory']);
    }
    
    /**
     * Create cache directory for SimplePie
     */
    public function create_cache_directory() {
        $cache_dir = $this->get_cache_directory();
        
        // Create the directory if it doesn't exist
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        // Create an index.php file to prevent directory listing
        $index_file = $cache_dir . '/index.php';
        if (!file_exists($index_file)) {
            $file_handle = @fopen($index_file, 'w');
            if ($file_handle) {
                fwrite($file_handle, "<?php\n// Silence is golden.");
                fclose($file_handle);
            }
        }
        
        // Create .htaccess to protect the directory
        $htaccess_file = $cache_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $file_handle = @fopen($htaccess_file, 'w');
            if ($file_handle) {
                fwrite($file_handle, "Deny from all");
                fclose($file_handle);
            }
        }
        
        // Try to set permissions
        @chmod($cache_dir, 0755);
    }
    
    /**
     * Get the cache directory path
     *
     * @return string Cache directory path
     */
    private function get_cache_directory() {
        $upload_dir = wp_upload_dir();
        return trailingslashit($upload_dir['basedir']) . 'athena-ai-cache';
    }
    
    /**
     * Register the feeds page
     */
    public function register_feeds_page() {
        // Check if the page already exists
        $page = get_page_by_path('athena-feeds');
        
        if (!$page) {
            // Create the page
            wp_insert_post([
                'post_title'    => __('Feeds', 'athena-ai'),
                'post_name'     => 'athena-feeds',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_content'  => '<!-- wp:shortcode -->[athena_feeds]<!-- /wp:shortcode -->',
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            ]);
        }
    }
    
    /**
     * Add rewrite rules for the feeds page
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^athena-feeds/category/([^/]+)/?$',
            'index.php?pagename=athena-feeds&athena_feed_category=$matches[1]',
            'top'
        );
    }
    
    /**
     * Add custom query vars
     *
     * @param array $vars Existing query vars
     * @return array Modified query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'athena_feed_category';
        return $vars;
    }
    
    /**
     * Handle template for feeds page
     *
     * @param string $template Current template path
     * @return string Modified template path
     */
    public function feeds_page_template($template) {
        if (is_page('athena-feeds')) {
            $new_template = ATHENA_AI_PLUGIN_DIR . 'templates/feeds-page.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        
        return $template;
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'athena-ai-frontend',
            ATHENA_AI_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            ATHENA_AI_VERSION
        );

        wp_enqueue_script(
            'athena-ai-frontend',
            ATHENA_AI_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            ATHENA_AI_VERSION,
            true
        );

        wp_localize_script('athena-ai-frontend', 'athenaAiFrontend', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('athena-ai-frontend-nonce'),
        ]);
    }

    /**
     * Render feeds shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function render_feeds_shortcode($atts) {
        $atts = shortcode_atts([
            'category' => '',
            'limit' => 10,
        ], $atts, 'athena_feeds');

        $category_slug = sanitize_text_field($atts['category']);
        $limit = absint($atts['limit']);

        // Get feeds
        $feeds = $this->get_feeds($category_slug, $limit);
        
        // Start output buffering
        ob_start();
        
        // Display feeds
        $this->render_feeds($feeds, $category_slug, $limit);
        
        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Get feeds by category
     *
     * @param string $category_slug Category slug
     * @param int $limit Number of feeds to get
     * @param int $offset Offset for pagination
     * @return array Array of feed items
     */
    public function get_feeds($category_slug = '', $limit = 10, $offset = 0) {
        $args = [
            'post_type' => 'athena-feed',
            'posts_per_page' => $limit,
            'offset' => $offset,
            'post_status' => 'publish',
        ];

        // Add category filter if specified
        if (!empty($category_slug)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'athena-feed-category',
                    'field' => 'slug',
                    'terms' => $category_slug,
                ],
            ];
        }

        $query = new \WP_Query($args);
        $feeds = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $feed_id = get_the_ID();
                $feed_url = get_post_meta($feed_id, '_athena_feed_url', true);
                
                if (!empty($feed_url)) {
                    $feed_items = $this->fetch_feed($feed_url);
                    
                    if (!empty($feed_items)) {
                        $feeds[] = [
                            'id' => $feed_id,
                            'title' => get_the_title(),
                            'url' => $feed_url,
                            'items' => $feed_items,
                        ];
                    }
                }
            }
            wp_reset_postdata();
        }

        return $feeds;
    }

    /**
     * Fetch feed content from URL
     *
     * @param string $url Feed URL
     * @return array Array of feed items
     */
    private function fetch_feed($url) {
        // Use WordPress's built-in SimplePie for feed fetching
        if (!class_exists('SimplePie', false)) {
            require_once ABSPATH . WPINC . '/class-simplepie.php';
        }

        $feed = new \SimplePie();
        $feed->set_feed_url($url);
        $feed->set_cache_duration(3600); // Cache for 1 hour
        
        // Set custom cache location
        $cache_dir = $this->get_cache_directory();
        $feed->set_cache_location($cache_dir);
        
        $feed->init();
        $feed->handle_content_type();

        if ($feed->error()) {
            return [];
        }

        $items = [];
        $max_items = 10; // Limit number of items per feed
        
        foreach ($feed->get_items(0, $max_items) as $item) {
            $items[] = [
                'title' => $item->get_title(),
                'permalink' => $item->get_permalink(),
                'date' => $item->get_date('U'),
                'description' => $item->get_description(),
                'author' => $item->get_author() ? $item->get_author()->get_name() : '',
            ];
        }

        return $items;
    }

    /**
     * Render feeds HTML
     *
     * @param array $feeds Array of feeds
     * @param string $category_slug Category slug for load more functionality
     * @param int $limit Items per page
     * @param int $offset Current offset
     */
    public function render_feeds($feeds, $category_slug = '', $limit = 10, $offset = 0) {
        if (empty($feeds)) {
            echo '<div class="athena-feeds-container">';
            echo '<p>' . esc_html__('No feeds found.', 'athena-ai') . '</p>';
            echo '</div>';
            return;
        }
        
        echo '<div class="athena-feeds-container">';
        
        foreach ($feeds as $feed) {
            echo '<div class="athena-feed">';
            echo '<h3 class="athena-feed-title">' . esc_html($feed['title']) . '</h3>';
            
            if (!empty($feed['items'])) {
                echo '<ul class="athena-feed-items">';
                
                foreach ($feed['items'] as $item) {
                    echo '<li class="athena-feed-item">';
                    echo '<a href="' . esc_url($item['permalink']) . '" target="_blank" rel="noopener">';
                    echo esc_html($item['title']);
                    echo '</a>';
                    
                    if (!empty($item['date'])) {
                        echo '<span class="athena-feed-date">' . esc_html(date_i18n(get_option('date_format'), $item['date'])) . '</span>';
                    }
                    
                    // Stelle sicher, dass die Beschreibung ein String ist und nicht NULL
                    $description = isset($item['description']) && is_string($item['description']) ? $item['description'] : '';
                    echo '<div class="athena-feed-description">' . wp_kses_post(wp_trim_words($description, 30, '...')) . '</div>';
                    echo '</li>';
                }
                
                echo '</ul>';
            } else {
                echo '<p>' . esc_html__('No items found in this feed.', 'athena-ai') . '</p>';
            }
            
            echo '</div>';
        }
        
        // Add load more button
        $total_feeds = wp_count_posts('athena-feed')->publish;
        $current_displayed = count($feeds) + $offset;
        
        if ($current_displayed < $total_feeds) {
            echo '<div class="athena-feeds-load-more">';
            echo '<button class="button athena-load-more-button" 
                data-category="' . esc_attr($category_slug) . '" 
                data-limit="' . esc_attr($limit) . '" 
                data-offset="' . esc_attr($current_displayed) . '">';
            echo esc_html__('Load More', 'athena-ai');
            echo '</button>';
            echo '</div>';
        }
        
        echo '</div>';
    }

    /**
     * AJAX handler for loading more feeds
     */
    public function ajax_load_more_feeds() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'athena-ai-frontend-nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'athena-ai')]);
        }

        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 10;
        $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;

        $feeds = $this->get_feeds($category, $limit, $offset);
        
        ob_start();
        $this->render_feeds($feeds, $category, $limit, $offset);
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }
}
