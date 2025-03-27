<?php
if (!defined('ABSPATH')) {
    exit;
}

$this->render_template('header');
?>

<div class="athena-ai-feed">
    <?php if (empty($items)): ?>
        <p><?php echo esc_html($this->__('No feed items available.')); ?></p>
    <?php else: ?>
        <div class="feed-items">
            <?php foreach ($items as $item): ?>
                <div class="feed-item">
                    <h3><?php echo esc_html($item['title']); ?></h3>
                    <div class="feed-content">
                        <?php echo wp_kses_post($item['content']); ?>
                    </div>
                    <div class="feed-meta">
                        <span class="feed-date">
                            <?php echo esc_html(
                                date_i18n(
                                    get_option('date_format') . ' ' . get_option('time_format'),
                                    strtotime($item['date'])
                                )
                            ); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div> 