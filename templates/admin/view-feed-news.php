<?php
if (!defined('ABSPATH')) exit;

// Get all feed posts
$feeds = get_posts([
    'post_type' => 'athena-feed',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
]);

// Helper function to safely fetch feed items
function fetch_feed_items($feed_url) {
    try {
        // Use WordPress's built-in feed fetching
        $rss = fetch_feed($feed_url);
        
        if (is_wp_error($rss)) {
            return [
                'error' => $rss->get_error_message(),
                'items' => []
            ];
        }

        $maxitems = $rss->get_item_quantity(10);
        $items = $rss->get_items(0, $maxitems);

        return [
            'error' => null,
            'items' => $items
        ];
    } catch (\Exception $e) {
        return [
            'error' => $e->getMessage(),
            'items' => []
        ];
    }
}

// Helper function to get and validate thumbnail
function get_feed_item_thumbnail($item, $feed_link) {
    if (!$item) return '';
    
    $thumbnail = '';
    
    // 1. Try to get image from enclosure
    $enclosure = $item->get_enclosure();
    if ($enclosure && 
        method_exists($enclosure, 'get_link') && 
        method_exists($enclosure, 'get_type')) {
        $link = $enclosure->get_link();
        $type = $enclosure->get_type();
        if ($link && $type && str_starts_with($type, 'image/')) {
            $thumbnail = $link;
        }
    }
    
    // 2. Try to get image from media:content
    if (!$thumbnail) {
        $mediaContent = $item->get_item_tags('http://search.yahoo.com/mrss/', 'content');
        if ($mediaContent && isset($mediaContent[0]['attribs']['']['url'])) {
            $thumbnail = $mediaContent[0]['attribs']['']['url'];
        }
    }
    
    // 3. Try to get image from media:thumbnail
    if (!$thumbnail) {
        $mediaThumbnail = $item->get_item_tags('http://search.yahoo.com/mrss/', 'thumbnail');
        if ($mediaThumbnail && isset($mediaThumbnail[0]['attribs']['']['url'])) {
            $thumbnail = $mediaThumbnail[0]['attribs']['']['url'];
        }
    }
    
    // 4. Try to find first image in content
    if (!$thumbnail) {
        $content = $item->get_content();
        if ($content) {
            preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches);
            if (!empty($matches[1])) {
                $thumbnail = $matches[1];
            }
        }
    }
    
    // 5. Try to find image in description
    if (!$thumbnail) {
        $description = $item->get_description();
        if ($description) {
            preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $description, $matches);
            if (!empty($matches[1])) {
                $thumbnail = $matches[1];
            }
        }
    }

    // Validate and clean thumbnail URL
    if ($thumbnail) {
        // Convert relative URLs to absolute
        if (!str_starts_with($thumbnail, 'http')) {
            $parsed_url = wp_parse_url($feed_link);
            if ($parsed_url && isset($parsed_url['scheme'], $parsed_url['host'])) {
                $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                
                if (str_starts_with($thumbnail, '//')) {
                    $thumbnail = $parsed_url['scheme'] . ':' . $thumbnail;
                } elseif (str_starts_with($thumbnail, '/')) {
                    $thumbnail = $base_url . $thumbnail;
                } else {
                    $thumbnail = $base_url . '/' . $thumbnail;
                }
            }
        }
        
        return esc_url($thumbnail);
    }
    
    return '';
}

