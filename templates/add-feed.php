<?php
/**
 * Template for adding a new feed
 */
?>
<div class="wrap">
    <h1><?php echo esc_html($title); ?></h1>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php echo $nonce_field; ?>
        <input type="hidden" name="action" value="athena_ai_add_feed">

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="feed_title"><?php esc_html_e('Feed Title', 'athena-ai'); ?></label>
                </th>
                <td>
                    <input name="feed_title" type="text" id="feed_title" class="regular-text" required>
                    <p class="description"><?php esc_html_e(
                        'Enter a name for this feed',
                        'athena-ai'
                    ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="feed_url"><?php esc_html_e('Feed URL', 'athena-ai'); ?></label>
                </th>
                <td>
                    <input name="feed_url" type="url" id="feed_url" class="regular-text" required>
                    <p class="description"><?php esc_html_e(
                        'Enter the URL of the RSS/Atom feed',
                        'athena-ai'
                    ); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Add Feed', 'athena-ai')); ?>
    </form>
</div>
