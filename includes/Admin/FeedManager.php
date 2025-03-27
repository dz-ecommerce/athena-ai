<?php
namespace AthenaAI\Admin;

class FeedManager extends BaseAdmin {
    /**
     * Initialize the feed manager
     */
    public function init() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_athena-feed', [$this, 'save_meta_box_data']);
    }

    /**
     * Register the custom post type
     */
    public function register_post_type() {
        $labels = [
            'name'               => $this->__('Feeds', 'athena-ai'),
            'singular_name'      => $this->__('Feed', 'athena-ai'),
            'menu_name'          => $this->__('Feeds', 'athena-ai'),
            'add_new'            => $this->__('Add New', 'athena-ai'),
            'add_new_item'       => $this->__('Add New Feed', 'athena-ai'),
            'edit_item'          => $this->__('Edit Feed', 'athena-ai'),
            'new_item'           => $this->__('New Feed', 'athena-ai'),
            'view_item'          => $this->__('View Feed', 'athena-ai'),
            'search_items'       => $this->__('Search Feeds', 'athena-ai'),
            'not_found'          => $this->__('No feeds found', 'athena-ai'),
            'not_found_in_trash' => $this->__('No feeds found in Trash', 'athena-ai'),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'athena-ai',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => ['title'],
            'menu_position'       => 30,
            'rewrite'            => ['slug' => 'athena-feed'],
            'show_in_rest'       => true,
        ];

        register_post_type('athena-feed', $args);
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'athena_feed_url',
            $this->__('Feed URL', 'athena-ai'),
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
        $url = get_post_meta($post->ID, '_athena_feed_url', true);
        wp_nonce_field('athena_feed_url', 'athena_feed_url_nonce');
        ?>
        <p>
            <label for="athena_feed_url"><?php echo esc_html($this->__('Feed URL:', 'athena-ai')); ?></label>
            <input type="url" 
                   id="athena_feed_url" 
                   name="athena_feed_url" 
                   value="<?php echo esc_attr($url); ?>" 
                   class="widefat">
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
} 