<?php
declare(strict_types=1);

namespace AthenaAI\Admin;

use AthenaAI\Admin\FeedItemsList;
use AthenaAI\Admin\FeedItemsPage;

class FeedManager extends BaseAdmin {
    /**
     * Initialize the feed manager
     */
    public function __construct() {
        // Add hooks
        $this->add_hooks();
    }

    public function add_hooks() {
        // Register post type and taxonomy on init
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
            'labels'            => $labels,
            'public'            => false,
            'show_ui'           => true,
            'show_in_menu'      => true, // Aktiviere das Standard-WordPress-Menü
            'capability_type'   => 'athena_feed',
            'capabilities'      => [
                'publish_posts'     => 'edit_athena_feeds',
                'edit_posts'        => 'edit_athena_feeds',
                'edit_others_posts' => 'edit_athena_feeds',
                'delete_posts'      => 'edit_athena_feeds',
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
        if ('edit_athena_feed' === $cap || 'delete_athena_feed' === $cap) {
            $caps = ['edit_athena_feeds'];
        } elseif ('publish_athena_feeds' === $cap) {
            $caps = ['manage_options'];
        }
        
        return $caps;
    }

    /**
     * Add meta boxes to the feed edit screen
     */
    public function add_meta_boxes() {
        add_meta_box(
            'athena_feed_settings',
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
            // Verwende UrlHelper zur sicheren Verarbeitung der URL
            $feed_url = \AthenaAI\Helpers\UrlHelper::safe_esc_url_raw($_POST['athena_feed_url']);
            
            // Aktualisiere die Datenbank nur, wenn die URL nicht leer ist
            if ($feed_url !== '') {
                update_post_meta(
                    $post_id,
                    '_athena_feed_url',
                    $feed_url
                );
                
                // Update feed meta data
                $this->update_feed_meta($post_id, $feed_url);
            }
        }
    }
    
    /**
     * Update feed meta data when feed URL changes
     * 
     * @param int $post_id The post ID
     * @param string|null $feed_url The feed URL
     */
    private function update_feed_meta($post_id, ?string $feed_url) {
        // Set default values if they don't exist
        if (!get_post_meta($post_id, '_athena_feed_update_interval', true)) {
            update_post_meta($post_id, '_athena_feed_update_interval', 3600); // Default: 1 hour
        }
        
        if (!get_post_meta($post_id, '_athena_feed_active', true)) {
            update_post_meta($post_id, '_athena_feed_active', '1'); // Active by default
        }
    }

    /**
     * Register admin menu
     * 
     * Hinweis: Dieser Code wurde angepasst, um zu verhindern, dass Menüpunkte doppelt angezeigt werden
     */
    public function register_admin_menu() {
        // WordPress registriert den CPT-Menüpunkt automatisch, wenn wir 'menu_position' angeben
        // Wir müssen nur benutzerdefinierte Untermenüpunkte hinzufügen

        // Prüfe, ob die Untermenüpunkte bereits existieren
        global $submenu;
        $submenu_exists = is_array($submenu) && isset($submenu['edit.php?post_type=athena-feed']);

        // Benutzerdefinierte Untermenüpunkte nur hinzufügen, wenn das Hauptmenü existiert, aber die Untermenüpunkte noch nicht
        if (!$submenu_exists) {
            // WordPress erstellt automatisch einen "All Feeds"-Menüpunkt, daher müssen wir ihn nicht manuell hinzufügen

            // Add "Add New" submenu - wir vertrauen darauf, dass WordPress diesen Menüpunkt bereits erstellt hat

            // Add categories submenu
            add_submenu_page(
                'edit.php?post_type=athena-feed',
                __('Feed Categories', 'athena-ai'),
                __('Categories', 'athena-ai'),
                'manage_options',
                'edit-tags.php?taxonomy=athena-feed-category&post_type=athena-feed'
            );
            
            // Add ViewFeed News submenu
            add_submenu_page(
                'edit.php?post_type=athena-feed',
                __('ViewFeed News', 'athena-ai'),
                __('ViewFeed News', 'athena-ai'),
                'read',
                'athena-viewfeed-news',
                [\AthenaAI\Core\Plugin::class, 'render_viewfeed_news_page']
            );
        }
    }
}
