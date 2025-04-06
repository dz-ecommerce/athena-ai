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
            'athena-feed-settings',
            __('Feed Settings', 'athena-ai'),
            [$this, 'render_settings_meta_box'],
            'athena-feed',
            'normal',
            'high'
        );

        add_meta_box(
            'athena-feed-ai-settings',
            __('AI Settings', 'athena-ai'),
            [$this, 'render_ai_settings_meta_box'],
            'athena-feed',
            'normal',
            'high'
        );
    }

    /**
     * Render settings meta box
     */
    public function render_settings_meta_box($post) {
        wp_nonce_field('athena_feed_settings', 'athena_feed_settings_nonce');
        
        $feed_url = get_post_meta($post->ID, '_feed_url', true);
        $feed_type = get_post_meta($post->ID, '_feed_type', true);
        $update_interval = get_post_meta($post->ID, '_update_interval', true) ?: 'hourly';
        
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
                           value="<?php echo esc_attr($feed_url); ?>" 
                           class="regular-text">
                    <p class="description">
                        <?php esc_html_e('Enter the URL of the RSS feed or webpage to monitor', 'athena-ai'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="feed_type"><?php esc_html_e('Feed Type', 'athena-ai'); ?></label>
                </th>
                <td>
                    <select id="feed_type" name="feed_type">
                        <option value="rss" <?php selected($feed_type, 'rss'); ?>>
                            <?php esc_html_e('RSS Feed', 'athena-ai'); ?>
                        </option>
                        <option value="webpage" <?php selected($feed_type, 'webpage'); ?>>
                            <?php esc_html_e('Webpage', 'athena-ai'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="update_interval"><?php esc_html_e('Update Interval', 'athena-ai'); ?></label>
                </th>
                <td>
                    <select id="update_interval" name="update_interval">
                        <option value="hourly" <?php selected($update_interval, 'hourly'); ?>>
                            <?php esc_html_e('Hourly', 'athena-ai'); ?>
                        </option>
                        <option value="twicedaily" <?php selected($update_interval, 'twicedaily'); ?>>
                            <?php esc_html_e('Twice Daily', 'athena-ai'); ?>
                        </option>
                        <option value="daily" <?php selected($update_interval, 'daily'); ?>>
                            <?php esc_html_e('Daily', 'athena-ai'); ?>
                        </option>
                        <option value="weekly" <?php selected($update_interval, 'weekly'); ?>>
                            <?php esc_html_e('Weekly', 'athena-ai'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render AI settings meta box
     */
    public function render_ai_settings_meta_box($post) {
        wp_nonce_field('athena_feed_ai_settings', 'athena_feed_ai_settings_nonce');
        
        $ai_provider = get_post_meta($post->ID, '_ai_provider', true) ?: 'openai';
        $summarize = get_post_meta($post->ID, '_summarize', true) ?: 'yes';
        $max_tokens = get_post_meta($post->ID, '_max_tokens', true) ?: '150';
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="ai_provider"><?php esc_html_e('AI Provider', 'athena-ai'); ?></label>
                </th>
                <td>
                    <select id="ai_provider" name="ai_provider">
                        <option value="openai" <?php selected($ai_provider, 'openai'); ?>>
                            <?php esc_html_e('OpenAI', 'athena-ai'); ?>
                        </option>
                        <option value="anthropic" <?php selected($ai_provider, 'anthropic'); ?>>
                            <?php esc_html_e('Anthropic Claude', 'athena-ai'); ?>
                        </option>
                        <option value="google" <?php selected($ai_provider, 'google'); ?>>
                            <?php esc_html_e('Google Gemini', 'athena-ai'); ?>
                        </option>
                        <option value="mistral" <?php selected($ai_provider, 'mistral'); ?>>
                            <?php esc_html_e('Mistral AI', 'athena-ai'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Select which AI provider to use for processing this feed', 'athena-ai'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="summarize"><?php esc_html_e('Auto-Summarize', 'athena-ai'); ?></label>
                </th>
                <td>
                    <select id="summarize" name="summarize">
                        <option value="yes" <?php selected($summarize, 'yes'); ?>>
                            <?php esc_html_e('Yes', 'athena-ai'); ?>
                        </option>
                        <option value="no" <?php selected($summarize, 'no'); ?>>
                            <?php esc_html_e('No', 'athena-ai'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Automatically generate summaries for new feed items', 'athena-ai'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="max_tokens"><?php esc_html_e('Max Summary Length', 'athena-ai'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="max_tokens" 
                           name="max_tokens" 
                           value="<?php echo esc_attr($max_tokens); ?>" 
                           class="small-text"
                           min="50"
                           max="500"
                           step="10">
                    <p class="description">
                        <?php esc_html_e('Maximum length of generated summaries (in tokens)', 'athena-ai'); ?>
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
        // Verify settings nonce
        if (!isset($_POST['athena_feed_settings_nonce']) || 
            !wp_verify_nonce($_POST['athena_feed_settings_nonce'], 'athena_feed_settings')) {
            return;
        }

        // Verify AI settings nonce
        if (!isset($_POST['athena_feed_ai_settings_nonce']) || 
            !wp_verify_nonce($_POST['athena_feed_ai_settings_nonce'], 'athena_feed_ai_settings')) {
            return;
        }

        // Save settings
        if (isset($_POST['feed_url'])) {
            update_post_meta($post_id, '_feed_url', sanitize_url($_POST['feed_url']));
        }
        if (isset($_POST['feed_type'])) {
            update_post_meta($post_id, '_feed_type', sanitize_text_field($_POST['feed_type']));
        }
        if (isset($_POST['update_interval'])) {
            update_post_meta($post_id, '_update_interval', sanitize_text_field($_POST['update_interval']));
        }

        // Save AI settings
        if (isset($_POST['ai_provider'])) {
            update_post_meta($post_id, '_ai_provider', sanitize_text_field($_POST['ai_provider']));
        }
        if (isset($_POST['summarize'])) {
            update_post_meta($post_id, '_summarize', sanitize_text_field($_POST['summarize']));
        }
        if (isset($_POST['max_tokens'])) {
            update_post_meta($post_id, '_max_tokens', absint($_POST['max_tokens']));
        }
    }

    /**
     * Render the feed page
     */
    public function render_page() {
        $items = $this->get_feed_items();
        
        $this->render_template('feed', [
            'title' => $this->__('Athena AI Feed', 'athena-ai'),
            'items' => $items,
            'nonce_field' => $this->get_nonce_field('athena_ai_feed'),
        ]);
    }

    /**
     * Render the feeds list page
     */
    public function render_feeds_page() {
        $feeds = $this->get_feeds();
        
        $this->render_template('feeds', [
            'title' => $this->__('All Feeds', 'athena-ai'),
            'feeds' => $feeds,
            'nonce_field' => $this->get_nonce_field('athena_ai_feeds'),
        ]);
    }

    /**
     * Render the add new feed page
     */
    public function render_add_feed_page() {
        $this->render_template('add-feed', [
            'title' => $this->__('Add New Feed', 'athena-ai'),
            'nonce_field' => $this->get_nonce_field('athena_ai_add_feed'),
        ]);
    }

    /**
     * Get feed items
     *
     * @return array
     */
    private function get_feed_items() {
        // This is a placeholder. In a real implementation, you would fetch actual feed items
        return [
            [
                'id' => 1,
                'title' => $this->__('Sample Feed Item', 'athena-ai'),
                'content' => $this->__('This is a sample feed item.', 'athena-ai'),
                'date' => current_time('mysql'),
            ],
        ];
    }

    /**
     * Get all feeds
     *
     * @return array
     */
    private function get_feeds() {
        $args = [
            'post_type' => 'athena-feed',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        $feeds = get_posts($args);
        return array_map(function($feed) {
            return [
                'id' => $feed->ID,
                'title' => $feed->post_title,
                'url' => get_post_meta($feed->ID, '_feed_url', true),
                'last_updated' => get_post_meta($feed->ID, '_last_updated', true),
            ];
        }, $feeds);
    }
} 