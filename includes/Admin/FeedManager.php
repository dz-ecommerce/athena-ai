<?php
namespace AthenaAI\Admin;

class FeedManager extends BaseAdmin {
    /**
     * Initialize the feed manager
     */
    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomy']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_athena-feed', [$this, 'save_meta_box_data']);
        add_filter('map_meta_cap', [$this, 'map_feed_capabilities'], 10, 4);
        add_filter('parent_file', [$this, 'set_current_menu']);
        add_action('init', [$this, 'proxy_external_image_init']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('wp_ajax_athena_proxy_image', [$this, 'proxy_external_image']);
    }

    /**
     * Set current menu for add/edit pages
     */
    public function set_current_menu($parent_file) {
        global $current_screen, $submenu_file;
        
        if ($current_screen && 'athena-feed' === $current_screen->post_type) {
            if ('edit-tags.php' === $current_screen->base && 'athena-feed-category' === $current_screen->taxonomy) {
                $submenu_file = 'edit-tags.php?taxonomy=athena-feed-category&post_type=athena-feed';
            }
            $parent_file = 'edit.php?post_type=athena-feed';
        }
        
        return $parent_file;
    }

    /**
     * Register the feed categories taxonomy
     */
    public function register_taxonomy() {
        $labels = [
            'name'              => _x('Feed Categories', 'taxonomy general name', 'athena-ai'),
            'singular_name'     => _x('Feed Category', 'taxonomy singular name', 'athena-ai'),
            'search_items'      => __('Search Feed Categories', 'athena-ai'),
            'all_items'         => __('All Feed Categories', 'athena-ai'),
            'parent_item'       => __('Parent Feed Category', 'athena-ai'),
            'parent_item_colon' => __('Parent Feed Category:', 'athena-ai'),
            'edit_item'         => __('Edit Feed Category', 'athena-ai'),
            'update_item'       => __('Update Feed Category', 'athena-ai'),
            'add_new_item'      => __('Add New Feed Category', 'athena-ai'),
            'new_item_name'     => __('New Feed Category Name', 'athena-ai'),
            'menu_name'         => __('Categories', 'athena-ai'),
        ];

        $args = [
            'hierarchical'      => true,
            'labels'           => $labels,
            'show_ui'          => true,
            'show_admin_column' => true,
            'query_var'        => true,
            'rewrite'          => false,
            'show_in_rest'     => true,
            'capabilities'     => [
                'manage_terms' => 'manage_athena_ai',
                'edit_terms'   => 'manage_athena_ai',
                'delete_terms' => 'manage_athena_ai',
                'assign_terms' => 'edit_athena_feeds',
            ],
        ];

        register_taxonomy('athena-feed-category', ['athena-feed'], $args);
    }

    /**
     * Register the custom post type
     */
    public function register_post_type() {
        $labels = [
            'name'                  => _x('Feeds', 'Post type general name', 'athena-ai'),
            'singular_name'         => _x('Feed', 'Post type singular name', 'athena-ai'),
            'menu_name'            => _x('Athena AI', 'Admin Menu text', 'athena-ai'),
            'name_admin_bar'       => _x('Feed', 'Add New on Toolbar', 'athena-ai'),
            'add_new'              => __('Add New', 'athena-ai'),
            'add_new_item'         => __('Add New Feed', 'athena-ai'),
            'new_item'             => __('New Feed', 'athena-ai'),
            'edit_item'            => __('Edit Feed', 'athena-ai'),
            'view_item'            => __('View Feed', 'athena-ai'),
            'all_items'            => __('All Feeds', 'athena-ai'),
            'search_items'         => __('Search Feeds', 'athena-ai'),
            'not_found'            => __('No feeds found.', 'athena-ai'),
            'not_found_in_trash'   => __('No feeds found in Trash.', 'athena-ai'),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 30,
            'menu_icon'           => 'dashicons-rss',
            'capability_type'     => 'post',
            'capabilities'        => [
                'edit_post'          => 'edit_athena_feed',
                'read_post'          => 'read_athena_feed',
                'delete_post'        => 'delete_athena_feed',
                'edit_posts'         => 'edit_athena_feeds',
                'edit_others_posts'  => 'edit_others_athena_feeds',
                'publish_posts'      => 'publish_athena_feeds',
                'read_private_posts' => 'read_private_athena_feeds',
                'create_posts'       => 'edit_athena_feeds',
            ],
            'map_meta_cap'        => true,
            'hierarchical'        => false,
            'supports'            => ['title'],
            'has_archive'         => false,
            'rewrite'            => false,
            'show_in_rest'       => true,
            'taxonomies'          => ['athena-feed-category'],
        ];

        register_post_type('athena-feed', $args);
    }

    /**
     * Map feed capabilities to WordPress core capabilities
     */
    public function map_feed_capabilities($caps, $cap, $user_id, $args) {
        if (!in_array($cap, [
            'edit_athena_feed',
            'read_athena_feed',
            'delete_athena_feed',
            'edit_athena_feeds',
            'edit_others_athena_feeds',
            'publish_athena_feeds',
            'read_private_athena_feeds'
        ])) {
            return $caps;
        }

        // Map all feed capabilities to manage_options
        if (!user_can($user_id, 'manage_options')) {
            return ['do_not_allow'];
        }

        return ['manage_options'];
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'athena_feed_url',
            __('Feed URL', 'athena-ai'),
            [$this, 'render_url_meta_box'],
            'athena-feed',
            'normal',
            'high'
        );
    }

    /**
     * Render the URL meta box
     *
     * @param \WP_Post $post
     */
    public function render_url_meta_box($post) {
        wp_nonce_field('athena_feed_url', 'athena_feed_url_nonce');
        $url = get_post_meta($post->ID, '_athena_feed_url', true);
        ?>
        <p>
            <label for="athena_feed_url"><?php echo esc_html($this->__('Feed URL:', 'athena-ai')); ?></label>
            <input type="url" 
                   id="athena_feed_url" 
                   name="athena_feed_url" 
                   value="<?php echo esc_url($url); ?>" 
                   class="widefat"
                   required>
        </p>
        <?php
    }

    /**
     * Save meta box data
     *
     * @param int $post_id
     */
    public function save_meta_box_data($post_id) {
        if (!isset($_POST['athena_feed_url_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['athena_feed_url_nonce'], 'athena_feed_url')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['athena_feed_url'])) {
            update_post_meta(
                $post_id,
                '_athena_feed_url',
                sanitize_url($_POST['athena_feed_url'])
            );
        }
    }

    /**
     * Initialize proxy function for handling external images
     */
    public function proxy_external_image_init() {
    }

    /**
     * Proxy function for handling external images
     */
    public function proxy_external_image() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        if (!isset($_GET['url']) || empty($_GET['url'])) {
            wp_die('No image URL provided');
        }

        $image_url = urldecode($_GET['url']);
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            wp_die('Invalid image URL');
        }
        
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'athena_proxy_image')) {
            wp_die('Invalid nonce');
        }

        // Get image content
        $response = wp_remote_get($image_url);
        
        if (is_wp_error($response)) {
            wp_die('Error fetching image');
        }

        $content_type = wp_remote_retrieve_header($response, 'content-type');
        
        // Verify it's an image
        if (!str_starts_with($content_type, 'image/')) {
            wp_die('Invalid image type');
        }

        // Output image with proper headers
        header('Content-Type: ' . $content_type);
        echo wp_remote_retrieve_body($response);
        exit;
    }

    /**
     * Get proxy URL for an image
     * 
     * @param string $image_url Original image URL
     * @return string Proxied image URL
     */
    public function get_proxy_url($image_url) {
        if (empty($image_url)) {
            return '';
        }

        $nonce = wp_create_nonce('athena_proxy_image');
        return admin_url('admin-ajax.php') . '?' . http_build_query([
            'action' => 'athena_proxy_image',
            'url' => $image_url,
            'nonce' => $nonce
        ]);
    }

    /**
     * Register admin menu
     */
    public function register_admin_menu() {
        add_menu_page(
            __('Athena AI', 'athena-ai'),
            __('Athena AI', 'athena-ai'),
            'manage_options',
            'edit.php?post_type=athena-feed',
            null,
            'dashicons-rss',
            30
        );
    }
}