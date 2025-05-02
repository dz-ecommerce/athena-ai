<?php
if (!defined('ABSPATH')) {
    exit();
}

$this->render_template('header');
?>

<div class="athena-ai-overview">
    <div class="athena-ai-card">
        <h2><?php echo esc_html($this->__('Quick Stats')); ?></h2>
        <div class="athena-ai-stats">
            <div class="stat-item">
                <span class="stat-value">0</span>
                <span class="stat-label"><?php echo esc_html(
                    $this->__('Total Interactions')
                ); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-value">0</span>
                <span class="stat-label"><?php echo esc_html(
                    $this->__('Active Features')
                ); ?></span>
            </div>
        </div>
    </div>

    <div class="athena-ai-card">
        <h2><?php echo esc_html($this->__('Recent Activity')); ?></h2>
        <p><?php echo esc_html($this->__('No recent activity to display.')); ?></p>
    </div>
</div> 