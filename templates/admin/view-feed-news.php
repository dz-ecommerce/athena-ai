<?php
if (!defined('ABSPATH')) exit;

// Get all feed posts
$feeds = get_posts([
    'post_type' => 'athena-feed',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
]);

?>
<div class="wrap athena-feed-news">
    <h1 class="wp-heading-inline"><?php echo esc_html__('ViewFeed News', 'athena-ai'); ?></h1>
    
    <?php if (empty($feeds)): ?>
        <div class="notice notice-warning">
            <p><?php echo esc_html__('No feeds found. Please add some feeds first.', 'athena-ai'); ?></p>
        </div>
    <?php else: ?>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder">
                <div id="post-body-content">
                    <div class="meta-box-sortables">
                        <?php foreach ($feeds as $feed): 
                            $feed_url = get_post_meta($feed->ID, '_athena_feed_url', true);
                            $feed_categories = get_the_terms($feed->ID, 'athena-feed-category');
                            
                            // Fetch RSS Feed
                            $rss = fetch_feed($feed_url);
                            
                            if (!is_wp_error($rss)):
                                $maxitems = $rss->get_item_quantity(10); // Get the latest 10 items
                                $rss_items = $rss->get_items(0, $maxitems);
                            ?>
                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle">
                                        <span class="dashicons dashicons-rss"></span>
                                        <?php echo esc_html($feed->post_title); ?>
                                        <?php if ($feed_categories && !is_wp_error($feed_categories)): ?>
                                            <span class="feed-categories">
                                                <span class="dashicons dashicons-category"></span>
                                                <?php echo esc_html(implode(', ', wp_list_pluck($feed_categories, 'name'))); ?>
                                            </span>
                                        <?php endif; ?>
                                    </h2>
                                </div>
                                <div class="inside">
                                    <?php if ($maxitems == 0): ?>
                                        <div class="notice notice-info notice-alt">
                                            <p><?php echo esc_html__('No items found in this feed.', 'athena-ai'); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <table class="wp-list-table widefat fixed striped">
                                            <tbody>
                                                <?php foreach ($rss_items as $item): 
                                                    $pub_date = $item->get_date('Y-m-d H:i:s');
                                                    $human_date = human_time_diff(strtotime($pub_date), current_time('timestamp'));
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <strong>
                                                                <a href="<?php echo esc_url($item->get_permalink()); ?>" target="_blank">
                                                                    <?php echo esc_html($item->get_title()); ?>
                                                                    <span class="dashicons dashicons-external"></span>
                                                                </a>
                                                            </strong>
                                                            <div class="row-actions">
                                                                <span class="date">
                                                                    <span class="dashicons dashicons-clock"></span>
                                                                    <?php printf(esc_html__('%s ago', 'athena-ai'), $human_date); ?>
                                                                </span>
                                                            </div>
                                                            <div class="feed-excerpt">
                                                                <?php 
                                                                $excerpt = wp_strip_all_tags($item->get_description());
                                                                echo esc_html(wp_trim_words($excerpt, 30, '...')); 
                                                                ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.athena-feed-news .postbox {
    margin-bottom: 20px;
}

.athena-feed-news .postbox-header {
    border-bottom: 1px solid #ccd0d4;
}

.athena-feed-news .hndle {
    display: flex !important;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.athena-feed-news .feed-categories {
    font-size: 12px;
    color: #666;
    font-weight: normal;
    display: flex;
    align-items: center;
    gap: 5px;
    margin-left: auto;
}

.athena-feed-news .feed-categories .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.athena-feed-news .inside {
    margin: 0;
    padding: 0;
}

.athena-feed-news .wp-list-table {
    border: none;
    border-spacing: 0;
}

.athena-feed-news .wp-list-table td {
    padding: 12px 15px;
}

.athena-feed-news .wp-list-table a {
    text-decoration: none;
    color: #2271b1;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.athena-feed-news .wp-list-table a:hover {
    color: #135e96;
}

.athena-feed-news .wp-list-table .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.athena-feed-news .row-actions {
    color: #666;
    font-size: 12px;
    padding: 4px 0;
}

.athena-feed-news .row-actions .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    vertical-align: text-bottom;
}

.athena-feed-news .feed-excerpt {
    color: #50575e;
    font-size: 13px;
    line-height: 1.5;
    margin-top: 4px;
}

@media screen and (max-width: 782px) {
    .athena-feed-news .hndle {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .athena-feed-news .feed-categories {
        margin-left: 0;
        margin-top: 5px;
    }
}
</style>
