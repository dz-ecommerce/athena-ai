<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Get all feed posts
$feeds = get_posts([
    'post_type' => 'athena-feed',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
]);

?>
<div class="wrap">
    <h1><?php echo esc_html__('ViewFeed News', 'athena-ai'); ?></h1>
    
    <?php if (empty($feeds)): ?>
        <p><?php echo esc_html__('No feeds found.', 'athena-ai'); ?></p>
    <?php else: ?>
        <?php foreach ($feeds as $feed): 
            $feed_url = get_post_meta($feed->ID, '_athena_feed_url', true);
            $feed_categories = get_the_terms($feed->ID, 'athena-feed-category');
            
            // Fetch RSS Feed
            $rss = fetch_feed($feed_url);
            
            if (!is_wp_error($rss)):
                $maxitems = $rss->get_item_quantity(10); // Get the latest 10 items
                $rss_items = $rss->get_items(0, $maxitems);
            ?>
            <div class="athena-feed-container">
                <h2><?php echo esc_html($feed->post_title); ?></h2>
                
                <?php if ($feed_categories && !is_wp_error($feed_categories)): ?>
                    <p class="feed-categories">
                        <strong><?php echo esc_html__('Categories:', 'athena-ai'); ?></strong>
                        <?php echo esc_html(implode(', ', wp_list_pluck($feed_categories, 'name'))); ?>
                    </p>
                <?php endif; ?>
                
                <?php if ($maxitems == 0): ?>
                    <p><?php echo esc_html__('No items found.', 'athena-ai'); ?></p>
                <?php else: ?>
                    <ul class="feed-items">
                        <?php foreach ($rss_items as $item): ?>
                            <li class="feed-item">
                                <h3>
                                    <a href="<?php echo esc_url($item->get_permalink()); ?>" target="_blank">
                                        <?php echo esc_html($item->get_title()); ?>
                                    </a>
                                </h3>
                                <p class="feed-date">
                                    <?php echo esc_html($item->get_date('j F Y | g:i a')); ?>
                                </p>
                                <div class="feed-excerpt">
                                    <?php echo wp_kses_post($item->get_description()); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.athena-feed-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-bottom: 20px;
    padding: 15px;
}

.athena-feed-container h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.feed-categories {
    color: #666;
    margin: 5px 0 15px;
}

.feed-items {
    margin: 0;
    padding: 0;
    list-style: none;
}

.feed-item {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.feed-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.feed-item h3 {
    margin: 0 0 5px;
    font-size: 16px;
}

.feed-item h3 a {
    text-decoration: none;
}

.feed-date {
    color: #666;
    margin: 0 0 10px;
    font-size: 13px;
}

.feed-excerpt {
    font-size: 14px;
    line-height: 1.5;
}

.feed-excerpt img {
    max-width: 100%;
    height: auto;
}
</style>
