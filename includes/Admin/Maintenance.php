<?php
/**
 * Maintenance class for database operations
 *
 * @package AthenaAI\Admin
 */

namespace AthenaAI\Admin;

/**
 * Maintenance class
 */
class Maintenance {
    /**
     * Initialize the Maintenance page
     */
    public static function init(): void {
        // Menü wird jetzt in AdminBootstrap registriert
        // add_action('admin_menu', [self::class, 'register_maintenance_page']);

        // Register AJAX handlers
        add_action('wp_ajax_athena_ai_truncate_feed_items', [
            self::class,
            'ajax_truncate_feed_items',
        ]);
        add_action('wp_ajax_athena_ai_drop_feed_items_table', [
            self::class,
            'ajax_drop_feed_items_table',
        ]);
        add_action('wp_ajax_athena_ai_create_feed_items_table', [
            self::class,
            'ajax_create_feed_items_table',
        ]);
    }

    /**
     * Register the maintenance page
     */
    public static function register_maintenance_page(): void {
        // Add as submenu under Feed Items
        add_submenu_page(
            'athena-feed-items',
            __('Feed Maintenance', 'athena-ai'),
            __('Maintenance', 'athena-ai'),
            'manage_options',
            'athena-feed-maintenance',
            [self::class, 'render_maintenance_page']
        );
    }

    /**
     * Render the maintenance page
     */
    public static function render_maintenance_page(): void {
        // Ensure user has proper permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'athena-ai'));
        }

        // Get table information
        global $wpdb;
        $feed_items_table = $wpdb->prefix . 'feed_raw_items';
        $feed_metadata_table = $wpdb->prefix . 'feed_metadata';

        $feed_items_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $feed_items_table");
        $feed_metadata_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $feed_metadata_table");

        $feed_items_exists =
            $wpdb->get_var("SHOW TABLES LIKE '$feed_items_table'") === $feed_items_table;
        $feed_metadata_exists =
            $wpdb->get_var("SHOW TABLES LIKE '$feed_metadata_table'") === $feed_metadata_table;

        // Page output
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Feed Maintenance', 'athena-ai') . '</h1>';

        // Display notices
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'success';

