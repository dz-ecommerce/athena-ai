<?php
/**
 * Template for the Feeds page
 *
 * @package AthenaAI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

// Get category from query var if available
$category = get_query_var('athena_feed_category', '');
$category_term = !empty($category) ? get_term_by('slug', $category, 'athena-feed-category') : null;

// Set page title
$title = $category_term
    ? sprintf(__('Feeds: %s', 'athena-ai'), $category_term->name)
    : __('All Feeds', 'athena-ai');

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <article class="page type-page status-publish hentry">
            <header class="entry-header">
                <h1 class="entry-title"><?php echo esc_html($title); ?></h1>
            </header><!-- .entry-header -->

            <div class="entry-content">
                <div class="athena-feed-categories">
                    <h2><?php esc_html_e('Categories', 'athena-ai'); ?></h2>
                    <ul class="athena-feed-category-list">
                        <li class="<?php echo empty($category) ? 'active' : ''; ?>">
                            <a href="<?php echo esc_url(
                                get_permalink(get_page_by_path('athena-feeds'))
                            ); ?>">
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
                    <?php echo do_shortcode(
                        '[athena_feeds category="' . esc_attr($category) . '" limit="10"]'
                    ); ?>
                </div>
            </div><!-- .entry-content -->
        </article><!-- #post-## -->
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_sidebar();
get_footer();


?>
