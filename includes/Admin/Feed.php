<?php
namespace AthenaAI\Admin;

class Feed extends BaseAdmin {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomy']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_athena-feed', [$this, 'save_meta_boxes'], 10, 2);
        add_filter('map_meta_cap', [$this, 'map_feed_capabilities'], 10, 4);
        add_filter('parent_file', [$this, 'set_current_menu']);
    }

    /**
     * Register the feed category taxonomy
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
     * Register the feed post type
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
            'supports'            => ['title', 'editor'],
            'has_archive'         => false,
            'rewrite'            => false,
            'show_in_rest'       => true,
            'register_meta_box_cb' => [$this, 'add_meta_boxes'],
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
            'athena-feed-url',
            __('Feed URL', 'athena-ai'),
            [$this, 'render_url_meta_box'],
            'athena-feed',
            'normal',
            'high'
        );
    }

    /**
     * Render URL meta box
     */
    public function render_url_meta_box($post) {
        wp_nonce_field('athena_feed_url', 'athena_feed_url_nonce');
        $feed_url = get_post_meta($post->ID, '_feed_url', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="feed_url"><?php esc_html_e('Feed URL', 'athena-ai'); ?></label>
                </th>
                <td>
                    <input type="url"
                           id="feed_url"
                           name="feed_url"
                           value="<?php echo esc_url($feed_url); ?>"
                           class="regular-text"
                           required>
                    <p class="description">
                        <?php esc_html_e('The URL of the RSS/Atom feed', 'athena-ai'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id, $post) {
        // Verify URL nonce
        if (!isset($_POST['athena_feed_url_nonce']) || 
            !wp_verify_nonce($_POST['athena_feed_url_nonce'], 'athena_feed_url')) {
            return;
        }

        // Save feed URL
        if (isset($_POST['feed_url'])) {
            update_post_meta($post_id, '_feed_url', sanitize_url($_POST['feed_url']));
        }
    }
} 