?>
<div class="wrap athena-feed-news">
    <h1 class="wp-heading-inline"><?php echo esc_html__('View Feed News', 'athena-ai'); ?></h1>
    
    <?php if (empty($feeds)): ?>
        <div class="notice notice-warning">
            <p><?php echo esc_html__('No feeds found. Please add some feeds first.', 'athena-ai'); ?></p>
        </div>
    <?php else: ?>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder">
                <div id="post-body-content">
                    <div class="meta-box-sortables">
                        <?php 
                        foreach ($feeds as $feed): 
                            $feed_url = get_post_meta($feed->ID, '_athena_feed_url', true);
                            $feed_categories = get_the_terms($feed->ID, 'athena-feed-category');
                            
                            // Get cached feed items
                            $result = fetch_feed_items($feed_url);
                            
                            if ($result['error']): ?>
                                <div class="notice notice-error notice-alt">
                                    <p><?php printf(
                                        esc_html__('Error loading feed: %s. Please check the feed URL and try again.', 'athena-ai'),
                                        esc_html($feed->post_title)
                                    ); ?></p>
                                </div>
                                <?php continue;
                            endif;
                            ?>
                            <div class="postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle">
                                        <span class="dashicons dashicons-rss"></span>
                                        <?php echo esc_html($feed->post_title); ?>
                                        <?php if ($feed_categories && !is_wp_error($feed_categories)): ?>
                                            <span class="feed-categories">
                                                <span class="dashicons dashicons-category"></span>
                                                <?php 
                                                $category_links = array_map(function($category) {
                                                    return sprintf(
                                                        '<a href="%s">%s</a>',
                                                        esc_url(admin_url('edit.php?post_type=athena-feed&athena-feed-category=' . $category->slug)),
                                                        esc_html($category->name)
                                                    );
                                                }, $feed_categories);
                                                echo implode(', ', $category_links);
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </h2>
                                </div>
                                <div class="inside">
                                    <?php if (empty($result['items'])): ?>
                                        <div class="notice notice-info notice-alt">
                                            <p><?php echo esc_html__('No items found in this feed.', 'athena-ai'); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <table class="wp-list-table widefat fixed striped">
                                            <tbody>
                                                <?php foreach ($result['items'] as $item): 
                                                    $pub_date = $item->get_date('Y-m-d H:i:s');
                                                    $human_date = human_time_diff(strtotime($pub_date), current_time('timestamp'));
                                                    $thumbnail = get_feed_item_thumbnail($item, $item->get_permalink());
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <div class="feed-item-content">
                                                                <?php if ($thumbnail): ?>
                                                                <div class="feed-thumbnail">
                                                                    <img src="<?php echo esc_url($thumbnail); ?>" 
                                                                         alt="<?php echo esc_attr($item->get_title()); ?>"
                                                                         loading="lazy"
                                                                         crossorigin="anonymous"
                                                                         onerror="this.style.display='none'; console.log('Failed to load image:', this.src);"
                                                                         style="max-width: 150px; height: auto;">
                                                                </div>
                                                                <?php endif; ?>
                                                                <div class="feed-text">
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
                                                                        <?php 
                                                                        $categories = $item->get_categories();
                                                                        if (!empty($categories)): ?>
                                                                            <span class="item-categories">
                                                                                <span class="separator">|</span>
                                                                                <span class="dashicons dashicons-tag"></span>
                                                                                <?php 
                                                                                $category_names = array_map(function($category) {
                                                                                    return esc_html($category->get_label());
                                                                                }, $categories);
                                                                                echo implode(', ', $category_names);
                                                                                ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="feed-excerpt">
                                                                        <?php 
                                                                        $excerpt = wp_strip_all_tags($item->get_description());
                                                                        echo esc_html(wp_trim_words($excerpt, 30, '...')); 
                                                                        ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
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

.athena-feed-news .feed-categories a {
    text-decoration: none;
    color: #2271b1;
}

.athena-feed-news .feed-categories a:hover {
    color: #135e96;
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

.athena-feed-news .feed-item-content {
    display: flex;
    gap: 15px;
}

.athena-feed-news .feed-thumbnail {
    flex: 0 0 120px;
}

.athena-feed-news .feed-thumbnail img {
    width: 120px;
    height: 80px;
    object-fit: cover;
    border-radius: 3px;
}

.athena-feed-news .feed-text {
    flex: 1;
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
    display: flex;
    align-items: center;
    gap: 5px;
}

.athena-feed-news .row-actions .separator {
    margin: 0 5px;
    color: #dcdcde;
}

.athena-feed-news .row-actions .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    vertical-align: text-bottom;
}

.athena-feed-news .item-categories {
    display: flex;
    align-items: center;
    gap: 5px;
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

    .athena-feed-news .feed-item-content {
        flex-direction: column;
    }

    .athena-feed-news .feed-thumbnail {
        flex: 0 0 auto;
    }

    .athena-feed-news .feed-thumbnail img {
        width: 100%;
        height: auto;
        max-height: 200px;
    }
}
</style>
