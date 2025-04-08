<?php
/**
 * Template for the Feeds page
 *
 * @package AthenaAI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get category from query var if available
$category = get_query_var('athena_feed_category', '');
$category_term = !empty($category) ? get_term_by('slug', $category, 'athena-feed-category') : null;

// Set page title
$title = $category_term ? sprintf(__('Feeds: %s', 'athena-ai'), $category_term->name) : __('All Feeds', 'athena-ai');

// Get admin header
require_once ABSPATH . 'wp-admin/admin-header.php';
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($title); ?></h1>
    <hr class="wp-header-end">
    
    <div class="athena-feed-categories">
        <h2><?php esc_html_e('Categories', 'athena-ai'); ?></h2>
        <ul class="athena-feed-category-list">
            <li class="<?php echo empty($category) ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(admin_url('admin.php?page=athena-view-feeds')); ?>">
                    <?php esc_html_e('All', 'athena-ai'); ?>
                </a>
            </li>
            <?php
            $categories = get_terms([
                'taxonomy' => 'athena-feed-category',
                'hide_empty' => true,
            ]);
            
            foreach ($categories as $cat) {
                $is_active = $category === $cat->slug;
                printf(
                    '<li class="%s"><a href="%s">%s</a></li>',
                    $is_active ? 'active' : '',
                    esc_url(admin_url('admin.php?page=athena-view-feeds&category=' . $cat->slug)),
                    esc_html($cat->name)
                );
            }
            ?>
        </ul>
    </div>

    <div class="athena-feeds-content">
        <?php 
        // Get feeds for the selected category
        $feed_display = new AthenaAI\Frontend\FeedDisplay();
        $feeds = $feed_display->get_feeds($category, 10);
        
        if (empty($feeds)) {
            echo '<div class="notice notice-info"><p>' . esc_html__('No feeds found for this category.', 'athena-ai') . '</p></div>';
        } else {
            // Display feeds in admin table format
            ?>
            <table class="wp-list-table widefat fixed striped athena-feeds-table">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('Feed', 'athena-ai'); ?></th>
                        <th scope="col"><?php esc_html_e('Items', 'athena-ai'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feeds as $feed): ?>
                        <tr>
                            <td class="feed-title column-primary">
                                <strong><?php echo esc_html($feed['title']); ?></strong>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="<?php echo esc_url($feed['url']); ?>" target="_blank" rel="noopener">
                                            <?php esc_html_e('View Source', 'athena-ai'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($feed['items'])): ?>
                                    <div class="feed-items-container">
                                        <ul class="feed-items-list">
                                            <?php foreach ($feed['items'] as $index => $item): ?>
                                                <?php if ($index < 5): // Show only first 5 items ?>
                                                    <li class="feed-item">
                                                        <a href="<?php echo esc_url($item['permalink']); ?>" target="_blank" rel="noopener">
                                                            <?php echo esc_html($item['title']); ?>
                                                        </a>
                                                        <?php if (!empty($item['date'])): ?>
                                                            <span class="feed-date">
                                                                <?php echo esc_html(date_i18n(get_option('date_format'), $item['date'])); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php if (count($feed['items']) > 5): ?>
                                            <div class="feed-items-more">
                                                <button type="button" class="button-link feed-items-toggle">
                                                    <?php 
                                                    printf(
                                                        /* translators: %d: number of additional items */
                                                        esc_html__('Show %d more items', 'athena-ai'), 
                                                        count($feed['items']) - 5
                                                    ); 
                                                    ?>
                                                </button>
                                                <ul class="feed-items-list feed-items-hidden">
                                                    <?php foreach ($feed['items'] as $index => $item): ?>
                                                        <?php if ($index >= 5): // Show remaining items ?>
                                                            <li class="feed-item">
                                                                <a href="<?php echo esc_url($item['permalink']); ?>" target="_blank" rel="noopener">
                                                                    <?php echo esc_html($item['title']); ?>
                                                                </a>
                                                                <?php if (!empty($item['date'])): ?>
                                                                    <span class="feed-date">
                                                                        <?php echo esc_html(date_i18n(get_option('date_format'), $item['date'])); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </li>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <p><?php esc_html_e('No items found in this feed.', 'athena-ai'); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }
        ?>
    </div>
</div>

<style>
.athena-feed-categories {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.athena-feed-category-list {
    display: flex;
    flex-wrap: wrap;
    list-style: none;
    margin: 10px 0;
    padding: 0;
    gap: 10px;
}

.athena-feed-category-list li {
    margin: 0;
    padding: 0;
}

.athena-feed-category-list li a {
    display: inline-block;
    padding: 5px 10px;
    background: #f6f7f7;
    border: 1px solid #ccd0d4;
    border-radius: 3px;
    color: #555;
    text-decoration: none;
    font-size: 13px;
}

.athena-feed-category-list li a:hover {
    background: #f0f0f1;
    color: #0073aa;
}

.athena-feed-category-list li.active a {
    background: #0073aa;
    border-color: #006799;
    color: white;
}

.athena-feeds-content {
    margin-top: 20px;
}

.feed-items-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.feed-item {
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f0f0f1;
}

.feed-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.feed-date {
    display: block;
    color: #72777c;
    font-size: 12px;
    margin-top: 2px;
}

.feed-items-hidden {
    display: none;
}

.feed-items-toggle {
    margin-top: 10px;
    color: #0073aa;
    cursor: pointer;
}

.feed-items-toggle:hover {
    color: #00a0d2;
}

.feed-items-more {
    margin-top: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle feed items
    $('.feed-items-toggle').on('click', function() {
        var $this = $(this);
        var $hiddenItems = $this.siblings('.feed-items-hidden');
        
        if ($hiddenItems.is(':visible')) {
            $hiddenItems.slideUp();
            $this.text($this.text().replace('Hide', 'Show'));
        } else {
            $hiddenItems.slideDown();
            $this.text($this.text().replace('Show', 'Hide'));
        }
    });
});
</script>

<?php
// Get admin footer
require_once ABSPATH . 'wp-admin/admin-footer.php';
?>
