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
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($title); ?></h1>
    <hr class="wp-header-end">
    
    <div class="athena-feed-categories">
        <h2><?php esc_html_e('Categories', 'athena-ai'); ?></h2>
        <ul class="athena-feed-category-list">
            <li class="<?php echo empty($category) ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('athena-feeds'))); ?>">
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
                    esc_url(home_url('/athena-feeds/category/' . $cat->slug . '/')),
                    esc_html($cat->name)
                );
            }
            ?>
        </ul>
    </div>

    <div class="athena-feeds-content">
        <?php echo do_shortcode('[athena_feeds category="' . esc_attr($category) . '" limit="10"]'); ?>
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
</style>