            echo '<div class="notice notice-' .
                esc_attr($type) .
                ' is-dismissible"><p>' .
                esc_html($message) .
                '</p></div>';
        }

        // Database Tables Status
        echo '<h2>' . esc_html__('Database Tables Status', 'athena-ai') . '</h2>';
        echo '<table class="widefat" style="max-width: 600px; margin-bottom: 20px;">';
        echo '<thead><tr><th>' .
            esc_html__('Table', 'athena-ai') .
            '</th><th>' .
            esc_html__('Status', 'athena-ai') .
            '</th><th>' .
            esc_html__('Records', 'athena-ai') .
            '</th></tr></thead>';
        echo '<tbody>';

        // Feed Items table
        echo '<tr>';
        echo '<td><code>' . esc_html($feed_items_table) . '</code></td>';
        echo '<td>' .
            ($feed_items_exists
                ? '<span style="color: green;">' . esc_html__('Exists', 'athena-ai') . '</span>'
                : '<span style="color: red;">' . esc_html__('Missing', 'athena-ai') . '</span>') .
            '</td>';
        echo '<td>' . ($feed_items_exists ? esc_html($feed_items_count) : '-') . '</td>';
        echo '</tr>';

        // Feed Metadata table
        echo '<tr>';
        echo '<td><code>' . esc_html($feed_metadata_table) . '</code></td>';
        echo '<td>' .
            ($feed_metadata_exists
                ? '<span style="color: green;">' . esc_html__('Exists', 'athena-ai') . '</span>'
                : '<span style="color: red;">' . esc_html__('Missing', 'athena-ai') . '</span>') .
            '</td>';
        echo '<td>' . ($feed_metadata_exists ? esc_html($feed_metadata_count) : '-') . '</td>';
        echo '</tr>';

        echo '</tbody></table>';

        // Maintenance Actions
        echo '<h2>' . esc_html__('Maintenance Actions', 'athena-ai') . '</h2>';
        echo '<div class="card" style="max-width: 600px; padding: 20px; margin-bottom: 20px;">';
        echo '<h3>' . esc_html__('Feed Items Operations', 'athena-ai') . '</h3>';
        echo '<p>' .
            esc_html__(
                'These operations allow you to manage feed items data for testing and troubleshooting.',
                'athena-ai'
            ) .
            '</p>';

        // Truncate feed items (empty table)
        echo '<p><button type="button" id="truncate-feed-items" class="button button-secondary">' .
            esc_html__('Empty Feed Items Table', 'athena-ai') .
            '</button> <span class="description">' .
            esc_html__('Removes all feed items but keeps the table structure.', 'athena-ai') .
            '</span></p>';

        // Drop feed items table
        echo '<p><button type="button" id="drop-feed-items-table" class="button button-secondary">' .
            esc_html__('Drop Feed Items Table', 'athena-ai') .
            '</button> <span class="description">' .
            esc_html__('Completely removes the feed items table from the database.', 'athena-ai') .
            '</span></p>';

        // Create feed items table
        echo '<p><button type="button" id="create-feed-items-table" class="button button-primary">' .
            esc_html__('Create Feed Items Table', 'athena-ai') .
            '</button> <span class="description">' .
            esc_html__('Creates the feed items table if it does not exist.', 'athena-ai') .
            '</span></p>';

        echo '</div>';

        // Debug Information
        echo '<h2>' . esc_html__('Debug Information', 'athena-ai') . '</h2>';
        echo '<div id="debug-output" class="card" style="max-width: 600px; padding: 20px; margin-bottom: 20px; max-height: 300px; overflow: auto; background-color: #f0f0f0;">';
        echo '<p><em>' .
            esc_html__('Debug information will appear here...', 'athena-ai') .
            '</em></p>';
        echo '</div>';
        // JavaScript for AJAX interactions
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Function to add message to debug output
            function addDebugMessage(message, isError) {
                var $debug = $('#debug-output');
                var $message = $('<p>').text(new Date().toLocaleTimeString() + ': ' + message);
                if (isError) {
                    $message.css('color', 'red');
                }
                $debug.prepend($message);
            }
            
            // Truncate feed items
            $('#truncate-feed-items').on('click', function() {
                if (!confirm('<?php echo esc_js(
                    __(
                        'Are you sure you want to empty the feed items table? This cannot be undone.',
                        'athena-ai'
                    )
                ); ?>')) {
                    return;
                }
                
                addDebugMessage('<?php echo esc_js(
                    __('Emptying feed items table...', 'athena-ai')
                ); ?>');
                $(this).prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'athena_ai_truncate_feed_items',
                        nonce: '<?php echo wp_create_nonce('athena_ai_maintenance'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            addDebugMessage(response.data.message);
                            // Reload the page to update counts
                            location.reload();
                        } else {
                            addDebugMessage(response.data.message, true);
                        }
                    },
                    error: function() {
                        addDebugMessage('<?php echo esc_js(
                            __('AJAX error occurred.', 'athena-ai')
                        ); ?>', true);
                        $('#truncate-feed-items').prop('disabled', false);
                    }
                });
            });
            
            // Drop feed items table
            $('#drop-feed-items-table').on('click', function() {
                if (!confirm('<?php echo esc_js(
                    __(
                        'Are you sure you want to completely remove the feed items table? This cannot be undone.',
                        'athena-ai'
                    )
                ); ?>')) {
                    return;
                }
                
                addDebugMessage('<?php echo esc_js(
                    __('Dropping feed items table...', 'athena-ai')
                ); ?>');
                $(this).prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'athena_ai_drop_feed_items_table',
                        nonce: '<?php echo wp_create_nonce('athena_ai_maintenance'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            addDebugMessage(response.data.message);
                            // Reload the page to update status
                            location.reload();
                        } else {
                            addDebugMessage(response.data.message, true);
                        }
                    },
                    error: function() {
                        addDebugMessage('<?php echo esc_js(
                            __('AJAX error occurred.', 'athena-ai')
                        ); ?>', true);
                        $('#drop-feed-items-table').prop('disabled', false);
                    }
                });
            });
            
            // Create feed items table
            $('#create-feed-items-table').on('click', function() {
                addDebugMessage('<?php echo esc_js(
                    __('Creating feed items table...', 'athena-ai')
                ); ?>');
                $(this).prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'athena_ai_create_feed_items_table',
                        nonce: '<?php echo wp_create_nonce('athena_ai_maintenance'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            addDebugMessage(response.data.message);
                            // Reload the page to update status
                            location.reload();
                        } else {
                            addDebugMessage(response.data.message, true);
                        }
                    },
                    error: function() {
                        addDebugMessage('<?php echo esc_js(
                            __('AJAX error occurred.', 'athena-ai')
                        ); ?>', true);
                        $('#create-feed-items-table').prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php echo '</div>'; // End of .wrap
    }

    /**
     * AJAX handler for truncating feed items table
     */
    public static function ajax_truncate_feed_items(): void {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'athena_ai_maintenance')) {
            wp_send_json_error(['message' => __('Security check failed.', 'athena-ai')]);
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'athena-ai'),
            ]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'feed_raw_items';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            wp_send_json_error(['message' => __('Feed items table does not exist.', 'athena-ai')]);
        }

        // Truncate table
        $result = $wpdb->query("TRUNCATE TABLE $table_name");

        if ($result !== false) {
            wp_send_json_success([
                'message' => __('Feed items table has been emptied successfully.', 'athena-ai'),
            ]);
        } else {
            wp_send_json_error([
                'message' =>
                    __('Failed to empty feed items table: ', 'athena-ai') . $wpdb->last_error,
            ]);
        }
    }

    /**
     * AJAX handler for dropping feed items table
     */
    public static function ajax_drop_feed_items_table(): void {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'athena_ai_maintenance')) {
            wp_send_json_error(['message' => __('Security check failed.', 'athena-ai')]);
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'athena-ai'),
            ]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'feed_raw_items';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            wp_send_json_error(['message' => __('Feed items table does not exist.', 'athena-ai')]);
        }

        // Drop table
        $result = $wpdb->query("DROP TABLE IF EXISTS $table_name");

        if ($result !== false) {
            wp_send_json_success([
                'message' => __('Feed items table has been dropped successfully.', 'athena-ai'),
            ]);
        } else {
            wp_send_json_error([
                'message' =>
                    __('Failed to drop feed items table: ', 'athena-ai') . $wpdb->last_error,
            ]);
        }
    }

    /**
     * AJAX handler for creating feed items table
     */
    public static function ajax_create_feed_items_table(): void {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'athena_ai_maintenance')) {
            wp_send_json_error(['message' => __('Security check failed.', 'athena-ai')]);
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have permission to perform this action.', 'athena-ai'),
            ]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'feed_raw_items';

        // Check if table already exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            wp_send_json_error(['message' => __('Feed items table already exists.', 'athena-ai')]);
        }

        // Include WordPress database upgrade functions
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // SQL to create the table
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            feed_id bigint(20) NOT NULL,
            guid varchar(255) NOT NULL,
            pub_date datetime NOT NULL,
            raw_content longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY guid (guid),
            KEY feed_id (feed_id),
            KEY pub_date (pub_date)
        ) $charset_collate;";

        // Create the table
        $result = dbDelta($sql);

        // Check if table was created
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            wp_send_json_success([
                'message' => __('Feed items table has been created successfully.', 'athena-ai'),
            ]);
        } else {
            wp_send_json_error([
                'message' =>
                    __('Failed to create feed items table. Error: ', 'athena-ai') .
                    $wpdb->last_error,
            ]);
        }
    }
}
