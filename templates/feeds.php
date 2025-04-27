<?php
/**
 * Template for displaying all feeds
 */
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($title); ?></h1>
    <a href="<?php echo \AthenaAI\Core\SafetyWrapper::esc_url(admin_url('admin.php?page=athena-ai-add-feed')); ?>" class="page-title-action"><?php esc_html_e('Add New', 'athena-ai'); ?></a>
    <hr class="wp-header-end">

    <?php echo $nonce_field; ?>

    <?php if (empty($feeds)): ?>
        <div class="notice notice-info">
            <p><?php esc_html_e('No feeds found. Click "Add New" to create your first feed.', 'athena-ai'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Title', 'athena-ai'); ?></th>
                    <th scope="col"><?php esc_html_e('URL', 'athena-ai'); ?></th>
                    <th scope="col"><?php esc_html_e('Last Updated', 'athena-ai'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'athena-ai'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feeds as $feed): ?>
                    <tr>
                        <td>
                            <strong>
                                <a href="<?php echo \AthenaAI\Core\SafetyWrapper::esc_url(admin_url('post.php?post=' . $feed['id'] . '&action=edit')); ?>">
                                    <?php echo esc_html($feed['title']); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo \AthenaAI\Core\SafetyWrapper::esc_url($feed['url']); ?></td>
                        <td>
                            <?php 
                            if ($feed['last_updated']) {
                                echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($feed['last_updated'])));
                            } else {
                                esc_html_e('Never', 'athena-ai');
                            }
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $feed['id'] . '&action=edit')); ?>" class="button button-small">
                                <?php esc_html_e('Edit', 'athena-ai'); ?>
                            </a>
                            <a href="<?php echo \AthenaAI\Core\SafetyWrapper::esc_url(wp_nonce_url(admin_url('admin-post.php?action=athena_ai_refresh_feed&feed_id=' . $feed['id']), 'refresh_feed_' . $feed['id'])); ?>" class="button button-small">
                                <?php esc_html_e('Refresh', 'athena-ai'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
