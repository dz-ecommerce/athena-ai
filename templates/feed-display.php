<?php
/**
 * Template for displaying feeds below categories
 *
 * @package AthenaAI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

// Get category from query var if available
$category = get_query_var('athena_feed_category', '');
?>

<div class="athena-ai-feed-section">
    <h2 class="athena-ai-section-title"><?php echo esc_html__('Feeds', 'athena-ai'); ?></h2>
    
    <?php // Display feeds using the shortcode

echo do_shortcode('[athena_feeds category="' . esc_attr($category) . '" limit="10"]'); ?>
</div>
