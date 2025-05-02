<?php
if (!defined('ABSPATH')) {
    exit();
}

// Ensure title is set
$title = isset($title) ? $title : __('Athena AI', 'athena-ai');
?>
<div class="wrap">
    <h1><?php echo esc_html($title); ?></h1>
    
    <?php settings_errors('athena_ai_messages'); ?>
    
    <?php if (isset($description)): ?>
        <p class="description"><?php echo esc_html($description); ?></p>
    <?php endif; ?>
</div> 