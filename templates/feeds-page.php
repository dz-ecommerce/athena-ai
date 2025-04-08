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

// Check if we're in admin or frontend
$is_admin = is_admin();

// Only include header if not in admin
if (!$is_admin) {
    get_header();
}
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <div class="athena-feeds-page">
            <header class="page-header">
                <h1 class="page-title">
                    <?php 
                    if ($category_term) {
                        printf(
                            /* translators: %s: category name */
                            esc_html__('Feeds: %s', 'athena-ai'),
                            esc_html($category_term->name)
                        );
                    } else {
                        esc_html_e('All Feeds', 'athena-ai');
                    }
                    ?>
                </h1>
            </header>

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
    </main><!-- #main -->
</div><!-- #primary -->

<?php
// Only include sidebar and footer if not in admin
if (!$is_admin) {
    get_sidebar();
    get_footer();
}
