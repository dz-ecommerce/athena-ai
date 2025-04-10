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
        add_filter('parent_file', [$this, 'set_current_menu']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
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
            'menu_name'            => _x('Feeds', 'Admin Menu text', 'athena-ai'),
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
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'show_in_admin_bar'  => true,
            'capability_type'    => ['athena_feed', 'athena_feeds'],
            'capabilities'       => [
                'edit_post'          => 'manage_options',
                'read_post'          => 'manage_options',
                'delete_post'        => 'manage_options',
                'edit_posts'         => 'manage_options',
                'edit_others_posts'  => 'manage_options',
                'publish_posts'      => 'manage_options',
                'read_private_posts' => 'manage_options',
                'create_posts'       => 'manage_options',
            ],
            'map_meta_cap'      => false,
            'hierarchical'       => false,
            'supports'          => ['title'],
            'has_archive'       => false,
            'rewrite'          => false,
            'show_in_rest'     => true,
            'taxonomies'       => ['athena-feed-category'],
        ];

        register_post_type('athena-feed', $args);
    }

    /**
     * Map feed capabilities to WordPress core capabilities
     */
    public function map_feed_capabilities($caps, $cap, $user_id, $args) {
        // Map all feed capabilities to manage_options
        if (!user_can($user_id, 'manage_options')) {
            return ['do_not_allow'];
        }

        return ['manage_options'];
    }

    /**
     * Add meta boxes to the feed edit screen
     */
    public function add_meta_boxes() {
        add_meta_box(
            'athena-feed-settings',
            __('Feed Settings', 'athena-ai'),
            [$this, 'render_meta_box'],
            'athena-feed',
            'normal',
            'high'
        );
    }

    /**
     * Render the feed settings meta box
     */
    public function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('athena_feed_meta_box', 'athena_feed_meta_box_nonce');

        // Get the current feed URL
        $feed_url = get_post_meta($post->ID, '_athena_feed_url', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="athena_feed_url"><?php _e('Feed URL', 'athena-ai'); ?></label>
                </th>
                <td>
                    <input type="url" 
                           id="athena_feed_url" 
                           name="athena_feed_url" 
                           value="<?php echo esc_attr($feed_url); ?>" 
                           class="regular-text"
                           required />
                    <p class="description">
                        <?php _e('Enter the RSS feed URL', 'athena-ai'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save meta box data
     */
    public function save_meta_box_data($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['athena_feed_meta_box_nonce'])) {
            return;
        }

        // Verify that the nonce is valid
        if (!wp_verify_nonce($_POST['athena_feed_meta_box_nonce'], 'athena_feed_meta_box')) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (!current_user_can('edit_athena_feed', $post_id)) {
            return;
        }

        // Save the feed URL
        if (isset($_POST['athena_feed_url'])) {
            update_post_meta(
                $post_id,
                '_athena_feed_url',
                sanitize_url($_POST['athena_feed_url'])
            );
        }
    }

    /**
     * Register admin menu
     */
    public function register_admin_menu() {
        // Add main menu
        add_menu_page(
            __('Athena AI', 'athena-ai'),
            __('Athena AI', 'athena-ai'),
            'manage_options',
            'edit.php?post_type=athena-feed',
            null,
            'dashicons-rss',
            30
        );

        // Add "All Feeds" as first submenu
        add_submenu_page(
            'edit.php?post_type=athena-feed',
            __('All Feeds', 'athena-ai'),
            __('All Feeds', 'athena-ai'),
            'edit_athena_feeds',
            'edit.php?post_type=athena-feed'
        );

        // Add "Add New" submenu
        add_submenu_page(
            'edit.php?post_type=athena-feed',
            __('Add New Feed', 'athena-ai'),
            __('Add New', 'athena-ai'),
            'edit_athena_feeds',
            'post-new.php?post_type=athena-feed'
        );

        // Add categories submenu
        add_submenu_page(
            'edit.php?post_type=athena-feed',
            __('Feed Categories', 'athena-ai'),
            __('Categories', 'athena-ai'),
            'manage_options',
            'edit-tags.php?taxonomy=athena-feed-category&post_type=athena-feed'
        );
    }
}